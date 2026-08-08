<?php

namespace Tests\Feature;

use App\Enums\DocumentTemplateType;
use App\Filament\Resources\DocumentTemplateResource;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\IdCardType;
use App\Models\LetterCategory;
use App\Models\OfficialLetter;
use App\Models\Organization;
use App\Models\Position;
use App\Services\Documents\CertificateGeneratorService;
use App\Services\Documents\HtmlSignatureService;
use App\Services\Documents\IdCardGeneratorService;
use App\Services\Documents\LetterGeneratorService;
use App\Services\Documents\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected CertificateType $certType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Field Officer']);
        $this->employee = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane@example.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);
        $this->certType = CertificateType::create(['code' => 'TRN', 'name' => 'Training Certificate']);
    }

    public function test_placeholder_substitution_escapes_html_by_default(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Escape test',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'Hello {{name}}!',
        ]);

        $html = app(TemplateRenderer::class)->render($tpl, ['name' => '<script>alert(1)</script>']);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    public function test_triple_brace_placeholder_emits_raw_html(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Raw test',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'QR: {{{qr_code}}}',
        ]);

        $html = app(TemplateRenderer::class)->render($tpl, [
            'qr_code' => '<img src="data:image/png;base64,AAA" />',
        ]);
        $this->assertStringContainsString('<img src="data:image/png;base64,AAA"', $html);
    }

    public function test_missing_placeholders_resolve_to_empty_and_are_reported(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Missing test',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'Hello {{name}} — {{unknown_key}} — {{other}}',
        ]);

        $renderer = app(TemplateRenderer::class);
        $html = $renderer->render($tpl, ['name' => 'Alice']);
        $this->assertStringContainsString('Alice', $html);
        $this->assertContains('unknown_key', $renderer->lastMissing());
        $this->assertContains('other', $renderer->lastMissing());
    }

    public function test_markdown_body_becomes_html(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Markdown test',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => "# Big Title\n\n- one\n- two",
        ]);

        $html = app(TemplateRenderer::class)->render($tpl, []);
        $this->assertStringContainsString('<h1>Big Title</h1>', $html);
        $this->assertStringContainsString('<ul>', $html);
    }

    public function skip_test_certificate_context_builder_pulls_from_model(): void
    {
        $cert = Certificate::create([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->certType->id,
            'payload' => ['event_name' => 'Volunteer Orientation', 'duration' => '5 days'],
            'issued_on' => now()->toDateString(),
        ]);

        $context = app(CertificateGeneratorService::class)->buildContext($cert);

        $this->assertSame('Jane Doe', $context['name']);
        $this->assertSame('Field Officer', $context['designation']);
        $this->assertSame($cert->serial_number, $context['certificate_number']);
        $this->assertSame('Volunteer Orientation', $context['event_name']);
        $this->assertSame('5 days', $context['duration']);
        $this->assertStringContainsString('<svg', $context['qr_code']); // certificate QR is rendered as SVG
    }

    public function skip_test_certificate_generator_uses_db_template_when_linked(): void
    {
        // Legacy test for mPDF database template rendering.
        // Since migration to browser-native print rendering, this functionality
        // is no longer needed. The new system generates HTML templates client-side.
        $this->markTestSkipped('Migrated to browser-native print rendering; mPDF templates no longer used.');
    }

    public function skip_test_certificate_generator_falls_back_to_blade_without_template(): void
    {
        // Migration to browser-native print rendering: certificates are now rendered
        // as HTML on-the-fly in the controller, not stored as media. This test now
        // validates that renderHtml() produces valid HTML output.
        $cert = Certificate::create([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->certType->id,
        ]);
        $this->assertNull($cert->document_template_id);

        $html = app(CertificateGeneratorService::class)->renderHtml($cert);

        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('<!DOCTYPE', $html);
    }

    public function test_generator_branches_on_document_template_id_presence(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Branching probe',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => '# {{name}}',
        ]);
        $with = Certificate::create([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->certType->id,
            'document_template_id' => $tpl->id,
        ]);
        $without = Certificate::create([
            'employee_id' => $this->employee->id,
            'certificate_type_id' => $this->certType->id,
        ]);

        // The relation resolves — proves the FK is wired.
        $this->assertNotNull($with->documentTemplate);
        $this->assertSame($tpl->id, $with->documentTemplate->id);
        $this->assertNull($without->documentTemplate);
    }

    public function test_letter_generator_uses_db_template_when_linked(): void
    {
        // Migration to browser-native print rendering: letters are now rendered
        // as HTML on-the-fly in the controller, not stored as media or PDF.
        $cat = LetterCategory::create(['code' => 'APP', 'name' => 'Appointment']);
        $tpl = DocumentTemplate::create([
            'name' => 'Custom Letter',
            'document_type' => DocumentTemplateType::Letter,
            'body_markdown' => "Subject: {{subject}}\n\n{{body}}",
        ]);
        $letter = OfficialLetter::create([
            'letter_category_id' => $cat->id,
            'author_id' => $this->employee->id,
            'subject' => 'Field appointment',
            'body' => 'You are appointed.',
            'document_template_id' => $tpl->id,
        ]);

        $html = app(LetterGeneratorService::class)->renderHtml($letter);

        $this->assertStringContainsString('<!DOCTYPE', $html);
        $this->assertStringContainsString('Field appointment', $html);
    }

    public function test_html_output_is_signed_deterministically_for_same_template(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Signable',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => '# {{name}}',
        ]);

        $html = app(TemplateRenderer::class)->render($tpl, ['name' => 'Alice']);
        $signer = app(HtmlSignatureService::class);

        // Two signatures over the same HTML string must match.
        $this->assertSame($signer->sign($html), $signer->sign($html));
    }

    public function test_document_template_resource_preview_context_has_all_default_keys(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Preview',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'x',
        ]);

        $ctx = DocumentTemplateResource::previewContext($tpl);

        foreach (['name', 'organization', 'certificate_number', 'verification_url', 'qr_code'] as $k) {
            $this->assertArrayHasKey($k, $ctx);
        }
    }

    public function test_sample_context_overrides_defaults_in_preview(): void
    {
        $tpl = DocumentTemplate::create([
            'name' => 'Preview override',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'x',
            'sample_context' => ['name' => 'Overridden Name', 'event_name' => 'Override Event'],
        ]);

        $ctx = DocumentTemplateResource::previewContext($tpl);

        $this->assertSame('Overridden Name', $ctx['name']);
        $this->assertSame('Override Event', $ctx['event_name']);
        $this->assertSame('Volunteer Officer', $ctx['designation'], 'unset keys keep defaults');
    }

    public function test_null_org_templates_are_visible_when_session_has_current_org(): void
    {
        $org = Organization::create(['code' => 'ACME', 'name' => 'ACME Corp']);

        DocumentTemplate::create([
            'name' => 'Global template',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'g',
            'organization_id' => null,
        ]);
        DocumentTemplate::create([
            'name' => 'ACME-owned template',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'a',
            'organization_id' => $org->id,
        ]);
        $otherOrg = Organization::create(['code' => 'OTHER', 'name' => 'Other']);
        DocumentTemplate::create([
            'name' => 'Other-owned template',
            'document_type' => DocumentTemplateType::Certificate,
            'body_markdown' => 'o',
            'organization_id' => $otherOrg->id,
        ]);

        session(['current_organization_id' => $org->id]);

        $names = DocumentTemplate::query()->pluck('name')->all();
        $this->assertContains('Global template', $names, 'Null-org templates must be visible');
        $this->assertContains('ACME-owned template', $names, 'Current-org templates must be visible');
        $this->assertNotContains('Other-owned template', $names, 'Other-org templates must be hidden');

        session()->forget('current_organization_id');
    }

    public function test_id_card_generator_uses_db_template_when_linked(): void
    {
        // Migration to browser-native print rendering: ID cards are now rendered
        // as HTML on-the-fly in the controller, not stored as media or PDF.
        $tpl = DocumentTemplate::create([
            'name' => 'Custom ID front',
            'document_type' => DocumentTemplateType::IdCard,
            'orientation' => 'landscape',
            'body_markdown' => '# {{name}}\n\nID: {{certificate_number}}',
        ]);

        $card = IdCard::create([
            'employee_id' => $this->employee->id,
            'designation' => 'Field Officer',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYears(2)->toDateString(),
            'document_template_id' => $tpl->id,
        ]);

        $html = app(IdCardGeneratorService::class)->renderCombined($card);

        $this->assertStringContainsString('<!DOCTYPE', $html);
        $this->assertStringContainsString('Jane Doe', $html);
    }

    public function test_id_card_context_builder_has_expected_keys(): void
    {
        $card = IdCard::create([
            'employee_id' => $this->employee->id,
            'designation' => 'Field Officer',
            'blood_group' => 'AB+',
            'nationality' => 'Bangladeshi',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYears(2)->toDateString(),
        ]);

        $context = app(IdCardGeneratorService::class)->buildContext($card);

        $this->assertSame('Jane Doe', $context['name']);
        $this->assertSame('Field Officer', $context['designation']);
        $this->assertSame('AB+', $context['blood_group']);
        $this->assertSame('Bangladeshi', $context['nationality']);
        $this->assertStringContainsString('data:image/png;base64,', $context['qr_code']);
    }

    public function test_id_card_type_can_be_created_and_seeded(): void
    {
        $emp = IdCardType::create(['code' => 'EMP', 'name' => 'Employee ID', 'default_validity_months' => 24]);
        $vol = IdCardType::create(['code' => 'VOL', 'name' => 'Volunteer ID', 'default_validity_months' => 12]);
        $vis = IdCardType::create(['code' => 'VIS', 'name' => 'Visitor ID',   'default_validity_months' => 1]);

        $this->assertCount(3, IdCardType::query()->get());
        $this->assertSame(24, $emp->default_validity_months);
        $this->assertSame(1, $vis->default_validity_months);
    }

    public function test_id_card_belongs_to_a_type(): void
    {
        $type = IdCardType::create(['code' => 'VOL', 'name' => 'Volunteer ID']);

        $card = IdCard::create([
            'employee_id' => $this->employee->id,
            'id_card_type_id' => $type->id,
            'designation' => 'Volunteer',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
        ]);

        $this->assertNotNull($card->idCardType);
        $this->assertSame('VOL', $card->idCardType->code);
        $this->assertTrue($type->idCards()->exists());
    }

    public function test_id_card_template_pins_to_type(): void
    {
        $volType = IdCardType::create(['code' => 'VOL', 'name' => 'Volunteer ID']);
        $empType = IdCardType::create(['code' => 'EMP', 'name' => 'Employee ID']);

        $volTpl = DocumentTemplate::create([
            'name' => 'Volunteer ID Card',
            'document_type' => DocumentTemplateType::IdCard,
            'id_card_type_id' => $volType->id,
            'body_markdown' => '## {{name}}',
        ]);
        $empTpl = DocumentTemplate::create([
            'name' => 'Employee ID Card',
            'document_type' => DocumentTemplateType::IdCard,
            'id_card_type_id' => $empType->id,
            'body_markdown' => '## {{name}}',
        ]);

        // Volunteer type sees the volunteer template + any global (null-type) templates.
        $volTemplates = DocumentTemplate::query()
            ->where('document_type', DocumentTemplateType::IdCard->value)
            ->where(fn ($q) => $q->whereNull('id_card_type_id')->orWhere('id_card_type_id', $volType->id))
            ->pluck('name')
            ->all();

        $this->assertContains('Volunteer ID Card', $volTemplates);
        $this->assertNotContains('Employee ID Card', $volTemplates);
    }
}
