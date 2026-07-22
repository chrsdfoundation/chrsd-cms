<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EngagementTier: string implements HasColor, HasLabel
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Active = 'active';
    case Champion = 'champion';

    public function getLabel(): string
    {
        return str($this->value)->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cold => 'gray',
            self::Warm => 'info',
            self::Active => 'success',
            self::Champion => 'warning',
        };
    }
}
