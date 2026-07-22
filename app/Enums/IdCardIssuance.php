<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum IdCardIssuance: string implements HasLabel, HasColor, HasIcon
{
    case Draft     = 'draft';
    case Printed   = 'printed';
    case Delivered = 'delivered';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft     => 'warning',
            self::Printed   => 'info',
            self::Delivered => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft     => 'heroicon-o-pencil-square',
            self::Printed   => 'heroicon-o-printer',
            self::Delivered => 'heroicon-o-check-badge',
        };
    }
}
