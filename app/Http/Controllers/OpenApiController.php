<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OpenApiController extends Controller
{
    /** JSON spec. Cache aggressively — the spec is a config file, not user data. */
    public function json(): JsonResponse
    {
        return response()
            ->json(config('openapi'), 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=1800');
    }

    /** Swagger UI page. Loads the spec from /api/openapi.json via JavaScript. */
    public function docs(): View
    {
        return view('openapi.docs');
    }
}
