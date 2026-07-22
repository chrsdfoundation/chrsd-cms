<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum VerificationStatus: string implements HasLabel, HasColor, HasIcon
{
    case Valid    = 'valid';
    case Invalid  = 'invalid';
    case Revoked  = 'revoked';
    case Expired  = 'expired';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Valid   => 'success',
            self::Invalid => 'gray',
            self::Revoked => 'danger',
            self::Expired => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Valid   => 'heroicon-o-shield-check',
            self::Invalid => 'heroicon-o-shield-exclamation',
            self::Revoked => 'heroicon-o-no-symbol',
            self::Expired => 'heroicon-o-clock',
        };
    }
}
