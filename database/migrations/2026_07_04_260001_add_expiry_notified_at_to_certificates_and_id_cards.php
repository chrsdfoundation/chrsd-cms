<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['certificates', 'id_cards'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->timestamp('expiry_notified_at')->nullable()
                    ->comment('Last time an expiry notice was sent for this document. Null = never notified.');
                $table->index(['valid_until', 'expiry_notified_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (['certificates', 'id_cards'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropIndex([$t . '_valid_until_expiry_notified_at_index']);
                $table->dropColumn('expiry_notified_at');
            });
        }
    }
};
