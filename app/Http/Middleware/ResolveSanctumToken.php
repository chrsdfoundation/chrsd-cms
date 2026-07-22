<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-authentication: if the request carries a valid `Authorization: Bearer …`
 * Sanctum token, resolve it and set the user on the request. If not, do
 * nothing — the endpoint remains anonymous-accessible.
 *
 * This lets the same endpoint serve both trusted integrators (who get a
 * higher rate limit via `throttle:verify_authed`) and public verifiers.
 */
class ResolveSanctumToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $next($request);
        }

        // Ask Sanctum's guard to authenticate. If the token is invalid or
        // expired, we simply do NOT set a user — no 401 raised.
        try {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                $request->setUserResolver(fn () => $user);
            }
        } catch (\Throwable) {
            // fall through as anonymous
        }

        return $next($request);
    }
}
