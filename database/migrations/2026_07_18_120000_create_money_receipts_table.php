<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations')->nullOnDelete();

            // Who logged the receipt (nullable so the row survives if the user
            // is soft-deleted later).
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->date('receipt_date');
            $table->string('payer_name');

            // Currency-safe: 15 digits total, 2 after decimal, plus a currency
            // code so BDT is not baked in silently.
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('BDT');

            $table->string('payment_method', 32);
            $table->string('reference_no')->nullable();      // cheque no. / txn id
            $table->text('purpose');
            $table->string('received_by')->nullable();        // name + designation of receiver

            // Stored verification URL the QR encodes. Denormalized so a scan
            // resolver never has to recompute the URL from scratch.
            $table->string('qr_code_uri', 512);

            // Adds serial_number (unique), verification_hash (unique), status,
            // verified_at, revoked_at, revocation_reason. Indexed by macro.
            $table->verificationColumns();

            $table->timestamps();
            $table->softDeletes();

            // Extra indexes for typical dashboard filters.
            $table->index('receipt_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_receipts');
    }
};
