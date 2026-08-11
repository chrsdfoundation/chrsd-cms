<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed the real employees carried over from the live CMS. Department and
     * position are resolved by code so IDs stay portable; must run after
     * DepartmentSeeder + PositionSeeder. Keyed on email.
     *
     * The lookup uses withTrashed() so a previously soft-deleted employee is
     * revived and updated in place instead of triggering a fresh INSERT that
     * would violate the (soft-delete-unaware) unique index on employees.email.
     *
     * serial_number / verification_hash are deliberately NOT set — they are
     * observer-managed (App\Observers\VerifiableObserver), so each environment
     * generates its own valid serial and hash under its own APP_KEY.
     * Profile photos live in media (not git) and are applied separately.
     */
    public function run(): void
    {
        foreach (require __DIR__ . '/data/sync_employees.php' as $r) {
            $departmentId = $r['department_code']
                ? Department::where('code', $r['department_code'])->value('id')
                : null;
            $positionId = $r['position_code']
                ? Position::where('code', $r['position_code'])->value('id')
                : null;

            $attributes = [
                'first_name' => $r['first_name'],
                'middle_name' => $r['middle_name'],
                'last_name' => $r['last_name'],
                'suffix' => $r['suffix'],
                'mobile' => $r['mobile'],
                'gender' => $r['gender'],
                'date_of_birth' => $r['date_of_birth'],
                'civil_status' => $r['civil_status'],
                'nationality' => $r['nationality'],
                'address' => $r['address'],
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'employment_type' => $r['employment_type'],
                'employee_status' => $r['employee_status'],
                'hired_at' => $r['hired_at'],
                'ended_at' => $r['ended_at'],
                'organization_id' => $r['organization_id'],
            ];

            $employee = Employee::withTrashed()
                ->updateOrCreate(['email' => $r['email']], $attributes);

            if ($employee->trashed()) {
                $employee->restore();
            }
        }
    }
}
