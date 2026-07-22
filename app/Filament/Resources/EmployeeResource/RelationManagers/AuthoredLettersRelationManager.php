<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Enums\OfficialLetterStatus;
use App\Filament\Resources\OfficialLetterResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuthoredLettersRelationManager extends RelationManager
{
    protected static string $relationship = 'authoredLetters';

    protected static ?string $recordTitleAttribute = 'subject';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('letter_category_id')
                ->relationship('category', 'name')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('subject')->required()->columnSpanFull(),
            Forms\Components\RichEditor::make('body')->required()->columnSpanFull(),
            Forms\Components\Select::make('letter_status')
                ->options(OfficialLetterStatus::class)->default(OfficialLetterStatus::Draft->value)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('Serial')
                    ->fontFamily('mono')->size('xs')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('category.code')->badge()->color('info'),
                Tables\Columns\TextColumn::make('subject')->limit(40),
                Tables\Columns\TextColumn::make('letter_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
                Tables\Columns\TextColumn::make('dated_on')->date(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                OfficialLetterResource::generatePdfAction(),
                OfficialLetterResource::downloadPdfAction(),
                OfficialLetterResource::qrPreviewAction(),
                OfficialLetterResource::revokeAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
