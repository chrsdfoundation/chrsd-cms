<?php

namespace App\Filament\Resources\BeneficiaryResource\Pages;

use App\Filament\Resources\BeneficiaryResource;
use App\Filament\Resources\PersonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_person')
                ->label('Add beneficiary (new person)')
                ->icon('heroicon-o-user-plus')
                ->url(PersonResource::getUrl('create')),
        ];
    }
}
