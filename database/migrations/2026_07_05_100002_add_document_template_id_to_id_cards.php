<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            $table->foreignId('document_template_id')->nullable()
                ->constrained('document_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            $table->dropForeign(['document_template_id']);
            $table->dropColumn('document_template_id');
        });
    }
};
