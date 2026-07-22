<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource;
use App\Models\Person;
use Filament\Resources\Pages\CreateRecord;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    /** Toggled flags picked up post-save to spawn profile rows. */
    protected array $roleFlags = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Underscore-prefixed toggles are UI-only (dehydrated: false), but
        // Filament still surfaces them in $data. Cache the flags for
        // afterCreate() and strip them so Eloquent doesn't complain.
        $this->roleFlags = [
            'donor' => (bool) ($data['_is_donor'] ?? false),
            'volunteer' => (bool) ($data['_is_volunteer'] ?? false),
            'beneficiary' => (bool) ($data['_is_beneficiary'] ?? false),
        ];

        foreach (['_is_donor', '_is_volunteer', '_is_beneficiary'] as $k) {
            unset($data[$k]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Person $record */
        $record = $this->record;

        // Profile rows get default enum values (Cold / Prospective / Enrolled)
        // set on their tables. Admin can edit each profile from the
        // person's view page afterwards.
        if ($this->roleFlags['donor'] ?? false) {
            $record->donorProfile()->create([]);
        }
        if ($this->roleFlags['volunteer'] ?? false) {
            $record->volunteerProfile()->create([]);
        }
        if ($this->roleFlags['beneficiary'] ?? false) {
            $record->beneficiaryProfile()->create([]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
