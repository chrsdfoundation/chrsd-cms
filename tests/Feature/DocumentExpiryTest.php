<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\Position;
use App\Models\User;
use App\Notifications\DocumentExpired;
use App\Notifications\DocumentExpiring;
use App\Services\Documents\ExpiryScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected CertificateType $certType;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Staff']);
        $this->employee = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane@example.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $this->certType = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);
    }

    protected function makeCert(?string $validUntil, ?string $notifiedAt = null, ?string $status = null): Certificate
    {
        $cert = Certificate::create([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->certType->id,
            'valid_until' => $validUntil,
        ]);

        if ($notifiedAt || $status) {
            $cert->forceFill(array_filter([
                'expiry_notified_at' => $notifiedAt,
                'status' => $status,
            ]))->save();
            $cert->refresh();
        }

        return $cert;
    }

    protected function makeIdCard(?string $validUntil): IdCard
    {
        return IdCard::create([
            'employee_id' => $this->employee->id,
            'designation' => 'Staff',
            'valid_from' => now()->toDateString(),
            'valid_until' => $validUntil,
        ]);
    }

    public function test_certificate_within_30_days_gets_soon_notice_and_stamp(): void
    {
        Notification::fake();
        $cert = $this->makeCert(now()->addDays(25)->toDateString());
        $this->assertNull($cert->expiry_notified_at);

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(1, $stats['expiring']);
        $this->assertSame(0, $stats['expired']);

        $cert->refresh();
        $this->assertNotNull($cert->expiry_notified_at);
        Notification::assertSentTo($this->employee, DocumentExpiring::class);
    }

    public function test_certificate_outside_window_is_ignored(): void
    {
        Notification::fake();
        $cert = $this->makeCert(now()->addDays(90)->toDateString());

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(0, $stats['expiring']);
        $cert->refresh();
        $this->assertNull($cert->expiry_notified_at);
        Notification::assertNothingSent();
    }

    public function test_second_reminder_fires_after_renotify_window(): void
    {
        Notification::fake();
        // Notified 22 days ago; now only 5 days out → URGENT should re-fire
        $cert = $this->makeCert(
            now()->addDays(5)->toDateString(),
            now()->subDays(22)->toDateTimeString(),
        );

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(1, $stats['expiring']);
        Notification::assertSentTo($this->employee, DocumentExpiring::class, function ($n) {
            return $n->daysUntil >= 4 && $n->daysUntil <= 6;
        });
    }

    public function test_anti_spam_skips_recently_notified_docs(): void
    {
        Notification::fake();
        // Notified just 5 days ago (< 21 day cutoff) → skip
        $cert = $this->makeCert(
            now()->addDays(4)->toDateString(),
            now()->subDays(5)->toDateTimeString(),
        );

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(0, $stats['expiring']);
        Notification::assertNothingSent();
    }

    public function test_past_expiry_flips_status_and_notifies(): void
    {
        Notification::fake();
        $cert = $this->makeCert(now()->subDays(1)->toDateString());
        $this->assertSame(VerificationStatus::Valid, $cert->status);

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(1, $stats['expired']);
        $cert->refresh();
        $this->assertSame(VerificationStatus::Expired, $cert->status);
        Notification::assertSentTo($this->employee, DocumentExpired::class);
    }

    public function test_already_expired_docs_are_not_re_processed(): void
    {
        Notification::fake();
        $this->makeCert(now()->subDays(10)->toDateString(), null, VerificationStatus::Expired->value);

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(0, $stats['expired']);
        Notification::assertNothingSent();
    }

    public function test_null_valid_until_is_ignored(): void
    {
        Notification::fake();
        $this->makeCert(null);

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(0, $stats['expiring']);
        $this->assertSame(0, $stats['expired']);
    }

    public function test_id_cards_are_scanned_alongside_certificates(): void
    {
        Notification::fake();
        $this->makeCert(now()->addDays(10)->toDateString());
        $this->makeIdCard(now()->addDays(10)->toDateString());

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(2, $stats['expiring']);
    }

    public function test_hr_is_broadcast_on_expiry_when_role_exists(): void
    {
        Notification::fake();

        Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => 'web']);
        $hr = User::create([
            'name' => 'HR Manager',
            'email' => 'hr@example.test',
            'password' => bcrypt('secret'),
        ]);
        $hr->assignRole('hr_manager');

        $this->makeCert(now()->subDay()->toDateString());

        $stats = app(ExpiryScannerService::class)->run();

        $this->assertSame(1, $stats['expired']);
        $this->assertSame(1, $stats['notified_hr']);
        Notification::assertSentTo($hr, DocumentExpired::class);
    }

    public function test_artisan_command_runs_scan(): void
    {
        Notification::fake();
        $this->makeCert(now()->addDays(10)->toDateString());

        $this->artisan('documents:expiry-scan')
            ->expectsOutputToContain('expiring: 1')
            ->assertExitCode(0);
    }

    public function test_artisan_command_dry_run_makes_no_changes(): void
    {
        Notification::fake();
        $cert = $this->makeCert(now()->addDays(10)->toDateString());

        $this->artisan('documents:expiry-scan', ['--dry-run' => true])
            ->assertExitCode(0);

        $cert->refresh();
        $this->assertNull($cert->expiry_notified_at);
        Notification::assertNothingSent();
    }
}
