<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();
            $table->foreignId('person_id')
                ->constrained('people')->restrictOnDelete();
            $table->foreignId('campaign_id')->nullable()
                ->constrained('campaigns')->nullOnDelete();

            // Auto-linked when a cash donation is saved. Nullable because
            // in-kind donations don't produce receipts.
            $table->foreignId('money_receipt_id')->nullable()
                ->constrained('money_receipts')->nullOnDelete();

            $table->date('receipt_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('BDT');

            // Cash / bKash / Nagad / Rocket / bank_transfer / cheque / card — null when in-kind.
            $table->string('payment_method', 32)->nullable();
            $table->string('reference_no')->nullable();

            $table->boolean('is_in_kind')->default(false)->index();
            $table->string('in_kind_description')->nullable();
            $table->decimal('in_kind_valuation', 15, 2)->nullable();

            $table->boolean('is_recurring')->default(false)->index();
            $table->string('recurring_cadence', 16)->nullable();

            // For recurring donations: each subsequent gift references the
            // original standing-order row so the series can be walked.
            $table->foreignId('parent_donation_id')->nullable()
                ->constrained('donations')->nullOnDelete();

            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('receipt_date');
            $table->index(['person_id', 'receipt_date']);
            $table->index(['campaign_id', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
