<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->foreignId('person_id')
                ->constrained('people')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('type', 16)->index();
            $table->timestamp('occurred_at');
            $table->string('subject');
            $table->text('body')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_interactions');
    }
};
