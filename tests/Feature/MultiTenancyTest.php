<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orgA = Organization::create(['code' => 'ORGA', 'name' => 'Org A']);
        $this->orgB = Organization::create(['code' => 'ORGB', 'name' => 'Org B']);
    }

    public function test_no_session_current_org_yields_no_scope_all_rows_visible(): void
    {
        // Belt-and-braces: ensure session key is absent
        session()->forget('current_organization_id');

        Department::create(['organization_id' => $this->orgA->id, 'code' => 'HR-A', 'name' => 'HR A']);
        Department::create(['organization_id' => $this->orgB->id, 'code' => 'HR-B', 'name' => 'HR B']);

        $this->assertSame(2, Department::query()->count());
    }

    public function test_session_current_org_filters_to_that_org(): void
    {
        Department::create(['organization_id' => $this->orgA->id, 'code' => 'HR-A', 'name' => 'HR A']);
        Department::create(['organization_id' => $this->orgB->id, 'code' => 'HR-B', 'name' => 'HR B']);

        session(['current_organization_id' => $this->orgA->id]);

        $rows = Department::query()->get();

        $this->assertCount(1, $rows);
        $this->assertSame('HR-A', $rows->first()->code);
    }

    public function test_across_organizations_scope_bypasses_the_filter(): void
    {
        Department::create(['organization_id' => $this->orgA->id, 'code' => 'HR-A', 'name' => 'HR A']);
        Department::create(['organization_id' => $this->orgB->id, 'code' => 'HR-B', 'name' => 'HR B']);

        session(['current_organization_id' => $this->orgA->id]);

        $this->assertSame(1, Department::query()->count());
        $this->assertSame(2, Department::query()->acrossOrganizations()->count());
    }

    public function test_creating_a_model_with_current_org_auto_stamps_organization_id(): void
    {
        session(['current_organization_id' => $this->orgB->id]);

        $d = Department::create(['code' => 'FIN', 'name' => 'Finance']);

        $this->assertSame($this->orgB->id, $d->organization_id);
    }

    public function test_explicit_organization_id_on_create_wins_over_session(): void
    {
        session(['current_organization_id' => $this->orgA->id]);

        $d = Department::create([
            'organization_id' => $this->orgB->id,
            'code' => 'FIN', 'name' => 'Finance',
        ]);

        $this->assertSame($this->orgB->id, $d->organization_id);
    }
}
