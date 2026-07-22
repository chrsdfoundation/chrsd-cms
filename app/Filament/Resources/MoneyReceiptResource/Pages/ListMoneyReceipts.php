<?php

namespace App\Filament\Resources\MoneyReceiptResource\Pages;

use App\Filament\Resources\MoneyReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMoneyReceipts extends ListRecords
{
    protected static string $resource = MoneyReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
