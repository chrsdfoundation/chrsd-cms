<?php

namespace App\Services\BusinessCard;

use App\Models\BusinessCard;
use InvalidArgumentException;

/**
 * Builds the normalised CardContact from a card's stored or form data.
 *
 * Phones are normalised to E.164 and validated for Bangladesh. LinkedIn is reduced to
 * one profile URL. Blank values are dropped. Invalid input throws, so nothing is
 * silently repaired on the way to print.
 */
final class BusinessCardContactFactory
{
    /** The organisation name as printed on the card's front and in the full vCard. */
    public const ORG_NAME = 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)';

    public function __construct(
        private readonly PhoneNumberNormaliser $phones,
        private readonly LinkedInNormaliser $linkedIn,
    ) {}

    /**
     * @param  array<string, mixed>  $data  person_name, person_title, org_name, phones, emails,
     *                                      website, linkedin_handle, address_lines (the stored shape)
     *
     * @throws InvalidArgumentException
     */
    public function fromArray(array $data): CardContact
    {
        $phones = [];
        foreach ($data['phones'] ?? [] as $phone) {
            $raw = trim((string) ($phone['display'] ?? $phone['number'] ?? ''));
            if ($raw === '') {
                continue;
            }
            $normalised = $this->phones->normalise($raw);
            $phones[] = [
                'type' => (string) $phone['type'],
                'e164' => $normalised['e164'],
                'display' => $normalised['display'],
            ];
        }

        $emails = [];
        foreach ($data['emails'] ?? [] as $email) {
            $address = trim((string) ($email['address'] ?? ''));
            if ($address === '') {
                continue;
            }
            if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Email [{$address}] is not valid.");
            }
            $emails[] = ['type' => (string) $email['type'], 'address' => $address];
        }

        $website = trim((string) ($data['website'] ?? ''));
        $handle = trim((string) ($data['linkedin_handle'] ?? ''));

        return new CardContact(
            personName: trim((string) ($data['person_name'] ?? '')),
            personTitle: trim((string) ($data['person_title'] ?? '')),
            orgName: (string) ($data['org_name'] ?? self::ORG_NAME),
            orgShort: (string) ($data['org_short'] ?? 'CHRSD'),
            phones: $phones,
            emails: $emails,
            website: $website !== '' ? $website : null,
            linkedinUrl: $handle !== '' ? $this->linkedIn->normalise($handle) : null,
            addressLines: array_values(array_filter(array_map('trim', (array) ($data['address_lines'] ?? [])), fn ($l) => $l !== '')),
        );
    }

    public function fromCard(BusinessCard $card): CardContact
    {
        return $this->fromArray([
            'person_name' => $card->person_name ?: $card->full_name,
            'person_title' => $card->person_title ?: $card->job_title,
            'phones' => $card->phones ?? [],
            'emails' => $card->emails ?? [],
            'website' => $card->website,
            'linkedin_handle' => $card->linkedin_handle,
            'address_lines' => $card->address_lines ?? [],
        ]);
    }

    /**
     * Stable hash of everything the artwork depends on. An approval and an export are
     * tied to this hash, so an edited card cannot be sent on an old approval.
     */
    public function contentHash(CardContact $contact, string $profile, string $specVersion): string
    {
        return hash('sha256', json_encode([
            'contact' => $contact,
            'profile' => $profile,
            'spec' => $specVersion,
        ], JSON_THROW_ON_ERROR));
    }
}
