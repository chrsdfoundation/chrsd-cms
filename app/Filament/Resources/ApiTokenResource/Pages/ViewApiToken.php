<?php

namespace App\Filament\Resources\ApiTokenResource\Pages;

use App\Filament\Resources\ApiTokenResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewApiToken extends ViewRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Revoke')];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Token')->columns(2)->schema([
                TextEntry::make('name')->label('Label'),
                TextEntry::make('tokenable.name')->label('User'),
                TextEntry::make('abilities')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                TextEntry::make('expires_at')->dateTime()->placeholder('Never'),
            ]),

            Section::make('Usage')->columns(2)->schema([
                TextEntry::make('last_used_at')->since()->placeholder('Never used'),
                TextEntry::make('last_used_ip')->placeholder('—'),
                TextEntry::make('last_used_user_agent')->placeholder('—')->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(),
            ]),
        ]);
    }
}
