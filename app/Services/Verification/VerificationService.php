<?php

namespace App\Services\Verification;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\MoneyReceipt;
use App\Models\OfficialLetter;
use Illuminate\Database\Eloquent\Model;

class VerificationService
{
    /** Registry of verifiable models — extend when new document types are introduced. */
    protected array $verifiables = [
        'EMP' => Employee::class,
        'CERT' => Certificate::class,
        'LTR' => OfficialLetter::class,
        'ID' => IdCard::class,
        'MR' => MoneyReceipt::class,
    ];

    /** Public accessor so admin pages can iterate the registry (e.g. serial-number lookup). */
    public function registry(): array
    {
        return $this->verifiables;
    }

    /**
     * Resolve a document by its verification_hash (preferred) or serial_number
     * (fallback, for manually-typed short verify URLs) across all registered types.
     * Uses acrossOrganizations() to bypass tenant scoping — verification must work
     * across all organizations (a user from Org A should be able to verify documents
     * from Org B via the public verify endpoint or inter-org integration).
     * Returns null when not found; never leaks which table matched on failure.
     */
    public function resolve(string $hash): ?Model
    {
        foreach ($this->verifiables as $class) {
            $model = $class::query()->acrossOrganizations()->byHash($hash)->first();
            if ($model) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Public-safe payload for the /verify/{hash} endpoint.
     * NEVER expose PII beyond what the QR is meant to prove.
     */
    public function publicSnapshot(Model $model): array
    {
        // Determine the correct issued_on date based on model type
        if ($model instanceof Certificate) {
            $issuedOn = $model->issued_on;
        } else {
            $issuedOn = $model->released_on ?? $model->verified_at ?? $model->created_at;
        }

        $snap = [
            'serial' => $model->serial_number,
            'kind' => class_basename($model),
            'status' => $model->status?->value,
            'issued_on' => optional($issuedOn)->toDateString(),
            'valid_until' => $model->valid_until ?? null,
            'valid_from' => null,
            'revoked_at' => optional($model->revoked_at)->toDateString(),
            'revocation_reason' => $model->revocation_reason,
            'is_valid' => $model->status === VerificationStatus::Valid,
            // Dynamic metadata — populated from model-specific fields below
            'recipient' => null,
            'signatory' => null,
            'purpose' => null,
        ];

        if ($model instanceof OfficialLetter) {
            $model->loadMissing(['signedBy.position', 'author.position', 'category']);
            $snap['recipient'] = $model->recipient_name ?: null;
            $snap['purpose'] = $model->subject ?: ($model->category?->name ?? null);
            $signer = $model->signedBy ?? $model->author;
            if ($signer) {
                $title = optional($signer->position)->title;
                $snap['signatory'] = $signer->full_name . ($title ? ', ' . $title : '');
            }
        }

        if ($model instanceof Certificate) {
            $model->loadMissing(['employee']);
            $snap['recipient'] = $model->recipient_name
                ?: (optional($model->employee)->full_name ?: null);
        }

        if ($model instanceof IdCard) {
            $model->loadMissing(['employee']);
            $snap['recipient'] = $model->displayName() ?: null;
            $snap['valid_from'] = optional($model->valid_from)->toDateString();
        }

        if ($model instanceof MoneyReceipt) {
            $snap['recipient'] = $model->payer_name;
            $snap['purpose'] = $model->purpose;
            $snap['issued_on'] = optional($model->receipt_date)->toDateString();
            // Receipt-specific fields — surfaced only when kind === 'MoneyReceipt'.
            $snap['amount'] = number_format((float) $model->amount, 2);
            $snap['currency'] = $model->currency;
            $snap['payment_method'] = $model->payment_method?->getLabel();
            $snap['reference_no'] = $model->reference_no;
            $snap['received_by'] = $model->received_by;
        }

        return $snap;
    }

    public function markExpired(Model $model): void
    {
        $model->forceFill(['status' => VerificationStatus::Expired])->save();
    }
}
