<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorResource\Pages;
use App\Models\Author;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Authors';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('designation')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Job title or role, e.g. Executive Director'),
                    Forms\Components\TextInput::make('department')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('organization')
                        ->maxLength(255)
                        ->default(config('app.name')),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(50),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ]),

            Forms\Components\Section::make('Signature')
                ->description('PNG file with transparent background. Max 2 MB. Used in generated PDFs.')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('signature_path')
                        ->label('Digital Signature (PNG)')
                        ->image()
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('signatures')
                        ->imagePreviewHeight('80')
                        ->uploadingMessage('Uploading signature...')
                        ->removeUploadedFileButtonPosition('right')
                        ->helperText('Transparent PNG. Appears above your printed name in the PDF.')
                        ->columnSpanFull()
                        ->deleteUploadedFileUsing(function (string $file) {
                            Storage::disk('public')->delete($file);
                        }),

                    Forms\Components\FileUpload::make('initial_path')
                        ->label('Initial / Small Signature (PNG, optional)')
                        ->image()
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('signatures')
                        ->imagePreviewHeight('60')
                        ->uploadingMessage('Uploading initial...')
                        ->removeUploadedFileButtonPosition('right')
                        ->helperText('Optional smaller signature for initials on multi-page documents.')
                        ->columnSpanFull()
                        ->deleteUploadedFileUsing(function (string $file) {
                            Storage::disk('public')->delete($file);
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('designation')
                    ->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('department')
                    ->toggleable()->limit(30),
                Tables\Columns\TextColumn::make('organization')
                    ->toggleable()->limit(30),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('signature_path')
                    ->label('Signature')
                    ->disk('public')
                    ->height(40)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('letters_count')
                    ->label('Letters')
                    ->counts('letters')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Author $record) {
                        // Clean up uploaded files on delete.
                        if ($record->signature_path) {
                            Storage::disk('public')->delete($record->signature_path);
                        }
                        if ($record->initial_path) {
                            Storage::disk('public')->delete($record->initial_path);
                        }
                    }),
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
            'index'  => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit'   => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
