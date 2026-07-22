<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VolunteerStatus: string implements HasLabel, HasColor
{
    case Prospective = 'prospective';
    case Active      = 'active';
    case Inactive    = 'inactive';
    case Alumni      = 'alumni';

    public function getLabel(): string
    {
        return str($this->value)->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Prospective => 'info',
            self::Active      => 'success',
            self::Inactive    => 'warning',
            self::Alumni      => 'gray',
        };
    }
}
