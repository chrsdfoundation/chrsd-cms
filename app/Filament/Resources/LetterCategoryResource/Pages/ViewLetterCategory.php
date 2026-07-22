<?php

namespace App\Filament\Resources\LetterCategoryResource\Pages;

use App\Filament\Resources\LetterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLetterCategory extends ViewRecord
{
    protected static string $resource = LetterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
