<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CertificateType extends Model
{
    use LogsActivity, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'code', 'name', 'description', 'template_view',
        'default_fields', 'requires_approval', 'validity_days', 'is_active',
    ];

    protected $casts = [
        'default_fields'    => 'array',
        'requires_approval' => 'boolean',
        'is_active'         => 'boolean',
        'validity_days'     => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
