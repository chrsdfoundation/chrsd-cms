<?php

namespace Tests\Unit;

use App\Services\VCardBuilder;
use PHPUnit\Framework\TestCase;

class VCardBuilderTest extends TestCase
{
    protected VCardBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new VCardBuilder;
    }

    public function test_minimal_vcard_with_name_only(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Jane Doe',
        ]);

        $this->assertStringContainsString("BEGIN:VCARD\r\n", $vcard);
        $this->assertStringContainsString("VERSION:3.0\r\n", $vcard);
        $this->assertStringContainsString("FN:Jane Doe\r\n", $vcard);
        $this->assertStringContainsString("N:Doe;Jane;;;\r\n", $vcard);
        $this->assertStringEndsWith("END:VCARD\r\n", $vcard);
    }

    public function test_full_vcard_with_all_fields(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'John Smith',
            'company_name' => 'CHRSD Foundation',
            'job_title' => 'Program Director',
            'phone' => '+880-2-12345678',
            'mobile' => '+8801700000000',
            'whatsapp' => '+8801700000000',
            'work_email' => 'john@chrsd.org',
            'personal_email' => 'john.smith@example.com',
            'website' => 'https://chrsd.org',
            'linkedin' => 'johnsmith',
            'address' => '29 Toyenbee Circular Road, Dhaka',
        ]);

        $this->assertStringContainsString("FN:John Smith\r\n", $vcard);
        $this->assertStringContainsString("ORG:CHRSD Foundation\r\n", $vcard);
        $this->assertStringContainsString("TITLE:Program Director\r\n", $vcard);
        $this->assertStringContainsString("TEL;TYPE=WORK,VOICE:+880-2-12345678\r\n", $vcard);
        $this->assertStringContainsString("TEL;TYPE=CELL:+8801700000000\r\n", $vcard);
        $this->assertStringContainsString("URL;TYPE=WhatsApp:https://wa.me/8801700000000\r\n", $vcard);
        $this->assertStringContainsString("EMAIL;TYPE=WORK:john@chrsd.org\r\n", $vcard);
        $this->assertStringContainsString("EMAIL;TYPE=HOME:john.smith@example.com\r\n", $vcard);
        $this->assertStringContainsString("URL:https://chrsd.org\r\n", $vcard);
        $this->assertStringContainsString("URL;TYPE=LinkedIn:https://www.linkedin.com/in/johnsmith\r\n", $vcard);
        $this->assertStringContainsString("ADR;TYPE=WORK:;;29 Toyenbee Circular Road\\, Dhaka;;;;\r\n", $vcard);
    }

    public function test_empty_fields_are_skipped(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test User',
            'company_name' => '',
            'job_title' => null,
            'phone' => '',
            'mobile' => '',
            'whatsapp' => '',
            'work_email' => '',
            'personal_email' => '',
            'website' => '',
            'linkedin' => '',
            'address' => '',
        ]);

        $this->assertStringNotContainsString('ORG:', $vcard);
        $this->assertStringNotContainsString('TITLE:', $vcard);
        $this->assertStringNotContainsString('TEL', $vcard);
        $this->assertStringNotContainsString('EMAIL', $vcard);
        $this->assertStringNotContainsString('URL', $vcard);
        $this->assertStringNotContainsString('ADR', $vcard);
    }

    public function test_escapes_commas(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Jane Doe',
            'company_name' => 'Acme, Inc.',
        ]);

        $this->assertStringContainsString('ORG:Acme\\, Inc.', $vcard);
    }

    public function test_escapes_semicolons(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Jane Doe',
            'job_title' => 'Director; Programs',
        ]);

        $this->assertStringContainsString('TITLE:Director\\; Programs', $vcard);
    }

    public function test_escapes_backslashes(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Jane Doe',
            'company_name' => 'A\\B Corp',
        ]);

        $this->assertStringContainsString('ORG:A\\\\B Corp', $vcard);
    }

    public function test_escapes_newlines(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Jane Doe',
            'address' => "Line 1\nLine 2",
        ]);

        $this->assertStringContainsString('ADR;TYPE=WORK:;;Line 1\\nLine 2;;;;', $vcard);
    }

    public function test_unicode_bengali_name(): void
    {
        $vcard = $this->builder->build([
            'full_name' => "\xe0\xa6\xae\xe0\xa7\x8b\xe0\xa6\xb9\xe0\xa6\xbe\xe0\xa6\xae\xe0\xa7\x8d\xe0\xa6\xae\xe0\xa6\xa6 \xe0\xa6\xb0\xe0\xa6\xb9\xe0\xa6\xbf\xe0\xa6\xae",
            'company_name' => "\xe0\xa6\xb8\xe0\xa7\x80\xe0\xa6\x8f\xe0\xa6\x87\xe0\xa6\x9a\xe0\xa6\x86\xe0\xa6\xb0\xe0\xa6\x8f\xe0\xa6\xb8\xe0\xa6\xa1\xe0\xa6\xbf",
        ]);

        $this->assertStringContainsString('BEGIN:VCARD', $vcard);
        $this->assertStringContainsString('FN:', $vcard);
        $this->assertStringContainsString('ORG:', $vcard);
    }

    public function test_linkedin_url_passthrough(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test',
            'linkedin' => 'https://www.linkedin.com/in/custom-slug',
        ]);

        $this->assertStringContainsString('URL;TYPE=LinkedIn:https://www.linkedin.com/in/custom-slug', $vcard);
    }

    public function test_linkedin_handle_normalization(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test',
            'linkedin' => '@johnsmith',
        ]);

        $this->assertStringContainsString('URL;TYPE=LinkedIn:https://www.linkedin.com/in/johnsmith', $vcard);
    }

    public function test_linkedin_bare_host_is_not_prefixed_twice(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test',
            'linkedin' => 'www.linkedin.com/in/maramim',
        ]);

        $this->assertContains('URL;TYPE=LinkedIn:https://www.linkedin.com/in/maramim', explode('
', $vcard));
        $this->assertStringNotContainsString('linkedin.com/in/www', $vcard);
    }

    public function test_linkedin_host_without_www_is_normalized(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test',
            'linkedin' => 'linkedin.com/in/maramim',
        ]);

        $this->assertContains('URL;TYPE=LinkedIn:https://www.linkedin.com/in/maramim', explode('
', $vcard));
    }

    public function test_linkedin_in_path_and_company_pages_are_normalized(): void
    {
        $inPath = $this->builder->build(['full_name' => 'Test', 'linkedin' => 'in/maramim']);
        $company = $this->builder->build(['full_name' => 'Test', 'linkedin' => 'linkedin.com/company/chrsd']);

        $this->assertContains('URL;TYPE=LinkedIn:https://www.linkedin.com/in/maramim', explode('
', $inPath));
        $this->assertContains('URL;TYPE=LinkedIn:https://www.linkedin.com/company/chrsd', explode('
', $company));
    }

    public function test_whatsapp_strips_non_digits(): void
    {
        $vcard = $this->builder->build([
            'full_name' => 'Test',
            'whatsapp' => '+880 170-000-0000',
        ]);

        $this->assertStringContainsString('URL;TYPE=WhatsApp:https://wa.me/8801700000000', $vcard);
    }

    public function test_crlf_line_endings(): void
    {
        $vcard = $this->builder->build(['full_name' => 'Test']);

        $this->assertStringNotContainsString("\r\r", $vcard);

        $lines = explode("\r\n", rtrim($vcard, "\r\n"));
        foreach ($lines as $line) {
            $this->assertStringNotContainsString("\n", $line, 'Bare LF found inside a line');
        }
    }

    public function test_single_name_has_empty_last_name(): void
    {
        $vcard = $this->builder->build(['full_name' => 'Madonna']);

        $this->assertStringContainsString("N:;Madonna;;;\r\n", $vcard);
    }
}
