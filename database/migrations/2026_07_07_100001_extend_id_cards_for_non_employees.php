<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ID cards can now be issued to non-employees (Volunteer, Consultant,
 * Visitor …). Adds:
 *   - recipient_name      : free-text display name when employee_id is null
 *   - id_type_label       : short label printed under the org name on the
 *                           front (e.g. "Volunteer ID Card", "Visitor Pass").
 *                           Distinct from `id_card_type_id` which is the
 *                           categorical FK.
 *
 * Idempotent — safe to run against an existing DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('id_cards', 'recipient_name')) {
                $table->string('recipient_name', 160)->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('id_cards', 'id_type_label')) {
                $table->string('id_type_label', 64)->nullable()->after('id_card_type_id');
            }
        });

        // Relax employee_id to nullable on SQLite via table rebuild.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $info = collect(DB::select('PRAGMA table_info(id_cards)'))->keyBy('name');
            if (isset($info['employee_id']) && $info['employee_id']->notnull) {
                DB::statement('PRAGMA foreign_keys=OFF');
                DB::statement('CREATE TABLE id_cards__fix AS SELECT * FROM id_cards');

                $cols = collect(DB::select('PRAGMA table_info(id_cards__fix)'))
                    ->pluck('name')->all();

                Schema::drop('id_cards');
                Schema::create('id_cards', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnDelete();
                    $table->string('recipient_name', 160)->nullable();
                    $table->foreignId('id_card_type_id')->nullable()->constrained('id_card_types')->nullOnDelete();
                    $table->string('id_type_label', 64)->nullable();
                    $table->foreignId('signed_by_id')->nullable()->constrained('employees')->nullOnDelete();
                    $table->string('designation', 128)->nullable();
                    $table->string('program_name', 128)->nullable();
                    $table->string('blood_group', 8)->nullable();
                    $table->string('nationality', 64)->nullable();
                    $table->string('serial_number', 64)->nullable()->unique();
                    $table->string('verification_hash', 64)->nullable()->unique();
                    $table->string('status', 16)->default('valid')->index();
                    $table->timestamp('verified_at')->nullable();
                    $table->timestamp('revoked_at')->nullable();
                    $table->text('revocation_reason')->nullable();
                    $table->date('valid_from')->nullable();
                    $table->date('valid_until')->nullable();
                    $table->string('issuance_status', 32)->default('draft')->index();
                    $table->string('pdf_content_hash_front', 64)->nullable();
                    $table->string('pdf_content_hash_back', 64)->nullable();
                    $table->timestamp('expiry_notified_at')->nullable();
                    $table->foreignId('document_template_id')->nullable()
                        ->constrained('document_templates')->nullOnDelete();
                    $table->timestamps();
                    $table->softDeletes();
                });

                $list = implode(',', $cols);
                DB::statement("INSERT INTO id_cards ({$list}) SELECT {$list} FROM id_cards__fix");
                DB::statement('DROP TABLE id_cards__fix');
                DB::statement('PRAGMA foreign_keys=ON');
            }
        } else {
            DB::statement('ALTER TABLE id_cards MODIFY COLUMN employee_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('id_cards', function (Blueprint $table) {
            if (Schema::hasColumn('id_cards', 'id_type_label')) {
                $table->dropColumn('id_type_label');
            }
            if (Schema::hasColumn('id_cards', 'recipient_name')) {
                $table->dropColumn('recipient_name');
            }
        });
    }
};
