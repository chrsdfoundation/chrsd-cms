<?php

namespace App\Services\BusinessCard\Pdf;

use App\Services\BusinessCard\CardLayout;

/**
 * Turns a laid-out card into PDF bytes. Implementations draw the layout's
 * primitives and must not add content of their own, so the artwork and the
 * on-screen preview always show the same thing.
 */
interface CardPdfRenderer
{
    /** Print artwork: CMYK, spot Silver_Foil, registration marks, two pages (front, back). */
    public function renderArtwork(CardLayout $layout): string;

    /** Foil plate: only the foil shapes, in K100 on white, same page geometry. */
    public function renderFoilPlate(CardLayout $layout): string;

    /** @return array{name: string, version: string} */
    public function engine(): array;
}
