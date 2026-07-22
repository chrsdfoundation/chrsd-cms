<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CertificateIssuance: string implements HasLabel, HasColor
{
    case Draft     = 'draft';
    case Generated = 'generated';
    case Issued    = 'issued';
    case Delivered = 'delivered';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft     => 'warning',
            self::Generated => 'info',
            self::Issued    => 'success',
            self::Delivered => 'gray',
        };
    }
}
