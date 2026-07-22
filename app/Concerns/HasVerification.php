<?php

namespace App\Concerns;

use App\Enums\VerificationStatus;
use App\Notifications\DocumentRevoked;
use App\Observers\VerifiableObserver;
use Illuminate\Database\Eloquent\Builder;

trait HasVerification
{
    public static function bootHasVerification(): void
    {
        static::observe(VerifiableObserver::class);
    }

    public function initializeHasVerification(): void
    {
        $this->mergeCasts([
            'status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    /** Every verifiable model MUST declare its serial prefix, e.g. 'EMP', 'CERT', 'LTR'. */
    abstract public function verificationPrefix(): string;

    /** Payload hashed into verification_hash. Do NOT override — extend via extraVerificationFields(). */
    public function verificationPayload(): array
    {
        return array_merge([
            'id' => $this->getKey(),
            'class' => static::class,
            'serial' => $this->serial_number,
            'issued' => optional($this->created_at)->toIso8601String(),
        ], $this->extraVerificationFields());
    }

    /** Override in models to include model-specific fields in the hash. */
    protected function extraVerificationFields(): array
    {
        return [];
    }

    public function isValid(): bool
    {
        return $this->status === VerificationStatus::Valid;
    }

    public function revoke(?string $reason = null): void
    {
        $this->forceFill([
            'status' => VerificationStatus::Revoked,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ])->save();

        $notifiable = $this->getRevocationNotifiable();
        if ($notifiable && ($notifiable->email ?? null)) {
            $notifiable->notify(new DocumentRevoked($this));
        }
    }

    /** Override in models to route the revocation email to the right recipient. */
    public function getRevocationNotifiable()
    {
        return null;
    }

    public function scopeValid(Builder $q): Builder
    {
        return $q->where('status', VerificationStatus::Valid->value);
    }

    public function scopeByHash(Builder $q, string $hash): Builder
    {
        return $q->where('verification_hash', $hash);
    }
}
