<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 32)->index();
            $table->date('occurred_on')->index();

            // JSON snapshots so we can render a diff even after the referenced
            // department/position rows are renamed or deleted later.
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_events');
    }
};
