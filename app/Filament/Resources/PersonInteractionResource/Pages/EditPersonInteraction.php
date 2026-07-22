<?php

namespace App\Filament\Resources\PersonInteractionResource\Pages;

use App\Filament\Resources\PersonInteractionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonInteraction extends EditRecord
{
    protected static string $resource = PersonInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
