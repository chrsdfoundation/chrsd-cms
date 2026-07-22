<?php

namespace App\Filament\Resources\MoneyReceiptResource\Pages;

use App\Filament\Resources\MoneyReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMoneyReceipt extends CreateRecord
{
    protected static string $resource = MoneyReceiptResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
