<?php

namespace App\Filament\Resources\DonorResource\Pages;

use App\Filament\Resources\DonorResource;
use App\Filament\Resources\PersonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDonors extends ListRecords
{
    protected static string $resource = DonorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_person')
                ->label('Add donor (new person)')
                ->icon('heroicon-o-user-plus')
                ->url(PersonResource::getUrl('create')),
        ];
    }
}
