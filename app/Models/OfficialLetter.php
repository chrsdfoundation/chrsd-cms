<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Concerns\HasVerification;
use App\Enums\OfficialLetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OfficialLetter extends Model implements HasMedia
{
    use BelongsToOrganization, HasVerification, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'letter_category_id', 'letter_author_id', 'author_id', 'signed_by_id',
        'subject', 'body',
        'recipient_name', 'recipient_title', 'recipient_address',
        'letter_status', 'dated_on', 'released_on',
        'pdf_content_hash', 'document_template_id',
    ];

    protected $casts = [
        'letter_status' => OfficialLetterStatus::class,
        'dated_on' => 'date',
        'released_on' => 'date',
    ];

    public function verificationPrefix(): string
    {
        return 'LTR';
    }

    protected function extraVerificationFields(): array
    {
        return [
            'category' => $this->letter_category_id,
            'author' => $this->author_id,
            'subject' => $this->subject,
        ];
    }

    /** Letter revocations notify the letter's author. */
    public function getRevocationNotifiable()
    {
        return $this->author;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rendered')->singleFile();
        $this->addMediaCollection('attachments');

        // Optional digital signature PNG. Single-file so re-uploading replaces
        // rather than accumulating variants.
        $this->addMediaCollection('signature')->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'letter_category_id');
    }

    /** New Author model — preferred for all new letters. */
    public function letterAuthor(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'letter_author_id');
    }

    /** Legacy employee author — kept for backward-compatibility with old letters. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'author_id');
    }

    /** Returns the effective author display name regardless of which system was used. */
    public function resolvedAuthorName(): string
    {
        return $this->letterAuthor?->name
            ?? $this->author?->full_name
            ?? '';
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
