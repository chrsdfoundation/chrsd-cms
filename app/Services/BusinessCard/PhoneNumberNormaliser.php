<?php

namespace App\Services\BusinessCard;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Normalises a phone number to E.164 with libphonenumber, and validates it.
 *
 * The printed form is kept separately (the client's display style, for
 * example "+880 1602-778984") so the card shows exactly what was agreed, and
 * the vCard always carries the E.164 value.
 */
final class PhoneNumberNormaliser
{
    public const DEFAULT_REGION = 'BD';

    /**
     * @return array{e164: string, display: string}
     *
     * @throws InvalidArgumentException when the number is not a valid number
     */
    public function normalise(string $raw, string $region = self::DEFAULT_REGION): array
    {
        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse(trim($raw), $region);
        } catch (NumberParseException $e) {
            throw new InvalidArgumentException("Phone number [{$raw}] cannot be parsed.", 0, $e);
        }

        if (! $util->isValidNumber($parsed)) {
            throw new InvalidArgumentException("Phone number [{$raw}] is not a valid number for {$region}.");
        }

        return [
            'e164' => $util->format($parsed, PhoneNumberFormat::E164),
            'display' => $util->format($parsed, PhoneNumberFormat::INTERNATIONAL),
        ];
    }

    /** Digits only, with a leading plus preserved, for wa.me links. */
    public function digitsOnly(string $e164): string
    {
        return ltrim(preg_replace('/\D+/', '', $e164) ?? '', '+');
    }
}
