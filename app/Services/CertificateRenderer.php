<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;

class CertificateRenderer
{
    /**
     * Render certificate to HTML string.
     */
    public function html(Certificate $certificate): string
    {
        return view('certificates.template', $this->data($certificate))->render();
    }

    /**
     * Build template data, including brand assets as base64 data URIs.
     */
    protected function data(Certificate $certificate): array
    {
        $certificate->loadMissing(['employee', 'type', 'signedBy.position']);

        // Responsive font sizing based on recipient name length.
        $nameLength = strlen($certificate->recipient_name ?? '');
        $fontSize = match (true) {
            $nameLength > 48 => 34,   // 60 chars
            $nameLength > 36 => 40,   // 48 chars
            $nameLength > 26 => 48,   // 36 chars
            default => 58,            // ≤26 chars
        };

        return [
            'certificate' => $certificate,
            'recipientName' => $certificate->employee?->full_name ?? $certificate->recipient_name,
            'programName' => $certificate->program_name ?? 'Program Name',
            'certificateTitle' => $certificate->certificate_title ?? 'Certificate of Achievement',
            'awardLeadIn' => $certificate->award_lead_in ?? 'has successfully completed',
            'issuedOn' => optional($certificate->issued_on)->format('F j, Y'),
            'certificateNo' => $certificate->certificate_no,
            'verifyUrl' => $certificate->verify_url,
            'nameFontSize' => $fontSize,
            'qrCodeSvg' => view('components.certificates.qr-code', [
                'url' => $certificate->verify_url,
            ])->render(),
            'signatory1Name' => $certificate->signedBy?->full_name ?? 'Razib Mustafiz',
            'signatory1Title' => $certificate->signedBy?->position?->title ?? 'Project Coordinator',
            'signatory1Image' => $this->assetOrPlaceholder('signatures/razib-mustafiz.png'),
            'signatory2Name' => 'M.A. Ramim',
            'signatory2Title' => 'Executive Director',
            'signatory2Image' => $this->assetOrPlaceholder('signatures/ma-ramim.png'),
            'logoImage' => $this->assetOrPlaceholder('chrsd-full-logo.png'),
            'sealImage' => $this->assetOrPlaceholder('chrsd-rosette-seal.png'),
            'watermarkImage' => $this->assetOrPlaceholder('chrsd-watermark.svg'),
        ];
    }

    /**
     * Load a brand asset as a base64 data URI with fallback placeholder.
     */
    protected function assetOrPlaceholder(string $path): string
    {
        $fullPath = "certificates/brand/{$path}";

        try {
            if (! Storage::exists($fullPath)) {
                return $this->placeholder($path);
            }

            $content = Storage::get($fullPath);
            $mime = str_ends_with($path, '.svg') ? 'image/svg+xml' : 'image/png';

            return "data:{$mime};base64," . base64_encode($content);
        } catch (\Exception $e) {
            return $this->placeholder($path);
        }
    }

    /**
     * Generate a placeholder image SVG when asset is missing.
     */
    protected function placeholder(string $path): string
    {
        $svg = match (true) {
            str_ends_with($path, 'logo.png') => '<svg viewBox="0 0 200 200"><rect fill="#ddd" width="200" height="200"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="14" fill="#999">Logo</text></svg>',
            str_ends_with($path, 'seal.png') => '<svg viewBox="0 0 200 200"><circle cx="100" cy="100" r="95" fill="none" stroke="#ddd" stroke-width="2"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="14" fill="#999">Seal</text></svg>',
            str_ends_with($path, 'watermark.svg') => '<svg viewBox="0 0 300 300"><circle cx="150" cy="150" r="140" fill="none" stroke="#ddd" stroke-width="2"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="20" fill="#ddd">Watermark</text></svg>',
            str_ends_with($path, '.png') => '<svg viewBox="0 0 200 100"><rect fill="#f5f5f5" width="200" height="100"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="12" fill="#999">Signature</text></svg>',
            default => '<svg viewBox="0 0 100 100"><rect fill="#eee" width="100" height="100"/></svg>',
        };

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
