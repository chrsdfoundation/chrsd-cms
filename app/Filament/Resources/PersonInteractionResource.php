<?php

namespace App\Filament\Resources;

use App\Enums\InteractionType;
use App\Filament\Resources\PersonInteractionResource\Pages;
use App\Models\Person;
use App\Models\PersonInteraction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonInteractionResource extends Resource
{
    protected static ?string $model = PersonInteraction::class;

    protected static ?string $cluster = \App\Filament\Clusters\Crm::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Interactions';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('person_id')
                ->label('Person')->required()
                ->options(fn () => Person::query()->orderBy('full_name')->pluck('full_name', 'id'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => Person::query()
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->limit(30)->pluck('full_name', 'id')),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->options(InteractionType::class)->required()
                    ->default(InteractionType::Note->value),
                Forms\Components\DateTimePicker::make('occurred_at')->native(false)
                    ->default(now())->required(),
            ]),
            Forms\Components\TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('body')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Person')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('user.name')->label('By')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(InteractionType::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPersonInteractions::route('/'),
            'create' => Pages\CreatePersonInteraction::route('/create'),
            'edit'   => Pages\EditPersonInteraction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['person', 'user']);
    }
}
