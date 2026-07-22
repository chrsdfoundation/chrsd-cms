<?php

namespace App\Filament\Resources\CertificateRequestResource\Pages;

use App\Filament\Resources\CertificateRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificateRequest extends ViewRecord
{
    protected static string $resource = CertificateRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
