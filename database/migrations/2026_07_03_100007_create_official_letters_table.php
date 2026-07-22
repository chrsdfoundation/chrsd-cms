<?php

use App\Enums\OfficialLetterStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('signed_by_id')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->string('subject');
            $table->longText('body');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_title')->nullable();
            $table->text('recipient_address')->nullable();

            $table->string('letter_status', 32)->default(OfficialLetterStatus::Draft->value)->index();
            $table->date('dated_on')->nullable();
            $table->date('released_on')->nullable();

            $table->verificationColumns();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_letters');
    }
};
