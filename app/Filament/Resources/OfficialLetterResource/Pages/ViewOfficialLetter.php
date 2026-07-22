<?php

namespace App\Filament\Resources\OfficialLetterResource\Pages;

use App\Enums\OfficialLetterStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\OfficialLetterResource;
use App\Models\OfficialLetter;
use App\Services\Documents\LetterGeneratorService;
use App\Services\Verification\QrCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewOfficialLetter extends ViewRecord
{
    protected static string $resource = OfficialLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_pdf')
                ->label('Generate & Release')->icon('heroicon-o-paper-airplane')->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var OfficialLetter $r */
                    $r = $this->getRecord();
                    app(LetterGeneratorService::class)->generate($r);
                    Notification::make()->success()->title('Letter generated and released')->send();
                })
                ->visible(fn () => $this->getRecord()->isValid()
                    && in_array($this->getRecord()->letter_status, [OfficialLetterStatus::Draft, OfficialLetterStatus::ForReview, OfficialLetterStatus::Approved])
                ),

            Actions\Action::make('regenerate_pdf')
                ->label('Regenerate PDF')->icon('heroicon-o-arrow-path')->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate letter PDF?')
                ->modalDescription('This replaces the existing PDF with a fresh render based on the current record and updates the verification hash.')
                ->modalSubmitActionLabel('Regenerate')
                ->action(function () {
                    /** @var OfficialLetter $r */
                    $r = $this->getRecord();
                    app(LetterGeneratorService::class)->generate($r);
                    Notification::make()->success()->title('PDF regenerated')
                        ->body('The old PDF has been replaced.')->send();
                })
                ->visible(fn () => $this->getRecord()->isValid()
                    && $this->getRecord()->letter_status === OfficialLetterStatus::Released
                ),

            Actions\Action::make('download_pdf')
                ->label('Download')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn () => $this->getRecord()->getFirstMediaUrl('rendered'))
                ->openUrlInNewTab()
                ->visible(fn () => $this->getRecord()->hasMedia('rendered')),

            Actions\Action::make('qr_preview')
                ->label('QR')->icon('heroicon-o-qr-code')->color('info')
                ->modalHeading('Verification QR')
                ->modalContent(fn () => new HtmlString(
                    '<div class="flex justify-center p-6">'
                    . app(QrCodeService::class)->svg($this->getRecord(), 6)
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
                    Notification::make()->danger()->title('Letter revoked')->send();
                })
                ->visible(fn () => $this->getRecord()->status === VerificationStatus::Valid),

            Actions\EditAction::make(),
        ];
    }
}
