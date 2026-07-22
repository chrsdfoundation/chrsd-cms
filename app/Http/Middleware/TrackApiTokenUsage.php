<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiTokenUsage
{
    /**
     * After Sanctum resolves a valid token onto the request user, stamp it
     * with `last_used_ip` + `last_used_user_agent` so admins can audit which
     * integration is calling from where. Sanctum sets `last_used_at` for us.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->forceFill([
                'last_used_ip' => $request->ip(),
                'last_used_user_agent' => substr((string) $request->userAgent(), 0, 512),
            ])->save();
        }

        return $response;
    }
}
