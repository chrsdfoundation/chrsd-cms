<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Enums\VolunteerStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VolunteerProfileRelationManager extends RelationManager
{
    protected static string $relationship = 'volunteerProfile';

    protected static ?string $title = 'Volunteer profile';

    protected static ?string $icon = 'heroicon-o-hand-raised';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options(VolunteerStatus::class)->required()
                ->default(VolunteerStatus::Prospective->value),
            Forms\Components\DatePicker::make('joined_on')->native(false),
            Forms\Components\TagsInput::make('skills')->placeholder('e.g. Data entry, First aid, Photography'),
            Forms\Components\TagsInput::make('availability')->placeholder('e.g. Weekends, Evenings')
                ->helperText('Free-text tags for scheduling.'),
            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('joined_on')->date(),
                Tables\Columns\TextColumn::make('skills')->badge()->separator(','),
                Tables\Columns\TextColumn::make('availability')->badge()->separator(','),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->volunteerProfile()->doesntExist()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
