<?php

namespace App\Filament\Resources;

use App\Enums\VolunteerStatus;
use App\Filament\Resources\VolunteerResource\Pages;
use App\Models\VolunteerProfile;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VolunteerResource extends Resource
{
    protected static ?string $model = VolunteerProfile::class;

    protected static ?string $cluster = \App\Filament\Clusters\Crm::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'Volunteers';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Volunteer')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('skills')->badge()->separator(',')->toggleable(),
                Tables\Columns\TextColumn::make('availability')->badge()->separator(',')->toggleable(),
                Tables\Columns\TextColumn::make('joined_on')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('person.phone')->toggleable(),
                Tables\Columns\TextColumn::make('person.email')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(VolunteerStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('open_person')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (VolunteerProfile $r) => PersonResource::getUrl('view', ['record' => $r->person_id])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVolunteers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('person');
    }
}
