<?php

use App\Enums\VolunteerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('volunteer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()
                ->constrained('people')->cascadeOnDelete();

            $table->string('status', 16)
                ->default(VolunteerStatus::Prospective->value)->index();
            $table->json('skills')->nullable();
            $table->json('availability')->nullable();
            $table->date('joined_on')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_profiles');
    }
};
