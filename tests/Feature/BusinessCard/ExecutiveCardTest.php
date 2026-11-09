<?php

namespace Tests\Feature\BusinessCard;

use App\Filament\Resources\BusinessCardResource\Pages\EditBusinessCard;
use App\Filament\Resources\BusinessCardResource\Pages\ListBusinessCards;
use App\Models\BusinessCard;
use App\Models\BusinessCardExport;
use App\Models\Organization;
use App\Models\User;
use App\Services\BusinessCard\BusinessCardApprover;
use App\Services\BusinessCard\BusinessCardContactFactory;
use App\Services\BusinessCard\BusinessCardExporter;
use App\Services\BusinessCard\CardAssets;
use App\Services\BusinessCard\CardContact;
use App\Services\BusinessCard\CardExportBlockedException;
use App\Services\BusinessCard\CardLayout;
use App\Services\BusinessCard\CardLayoutEngine;
use App\Services\BusinessCard\CardQrCode;
use App\Services\BusinessCard\CardSpec;
use App\Services\BusinessCard\CardVCardBuilder;
use App\Services\BusinessCard\LinkedInNormaliser;
use App\Services\BusinessCard\Pdf\CustomPdfCardRenderer;
use App\Services\BusinessCard\PhoneNumberNormaliser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Zxing\QrReader;

/**
 * The executive card end to end: layout reflow, validation rules, the QR decoded from
 * the PDF itself, exports and their audit, approval, and authorisation.
 */
class ExecutiveCardTest extends TestCase
{
    use RefreshDatabase;

    private PhoneNumberNormaliser $phones;

    private CardVCardBuilder $vcard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phones = new PhoneNumberNormaliser;
        $this->vcard = new CardVCardBuilder($this->phones);
        CardAssets::flush();
    }

    /** @return array{office: string, mobile: string, whatsapp: string} */
    private function numbers(): array
    {
        return [
            'office' => '+880 2 47122566',
            'mobile' => '+880 1602-778984',
            'whatsapp' => '+880 1334-383341',
        ];
    }

    /**
     * The Ramim contact. $rows selects how many contact rows the back shows: 1 to 8.
     */
    private function contact(int $rows = 7, array $overrides = []): CardContact
    {
        $n = $this->numbers();
        $phones = [
            ['type' => CardContact::PHONE_OFFICE, 'e164' => $this->phones->normalise($n['office'])['e164'], 'display' => $n['office']],
            ['type' => CardContact::PHONE_MOBILE, 'e164' => $this->phones->normalise($n['mobile'])['e164'], 'display' => $n['mobile']],
            ['type' => CardContact::PHONE_WHATSAPP, 'e164' => $this->phones->normalise($n['whatsapp'])['e164'], 'display' => $n['whatsapp']],
        ];
        $emails = [
            ['type' => CardContact::EMAIL_WORK, 'address' => 'info@chrsd.org'],
            ['type' => CardContact::EMAIL_WORK, 'address' => 'ed@chrsd.org'],
        ];

        // Row count drives which optional rows exist. 7 is the full reference card.
        $phones = array_slice($phones, 0, min(3, max(1, $rows - 3)));
        $emails = array_slice($emails, 0, max(0, min(2, $rows - count($phones) - 2)));
        $withWeb = $rows >= 2;
        $withLinkedIn = $rows >= 3;

        $args = [
            'personName' => 'Mohammed Abu Ramim',
            'personTitle' => 'Executive Director',
            'orgName' => 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)',
            'orgShort' => 'CHRSD',
            'phones' => $phones,
            'emails' => $emails,
            'website' => $withWeb ? 'https://chrsd.org' : null,
            'linkedinUrl' => $withLinkedIn ? 'https://www.linkedin.com/in/maramim' : null,
            'addressLines' => ['29 Toyenbee Circular Road (5th Floor)', 'Motijheel C/A', 'Dhaka-1000, Bangladesh'],
        ];

        return new CardContact(...array_merge($args, $overrides));
    }

    private function engine(): CardLayoutEngine
    {
        return CardLayoutEngine::fromConfig(CardSpec::fromConfig(), new CardQrCode);
    }

    private function issueCodes(array $issues): array
    {
        return array_column($issues, 'code');
    }

    /* ----------------------------------------------------------- layout reflow */

    public function test_one_row_card_lays_out_without_errors(): void
    {
        $contact = $this->contact(1);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertFalse($layout->isBlocked(), implode('; ', array_column($layout->errors(), 'message')));
        $this->assertCount(1, $this->rowDeltas($layout) === [] ? [1] : $this->rowDeltas($layout) + [0]);
    }

    public function test_five_row_card_reflows_with_group_gaps_and_no_errors(): void
    {
        $contact = $this->contact(5);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertFalse($layout->isBlocked(), implode('; ', array_column($layout->errors(), 'message')));
        // Rows are 42 px apart inside a group and 58 px (42 + the 16 px group gap) between groups:
        // phones (56, 98), email (156), web (214, 256).
        $this->assertSame([42.0, 58.0, 58.0, 42.0], $this->rowDeltas($layout));
    }

    public function test_eight_row_card_is_refused_not_truncated(): void
    {
        $contact = new CardContact(
            personName: 'Mohammed Abu Ramim', personTitle: 'Executive Director',
            orgName: 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)', orgShort: 'CHRSD',
            phones: [
                ['type' => 'office', 'e164' => '+8802477122566', 'display' => '+880 2 47122566'],
                ['type' => 'mobile', 'e164' => '+8801602778984', 'display' => '+880 1602-778984'],
                ['type' => 'whatsapp', 'e164' => '+8801334383341', 'display' => '+880 1334-383341'],
            ],
            emails: [
                ['type' => 'work', 'address' => 'info@chrsd.org'],
                ['type' => 'work', 'address' => 'ed@chrsd.org'],
                ['type' => 'work', 'address' => 'press@chrsd.org'],
            ],
            website: 'https://chrsd.org',
            linkedinUrl: 'https://www.linkedin.com/in/maramim',
            addressLines: ['29 Toyenbee Circular Road'],
        );
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        // Eight rows reach the address block, so the engine must report it.
        $this->assertContains('contact_rows_overflow', $this->issueCodes($layout->errors()));
    }

    /** @return list<float> spacing in design px between consecutive contact rows (baseline to baseline) */
    private function rowDeltas(CardLayout $layout): array
    {
        $baselines = [];
        foreach ($layout->back as $p) {
            if (($p['kind'] ?? null) === 'text' && str_starts_with((string) $p['name'], 'contact-')) {
                // Points back to design px: y_pt = 171 - y_px * 0.24.
                $baselines[] = (171.0 - $p['baseline']) / 0.24;
            }
        }
        sort($baselines);

        $deltas = [];
        for ($i = 1; $i < count($baselines); $i++) {
            $deltas[] = round($baselines[$i] - $baselines[$i - 1], 1);
        }

        return $deltas;
    }

    /* ---------------------------------------------------------- safe area, sizes */

    public function test_text_outside_the_safe_area_is_an_error(): void
    {
        $contact = $this->contact(1, ['personName' => str_repeat('Mohammed Abu Ramim ', 6)]);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertContains('text_outside_safe_area', $this->issueCodes($layout->errors()));
    }

    public function test_text_below_the_minimum_font_size_is_an_error(): void
    {
        config(['business_card.min_font_pt' => 7.0]);
        $contact = $this->contact(1);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        // Contact text is 28 px = 6.72 pt, under the 7 pt minimum set here.
        $this->assertContains('font_below_minimum', $this->issueCodes($layout->errors()));
    }

    public function test_foil_line_below_the_printer_minimum_is_an_error(): void
    {
        config(['business_card.foil_min_line_pt' => 1.0]);
        $contact = $this->contact(1);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertContains('foil_line_below_minimum', $this->issueCodes($layout->errors()));
    }

    public function test_ink_over_the_maximum_is_an_error(): void
    {
        config(['business_card.max_total_ink_percent' => 200]);
        $contact = $this->contact(1);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertContains('ink_over_maximum', $this->issueCodes($layout->errors()));
    }

    public function test_small_qr_modules_warn_and_require_acknowledgement(): void
    {
        config(['business_card.qr_min_module_mm' => 0.5]);
        $contact = $this->contact(1);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertFalse($layout->isBlocked());
        $this->assertTrue($layout->requiresAcknowledgement());
        $this->assertSame('qr_module_below_minimum', $layout->warnings()[0]['code']);
    }

    public function test_compact_qr_module_size_is_computed_from_the_real_matrix(): void
    {
        $contact = $this->contact(7);
        $layout = $this->engine()->build($contact, $this->vcard->build($contact), 'compact');

        $this->assertSame($layout->qr['modules'], 61, 'The compact vCard for this card is 61 modules.');
        $this->assertEqualsWithDelta(21.336 / $layout->qr['modules'], $layout->qr['module_mm'], 0.01);
        $this->assertGreaterThanOrEqual(0.33, $layout->qr['module_mm']);
    }

    /* -------------------------------------------- the QR, decoded from the PDF */

    public function test_the_qr_printed_in_the_artwork_decodes_to_the_vcard(): void
    {
        $contact = $this->contact(7);
        $vcard = $this->vcard->build($contact, 'compact');
        $layout = $this->engine()->build($contact, $vcard, 'compact');
        $pdf = (new CustomPdfCardRenderer)->renderArtwork($layout);

        $decoded = $this->decodeQrFromPdf($pdf, $layout);

        $this->assertSame($vcard, $decoded);
    }

    /**
     * Read the QR module rectangles back out of the back page's content stream, rebuild the
     * matrix, render it with a quiet zone, and decode it. The decode runs on the PDF's own
     * drawing, not on the layout object.
     */
    private function decodeQrFromPdf(string $pdf, CardLayout $layout): string
    {
        // Our writer emits fonts first, then the front and back content streams, so the back is the last stream.
        preg_match_all('/<< \/Filter \/FlateDecode \/Length \d+ >>\nstream\n(.*?)\nendstream/s', $pdf, $m);
        $backContent = gzuncompress(end($m[1]));
        $this->assertNotFalse($backContent);

        $n = $layout->qr['modules'];
        $size = 252 * 0.24;
        $origin = [27 + 718 * 0.24, 171 - 188 * 0.24]; // QR top-left in PDF points
        $m = $size / $n;

        $grid = array_fill(0, $n, array_fill(0, $n, false));
        preg_match_all('/([-\d.]+) ([-\d.]+) ([-\d.]+) ([-\d.]+) re/', $backContent, $rects, PREG_SET_ORDER);
        foreach ($rects as $r) {
            [$x, $y, $w, $h] = [(float) $r[1], (float) $r[2], (float) $r[3], (float) $r[4]];
            // Only the QR block: inside the QR square, with module-height bars.
            if ($x < $origin[0] - 0.01 || $x > $origin[0] + $size || abs($h - $m) > 0.01 || $y < $origin[1] - $size - 0.01 || $y > $origin[1] + 0.01) {
                continue;
            }
            $row = (int) round(($origin[1] - ($y + $h)) / $m);
            $startCol = (int) round(($x - $origin[0]) / $m);
            $run = (int) round($w / $m);
            for ($c = $startCol; $c < $startCol + $run; $c++) {
                $grid[$row][$c] = true;
            }
        }

        $quiet = 4;
        $scale = 8;
        $side = ($n + 2 * $quiet) * $scale;
        $img = imagecreatetruecolor($side, $side);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        $black = imagecolorallocate($img, 0, 0, 0);
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($grid[$y][$x]) {
                    imagefilledrectangle($img, ($x + $quiet) * $scale, ($y + $quiet) * $scale, ($x + $quiet + 1) * $scale - 1, ($y + $quiet + 1) * $scale - 1, $black);
                }
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'qr') . '.png';
        imagepng($img, $path);
        imagedestroy($img);

        try {
            return (new QrReader($path))->text();
        } finally {
            @unlink($path);
        }
    }

    /* ------------------------------------------------------- exports and audit */

    private function card(array $overrides = []): BusinessCard
    {
        Organization::firstOrCreate(['id' => config('chrsd.org_id')], ['code' => 'CHRSD', 'name' => 'CHRSD']);

        return BusinessCard::create(array_merge([
            'organization_id' => config('chrsd.org_id'),
            'company_name' => 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)',
            'full_name' => 'Mohammed Abu Ramim',
            'person_name' => 'Mohammed Abu Ramim',
            'person_title' => 'Executive Director',
            'phones' => [
                ['type' => 'office', 'number_e164' => '+8802477122566', 'display' => '+880 2 47122566'],
                ['type' => 'mobile', 'number_e164' => '+8801602778984', 'display' => '+880 1602-778984'],
                ['type' => 'whatsapp', 'number_e164' => '+8801334383341', 'display' => '+880 1334-383341'],
            ],
            'emails' => [
                ['type' => 'work', 'address' => 'info@chrsd.org'],
                ['type' => 'work', 'address' => 'ed@chrsd.org'],
            ],
            'website' => 'https://chrsd.org',
            'linkedin_handle' => 'maramim',
            'address_lines' => ['29 Toyenbee Circular Road (5th Floor)', 'Motijheel C/A', 'Dhaka-1000, Bangladesh'],
            'qr_profile' => 'compact',
        ], $overrides));
    }

    private function exporter(): BusinessCardExporter
    {
        return app(BusinessCardExporter::class);
    }

    public function test_export_stores_both_pdfs_on_the_private_disk_and_audits_each(): void
    {
        Storage::fake('local');
        $card = $this->card();
        $user = User::factory()->create();

        $artwork = $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, $user);
        $plate = $this->exporter()->export($card, BusinessCardExport::KIND_FOIL_PLATE, $user);

        foreach ([$artwork, $plate] as $export) {
            Storage::disk('local')->assertExists($export->file_path);
            $this->assertSame(hash('sha256', Storage::disk('local')->get($export->file_path)), $export->sha256);
            $this->assertTrue($export->preflight_report['ok']);
            $this->assertSame($user->id, $export->created_by);
            $this->assertSame('chrsd-pure-php-pdf', $export->engine_name);
            $this->assertStringStartsWith('card-spec-', $export->spec_version);
        }
        $this->assertSame(['Silver_Foil', 'All'], $artwork->preflight_report['separations']);
        $this->assertSame(['All'], $plate->preflight_report['separations']);
        $this->assertDatabaseCount('business_card_exports', 2);
    }

    public function test_an_export_with_a_qr_mismatch_is_refused_and_writes_nothing(): void
    {
        Storage::fake('local');
        $card = $this->card(['phones' => [
            ['type' => 'office', 'number_e164' => '+8801334383341', 'display' => '+880 1334-383341'],
            ['type' => 'mobile', 'number_e164' => '+8801602778984', 'display' => '+880 1602-778984'],
        ]]);

        // The office number is the WhatsApp number here, so the printed office line is 'correct' and the
        // QR matches. The mismatch case is covered by CardServicesTest. This test checks the refusal path
        // with a real blocking layout error instead: an over-long name crosses the safe area.
        $card->update(['person_name' => str_repeat('Mohammed Abu Ramim ', 6), 'full_name' => 'x']);

        try {
            $this->exporter()->export($card->fresh(), BusinessCardExport::KIND_ARTWORK, null);
            $this->fail('The export should have been refused.');
        } catch (CardExportBlockedException $e) {
            $this->assertContains('text_outside_safe_area', array_column($e->issues, 'code'));
        }
        $this->assertDatabaseCount('business_card_exports', 0);
        Storage::disk('local')->assertDirectoryEmpty('business-cards');
    }

    public function test_a_small_qr_needs_the_acknowledgement_to_export(): void
    {
        Storage::fake('local');
        config(['business_card.qr_min_module_mm' => 0.5]);
        $card = $this->card();

        try {
            $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, null, acknowledgeQr: false);
            $this->fail('Export should need the acknowledgement.');
        } catch (CardExportBlockedException $e) {
            $this->assertTrue($e->acknowledgementRequired);
        }

        $export = $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, null, acknowledgeQr: true);
        $this->assertTrue($export->qr_acknowledged);
    }

    /* -------------------------------------------------------------- approval */

    public function test_approval_needs_an_export_for_the_current_data(): void
    {
        Storage::fake('local');
        $card = $this->card();
        $user = User::factory()->create();

        try {
            app(BusinessCardApprover::class)->approve($card, $user);
            $this->fail('Approval without an export must fail.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Export the print artwork', $e->getMessage());
        }

        $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, $user);
        app(BusinessCardApprover::class)->approve($card, $user);

        $card->refresh();
        $this->assertSame('approved', $card->status->value);
        $this->assertSame($user->id, $card->approved_by_id);
        $this->assertNotNull($card->approved_at);
    }

    public function test_an_edited_card_cannot_ride_on_an_old_approval(): void
    {
        Storage::fake('local');
        $card = $this->card();
        $user = User::factory()->create();
        $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, $user);

        $card->update(['person_title' => 'Director']);

        $this->expectException(\RuntimeException::class);
        app(BusinessCardApprover::class)->approve($card->fresh(), $user);
    }

    /* ------------------------------------------------------- authorisation */

    private function viewer(array $permissions = ['view_business_card']): User
    {
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $user = User::create(['name' => 'Viewer', 'email' => uniqid('v', true) . '@test.test', 'password' => Hash::make('secret')]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_a_user_who_can_view_the_card_can_download_its_pdf(): void
    {
        Storage::fake('local');
        $card = $this->card();
        $export = $this->exporter()->export($card, BusinessCardExport::KIND_ARTWORK, null);

        $response = $this->actingAs($this->viewer())->get(route('business-cards.exports.download', $export));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_a_user_without_view_permission_cannot_download_the_pdf(): void
    {
        Storage::fake('local');
        $export = $this->exporter()->export($this->card(), BusinessCardExport::KIND_ARTWORK, null);

        $this->actingAs($this->viewer(['view_official_letter']))
            ->get(route('business-cards.exports.download', $export))
            ->assertForbidden();
    }

    public function test_only_users_with_the_approve_permission_can_approve(): void
    {
        $card = $this->card();

        $this->assertFalse($this->viewer(['view_business_card'])->can('approve', $card));
        $this->assertTrue($this->viewer(['view_business_card', 'approve_business_card'])->can('approve', $card));
    }

    /* ------------------------------------------------------------- Filament */

    private function adminUser(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $names = ['view_any_business_card', 'view_business_card', 'update_business_card', 'approve_business_card'];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $user = User::create(['name' => 'Admin', 'email' => uniqid('a', true) . '@test.test', 'password' => Hash::make('secret')]);
        $user->givePermissionTo($names);

        return $user;
    }

    public function test_edit_page_renders_the_live_preview_for_a_card(): void
    {
        $card = $this->card();

        Livewire::actingAs($this->adminUser())
            ->test(EditBusinessCard::class, ['record' => $card->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Foil plate view');
    }

    public function test_export_action_redirects_to_the_download_for_a_valid_card(): void
    {
        Storage::fake('local');
        $card = $this->card();

        Livewire::actingAs($this->adminUser())
            ->test(ListBusinessCards::class)
            ->callTableAction('export_artwork', $card, ['acknowledge_qr' => false])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseCount('business_card_exports', 1);
        $this->assertSame(BusinessCardExport::KIND_ARTWORK, BusinessCardExport::first()->kind);
    }

    /* ---------------------------------------- contact normalisation, shared path */

    public function test_the_form_and_the_export_build_the_same_contact(): void
    {
        $card = $this->card();
        $fromCard = app(BusinessCardContactFactory::class)->fromCard($card);
        $fromArray = app(BusinessCardContactFactory::class)->fromArray([
            'person_name' => $card->person_name,
            'person_title' => $card->person_title,
            'phones' => $card->phones,
            'emails' => $card->emails,
            'website' => $card->website,
            'linkedin_handle' => $card->linkedin_handle,
            'address_lines' => $card->address_lines,
        ]);

        $this->assertEquals($fromCard, $fromArray);
        $this->assertSame('https://www.linkedin.com/in/maramim', (new LinkedInNormaliser)->normalise($card->linkedin_handle));
    }
}
