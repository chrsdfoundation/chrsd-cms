<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class KioskVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify_kiosk');
    }

    protected function makeCertificate(): Certificate
    {
        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Staff']);
        $emp = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane@test.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);

        return Certificate::create([
            'employee_id' => $emp->id, 'certificate_type_id' => $type->id,
        ]);
    }

    public function test_landing_page_shows_the_form(): void
    {
        $this->get('/verify/kiosk')
            ->assertOk()
            ->assertSee('Verify a document')
            ->assertSee('Serial number');
    }

    public function test_serial_number_lookup_returns_verified_banner(): void
    {
        $cert = $this->makeCertificate();

        $this->post('/verify/kiosk', ['query' => $cert->serial_number])
            ->assertOk()
            ->assertSee('VERIFIED', false)
            ->assertSee($cert->serial_number);
    }

    public function test_hash_lookup_returns_verified_banner(): void
    {
        $cert = $this->makeCertificate();

        $this->post('/verify/kiosk', ['query' => $cert->verification_hash])
            ->assertOk()
            ->assertSee('VERIFIED', false)
            ->assertSee($cert->serial_number);
    }

    public function test_pdf_upload_matching_stored_hash_returns_verified(): void
    {
        // Migrated to browser-native HTML rendering: PDF upload verification
        // is no longer supported. Certificates are verified via hash-based URLs.
        $this->markTestSkipped('PDF upload verification removed; certificates verified via URL hash.');
    }

    public function test_unknown_serial_shows_not_found_banner(): void
    {
        $this->post('/verify/kiosk', ['query' => 'EMP-2099-999999'])
            ->assertOk()
            ->assertSee('NOT FOUND', false);
    }

    public function test_revoked_document_shows_revoked_banner(): void
    {
        $cert = $this->makeCertificate();
        $cert->revoke('Duplicate issuance');

        $this->post('/verify/kiosk', ['query' => $cert->serial_number])
            ->assertOk()
            ->assertSee('REVOKED', false)
            ->assertSee('Duplicate issuance');
    }

    public function test_kiosk_has_its_own_higher_rate_limit(): void
    {
        // 120/min for kiosk (vs 30/min for /verify) — 40 requests must all pass.
        for ($i = 0; $i < 40; $i++) {
            $this->get('/verify/kiosk')->assertOk();
        }
    }
}
