<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds:
 *   - authorized_signatory : free-text signatory caption printed under the
 *                            signature hairline, overriding the generic
 *                            "Authorized Signatory" fallback when set.
 *   - signature_version     : bumped whenever the signature media is
 *                            replaced, so printed/cached card URLs can be
 *                            cache-busted with ?v={signature_version}.
 *
 * Idempotent — safe to run against an existing DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('id_cards', 'authorized_signatory')) {
                $table->string('authorized_signatory', 128)->nullable()->after('signed_by_id');
            }
            if (! Schema::hasColumn('id_cards', 'signature_version')) {
                $table->unsignedInteger('signature_version')->default(1)->after('authorized_signatory');
            }
        });
    }

    public function down(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (Schema::hasColumn('id_cards', 'signature_version')) {
                $table->dropColumn('signature_version');
            }
            if (Schema::hasColumn('id_cards', 'authorized_signatory')) {
                $table->dropColumn('authorized_signatory');
            }
        });
    }
};
