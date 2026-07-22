<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateTypeResource\Pages;
use App\Models\CertificateType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateTypeResource extends Resource
{
    protected static ?string $model = CertificateType::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('code')
                    ->required()->maxLength(32)->unique(ignoreRecord: true)
                    ->helperText('e.g. COE, TRN, SR'),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('template_view')
                    ->placeholder('documents.certificates.default')
                    ->helperText('Blade view path. Leave blank to use default template.'),
                Forms\Components\TextInput::make('validity_days')
                    ->numeric()->minValue(0)
                    ->helperText('Days after issuance until expiry. Blank = no expiry.'),
                Forms\Components\Toggle::make('requires_approval')->default(true)->inline(false),
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
            ]),

            Forms\Components\Section::make('Default Fields')
                ->description('Template placeholders that will be pre-filled when a certificate of this type is issued.')
                ->collapsible()
                ->schema([
                    Forms\Components\KeyValue::make('default_fields')
                        ->keyLabel('Field')->valueLabel('Default Value')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('validity_days')->sortable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('requires_approval')->boolean(),
                Tables\Columns\TextColumn::make('certificates_count')->label('Issued')
                    ->counts('certificates')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('requires_approval'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription(fn (CertificateType $record) => $record->certificates()->withTrashed()->count() > 0
                        ? 'This type has certificates attached. They will become uncategorized but will not be deleted.'
                        : 'This action cannot be undone.')
                    ->before(fn (CertificateType $record) => $record->certificates()->withTrashed()->update(['certificate_type_id' => null])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(fn (\Illuminate\Support\Collection $records) => $records->each(
                            fn (CertificateType $r) => $r->certificates()->withTrashed()->update(['certificate_type_id' => null])
                        )),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCertificateTypes::route('/'),
            'create' => Pages\CreateCertificateType::route('/create'),
            'view'   => Pages\ViewCertificateType::route('/{record}'),
            'edit'   => Pages\EditCertificateType::route('/{record}/edit'),
        ];
    }
}
