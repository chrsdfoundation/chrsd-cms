<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Concerns\HasVerification;
use App\Enums\IdCardIssuance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class IdCard extends Model implements HasMedia
{
    use BelongsToOrganization, HasVerification, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id', 'recipient_name',
        'id_card_type_id', 'id_type_label',
        'signed_by_id', 'authorized_signatory', 'signature_version',
        'designation', 'signatory_designation', 'program_name', 'blood_group', 'nationality',
        'valid_from', 'valid_until',
        'issuance_status',
        'pdf_content_hash_front', 'pdf_content_hash_back',
        'expiry_notified_at',
        'document_template_id',
    ];

    protected $casts = [
        'issuance_status' => IdCardIssuance::class,
        'valid_from' => 'date',
        'valid_until' => 'date',
        'expiry_notified_at' => 'datetime',
    ];

    public function verificationPrefix(): string
    {
        return 'ID';
    }

    protected function extraVerificationFields(): array
    {
        return [
            'employee' => $this->employee_id,
            'valid_from' => optional($this->valid_from)->toDateString(),
            'valid_till' => optional($this->valid_until)->toDateString(),
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rendered');   // front + back + combined PDFs land here
        $this->addMediaCollection('photo')->singleFile(); // optional override of employee avatar
        // Authorised signatory's PNG signature (transparent bg ideal). One per card.
        $this->addMediaCollection('signature')->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
        // Bearer's own signature printed on the front of the card.
        $this->addMediaCollection('bearer_signature')->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }

    /**
     * Printed display name — free-text `recipient_name` (for non-employees)
     * wins over the linked employee's full name.
     */
    public function displayName(): string
    {
        return $this->recipient_name
            ?: (optional($this->employee)->full_name ?? '');
    }

    public function signatureUrl(): ?string
    {
        return $this->hasMedia('signature')
            ? $this->getFirstMedia('signature')->getPath()
            : null;
    }

    /** Auto-fit font size for the printed holder name, longest names shrink to keep the card layout intact. */
    public function nameFontSize(): string
    {
        $len = mb_strlen($this->displayName());

        return match (true) {
            $len <= 18 => '13pt',
            $len <= 24 => '11pt',
            $len <= 30 => '9.5pt',
            default => '8.5pt',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /** ID revocations notify the subject employee. */
    public function getRevocationNotifiable()
    {
        return $this->employee;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function idCardType(): BelongsTo
    {
        return $this->belongsTo(IdCardType::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signed_by_id');
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    /** Photo shown on the card — override, else the employee's avatar. */
    public function photoUrl(): ?string
    {
        if ($this->hasMedia('photo')) {
            return $this->getFirstMedia('photo')->getPath();
        }
        $emp = $this->employee;
        if ($emp && $emp->hasMedia('avatar')) {
            return $emp->getFirstMedia('avatar')->getPath();
        }

        return null;
    }
}
