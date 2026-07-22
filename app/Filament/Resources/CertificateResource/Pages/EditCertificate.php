<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\Documents\CertificateGeneratorService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    /**
     * If the record already has a rendered PDF, editing the source data makes
     * the stored PDF stale. Nudge the user to regenerate rather than doing it
     * silently — a certificate re-render changes the verification hash.
     */
    protected function getSavedNotification(): ?Notification
    {
        $record = $this->getRecord();
        if ($record instanceof Certificate && $record->hasMedia('rendered')) {
            return Notification::make()
                ->warning()
                ->title('Saved — certificate PDF is now stale')
                ->body('Click "Regenerate PDF" to rebuild the certificate with your changes and refresh the verification hash.')
                ->persistent();
        }

        return parent::getSavedNotification();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_pdf')
                ->label('Generate PDF')->icon('heroicon-o-document-arrow-down')->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Generate certificate PDF?')
                ->modalDescription('Renders the certificate via the current template and attaches the PDF to the record.')
                ->action(function () {
                    /** @var Certificate $r */
                    $r = $this->getRecord();
                    app(CertificateGeneratorService::class)->generate($r);
                    Notification::make()->success()->title('Certificate PDF generated')->send();
                    $this->refreshFormData(['issuance_status']);
                })
                ->visible(fn () => $this->getRecord() instanceof Certificate
                    && $this->getRecord()->isValid()
                    && ! $this->getRecord()->hasMedia('rendered')
                ),

            Actions\Action::make('regenerate_pdf')
                ->label('Regenerate PDF')->icon('heroicon-o-arrow-path')->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate certificate PDF?')
                ->modalDescription('Replaces the existing PDF with a fresh render and updates the verification hash.')
                ->modalSubmitActionLabel('Regenerate')
                ->action(function () {
                    /** @var Certificate $r */
                    $r = $this->getRecord();
                    app(CertificateGeneratorService::class)->generate($r);
                    Notification::make()->success()->title('PDF regenerated')
                        ->body('The old certificate PDF has been replaced.')->send();
                })
                ->visible(fn () => $this->getRecord() instanceof Certificate
                    && $this->getRecord()->isValid()
                    && $this->getRecord()->hasMedia('rendered')
                ),

            Actions\Action::make('download_pdf')
                ->label('Download')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn () => $this->getRecord()->getFirstMediaUrl('rendered'))
                ->openUrlInNewTab()
                ->visible(fn () => $this->getRecord() instanceof Certificate && $this->getRecord()->hasMedia('rendered')),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
