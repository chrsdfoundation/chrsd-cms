<?php

namespace App\Filament\Resources\CertificateTypeResource\Pages;

use App\Filament\Resources\CertificateTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificateType extends ViewRecord
{
    protected static string $resource = CertificateTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
