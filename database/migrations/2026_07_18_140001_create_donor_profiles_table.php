<?php

use App\Enums\EngagementTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()
                ->constrained('people')->cascadeOnDelete();

            $table->string('engagement_tier', 16)
                ->default(EngagementTier::Cold->value)->index();
            $table->date('first_donated_at')->nullable();
            $table->string('preferred_method', 32)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_profiles');
    }
};
