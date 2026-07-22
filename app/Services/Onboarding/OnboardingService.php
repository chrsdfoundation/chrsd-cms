<?php

namespace App\Services\Onboarding;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OnboardingService
{
    /**
     * Provision a new tenant in one transaction.
     *
     * Any failure — duplicate email, missing role, DB error — rolls back the
     * entire onboarding so we never leave an org with no admin, or a user
     * dangling without a pivot row.
     *
     * @param  array{code:string,name:string,description?:?string}  $org
     * @param  array{name:string,email:string,password:string,role:string}  $admin
     * @param  ?array{department:string,position:string,first_name:string,last_name:string,employee_email:string}  $starter
     */
    public function run(array $org, array $admin, ?array $starter = null): array
    {
        return DB::transaction(function () use ($org, $admin, $starter) {
            $organization = Organization::create([
                'code' => strtoupper(trim($org['code'])),
                'name' => trim($org['name']),
                'description' => $org['description'] ?? null,
                'is_active' => true,
            ]);

            $role = Role::where('name', $admin['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user = User::create([
                'name' => $admin['name'],
                'email' => strtolower(trim($admin['email'])),
                'password' => Hash::make($admin['password']),
            ]);
            $user->assignRole($role);
            $user->organizations()->attach($organization->id, ['is_default' => true]);

            $result = [
                'organization' => $organization,
                'user' => $user->refresh(),
            ];

            if ($starter) {
                // Set the session so the BelongsToOrganization scope auto-stamps
                // organization_id on these child rows. Restore afterwards.
                $previous = session('current_organization_id');
                session(['current_organization_id' => $organization->id]);

                try {
                    $dept = Department::create([
                        'code' => 'ADMIN',
                        'name' => $starter['department'],
                    ]);
                    $pos = Position::create([
                        'department_id' => $dept->id,
                        'code' => 'LEAD',
                        'title' => $starter['position'],
                        'rank' => 10,
                    ]);
                    $emp = Employee::create([
                        'first_name' => $starter['first_name'],
                        'last_name' => $starter['last_name'],
                        'email' => strtolower(trim($starter['employee_email'])),
                        'department_id' => $dept->id,
                        'position_id' => $pos->id,
                        'hired_at' => now(),
                    ]);

                    $result['department'] = $dept;
                    $result['position'] = $pos;
                    $result['employee'] = $emp;
                } finally {
                    if ($previous) {
                        session(['current_organization_id' => $previous]);
                    } else {
                        session()->forget('current_organization_id');
                    }
                }
            }

            return $result;
        });
    }
}
