<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PledgeStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';
    case Overdue = 'overdue';
    case WrittenOff = 'written_off';

    public function getLabel(): string
    {
        return str(str_replace('_', ' ', $this->value))->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Open => 'info',
            self::Partial => 'warning',
            self::Fulfilled => 'success',
            self::Overdue => 'danger',
            self::WrittenOff => 'gray',
        };
    }
}
