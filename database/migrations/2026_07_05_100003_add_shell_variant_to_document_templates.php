<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('shell_variant', 64)->nullable()->after('orientation')
                ->comment('Optional shell variant, e.g. "course-completion". Null = default shell for the document type.');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('shell_variant');
        });
    }
};
