<?php

namespace App\Filament\Resources\OfficialLetterResource\Pages;

use App\Filament\Resources\OfficialLetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialLetters extends ListRecords
{
    protected static string $resource = OfficialLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
