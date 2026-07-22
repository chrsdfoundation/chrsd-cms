<?php

namespace App\Filament\Resources\EmploymentEventResource\Pages;

use App\Filament\Resources\EmploymentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentEvents extends ListRecords
{
    protected static string $resource = EmploymentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
