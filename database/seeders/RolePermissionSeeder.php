<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Base roles for CHRSD CMS. Call AFTER shield:generate has populated the
     * permissions table. Idempotent — safe to run repeatedly.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // super_admin exists and bypasses Gate via Shield's config (see config/filament-shield.php).
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->syncRole('hr_manager', $this->hrManagerPermissions());
        $this->syncRole('hr_staff', $this->hrStaffPermissions());
        $this->syncRole('viewer', $this->viewerPermissions());
    }

    protected function syncRole(string $name, array $patterns): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        $all = Permission::query()->where('guard_name', 'web')->pluck('name');
        $matched = $all->filter(fn (string $perm) => $this->matchesAny($perm, $patterns))->values();

        $role->syncPermissions($matched->all());
    }

    protected function matchesAny(string $perm, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (fnmatch($p, $perm)) {
                return true;
            }
        }
        return false;
    }

    /** HR Manager: full CRUD on all HR + document resources, no user/role admin. */
    protected function hrManagerPermissions(): array
    {
        return [
            '*_department', '*_any_department',
            '*_position', '*_any_position',
            '*_employee', '*_any_employee',
            '*_certificate_type', '*_any_certificate_type',
            '*_certificate', '*_any_certificate',
            '*_letter_category', '*_any_letter_category',
            '*_official_letter', '*_any_official_letter',
            'widget_*',
        ];
    }

    /** HR Staff: create/edit employees and certificates; read-only on lookups. */
    protected function hrStaffPermissions(): array
    {
        return [
            'view_employee', 'view_any_employee', 'create_employee', 'update_employee',
            'view_department', 'view_any_department',
            'view_position', 'view_any_position',
            'view_certificate_type', 'view_any_certificate_type',
            'view_letter_category', 'view_any_letter_category',
            'view_certificate', 'view_any_certificate', 'create_certificate', 'update_certificate',
            'view_official_letter', 'view_any_official_letter', 'create_official_letter', 'update_official_letter',
            'widget_*',
        ];
    }

    /** Viewer: read-only across all business resources. */
    protected function viewerPermissions(): array
    {
        return [
            'view_department', 'view_any_department',
            'view_position', 'view_any_position',
            'view_employee', 'view_any_employee',
            'view_certificate_type', 'view_any_certificate_type',
            'view_certificate', 'view_any_certificate',
            'view_letter_category', 'view_any_letter_category',
            'view_official_letter', 'view_any_official_letter',
            'widget_*',
        ];
    }
}
