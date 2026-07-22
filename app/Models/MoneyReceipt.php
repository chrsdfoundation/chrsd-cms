<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Concerns\HasVerification;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MoneyReceipt extends Model
{
    use BelongsToOrganization, HasVerification, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id', 'person_id', 'created_by',
        'receipt_date', 'payer_name',
        'amount', 'currency',
        'payment_method', 'reference_no',
        'purpose', 'received_by',
        'qr_code_uri',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
    ];

    public function verificationPrefix(): string
    {
        return 'MR';
    }

    /**
     * Fields folded into the tamper-evident hash. Amount + currency + payer are
     * the receipt's material contents — if any of them are changed post-issue,
     * the stored hash will no longer match a re-hash of the row.
     */
    protected function extraVerificationFields(): array
    {
        return [
            'amount' => (string) $this->amount,
            'currency' => $this->currency,
            'payer' => $this->payer_name,
            'method' => $this->payment_method?->value,
        ];
    }

    protected static function booted(): void
    {
        // Both hooks run inside the same `creating` event; Laravel dispatches
        // listeners in registration order. HasVerification's observer is
        // registered from bootHasVerification() (trait boot) which runs
        // BEFORE booted(), so the observer's `creating` handler assigns
        // serial_number first. This closure then reads that serial and fills
        // the QR URL, all within the same INSERT.
        static::creating(function (self $model): void {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (empty($model->qr_code_uri) && ! empty($model->serial_number)) {
                $model->qr_code_uri = static::buildVerificationUrl($model->serial_number);
            }
        });
    }

    /**
     * Build the QR verification URL. Prefers VERIFY_BASE_URL (the external
     * Website portal that renders verify pages for every document type via
     * the CMS's /api/verify/ref/{serial} endpoint) and falls back to APP_URL
     * so the CMS keeps serving verification standalone if no portal is
     * configured.
     */
    public static function buildVerificationUrl(string $serial): string
    {
        $base = rtrim(config('chrsd.verify_base_url') ?: config('app.url'), '/');

        return $base . '/verify/ref/' . $serial;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function donation(): HasOne
    {
        return $this->hasOne(Donation::class, 'money_receipt_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
