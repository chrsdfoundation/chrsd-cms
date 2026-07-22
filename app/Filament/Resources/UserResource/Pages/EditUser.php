<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserResource::forceResetHeaderAction(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->getRecord()->id !== Auth::id()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // When the admin sets a new password from this page, refresh the
        // timestamp so the "password age" column is accurate.
        if (! empty($data['password'])) {
            $data['password_changed_at'] = now();
        }

        return $data;
    }
}
