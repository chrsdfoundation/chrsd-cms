<?php

namespace App\Services\BusinessCard\Pdf;

use App\Services\BusinessCard\CardAssets;
use App\Services\BusinessCard\CardLayout;

/**
 * Draws a CardLayout with the pure-PHP PDF writer (see ENGINE_DECISION.md).
 *
 * Page geometry is fixed by the spec: 306 x 198 pt media, 252 x 144 pt trim, and
 * an 18 pt bleed box. Every primitive is drawn in points, in page coordinates.
 */
final class CustomPdfCardRenderer implements CardPdfRenderer
{
    public const FOIL_SPOT = 'Silver_Foil';

    private const TRIM = [27.0, 27.0, 279.0, 171.0];

    private const BLEED = [18.0, 18.0, 288.0, 180.0];

    private const MEDIA = [0.0, 0.0, 306.0, 198.0];

    public function __construct(
        private readonly string $creationDate = 'D:20260101000000Z',
    ) {}

    public function renderArtwork(CardLayout $layout): string
    {
        return $this->document($layout, plate: false, title: 'CHRSD executive card - print artwork');
    }

    public function renderFoilPlate(CardLayout $layout): string
    {
        return $this->document($layout, plate: true, title: 'CHRSD executive card - silver foil plate');
    }

    /** @return array{name: string, version: string} */
    public function engine(): array
    {
        return [
            'name' => (string) config('business_card.engine.name'),
            'version' => (string) config('business_card.engine.version'),
        ];
    }

    private function document(CardLayout $layout, bool $plate, string $title): string
    {
        $doc = new PdfDocument($title, 'CHRSD', 'Business card 3.5 x 2 in, 0.125 in bleed, crop marks', 'CHRSD card PDF writer', $this->creationDate);

        foreach (['front' => $layout->front, 'back' => $layout->back] as $list) {
            $page = $doc->addPage(self::MEDIA[2], self::MEDIA[3]);
            $page->setBoxes(self::TRIM, self::BLEED);

            foreach ($list as $primitive) {
                if ($plate && ! $this->belongsOnPlate($primitive)) {
                    continue;
                }
                $this->draw($doc, $page, $primitive, $plate);
            }
        }

        // The plate uses only the registration colourant; the artwork also has the foil.
        return $doc->output();
    }

    /** Foil shapes, crop marks and the slug belong on the plate. Everything else does not. */
    private function belongsOnPlate(array $p): bool
    {
        if (($p['name'] ?? '') === 'slug' || str_starts_with((string) ($p['name'] ?? ''), 'crop-mark')) {
            return true;
        }

        $fill = $p['fill'] ?? null;
        $stroke = $p['stroke'] ?? null;

        return $this->isFoil($fill) || $this->isFoil($stroke);
    }

    private function isFoil(?array $colour): bool
    {
        return $colour !== null && ($colour['type'] ?? null) === 'spot' && ($colour['name'] ?? null) === self::FOIL_SPOT;
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function draw(PdfDocument $doc, PdfPage $page, array $p, bool $plate): void
    {
        $kind = $p['kind'];
        $page->save();

        if (! empty($p['clip'])) {
            $c = $p['clip'];
            $page->rect($c['x'], $c['y'], $c['w'], $c['h']);
            $page->clip();
        }

        $overprint = (bool) ($p['overprint'] ?? false) && ! $plate;
        $page->useState($doc->overprintState($overprint));

        match ($kind) {
            'rect' => $this->drawRect($doc, $page, $p, $plate),
            'path' => $this->drawPath($doc, $page, $p, $plate),
            'gradient' => $this->drawGradient($doc, $page, $p),
            'text' => $this->drawText($doc, $page, $p, $plate),
            'qr' => $this->drawQr($doc, $page, $p, $plate),
            default => throw new \InvalidArgumentException("Unknown primitive [{$kind}]."),
        };

        $page->restore();
    }

    private function drawRect(PdfDocument $doc, PdfPage $page, array $p, bool $plate): void
    {
        $this->fill($doc, $page, $p['fill'], $plate);
        $page->rect($p['x'], $p['y'], $p['w'], $p['h']);
        $page->fill();
    }

    private function drawPath(PdfDocument $doc, PdfPage $page, array $p, bool $plate): void
    {
        $this->emitOps($page, $p['ops']);

        if ($p['fill'] !== null) {
            $this->fill($doc, $page, $p['fill'], $plate);
            $page->fill();
        }
        if ($p['stroke'] !== null) {
            $this->stroke($doc, $page, $p['stroke'], $plate);
            $page->lineWidth((float) $p['strokeWidth']);
            $page->lineCap((int) ($p['cap'] ?? 0));
            $page->lineJoin((int) ($p['join'] ?? 0));
            $page->stroke();
        }
    }

    private function drawGradient(PdfDocument $doc, PdfPage $page, array $p): void
    {
        $this->emitOps($page, $p['ops']);
        $page->clip();

        $stops = $p['gradient']['stops'];
        $shading = $doc->axialShading($p['gradient']['coords'], $stops);
        $page->shade($shading);
    }

    private function drawText(PdfDocument $doc, PdfPage $page, array $p, bool $plate): void
    {
        $this->fill($doc, $page, $p['fill'], $plate);
        $page->text(CardAssets::font($p['font']), $p['text'], (float) $p['size'], (float) $p['x'], (float) $p['baseline'], (float) ($p['tracking'] ?? 0.0));
    }

    private function drawQr(PdfDocument $doc, PdfPage $page, array $p, bool $plate): void
    {
        $this->fill($doc, $page, $p['fill'], $plate);

        $n = $p['modules'];
        $m = $p['size'] / $n;
        $bottom = $p['y'];
        foreach ($p['rows'] as $r => $row) {
            $x = 0;
            while ($x < $n) {
                if (! $row[$x]) {
                    $x++;

                    continue;
                }
                $start = $x;
                while ($x < $n && $row[$x]) {
                    $x++;
                }
                // Rows are top-down; PDF y grows upward.
                $page->rect($p['x'] + $start * $m, $bottom + $p['size'] - ($r + 1) * $m, ($x - $start) * $m, $m);
            }
        }
        $page->fill();
    }

    /** @param  list<array<int, float|string>>  $ops  M, L, C and Z commands in points */
    private function emitOps(PdfPage $page, array $ops): void
    {
        foreach ($ops as $cmd) {
            match ($cmd[0]) {
                'M' => $page->moveTo((float) $cmd[1], (float) $cmd[2]),
                'L' => $page->lineTo((float) $cmd[1], (float) $cmd[2]),
                'C' => $page->curveTo((float) $cmd[1], (float) $cmd[2], (float) $cmd[3], (float) $cmd[4], (float) $cmd[5], (float) $cmd[6]),
                'Z' => $page->closePath(),
                default => throw new \InvalidArgumentException("Unknown path command [{$cmd[0]}]."),
            };
        }
    }

    /** Set the non-stroking colour. The plate maps every foil colour to K100. */
    private function fill(PdfDocument $doc, PdfPage $page, ?array $colour, bool $plate): void
    {
        if ($colour === null) {
            return;
        }
        if ($plate && $this->isFoil($colour)) {
            $page->fillCmyk([0.0, 0.0, 0.0, 1.0]);

            return;
        }
        if (($colour['type'] ?? null) === 'spot') {
            $resource = $doc->separation($colour['name'], $colour['alt']);
            $page->fillSpot($resource, (float) $colour['tint']);

            return;
        }
        $page->fillCmyk($colour['c']);
    }

    private function stroke(PdfDocument $doc, PdfPage $page, array $colour, bool $plate): void
    {
        if ($plate && $this->isFoil($colour)) {
            $page->strokeCmyk([0.0, 0.0, 0.0, 1.0]);

            return;
        }
        if (($colour['type'] ?? null) === 'spot') {
            $resource = $doc->separation($colour['name'], $colour['alt']);
            $page->strokeSpot($resource, (float) $colour['tint']);

            return;
        }
        $page->strokeCmyk($colour['c']);
    }
}
