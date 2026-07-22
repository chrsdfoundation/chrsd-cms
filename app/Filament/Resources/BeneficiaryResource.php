<?php

namespace App\Filament\Resources;

use App\Enums\BeneficiaryStatus;
use App\Filament\Clusters\Crm;
use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Models\BeneficiaryProfile;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BeneficiaryResource extends Resource
{
    protected static ?string $model = BeneficiaryProfile::class;

    protected static ?string $cluster = Crm::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Beneficiaries';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Beneficiary')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('program_ref')->label('Program')->toggleable(),
                Tables\Columns\TextColumn::make('enrolled_on')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('person.phone')->toggleable(),
                Tables\Columns\TextColumn::make('person.email')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(BeneficiaryStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('open_person')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (BeneficiaryProfile $r) => PersonResource::getUrl('view', ['record' => $r->person_id])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBeneficiaries::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('person');
    }
}
