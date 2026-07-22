<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the abandoned Letters domain module tables. The scaffolding under
 * app/Domain/Letters/ was never wired into the app; forward work continued
 * on the legacy OfficialLetter path. Removed together with the source.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('letter_settings');
        Schema::dropIfExists('reference_sequences');
        Schema::dropIfExists('letter_logs');
        Schema::dropIfExists('letter_files');
        Schema::dropIfExists('letter_signatories');
        Schema::dropIfExists('letter_versions');
        Schema::dropIfExists('letters');
        Schema::dropIfExists('letter_templates');
        Schema::dropIfExists('letter_module_departments');
    }

    public function down(): void
    {
        // Intentionally empty: the source for these tables has been deleted
        // along with the scaffolding, so a rollback cannot restore a working
        // module. If ever revived, start from fresh migrations.
    }
};
