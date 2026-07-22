<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The middleware EnsureTwoFactorPassed lives ONLY inside the Filament admin
 * panel's authMiddleware stack. Hitting /admin/* directly exercises it.
 */
class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $org = Organization::create(['code' => 'ACME', 'name' => 'ACME']);

        $this->user = User::create([
            'name' => 'Jane', 'email' => 'jane@acme.test',
            'password' => Hash::make('secret'),
        ]);
        $this->user->assignRole('super_admin');
        $this->user->organizations()->attach($org->id, ['is_default' => true]);
        $this->user = $this->user->refresh();

        $this->totp = app(TotpService::class);
    }

    protected function enable2fa(): string
    {
        $secret = $this->totp->generateSecret();
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['aaaaaaaaaa', 'bbbbbbbbbb'],
            'two_factor_confirmed_at' => now(),
        ])->save();
        $this->user->refresh();

        return $secret;
    }

    public function test_user_without_2fa_reaches_admin_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_user_with_2fa_is_redirected_to_challenge(): void
    {
        $this->enable2fa();

        $this->actingAs($this->user)
            ->get('/admin')
            ->assertRedirect('/admin/two-factor-challenge');
    }

    public function test_user_with_2fa_and_session_flag_passes_through(): void
    {
        $this->enable2fa();

        $this->actingAs($this->user)
            ->withSession(['two_factor_passed_at' => now()->timestamp])
            ->get('/admin')
            ->assertOk();
    }

    public function test_challenge_page_itself_is_exempt_from_redirect_loop(): void
    {
        $this->enable2fa();

        $this->actingAs($this->user)
            ->get('/admin/two-factor-challenge')
            ->assertOk();
    }
}
