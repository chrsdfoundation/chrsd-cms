<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Substitutes visa-support-letter [BRACKETED] placeholders in a letter body
 * with values from the Filament create/edit form's Quick-Fill section.
 *
 * Keeps the concern out of the Resource class so both Create and Edit pages
 * can share the exact same substitution table.
 */
final class VisaLetterSubstitutor
{
    /**
     * Apply substitutions and return the modified body. Any Quick-Fill field
     * that is empty leaves the corresponding placeholder untouched, so partial
     * fills are safe.
     *
     * @param  array<string,mixed>  $data  the form payload (may contain
     *                                     visa_* keys that were dehydrated)
     */
    public static function apply(string $body, array $data): string
    {
        $fullName = trim((string) ($data['visa_employee_full_name'] ?? ''));
        $firstName = $fullName !== '' ? preg_split('/\s+/', $fullName)[0] : '';

        $pronoun = strtolower((string) ($data['visa_pronoun'] ?? ''));
        [$objective, $possessive, $subject] = match ($pronoun) {
            'she'  => ['her',   'her',    'She'],
            'they' => ['them',  'their',  'They'],
            'he'   => ['him',   'his',    'He'],
            default => ['', '', ''],
        };

        $dateFmt = static fn (?string $iso): string =>
            $iso ? Carbon::parse($iso)->format('j F Y') : '';

        // Map: bracket-placeholder → replacement value. Any empty value causes
        // the placeholder to be skipped, preserving partial fills.
        $subs = [
            "[Employee's Full Name]"     => $fullName,
            "[Employee's First Name]"    => $firstName,
            "[Passport No.]"             => trim((string) ($data['visa_passport_no'] ?? '')),
            "[Job Title]"                => trim((string) ($data['visa_job_title'] ?? '')),
            "[Start Date at CHRSD]"      => $dateFmt($data['visa_start_date_at_chrsd'] ?? null),
            "[Business / Short-Stay]"    => trim((string) ($data['visa_type'] ?? '')),
            "[Seminar / Event Name]"     => trim((string) ($data['visa_event_name'] ?? '')),
            "[City, Country]"            => trim((string) ($data['visa_event_location'] ?? '')),
            "[Event Start Date]"         => $dateFmt($data['visa_event_start'] ?? null),
            "[Event End Date]"           => $dateFmt($data['visa_event_end'] ?? null),
            "[Destination Country]"      => trim((string) ($data['visa_destination_country'] ?? '')),
            "[Return Date]"              => $dateFmt($data['visa_return_date'] ?? null),
            "[him/her/them]"             => $objective,
            "[his/her/their]"            => $possessive,
            "[He/She/They]"              => $subject,
            "[briefly state the benefit — e.g. strengthen CHRSD's capacity in environmental monitoring, build institutional partnerships, and represent our programmes at an international forum]"
                                          => trim((string) ($data['visa_purpose_statement'] ?? '')),
            "[list enclosures — e.g. official invitation letter, employment certificate, bank statement, travel itinerary]"
                                          => trim((string) ($data['visa_enclosures'] ?? '')),
        ];

        foreach ($subs as $needle => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $body = str_replace($needle, $value, $body);
        }

        return $body;
    }
}
