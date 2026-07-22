<?php

namespace App\Support\Blueprint;

use App\Enums\VerificationStatus;
use Illuminate\Database\Schema\Blueprint;

final class VerificationBlueprint
{
    /**
     * Adds the canonical verification columns to a table.
     * Call as: $table->verificationColumns();
     */
    public static function apply(Blueprint $table): void
    {
        $table->string('serial_number', 64)->nullable()->unique()
            ->comment('Human-readable sequence, e.g. EMP-2026-000123');

        $table->string('verification_hash', 64)->nullable()->unique()
            ->comment('HMAC-SHA256 hex digest, tamper-evident anchor');

        $table->string('status', 16)
            ->default(VerificationStatus::Valid->value)
            ->index()
            ->comment(implode('|', array_column(VerificationStatus::cases(), 'value')));

        $table->timestamp('verified_at')->nullable();
        $table->timestamp('revoked_at')->nullable();
        $table->text('revocation_reason')->nullable();
    }
}
