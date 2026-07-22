<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Campaign extends Model
{
    use BelongsToOrganization, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code', 'name', 'description',
        'goal_amount', 'currency',
        'starts_on', 'ends_on', 'is_active',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'bool',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /** Sum of donation amounts against this campaign (BDT — currency conversion is out of scope). */
    public function raisedAmount(): float
    {
        return (float) $this->donations()->sum('amount');
    }

    public function progressPercent(): ?int
    {
        if (! $this->goal_amount || (float) $this->goal_amount <= 0) {
            return null;
        }

        return (int) min(100, round($this->raisedAmount() / (float) $this->goal_amount * 100));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
