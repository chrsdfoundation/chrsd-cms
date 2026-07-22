<?php

namespace App\Models;

use App\Enums\BeneficiaryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryProfile extends Model
{
    protected $fillable = [
        'person_id',
        'program_ref',
        'status',
        'enrolled_on',
        'notes',
    ];

    protected $casts = [
        'status'      => BeneficiaryStatus::class,
        'enrolled_on' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
