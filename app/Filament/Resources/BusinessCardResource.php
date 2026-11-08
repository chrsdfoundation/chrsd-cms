<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Documents;
use App\Filament\Resources\BusinessCardResource\Pages;
use App\Models\BusinessCard;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class BusinessCardResource extends Resource
{
    protected static ?string $model = BusinessCard::class;

    protected static ?string $cluster = Documents::class;

    protected static ?string $slug = 'business-cards';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 26;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'company_name', 'work_email', 'personal_email'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('organization_id')
                ->default(fn () => config('chrsd.org_id') ?? 1)
                ->dehydrated(true),

            Forms\Components\Section::make('Card Holder')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->required()
                        ->maxLength(160)
                        ->placeholder('e.g. Mohammad Rahim'),

                    Forms\Components\TextInput::make('job_title')
                        ->maxLength(128)
                        ->placeholder('e.g. Program Director'),

                    Forms\Components\TextInput::make('company_name')
                        ->required()
                        ->maxLength(255)
                        ->default('Centre for Humanitarian Research and Social Development Foundation (CHRSD)')
                        ->placeholder('e.g. CHRSD Foundation'),
                ]),

            Forms\Components\Section::make('Contact Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(32)
                        ->placeholder('+880-2-12345678'),

                    Forms\Components\TextInput::make('mobile')
                        ->tel()
                        ->maxLength(32)
                        ->placeholder('+8801700000000'),

                    Forms\Components\TextInput::make('whatsapp')
                        ->tel()
                        ->maxLength(32)
                        ->placeholder('+8801700000000'),

                    Forms\Components\TextInput::make('work_email')
                        ->label('Work email')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('name@chrsd.org'),

                    Forms\Components\TextInput::make('personal_email')
                        ->label('Personal email')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('name@gmail.com'),

                    Forms\Components\TextInput::make('website')
                        ->url()
                        ->maxLength(255)
                        ->default('https://chrsd.org')
                        ->placeholder('https://chrsd.org'),

                    Forms\Components\TextInput::make('linkedin')
                        ->maxLength(255)
                        ->placeholder('johndoe or https://linkedin.com/in/johndoe')
                        ->helperText('LinkedIn handle or full URL'),
                ]),

            Forms\Components\Section::make('Card Content')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('tagline')
                        ->maxLength(100)
                        ->placeholder('Empowering Communities Through Education')
                        ->helperText('Maximum 6 words')
                        ->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if ($value && str_word_count(trim($value)) > 6) {
                                        $fail('The tagline must not exceed 6 words.');
                                    }
                                };
                            },
                        ]),

                    Forms\Components\Toggle::make('include_bleed')
                        ->label('Include bleed area')
                        ->helperText(new HtmlString('Adds 3 mm bleed per side (91×61 mm). <strong>Your printer\'s spec takes priority.</strong>'))
                        ->default(false),

                    Forms\Components\Textarea::make('address')
                        ->columnSpanFull()
                        ->rows(2)
                        ->maxLength(500)
                        ->default('29 Toyenbee Circular Road (5th Floor), Motijheel C/A, Dhaka-1000, Bangladesh')
                        ->placeholder('Registered address'),
                ]),

            Forms\Components\Section::make('Logo')
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('logo')
                        ->collection('logo')
                        ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(4096)
                        ->helperText(new HtmlString('SVG recommended for sharp printing. Raster images (PNG/JPEG) should be at least 300 DPI at printed size (~240 px wide).')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('work_email')
                    ->label('Work email')
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('personal_email')
                    ->label('Personal email')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mobile')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning'),
                self::printAction(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->label('More'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessCards::route('/'),
            'create' => Pages\CreateBusinessCard::route('/create'),
            'edit' => Pages\EditBusinessCard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function printAction(): Tables\Actions\Action
    {
        return self::applyPrintShape(Tables\Actions\Action::make('print'));
    }

    public static function printHeaderAction(): Action
    {
        return self::applyPrintShape(Action::make('print'));
    }

    private static function applyPrintShape(Tables\Actions\Action|Action $action): Tables\Actions\Action|Action
    {
        return $action
            ->label('Print / Save as PDF')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn ($record) => route('print.business-card', $record), shouldOpenInNewTab: true);
    }
}
