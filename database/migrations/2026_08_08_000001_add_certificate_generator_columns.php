<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'certificate_no')) {
                $table->string('certificate_no')->nullable()->unique();
            }
            if (!Schema::hasColumn('certificates', 'verification_hash')) {
                $table->string('verification_hash', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('certificates', 'certificate_title')) {
                $table->string('certificate_title')->default('Certificate of Achievement');
            }
            if (!Schema::hasColumn('certificates', 'award_lead_in')) {
                $table->string('award_lead_in')->default('has successfully completed');
            }
            if (!Schema::hasColumn('certificates', 'program_name')) {
                $table->string('program_name')->nullable();
            }
            if (!Schema::hasColumn('certificates', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable();
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
