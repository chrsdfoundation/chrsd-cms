<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel
{
    case Regular     = 'regular';
    case Probationary= 'probationary';
    case Contractual = 'contractual';
    case JobOrder    = 'job_order';
    case Consultant  = 'consultant';
    case Intern      = 'intern';

    public function getLabel(): string
    {
        return str(str_replace('_', ' ', $this->value))->title();
    }
}
