<?php

use App\Enums\IdCardIssuance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signed_by_id')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->string('designation', 128)->nullable();
            $table->string('program_name', 128)->nullable();
            $table->string('blood_group', 8)->nullable();
            $table->string('nationality', 64)->nullable();

            $table->date('valid_from');
            $table->date('valid_until');

            // Front + back are separately signed since Filament stores them as
            // two distinct media items in the `rendered` collection.
            $table->string('pdf_content_hash_front', 64)->nullable()->index();
            $table->string('pdf_content_hash_back', 64)->nullable()->index();

            $table->string('issuance_status', 32)->default(IdCardIssuance::Draft->value)->index();

            $table->verificationColumns();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_cards');
    }
};
