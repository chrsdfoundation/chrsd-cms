<?php

namespace App\Filament\Resources\CertificateTypeResource\Pages;

use App\Filament\Resources\CertificateTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateType extends EditRecord
{
    protected static string $resource = CertificateTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
