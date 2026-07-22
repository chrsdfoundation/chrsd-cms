<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * End-to-end tenant isolation: log in as a user in Org A, hit the admin
 * panel, and confirm the middleware set the session correctly + the scope
 * filtered rows to Org A.
 */
class TenantIsolationHttpTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;
    protected Organization $orgB;
    protected User $userA;
    protected User $userB;
    protected User $orphan;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->orgA = Organization::create(['code' => 'ORGA', 'name' => 'Org A']);
        $this->orgB = Organization::create(['code' => 'ORGB', 'name' => 'Org B']);

        Department::create(['organization_id' => $this->orgA->id, 'code' => 'HR-A', 'name' => 'HR A']);
        Department::create(['organization_id' => $this->orgB->id, 'code' => 'HR-B', 'name' => 'HR B']);

        $this->userA = User::create([
            'name' => 'Alice A', 'email' => 'a@example.com', 'password' => Hash::make('secret'),
        ])->assignRole('super_admin');
        $this->userA->organizations()->attach($this->orgA->id, ['is_default' => true]);
        $this->userA = $this->userA->refresh();

        $this->userB = User::create([
            'name' => 'Bob B', 'email' => 'b@example.com', 'password' => Hash::make('secret'),
        ])->assignRole('super_admin');
        $this->userB->organizations()->attach($this->orgB->id, ['is_default' => true]);
        $this->userB = $this->userB->refresh();

        $this->orphan = User::create([
            'name' => 'Orphan', 'email' => 'orphan@example.com', 'password' => Hash::make('secret'),
        ])->assignRole('super_admin');
        $this->orphan = $this->orphan->refresh();
    }

    public function test_user_a_middleware_sets_current_org_a_from_default_pivot(): void
    {
        // A simple authenticated GET is enough to trigger the middleware.
        $this->actingAs($this->userA)->get('/');

        $this->assertSame($this->orgA->id, session('current_organization_id'));
    }

    public function test_orphaned_user_gets_403(): void
    {
        $this->actingAs($this->orphan)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_scope_filters_departments_to_the_session_org(): void
    {
        $this->actingAs($this->userA)->get('/');  // populates session

        // Now query in the same request lifecycle by reusing the session.
        // NB: RefreshDatabase transaction is active across the test.
        $rows = $this->withSession(['current_organization_id' => $this->orgA->id])
            ->call('GET', '/'); // dummy request to keep session

        session(['current_organization_id' => $this->orgA->id]);
        $this->assertSame(1, Department::query()->count());

        session(['current_organization_id' => $this->orgB->id]);
        $this->assertSame(1, Department::query()->count());
        $this->assertSame('HR-B', Department::query()->first()->code);
    }

    public function test_switching_to_a_non_member_org_is_rejected(): void
    {
        $this->actingAs($this->userA)
            ->post('/switch-organization', ['organization_id' => $this->orgB->id])
            ->assertStatus(403);

        // Session should NOT have been switched.
        $this->assertNotSame($this->orgB->id, session('current_organization_id'));
    }

    public function test_switching_to_a_member_org_updates_the_session(): void
    {
        // Give userA membership of orgB too, but keep A as default
        $this->userA->organizations()->attach($this->orgB->id);

        $this->actingAs($this->userA)
            ->post('/switch-organization', ['organization_id' => $this->orgB->id])
            ->assertRedirect('/admin');

        $this->assertSame($this->orgB->id, session('current_organization_id'));
    }
}
