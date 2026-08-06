<?php

namespace App\Http\Controllers;

use App\Models\IdCard;
use App\Services\Documents\IdCardGeneratorService;

class PrintIdCardController extends Controller
{
    public function __construct(
        protected IdCardGeneratorService $generator,
    ) {}

    public function show(IdCard $card)
    {
        $html = $this->generator->renderCombined($card);
        $htmlHash = $this->generator->computeHtmlHash($html);

        if ($card->pdf_content_hash_front !== $htmlHash) {
            $card->update(['pdf_content_hash_front' => $htmlHash]);
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
