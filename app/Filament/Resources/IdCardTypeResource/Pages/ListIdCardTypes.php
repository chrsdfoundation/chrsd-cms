<?php

namespace App\Filament\Resources\IdCardTypeResource\Pages;

use App\Filament\Resources\IdCardTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIdCardTypes extends ListRecords
{
    protected static string $resource = IdCardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
