<?php

namespace App\Services\Reports;

use App\Enums\CertificateIssuance;
use App\Enums\OfficialLetterStatus;
use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficialLetter;
use App\Services\Documents\PrintHtmlService;
use App\Services\QrCodeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(protected PrintHtmlService $print) {}

    // ---- Department roster --------------------------------------------

    public function departmentRoster(?int $departmentId): array
    {
        $depts = Department::query()
            ->when($departmentId, fn ($q) => $q->where('id', $departmentId))
            ->with(['head', 'employees' => fn ($q) => $q->orderBy('last_name')->with('position')])
            ->orderBy('name')
            ->get();

        return [
            'generated_at' => now(),
            'departments' => $depts,
            'totals' => [
                'departments' => $depts->count(),
                'employees' => $depts->sum(fn ($d) => $d->employees->count()),
            ],
        ];
    }

    public function exportDepartmentRoster(?int $departmentId): StreamedResponse
    {
        $data = $this->departmentRoster($departmentId);

        $bytes = $this->pdf->renderView('documents.reports.department-roster', $data, [
            'format' => 'A4', 'orientation' => 'portrait',
        ]);

        return $this->download($bytes, sprintf('roster-%s.pdf', now()->format('Ymd-His')));
    }

    // ---- Monthly issuance summary -------------------------------------

    public function monthlyIssuance(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $certificates = Certificate::query()
            ->with(['employee.department', 'type'])
            ->whereBetween('issued_on', [$start, $end])
            ->whereIn('issuance_status', [
                CertificateIssuance::Generated->value,
                CertificateIssuance::Issued->value,
                CertificateIssuance::Delivered->value,
            ])
            ->orderBy('issued_on')
            ->get();

        $letters = OfficialLetter::query()
            ->with(['category', 'author.department'])
            ->whereBetween('released_on', [$start, $end])
            ->where('letter_status', OfficialLetterStatus::Released->value)
            ->orderBy('released_on')
            ->get();

        return [
            'generated_at' => now(),
            'period' => $month,
            'certificates' => $certificates,
            'letters' => $letters,
            'by_type' => $certificates->groupBy(fn ($c) => $c->type->code)
                ->map(fn (Collection $g) => $g->count()),
            'by_dept' => $certificates
                ->groupBy(fn ($c) => optional($c->employee->department)->name ?? 'Unassigned')
                ->map(fn (Collection $g) => $g->count()),
            'totals' => [
                'certificates' => $certificates->count(),
                'letters' => $letters->count(),
            ],
        ];
    }

    public function exportMonthlyIssuance(Carbon $month): StreamedResponse
    {
        $data = $this->monthlyIssuance($month);

        $bytes = $this->pdf->renderView('documents.reports.monthly-issuance', $data, [
            'format' => 'A4', 'orientation' => 'portrait',
        ]);

        return $this->download($bytes, sprintf('issuance-%s.pdf', $month->format('Y-m')));
    }

    // ---- Revocation register ------------------------------------------

    public function revocationRegister(Carbon $from, Carbon $to): array
    {
        $filter = fn ($q) => $q->where('status', VerificationStatus::Revoked->value)
            ->whereBetween('revoked_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('revoked_at');

        $rows = collect();

        $rows = $rows->merge(
            Employee::query()->tap($filter)->get()->map(fn (Employee $e) => [
                'kind' => 'Employee',
                'serial' => $e->serial_number,
                'label' => $e->full_name,
                'when' => $e->revoked_at,
                'reason' => $e->revocation_reason,
                'hash' => $e->verification_hash,
            ])
        );

        $rows = $rows->merge(
            Certificate::query()->tap($filter)->with(['employee', 'type'])->get()
                ->map(fn (Certificate $c) => [
                    'kind' => 'Certificate',
                    'serial' => $c->serial_number,
                    'label' => optional($c->type)->code . ' — ' . optional($c->employee)->full_name,
                    'when' => $c->revoked_at,
                    'reason' => $c->revocation_reason,
                    'hash' => $c->verification_hash,
                ])
        );

        $rows = $rows->merge(
            OfficialLetter::query()->tap($filter)->with('category')->get()
                ->map(fn (OfficialLetter $l) => [
                    'kind' => 'OfficialLetter',
                    'serial' => $l->serial_number,
                    'label' => optional($l->category)->code . ' — ' . $l->subject,
                    'when' => $l->revoked_at,
                    'reason' => $l->revocation_reason,
                    'hash' => $l->verification_hash,
                ])
        );

        return [
            'generated_at' => now(),
            'from' => $from,
            'to' => $to,
            'rows' => $rows->sortByDesc('when')->values(),
            'total' => $rows->count(),
        ];
    }

    public function exportRevocationRegister(Carbon $from, Carbon $to): StreamedResponse
    {
        $data = $this->revocationRegister($from, $to);

        $bytes = $this->pdf->renderView('documents.reports.revocation-register', $data, [
            'format' => 'A4', 'orientation' => 'portrait',
        ]);

        return $this->download($bytes, sprintf(
            'revocations-%s_%s.pdf',
            $from->format('Ymd'), $to->format('Ymd')
        ));
    }

    // ---- Employee service record --------------------------------------

    public function serviceRecord(Employee $employee): array
    {
        $employee->loadMissing([
            'department', 'position', 'supervisor',
            'history' => fn ($q) => $q->orderBy('occurred_on')->orderBy('id'),
        ]);

        return [
            'generated_at' => now(),
            'employee' => $employee,
            'history' => $employee->history,
            'qr_uri' => app(QrCodeService::class)->pngDataUri($employee),
            'verify_url' => app(QrCodeService::class)->verificationUrl($employee),
        ];
    }

    public function exportServiceRecord(Employee $employee): StreamedResponse
    {
        $data = $this->serviceRecord($employee);

        $bytes = $this->pdf->renderView('documents.reports.service-record', $data, [
            'format' => 'A4', 'orientation' => 'portrait',
        ]);

        return $this->download($bytes, sprintf(
            'service-record-%s.pdf',
            $employee->serial_number ?? $employee->id,
        ));
    }

    // ---- Compliance / audit bundle ------------------------------------

    /**
     * Build a single-PDF chain-of-custody bundle for compliance/legal review:
     *  - Cover page (period, purpose, generator)
     *  - Activity log excerpt (all actions in the period)
     *  - Revocation register (reused from revocationRegister())
     *  - Document register (all verifiable docs' current status + hash)
     *  - Cryptographic manifest — SHA-256 of the concatenated payload, keyed
     *    to APP_KEY, printed as a footer so anyone with the manifest can
     *    verify the bundle wasn't tampered with post-generation.
     */
    public function complianceBundle(Carbon $from, Carbon $to): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $activity = Activity::query()
            ->whereBetween('created_at', $range)
            ->with('causer')
            ->orderBy('created_at')
            ->get();

        $revocations = $this->revocationRegister($from, $to);

        $documents = collect();
        $documents = $documents->merge(
            Employee::query()->orderBy('serial_number')->get()->map(fn (Employee $e) => [
                'kind' => 'Employee',
                'serial' => $e->serial_number,
                'label' => $e->full_name,
                'status' => $e->status?->value,
                'hash' => $e->verification_hash,
            ])
        );
        $documents = $documents->merge(
            Certificate::query()->with('type', 'employee')->orderBy('serial_number')->get()->map(fn (Certificate $c) => [
                'kind' => 'Certificate',
                'serial' => $c->serial_number,
                'label' => optional($c->type)->code . ' — ' . optional($c->employee)->full_name,
                'status' => $c->status?->value,
                'hash' => $c->verification_hash,
            ])
        );
        $documents = $documents->merge(
            OfficialLetter::query()->with('category')->orderBy('serial_number')->get()->map(fn (OfficialLetter $l) => [
                'kind' => 'OfficialLetter',
                'serial' => $l->serial_number,
                'label' => optional($l->category)->code . ' — ' . $l->subject,
                'status' => $l->status?->value,
                'hash' => $l->verification_hash,
            ])
        );

        $payloadForManifest = [
            'app' => config('app.name'),
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'activity_ids' => $activity->pluck('id')->all(),
            'revoked_ids' => $revocations['rows']->pluck('serial')->all(),
            'doc_hashes' => $documents->pluck('hash')->all(),
        ];

        return [
            'generated_at' => now(),
            'from' => $from,
            'to' => $to,
            'activity' => $activity,
            'revocations' => $revocations,
            'documents' => $documents,
            'totals' => [
                'activity' => $activity->count(),
                'revocations' => $revocations['total'],
                'documents' => $documents->count(),
            ],
            'manifest' => $this->manifestHash($payloadForManifest),
        ];
    }

    public function exportComplianceBundle(Carbon $from, Carbon $to): StreamedResponse
    {
        $data = $this->complianceBundle($from, $to);

        $bytes = $this->pdf->renderView('documents.reports.compliance-bundle', $data, [
            'format' => 'A4', 'orientation' => 'portrait',
        ]);

        return $this->download($bytes, sprintf(
            'compliance-%s_%s.pdf',
            $from->format('Ymd'), $to->format('Ymd')
        ));
    }

    /**
     * HMAC-SHA256 of the sorted payload, keyed to APP_KEY. Anyone with the
     * same APP_KEY can recompute this manifest from the printed PDF contents
     * to verify the bundle was not modified after generation.
     */
    protected function manifestHash(array $payload): string
    {
        ksort($payload);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            config('app.key'),
        );
    }

    /** Stream a set of PDF bytes as a browser download. */
    protected function download(string $bytes, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print $bytes,
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    // ---- HTML Rendering (for browser print-to-PDF) ----------------------

    /**
     * Render monthly issuance report to HTML string.
     */
    public function renderMonthlyIssuanceHtml(Carbon $month): string
    {
        $data = $this->monthlyIssuance($month);

        return $this->print->render('print.reports.monthly-issuance', $data);
    }

    /**
     * Render department roster report to HTML string.
     */
    public function renderDepartmentRosterHtml(?int $departmentId): string
    {
        $data = $this->departmentRoster($departmentId);

        return $this->print->render('print.reports.department-roster', $data);
    }

    /**
     * Render compliance bundle report to HTML string.
     */
    public function renderComplianceBundleHtml(Carbon $from, Carbon $to): string
    {
        $data = $this->complianceBundle($from, $to);

        return $this->print->render('print.reports.compliance-bundle', $data);
    }
}
