<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BeneficiaryStatus: string implements HasColor, HasLabel
{
    case Enrolled = 'enrolled';
    case Active = 'active';
    case Completed = 'completed';
    case Exited = 'exited';

    public function getLabel(): string
    {
        return str($this->value)->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Enrolled => 'info',
            self::Active => 'success',
            self::Completed => 'gray',
            self::Exited => 'danger',
        };
    }
}
