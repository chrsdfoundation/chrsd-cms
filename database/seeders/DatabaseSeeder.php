<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DocumentLookupSeeder::class,
            AuthorSeeder::class,
            DocumentTemplateSeeder::class,
            CrmDemoSeeder::class,
        ]);

        $org = Organization::firstOrCreate(
            ['code' => 'CHRSD'],
            ['name' => 'CHRSD (default)', 'description' => 'Default organization'],
        );

        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@chrsd.org'],
            ['name' => 'System Administrator', 'password' => Hash::make('password')],
        );
        $admin->syncRoles([Role::where('name', 'super_admin')->firstOrFail()]);
        $admin->organizations()->syncWithoutDetaching([$org->id => ['is_default' => true]]);

        // Demo portal user — linked to a demo employee so the /portal panel has something to show
        $hr = Department::firstOrCreate(['code' => 'HR'], ['name' => 'Human Resources']);
        $pos = Position::firstOrCreate(
            ['department_id' => $hr->id, 'code' => 'HR-STAFF'],
            ['title' => 'HR Staff'],
        );
        $emp = Employee::firstOrCreate(
            ['email' => 'employee@chrsd.org'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Employee',
                'department_id' => $hr->id,
                'position_id' => $pos->id,
                'hired_at' => now()->subYear(),
            ],
        );
        $portalUser = User::updateOrCreate(
            ['email' => 'employee@chrsd.org'],
            [
                'employee_id' => $emp->id,
                'name' => $emp->full_name,
                'password' => Hash::make('password'),
            ],
        );
        $portalUser->organizations()->syncWithoutDetaching([$org->id => ['is_default' => true]]);
    }
}
