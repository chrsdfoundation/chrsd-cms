<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\Documents\PdfSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PdfSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify');
    }

    public function test_signature_is_deterministic_for_the_same_bytes(): void
    {
        $svc = app(PdfSignatureService::class);
        $bytes = "%PDF-1.7 fake bytes for testing purposes";

        $this->assertSame($svc->sign($bytes), $svc->sign($bytes));
    }

    public function test_different_bytes_produce_different_signatures(): void
    {
        $svc = app(PdfSignatureService::class);

        $this->assertNotSame($svc->sign('AAA'), $svc->sign('AAB'));
    }

    public function test_verify_returns_true_only_for_the_correct_signature(): void
    {
        $svc = app(PdfSignatureService::class);
        $bytes = 'some pdf content';
        $sig = $svc->sign($bytes);

        $this->assertTrue($svc->verify($bytes, $sig));
        $this->assertFalse($svc->verify($bytes, str_repeat('0', 64)));
        $this->assertFalse($svc->verify('tampered', $sig));
    }

    public function test_signature_changes_when_app_key_changes(): void
    {
        $svc = app(PdfSignatureService::class);
        $bytes = 'stable content';

        $originalKey = config('app.key');
        $withKey1 = $svc->sign($bytes);

        // Swap key and re-sign — should produce a different signature.
        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
        $withKey2 = $svc->sign($bytes);

        // Restore key so subsequent tests aren't affected.
        config(['app.key' => $originalKey]);

        $this->assertNotSame($withKey1, $withKey2,
            'Same bytes with a different APP_KEY MUST produce a different signature — otherwise HMAC is broken');
    }

    public function test_pdf_verify_endpoint_finds_matching_certificate(): void
    {
        // Seed a certificate with a known signature attached
        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos  = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Staff']);
        $emp  = Employee::create(['first_name' => 'A', 'last_name' => 'B',
            'email' => 'a.b@test.test', 'department_id' => $dept->id, 'position_id' => $pos->id]);
        $type = CertificateType::create(['code' => 'COE', 'name' => 'Certificate of Employment']);

        $cert = Certificate::create(['employee_id' => $emp->id, 'certificate_type_id' => $type->id]);

        $bytes = "%PDF-1.7\ndeterministic body for signature testing";
        $sig = app(PdfSignatureService::class)->sign($bytes);
        $cert->forceFill(['pdf_content_hash' => $sig])->save();

        $upload = UploadedFile::fake()->createWithContent('doc.pdf', $bytes);

        $this->post('/api/verify/pdf', ['pdf' => $upload])
            ->assertOk()
            ->assertJsonStructure(['found', 'signature', 'snapshot'])
            ->assertJson([
                'found' => true,
                'snapshot' => [
                    'serial' => $cert->serial_number,
                    'kind'   => 'Certificate',
                ],
            ]);
    }

    public function test_pdf_verify_endpoint_returns_404_for_unknown_bytes(): void
    {
        $upload = UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.7\nunknown");

        $this->post('/api/verify/pdf', ['pdf' => $upload])
            ->assertStatus(404)
            ->assertJsonStructure(['found', 'signature', 'reason'])
            ->assertJson(['found' => false, 'reason' => 'no_matching_document']);
    }

    public function test_pdf_verify_endpoint_rejects_non_pdf_uploads(): void
    {
        // Fake a plain-text file — validation must reject it as non-PDF.
        $upload = UploadedFile::fake()->createWithContent('note.txt', 'not a pdf');

        // JSON call returns a 422 on validation failure; HTML call would redirect.
        $this->postJson('/api/verify/pdf', ['pdf' => $upload])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pdf']);
    }

    public function test_pdf_verify_endpoint_is_rate_limited(): void
    {
        $upload = UploadedFile::fake()->createWithContent('doc.pdf', 'x');

        // 30/min shared bucket with the URL-based verify endpoints.
        for ($i = 0; $i < 30; $i++) {
            $this->post('/api/verify/pdf', ['pdf' => $upload]);
        }

        $this->post('/api/verify/pdf', ['pdf' => $upload])
            ->assertStatus(429);
    }
}
