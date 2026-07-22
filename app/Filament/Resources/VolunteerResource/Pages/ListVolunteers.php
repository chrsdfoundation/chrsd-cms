<?php

namespace App\Filament\Resources\VolunteerResource\Pages;

use App\Filament\Resources\PersonResource;
use App\Filament\Resources\VolunteerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVolunteers extends ListRecords
{
    protected static string $resource = VolunteerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_person')
                ->label('Add volunteer (new person)')
                ->icon('heroicon-o-user-plus')
                ->url(PersonResource::getUrl('create')),
        ];
    }
}
