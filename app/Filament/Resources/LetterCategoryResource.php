<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterCategoryResource\Pages;
use App\Models\LetterCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LetterCategoryResource extends Resource
{
    protected static ?string $model = LetterCategory::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('code')->required()->maxLength(32)
                    ->unique(ignoreRecord: true)
                    ->helperText('e.g. MEMO, CIRC, EXT'),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('template_view')
                    ->placeholder('documents.letters.default')
                    ->helperText('Blade view path. Leave blank for default template.'),
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
                Tables\Columns\TextColumn::make('letters_count')->label('Issued')
                    ->counts('letters')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription(fn (LetterCategory $record) => $record->letters()->withTrashed()->count() > 0
                        ? 'This category has letters attached. They will become uncategorized but will not be deleted.'
                        : 'This action cannot be undone.')
                    ->before(fn (LetterCategory $record) => $record->letters()->withTrashed()->update(['letter_category_id' => null])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(fn (\Illuminate\Support\Collection $records) => $records->each(
                            fn (LetterCategory $r) => $r->letters()->withTrashed()->update(['letter_category_id' => null])
                        )),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLetterCategories::route('/'),
            'create' => Pages\CreateLetterCategory::route('/create'),
            'view'   => Pages\ViewLetterCategory::route('/{record}'),
            'edit'   => Pages\EditLetterCategory::route('/{record}/edit'),
        ];
    }
}
