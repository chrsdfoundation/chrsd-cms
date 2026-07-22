<?php

namespace App\Models;

use App\Enums\VolunteerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerProfile extends Model
{
    protected $fillable = [
        'person_id',
        'status',
        'skills',
        'availability',
        'joined_on',
        'notes',
    ];

    protected $casts = [
        'status'       => VolunteerStatus::class,
        'skills'       => 'array',
        'availability' => 'array',
        'joined_on'    => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
