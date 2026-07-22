<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComplianceManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSomeDocs(): void
    {
        $dept = Department::create(['code' => 'HR', 'name' => 'Human Resources']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'HR-STAFF', 'title' => 'HR Staff']);
        $emp = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);
        $cert = Certificate::create(['employee_id' => $emp->id, 'certificate_type_id' => $type->id]);
        $cert->revoke('smoke test revocation');
    }

    public function test_manifest_is_a_64_char_hex_hash(): void
    {
        $this->seedSomeDocs();

        $data = app(ReportService::class)->complianceBundle(now()->subDay(), now());

        $this->assertSame(64, strlen($data['manifest']));
        $this->assertTrue(ctype_xdigit($data['manifest']));
    }

    public function test_same_inputs_produce_the_same_manifest(): void
    {
        $this->seedSomeDocs();

        $from = Carbon::now()->subDay();
        $to = Carbon::now();

        $a = app(ReportService::class)->complianceBundle($from, $to);
        $b = app(ReportService::class)->complianceBundle($from, $to);

        $this->assertSame($a['manifest'], $b['manifest']);
    }

    public function test_changing_the_period_changes_the_manifest(): void
    {
        $this->seedSomeDocs();

        $a = app(ReportService::class)->complianceBundle(now()->subDays(30), now());
        $b = app(ReportService::class)->complianceBundle(now()->subDays(7), now());

        $this->assertNotSame($a['manifest'], $b['manifest']);
    }

    public function test_bundle_totals_reflect_what_it_covers(): void
    {
        $this->seedSomeDocs();

        $data = app(ReportService::class)->complianceBundle(now()->subDay(), now());

        // 1 employee + 1 certificate = 2 documents in the register
        $this->assertSame(2, $data['totals']['documents']);
        // The single revocation from seedSomeDocs()
        $this->assertSame(1, $data['totals']['revocations']);
    }
}
