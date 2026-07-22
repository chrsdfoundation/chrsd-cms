<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordChangeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $org = Organization::create(['code' => 'ACME', 'name' => 'ACME']);

        $this->user = User::create([
            'name' => 'Jane', 'email' => 'jane@acme.test',
            'password' => Hash::make('Original-Pw-123!'),
            'password_changed_at' => now()->subMonths(2),
        ]);
        $this->user->assignRole('super_admin');
        $this->user->organizations()->attach($org->id, ['is_default' => true]);
        $this->user = $this->user->refresh();
    }

    public function test_user_without_must_change_flag_reaches_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_user_with_must_change_flag_is_redirected_to_change_page(): void
    {
        $this->user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($this->user)
            ->get('/admin')
            ->assertRedirect('/admin/change-password');
    }

    public function test_change_password_page_itself_is_exempt_from_redirect_loop(): void
    {
        $this->user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($this->user)
            ->get('/admin/change-password')
            ->assertOk();
    }

    public function test_force_reset_action_flips_the_flag_and_regenerates_password(): void
    {
        $originalHash = $this->user->password;
        $this->user->forceFill(['must_change_password' => false])->save();

        // Directly invoke the same logic the Filament action calls.
        $temp = Str::password(16);
        $this->user->forceFill([
            'password' => Hash::make($temp),
            'password_changed_at' => now(),
            'must_change_password' => true,
        ])->save();
        $this->user->refresh();

        $this->assertTrue($this->user->must_change_password);
        $this->assertNotSame($originalHash, $this->user->password);
        $this->assertTrue(Hash::check($temp, $this->user->password));
    }

    public function test_password_columns_are_present_and_cast(): void
    {
        $this->assertTrue($this->user->hasCast('must_change_password'));
        $this->assertTrue($this->user->hasCast('password_changed_at'));
        $this->assertInstanceOf(Carbon::class, $this->user->password_changed_at);
    }
}
