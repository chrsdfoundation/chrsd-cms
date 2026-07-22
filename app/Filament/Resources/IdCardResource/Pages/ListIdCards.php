<?php

namespace App\Filament\Resources\IdCardResource\Pages;

use App\Filament\Resources\IdCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIdCards extends ListRecords
{
    protected static string $resource = IdCardResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
