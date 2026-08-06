<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'html_snapshot')) {
                $table->longText('html_snapshot')->nullable()->after('pdf_content_hash')
                    ->comment('HTML snapshot for verification audit trail');
            }
        });

        Schema::table('official_letters', function (Blueprint $table) {
            if (!Schema::hasColumn('official_letters', 'html_snapshot')) {
                $table->longText('html_snapshot')->nullable()->after('pdf_content_hash')
                    ->comment('HTML snapshot for verification audit trail');
            }
        });

        Schema::table('id_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('id_cards', 'html_snapshot')) {
                $table->longText('html_snapshot')->nullable()->after('pdf_content_hash_back')
                    ->comment('HTML snapshot for verification audit trail');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('html_snapshot');
        });

        Schema::table('official_letters', function (Blueprint $table) {
            $table->dropColumn('html_snapshot');
        });

        Schema::table('id_cards', function (Blueprint $table) {
            $table->dropColumn('html_snapshot');
        });
    }
};
