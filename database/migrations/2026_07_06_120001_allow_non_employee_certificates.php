<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates can now be issued to people who don't have an Employee row.
 *
 * - `employee_id` becomes nullable — the FK is preserved for the common case,
 *   but external recipients (partner-org staff, board members, community
 *   volunteers) don't need an internal HR record.
 * - `recipient_name` is the free-text display name. When both employee_id and
 *   recipient_name are set, `recipient_name` wins (see CertificateGeneratorService).
 *
 * This migration is idempotent — safe to run against a database that already
 * has the columns / relaxed constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add recipient_name column for non-employee certificates
        if (! Schema::hasColumn('certificates', 'recipient_name')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->string('recipient_name', 160)->nullable();
            });
        }

        // Note: employee_id is now nullable from the create migration (2026_07_03_100005)
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'recipient_name')) {
                $table->dropColumn('recipient_name');
            }
        });
    }
};
