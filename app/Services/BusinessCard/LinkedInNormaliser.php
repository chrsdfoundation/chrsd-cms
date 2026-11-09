<?php

namespace App\Services\BusinessCard;

use InvalidArgumentException;

/**
 * Reduces any LinkedIn input to exactly https://www.linkedin.com/in/<handle>.
 *
 * Accepted: a bare handle, "@handle", "in/handle", "/in/handle", "linkedin.com/in/handle",
 * "www.linkedin.com/in/handle", a full https URL, and the doubled form
 * "linkedin.com/in/www.linkedin.com/in/handle" that shipped on the old card.
 */
final class LinkedInNormaliser
{
    private const PROFILE_PREFIX = 'https://www.linkedin.com/in/';

    public function normalise(string $input): string
    {
        $value = trim($input);
        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = preg_replace('#^(www\.)?linkedin\.com/#i', '', $value) ?? $value;
        $value = trim($value, " \t/");

        // Repair the doubled profile path: in/www.linkedin.com/in/handle -> handle.
        $value = preg_replace('#^(in/)?(www\.)?linkedin\.com/in/#i', '', $value) ?? $value;
        $value = preg_replace('#^in/#i', '', $value) ?? $value;
        $value = ltrim($value, '@');

        // Drop any query or fragment, and a trailing slash.
        $value = preg_replace('#[?\#].*$#', '', $value) ?? $value;
        $value = rtrim($value, '/');

        if (! preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{1,98}[A-Za-z0-9])$/', $value)) {
            throw new InvalidArgumentException("LinkedIn value [{$input}] is not a valid profile handle.");
        }

        return self::PROFILE_PREFIX . $value;
    }

    /** The handle of a normalised profile URL, or null if the URL is not one. */
    public function handleOf(string $url): ?string
    {
        if (! str_starts_with($url, self::PROFILE_PREFIX)) {
            return null;
        }

        return substr($url, strlen(self::PROFILE_PREFIX));
    }
}
