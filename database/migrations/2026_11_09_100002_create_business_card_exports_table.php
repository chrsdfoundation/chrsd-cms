<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for every executive card export. Each row records what was made,
 * from which spec and engine, the preflight result, and any QR acknowledgement.
 * Files are on the private disk: they hold personal contact data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_card_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_card_id')->constrained('business_cards')->cascadeOnDelete();
            $table->string('kind', 20);                 // artwork | foil_plate
            $table->string('file_path', 255);
            $table->char('sha256', 64);
            $table->char('content_hash', 64);           // hash of the card data the file was made from
            $table->string('engine_name', 60);
            $table->string('engine_version', 20);
            $table->string('spec_version', 40);
            $table->string('qr_profile', 16);
            $table->decimal('qr_module_mm', 6, 3)->nullable();
            $table->boolean('qr_acknowledged')->default(false);
            $table->json('preflight_report');
            $table->json('issues');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['business_card_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_card_exports');
    }
};
