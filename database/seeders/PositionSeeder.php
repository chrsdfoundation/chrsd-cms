<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed the real positions carried over from the live CMS. Department is
     * resolved by code so IDs stay portable across environments; must run
     * after DepartmentSeeder. Keyed on department + code to match the app's
     * existing uniqueness (see DatabaseSeeder's demo position).
     */
    public function run(): void
    {
        foreach (require __DIR__ . '/data/sync_positions.php' as $r) {
            $departmentId = Department::where('code', $r['department_code'])->value('id');

            if ($departmentId === null) {
                continue;
            }

            Position::updateOrCreate(
                ['department_id' => $departmentId, 'code' => $r['code']],
                [
                    'title' => $r['title'],
                    'rank' => $r['rank'],
                    'salary_grade' => $r['salary_grade'],
                    'is_active' => $r['is_active'],
                    'organization_id' => 1,
                ],
            );
        }
    }
}
