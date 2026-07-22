<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Enums\InteractionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $title = 'Interactions';

    protected static ?string $icon = 'heroicon-o-chat-bubble-left-right';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->options(InteractionType::class)->required()
                ->default(InteractionType::Note->value),
            Forms\Components\DateTimePicker::make('occurred_at')->native(false)
                ->default(now())->required(),
            Forms\Components\TextInput::make('subject')->required()->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('body')->rows(4)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('user.name')->label('By')->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
