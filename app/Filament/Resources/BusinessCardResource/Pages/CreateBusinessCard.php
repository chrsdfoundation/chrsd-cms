<?php

namespace App\Filament\Resources\BusinessCardResource\Pages;

use App\Filament\Resources\BusinessCardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessCard extends CreateRecord
{
    protected static string $resource = BusinessCardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BusinessCardResource::prepareData($data);
    }
}
