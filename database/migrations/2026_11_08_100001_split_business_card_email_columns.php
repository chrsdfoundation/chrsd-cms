<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->renameColumn('email', 'work_email');
        });

        Schema::table('business_cards', function (Blueprint $table) {
            $table->string('personal_email', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->dropColumn('personal_email');
        });

        Schema::table('business_cards', function (Blueprint $table) {
            $table->renameColumn('work_email', 'email');
        });
    }
};
