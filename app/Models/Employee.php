<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Concerns\HasVerification;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Observers\EmployeeStateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(EmployeeStateObserver::class)]
class Employee extends Model implements HasMedia
{
    use SoftDeletes, HasVerification, LogsActivity, InteractsWithMedia, Notifiable, BelongsToOrganization;

    /** Route mail notifications to the employee's own email address. */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }

    /** Employee revocations notify the employee themselves. */
    public function getRevocationNotifiable()
    {
        return $this;
    }

    protected $fillable = [
        'organization_id',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'email', 'mobile',
        'gender', 'date_of_birth', 'civil_status', 'nationality', 'address',
        'department_id', 'position_id', 'supervisor_id',
        'employment_type', 'employee_status',
        'hired_at', 'ended_at',
    ];

    protected $casts = [
        'gender'          => Gender::class,
        'employment_type' => EmploymentType::class,
        'employee_status' => EmployeeStatus::class,
        'date_of_birth'   => 'date',
        'hired_at'        => 'date',
        'ended_at'        => 'date',
    ];

    public function verificationPrefix(): string
    {
        return 'EMP';
    }

    protected function extraVerificationFields(): array
    {
        return [
            'email' => $this->email,
            'hired' => optional($this->hired_at)->toDateString(),
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('signature')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 200, 200)
            ->performOnCollections('avatar');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])
            ->filter()->implode(' '));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function authoredLetters(): HasMany
    {
        return $this->hasMany(OfficialLetter::class, 'author_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmploymentEvent::class)->orderByDesc('occurred_on');
    }
}
