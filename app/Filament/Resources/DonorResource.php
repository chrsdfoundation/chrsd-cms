<?php

namespace App\Filament\Resources;

use App\Enums\EngagementTier;
use App\Filament\Resources\DonorResource\Pages;
use App\Models\DonorProfile;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonorResource extends Resource
{
    protected static ?string $model = DonorProfile::class;

    protected static ?string $cluster = \App\Filament\Clusters\Crm::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-bangladeshi';

    protected static ?string $navigationLabel = 'Donors';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Donor')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('engagement_tier')->badge(),
                Tables\Columns\TextColumn::make('person.email')->toggleable(),
                Tables\Columns\TextColumn::make('person.phone')->toggleable(),
                Tables\Columns\TextColumn::make('first_donated_at')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('preferred_method')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('lifetime_total')
                    ->label('Lifetime')
                    ->money('BDT')->alignEnd()
                    ->state(fn (DonorProfile $r) => $r->lifetimeTotal()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('engagement_tier')->options(EngagementTier::class),
            ])
            ->actions([
                Tables\Actions\Action::make('open_person')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DonorProfile $r) => PersonResource::getUrl('view', ['record' => $r->person_id])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonors::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('person');
    }
}
