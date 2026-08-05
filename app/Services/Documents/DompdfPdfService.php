<?php

namespace App\Services\Documents;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF service using dompdf (pure PHP, no browser required).
 * Perfect for shared hosting with CageFS restrictions.
 * Better CSS support than mPDF, better page sizing control.
 */
class DompdfPdfService
{
    /**
     * Render HTML to PDF bytes using dompdf.
     *
     * @param  array{
     *   format?: string,
     *   orientation?: 'portrait'|'landscape',
     *   margin?: array{top: string, right: string, bottom: string, left: string},
     * }  $opts
     */
    public function render(string $html, array $opts = []): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);

        // Set page size and orientation
        $format = $opts['format'] ?? 'A4';
        $orientation = ($opts['orientation'] ?? 'portrait') === 'landscape' ? 'L' : 'P';

        // Handle custom page sizes (CR80 ID cards, etc.)
        if (!empty($opts['pageSize'])) {
            [$w, $h] = $this->parseSizeMm($opts['pageSize']);
            $dompdf->setPaper([$w * 2.834645669, $h * 2.834645669], $orientation);
        } else {
            $dompdf->setPaper($format, $orientation);
        }

        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Convenience for callers that already know a Blade view name.
     */
    public function renderView(string $view, array $data = [], array $opts = []): string
    {
        return $this->render(view($view, $data)->render(), $opts);
    }

    /**
     * Parse an {width, height} array of CSS lengths into points (1/72 inch).
     * dompdf uses points internally, not millimetres.
     *
     * @param  array{width: string, height: string}  $size
     * @return array{0: float, 1: float}
     */
    private function parseSizeMm(array $size): array
    {
        return [$this->toMm($size['width']), $this->toMm($size['height'])];
    }

    /** Turn any CSS length ("15mm", "20px", "0.5in") into millimetres. */
    private function toMm(string $v): float
    {
        if (preg_match('/^([\d.]+)\s*(mm|in|px|cm|pt)?$/i', trim($v), $m)) {
            $n = (float) $m[1];

            return match (strtolower($m[2] ?? 'mm')) {
                'in' => $n * 25.4,
                'cm' => $n * 10,
                'px' => $n * 25.4 / 96.0,
                'pt' => $n * 25.4 / 72.0,
                default => $n,
            };
        }

        return 0.0;
    }
}
