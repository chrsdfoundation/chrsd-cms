<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->string('name');
            $table->string('document_type', 32)->index(); // 'certificate' | 'letter'
            $table->foreignId('certificate_type_id')->nullable()
                ->constrained('certificate_types')->nullOnDelete();
            $table->foreignId('letter_category_id')->nullable()
                ->constrained('letter_categories')->nullOnDelete();
            $table->longText('body_markdown');
            $table->string('orientation', 16)->default('portrait');
            $table->boolean('is_default')->default(false)->index();
            $table->json('sample_context')->nullable();
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Bind a specific template to an issued document. Nullable — legacy
        // documents and code-driven Blade renders leave this NULL.
        foreach (['certificates', 'official_letters'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('document_template_id')->nullable()
                    ->constrained('document_templates')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['certificates', 'official_letters'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign([$t . '_document_template_id_foreign']);
                $table->dropColumn('document_template_id');
            });
        }
        Schema::dropIfExists('document_templates');
    }
};
