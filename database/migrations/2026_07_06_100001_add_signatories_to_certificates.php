<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-signatory support for certificates. Each certificate carries an
 * independent {name, designation} pair for both signatories; the actual
 * signature PNGs are attached via Spatie MediaLibrary collections
 * ('signature_1' / 'signature_2') on the Certificate model.
 *
 * `signed_by_id` is left in place — that FK still points at the primary
 * signing employee (the Executive Director on most certificates), which
 * the verification hash and audit trail reference. The new columns are
 * for CERTIFICATE-DISPLAY only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('signatory_1_name', 128)->nullable()->after('signed_by_id');
            $table->string('signatory_1_title', 128)->nullable()->after('signatory_1_name');
            $table->string('signatory_2_name', 128)->nullable()->after('signatory_1_title');
            $table->string('signatory_2_title', 128)->nullable()->after('signatory_2_name');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'signatory_1_name', 'signatory_1_title',
                'signatory_2_name', 'signatory_2_title',
            ]);
        });
    }
};
