<?php

namespace App\Observers;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VerifiableObserver
{
    public function creating(Model $model): void
    {
        if (empty($model->status)) {
            $model->status = VerificationStatus::Valid;
        }

        if (empty($model->serial_number)) {
            $model->serial_number = $this->nextSerial($model);
        }

        if (empty($model->verified_at)) {
            $model->verified_at = now();
        }
    }

    public function created(Model $model): void
    {
        if (empty($model->verification_hash)) {
            $model->forceFill([
                'verification_hash' => $this->computeHash($model),
            ])->saveQuietly();
        }
    }

    /** Generate PREFIX-YYYY-###### with per-year, per-model atomicity. */
    protected function nextSerial(Model $model): string
    {
        $prefix = $model->verificationPrefix();
        $year   = now()->year;
        $table  = $model->getTable();

        $seq = DB::transaction(function () use ($table, $prefix, $year) {
            $last = DB::table($table)
                ->where('serial_number', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('serial_number')
                ->value('serial_number');

            $lastSeq = $last ? (int) substr($last, strrpos($last, '-') + 1) : 0;
            return $lastSeq + 1;
        });

        return sprintf('%s-%d-%06d', $prefix, $year, $seq);
    }

    protected function computeHash(Model $model): string
    {
        $payload = $model->verificationPayload();
        ksort($payload);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            config('app.key')
        );
    }
}
