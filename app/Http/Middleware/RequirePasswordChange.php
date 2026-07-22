<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the user's `must_change_password` flag is set, block every admin request
 * except the change-password page (and logout) until they pick a new one.
 *
 * The flag is toggled on by the "Force password reset" admin action, and off
 * on successful password change.
 */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if (! Auth::user()->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.pages.change-password')
            || $request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        return redirect()->to('/admin/change-password');
    }
}
