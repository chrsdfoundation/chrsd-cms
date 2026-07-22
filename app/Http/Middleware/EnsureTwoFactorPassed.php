<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorPassed
{
    /**
     * If the authenticated user has 2FA enabled but has NOT presented a valid
     * code this session, redirect them to the challenge page. Otherwise pass
     * through untouched.
     *
     * The challenge page itself is exempt (otherwise: redirect loop).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user->hasEnabledTwoFactor()) {
            return $next($request);
        }

        if (session()->has('two_factor_passed_at')) {
            return $next($request);
        }

        // Don't loop on the challenge page or the logout POST.
        if ($request->routeIs('filament.admin.pages.two-factor-challenge')
            || $request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        return redirect()->to('/admin/two-factor-challenge');
    }
}
