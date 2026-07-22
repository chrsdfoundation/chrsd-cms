<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['certificates', 'official_letters'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('pdf_content_hash', 64)->nullable()
                    ->after('verification_hash')
                    ->comment('HMAC-SHA256 of the rendered PDF bytes, keyed to APP_KEY. Set on generate().');
                $table->index('pdf_content_hash');
            });
        }
    }

    public function down(): void
    {
        foreach (['certificates', 'official_letters'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropIndex([$t . '_pdf_content_hash_index']);
                $table->dropColumn('pdf_content_hash');
            });
        }
    }
};
