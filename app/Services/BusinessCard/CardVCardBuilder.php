<?php

namespace App\Services\BusinessCard;

/**
 * vCard 3.0 (RFC 2426) for the executive card.
 *
 * - CRLF line endings, values escaped for backslash, comma, semicolon and newline.
 * - Lines folded at 75 octets (RFC 2425 section 5.8.1).
 * - Office phone is TEL;TYPE=WORK,VOICE, mobile is TEL;TYPE=CELL, and WhatsApp is a
 *   wa.me URL. Work emails are typed WORK (the old card wrongly used HOME).
 *
 * Profiles (card-spec.json, qr.profiles):
 *   compact: FN, N, ORG (short), TITLE, office TEL, mobile TEL, work EMAIL, URL.
 *   full:    adds WhatsApp URL, every email, LinkedIn URL and ADR.
 */
final class CardVCardBuilder
{
    public const PROFILE_COMPACT = 'compact';

    public const PROFILE_FULL = 'full';

    public function __construct(
        private readonly PhoneNumberNormaliser $phones,
    ) {}

    public function build(CardContact $contact, string $profile = self::PROFILE_COMPACT): string
    {
        if (! in_array($profile, [self::PROFILE_COMPACT, self::PROFILE_FULL], true)) {
            throw new \InvalidArgumentException("Unknown vCard profile [{$profile}].");
        }
        $full = $profile === self::PROFILE_FULL;

        $lines = ['BEGIN:VCARD', 'VERSION:3.0'];

        $lines[] = 'FN:' . $this->escape($contact->personName);
        $lines[] = 'N:' . $this->structuredName($contact->personName);
        $lines[] = 'ORG:' . $this->escape($full ? $contact->orgName : $contact->orgShort);
        if ($contact->personTitle !== '') {
            $lines[] = 'TITLE:' . $this->escape($contact->personTitle);
        }

        foreach ($contact->phonesOfType(CardContact::PHONE_OFFICE) as $phone) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . $this->escape($phone['e164']);
        }
        foreach ($contact->phonesOfType(CardContact::PHONE_MOBILE) as $phone) {
            $lines[] = 'TEL;TYPE=CELL:' . $this->escape($phone['e164']);
        }

        if ($full) {
            foreach ($contact->phonesOfType(CardContact::PHONE_WHATSAPP) as $phone) {
                $lines[] = 'URL;TYPE=WhatsApp:https://wa.me/' . $this->phones->digitsOnly($phone['e164']);
            }
            foreach ($contact->emails as $email) {
                $type = $email['type'] === CardContact::EMAIL_PERSONAL ? 'HOME' : 'WORK';
                $lines[] = "EMAIL;TYPE={$type}:" . $this->escape($email['address']);
            }
        } else {
            foreach ($contact->emailsOfType(CardContact::EMAIL_WORK) as $email) {
                $lines[] = 'EMAIL;TYPE=WORK:' . $this->escape($email['address']);
                break;
            }
        }

        if ($contact->website !== null && $contact->website !== '') {
            $lines[] = 'URL:' . $this->escape($contact->website);
        }

        if ($full && $contact->linkedinUrl !== null) {
            $lines[] = 'URL;TYPE=LinkedIn:' . $this->escape($contact->linkedinUrl);
        }

        if ($full && $contact->addressLines !== []) {
            $lines[] = 'ADR;TYPE=WORK:;;' . $this->escape(implode(', ', $contact->addressLines)) . ';;;;';
        }

        $lines[] = 'END:VCARD';

        $out = '';
        foreach ($lines as $line) {
            $out .= $this->fold($line) . "\r\n";
        }

        return $out;
    }

    /** N: family;given;;; — the last word is the family name, the rest are given names. */
    private function structuredName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return ';;;;';
        }

        $family = array_pop($parts);
        $given = implode(' ', $parts);

        return $this->escape($family) . ';' . $this->escape($given) . ';;;';
    }

    public function escape(string $value): string
    {
        $value = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $value);
        $value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);

        return $value;
    }

    /** Fold a content line at 75 octets; continuation lines start with one space. */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $first = true;
        while ($line !== '') {
            $limit = $first ? 75 : 74;
            $chunk = substr($line, 0, $limit);
            // Do not split a multi-byte UTF-8 character.
            while ($chunk !== '' && strlen($line) > $limit && (ord($line[strlen($chunk)] ?? "\0") & 0xC0) === 0x80) {
                $chunk = substr($chunk, 0, -1);
            }
            $out .= ($first ? '' : "\r\n ") . $chunk;
            $line = (string) substr($line, strlen($chunk));
            $first = false;
        }

        return $out;
    }
}
