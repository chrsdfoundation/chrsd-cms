<?php

namespace App\Services\Documents;

class HtmlSignatureService
{
    /**
     * Sign rendered HTML with HMAC-SHA256 keyed to APP_KEY.
     *
     * Anyone can compute SHA-256 of HTML; only holders of APP_KEY can
     * produce the matching HMAC. That means:
     *   - We store the signature alongside the record on generation.
     *   - Verification hashes the rendered HTML with the same key and
     *     compares constant-time to what we stored.
     *   - Third parties cannot forge a matching signature without APP_KEY.
     */
    public function sign(string $html): string
    {
        return hash_hmac('sha256', $html, config('app.key'));
    }

    /**
     * Constant-time comparison — do NOT swap for === or strcmp() (timing
     * side-channel would let attackers narrow down the correct signature
     * byte-by-byte).
     */
    public function verify(string $html, string $expectedSignature): bool
    {
        return hash_equals($expectedSignature, $this->sign($html));
    }

    /**
     * Human-readable fingerprint — the last 12 hex chars — safe to print on
     * the document footer so someone comparing two copies of the same file can
     * eyeball whether they're identical without running a hash tool.
     */
    public function humanFingerprint(string $signature): string
    {
        return strtoupper(substr($signature, -12));
    }
}
