<?php

namespace App\Services\BusinessCard;

use Illuminate\Support\Facades\View;
use InvalidArgumentException;

/**
 * Builds the live preview and validation panel from unsaved form state.
 *
 * Input is not saved here. Invalid input is shown as a message rather than a preview,
 * so the preview never shows contact data that could not be printed.
 */
final class CardPreviewData
{
    /** @param  array<string, mixed>  $state  the form's current state */
    public static function html(array $state): string
    {
        try {
            $data = self::contactInput($state);
            $contact = app(BusinessCardContactFactory::class)->fromArray($data);
            $profile = $state['qr_profile'] ?? CardVCardBuilder::PROFILE_COMPACT;
            $vcard = app(CardVCardBuilder::class)->build($contact, $profile);
            $layout = app(CardLayoutEngine::class)->build($contact, $vcard, $profile, 'artwork');

            $issues = $layout->issues;
            foreach (app(CardConsistencyChecker::class)->check($contact, $vcard, $profile) as $message) {
                $issues[] = ['severity' => 'error', 'code' => 'qr_mismatch', 'side' => null, 'message' => $message, 'requires_acknowledgement' => false];
            }

            $view = ($state['preview_foil'] ?? false) ? 'foil' : 'flat';
            $guides = [
                'bleed' => (bool) ($state['guide_bleed'] ?? true),
                'trim' => (bool) ($state['guide_trim'] ?? true),
                'safe' => (bool) ($state['guide_safe'] ?? false),
            ];
            $svg = app(CardSvgPreview::class);

            return View::make('filament.business-card-preview', [
                'front' => $svg->render($layout->front, $view, $guides),
                'back' => $svg->render($layout->back, $view, $guides),
                'issues' => $issues,
                'qr' => $layout->qr,
                'specVersion' => $layout->specVersion,
                'error' => null,
            ])->render();
        } catch (InvalidArgumentException $e) {
            return View::make('filament.business-card-preview', [
                'front' => null, 'back' => null, 'issues' => [], 'qr' => null, 'specVersion' => null,
                'error' => $e->getMessage(),
            ])->render();
        }
    }

    /**
     * The factory's input shape, from the form's repeater state.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function contactInput(array $state): array
    {
        $phones = [];
        foreach ($state['phones'] ?? [] as $row) {
            $phones[] = ['type' => $row['type'] ?? 'office', 'display' => (string) ($row['number'] ?? '')];
        }

        $emails = [];
        foreach ($state['emails'] ?? [] as $row) {
            $emails[] = ['type' => $row['type'] ?? 'work', 'address' => (string) ($row['address'] ?? '')];
        }

        $lines = [];
        foreach ($state['address_lines'] ?? [] as $row) {
            $lines[] = (string) ($row['line'] ?? '');
        }

        return [
            'person_name' => (string) ($state['person_name'] ?? ''),
            'person_title' => (string) ($state['person_title'] ?? ''),
            'phones' => $phones,
            'emails' => $emails,
            'website' => (string) ($state['website'] ?? ''),
            'linkedin_handle' => (string) ($state['linkedin_handle'] ?? ''),
            'address_lines' => array_slice(array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== '')), 0, 3),
        ];
    }
}
