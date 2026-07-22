<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Panel $adminPanel;
    protected Panel $portalPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPanel  = filament()->getPanel('admin');
        $this->portalPanel = filament()->getPanel('portal');

        foreach (['super_admin', 'hr_manager', 'hr_staff', 'viewer'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $roles = [], bool $withEmployee = false): User
    {
        static $seq = 0;
        $seq++;

        $employeeId = null;
        if ($withEmployee) {
            $dept = Department::firstOrCreate(['code' => 'HR'], ['name' => 'Human Resources']);
            $pos  = Position::firstOrCreate(['department_id' => $dept->id, 'code' => 'HR-STAFF'], ['title' => 'HR Staff']);
            $emp  = Employee::create([
                'first_name' => 'User', 'last_name' => (string) $seq,
                'email' => "user{$seq}@example.com",
                'department_id' => $dept->id, 'position_id' => $pos->id,
            ]);
            $employeeId = $emp->id;
        }

        $user = User::create([
            'name' => 'User ' . $seq,
            'email' => "u{$seq}@example.com",
            'password' => Hash::make('secret'),
            'employee_id' => $employeeId,
        ]);
        if ($roles) {
            $user->syncRoles($roles);
        }
        return $user->refresh();
    }

    public function test_admin_role_can_access_admin_panel(): void
    {
        $u = $this->makeUser(['super_admin']);
        $this->assertTrue($u->canAccessPanel($this->adminPanel));
    }

    public function test_admin_without_employee_link_cannot_access_portal(): void
    {
        $u = $this->makeUser(['super_admin'], withEmployee: false);
        $this->assertFalse($u->canAccessPanel($this->portalPanel));
    }

    public function test_employee_without_admin_role_cannot_access_admin(): void
    {
        $u = $this->makeUser(withEmployee: true);
        $this->assertFalse($u->canAccessPanel($this->adminPanel));
    }

    public function test_employee_with_link_can_access_portal(): void
    {
        $u = $this->makeUser(withEmployee: true);
        $this->assertTrue($u->canAccessPanel($this->portalPanel));
    }

    public function test_hr_staff_can_access_admin_panel(): void
    {
        $u = $this->makeUser(['hr_staff']);
        $this->assertTrue($u->canAccessPanel($this->adminPanel));
    }

    public function test_orphaned_user_no_roles_no_employee_can_access_nothing(): void
    {
        $u = $this->makeUser();
        $this->assertFalse($u->canAccessPanel($this->adminPanel));
        $this->assertFalse($u->canAccessPanel($this->portalPanel));
    }
}
