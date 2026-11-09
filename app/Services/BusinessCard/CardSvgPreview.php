<?php

namespace App\Services\BusinessCard;

/**
 * Draws one side of a CardLayout as SVG, for the on-screen preview.
 *
 * It reads the same primitive list as the PDF renderer, so the preview shows what
 * the artwork will print. Points are converted to a 306 x 198 viewBox with y flipped
 * (SVG y grows downward). Fonts are self-hosted from public/fonts/business-card.
 */
final class CardSvgPreview
{
    private int $defsId = 0;

    /**
     * @param  list<array<string, mixed>>  $primitives
     * @param  'flat'|'foil'  $view  flat: artwork colours; foil: the plate (foil shapes only, K100)
     * @param  array{bleed: bool, trim: bool, safe: bool}  $guides
     */
    public function render(array $primitives, string $view, array $guides): string
    {
        $defs = '';
        $body = '';

        foreach ($primitives as $p) {
            if ($view === 'foil' && ! $this->onPlate($p)) {
                continue;
            }
            $body .= $this->primitive($p, $view === 'foil', $defs);
        }

        $body .= $this->guidesSvg($guides);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 306 198" width="100%" role="img">'
            . ($defs !== '' ? '<defs>' . $defs . '</defs>' : '')
            . '<rect x="0" y="0" width="306" height="198" fill="#ffffff"/>'
            . $body
            . '</svg>';
    }

    /** @param  array<string, mixed>  $p */
    private function primitive(array $p, bool $plate, string &$defs): string
    {
        $clip = '';
        if (! empty($p['clip'])) {
            $id = 'clip' . (++$this->defsId);
            $c = $p['clip'];
            $defs .= sprintf('<clipPath id="%s"><rect x="%s" y="%s" width="%s" height="%s"/></clipPath>', $id, $this->n($c['x']), $this->n(198 - $c['y'] - $c['h']), $this->n($c['w']), $this->n($c['h']));
            $clip = ' clip-path="url(#' . $id . ')"';
        }

        return match ($p['kind']) {
            'rect' => sprintf('<rect x="%s" y="%s" width="%s" height="%s" fill="%s"%s/>',
                $this->n($p['x']), $this->n(198 - $p['y'] - $p['h']), $this->n($p['w']), $this->n($p['h']),
                $this->colour($p['fill'], $plate), $clip),
            'path' => '<g' . $clip . '>' . $this->pathElement($p, $plate) . '</g>',
            'gradient' => $this->gradientElement($p, $plate, $defs, $clip),
            'text' => $this->textElement($p, $plate, $clip),
            'qr' => $this->qrElement($p, $plate, $clip),
            default => '',
        };
    }

    /** @param  array<string, mixed>  $p */
    private function pathElement(array $p, bool $plate): string
    {
        $d = $this->pathData($p['ops']);
        $fill = $p['fill'] !== null ? $this->colour($p['fill'], $plate) : 'none';
        $stroke = '';
        if ($p['stroke'] !== null) {
            $stroke = sprintf(' stroke="%s" stroke-width="%s" stroke-linecap="%s" stroke-linejoin="round"',
                $this->colour($p['stroke'], $plate), $this->n(max((float) $p['strokeWidth'], 0.35)), ($p['cap'] ?? 0) === 1 ? 'round' : 'butt');
        }

        return sprintf('<path d="%s" fill="%s"%s/>', $d, $fill, $stroke);
    }

    /** @param  array<string, mixed>  $p */
    private function gradientElement(array $p, bool $plate, string &$defs, string $clip): string
    {
        if ($plate) {
            return '';
        }
        $id = 'g' . (++$this->defsId);
        [$x1, $y1, $x2, $y2] = $p['gradient']['coords'];
        $stops = '';
        foreach ($p['gradient']['stops'] as [$t, $c, $m, $y, $k]) {
            $stops .= sprintf('<stop offset="%s" stop-color="%s"/>', $this->n($t), $this->cmykToRgb([$c, $m, $y, $k]));
        }
        $defs .= sprintf('<linearGradient id="%s" gradientUnits="userSpaceOnUse" x1="%s" y1="%s" x2="%s" y2="%s">%s</linearGradient>',
            $id, $this->n($x1), $this->n(198 - $y1), $this->n($x2), $this->n(198 - $y2), $stops);

        return sprintf('<path d="%s" fill="url(#%s)"%s/>', $this->pathData($p['ops']), $id, $clip === '' ? '' : '');
    }

    /** @param  array<string, mixed>  $p */
    private function textElement(array $p, bool $plate, string $clip): string
    {
        $family = match ($p['font']) {
            'serif_org' => "'CardSerif', Georgia, serif",
            default => "'CardSans', Helvetica, Arial, sans-serif",
        };
        $size = (float) $p['size'];
        $letterSpacing = (float) ($p['tracking'] ?? 0.0) * $size;

        return sprintf('<text x="%s" y="%s" font-family="%s" font-size="%s" letter-spacing="%s" fill="%s"%s>%s</text>',
            $this->n($p['x']), $this->n(198 - $p['baseline']), $family, $this->n($size),
            $this->n($letterSpacing), $this->colour($p['fill'], $plate), $clip, htmlspecialchars((string) $p['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
    }

    /** @param  array<string, mixed>  $p */
    private function qrElement(array $p, bool $plate, string $clip): string
    {
        $n = $p['modules'];
        $m = $p['size'] / $n;
        $fill = $this->colour($p['fill'], $plate);
        $d = '';
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
                $top = 198 - ($p['y'] + $p['size'] - $r * $m);
                $d .= sprintf('M%s %sh%sv%sh-%sz', $this->n($p['x'] + $start * $m), $this->n($top), $this->n(($x - $start) * $m), $this->n($m), $this->n(($x - $start) * $m));
            }
        }

        return sprintf('<path d="%s" fill="%s"%s/>', $d, $fill, $clip);
    }

    private function guidesSvg(array $guides): string
    {
        $out = '';
        if ($guides['bleed']) {
            $out .= '<rect x="18" y="18" width="270" height="162" fill="none" stroke="#e11d48" stroke-width="0.4" stroke-dasharray="2 2"/>';
        }
        if ($guides['trim']) {
            $out .= '<rect x="27" y="27" width="252" height="144" fill="none" stroke="#0ea5e9" stroke-width="0.4"/>';
        }
        if ($guides['safe']) {
            // Safe area: 45 design px = 10.8 pt inside the trim.
            $out .= '<rect x="37.8" y="37.8" width="228.4" height="120.4" fill="none" stroke="#16a34a" stroke-width="0.4" stroke-dasharray="1 1"/>';
        }

        return $out;
    }

    /** @param  list<array<int, float|string>>  $ops */
    private function pathData(array $ops): string
    {
        $d = '';
        foreach ($ops as $cmd) {
            $d .= match ($cmd[0]) {
                'M' => sprintf('M%s %s', $this->n($cmd[1]), $this->n(198 - $cmd[2])),
                'L' => sprintf('L%s %s', $this->n($cmd[1]), $this->n(198 - $cmd[2])),
                'C' => sprintf('C%s %s %s %s %s %s', $this->n($cmd[1]), $this->n(198 - $cmd[2]), $this->n($cmd[3]), $this->n(198 - $cmd[4]), $this->n($cmd[5]), $this->n(198 - $cmd[6])),
                'Z' => 'Z',
                default => '',
            };
        }

        return $d;
    }

    /** @param  array<string, mixed>  $p */
    private function onPlate(array $p): bool
    {
        $name = (string) ($p['name'] ?? '');
        if ($name === 'slug' || str_starts_with($name, 'crop-mark')) {
            return true;
        }
        foreach (['fill', 'stroke'] as $key) {
            $c = $p[$key] ?? null;
            if (is_array($c) && ($c['type'] ?? null) === 'spot' && ($c['name'] ?? null) === 'Silver_Foil') {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, mixed>|null  $colour */
    private function colour(?array $colour, bool $plate): string
    {
        if ($colour === null) {
            return 'none';
        }
        if (($colour['type'] ?? null) === 'spot') {
            if ($plate) {
                return '#000000';
            }

            // Screen stand-in for the silver foil: the spec's alternate CMYK, as a metallic grey.
            return $colour['name'] === 'All' ? '#000000' : '#c3cad3';
        }

        return $plate ? '#000000' : $this->cmykToRgb($colour['c']);
    }

    /** @param  list<float>  $cmyk  fractions 0-1 */
    private function cmykToRgb(array $cmyk): string
    {
        [$c, $m, $y, $k] = $cmyk;
        $r = (int) round(255 * (1 - $c) * (1 - $k));
        $g = (int) round(255 * (1 - $m) * (1 - $k));
        $b = (int) round(255 * (1 - $y) * (1 - $k));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function n(float|int|string $v): string
    {
        return rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.') ?: '0';
    }
}
