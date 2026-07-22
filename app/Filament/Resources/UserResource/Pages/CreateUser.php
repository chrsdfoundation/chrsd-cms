<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Freshly-minted users MUST change on first login unless the admin
        // explicitly leaves the toggle off.
        if (! array_key_exists('must_change_password', $data)) {
            $data['must_change_password'] = true;
        }
        $data['password_changed_at'] = now();
        return $data;
    }
}
