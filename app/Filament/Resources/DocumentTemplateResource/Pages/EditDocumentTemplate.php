<?php

namespace App\Filament\Resources\DocumentTemplateResource\Pages;

use App\Filament\Resources\DocumentTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentTemplate extends EditRecord
{
    protected static string $resource = DocumentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->action(fn () => DocumentTemplateResource::streamPreview($this->getRecord())),
            Actions\DeleteAction::make(),
        ];
    }
}
