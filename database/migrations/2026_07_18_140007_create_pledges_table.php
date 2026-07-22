<?php

use App\Enums\PledgeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->foreignId('person_id')
                ->constrained('people')->restrictOnDelete();
            $table->foreignId('campaign_id')->nullable()
                ->constrained('campaigns')->nullOnDelete();

            $table->decimal('promised_amount', 15, 2);
            $table->string('currency', 3)->default('BDT');
            $table->decimal('fulfilled_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->string('status', 16)
                ->default(PledgeStatus::Open->value)->index();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('due_date');
            $table->index(['person_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledges');
    }
};
