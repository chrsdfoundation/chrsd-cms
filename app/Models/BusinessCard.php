<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\BusinessCardStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BusinessCard extends Model implements HasMedia
{
    use BelongsToOrganization, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $attributes = [
        'include_bleed' => false,
    ];

    protected $fillable = [
        'organization_id',
        'company_name',
        'full_name',
        'job_title',
        'phone',
        'mobile',
        'whatsapp',
        'work_email',
        'personal_email',
        'website',
        'linkedin',
        'tagline',
        'address',
        'include_bleed',
        // Executive card (structured fields; see docs/business-card/)
        'person_name',
        'person_title',
        'phones',
        'emails',
        'address_lines',
        'linkedin_handle',
        'qr_profile',
        'template_key',
        'template_version',
        'status',
        'notes',
    ];

    protected $casts = [
        'include_bleed' => 'boolean',
        'phones' => 'array',
        'emails' => 'array',
        'address_lines' => 'array',
        'status' => BusinessCardStatus::class,
        'approved_at' => 'datetime',
        'sent_to_press_at' => 'datetime',
    ];

    public function exports(): HasMany
    {
        return $this->hasMany(BusinessCardExport::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/svg+xml',
                'image/png',
                'image/jpeg',
                'image/webp',
            ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function nameFontSize(): string
    {
        $len = mb_strlen($this->full_name);

        return match (true) {
            $len <= 20 => '10pt',
            $len <= 28 => '8.5pt',
            $len <= 36 => '7.5pt',
            default => '6.5pt',
        };
    }
}
