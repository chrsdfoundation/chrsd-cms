<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('id_cards', 'signatory_designation')) {
                $table->string('signatory_designation', 128)->nullable()->after('authorized_signatory');
            }
        });
    }

    public function down(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (Schema::hasColumn('id_cards', 'signatory_designation')) {
                $table->dropColumn('signatory_designation');
            }
        });
    }
};
