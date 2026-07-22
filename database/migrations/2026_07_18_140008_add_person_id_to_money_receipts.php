<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('money_receipts', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('organization_id')
                ->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('money_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
