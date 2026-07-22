<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Concerns\HasVerification;
use App\Enums\CertificateIssuance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Certificate extends Model implements HasMedia
{
    use BelongsToOrganization, HasVerification, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id', 'recipient_name', 'certificate_type_id', 'issued_by_id', 'signed_by_id',
        'signatory_1_name', 'signatory_1_title',
        'signatory_2_name', 'signatory_2_title',
        'purpose', 'payload', 'issuance_status', 'issued_on', 'valid_until',
        'pdf_content_hash', 'expiry_notified_at', 'document_template_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'issuance_status' => CertificateIssuance::class,
        'issued_on' => 'date',
        'valid_until' => 'date',
        'expiry_notified_at' => 'datetime',
    ];

    public function verificationPrefix(): string
    {
        return 'CERT';
    }

    protected function extraVerificationFields(): array
    {
        return [
            'employee' => $this->employee_id,
            'type' => $this->certificate_type_id,
            'issued' => optional($this->issued_on)->toDateString(),
        ];
    }

    /** Certificate revocations notify the subject employee. */
    public function getRevocationNotifiable()
    {
        return $this->employee;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rendered')->singleFile();
        $this->addMediaCollection('attachments');

        // Signature PNGs — one per signatory. singleFile() so re-uploading
        // replaces the existing image instead of accumulating variants.
        $this->addMediaCollection('signature_1')->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
        $this->addMediaCollection('signature_2')->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }

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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_by_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signed_by_id');
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }
}
