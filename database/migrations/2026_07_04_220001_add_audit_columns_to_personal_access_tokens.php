<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->ipAddress('last_used_ip')->nullable()->after('last_used_at');
            $table->string('last_used_user_agent', 512)->nullable()->after('last_used_ip');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['last_used_ip', 'last_used_user_agent']);
        });
    }
};
