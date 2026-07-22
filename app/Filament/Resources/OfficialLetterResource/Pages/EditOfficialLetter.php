<?php

namespace App\Filament\Resources\OfficialLetterResource\Pages;

use App\Enums\OfficialLetterStatus;
use App\Filament\Resources\OfficialLetterResource;
use App\Models\OfficialLetter;
use App\Services\Documents\LetterGeneratorService;
use App\Support\VisaLetterSubstitutor;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOfficialLetter extends EditRecord
{
    protected static string $resource = OfficialLetterResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['body'])) {
            $data['body'] = VisaLetterSubstitutor::apply($data['body'], $data);
        }
        foreach (array_keys($data) as $k) {
            if (str_starts_with($k, 'visa_')) {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * After saving edits on a Released letter, the stored PDF is now stale.
     * Nudge HR to regenerate rather than silently doing it — some edits are
     * bookkeeping (dated_on, released_on) that don't need a re-render, and
     * regeneration mutates the verification hash.
     */
    protected function getSavedNotification(): ?Notification
    {
        $record = $this->getRecord();
        if ($record instanceof OfficialLetter && $record->letter_status === OfficialLetterStatus::Released) {
            return Notification::make()
                ->warning()
                ->title('Saved — PDF is now stale')
                ->body('Click "Regenerate PDF" to rebuild the letter with your changes and refresh the verification hash.')
                ->persistent();
        }

        return parent::getSavedNotification();
    }

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
                    $this->refreshFormData(['letter_status', 'released_on']);
                })
                ->visible(fn () => $this->getRecord() instanceof OfficialLetter
                    && $this->getRecord()->isValid()
                    && in_array($this->getRecord()->letter_status, [
                        OfficialLetterStatus::Draft,
                        OfficialLetterStatus::ForReview,
                        OfficialLetterStatus::Approved,
                    ], true)
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
                ->visible(fn () => $this->getRecord() instanceof OfficialLetter
                    && $this->getRecord()->isValid()
                    && $this->getRecord()->letter_status === OfficialLetterStatus::Released
                ),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
