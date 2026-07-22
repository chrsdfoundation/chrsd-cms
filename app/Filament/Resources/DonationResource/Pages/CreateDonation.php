<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDonation extends CreateRecord
{
    protected static string $resource = DonationResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        // Pre-select the donor when the create URL has ?person_id=... (used
        // by the "Log donation" button on the Person view).
        if ($personId = request()->query('person_id')) {
            $this->form->fill(['person_id' => (int) $personId]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // For in-kind rows, use the valuation as the amount so aggregate
        // queries (raised/lifetime) still work. Payment method is nulled out
        // because it's meaningless for a non-cash gift.
        if (! empty($data['is_in_kind'])) {
            $data['amount']         = $data['in_kind_valuation'] ?? 0;
            $data['payment_method'] = null;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
