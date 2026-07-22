<?php

use App\Enums\CertificateIssuance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by_id')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('signed_by_id')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->string('purpose')->nullable();
            $table->json('payload')->nullable();
            $table->string('issuance_status', 32)->default(CertificateIssuance::Draft->value)->index();

            $table->date('issued_on')->nullable();
            $table->date('valid_until')->nullable();

            $table->verificationColumns();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
