<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed the real organisation departments carried over from the live CMS.
     * Two passes: upsert the rows, then resolve parent links by code (a
     * self-referencing parent in the source data is treated as no parent).
     * organization_id is stamped so the tenant-scoped panel can see them.
     */
    public function run(): void
    {
        $rows = require __DIR__ . '/data/sync_departments.php';

        foreach ($rows as $r) {
            Department::updateOrCreate(
                ['code' => $r['code']],
                [
                    'name' => $r['name'],
                    'description' => $r['description'],
                    'is_active' => $r['is_active'],
                    'organization_id' => 1,
                ],
            );
        }

        foreach ($rows as $r) {
            if (! empty($r['parent_code']) && $r['parent_code'] !== $r['code']) {
                $parentId = Department::where('code', $r['parent_code'])->value('id');
                Department::where('code', $r['code'])->update(['parent_id' => $parentId]);
            }
        }
    }
}
