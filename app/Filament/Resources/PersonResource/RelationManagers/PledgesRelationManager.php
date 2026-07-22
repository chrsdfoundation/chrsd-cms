<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PledgesRelationManager extends RelationManager
{
    protected static string $relationship = 'pledges';

    protected static ?string $title = 'Pledges';

    protected static ?string $icon = 'heroicon-o-hand-thumb-up';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('promised_amount')->money('BDT')->alignEnd(),
                Tables\Columns\TextColumn::make('fulfilled_amount')->money('BDT')->alignEnd()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('campaign.name')->label('Campaign')->toggleable(),
            ])
            ->defaultSort('due_date', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('new_pledge')
                    ->label('Log pledge')->icon('heroicon-o-plus')
                    ->url(fn () => \App\Filament\Resources\PledgeResource::getUrl('create', ['person_id' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\PledgeResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
