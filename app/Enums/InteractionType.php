<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InteractionType: string implements HasColor, HasIcon, HasLabel
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';
    case Event = 'event';
    case Donation = 'donation';

    public function getLabel(): string
    {
        return str($this->value)->title();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Call => 'heroicon-o-phone',
            self::Email => 'heroicon-o-envelope',
            self::Meeting => 'heroicon-o-users',
            self::Note => 'heroicon-o-pencil-square',
            self::Event => 'heroicon-o-calendar-days',
            self::Donation => 'heroicon-o-banknotes',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Call, self::Meeting => 'info',
            self::Email => 'gray',
            self::Note => 'warning',
            self::Event => 'success',
            self::Donation => 'primary',
        };
    }
}
