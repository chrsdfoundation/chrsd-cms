<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DonationResource;
use App\Models\Donation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentDonationsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent donations';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Donation::query()->acrossOrganizations()
                    ->with(['person', 'campaign', 'moneyReceipt'])
                    ->latest('receipt_date')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_date')->date(),
                Tables\Columns\TextColumn::make('person.full_name')->label('Donor')->weight('bold'),
                Tables\Columns\TextColumn::make('amount')->money('BDT')->alignEnd(),
                Tables\Columns\TextColumn::make('payment_method')->badge(),
                Tables\Columns\TextColumn::make('campaign.name')->label('Campaign')->limit(30),
                Tables\Columns\IconColumn::make('is_in_kind')->boolean()->label('In-kind'),
                Tables\Columns\TextColumn::make('moneyReceipt.serial_number')
                    ->label('Receipt')->fontFamily('mono')->size('xs'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Donation $r) => DonationResource::getUrl('view', ['record' => $r]))
                    ->size('xs'),
            ])
            ->paginated(false);
    }
}
