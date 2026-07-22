<?php

namespace App\Services\Documents;

class PdfSignatureService
{
    /**
     * Sign raw PDF bytes with HMAC-SHA256 keyed to APP_KEY.
     *
     * Anyone can compute SHA-256 of a PDF; only holders of APP_KEY can
     * produce the matching HMAC. That means:
     *   - We store the signature alongside the record on generation.
     *   - Verification hashes the uploaded PDF with the same key and
     *     compares constant-time to what we stored.
     *   - Third parties cannot forge a matching signature without APP_KEY.
     */
    public function sign(string $pdfBytes): string
    {
        return hash_hmac('sha256', $pdfBytes, config('app.key'));
    }

    /**
     * Constant-time comparison — do NOT swap for === or strcmp() (timing
     * side-channel would let attackers narrow down the correct signature
     * byte-by-byte).
     */
    public function verify(string $pdfBytes, string $expectedSignature): bool
    {
        return hash_equals($expectedSignature, $this->sign($pdfBytes));
    }

    /**
     * Human-readable fingerprint — the last 12 hex chars — safe to print on
     * the PDF footer so someone comparing two copies of the same file can
     * eyeball whether they're identical without running a hash tool.
     */
    public function humanFingerprint(string $signature): string
    {
        return strtoupper(substr($signature, -12));
    }
}
