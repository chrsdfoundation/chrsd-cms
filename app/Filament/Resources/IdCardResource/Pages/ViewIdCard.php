<?php

namespace App\Filament\Resources\IdCardResource\Pages;

use App\Filament\Resources\IdCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIdCard extends ViewRecord
{
    protected static string $resource = IdCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning'),
            IdCardResource::updatePhotoHeaderAction(),
            IdCardResource::generateHeaderAction(),
            IdCardResource::downloadFrontHeaderAction(),
            IdCardResource::downloadBackHeaderAction(),
            IdCardResource::qrPreviewHeaderAction(),
            IdCardResource::revokeHeaderAction(),
        ];
    }
}
