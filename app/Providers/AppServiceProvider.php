<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our custom PAT model with audit columns (last_used_ip, last_used_user_agent)
        // instead of Sanctum's default so token usage is trackable per-request.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Global password policy. Anything calling Password::default() (Filament
        // login/register/reset, form requests, our own change-password page)
        // inherits this baseline. Testing env is relaxed so we don't slow the
        // suite with `->uncompromised()` HIBP calls or force noisy fixtures.
        Password::defaults(function () {
            $rule = Password::min(12)->letters()->numbers()->mixedCase()->symbols();
            return app()->environment('production') ? $rule->uncompromised() : $rule;
        });

        // Public verify endpoint — deters hash enumeration and cheap-scan bots.
        // Legitimate humans scanning a printed QR won't hit this limit; scripted
        // brute-force of 2^256 hashes obviously cannot either, but the cap saves
        // CPU/DB when someone tries.
        RateLimiter::for('verify', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perHour(300)->by($request->ip()),
            ];
        });

        // Kiosk endpoint runs at a reception desk — legitimate operators need
        // roomier limits. 2/sec average, 20/min average over an hour.
        RateLimiter::for('verify_kiosk', function (Request $request) {
            return [
                Limit::perMinute(120)->by($request->ip()),
                Limit::perHour(1200)->by($request->ip()),
            ];
        });

        // Auth-aware verify limiter. Presence of a Sanctum token = trusted
        // integration; the limit is keyed on the token id (so multiple
        // machines behind the same NAT don't compete) and 200× higher than
        // the IP-based limit. Lookup goes direct to the token model so the
        // limiter doesn't depend on the sanctum guard having run first.
        RateLimiter::for('verify_authed', function (Request $request) {
            if ($bearer = $request->bearerToken()) {
                $token = PersonalAccessToken::findToken($bearer);
                if ($token) {
                    return [
                        Limit::perMinute(1000)->by('token:' . $token->id),
                        Limit::perHour(6000)->by('token:' . $token->id),
                    ];
                }
            }
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perHour(300)->by($request->ip()),
            ];
        });
    }
}
