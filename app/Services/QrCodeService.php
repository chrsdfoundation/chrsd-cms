<?php

namespace App\Services;

use App\Models\MoneyReceipt;
use Illuminate\Database\Eloquent\Model;
use Milon\Barcode\DNS2D;

class QrCodeService
{
    /** Standard module size for all QR codes (creates ~150-200px at default 96dpi) */
    private const DEFAULT_MODULE_SIZE = 5;

    /**
     * Get the verification URL for a model or string.
     * Supports both verification_hash (preferred) and serial_number fallback.
     * Routes to chrsd.org/verify in production, localhost:8001/verify in local.
     */
    public function verificationUrl(Model|string $data): string
    {
        // Public verification lives on the main website, not the apps subdomain.
        $base = rtrim(config('chrsd.verify_base_url') ?: config('app.website_url') ?: config('app.url'), '/');

        // Human-readable serial URL, e.g. https://chrsd.org/verify/ref/LTR-2026-000004
        if ($data instanceof Model
            && ! $data instanceof MoneyReceipt
            && ! empty($data->serial_number)) {
            return $base . '/verify/ref/' . $data->serial_number;
        }

        if ($data instanceof Model) {
            // Prefer verification_hash if available, fall back to serial_number
            $identifier = $data->verification_hash ?? $data->serial_number ?? $data->id;
        } else {
            $identifier = $data;
        }

        return $base . '/verify/' . $identifier;
    }

    /**
     * Generate QR code as SVG string (safe for PDF/HTML embedding).
     * Module size is standardized across all documents for consistent appearance.
     */
    public function svg(Model|string $data, int $moduleSize = self::DEFAULT_MODULE_SIZE): string
    {
        $url = is_string($data) ? $data : $this->verificationUrl($data);

        return (new DNS2D)->getBarcodeSVG(
            $url,
            'QRCODE',
            $moduleSize,
            $moduleSize,
        );
    }

    /**
     * Generate QR code as PNG base64 data URI.
     * Use for DomPDF or embedded image contexts where SVG isn't suitable.
     *
     * Falls back to an SVG data URI when PNG generation fails (typically the
     * GD extension is not loaded — Milon's DNS2D::getBarcodePNG() returns an
     * empty string in that case, which would render as a broken image icon
     * inside an <img src="…"> tag).
     */
    public function pngDataUri(Model|string $data, int $moduleSize = self::DEFAULT_MODULE_SIZE): string
    {
        $url = is_string($data) ? $data : $this->verificationUrl($data);

        $png = (new DNS2D)->getBarcodePNG(
            $url,
            'QRCODE',
            $moduleSize,
            $moduleSize,
        );

        if (! is_string($png) || $png === '') {
            return 'data:image/svg+xml;base64,' . base64_encode($this->svg($data, $moduleSize));
        }

        return 'data:image/png;base64,' . $png;
    }

    /**
     * Generate QR code as a public image URL (for legacy external API usage).
     * Returns a URL pointing to an external QR code service.
     * Size is in pixels (e.g., 256 = 256x256px image).
     *
     * @deprecated Use svg() or pngDataUri() instead for self-contained generation
     */
    public function generateUrl(string $data, int $size = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    }

    /**
     * Generate QR code as data URI from external service.
     * Fetches from external service and converts to base64.
     * Only use as fallback; prefer svg() or pngDataUri() for reliability.
     *
     * @deprecated Use pngDataUri() instead for self-contained generation
     */
    public function generateDataUri(string $data, int $size = 200): string
    {
        $url = $this->generateUrl($data, $size);
        $imageData = @file_get_contents($url);

        if ($imageData === false) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
