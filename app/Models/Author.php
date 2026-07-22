<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Author extends Model
{
    protected $fillable = [
        'name',
        'designation',
        'department',
        'organization',
        'email',
        'phone',
        'signature_path',
        'initial_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function letters(): HasMany
    {
        return $this->hasMany(OfficialLetter::class, 'letter_author_id');
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path
            ? Storage::disk('public')->url($this->signature_path)
            : null;
    }

    public function signatureBase64(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->signature_path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($this->signature_path);
        return 'data:image/png;base64,' . base64_encode($bytes);
    }

    public function initialBase64(): ?string
    {
        if (! $this->initial_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->initial_path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($this->initial_path);
        return 'data:image/png;base64,' . base64_encode($bytes);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
