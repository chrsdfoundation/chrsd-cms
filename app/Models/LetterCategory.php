<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LetterCategory extends Model
{
    use BelongsToOrganization, LogsActivity;

    protected $fillable = [
        'organization_id',
        'code', 'name', 'description', 'template_view', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function letters(): HasMany
    {
        return $this->hasMany(OfficialLetter::class);
    }
}
