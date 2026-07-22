<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiTokenVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected Certificate $cert;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear both buckets — the authed limiter shares the anonymous limiter
        // for anonymous requests, so we clear the underlying keys.
        RateLimiter::clear('verify_authed');
        RateLimiter::clear('verify');

        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Staff']);
        $emp = Employee::create([
            'first_name' => 'J', 'last_name' => 'D',
            'email' => 'j@test.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);
        $this->cert = Certificate::create(['employee_id' => $emp->id, 'certificate_type_id' => $type->id]);

        $this->user = User::create([
            'name' => 'Integrator', 'email' => 'api@test.test',
            'password' => Hash::make('secret'),
        ]);
    }

    public function test_anonymous_verify_still_works(): void
    {
        $this->getJson("/api/verify/{$this->cert->verification_hash}")
            ->assertOk()
            ->assertJsonPath('found', true);
    }

    public function test_authenticated_verify_works_and_stamps_usage(): void
    {
        $token = $this->user->createToken('Test integration', ['verify:read'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/verify/{$this->cert->verification_hash}")
            ->assertOk()
            ->assertJsonPath('found', true);

        $pat = $this->user->tokens()->first();
        $this->assertNotNull($pat->last_used_at, 'Sanctum should have stamped last_used_at');
        $this->assertNotNull($pat->last_used_ip, 'Middleware should have stamped last_used_ip');
    }

    public function test_invalid_token_is_ignored_softly_endpoint_still_serves_anonymously(): void
    {
        // A garbage token must not 401 — the endpoint is anonymous-accessible.
        $this->withHeader('Authorization', 'Bearer not_a_real_token_12345')
            ->getJson("/api/verify/{$this->cert->verification_hash}")
            ->assertOk()
            ->assertJsonPath('found', true);
    }

    public function test_authenticated_limit_is_higher_than_anonymous(): void
    {
        // Anonymous would be blocked at 30/min. Fire 60 with a valid token —
        // all should pass.
        $token = $this->user->createToken('Test integration', ['verify:read'])->plainTextToken;

        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson("/api/verify/{$this->cert->verification_hash}")
                ->assertOk();
        }
    }

    public function test_anonymous_still_rate_limited_at_30_per_minute(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson("/api/verify/{$this->cert->verification_hash}")->assertOk();
        }

        $this->getJson("/api/verify/{$this->cert->verification_hash}")->assertStatus(429);
    }
}
