<?php

namespace App\Filament\Resources\PersonInteractionResource\Pages;

use App\Filament\Resources\PersonInteractionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonInteractions extends ListRecords
{
    protected static string $resource = PersonInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
