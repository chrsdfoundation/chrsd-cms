<?php

namespace App\Http\Controllers;

use App\Models\BusinessCard;
use App\Services\BusinessCard\BusinessCardContactFactory;
use App\Services\BusinessCard\CardLayoutEngine;
use App\Services\BusinessCard\CardSvgPreview;
use App\Services\BusinessCard\CardVCardBuilder;
use Illuminate\View\View;
use InvalidArgumentException;

class PrintBusinessCardController extends Controller
{
    public function show(BusinessCard $card): View
    {
        try {
            $contact = app(BusinessCardContactFactory::class)->fromArray([
                'person_name' => $card->person_name,
                'person_title' => $card->person_title,
                'phones' => $card->phones ?? [],
                'emails' => $card->emails ?? [],
                'website' => $card->website,
                'linkedin_handle' => $card->linkedin_handle,
                'address_lines' => $card->address_lines ?? [],
            ]);

            $profile = $card->qr_profile ?: CardVCardBuilder::PROFILE_COMPACT;
            $vcard = app(CardVCardBuilder::class)->build($contact, $profile);
            $layout = app(CardLayoutEngine::class)->build($contact, $vcard, $profile, 'artwork');
        } catch (InvalidArgumentException $e) {
            return $this->view($card, null, null, [], $e->getMessage());
        }

        $svg = app(CardSvgPreview::class);
        $guides = ['bleed' => false, 'trim' => false, 'safe' => false];

        return $this->view(
            $card,
            $svg->render($layout->front, 'flat', $guides),
            $svg->render($layout->back, 'flat', $guides),
            $layout->issues,
            null,
        );
    }

    /** @param  list<array<string, mixed>>  $issues */
    private function view(BusinessCard $card, ?string $front, ?string $back, array $issues, ?string $error): View
    {
        return view('print.business-card', [
            'card' => $card,
            'front' => $front,
            'back' => $back,
            'issues' => $issues,
            'error' => $error,
        ]);
    }
}
