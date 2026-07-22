<?php

namespace App\Services\Verification;

use Illuminate\Database\Eloquent\Model;
use Milon\Barcode\DNS2D;

class QrCodeService
{
    /** Public verification URL that a scanner will resolve to. */
    public function verificationUrl(Model $model): string
    {
        // Prefer the external verify portal (config('chrsd.verify_base_url')
        // ← VERIFY_BASE_URL). Fall back to APP_URL so the CMS keeps working
        // standalone when no external portal is configured.
        $base = rtrim(config('chrsd.verify_base_url') ?: config('app.url'), '/');

        return $base . '/verify/' . $model->verification_hash;
    }

    /** SVG QR string, safe to embed in PDF/HTML templates. */
    public function svg(Model $model, int $size = 4): string
    {
        return (new DNS2D)->getBarcodeSVG(
            $this->verificationUrl($model),
            'QRCODE',
            $size,
            $size,
        );
    }

    /** PNG base64 (data URI), for DomPDF where SVG is limited. */
    public function pngDataUri(Model $model, int $size = 4): string
    {
        $png = (new DNS2D)->getBarcodePNG(
            $this->verificationUrl($model),
            'QRCODE',
            $size,
            $size,
        );

        return 'data:image/png;base64,' . $png;
    }
}
