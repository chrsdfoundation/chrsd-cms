<?php

namespace App\Filament\Resources\LetterCategoryResource\Pages;

use App\Filament\Resources\LetterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLetterCategory extends EditRecord
{
    protected static string $resource = LetterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
