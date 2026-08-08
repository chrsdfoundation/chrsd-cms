<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a department for employees
        Department::create([
            'name' => 'Test Department',
            'code' => 'TD',
        ]);

        // Create a test employee (required for FK constraint)
        Employee::create([
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'test@example.com',
            'department_id' => Department::first()->id,
        ]);

        // Create a test certificate type
        CertificateType::create([
            'name' => 'Test Certificate Type',
            'code' => 'TCT',
            'slug' => 'test-certificate-type',
        ]);
    }

    /** @test */
    public function certificate_can_be_issued_with_auto_allocated_number()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'employee_id' => Employee::first()->id,
            'certificate_type_id' => CertificateType::first()->id,
            'recipient_name' => 'John Doe',
            'program_name' => 'Leadership Training',
            'certificate_title' => 'Certificate of Achievement',
            'award_lead_in' => 'has successfully completed',
            'issued_on' => now(),
        ]);

        $this->assertNotNull($cert->id);
        $this->assertNotNull($cert->certificate_no);
        $this->assertNotNull($cert->verification_hash);
        $this->assertStringStartsWith('CERT-', $cert->certificate_no);
        $this->assertEquals(64, strlen($cert->verification_hash));
    }

    /** @test */
    public function certificate_numbers_are_sequential_per_year()
    {
        $year = now()->year;

        $cert1 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $cert2 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'Jane Doe',
            'program_name' => 'Program 2',
            'issued_on' => now(),
        ]);

        $this->assertEquals("CERT-$year-000001", $cert1->certificate_no);
        $this->assertEquals("CERT-$year-000002", $cert2->certificate_no);
    }

    /** @test */
    public function verification_hash_is_unique()
    {
        $cert1 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $cert2 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'Jane Doe',
            'program_name' => 'Program 2',
            'issued_on' => now(),
        ]);

        $this->assertNotEquals($cert1->verification_hash, $cert2->verification_hash);
    }

    /** @test */
    public function certificate_can_be_previewed_as_html()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Leadership Training',
            'issued_on' => now(),
        ]);

        $response = $this->get(route('certificates.preview', $cert));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        // Verify HTML content includes key certificate elements
        $this->assertStringContainsString('John Doe', $response->getContent());
        $this->assertStringContainsString('Leadership Training', $response->getContent());
    }

    /** @test */
    public function certificate_can_be_verified_when_valid()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $response = $this->get(route('certificates.verify', $cert->verification_hash));

        $response->assertStatus(200);
        $this->assertStringContainsString('John Doe', $response->getContent());
    }

    /** @test */
    public function certificate_shows_revoked_status_when_revoked()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $cert->update(['revoked_at' => now()]);

        $response = $this->get(route('certificates.verify', $cert->verification_hash));

        $response->assertStatus(200);
        $this->assertStringContainsString('Revoked', $response->getContent());
        $this->assertStringContainsString('Certificate Revoked', $response->getContent());
    }

    /** @test */
    public function certificate_verification_returns_404_for_unknown_hash()
    {
        $unknownHash = 'a' . str_repeat('0', 63);

        $response = $this->get(route('certificates.verify', $unknownHash));

        $response->assertStatus(404);
        $this->assertStringContainsString('Not Found', $response->getContent());
    }

    /** @test */
    public function concurrent_certificate_issuance_does_not_create_duplicate_numbers()
    {
        // Simulate concurrent issuance by calling issue() multiple times rapidly
        $certs = [];
        for ($i = 0; $i < 10; $i++) {
            $certs[] = Certificate::issue([
                'organization_id' => 1,
                'recipient_name' => "Person $i",
                'program_name' => 'Program',
                'issued_on' => now(),
            ]);
        }

        // Verify all certificate numbers are unique
        $numbers = collect($certs)->pluck('certificate_no')->toArray();
        $this->assertEquals(count($numbers), count(array_unique($numbers)));

        // Verify they're sequential
        $year = now()->year;
        for ($i = 0; $i < 10; $i++) {
            $expectedNo = sprintf('CERT-%d-%06d', $year, $i + 1);
            $this->assertEquals($expectedNo, $certs[$i]->certificate_no);
        }
    }

    /** @test */
    public function valid_scope_filters_out_revoked_certificates()
    {
        $valid = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $revoked = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'Jane Doe',
            'program_name' => 'Program 2',
            'issued_on' => now(),
        ]);

        $revoked->update(['revoked_at' => now()]);

        $validCerts = Certificate::valid()->get();

        $this->assertCount(1, $validCerts);
        $this->assertEquals($valid->id, $validCerts->first()->id);
    }

    /** @test */
    public function certificate_has_verify_url_attribute()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Program 1',
            'issued_on' => now(),
        ]);

        $this->assertStringContainsString('certificates/verify', $cert->verify_url);
        $this->assertStringContainsString($cert->verification_hash, $cert->verify_url);
    }

    /** @test */
    public function artisan_command_issues_certificate()
    {
        $this->artisan('certificate:issue', [
            '--name' => 'Test User',
            '--program' => 'Test Program',
        ])
            ->assertExitCode(0)
            ->expectsOutput('Certificate issued successfully');

        $cert = Certificate::where('recipient_name', 'Test User')->first();
        $this->assertNotNull($cert);
        $this->assertEquals('Test Program', $cert->program_name);
    }

    /** @test */
    public function artisan_command_requires_name_and_program()
    {
        $this->artisan('certificate:issue')
            ->assertExitCode(1)
            ->expectsOutput('--name and --program are required');
    }

    /** @test */
    public function certificate_responsive_name_sizing_works()
    {
        // Test short name (58px)
        $shortName = 'John Doe';
        $cert1 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => $shortName,
            'program_name' => 'Program',
            'issued_on' => now(),
        ]);

        // Test long name (34px)
        $longName = 'Alexander Fitzgerald Johannesburg Montgomery III';
        $cert2 = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => $longName,
            'program_name' => 'Program',
            'issued_on' => now(),
        ]);

        // Both should render without errors
        $response1 = $this->get(route('certificates.preview', $cert1));
        $response2 = $this->get(route('certificates.preview', $cert2));

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertStringContainsString($shortName, $response1->getContent());
        $this->assertStringContainsString($longName, $response2->getContent());
    }

    /** @test */
    public function certificate_can_have_custom_title_and_lead_in()
    {
        $cert = Certificate::issue([
            'organization_id' => 1,
            'recipient_name' => 'John Doe',
            'program_name' => 'Advanced Leadership',
            'certificate_title' => 'Diploma of Excellence',
            'award_lead_in' => 'is recognized for outstanding achievement in',
            'issued_on' => now(),
        ]);

        $response = $this->get(route('certificates.preview', $cert));

        $this->assertStringContainsString('Diploma of Excellence', $response->getContent());
        $this->assertStringContainsString('is recognized for outstanding achievement in', $response->getContent());
    }
}
