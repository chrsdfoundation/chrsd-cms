<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // official_letters: make letter_category_id nullable, switch FK to nullOnDelete
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('official_letters', function (Blueprint $table) {
                try { $table->dropForeign(['letter_category_id']); } catch (\Throwable) {}
                $table->unsignedBigInteger('letter_category_id')->nullable()->change();
                $table->foreign('letter_category_id')
                    ->references('id')->on('letter_categories')
                    ->nullOnDelete();
            });
        });

        // certificates: make certificate_type_id nullable, switch FK to nullOnDelete
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('certificates', function (Blueprint $table) {
                try { $table->dropForeign(['certificate_type_id']); } catch (\Throwable) {}
                $table->unsignedBigInteger('certificate_type_id')->nullable()->change();
                $table->foreign('certificate_type_id')
                    ->references('id')->on('certificate_types')
                    ->nullOnDelete();
            });
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('official_letters', function (Blueprint $table) {
                try { $table->dropForeign(['letter_category_id']); } catch (\Throwable) {}
                $table->unsignedBigInteger('letter_category_id')->nullable(false)->change();
                $table->foreign('letter_category_id')
                    ->references('id')->on('letter_categories')
                    ->restrictOnDelete();
            });
        });

        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('certificates', function (Blueprint $table) {
                try { $table->dropForeign(['certificate_type_id']); } catch (\Throwable) {}
                $table->unsignedBigInteger('certificate_type_id')->nullable(false)->change();
                $table->foreign('certificate_type_id')
                    ->references('id')->on('certificate_types')
                    ->restrictOnDelete();
            });
        });
    }
};
