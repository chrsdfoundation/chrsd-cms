<?php

namespace App\Services;

class QrCodeService
{
    /**
     * Generate QR code URL using external API (no dependencies)
     * Uses QR Server API for free QR code generation
     */
    public function generateUrl(string $data, int $size = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    }

    /**
     * Generate QR code as data URI using external service
     */
    public function generateDataUri(string $data, int $size = 200): string
    {
        // Fetch QR code from external service and convert to data URI
        $url = $this->generateUrl($data, $size);
        $imageData = @file_get_contents($url);

        if ($imageData === false) {
            return ''; // Return empty if QR generation fails
        }

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
