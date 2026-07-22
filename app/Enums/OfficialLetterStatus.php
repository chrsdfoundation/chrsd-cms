<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OfficialLetterStatus: string implements HasLabel, HasColor
{
    case Draft     = 'draft';
    case ForReview = 'for_review';
    case Approved  = 'approved';
    case Released  = 'released';
    case Filed     = 'filed';

    public function getLabel(): string
    {
        return str(str_replace('_', ' ', $this->value))->title();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft, self::ForReview => 'warning',
            self::Approved               => 'info',
            self::Released               => 'success',
            self::Filed                  => 'gray',
        };
    }
}
