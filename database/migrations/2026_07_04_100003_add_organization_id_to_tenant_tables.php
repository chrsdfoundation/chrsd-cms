<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nine tenant-owned tables get a nullable organization_id FK. Nullable so
     * the existing rows survive the migration; the follow-up backfill sets
     * them to the default org. A future migration can flip to NOT NULL once
     * the scope is fully enforced.
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
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('id')
                    ->constrained('organizations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
