<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Enums\CertificateIssuance;
use App\Filament\Resources\CertificateResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $recordTitleAttribute = 'serial_number';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('certificate_type_id')
                ->relationship('type', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('signed_by_id')
                ->relationship('signedBy', 'last_name', fn (Builder $q) => $q->orderBy('last_name'))
                ->getOptionLabelFromRecordUsing(fn ($r) => $r->full_name)
                ->searchable(['first_name', 'last_name'])->preload(),
            Forms\Components\TextInput::make('purpose')->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('issuance_status')
                ->options(CertificateIssuance::class)
                ->default(CertificateIssuance::Draft->value)->required(),
            Forms\Components\DatePicker::make('issued_on')->native(false),
            Forms\Components\DatePicker::make('valid_until')->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('Serial')
                    ->fontFamily('mono')->size('xs')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('type.code')->badge()->color('info'),
                Tables\Columns\TextColumn::make('purpose')->limit(30),
                Tables\Columns\TextColumn::make('issuance_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
                Tables\Columns\TextColumn::make('issued_on')->date(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                CertificateResource::generatePdfAction(),
                CertificateResource::downloadPdfAction(),
                CertificateResource::qrPreviewAction(),
                CertificateResource::revokeAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
