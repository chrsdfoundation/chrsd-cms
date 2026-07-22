<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RecurringCadence: string implements HasLabel
{
    case Monthly   = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly    = 'yearly';

    public function getLabel(): string
    {
        return str($this->value)->title();
    }
}
