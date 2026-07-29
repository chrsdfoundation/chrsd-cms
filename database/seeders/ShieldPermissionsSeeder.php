<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ShieldPermissionsSeeder extends Seeder
{
    /**
     * Generate Filament Shield permissions from all resources.
     * Must run BEFORE RolePermissionSeeder so the role syncing has permissions to match.
     *
     * Idempotent — safe to run repeatedly (Shield regenerates but doesn't break existing perms).
     */
    public function run(): void
    {
        echo "   [~] Generating Filament Shield permissions...\n";

        Artisan::call('shield:generate', ['--force' => true]);

        echo "   [OK] Shield permissions generated/refreshed\n";
    }
}
