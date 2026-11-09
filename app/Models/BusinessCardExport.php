<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One export of a card's print artwork or foil plate. Append-only: rows are never
 * updated, so the audit trail is what was actually produced.
 */
class BusinessCardExport extends Model
{
    public const UPDATED_AT = null;

    public const KIND_ARTWORK = 'artwork';

    public const KIND_FOIL_PLATE = 'foil_plate';

    protected $fillable = [
        'business_card_id',
        'kind',
        'file_path',
        'sha256',
        'content_hash',
        'engine_name',
        'engine_version',
        'spec_version',
        'qr_profile',
        'qr_module_mm',
        'qr_acknowledged',
        'preflight_report',
        'issues',
        'created_by',
    ];

    protected $casts = [
        'qr_module_mm' => 'float',
        'qr_acknowledged' => 'boolean',
        'preflight_report' => 'array',
        'issues' => 'array',
        'created_at' => 'datetime',
    ];

    public function businessCard(): BelongsTo
    {
        return $this->belongsTo(BusinessCard::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
