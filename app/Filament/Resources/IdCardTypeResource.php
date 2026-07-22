<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdCardTypeResource\Pages;
use App\Models\IdCardType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IdCardTypeResource extends Resource
{
    protected static ?string $model = IdCardType::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'ID Card Types';

    protected static ?int $navigationSort = 27;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('code')
                    ->required()->maxLength(32)
                    ->unique(ignoreRecord: true)
                    ->helperText('e.g. EMP, VOL, VIS'),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('default_validity_months')
                    ->numeric()->minValue(1)->maxValue(120)
                    ->helperText('Suggested validity in months when a card is issued for this type.'),
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('default_validity_months')
                    ->label('Default validity (months)')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('id_cards_count')
                    ->label('Issued')->counts('idCards')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')])
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIdCardTypes::route('/'),
            'create' => Pages\CreateIdCardType::route('/create'),
            'view'   => Pages\ViewIdCardType::route('/{record}'),
            'edit'   => Pages\EditIdCardType::route('/{record}/edit'),
        ];
    }
}
