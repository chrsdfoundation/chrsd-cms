<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'hr_manager', 'guard_name' => 'web']);
    }

    public function test_creates_organization_admin_user_and_pivot_row_in_one_go(): void
    {
        $result = app(OnboardingService::class)->run(
            org: ['code' => 'acme', 'name' => 'ACME Corp'],
            admin: ['name' => 'Ada', 'email' => 'ada@acme.test', 'password' => 'secret123', 'role' => 'super_admin'],
        );

        $this->assertInstanceOf(Organization::class, $result['organization']);
        $this->assertSame('ACME', $result['organization']->code);
        $this->assertTrue($result['organization']->is_active);

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertSame('ada@acme.test', $result['user']->email);
        $this->assertTrue($result['user']->hasRole('super_admin'));

        $this->assertTrue(
            $result['user']->organizations()->where('organizations.id', $result['organization']->id)->exists(),
            'User must be attached to the new organization via the pivot',
        );

        $isDefault = $result['user']->organizations()
            ->wherePivot('is_default', true)
            ->where('organizations.id', $result['organization']->id)
            ->exists();
        $this->assertTrue($isDefault, 'The pivot row must be marked as the user\'s default org');
    }

    public function test_starter_hr_data_is_created_and_stamped_with_the_new_org(): void
    {
        $result = app(OnboardingService::class)->run(
            org: ['code' => 'BRAVO', 'name' => 'Bravo Ltd'],
            admin: ['name' => 'Grace', 'email' => 'g@bravo.test', 'password' => 'secret123', 'role' => 'hr_manager'],
            starter: [
                'department' => 'Operations',
                'position' => 'COO',
                'first_name' => 'Chief',
                'last_name' => 'Operator',
                'employee_email' => 'coo@bravo.test',
            ],
        );

        $this->assertArrayHasKey('department', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertArrayHasKey('employee', $result);

        $orgId = $result['organization']->id;
        $this->assertSame($orgId, $result['department']->organization_id);
        $this->assertSame($orgId, $result['position']->organization_id);
        $this->assertSame($orgId, $result['employee']->organization_id);

        $emp = Employee::query()->acrossOrganizations()->find($result['employee']->id);
        $this->assertMatchesRegularExpression('/^EMP-\d{4}-\d{6}$/', $emp->serial_number);
    }

    public function test_duplicate_email_rolls_back_the_whole_transaction(): void
    {
        // Seed a user with the email we're about to try to onboard with.
        User::create(['name' => 'Old', 'email' => 'dup@test.test', 'password' => bcrypt('x')]);

        $orgsBefore = Organization::count();

        try {
            app(OnboardingService::class)->run(
                org: ['code' => 'FAILTEST', 'name' => 'Should Not Exist'],
                admin: ['name' => 'New', 'email' => 'dup@test.test', 'password' => 'secret123', 'role' => 'super_admin'],
            );
            $this->fail('Expected duplicate-email exception was not thrown');
        } catch (\Throwable) {
            // expected
        }

        $this->assertSame($orgsBefore, Organization::count(),
            'Organization row must not survive a failed transaction');
        $this->assertNull(Organization::where('code', 'FAILTEST')->first());
    }

    public function test_session_current_organization_is_preserved_across_the_onboarding_call(): void
    {
        // Simulate an already-active session (e.g. super_admin operating within org A)
        $orgA = Organization::create(['code' => 'ORGA', 'name' => 'Org A']);
        session(['current_organization_id' => $orgA->id]);

        app(OnboardingService::class)->run(
            org: ['code' => 'NEW', 'name' => 'New Tenant'],
            admin: ['name' => 'N', 'email' => 'n@new.test', 'password' => 'secret123', 'role' => 'super_admin'],
            starter: [
                'department' => 'Admin', 'position' => 'MD',
                'first_name' => 'M', 'last_name' => 'D',
                'employee_email' => 'md@new.test',
            ],
        );

        $this->assertSame($orgA->id, session('current_organization_id'),
            'Session current-org must be restored after onboarding a different tenant');
    }
}
