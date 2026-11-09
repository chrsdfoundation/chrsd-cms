<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive: the executive card's structured fields. The old columns (full_name,
 * phone, mobile, whatsapp, work_email, personal_email, linkedin, address, ...)
 * stay in place for the existing print route. Each card is backfilled from them,
 * so the new fields start from the same data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->string('person_name', 160)->nullable()->after('full_name');
            $table->string('person_title', 128)->nullable()->after('person_name');
            $table->json('phones')->nullable();
            $table->json('emails')->nullable();
            $table->json('address_lines')->nullable();
            $table->string('linkedin_handle', 100)->nullable();
            $table->string('qr_profile', 16)->default('compact');
            $table->string('template_key', 40)->default('executive-v1');
            $table->string('template_version', 16)->default('v1');
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_to_press_at')->nullable();
            $table->text('notes')->nullable();
            $table->index('status');
        });

        // Backfill from the old columns. Phones and emails keep the raw text in
        // "display" with no e164 yet; the form normalises them the next time the card is saved.
        DB::table('business_cards')->orderBy('id')->chunkById(200, function ($cards) {
            foreach ($cards as $card) {
                $phones = [];
                foreach (['phone' => 'office', 'mobile' => 'mobile', 'whatsapp' => 'whatsapp'] as $column => $type) {
                    if (filled($card->{$column})) {
                        $phones[] = ['type' => $type, 'number_e164' => null, 'display' => trim((string) $card->{$column})];
                    }
                }

                $emails = [];
                foreach (['work_email' => 'work', 'personal_email' => 'personal'] as $column => $type) {
                    if (filled($card->{$column})) {
                        $emails[] = ['type' => $type, 'address' => trim((string) $card->{$column})];
                    }
                }

                $addressLines = array_slice(array_values(array_filter(array_map('trim', preg_split('/\R|,\s*(?=\d)/', (string) $card->address) ?: []))), 0, 3);

                $handle = null;
                if (filled($card->linkedin)) {
                    $handle = preg_match('#/in/([A-Za-z0-9-]+)#', (string) $card->linkedin, $m)
                        ? $m[1]
                        : ltrim(trim((string) $card->linkedin), '@/');
                }

                DB::table('business_cards')->where('id', $card->id)->update([
                    'person_name' => $card->full_name,
                    'person_title' => $card->job_title,
                    'phones' => json_encode($phones),
                    'emails' => json_encode($emails),
                    'address_lines' => json_encode($addressLines),
                    'linkedin_handle' => $handle,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn([
                'person_name', 'person_title', 'phones', 'emails', 'address_lines', 'linkedin_handle',
                'qr_profile', 'template_key', 'template_version', 'status', 'approved_at',
                'sent_to_press_at', 'notes',
            ]);
        });
    }
};
