<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use App\Filament\Resources\MoneyReceiptResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_receipt')
                ->label('Open receipt')->icon('heroicon-o-document-text')
                ->url(fn () => MoneyReceiptResource::getUrl('view', ['record' => $this->getRecord()->money_receipt_id]))
                ->visible(fn () => (bool) $this->getRecord()->money_receipt_id)
                ->openUrlInNewTab(),

            Actions\Action::make('generate_receipt')
                ->label('Generate receipt')->icon('heroicon-o-plus')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $r = $this->getRecord()->generateReceipt();
                    if ($r) {
                        Notification::make()
                            ->success()->title("Receipt {$r->serial_number} generated")->send();
                    } else {
                        Notification::make()
                            ->danger()->title('Could not generate receipt')
                            ->body('In-kind donations do not have receipts, or a receipt already exists.')
                            ->send();
                    }
                    $this->refreshFormData(['money_receipt_id']);
                })
                ->visible(fn () => ! $this->getRecord()->money_receipt_id && ! $this->getRecord()->is_in_kind),

            Actions\EditAction::make(),
        ];
    }
}
