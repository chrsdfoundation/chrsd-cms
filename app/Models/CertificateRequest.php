<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\CertificateRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CertificateRequest extends Model
{
    use LogsActivity, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'certificate_type_id',
        'purpose',
        'notes',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'review_notes',
        'resulting_certificate_id',
    ];

    protected $casts = [
        'status'      => CertificateRequestStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function resultingCertificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'resulting_certificate_id');
    }

    public function isPending(): bool
    {
        return $this->status === CertificateRequestStatus::Pending;
    }
}
