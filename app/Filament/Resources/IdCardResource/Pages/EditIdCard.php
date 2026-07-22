<?php

namespace App\Filament\Resources\IdCardResource\Pages;

use App\Filament\Resources\IdCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIdCard extends EditRecord
{
    protected static string $resource = IdCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            IdCardResource::updatePhotoHeaderAction(),
            IdCardResource::generateHeaderAction(),
            IdCardResource::qrPreviewHeaderAction(),
            Actions\DeleteAction::make(),
        ];
    }
}
