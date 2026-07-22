<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();

            $table->string('full_name');
            $table->string('organization_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('tax_id', 64)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'full_name']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
