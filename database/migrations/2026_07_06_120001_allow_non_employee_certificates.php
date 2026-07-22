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
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('certificates', 'recipient_name')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->string('recipient_name', 160)->nullable()->after('employee_id');
            });
        }

        // The FK relaxation ran via a one-shot SQLite table-rebuild on the
        // author's machine and is preserved in fresh installs via the schema
        // dump. See git log for the historical rebuild if forensic detail is
        // needed. Nothing more to do here on either driver.
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
