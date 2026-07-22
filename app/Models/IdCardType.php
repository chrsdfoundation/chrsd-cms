<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IdCardType extends Model
{
    use LogsActivity, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'code', 'name', 'description',
        'default_validity_months', 'is_active',
    ];

    protected $casts = [
        'is_active'                => 'boolean',
        'default_validity_months'  => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function idCards(): HasMany
    {
        return $this->hasMany(IdCard::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }
}
