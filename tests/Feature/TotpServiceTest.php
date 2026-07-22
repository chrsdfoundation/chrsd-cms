<?php

namespace Tests\Feature;

use App\Services\Auth\TotpService;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    /**
     * Reference vectors from RFC 6238 Appendix B. The test key is the ASCII
     * string "12345678901234567890" (20 bytes), which in Base32 is
     * "GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ".
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private const RFC_VECTORS = [
        //   time    → sha1 6-digit
        59 => '287082',
        1111111109 => '081804',
        1111111111 => '050471',
        1234567890 => '005924',
        2000000000 => '279037',
    ];

    public function test_generated_secret_is_base32_and_looks_random(): void
    {
        $svc = new TotpService;
        $a = $svc->generateSecret();
        $b = $svc->generateSecret();

        $this->assertSame(32, strlen($a));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $a);
        $this->assertNotSame($a, $b, 'Two calls must not return the same secret');
    }

    public function test_at_produces_the_rfc_6238_reference_vectors(): void
    {
        $svc = new TotpService;

        foreach (self::RFC_VECTORS as $time => $expected) {
            $counter = intdiv($time, TotpService::PERIOD);
            $this->assertSame(
                $expected,
                $svc->at(self::RFC_SECRET, $counter),
                "Code mismatch at time $time",
            );
        }
    }

    public function test_verify_accepts_current_code(): void
    {
        $svc = new TotpService;
        $counter = intdiv(time(), TotpService::PERIOD);
        $current = $svc->at(self::RFC_SECRET, $counter);

        $this->assertTrue($svc->verify(self::RFC_SECRET, $current));
    }

    public function test_verify_accepts_previous_and_next_window(): void
    {
        $svc = new TotpService;
        $counter = intdiv(time(), TotpService::PERIOD);
        $prev = $svc->at(self::RFC_SECRET, $counter - 1);
        $next = $svc->at(self::RFC_SECRET, $counter + 1);

        $this->assertTrue($svc->verify(self::RFC_SECRET, $prev), 'Previous 30s window must be accepted');
        $this->assertTrue($svc->verify(self::RFC_SECRET, $next), 'Next 30s window must be accepted');
    }

    public function test_verify_rejects_two_windows_out(): void
    {
        $svc = new TotpService;
        $counter = intdiv(time(), TotpService::PERIOD);
        $stale = $svc->at(self::RFC_SECRET, $counter - 5); // 2.5 minutes old

        $this->assertFalse($svc->verify(self::RFC_SECRET, $stale));
    }

    public function test_verify_rejects_wrong_length_or_non_numeric_input(): void
    {
        $svc = new TotpService;
        $this->assertFalse($svc->verify(self::RFC_SECRET, '12345'));
        $this->assertFalse($svc->verify(self::RFC_SECRET, '1234567'));
        $this->assertFalse($svc->verify(self::RFC_SECRET, 'abcdef'));
        $this->assertFalse($svc->verify(self::RFC_SECRET, ''));
    }

    public function test_provisioning_uri_conforms_to_otpauth_scheme(): void
    {
        $uri = (new TotpService)->provisioningUri(
            secret: 'JBSWY3DPEHPK3PXP',
            account: 'user@example.com',
            issuer: 'CHRSD CMS',
        );

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=CHRSD%20CMS', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    public function test_recovery_codes_are_unique_and_correct_length(): void
    {
        $codes = (new TotpService)->generateRecoveryCodes();

        $this->assertCount(8, $codes);
        $this->assertSame(8, count(array_unique($codes)), 'Recovery codes must not collide');
        foreach ($codes as $c) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{10}$/', $c);
        }
    }
}
