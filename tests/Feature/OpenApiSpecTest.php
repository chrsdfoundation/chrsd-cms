<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OpenApiSpecTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The OpenAPI spec + docs are intentionally restricted to authenticated
        // users (see routes/web.php: middleware auth:sanctum,web). Authenticate
        // so these tests exercise the real, protected endpoints.
        $this->actingAs(User::create([
            'name' => 'Spec Reader',
            'email' => 'spec@example.test',
            'password' => Hash::make('secret'),
        ]));
    }

    public function test_spec_endpoint_returns_valid_oas_3_1(): void
    {
        $res = $this->getJson('/api/openapi.json');

        $res->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version', 'description'],
                'servers',
                'paths',
                'components' => ['schemas', 'securitySchemes', 'responses'],
            ]);

        $this->assertSame('3.1.0', $res->json('openapi'));
    }

    public function test_spec_documents_the_actual_verify_endpoints(): void
    {
        $spec = $this->getJson('/api/openapi.json')->json();

        // These paths MUST be documented — regression guard if someone adds a
        // route without updating the spec.
        $this->assertArrayHasKey('/api/verify/{hash}', $spec['paths']);
        $this->assertArrayHasKey('/api/verify/pdf', $spec['paths']);
        $this->assertArrayHasKey('/verify/{hash}', $spec['paths']);
    }

    public function test_spec_declares_sanctum_bearer_scheme(): void
    {
        $spec = $this->getJson('/api/openapi.json')->json();

        $this->assertArrayHasKey('sanctumBearerToken', $spec['components']['securitySchemes']);
        $this->assertSame('http', $spec['components']['securitySchemes']['sanctumBearerToken']['type']);
        $this->assertSame('bearer', $spec['components']['securitySchemes']['sanctumBearerToken']['scheme']);
    }

    public function test_spec_declares_the_public_snapshot_schema(): void
    {
        $spec = $this->getJson('/api/openapi.json')->json();

        $schema = $spec['components']['schemas']['PublicSnapshot'];
        $this->assertContains('serial', $schema['required']);
        $this->assertContains('status', $schema['required']);
        $this->assertSame(['Employee', 'Certificate', 'OfficialLetter'], $schema['properties']['kind']['enum']);
        $this->assertSame(['valid', 'invalid', 'revoked', 'expired'], $schema['properties']['status']['enum']);
    }

    public function test_docs_page_renders_and_points_at_the_spec(): void
    {
        $this->get('/api/docs')
            ->assertOk()
            ->assertSee('swagger-ui', false)
            ->assertSee('/api/openapi.json', false);
    }
}
