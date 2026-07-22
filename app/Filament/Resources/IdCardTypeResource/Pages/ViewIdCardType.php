<?php

namespace App\Filament\Resources\IdCardTypeResource\Pages;

use App\Filament\Resources\IdCardTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIdCardType extends ViewRecord
{
    protected static string $resource = IdCardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
