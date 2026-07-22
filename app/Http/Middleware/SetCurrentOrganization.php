<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    /**
     * Single-organization resolver.
     *
     * This CMS belongs exclusively to CHRSD Foundation. On every authenticated
     * request we ensure the session carries the CHRSD org ID so the
     * BelongsToOrganization global scope applies correctly.
     *
     * Anonymous requests (login page, public verify endpoints) pass through
     * untouched — the scope only activates when the session key is set.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! session()->has('current_organization_id')) {
            session(['current_organization_id' => config('chrsd.org_id')]);
        }

        return $next($request);
    }
}
