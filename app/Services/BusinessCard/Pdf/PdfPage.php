<?php

namespace App\Services\BusinessCard\Pdf;

/**
 * One page's content stream and boxes. Every method appends a PDF operator,
 * so the content stream is exactly the sequence of calls made here.
 *
 * Units are points, origin at the bottom-left, as in PDF.
 */
final class PdfPage
{
    /** @var array{0: float, 1: float, 2: float, 3: float} */
    private array $mediaBox;

    /** @var array{0: float, 1: float, 2: float, 3: float} */
    private array $trimBox;

    /** @var array{0: float, 1: float, 2: float, 3: float} */
    private array $bleedBox;

    /** @var array{0: float, 1: float, 2: float, 3: float} */
    private array $artBox;

    private string $ops = '';

    public function __construct(
        private readonly PdfDocument $document,
        float $widthPt,
        float $heightPt,
    ) {
        $this->mediaBox = [0.0, 0.0, $widthPt, $heightPt];
        $this->trimBox = $this->mediaBox;
        $this->bleedBox = $this->mediaBox;
        $this->artBox = $this->mediaBox;
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $trim  x0, y0, x1, y1
     * @param  array{0: float, 1: float, 2: float, 3: float}  $bleed
     */
    public function setBoxes(array $trim, array $bleed): void
    {
        $this->trimBox = $trim;
        $this->bleedBox = $bleed;
        $this->artBox = $trim;
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function mediaBox(): array
    {
        return $this->mediaBox;
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function trimBox(): array
    {
        return $this->trimBox;
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function bleedBox(): array
    {
        return $this->bleedBox;
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function artBox(): array
    {
        return $this->artBox;
    }

    public function content(): string
    {
        return $this->ops;
    }

    public function save(): void
    {
        $this->ops .= "q\n";
    }

    public function restore(): void
    {
        $this->ops .= "Q\n";
    }

    /** @param  array{0: float, 1: float, 2: float, 3: float}  $cmyk  0.0-1.0 */
    public function fillCmyk(array $cmyk): void
    {
        $this->ops .= $this->list($cmyk) . " k\n";
    }

    /** @param  array{0: float, 1: float, 2: float, 3: float}  $cmyk  0.0-1.0 */
    public function strokeCmyk(array $cmyk): void
    {
        $this->ops .= $this->list($cmyk) . " K\n";
    }

    /** Fill with a named Separation colour at the given tint (0-1). */
    public function fillSpot(string $resourceName, float $tint): void
    {
        $this->ops .= "/{$resourceName} cs " . $this->num($tint) . " scn\n";
    }

    /** Stroke with a named Separation colour at the given tint (0-1). */
    public function strokeSpot(string $resourceName, float $tint): void
    {
        $this->ops .= "/{$resourceName} CS " . $this->num($tint) . " SCN\n";
    }

    /** Select an ExtGState resource (overprint on or off). */
    public function useState(string $resourceName): void
    {
        $this->ops .= "/{$resourceName} gs\n";
    }

    public function lineWidth(float $width): void
    {
        $this->ops .= $this->num($width) . " w\n";
    }

    public function lineCap(int $style): void
    {
        $this->ops .= "{$style} J\n";
    }

    public function lineJoin(int $style): void
    {
        $this->ops .= "{$style} j\n";
    }

    public function moveTo(float $x, float $y): void
    {
        $this->ops .= $this->num($x) . ' ' . $this->num($y) . " m\n";
    }

    public function lineTo(float $x, float $y): void
    {
        $this->ops .= $this->num($x) . ' ' . $this->num($y) . " l\n";
    }

    public function curveTo(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $this->ops .= $this->list([$x1, $y1, $x2, $y2, $x3, $y3]) . " c\n";
    }

    public function closePath(): void
    {
        $this->ops .= "h\n";
    }

    public function rect(float $x, float $y, float $width, float $height): void
    {
        $this->ops .= $this->list([$x, $y, $width, $height]) . " re\n";
    }

    /** Fill the current path with the non-zero winding rule. */
    public function fill(): void
    {
        $this->ops .= "f\n";
    }

    /** Stroke the current path. */
    public function stroke(): void
    {
        $this->ops .= "S\n";
    }

    /** Use the current path as a non-zero clipping region for later painting. */
    public function clip(): void
    {
        $this->ops .= "W n\n";
    }

    /** Paint an axial shading resource, clipped to the current clipping path. */
    public function shade(string $shadingName): void
    {
        $this->ops .= "/{$shadingName} sh\n";
    }

    /**
     * Draw a line of text. $baseline is the baseline y in points; $tracking is
     * extra space after each glyph, in em. The string is encoded through the
     * font's subset, which records every glyph used.
     */
    public function text(TrueTypeFont $font, string $utf8, float $sizePt, float $x, float $baseline, float $tracking = 0.0): void
    {
        $name = $this->document->font($font);
        $hex = $font->encode($utf8);

        $this->ops .= "BT\n";
        $this->ops .= "/{$name} " . $this->num($sizePt) . " Tf\n";
        if ($tracking !== 0.0) {
            $this->ops .= $this->num($tracking * $sizePt) . " Tc\n";
        }
        $this->ops .= '1 0 0 1 ' . $this->num($x) . ' ' . $this->num($baseline) . " Tm\n";
        $this->ops .= "<{$hex}> Tj\n";
        $this->ops .= "ET\n";
    }

    /** @param  array<int, float>  $values */
    private function list(array $values): string
    {
        return implode(' ', array_map(fn (float $v) => $this->num($v), $values));
    }

    private function num(float $value): string
    {
        return $this->document->num($value);
    }
}
