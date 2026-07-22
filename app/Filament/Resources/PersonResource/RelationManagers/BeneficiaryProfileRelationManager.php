<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Enums\BeneficiaryStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BeneficiaryProfileRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiaryProfile';

    protected static ?string $title = 'Beneficiary profile';

    protected static ?string $icon = 'heroicon-o-heart';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options(BeneficiaryStatus::class)->required()
                ->default(BeneficiaryStatus::Enrolled->value),
            Forms\Components\TextInput::make('program_ref')
                ->label('Program / project reference')->maxLength(255),
            Forms\Components\DatePicker::make('enrolled_on')->native(false),
            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('program_ref')->label('Program'),
                Tables\Columns\TextColumn::make('enrolled_on')->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->beneficiaryProfile()->doesntExist()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
