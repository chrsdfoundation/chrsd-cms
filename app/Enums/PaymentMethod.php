<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel, HasColor
{
    case Cash         = 'cash';
    case Bkash        = 'bkash';
    case Nagad        = 'nagad';
    case Rocket       = 'rocket';
    case BankTransfer = 'bank_transfer';
    case Cheque       = 'cheque';
    case Card         = 'card';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash         => 'Cash',
            self::Bkash        => 'bKash',
            self::Nagad        => 'Nagad',
            self::Rocket       => 'Rocket',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque       => 'Cheque',
            self::Card         => 'Card',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cash                     => 'success',
            self::Bkash, self::Nagad,
            self::Rocket                   => 'warning',
            self::BankTransfer, self::Card => 'info',
            self::Cheque                   => 'gray',
        };
    }
}
