<?php

namespace App\Services\BusinessCard;

/**
 * Parses the generated vCard back and compares it with what the card prints.
 *
 * A mismatch blocks approval. This is the guard against the old card's faults:
 * a WhatsApp number recorded as the work phone, the printed office line missing
 * from the QR, a work email tagged HOME, and a doubled LinkedIn URL.
 *
 * Rule: every value in the QR must be printed on the card (nothing is encoded
 * that the card does not show), and the QR must carry every value its profile
 * promises. compact promises the office phone, the mobile, the work email and the
 * website. full promises every printed phone, email, URL and address.
 */
final class CardConsistencyChecker
{
    public function __construct(
        private readonly LinkedInNormaliser $linkedIn,
    ) {}

    /** @return list<string> human-readable mismatches; empty when consistent */
    public function check(CardContact $contact, string $vcard, string $profile): array
    {
        $props = $this->parse($vcard);
        $errors = [];

        // Printed values the QR may carry.
        $printedPhones = array_map(fn (array $p) => $p['e164'], $contact->phones);
        $printedEmails = array_map(fn (array $e) => $e['address'], $contact->emails);
        $printedUrls = array_filter([$contact->website, $contact->linkedinUrl]);

        // Office and mobile: TEL;TYPE=WORK,VOICE and TEL;TYPE=CELL.
        $office = $contact->phonesOfType(CardContact::PHONE_OFFICE);
        $mobile = $contact->phonesOfType(CardContact::PHONE_MOBILE);
        $whatsapp = $contact->phonesOfType(CardContact::PHONE_WHATSAPP);

        $qrWork = $this->values($props, 'TEL', 'WORK');
        $qrCell = $this->values($props, 'TEL', 'CELL');

        foreach ($office as $phone) {
            if (! in_array($phone['e164'], $qrWork, true)) {
                $errors[] = "The QR work phone does not match the printed office line {$phone['display']}.";
            }
        }
        foreach ($mobile as $phone) {
            if (! in_array($phone['e164'], $qrCell, true)) {
                $errors[] = "The QR mobile does not match the printed mobile {$phone['display']}.";
            }
        }
        foreach ($qrWork as $value) {
            if (! in_array($value, array_column($office, 'e164'), true)) {
                $errors[] = "The QR work phone {$value} is not the printed office line (WhatsApp or another number was recorded as work).";
            }
        }
        foreach ($qrCell as $value) {
            if (! in_array($value, array_column($mobile, 'e164'), true)) {
                $errors[] = "The QR mobile {$value} is not a printed mobile.";
            }
        }

        // Emails: the work email must be typed WORK, and must be printed.
        foreach ($this->valuesWithType($props, 'EMAIL') as [$type, $value]) {
            if (! in_array($value, $printedEmails, true)) {
                $errors[] = "The QR email {$value} is not printed on the card.";
            }
            $expected = $this->emailTypeFor($contact, $value);
            if ($expected !== null && $type !== $expected) {
                $errors[] = "The QR email {$value} is tagged {$type} but the card lists it as {$expected}.";
            }
        }

        // URLs: the website, the LinkedIn profile, and the WhatsApp link.
        $qrUrls = array_map(fn (array $u) => $u[1], $this->valuesWithType($props, 'URL'));
        foreach ($qrUrls as $url) {
            $isWhatsApp = str_starts_with($url, 'https://wa.me/');
            $isLinkedIn = str_contains($url, 'linkedin.com');
            if ($isWhatsApp) {
                $digits = array_map(fn (array $p) => $this->digits($p['e164']), $whatsapp);
                if (! in_array(substr($url, strlen('https://wa.me/')), $digits, true)) {
                    $errors[] = "The QR WhatsApp link {$url} does not match the printed WhatsApp number.";
                }

                continue;
            }
            if ($isLinkedIn) {
                if (substr_count($url, 'linkedin.com') !== 1 || $this->linkedIn->handleOf($url) === null) {
                    $errors[] = "The QR LinkedIn link {$url} is malformed (doubled or not a profile URL).";
                } elseif ($contact->linkedinUrl !== $url) {
                    $errors[] = "The QR LinkedIn link {$url} does not match the printed profile.";
                }

                continue;
            }
            if (! in_array($url, $printedUrls, true)) {
                $errors[] = "The QR URL {$url} is not printed on the card.";
            }
        }

        // Profile promises.
        if ($contact->website !== null && $contact->website !== '' && ! in_array($contact->website, $qrUrls, true)) {
            $errors[] = "The printed website {$contact->website} is missing from the QR.";
        }
        $workEmail = $contact->emailsOfType(CardContact::EMAIL_WORK)[0]['address'] ?? null;
        if ($workEmail !== null && ! in_array($workEmail, array_map(fn ($e) => $e[1], $this->valuesWithType($props, 'EMAIL')), true)) {
            $errors[] = "The printed work email {$workEmail} is missing from the QR.";
        }

        if ($profile === CardVCardBuilder::PROFILE_FULL) {
            foreach ($printedEmails as $email) {
                if (! in_array($email, array_map(fn ($e) => $e[1], $this->valuesWithType($props, 'EMAIL')), true)) {
                    $errors[] = "The printed email {$email} is missing from the full QR.";
                }
            }
            foreach ($whatsapp as $phone) {
                $link = 'https://wa.me/' . $this->digits($phone['e164']);
                if (! in_array($link, $qrUrls, true)) {
                    $errors[] = "The printed WhatsApp {$phone['display']} is missing from the full QR.";
                }
            }
            if ($contact->linkedinUrl !== null && ! in_array($contact->linkedinUrl, $qrUrls, true)) {
                $errors[] = 'The printed LinkedIn profile is missing from the full QR.';
            }
        }

        return $errors;
    }

    /**
     * @return array<string, list<array{0: string, 1: string}>> property => [[params, value], ...]
     */
    private function parse(string $vcard): array
    {
        // Unfold continuation lines (CRLF followed by a space or tab).
        $unfolded = preg_replace("/\r\n[ \t]/", '', $vcard) ?? $vcard;
        $props = [];
        foreach (preg_split("/\r\n/", $unfolded, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = substr($line, 0, $colon);
            $value = $this->unescape(substr($line, $colon + 1));
            $parts = explode(';', $name);
            $prop = strtoupper(array_shift($parts));
            $params = strtoupper(implode(';', $parts));
            $props[$prop][] = [$params, $value];
        }

        return $props;
    }

    /** @return list<string> */
    private function values(array $props, string $prop, string $type): array
    {
        $out = [];
        foreach ($props[$prop] ?? [] as [$params, $value]) {
            if (str_contains($params, $type)) {
                $out[] = $value;
            }
        }

        return $out;
    }

    /** @return list<array{0: string, 1: string}> [type, value] */
    private function valuesWithType(array $props, string $prop): array
    {
        $out = [];
        foreach ($props[$prop] ?? [] as [$params, $value]) {
            $type = str_contains($params, 'TYPE=HOME') ? 'HOME'
                : (str_contains($params, 'TYPE=WORK') ? 'WORK'
                : (str_contains($params, 'LINKEDIN') ? 'LINKEDIN'
                : (str_contains($params, 'WHATSAPP') ? 'WHATSAPP' : 'OTHER')));
            $out[] = [$type, $value];
        }

        return $out;
    }

    private function emailTypeFor(CardContact $contact, string $address): ?string
    {
        foreach ($contact->emails as $email) {
            if ($email['address'] === $address) {
                return $email['type'] === CardContact::EMAIL_PERSONAL ? 'HOME' : 'WORK';
            }
        }

        return null;
    }

    private function digits(string $e164): string
    {
        return ltrim(preg_replace('/\D+/', '', $e164) ?? '', '+');
    }

    /** Single pass, so an escaped backslash followed by n is not read as a newline. */
    private function unescape(string $value): string
    {
        return preg_replace_callback('/\\\\(.)/s', static function (array $m): string {
            return match ($m[1]) {
                'n', 'N' => "\n",
                ',' => ',',
                ';' => ';',
                '\\' => '\\',
                default => $m[0],
            };
        }, $value) ?? $value;
    }
}
