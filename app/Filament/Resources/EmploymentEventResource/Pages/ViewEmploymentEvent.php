<?php

namespace App\Filament\Resources\EmploymentEventResource\Pages;

use App\Filament\Resources\EmploymentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmploymentEvent extends ViewRecord
{
    protected static string $resource = EmploymentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
