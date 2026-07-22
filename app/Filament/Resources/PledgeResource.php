<?php

namespace App\Filament\Resources;

use App\Enums\PledgeStatus;
use App\Filament\Resources\PledgeResource\Pages;
use App\Models\Campaign;
use App\Models\Person;
use App\Models\Pledge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PledgeResource extends Resource
{
    protected static ?string $model = Pledge::class;

    protected static ?string $cluster = \App\Filament\Clusters\Fundraising::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Donor & Campaign')->columns(2)->schema([
                Forms\Components\Select::make('person_id')->label('Donor')
                    ->required()
                    ->options(fn () => Person::query()->orderBy('full_name')->limit(200)->pluck('full_name', 'id'))
                    ->getSearchResultsUsing(fn (string $search) => Person::query()
                        ->where('full_name', 'like', "%{$search}%")->limit(30)->pluck('full_name', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('campaign_id')->label('Campaign')
                    ->options(fn () => Campaign::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ]),

            Forms\Components\Section::make('Pledge')->columns(2)->schema([
                Forms\Components\TextInput::make('promised_amount')
                    ->required()->numeric()->minValue(0.01)->step(0.01)->prefix('৳'),
                Forms\Components\Select::make('currency')
                    ->options(['BDT' => 'BDT', 'USD' => 'USD'])->default('BDT'),
                Forms\Components\DatePicker::make('due_date')->required()->native(false),
                Forms\Components\Select::make('status')
                    ->options(PledgeStatus::class)->default(PledgeStatus::Open->value)->required(),
                Forms\Components\TextInput::make('fulfilled_amount')
                    ->numeric()->minValue(0)->step(0.01)->prefix('৳')->default(0),
                Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Donor')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('campaign.name')->label('Campaign')->toggleable()->limit(30),
                Tables\Columns\TextColumn::make('promised_amount')->money('BDT')->alignEnd(),
                Tables\Columns\TextColumn::make('fulfilled_amount')->money('BDT')->alignEnd()->toggleable(),
                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Outstanding')->money('BDT')->alignEnd()
                    ->state(fn (Pledge $r) => $r->outstandingAmount()),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(PledgeStatus::class),
                Tables\Filters\SelectFilter::make('campaign_id')->relationship('campaign', 'name'),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue only')
                    ->query(fn (Builder $q) => $q->where('status', PledgeStatus::Overdue->value)
                        ->orWhere(fn ($qq) => $qq->where('status', PledgeStatus::Open->value)
                            ->whereDate('due_date', '<', now()->toDateString())))
                    ->toggle(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_fulfilled')
                    ->label('Mark fulfilled')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()
                    ->action(function (Pledge $record) {
                        $record->forceFill([
                            'status'           => PledgeStatus::Fulfilled,
                            'fulfilled_amount' => $record->promised_amount,
                        ])->save();
                    })
                    ->visible(fn (Pledge $r) => ! in_array($r->status, [PledgeStatus::Fulfilled, PledgeStatus::WrittenOff])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPledges::route('/'),
            'create' => Pages\CreatePledge::route('/create'),
            'view'   => Pages\ViewPledge::route('/{record}'),
            'edit'   => Pages\EditPledge::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['person', 'campaign']);
    }
}
