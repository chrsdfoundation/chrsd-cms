<?php

namespace Tests\Unit\BusinessCard;

use App\Services\BusinessCard\CardConsistencyChecker;
use App\Services\BusinessCard\CardContact;
use App\Services\BusinessCard\CardVCardBuilder;
use App\Services\BusinessCard\LinkedInNormaliser;
use App\Services\BusinessCard\PhoneNumberNormaliser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CardServicesTest extends TestCase
{
    private PhoneNumberNormaliser $phones;

    private LinkedInNormaliser $linkedIn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phones = new PhoneNumberNormaliser;
        $this->linkedIn = new LinkedInNormaliser;
    }

    /** The executive card's own contact data, normalised the same way the form will. */
    private function ramim(array $overrides = []): CardContact
    {
        $office = $this->phones->normalise('+880 2 47122566');
        $mobile = $this->phones->normalise('+880 1602-778984');
        $whatsapp = $this->phones->normalise('+880 1334-383341');

        $args = [
            'personName' => 'Mohammed Abu Ramim',
            'personTitle' => 'Executive Director',
            'orgName' => 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)',
            'orgShort' => 'CHRSD',
            'phones' => [
                ['type' => CardContact::PHONE_OFFICE, 'e164' => $office['e164'], 'display' => '+880 2 47122566'],
                ['type' => CardContact::PHONE_MOBILE, 'e164' => $mobile['e164'], 'display' => '+880 1602-778984'],
                ['type' => CardContact::PHONE_WHATSAPP, 'e164' => $whatsapp['e164'], 'display' => '+880 1334-383341'],
            ],
            'emails' => [
                ['type' => CardContact::EMAIL_WORK, 'address' => 'info@chrsd.org'],
                ['type' => CardContact::EMAIL_WORK, 'address' => 'ed@chrsd.org'],
            ],
            'website' => 'https://chrsd.org',
            'linkedinUrl' => 'https://www.linkedin.com/in/maramim',
            'addressLines' => ['29 Toyenbee Circular Road (5th Floor)', 'Motijheel C/A', 'Dhaka-1000, Bangladesh'],
        ];

        return new CardContact(...array_merge($args, $overrides));
    }

    private function vcard(CardContact $contact, string $profile = CardVCardBuilder::PROFILE_COMPACT): string
    {
        return (new CardVCardBuilder($this->phones))->build($contact, $profile);
    }

    /* ---------- LinkedIn ---------- */

    public function test_linkedin_handle_forms_normalise_to_one_profile_url(): void
    {
        foreach (['maramim', '@maramim', '/maramim', 'in/maramim', 'linkedin.com/in/maramim',
            'www.linkedin.com/in/maramim', 'https://www.linkedin.com/in/maramim/', 'https://linkedin.com/in/maramim?trk=x'] as $input) {
            $this->assertSame('https://www.linkedin.com/in/maramim', $this->linkedIn->normalise($input), $input);
        }
    }

    public function test_linkedin_doubled_path_bug_is_repaired(): void
    {
        // The exact value that shipped as https://www.linkedin.com/in/www.linkedin.com/in/maramim.
        $this->assertSame(
            'https://www.linkedin.com/in/maramim',
            $this->linkedIn->normalise('linkedin.com/in/www.linkedin.com/in/maramim'),
        );
        $this->assertSame(
            'https://www.linkedin.com/in/maramim',
            $this->linkedIn->normalise('https://www.linkedin.com/in/www.linkedin.com/in/maramim'),
        );
    }

    public function test_linkedin_rejects_values_that_are_not_a_profile_handle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->linkedIn->normalise('bad handle!!');
    }

    /* ---------- phone ---------- */

    public function test_bangladesh_numbers_normalise_to_e164_and_keep_the_display_form(): void
    {
        $mobile = $this->phones->normalise('+880 1602-778984');

        $this->assertSame('+8801602778984', $mobile['e164']);
        $this->assertStringStartsWith('+880 1602', $mobile['display']);
        $this->assertSame('8801334383341', $this->phones->digitsOnly($this->phones->normalise('+880 1334-383341')['e164']));
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->phones->normalise('+880 123');
    }

    /* ---------- vCard ---------- */

    public function test_vcard_has_crlf_endings_and_the_required_types(): void
    {
        $vcard = $this->vcard($this->ramim());

        $this->assertStringStartsWith("BEGIN:VCARD\r\nVERSION:3.0\r\n", $vcard);
        $this->assertStringEndsWith("END:VCARD\r\n", $vcard);
        $this->assertStringContainsString("TEL;TYPE=WORK,VOICE:+880247122566\r\n", $vcard);
        $this->assertStringContainsString("TEL;TYPE=CELL:+8801602778984\r\n", $vcard);
        $this->assertStringContainsString("EMAIL;TYPE=WORK:info@chrsd.org\r\n", $vcard);
        $this->assertStringNotContainsString('TYPE=HOME', $vcard);
    }

    public function test_compact_profile_carries_office_mobile_work_email_and_website_only(): void
    {
        $vcard = $this->vcard($this->ramim(), CardVCardBuilder::PROFILE_COMPACT);

        $this->assertStringContainsString('ORG:CHRSD', $vcard);
        $this->assertStringContainsString('URL:https://chrsd.org', $vcard);
        $this->assertStringNotContainsString('wa.me', $vcard);
        $this->assertStringNotContainsString('ed@chrsd.org', $vcard);
        $this->assertStringNotContainsString('ADR', $vcard);
    }

    public function test_full_profile_adds_whatsapp_all_emails_linkedin_and_address(): void
    {
        $vcard = $this->vcard($this->ramim(), CardVCardBuilder::PROFILE_FULL);

        $this->assertStringContainsString('URL;TYPE=WhatsApp:https://wa.me/8801334383341', $vcard);
        $this->assertStringContainsString('EMAIL;TYPE=WORK:ed@chrsd.org', $vcard);
        $this->assertStringContainsString('URL;TYPE=LinkedIn:https://www.linkedin.com/in/maramim', $vcard);
        $this->assertStringContainsString('ADR;TYPE=WORK:', $vcard);
    }

    public function test_special_characters_are_escaped(): void
    {
        $contact = $this->ramim(['personTitle' => 'Director, Programmes; Field\\Ops']);

        $vcard = $this->vcard($contact);

        $this->assertStringContainsString('TITLE:Director\\, Programmes\\; Field\\\\Ops', $vcard);
    }

    public function test_long_lines_are_folded_at_75_octets(): void
    {
        $vcard = $this->vcard($this->ramim(), CardVCardBuilder::PROFILE_FULL);

        foreach (explode("\r\n", $vcard) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Line too long: {$line}");
        }
        $this->assertStringContainsString("\r\n ", $vcard, 'The ADR line should be folded.');
    }

    /* ---------- consistency checker: the three known bugs ---------- */

    private function check(CardContact $contact, string $vcard, string $profile = CardVCardBuilder::PROFILE_COMPACT): array
    {
        return (new CardConsistencyChecker($this->linkedIn))->check($contact, $vcard, $profile);
    }

    public function test_consistent_card_and_qr_pass(): void
    {
        $contact = $this->ramim();

        $this->assertSame([], $this->check($contact, $this->vcard($contact)));
        $this->assertSame([], $this->check($contact, $this->vcard($contact, CardVCardBuilder::PROFILE_FULL), CardVCardBuilder::PROFILE_FULL));
    }

    public function test_bug_whatsapp_recorded_as_work_phone_is_blocked(): void
    {
        $contact = $this->ramim();
        $office = $contact->phonesOfType(CardContact::PHONE_OFFICE)[0]['e164'];
        $whatsapp = $contact->phonesOfType(CardContact::PHONE_WHATSAPP)[0]['e164'];
        // The QR as the old card produced it: the WhatsApp number in place of the office line.
        $bad = str_replace("TEL;TYPE=WORK,VOICE:{$office}", "TEL;TYPE=WORK,VOICE:{$whatsapp}", $this->vcard($contact));

        $errors = $this->check($contact, $bad);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('work phone', implode(' ', $errors));
    }

    public function test_bug_printed_office_line_missing_from_qr_is_blocked(): void
    {
        $contact = $this->ramim();
        $office = $contact->phonesOfType(CardContact::PHONE_OFFICE)[0]['e164'];
        $bad = str_replace("TEL;TYPE=WORK,VOICE:{$office}\r\n", '', $this->vcard($contact));

        $errors = $this->check($contact, $bad);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('printed office line', implode(' ', $errors));
    }

    public function test_bug_work_email_tagged_home_is_blocked(): void
    {
        $contact = $this->ramim();
        $bad = str_replace('EMAIL;TYPE=WORK:info@chrsd.org', 'EMAIL;TYPE=HOME:info@chrsd.org', $this->vcard($contact));

        $errors = $this->check($contact, $bad);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('tagged HOME', implode(' ', $errors));
    }

    public function test_bug_doubled_linkedin_path_is_blocked(): void
    {
        $contact = $this->ramim();
        $bad = str_replace(
            'URL;TYPE=LinkedIn:https://www.linkedin.com/in/maramim',
            'URL;TYPE=LinkedIn:https://www.linkedin.com/in/www.linkedin.com/in/maramim',
            $this->vcard($contact, CardVCardBuilder::PROFILE_FULL),
        );

        $errors = $this->check($contact, $bad, CardVCardBuilder::PROFILE_FULL);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('LinkedIn', implode(' ', $errors));
    }

    public function test_qr_value_not_printed_on_the_card_is_blocked(): void
    {
        $contact = $this->ramim();
        $bad = str_replace('EMAIL;TYPE=WORK:info@chrsd.org', "EMAIL;TYPE=WORK:someone@example.com\r\nX-EXTRA:1", $this->vcard($contact));

        $errors = $this->check($contact, $bad);

        $this->assertStringContainsString('someone@example.com is not printed', implode(' ', $errors));
    }
}
