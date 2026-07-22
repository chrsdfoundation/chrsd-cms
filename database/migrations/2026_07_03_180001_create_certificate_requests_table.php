<?php

use App\Enums\CertificateRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_type_id')->constrained()->restrictOnDelete();

            $table->string('purpose', 255);
            $table->text('notes')->nullable();

            $table->string('status', 32)
                ->default(CertificateRequestStatus::Pending->value)
                ->index();

            $table->foreignId('reviewed_by_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->foreignId('resulting_certificate_id')->nullable()
                ->constrained('certificates')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
