<?php

namespace App\Services\Auth;

/**
 * RFC 6238 TOTP implementation. Compatible with Google Authenticator, Authy,
 * 1Password, Bitwarden, and every other standard TOTP client.
 *
 *  - 6-digit codes
 *  - 30-second window
 *  - SHA1 (spec default)
 *  - Base32-encoded shared secret (RFC 4648, no padding)
 *
 * No external dependencies — we don't want a whole package for ~50 lines.
 */
class TotpService
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** 20 random bytes → 32-char base32 secret (recommended by RFC 4226). */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->encodeBase32(random_bytes($bytes));
    }

    /** 8 groups of 10 hex chars — printed on setup, one-time use each. */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn () => strtolower(bin2hex(random_bytes(5))),
            range(1, $count),
        );
    }

    /**
     * Constant-time verification with ±$window slippage (default: ±1 step,
     * so ±30s of clock drift is tolerated).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (strlen($code) !== self::DIGITS || ! ctype_digit($code)) {
            return false;
        }

        $counter = intdiv(time(), self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->at($secret, $counter + $offset), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Compute the code for a given counter — used by verify() and tests. */
    public function at(string $secret, int $counter): string
    {
        $key = $this->decodeBase32($secret);
        $bin = pack('N*', 0, $counter);          // 64-bit big-endian counter
        $hash = hash_hmac('sha1', $bin, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value  = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
             (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** otpauth:// URI — paste into a QR renderer, or scan directly. */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD,
        );
    }

    // ---- Base32 (RFC 4648) ---------------------------------------------

    protected function encodeBase32(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $b) {
            $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i + 5 <= strlen($bits); $i += 5) {
            $out .= self::BASE32[bindec(substr($bits, $i, 5))];
        }
        return $out;
    }

    protected function decodeBase32(string $s): string
    {
        $s = strtoupper(preg_replace('/[^A-Z2-7]/', '', $s));
        $bits = '';
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $pos = strpos(self::BASE32, $s[$i]);
            if ($pos === false) continue;
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0, $n = strlen($bits); $i + 8 <= $n; $i += 8) {
            $out .= chr(bindec(substr($bits, $i, 8)));
        }
        return $out;
    }
}
