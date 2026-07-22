<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\PledgeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pledge extends Model
{
    use BelongsToOrganization, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id', 'person_id', 'campaign_id',
        'promised_amount', 'currency', 'fulfilled_amount',
        'due_date', 'status', 'last_reminder_sent_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'promised_amount' => 'decimal:2',
        'fulfilled_amount' => 'decimal:2',
        'due_date' => 'date',
        'status' => PledgeStatus::class,
        'last_reminder_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (empty($model->status)) {
                $model->status = PledgeStatus::Open;
            }
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === PledgeStatus::Overdue
            || ($this->status === PledgeStatus::Open && $this->due_date?->isPast());
    }

    public function outstandingAmount(): float
    {
        return max(0, (float) $this->promised_amount - (float) $this->fulfilled_amount);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
