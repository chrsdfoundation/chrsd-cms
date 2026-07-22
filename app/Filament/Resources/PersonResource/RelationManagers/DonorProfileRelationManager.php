<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Enums\EngagementTier;
use App\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DonorProfileRelationManager extends RelationManager
{
    protected static string $relationship = 'donorProfile';

    protected static ?string $title = 'Donor profile';

    protected static ?string $icon = 'heroicon-o-currency-bangladeshi';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('engagement_tier')
                ->options(EngagementTier::class)->required()
                ->default(EngagementTier::Cold->value),
            Forms\Components\DatePicker::make('first_donated_at')->native(false),
            Forms\Components\Select::make('preferred_method')
                ->options(PaymentMethod::class),
            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('engagement_tier')
            ->columns([
                Tables\Columns\TextColumn::make('engagement_tier')->badge(),
                Tables\Columns\TextColumn::make('first_donated_at')->date(),
                Tables\Columns\TextColumn::make('preferred_method')->badge(),
                Tables\Columns\TextColumn::make('notes')->limit(50)->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->donorProfile()->doesntExist()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
