<?php

namespace App\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        /*
         * Global scope: filter rows to the current organization ONLY when
         * session()->has('current_organization_id'). No session → no scope,
         * so existing code paths (artisan tests, tinker, seeders, one-off
         * scripts) keep seeing every row. This is deliberate for the "prep"
         * phase — flipping the switch is a future step.
         */
        static::addGlobalScope('organization', function (Builder $query) {
            if ($orgId = session('current_organization_id')) {
                $table = $query->getModel()->getTable();
                $query->where("{$table}.organization_id", $orgId);
            }
        });

        /*
         * Auto-stamp organization_id on create when a session current-org is
         * set and the caller didn't provide one explicitly.
         */
        static::creating(function (Model $model) {
            if (empty($model->organization_id) && $orgId = session('current_organization_id')) {
                $model->organization_id = $orgId;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Escape hatch for admin queries that need to see every tenant's data. */
    public function scopeAcrossOrganizations(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
