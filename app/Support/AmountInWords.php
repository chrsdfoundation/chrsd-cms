<?php

namespace App\Support;

/**
 * Number-to-words in the Bangladeshi (Indic) numbering system —
 * crore / lakh / thousand rather than million / billion. Ported from the
 * receipt template's JS so printable receipts and PDF renders match the
 * reference exactly.
 */
final class AmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight',
        'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
        'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy',
        'Eighty', 'Ninety',
    ];

    /**
     * @param float|int|string $value  numeric amount, may include decimals
     * @param string $unit             major currency unit label ("Taka")
     * @param string $subUnit          minor currency unit label ("Poisha")
     */
    public static function convert(float|int|string $value, string $unit = 'Taka', string $subUnit = 'Poisha'): string
    {
        $value = (float) $value;
        $major = (int) floor(abs($value));
        $minor = (int) round((abs($value) - $major) * 100);

        $words = self::inWords($major) . ' ' . $unit;
        if ($minor > 0) {
            $words .= ' and ' . self::inWords($minor) . ' ' . $subUnit;
        }

        return $words . ' Only';
    }

    private static function inWords(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }

        $parts = [];

        $crore = intdiv($n, 10_000_000);
        $n %= 10_000_000;
        $lakh = intdiv($n, 100_000);
        $n %= 100_000;
        $thou = intdiv($n, 1_000);
        $n %= 1_000;

        if ($crore) {
            $parts[] = self::three($crore) . ' Crore';
        }
        if ($lakh) {
            $parts[] = self::two($lakh) . ' Lakh';
        }
        if ($thou) {
            $parts[] = self::three($thou) . ' Thousand';
        }
        if ($n) {
            $parts[] = self::three($n);
        }

        return implode(' ', $parts);
    }

    private static function two(int $x): string
    {
        if ($x < 20) {
            return self::ONES[$x];
        }
        $tens = self::TENS[intdiv($x, 10)];
        $ones = $x % 10;

        return $ones ? $tens . ' ' . self::ONES[$ones] : $tens;
    }

    private static function three(int $x): string
    {
        if ($x < 100) {
            return self::two($x);
        }
        $hundreds = self::ONES[intdiv($x, 100)] . ' Hundred';
        $rest = $x % 100;

        return $rest ? $hundreds . ' ' . self::two($rest) : $hundreds;
    }
}
