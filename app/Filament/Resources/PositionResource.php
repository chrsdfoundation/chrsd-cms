<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $cluster = \App\Filament\Clusters\OrgUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('code')
                    ->required()->maxLength(32)
                    ->helperText('Unique within the department, e.g. HR-MGR'),
                Forms\Components\TextInput::make('title')
                    ->required()->maxLength(255),
                Forms\Components\TextInput::make('rank')
                    ->numeric()->minValue(0)->maxValue(255)->default(0)
                    ->helperText('Higher = more senior (used for hierarchy display)'),
                Forms\Components\TextInput::make('salary_grade')
                    ->numeric()->step(0.01)->minValue(0)->maxValue(999.99),
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department.name')->searchable()->sortable()
                    ->badge()->color('gray'),
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('rank')->sortable(),
                Tables\Columns\TextColumn::make('salary_grade')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('employees_count')->label('# Employees')
                    ->counts('employees')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('is_active'),
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
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'view'   => Pages\ViewPosition::route('/{record}'),
            'edit'   => Pages\EditPosition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
