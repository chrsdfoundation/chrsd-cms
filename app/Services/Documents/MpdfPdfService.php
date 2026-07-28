<?php

namespace App\Services\Documents;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Single entry point for HTML → PDF across the whole CMS (mPDF backend).
 *
 * Drop-in replacement for the former BrowsershotPdfService: it exposes the
 * same render()/renderView() signature and the same $opts shape, so the
 * generators (Letter, ID Card, Certificate, Reports) do not need to change
 * how they call it.
 *
 * mPDF is pure PHP (needs only the GD + mbstring extensions), so — unlike the
 * Chromium/Puppeteer pipeline — it runs on the cPanel production host with no
 * Node or headless browser. The trade-off is strict CSS 2.1 compliance: no
 * flexbox, no grid, no calc(), limited absolute positioning. Use <table> for
 * columns, float for side-by-side, and block layout for content flow.
 *
 * CRITICAL: All image assets MUST be passed as:
 *   • base64 data URIs (data:image/png;base64,…), or
 *   • absolute filesystem paths (public_path('…'))
 * Do NOT pass relative URLs or URLs that require HTTP resolution — mPDF cannot
 * fetch them. The generator services (CertificateGeneratorService,
 * IdCardGeneratorService, LetterGeneratorService) convert all images to data
 * URIs before rendering.
 */
class MpdfPdfService
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
        $orientation = ($opts['orientation'] ?? 'portrait') === 'landscape' ? 'L' : 'P';

        $m = $opts['margin'] ?? ['top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm'];
        $marginTop = $this->toMm($m['top']);
        $marginBottom = $this->toMm($m['bottom']);

        $config = [
            'mode' => 'utf-8',
            'orientation' => $orientation,
            'margin_left' => $this->toMm($m['left']),
            'margin_right' => $this->toMm($m['right']),
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'tempDir' => $this->tempDir(),
        ];

        // --- Page size / format --------------------------------------------
        if (! empty($opts['pageSize'])) {
            // Explicit width/height (CR80 ID cards, bespoke stationery). mPDF's
            // "format" takes [width, height] in millimetres. Orientation is auto-
            // detected: landscape if width > height, portrait otherwise.
            [$w, $h] = $this->parseSizeMm($opts['pageSize']);
            $config['format'] = [$w, $h];
            // Only override orientation if not already set (don't override explicit opts['orientation'])
            if (empty($opts['orientation']) && $w > $h) {
                $config['orientation'] = 'L';
            }
        } else {
            $config['format'] = $opts['format'] ?? 'A4';
        }

        // --- CSS 2.1 only; no flexbox, grid, or advanced features.
        // mPDF has very limited CSS support — most modern layouts won't work.
        // Use inline styles, <table> elements, float, and block layout only.
        $config['disable_html_object_protocol'] = true;

        // --- Optional running header / footer ------------------------------
        // Used by the Letter module to repeat the CHRSD letterhead strips in
        // the physical top/bottom margins of every page. mPDF places header /
        // footer HTML inside the page margin, offset by margin_header /
        // margin_footer, so we pin those to 0 for an edge-to-edge strip.
        $hasHeader = ! empty($opts['headerHtml']);
        $hasFooter = ! empty($opts['footerHtml']);
        if ($hasHeader) {
            $config['margin_header'] = 0;
        }
        if ($hasFooter) {
            $config['margin_footer'] = 0;
        }

        $mpdf = new Mpdf($config);

        if ($hasHeader) {
            $mpdf->SetHTMLHeader($opts['headerHtml']);
        }
        if ($hasFooter) {
            $mpdf->SetHTMLFooter($opts['footerHtml']);
        }

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Convenience for callers that already know a Blade view name.
     */
    public function renderView(string $view, array $data = [], array $opts = []): string
    {
        return $this->render(view($view, $data)->render(), $opts);
    }

    /** Writable scratch directory mPDF needs for font/image caching. */
    private function tempDir(): string
    {
        $dir = storage_path('app/mpdf');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Parse an {width, height} array of CSS lengths into millimetres.
     *
     * @param  array{width: string, height: string}  $size
     * @return array{0: float, 1: float}
     */
    private function parseSizeMm(array $size): array
    {
        return [$this->toMm($size['width']), $this->toMm($size['height'])];
    }

    /** Turn any css length ("15mm", "20px", "0.5in") into millimetres. */
    private function toMm(string $v): float
    {
        if (preg_match('/^([\d.]+)\s*(mm|in|px|cm)?$/i', trim($v), $m)) {
            $n = (float) $m[1];

            return match (strtolower($m[2] ?? 'mm')) {
                'in' => $n * 25.4,
                'cm' => $n * 10,
                'px' => $n * 25.4 / 96.0,
                default => $n,
            };
        }

        return 0.0;
    }
}
