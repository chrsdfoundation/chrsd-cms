<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmployeeStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Resigned = 'resigned';
    case Retired = 'retired';

    public function getLabel(): string
    {
        return str(str_replace('_', ' ', $this->value))->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::OnLeave => 'warning',
            self::Suspended => 'danger',
            self::Terminated, self::Resigned => 'gray',
            self::Retired => 'info',
        };
    }
}
