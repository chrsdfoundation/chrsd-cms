<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Idempotent data migration:
     *   1. Ensure a default "CHRSD" organization row exists.
     *   2. Stamp every existing row in the tenant tables with that org id
     *      (only rows where organization_id is currently NULL).
     */
    protected array $tables = [
        'departments',
        'positions',
        'employees',
        'certificate_types',
        'letter_categories',
        'certificates',
        'official_letters',
        'employment_events',
        'certificate_requests',
    ];

    public function up(): void
    {
        $now = now();

        $orgId = DB::table('organizations')->where('code', 'CHRSD')->value('id');

        if (! $orgId) {
            $orgId = DB::table('organizations')->insertGetId([
                'code' => 'CHRSD',
                'name' => 'CHRSD (default)',
                'description' => 'Default organization created by the multi-tenancy prep migration.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->tables as $t) {
            DB::table($t)->whereNull('organization_id')->update(['organization_id' => $orgId]);
        }
    }

    public function down(): void
    {
        // No-op: we never remove the default org on rollback — dropping the
        // organizations table (previous migration) handles that.
    }
};
