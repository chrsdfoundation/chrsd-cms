<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Filament\Resources\DonationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $title = 'Donations';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('BDT')->alignEnd(),
                Tables\Columns\TextColumn::make('payment_method')->badge(),
                Tables\Columns\TextColumn::make('campaign.name')->label('Campaign')->toggleable(),
                Tables\Columns\IconColumn::make('is_in_kind')->boolean()->label('In-kind'),
                Tables\Columns\IconColumn::make('is_recurring')->boolean()->label('Recurring'),
                Tables\Columns\TextColumn::make('moneyReceipt.serial_number')
                    ->label('Receipt')->fontFamily('mono')->size('xs')->toggleable(),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('new_donation')
                    ->label('Log donation')->icon('heroicon-o-plus')
                    ->url(fn () => DonationResource::getUrl('create', ['person_id' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => DonationResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
