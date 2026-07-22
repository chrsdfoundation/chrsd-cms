<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentTemplateType: string implements HasColor, HasIcon, HasLabel
{
    case Certificate = 'certificate';
    case Letter = 'letter';
    case IdCard = 'id_card';

    public function getLabel(): string
    {
        return match ($this) {
            self::Certificate => 'Certificate',
            self::Letter => 'Letter',
            self::IdCard => 'ID Card',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Certificate => 'success',
            self::Letter => 'info',
            self::IdCard => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Certificate => 'heroicon-o-document-check',
            self::Letter => 'heroicon-o-envelope',
            self::IdCard => 'heroicon-o-identification',
        };
    }
}
