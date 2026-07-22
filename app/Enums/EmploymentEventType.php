<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmploymentEventType: string implements HasColor, HasIcon, HasLabel
{
    case Hire = 'hire';
    case Promotion = 'promotion';
    case Transfer = 'transfer';
    case ContractChange = 'contract_change';
    case StatusChange = 'status_change';
    case Ended = 'ended';
    case Note = 'note';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hire => 'Hired',
            self::Promotion => 'Promotion',
            self::Transfer => 'Transfer',
            self::ContractChange => 'Contract Change',
            self::StatusChange => 'Status Change',
            self::Ended => 'Ended Employment',
            self::Note => 'Note',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Hire => 'success',
            self::Promotion => 'success',
            self::Transfer => 'info',
            self::ContractChange => 'warning',
            self::StatusChange => 'warning',
            self::Ended => 'danger',
            self::Note => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Hire => 'heroicon-o-user-plus',
            self::Promotion => 'heroicon-o-arrow-trending-up',
            self::Transfer => 'heroicon-o-arrows-right-left',
            self::ContractChange => 'heroicon-o-document-duplicate',
            self::StatusChange => 'heroicon-o-adjustments-horizontal',
            self::Ended => 'heroicon-o-arrow-right-on-rectangle',
            self::Note => 'heroicon-o-chat-bubble-left',
        };
    }
}
