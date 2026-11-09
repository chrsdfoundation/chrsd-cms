<?php

namespace App\Services\BusinessCard;

/**
 * The contact facts printed on a card, normalised. Both the printed card and the
 * QR code are built from this one object, so they cannot disagree.
 */
final readonly class CardContact
{
    public const PHONE_OFFICE = 'office';

    public const PHONE_MOBILE = 'mobile';

    public const PHONE_WHATSAPP = 'whatsapp';

    public const EMAIL_WORK = 'work';

    public const EMAIL_PERSONAL = 'personal';

    /**
     * @param  list<array{type: string, e164: string, display: string}>  $phones
     * @param  list<array{type: string, address: string}>  $emails
     * @param  list<string>  $addressLines
     */
    public function __construct(
        public string $personName,
        public string $personTitle,
        public string $orgName,
        public string $orgShort,
        public array $phones,
        public array $emails,
        public ?string $website,
        public ?string $linkedinUrl,
        public array $addressLines,
    ) {}

    /** @return list<array{type: string, e164: string, display: string}> */
    public function phonesOfType(string $type): array
    {
        return array_values(array_filter($this->phones, fn (array $p) => $p['type'] === $type));
    }

    /** @return list<array{type: string, address: string}> */
    public function emailsOfType(string $type): array
    {
        return array_values(array_filter($this->emails, fn (array $e) => $e['type'] === $type));
    }
}
