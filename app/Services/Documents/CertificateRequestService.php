<?php

namespace App\Services\Documents;

use App\Enums\CertificateIssuance;
use App\Enums\CertificateRequestStatus;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\User;
use App\Notifications\CertificateRequestApproved;
use App\Notifications\CertificateRequestRejected;
use App\Notifications\CertificateRequestSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CertificateRequestService
{
    public function __construct(protected CertificateGeneratorService $generator) {}

    /**
     * Portal-side entry point. Persists the request and pings HR.
     */
    public function submit(array $data): CertificateRequest
    {
        $req = CertificateRequest::create(array_merge($data, [
            'status' => CertificateRequestStatus::Pending,
        ]));

        $this->notifyHr($req);

        return $req;
    }

    /**
     * Approve a pending request → creates a Certificate → generates the PDF →
     * links the resulting cert back to the request → flips status to Issued →
     * emails the employee (with PDF attached).
     */
    public function approveAndIssue(
        CertificateRequest $request,
        User $reviewer,
        ?int $signatoryId = null,
        ?string $reviewNotes = null,
    ): Certificate {
        if (! $request->isPending()) {
            throw new \RuntimeException("Request {$request->id} is not pending (current: {$request->status?->value}).");
        }

        $cert = DB::transaction(function () use ($request, $reviewer, $signatoryId, $reviewNotes) {
            $request->loadMissing(['employee', 'type']);

            $cert = Certificate::create([
                'employee_id'         => $request->employee_id,
                'certificate_type_id' => $request->certificate_type_id,
                'signed_by_id'        => $signatoryId,
                'purpose'             => $request->purpose,
                'issuance_status'     => CertificateIssuance::Draft,
                'issued_on'           => now()->toDateString(),
                'valid_until'         => $request->type?->validity_days
                    ? now()->addDays($request->type->validity_days)->toDateString()
                    : null,
            ]);

            $this->generator->generate($cert);

            $request->forceFill([
                'status'                   => CertificateRequestStatus::Issued,
                'reviewed_by_id'           => $reviewer->id,
                'reviewed_at'              => now(),
                'review_notes'             => $reviewNotes,
                'resulting_certificate_id' => $cert->id,
            ])->save();

            return $cert;
        });

        // Notify OUTSIDE the transaction — mail failure never rolls back the
        // successful issuance (and the queued job stays independent of the DB).
        if ($request->employee?->email) {
            $request->employee->notify(new CertificateRequestApproved($request->fresh(['resultingCertificate.type', 'type'])));
        }

        return $cert;
    }

    public function reject(
        CertificateRequest $request,
        User $reviewer,
        string $reason,
    ): CertificateRequest {
        if (! $request->isPending()) {
            throw new \RuntimeException("Request {$request->id} is not pending (current: {$request->status?->value}).");
        }

        $request->forceFill([
            'status'         => CertificateRequestStatus::Rejected,
            'reviewed_by_id' => $reviewer->id,
            'reviewed_at'    => now(),
            'review_notes'   => $reason,
        ])->save();

        $request->refresh();

        if ($request->employee?->email) {
            $request->employee->notify(new CertificateRequestRejected($request));
        }

        return $request;
    }

    /**
     * Route "new request" notifications to HR. Priority:
     *   1. CMS_HR_INBOX env var → single address
     *   2. All users with any of the fallback roles that ACTUALLY EXIST
     *
     * Resilient to missing roles — someone deleting `hr_manager` from Shield
     * must not crash the portal submission flow.
     */
    protected function notifyHr(CertificateRequest $request): void
    {
        $inbox = config('cms.hr_inbox');

        if (! empty($inbox)) {
            Notification::route('mail', $inbox)
                ->notify(new CertificateRequestSubmitted($request));
            return;
        }

        $wanted = config('cms.hr_fallback_roles', ['hr_manager', 'super_admin']);
        $existing = \Spatie\Permission\Models\Role::query()
            ->whereIn('name', $wanted)
            ->pluck('name')
            ->all();

        if (empty($existing)) {
            return;
        }

        $recipients = User::query()
            ->role($existing)
            ->whereNotNull('email')
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CertificateRequestSubmitted($request));
        }
    }
}
