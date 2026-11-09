<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessCardTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCard(array $overrides = []): BusinessCard
    {
        return BusinessCard::create(array_merge([
            'company_name' => 'CHRSD Foundation',
            'full_name' => 'Mohammad Rahim',
            'job_title' => 'Program Director',
            'phone' => '+880-2-12345678',
            'mobile' => '+8801700000000',
            'work_email' => 'rahim@chrsd.org',
            'address' => '29 Toyenbee Circular Road (5th Floor), Motijheel C/A, Dhaka-1000, Bangladesh',
        ], $overrides));
    }

    public function test_business_card_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('business_cards'));
    }

    public function test_business_card_can_be_created(): void
    {
        $card = $this->makeCard();

        $this->assertDatabaseHas('business_cards', [
            'id' => $card->id,
            'full_name' => 'Mohammad Rahim',
            'company_name' => 'CHRSD Foundation',
        ]);
    }

    public function test_soft_delete(): void
    {
        $card = $this->makeCard();
        $card->delete();

        $this->assertSoftDeleted('business_cards', ['id' => $card->id]);
        $this->assertNotNull(BusinessCard::withTrashed()->find($card->id));
    }

    public function test_include_bleed_defaults_to_false(): void
    {
        $card = $this->makeCard();

        $this->assertFalse($card->include_bleed);
    }

    public function test_include_bleed_can_be_enabled(): void
    {
        $card = $this->makeCard(['include_bleed' => true]);

        $this->assertTrue($card->include_bleed);
    }

    public function test_optional_fields_can_be_null(): void
    {
        $card = BusinessCard::create([
            'company_name' => 'Test Corp',
            'full_name' => 'Test User',
        ]);

        $this->assertNull($card->job_title);
        $this->assertNull($card->phone);
        $this->assertNull($card->mobile);
        $this->assertNull($card->whatsapp);
        $this->assertNull($card->work_email);
        $this->assertNull($card->personal_email);
        $this->assertNull($card->website);
        $this->assertNull($card->linkedin);
        $this->assertNull($card->tagline);
        $this->assertNull($card->address);
    }

    public function test_name_font_size_scales_with_length(): void
    {
        $short = $this->makeCard(['full_name' => 'John']);
        $this->assertSame('10pt', $short->nameFontSize());

        $medium = $this->makeCard(['full_name' => 'Mohammad Abdul Rahim Uddin']);
        $this->assertSame('8.5pt', $medium->nameFontSize());

        $long = $this->makeCard(['full_name' => 'A Very Long Name That Exceeds Thirty Characters']);
        $this->assertSame('6.5pt', $long->nameFontSize());
    }

    public function test_print_route_renders_executive_front_and_back(): void
    {
        $card = $this->makeCard([
            'person_name' => 'Mohammad Rahim',
            'person_title' => 'Program Director',
            'phones' => [['type' => 'office', 'number_e164' => null, 'display' => '+8801716610665']],
            'emails' => [['type' => 'work', 'address' => 'rahim@chrsd.org']],
            'address_lines' => ['29 Toyenbee Circular Road, Dhaka'],
            'linkedin_handle' => 'mrahim',
            'qr_profile' => 'compact',
        ]);

        $response = $this->get(route('print.business-card', $card));

        $response->assertOk();
        $response->assertViewIs('print.business-card');
        $response->assertViewHas('error', null);
        $response->assertViewHas('front');
        $response->assertViewHas('back');
        $this->assertStringContainsString('<svg', $response->viewData('front'));
        $this->assertStringContainsString('<svg', $response->viewData('back'));
    }
}
