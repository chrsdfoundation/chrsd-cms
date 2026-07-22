<?php

namespace App\Filament\Resources\MoneyReceiptResource\Pages;

use App\Enums\VerificationStatus;
use App\Filament\Resources\MoneyReceiptResource;
use App\Models\MoneyReceipt;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use Milon\Barcode\DNS2D;

class ViewMoneyReceipt extends ViewRecord
{
    protected static string $resource = MoneyReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print')->icon('heroicon-o-printer')->color('primary')
                ->url(fn () => route('money-receipts.print', $this->getRecord()))
                ->openUrlInNewTab(),

            Actions\Action::make('qr_preview')
                ->label('QR')->icon('heroicon-o-qr-code')->color('info')
                ->modalHeading('Verification QR')
                ->modalContent(function () {
                    /** @var MoneyReceipt $r */
                    $r = $this->getRecord();
                    return new HtmlString(
                        '<div class="flex justify-center p-6">'
                        . (new DNS2D())->getBarcodeSVG($r->qr_code_uri, 'QRCODE', 6, 6)
                        . '</div>'
                        . '<p class="text-center text-sm text-gray-600 break-all px-4 pb-4">'
                        . e($r->qr_code_uri)
                        . '</p>'
                    );
                })
                ->modalSubmitAction(false)->modalCancelActionLabel('Close'),

            Actions\Action::make('revoke')
                ->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
                ->form([Forms\Components\Textarea::make('reason')->required()->label('Reason')->rows(3)])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $this->getRecord()->revoke($data['reason']);
                    Notification::make()->danger()->title('Receipt revoked')->send();
                })
                ->visible(fn () => $this->getRecord()->status === VerificationStatus::Valid),

            Actions\EditAction::make(),
        ];
    }
}
