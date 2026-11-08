<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'include_bleed' => 'boolean',
    ];

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
