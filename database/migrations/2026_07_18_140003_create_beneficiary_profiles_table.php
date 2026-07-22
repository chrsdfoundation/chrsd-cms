<?php

use App\Enums\BeneficiaryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beneficiary_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()
                ->constrained('people')->cascadeOnDelete();

            $table->string('program_ref')->nullable();
            $table->string('status', 16)
                ->default(BeneficiaryStatus::Enrolled->value)->index();
            $table->date('enrolled_on')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_profiles');
    }
};
