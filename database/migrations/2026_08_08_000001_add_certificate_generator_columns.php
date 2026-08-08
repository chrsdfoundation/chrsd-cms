<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Certificate numbering and verification
            if (!Schema::hasColumn('certificates', 'certificate_no')) {
                $table->string('certificate_no')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('certificates', 'verification_hash')) {
                $table->string('verification_hash', 64)->nullable()->unique()->indexed()->after('certificate_no');
            }

            // Content fields
            if (!Schema::hasColumn('certificates', 'certificate_title')) {
                $table->string('certificate_title')->default('Certificate of Achievement')->after('purpose');
            }
            if (!Schema::hasColumn('certificates', 'award_lead_in')) {
                $table->string('award_lead_in')->default('has successfully completed')->after('certificate_title');
            }
            if (!Schema::hasColumn('certificates', 'program_name')) {
                $table->string('program_name')->nullable()->after('award_lead_in');
            }

            // Revocation
            if (!Schema::hasColumn('certificates', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('valid_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_no',
                'verification_hash',
                'certificate_title',
                'award_lead_in',
                'program_name',
                'revoked_at',
            ]);
        });
    }
};
