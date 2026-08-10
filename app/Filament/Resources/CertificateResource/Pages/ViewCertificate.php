<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\VerificationStatus;
use App\Filament\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('print_pdf')
                ->label('🖨️ Print / Save as PDF')->icon('heroicon-o-printer')->color('primary')
                ->url(fn (Certificate $r) => route('print.certificate', $r))
                ->openUrlInNewTab(),

            Actions\Action::make('qr_preview')
                ->label('QR')->icon('heroicon-o-qr-code')->color('info')
                ->modalHeading('Verification QR')
                ->modalContent(fn () => new HtmlString(
                    '<div class="flex justify-center p-6">'
                    . app(QrCodeService::class)->svg($this->getRecord())
                    . '</div>'
                    . '<p class="text-center text-sm text-gray-600 break-all">'
                    . e(app(QrCodeService::class)->verificationUrl($this->getRecord()))
                    . '</p>'
                ))
                ->modalSubmitAction(false)->modalCancelActionLabel('Close'),

            Actions\Action::make('revoke')
                ->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
                ->form([Forms\Components\Textarea::make('reason')->required()->label('Reason')->rows(3)])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $this->getRecord()->revoke($data['reason']);
                    Notification::make()->danger()->title('Certificate revoked')->send();
                })
                ->visible(fn () => $this->getRecord()->status === VerificationStatus::Valid),
        ];
    }
}
