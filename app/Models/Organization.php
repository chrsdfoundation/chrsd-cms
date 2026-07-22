<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['code', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Resolve the CHRSD Foundation organization.
     *
     * Reads from the session first (set by SetCurrentOrganization middleware).
     * Falls back to the config value so code running outside of a web request
     * (artisan commands, queue jobs, seeders) still gets a valid org.
     */
    public static function current(): ?self
    {
        $id = session('current_organization_id') ?? config('chrsd.org_id');
        return $id ? static::find($id) : null;
    }
}
