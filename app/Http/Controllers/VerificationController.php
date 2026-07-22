<?php

namespace App\Http\Controllers;

use App\Services\Verification\VerificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    public function __construct(protected VerificationService $verifier) {}

    public function show(Request $request, string $hash): View|Response
    {
        if (! $this->isValidHash($hash)) {
            abort(404);
        }

        $model = $this->verifier->resolve($hash);

        $response = response()->view('verify.show', [
            'found' => $model !== null,
            'snapshot' => $model ? $this->verifier->publicSnapshot($model) : null,
        ], $model ? 200 : 404);

        return $this->hardenHeaders($response);
    }

    /**
     * Serial-number sibling of show() for humans who cannot scan a QR (e.g. a
     * printed reference number quoted over the phone). Same public snapshot,
     * same hardening, same "found or 404" semantics.
     */
    public function showBySerial(Request $request, string $serial): View|Response
    {
        $model = $this->resolveBySerial($serial);

        $response = response()->view('verify.show', [
            'found' => $model !== null,
            'snapshot' => $model ? $this->verifier->publicSnapshot($model) : null,
        ], $model ? 200 : 404);

        return $this->hardenHeaders($response);
    }

    /**
     * JSON sibling of show() for third-party verifier integrations (kiosks,
     * partner background-check systems, government portals). Same rate limit,
     * same 404 semantics, same body-shape hardening.
     */
    public function api(Request $request, string $hash): JsonResponse
    {
        if (! $this->isValidHash($hash)) {
            return response()->json(['error' => 'invalid_hash_format'], 404);
        }

        $model = $this->verifier->resolve($hash);

        if (! $model) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'snapshot' => $this->verifier->publicSnapshot($model),
        ]);
    }

    /** Serial-number sibling of api(). */
    public function apiBySerial(Request $request, string $serial): JsonResponse
    {
        $model = $this->resolveBySerial($serial);

        if (! $model) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'snapshot' => $this->verifier->publicSnapshot($model),
        ]);
    }

    protected function isValidHash(string $hash): bool
    {
        return strlen($hash) === 64 && ctype_xdigit($hash);
    }

    /**
     * Iterate the verifier registry looking for a record whose serial_number
     * matches exactly. Uses acrossOrganizations() so anonymous verifiers (who
     * have no session tenant) can still find records.
     */
    protected function resolveBySerial(string $serial): ?Model
    {
        // Accept a bounded serial shape only: PREFIX-YYYY-NNNNNN. Prevents
        // this endpoint from becoming a general "peek at any string" probe.
        if (! preg_match('/^[A-Z]{2,5}-\d{4}-\d{4,10}$/', $serial)) {
            return null;
        }

        foreach ($this->verifier->registry() as $class) {
            $model = $class::query()->acrossOrganizations()
                ->where('serial_number', $serial)->first();
            if ($model) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Public verify pages are one-shot — cache them briefly at the CDN edge but
     * never index them, never leak referrer, never allow embedding.
     */
    protected function hardenHeaders(Response $response): Response
    {
        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Frame-Options', 'DENY')
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
    }
}
