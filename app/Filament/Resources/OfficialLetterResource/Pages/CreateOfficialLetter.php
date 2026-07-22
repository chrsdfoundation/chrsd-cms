<?php

namespace App\Filament\Resources\OfficialLetterResource\Pages;

use App\Filament\Resources\OfficialLetterResource;
use App\Support\VisaLetterSubstitutor;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficialLetter extends CreateRecord
{
    protected static string $resource = OfficialLetterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->applyVisaSubstitutionAndStrip($data);
    }

    /**
     * Apply Quick-Fill substitution to the body, then strip visa_* keys so
     * Eloquent doesn't try to save them as columns.
     */
    protected function applyVisaSubstitutionAndStrip(array $data): array
    {
        if (! empty($data['body'])) {
            $data['body'] = VisaLetterSubstitutor::apply($data['body'], $data);
        }
        foreach (array_keys($data) as $k) {
            if (str_starts_with($k, 'visa_')) {
                unset($data[$k]);
            }
        }

        return $data;
    }
}
