<?php

namespace App\Http\Controllers;

use App\Models\OfficialLetter;
use App\Services\Documents\LetterGeneratorService;

class PrintLetterController extends Controller
{
    public function __construct(
        protected LetterGeneratorService $generator,
    ) {}

    public function show(OfficialLetter $letter)
    {
        $html = $this->generator->renderHtml($letter);
        $htmlHash = $this->generator->computeHtmlHash($html);

        if ($letter->pdf_content_hash !== $htmlHash) {
            $letter->update(['pdf_content_hash' => $htmlHash]);
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
