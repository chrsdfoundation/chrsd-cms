<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDonation extends EditRecord
{
    protected static string $resource = DonationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['is_in_kind'])) {
            $data['amount'] = $data['in_kind_valuation'] ?? $data['amount'] ?? 0;
            $data['payment_method'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }
}
