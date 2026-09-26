<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\Position;
use App\Services\Verification\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify');
    }

    protected function makeCard(): IdCard
    {
        $dept = Department::create(['code' => 'HR', 'name' => 'HR']);
        $pos = Position::create(['department_id' => $dept->id, 'code' => 'S', 'title' => 'Staff']);
        $emp = Employee::create([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane@test.test',
            'department_id' => $dept->id, 'position_id' => $pos->id,
        ]);

        return IdCard::create([
            'employee_id' => $emp->id,
            'designation' => 'Field Officer',
            'blood_group' => 'O+',
            'nationality' => 'Bangladeshi',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYears(2)->toDateString(),
        ]);
    }

    public function test_id_card_gets_id_prefixed_serial_and_hash(): void
    {
        $card = $this->makeCard();

        $this->assertMatchesRegularExpression('/^ID-\d{4}-\d{6}$/', $card->serial_number);
        $this->assertSame(64, strlen($card->verification_hash));
        $this->assertSame(VerificationStatus::Valid, $card->status);
    }

    public function test_verification_service_resolves_id_cards_by_hash(): void
    {
        $card = $this->makeCard();

        $resolved = app(VerificationService::class)->resolve($card->verification_hash);

        $this->assertNotNull($resolved);
        $this->assertInstanceOf(IdCard::class, $resolved);
        $this->assertSame($card->id, $resolved->id);
    }

    public function test_verify_endpoint_returns_id_card_snapshot(): void
    {
        $card = $this->makeCard();

        $this->getJson("/api/verify/{$card->verification_hash}")
            ->assertOk()
            ->assertJson([
                'found' => true,
                'snapshot' => [
                    'serial' => $card->serial_number,
                    'kind' => 'IdCard',
                ],
            ]);
    }

    public function test_html_verify_page_shows_id_card_as_valid(): void
    {
        $card = $this->makeCard();

        $this->get("/verify/{$card->verification_hash}")
            ->assertOk()
            ->assertSee($card->serial_number)
            ->assertSee('Valid', false);
    }

    /**
     * ID cards are dated by their validity window, not a generic issuance
     * date — the public verify page and kiosk screen must label and value
     * that row from valid_from, never from created_at/verified_at.
     */
    public function test_verify_page_shows_valid_from_not_date_of_issuance_for_id_cards(): void
    {
        $card = $this->makeCard();

        $response = $this->get("/verify/{$card->verification_hash}")->assertOk();

        $response->assertSee('Valid From');
        $response->assertDontSee('Date of Issuance');
        $response->assertSee($card->valid_from->format('F j, Y'));

        $snapshot = app(VerificationService::class)->publicSnapshot($card->fresh());
        $this->assertSame($card->valid_from->toDateString(), $snapshot['issued_on']);
    }

    public function test_kiosk_shows_valid_from_not_issued_for_id_cards(): void
    {
        $card = $this->makeCard();

        $response = $this->post('/verify/kiosk', ['query' => $card->verification_hash])->assertOk();

        $response->assertSee('Valid from');
    }

    public function test_revoking_an_id_card_transitions_to_revoked(): void
    {
        $card = $this->makeCard();

        $card->revoke('Lost card, replaced by ID-2026-000002');
        $card->refresh();

        $this->assertSame(VerificationStatus::Revoked, $card->status);
        $this->assertNotNull($card->revoked_at);
        $this->assertStringContainsString('Lost card', $card->revocation_reason);
    }

    public function test_id_card_has_two_pdf_content_hash_columns(): void
    {
        $card = $this->makeCard();

        $this->assertTrue(Schema::hasColumn('id_cards', 'pdf_content_hash_front'));
        $this->assertTrue(Schema::hasColumn('id_cards', 'pdf_content_hash_back'));
        $this->assertNull($card->pdf_content_hash_front, 'Fresh card has no rendered PDF yet');
        $this->assertNull($card->pdf_content_hash_back);
    }
}
