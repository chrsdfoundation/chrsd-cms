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

class VerifyEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify');
    }

    protected function makeCertificate(): Certificate
    {
        $dept = Department::create(['code' => 'HR', 'name' => 'Human Resources']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'HR-STAFF', 'title' => 'HR Staff']);
        $emp = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);

        return Certificate::create([
            'employee_id' => $emp->id, 'certificate_type_id' => $type->id,
        ]);
    }

    public function test_html_endpoint_returns_200_and_status_valid_for_a_real_hash(): void
    {
        $cert = $this->makeCertificate();

        $this->get("/verify/{$cert->verification_hash}")
            ->assertOk()
            ->assertSee($cert->serial_number)
            ->assertSee('Valid', false); // case-insensitive not needed; blade uppercases
    }

    public function test_html_endpoint_returns_404_for_unknown_hash_of_correct_shape(): void
    {
        $bogus = str_repeat('0', 64);

        $this->get("/verify/{$bogus}")->assertNotFound();
    }

    public function test_html_endpoint_returns_404_for_malformed_hash_via_route_pattern(): void
    {
        // The route is constrained to [a-f0-9]{64}. Anything else must 404.
        $this->get('/verify/not-a-hash')->assertNotFound();
        $this->get('/verify/' . str_repeat('g', 64))->assertNotFound(); // 'g' isn't hex
    }

    public function test_json_endpoint_returns_expected_shape(): void
    {
        $cert = $this->makeCertificate();

        $this->getJson("/api/verify/{$cert->verification_hash}")
            ->assertOk()
            ->assertJsonStructure([
                'found',
                'snapshot' => ['serial', 'kind', 'status', 'issued_on', 'valid_until', 'revoked_at', 'revocation_reason', 'is_valid'],
            ])
            ->assertJson([
                'found' => true,
                'snapshot' => [
                    'serial' => $cert->serial_number,
                    'kind' => 'Certificate',
                    'is_valid' => true,
                ],
            ]);
    }

    public function test_json_endpoint_returns_404_json_for_unknown_hash(): void
    {
        $bogus = str_repeat('a', 64);

        $this->getJson("/api/verify/{$bogus}")
            ->assertStatus(404)
            ->assertExactJson(['found' => false]);
    }

    public function test_response_carries_privacy_and_no_indexing_headers(): void
    {
        $cert = $this->makeCertificate();

        $res = $this->get("/verify/{$cert->verification_hash}");

        $res->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $res->assertHeader('Referrer-Policy', 'no-referrer');
        $res->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_rate_limiter_blocks_after_the_configured_threshold(): void
    {
        $cert = $this->makeCertificate();
        $url = "/verify/{$cert->verification_hash}";

        // 30 per minute → first 30 must all succeed
        for ($i = 0; $i < 30; $i++) {
            $this->get($url)->assertOk();
        }

        // 31st request from the same IP must be throttled
        $this->get($url)->assertStatus(429);
    }
}
