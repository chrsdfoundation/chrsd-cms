<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Person extends Model
{
    use SoftDeletes, LogsActivity, BelongsToOrganization;

    protected $table = 'people';

    protected $fillable = [
        'organization_id',
        'full_name', 'organization_name',
        'email', 'phone',
        'address_line', 'city', 'country',
        'tax_id', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function donorProfile(): HasOne
    {
        return $this->hasOne(DonorProfile::class);
    }

    public function volunteerProfile(): HasOne
    {
        return $this->hasOne(VolunteerProfile::class);
    }

    public function beneficiaryProfile(): HasOne
    {
        return $this->hasOne(BeneficiaryProfile::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(PersonInteraction::class)->orderByDesc('occurred_at');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    public function moneyReceipts(): HasMany
    {
        return $this->hasMany(MoneyReceipt::class);
    }

    /**
     * Which role tags apply to this person, based on which profile rows exist.
     * Used in list badges — "Donor", "Volunteer", "Beneficiary".
     */
    public function getRolesAttribute(): array
    {
        $roles = [];
        if ($this->relationLoaded('donorProfile')       ? $this->donorProfile       : $this->donorProfile()->exists())       $roles[] = 'donor';
        if ($this->relationLoaded('volunteerProfile')   ? $this->volunteerProfile   : $this->volunteerProfile()->exists())   $roles[] = 'volunteer';
        if ($this->relationLoaded('beneficiaryProfile') ? $this->beneficiaryProfile : $this->beneficiaryProfile()->exists()) $roles[] = 'beneficiary';
        return $roles;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
