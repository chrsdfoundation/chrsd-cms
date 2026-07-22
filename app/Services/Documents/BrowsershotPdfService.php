<?php

namespace App\Services\Documents;

use Spatie\Browsershot\Browsershot;

/**
 * Single entry point for HTML → PDF across the whole CMS.
 *
 * Every generator (Letter, ID Card, Certificate, Reports, template previews)
 * calls one of the methods below instead of instantiating Chromium directly.
 * That keeps the Chromium/Node paths and page-size heuristics in one place,
 * and — importantly — makes the app independent of the PHP GD extension that
 * DomPDF used to require.
 *
 * Uses spatie/browsershot on top of Puppeteer. On Windows the binary paths
 * live in .env (NODE_PATH, CHROMIUM_PATH); on Linux/Mac Browsershot's
 * defaults kick in.
 */
class BrowsershotPdfService
{
    /**
     * Render HTML to PDF bytes.
     *
     * @param  array{
     *   format?: string,
     *   pageSize?: array{width: string, height: string}|null,
     *   orientation?: 'portrait'|'landscape',
     *   margin?: array{top: string, right: string, bottom: string, left: string},
     *   headerHtml?: ?string,
     *   footerHtml?: ?string,
     *   waitUntil?: string,
     * }  $opts
     */
    public function render(string $html, array $opts = []): string
    {
        $shot = $this->base($html);

        // --- Page size / orientation ----------------------------------------
        if (! empty($opts['pageSize'])) {
            // Explicit width/height wins — used for CR80 (85.6×54mm) ID cards
            // and any bespoke stationery. We convert mm strings to px because
            // spatie/browsershot's ->paperSize() takes numeric units.
            [$w, $h, $u] = $this->parseSize($opts['pageSize']);
            $shot->paperSize($w, $h, $u);
        } else {
            $shot->format($opts['format'] ?? 'A4');
            if (($opts['orientation'] ?? 'portrait') === 'landscape') {
                $shot->landscape();
            }
        }

        // --- Margins --------------------------------------------------------
        $m = $opts['margin'] ?? ['top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm'];
        $shot->margins(
            $this->toMm($m['top']),
            $this->toMm($m['right']),
            $this->toMm($m['bottom']),
            $this->toMm($m['left']),
        );

        // --- Optional header / footer templates ------------------------------
        // Chromium renders these into the physical margin area on every page —
        // used by the Letter module to stitch the CHRSD letterhead on top /
        // bottom of every sheet without repeating in the flowed HTML.
        if (! empty($opts['headerHtml']) || ! empty($opts['footerHtml'])) {
            $shot->showBrowserHeaderAndFooter();
            $shot->headerHtml($opts['headerHtml'] ?? '<span></span>');
            $shot->footerHtml($opts['footerHtml'] ?? '<span></span>');
        }

        return $shot->pdf();
    }

    /**
     * Convenience for callers that already know a Blade view name.
     */
    public function renderView(string $view, array $data = [], array $opts = []): string
    {
        $html = view($view, $data)->render();
        return $this->render($html, $opts);
    }

    /**
     * Shared Browsershot builder — sets the Chromium path from .env, gives the
     * page time to load embedded assets (data URIs, web fonts), and enables
     * background printing so watermarks and letterhead PNGs actually appear.
     */
    private function base(string $html): Browsershot
    {
        $shot = Browsershot::html($html)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->noSandbox();

        $chrome = config('chrsd.puppeteer.chromium');
        if (is_string($chrome) && $chrome !== '') {
            $shot->setChromePath($chrome);
        }
        $node = config('chrsd.puppeteer.node');
        if (is_string($node) && $node !== '') {
            $shot->setNodeBinary($node);
        }

        return $shot;
    }

    /**
     * Accept sizes like "210mm" / "8.5in" / "85.6mm" and return a tuple that
     * ->paperSize() can consume.
     *
     * @param  array{width: string, height: string}  $size
     * @return array{0: float, 1: float, 2: string}  [width, height, unit]
     */
    private function parseSize(array $size): array
    {
        $extract = static function (string $v): array {
            if (preg_match('/^([\d.]+)\s*(mm|in|px)?$/i', trim($v), $m)) {
                return [(float) $m[1], strtolower($m[2] ?? 'mm')];
            }
            throw new \InvalidArgumentException("Unrecognised page dimension: {$v}");
        };
        [$w, $wu] = $extract($size['width']);
        [$h, $hu] = $extract($size['height']);
        if ($wu !== $hu) {
            throw new \InvalidArgumentException("Width and height must share a unit ({$wu} vs {$hu})");
        }
        return [$w, $h, $wu];
    }

    /** Turn any css length ("15mm", "20px", "0.5in") into millimetres. */
    private function toMm(string $v): float
    {
        if (preg_match('/^([\d.]+)\s*(mm|in|px)?$/i', trim($v), $m)) {
            $n = (float) $m[1];
            return match (strtolower($m[2] ?? 'mm')) {
                'in' => $n * 25.4,
                'px' => $n * 25.4 / 96.0,
                default => $n,
            };
        }
        return 0.0;
    }
}
