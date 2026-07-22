<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\PaymentMethod;
use App\Enums\RecurringCadence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Donation extends Model
{
    use BelongsToOrganization, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id', 'person_id', 'campaign_id',
        'money_receipt_id',
        'receipt_date', 'amount', 'currency',
        'payment_method', 'reference_no',
        'is_in_kind', 'in_kind_description', 'in_kind_valuation',
        'is_recurring', 'recurring_cadence', 'parent_donation_id',
        'acknowledged_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'in_kind_valuation' => 'decimal:2',
        'is_in_kind' => 'bool',
        'is_recurring' => 'bool',
        'payment_method' => PaymentMethod::class,
        'recurring_cadence' => RecurringCadence::class,
        'acknowledged_at' => 'datetime',
    ];

    // Mirror the DB defaults so the auto-receipt observer sees these values on
    // the in-memory model instance even before the row is re-read.
    protected $attributes = [
        'currency' => 'BDT',
        'is_in_kind' => false,
        'is_recurring' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Auto-create a linked MoneyReceipt for cash donations. In-kind
        // donations skip this — they get their own paper trail via the
        // in_kind_description + valuation on the Donation row itself.
        static::created(function (self $model): void {
            if ($model->is_in_kind || $model->money_receipt_id) {
                return;
            }
            $model->generateReceipt();
        });
    }

    public function generateReceipt(): ?MoneyReceipt
    {
        $this->loadMissing(['person', 'campaign']);

        if (! $this->person || $this->is_in_kind || $this->money_receipt_id) {
            return null;
        }

        $purpose = $this->campaign
            ? 'Donation towards ' . $this->campaign->name
            : ($this->notes ?: 'Charitable donation');

        $receipt = MoneyReceipt::create([
            'organization_id' => $this->organization_id,
            'person_id' => $this->person_id,
            'created_by' => $this->created_by,
            'receipt_date' => $this->receipt_date,
            'payer_name' => $this->person->full_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method?->value ?? 'cash',
            'reference_no' => $this->reference_no,
            'purpose' => $purpose,
            'received_by' => auth()->user()?->name,
        ]);

        $this->forceFill(['money_receipt_id' => $receipt->id])->saveQuietly();

        return $receipt;
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function moneyReceipt(): BelongsTo
    {
        return $this->belongsTo(MoneyReceipt::class);
    }

    public function parentDonation(): BelongsTo
    {
        return $this->belongsTo(Donation::class, 'parent_donation_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
