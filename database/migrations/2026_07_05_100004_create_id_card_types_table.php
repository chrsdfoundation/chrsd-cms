<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('id_card_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_validity_months')->nullable()
                ->comment('Suggested validity in months (e.g. Volunteer=12, Employee=24, Visitor=1)');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::table('id_cards', function (Blueprint $table) {
            $table->foreignId('id_card_type_id')->nullable()
                ->constrained('id_card_types')->nullOnDelete()
                ->after('employee_id');
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->foreignId('id_card_type_id')->nullable()
                ->constrained('id_card_types')->nullOnDelete()
                ->after('letter_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropForeign(['id_card_type_id']);
            $table->dropColumn('id_card_type_id');
        });
        Schema::table('id_cards', function (Blueprint $table) {
            $table->dropForeign(['id_card_type_id']);
            $table->dropColumn('id_card_type_id');
        });
        Schema::dropIfExists('id_card_types');
    }
};
