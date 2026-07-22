<?php

namespace Tests\Feature;

use App\Enums\CertificateIssuance;
use App\Enums\CertificateRequestStatus;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Notifications\CertificateRequestApproved;
use App\Notifications\CertificateRequestRejected;
use App\Notifications\CertificateRequestSubmitted;
use App\Services\Documents\CertificateGeneratorService;
use App\Services\Documents\CertificateRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CertificateRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected User $admin;

    protected CertificateType $coe;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['code' => 'HR', 'name' => 'Human Resources']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'HR-STAFF', 'title' => 'HR Staff']);
        $this->employee = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ])->assignRole('super_admin');
        $this->admin = User::find($this->admin->id);

        $this->coe = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);
    }

    public function test_submit_persists_request_and_notifies_hr(): void
    {
        Notification::fake();

        $req = app(CertificateRequestService::class)->submit([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->coe->id,
            'purpose' => 'Bank loan',
        ]);

        $this->assertSame(CertificateRequestStatus::Pending, $req->status);
        Notification::assertSentTimes(CertificateRequestSubmitted::class, 1);
    }

    public function test_approve_and_issue_creates_certificate_and_flips_status(): void
    {
        Notification::fake();

        // Fake PDF generation so we don't need Chromium + views inside a unit test.
        $this->mock(CertificateGeneratorService::class, function ($m) {
            $m->shouldReceive('generate')->once()->andReturnUsing(fn ($cert) => $cert);
        });

        $svc = app(CertificateRequestService::class);
        $req = $svc->submit([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->coe->id,
            'purpose' => 'Personal record',
        ]);

        $cert = $svc->approveAndIssue($req, $this->admin, null, 'ok');

        $req->refresh();
        $this->assertSame(CertificateRequestStatus::Issued, $req->status);
        $this->assertSame($cert->id, $req->resulting_certificate_id);
        $this->assertSame($this->admin->id, $req->reviewed_by_id);
        $this->assertNotNull($req->reviewed_at);

        $this->assertInstanceOf(Certificate::class, $cert);
        $this->assertSame($this->employee->id, $cert->employee_id);
        $this->assertSame(CertificateIssuance::Draft, $cert->issuance_status);

        Notification::assertSentTo($this->employee, CertificateRequestApproved::class);
    }

    public function test_reject_flips_status_and_records_reason_and_notifies_employee(): void
    {
        Notification::fake();

        $svc = app(CertificateRequestService::class);
        $req = $svc->submit([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->coe->id,
            'purpose' => 'Duplicate',
        ]);

        $svc->reject($req, $this->admin, 'Duplicate — see prior request');
        $req->refresh();

        $this->assertSame(CertificateRequestStatus::Rejected, $req->status);
        $this->assertSame('Duplicate — see prior request', $req->review_notes);
        $this->assertNull($req->resulting_certificate_id);

        Notification::assertSentTo($this->employee, CertificateRequestRejected::class);
    }

    public function test_double_approve_is_rejected_with_an_exception(): void
    {
        Notification::fake();

        $this->mock(CertificateGeneratorService::class, function ($m) {
            $m->shouldReceive('generate')->once()->andReturnUsing(fn ($cert) => $cert);
        });

        $svc = app(CertificateRequestService::class);
        $req = $svc->submit([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->coe->id,
            'purpose' => 'Test',
        ]);

        $svc->approveAndIssue($req, $this->admin);

        $this->expectException(\RuntimeException::class);
        $svc->approveAndIssue($req->refresh(), $this->admin);
    }

    public function test_generate_failure_rolls_back_the_transaction(): void
    {
        Notification::fake();

        // Simulate the PDF renderer throwing — the certificate row must NOT
        // survive, and the request must stay pending.
        $this->mock(CertificateGeneratorService::class, function ($m) {
            $m->shouldReceive('generate')->once()->andThrow(new \RuntimeException('Browsershot blew up'));
        });

        $svc = app(CertificateRequestService::class);
        $req = $svc->submit([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->coe->id,
            'purpose' => 'Rollback drill',
        ]);

        $certsBefore = Certificate::count();

        try {
            $svc->approveAndIssue($req, $this->admin);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame($certsBefore, Certificate::count(), 'Certificate row must be rolled back');
        $req->refresh();
        $this->assertSame(CertificateRequestStatus::Pending, $req->status, 'Request must remain pending');
        $this->assertNull($req->resulting_certificate_id);
    }
}
