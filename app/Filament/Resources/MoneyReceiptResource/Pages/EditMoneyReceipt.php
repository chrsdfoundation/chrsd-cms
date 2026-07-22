<?php

namespace App\Filament\Resources\MoneyReceiptResource\Pages;

use App\Filament\Resources\MoneyReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMoneyReceipt extends EditRecord
{
    protected static string $resource = MoneyReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
