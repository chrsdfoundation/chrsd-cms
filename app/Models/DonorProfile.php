<?php

namespace App\Models;

use App\Enums\EngagementTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorProfile extends Model
{
    protected $fillable = [
        'person_id',
        'engagement_tier',
        'first_donated_at',
        'preferred_method',
        'notes',
    ];

    protected $casts = [
        'engagement_tier' => EngagementTier::class,
        'first_donated_at' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** Live lifetime total across all completed donations. */
    public function lifetimeTotal(): float
    {
        return (float) $this->person?->donations()
            ->whereNull('deleted_at')
            ->sum('amount');
    }
}
