<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Notifications\DocumentRevoked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationBackboneTest extends TestCase
{
    use RefreshDatabase;

    protected function makeEmployee(array $overrides = []): Employee
    {
        static $counter = 0;
        $counter++;
        $dept = Department::firstOrCreate(['code' => 'HR'], ['name' => 'Human Resources']);
        $pos = Position::firstOrCreate(
            ['department_id' => $dept->id, 'code' => 'HR-STAFF'],
            ['title' => 'HR Staff'],
        );

        return Employee::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User' . $counter,
            'email' => "test.user{$counter}@example.com",
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ], $overrides));
    }

    public function test_employee_gets_serial_hash_and_valid_status_on_create(): void
    {
        $e = $this->makeEmployee();

        $this->assertMatchesRegularExpression('/^EMP-\d{4}-\d{6}$/', $e->serial_number);
        $this->assertSame(64, strlen($e->verification_hash));
        $this->assertTrue(ctype_xdigit($e->verification_hash));
        $this->assertSame(VerificationStatus::Valid, $e->status);
        $this->assertNotNull($e->verified_at);
    }

    public function test_serial_number_increments_within_the_same_year(): void
    {
        $year = now()->year;

        $a = $this->makeEmployee();
        $b = $this->makeEmployee();
        $c = $this->makeEmployee();

        $this->assertSame(sprintf('EMP-%d-000001', $year), $a->serial_number);
        $this->assertSame(sprintf('EMP-%d-000002', $year), $b->serial_number);
        $this->assertSame(sprintf('EMP-%d-000003', $year), $c->serial_number);
    }

    public function test_verification_hash_is_unique_across_employees(): void
    {
        $a = $this->makeEmployee();
        $b = $this->makeEmployee();

        $this->assertNotSame($a->verification_hash, $b->verification_hash);
    }

    public function test_verification_hash_is_hmac_keyed_to_app_key_not_plain_sha256(): void
    {
        $e = $this->makeEmployee();

        $payload = $e->verificationPayload();
        ksort($payload);
        $expected = hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            config('app.key'),
        );

        $this->assertSame($expected, $e->verification_hash);

        // A plain SHA-256 of the same payload MUST NOT match — that would mean
        // anyone could forge a hash without the app key.
        $plainSha = hash(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        $this->assertNotSame($plainSha, $e->verification_hash);
    }

    public function test_certificate_uses_its_own_prefix_and_sequence(): void
    {
        $emp = $this->makeEmployee();
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);

        $c1 = Certificate::create([
            'employee_id' => $emp->id, 'certificate_type_id' => $type->id, 'purpose' => 'test1',
        ]);
        $c2 = Certificate::create([
            'employee_id' => $emp->id, 'certificate_type_id' => $type->id, 'purpose' => 'test2',
        ]);

        $year = now()->year;
        $this->assertSame(sprintf('CERT-%d-000001', $year), $c1->serial_number);
        $this->assertSame(sprintf('CERT-%d-000002', $year), $c2->serial_number);
    }

    public function test_revoke_transitions_status_and_stamps_reason(): void
    {
        Notification::fake();

        $emp = $this->makeEmployee();
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);
        $cert = Certificate::create([
            'employee_id' => $emp->id, 'certificate_type_id' => $type->id,
        ]);

        $cert->revoke('Fraudulent request');
        $cert->refresh();

        $this->assertSame(VerificationStatus::Revoked, $cert->status);
        $this->assertNotNull($cert->revoked_at);
        $this->assertSame('Fraudulent request', $cert->revocation_reason);

        // Notification routed to the subject employee (per Certificate::getRevocationNotifiable())
        Notification::assertSentTo($emp, DocumentRevoked::class);
    }

    public function test_byhash_scope_finds_only_exact_match(): void
    {
        $emp = $this->makeEmployee();

        $found = Employee::byHash($emp->verification_hash)->first();
        $miss = Employee::byHash(str_repeat('0', 64))->first();

        $this->assertNotNull($found);
        $this->assertSame($emp->id, $found->id);
        $this->assertNull($miss);
    }

    public function test_valid_scope_filters_out_revoked_documents(): void
    {
        $emp1 = $this->makeEmployee();
        $emp2 = $this->makeEmployee();
        $emp2->revoke('offboarded');

        $active = Employee::valid()->pluck('id')->all();

        $this->assertContains($emp1->id, $active);
        $this->assertNotContains($emp2->id, $active);
    }
}
