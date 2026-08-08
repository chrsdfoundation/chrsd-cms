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
        'certificate_no', 'verification_hash',
        'certificate_title', 'award_lead_in', 'program_name',
        'purpose', 'payload', 'issuance_status', 'issued_on', 'valid_until',
        'pdf_content_hash', 'expiry_notified_at', 'revoked_at',
        'document_template_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'issuance_status' => CertificateIssuance::class,
        'issued_on' => 'date',
        'valid_until' => 'date',
        'expiry_notified_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    /**
     * Scope: only valid (non-revoked) certificates.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Get the public verification URL for this certificate.
     */
    public function getVerifyUrlAttribute(): string
    {
        return route('certificates.verify', $this->verification_hash);
    }

    /**
     * Issue a new certificate with auto-allocated certificate number.
     *
     * @param array $data Certificate data (recipient_name, program_name, issued_on, etc.)
     * @return static
     */
    public static function issue(array $data)
    {
        // Use a database transaction with row-level locking to allocate
        // the next certificate number sequentially, preventing collisions
        // under concurrent issuance.
        return \DB::transaction(function () use ($data) {
            $year = $data['issued_on']?->year ?? now()->year;

            // Lock the sequence counter for this year (use a dummy record
            // or just count+1 if not found, but for safety lock the max).
            $lastThisYear = static::whereYear('issued_on', $year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = ($lastThisYear?->certificate_no
                ? (int) substr($lastThisYear->certificate_no, -6) + 1
                : 1
            );

            // Allocate a random verification hash (32 bytes = 256-bit).
            $verificationHash = bin2hex(random_bytes(32));

            $data['certificate_no'] = sprintf('CERT-%d-%06d', $year, $sequence);
            $data['verification_hash'] = $verificationHash;

            return static::create($data);
        });
    }
}
