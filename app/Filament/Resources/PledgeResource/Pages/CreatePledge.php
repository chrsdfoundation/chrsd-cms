<?php

namespace App\Filament\Resources\PledgeResource\Pages;

use App\Filament\Resources\PledgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePledge extends CreateRecord
{
    protected static string $resource = PledgeResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();
        if ($personId = request()->query('person_id')) {
            $this->form->fill(['person_id' => (int) $personId]);
        }
    }
}
