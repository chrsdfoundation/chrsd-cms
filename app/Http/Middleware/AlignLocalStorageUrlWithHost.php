<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local development only.
 *
 * APP_URL is often http://localhost:8000 while the panel is opened as
 * http://127.0.0.1:8000 (or another loopback origin). Media URLs built from
 * APP_URL are then cross-origin. Filament's file fields fetch existing files
 * from those URLs; the storage response has no CORS header, so the fetch fails
 * and the field stays on "Loading" with no way to replace the file.
 *
 * In the local environment, when both APP_URL and the request are loopback
 * origins that differ, the public disk is pointed at the origin the request came
 * in on. Production, and any non-loopback host, keep APP_URL unchanged.
 */
class AlignLocalStorageUrlWithHost
{
    private const LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1'];

    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $requestOrigin = $request->getSchemeAndHttpHost();

        if (app()->environment('local')
            && $requestOrigin !== $appUrl
            && in_array($appHost, self::LOOPBACK_HOSTS, true)
            && in_array($request->getHost(), self::LOOPBACK_HOSTS, true)) {
            config(['filesystems.disks.public.url' => $requestOrigin . '/storage']);
            Storage::forgetDisk('public');
        }

        return $next($request);
    }
}
