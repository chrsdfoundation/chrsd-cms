<?php

namespace App\Models;

use App\Enums\DocumentTemplateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DocumentTemplate extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * Templates are a shared library: null organization_id = system-wide default
     * available to every tenant; non-null = tenant-owned override. We do NOT use
     * the BelongsToOrganization trait because its scope would hide null-org rows
     * when a session current-org is set, defeating the purpose of a shared library.
     * Instead we register a scope that includes both current-org and null-org rows.
     *
     * Uses booted() (Laravel's model boot hook), NOT boot{ModelName}() — the
     * latter is a trait convention only.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if ($orgId = session('current_organization_id')) {
                $table = $query->getModel()->getTable();
                $query->where(function ($q) use ($table, $orgId) {
                    $q->where("{$table}.organization_id", $orgId)
                        ->orWhereNull("{$table}.organization_id");
                });
            }
        });
    }

    public function scopeAcrossOrganizations(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected $fillable = [
        'organization_id',
        'name',
        'document_type',
        'certificate_type_id',
        'letter_category_id',
        'id_card_type_id',
        'body_markdown',
        'orientation',
        'shell_variant',
        'is_default',
        'sample_context',
        'created_by_user_id',
    ];

    protected $casts = [
        'document_type'   => DocumentTemplateType::class,
        'is_default'      => 'boolean',
        'sample_context'  => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function letterCategory(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class);
    }

    public function idCardType(): BelongsTo
    {
        return $this->belongsTo(IdCardType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
