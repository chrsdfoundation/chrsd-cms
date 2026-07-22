<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\RecurringCadence;
use App\Filament\Clusters\Fundraising;
use App\Filament\Resources\DonationResource\Pages;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $cluster = Fundraising::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getGloballySearchableAttributes(): array
    {
        return ['person.full_name', 'campaign.name', 'reference_no', 'notes'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Donor & Campaign')->columns(2)->schema([
                Forms\Components\Select::make('person_id')->label('Donor')
                    ->required()
                    ->options(fn () => Person::query()->orderBy('full_name')->limit(200)->pluck('full_name', 'id'))
                    ->getSearchResultsUsing(fn (string $search) => Person::query()
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->limit(30)->pluck('full_name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('full_name')->required(),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('phone'),
                    ])
                    ->createOptionUsing(fn (array $data) => Person::create($data)->getKey()),

                Forms\Components\Select::make('campaign_id')->label('Campaign (optional)')
                    ->options(fn () => Campaign::query()->where('is_active', true)
                        ->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ]),

            Forms\Components\Section::make('Gift Details')->columns(2)->schema([
                Forms\Components\DatePicker::make('receipt_date')
                    ->required()->default(now())->native(false),

                Forms\Components\Toggle::make('is_in_kind')
                    ->label('In-kind donation (goods / services)')
                    ->helperText('When on, no MoneyReceipt is auto-created.')
                    ->live()->inline(false),

                // Cash-only fields ---------------------------------------
                Forms\Components\TextInput::make('amount')
                    ->required()->numeric()->minValue(0.01)->step(0.01)->prefix('৳')
                    ->visible(fn (Forms\Get $g) => ! $g('is_in_kind')),

                Forms\Components\Select::make('payment_method')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Cash->value)
                    ->required(fn (Forms\Get $g) => ! $g('is_in_kind'))
                    ->visible(fn (Forms\Get $g) => ! $g('is_in_kind')),

                Forms\Components\TextInput::make('reference_no')
                    ->label('Reference / Txn ID')->maxLength(100)
                    ->visible(fn (Forms\Get $g) => ! $g('is_in_kind')),

                // In-kind-only fields -----------------------------------
                Forms\Components\TextInput::make('in_kind_description')
                    ->label('In-kind item description')->maxLength(255)
                    ->required(fn (Forms\Get $g) => (bool) $g('is_in_kind'))
                    ->visible(fn (Forms\Get $g) => (bool) $g('is_in_kind'))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('in_kind_valuation')
                    ->label('Fair market valuation')->numeric()->prefix('৳')
                    ->visible(fn (Forms\Get $g) => (bool) $g('is_in_kind')),

                // We still store an amount for in-kind so aggregate queries work;
                // default it to the valuation before save (see mutateFormDataBeforeCreate).
                Forms\Components\Hidden::make('currency')->default('BDT'),
            ]),

            Forms\Components\Section::make('Recurring')->columns(2)->schema([
                Forms\Components\Toggle::make('is_recurring')
                    ->helperText('Marks this row as the start of a standing-order series.')
                    ->live()->inline(false),
                Forms\Components\Select::make('recurring_cadence')
                    ->options(RecurringCadence::class)
                    ->visible(fn (Forms\Get $g) => (bool) $g('is_recurring'))
                    ->required(fn (Forms\Get $g) => (bool) $g('is_recurring')),
                Forms\Components\Select::make('parent_donation_id')->label('Belongs to recurring series')
                    ->options(fn () => Donation::query()->where('is_recurring', true)
                        ->orderByDesc('id')->limit(50)
                        ->get()->mapWithKeys(fn ($d) => [$d->id => "#{$d->id} — {$d->person?->full_name}"]))
                    ->searchable()
                    ->helperText('Optional: link this gift to a parent recurring donation.'),
            ])->collapsed(fn (Forms\Get $g, ?Donation $record) => ! ($record?->is_recurring || $record?->parent_donation_id)),

            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('Donor')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('campaign.name')->label('Campaign')->toggleable()->limit(30),
                Tables\Columns\TextColumn::make('amount')->money('BDT')->alignEnd()->sortable(),
                Tables\Columns\TextColumn::make('payment_method')->badge()->toggleable(),
                Tables\Columns\IconColumn::make('is_in_kind')->boolean()->label('In-kind'),
                Tables\Columns\IconColumn::make('is_recurring')->boolean()->label('Recurring'),
                Tables\Columns\TextColumn::make('moneyReceipt.serial_number')
                    ->label('Receipt')->fontFamily('mono')->size('xs')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_id')->relationship('campaign', 'name'),
                Tables\Filters\SelectFilter::make('payment_method')->options(PaymentMethod::class),
                Tables\Filters\TernaryFilter::make('is_in_kind'),
                Tables\Filters\TernaryFilter::make('is_recurring'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_receipt')
                    ->label('Receipt')->icon('heroicon-o-document-text')
                    ->url(fn (Donation $r) => $r->money_receipt_id
                        ? MoneyReceiptResource::getUrl('view', ['record' => $r->money_receipt_id])
                        : null)
                    ->visible(fn (Donation $r) => (bool) $r->money_receipt_id)
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('receipt_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'create' => Pages\CreateDonation::route('/create'),
            'view' => Pages\ViewDonation::route('/{record}'),
            'edit' => Pages\EditDonation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['person', 'campaign', 'moneyReceipt']);
    }
}
