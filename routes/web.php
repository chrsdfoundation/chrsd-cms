<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\KioskVerifyController;
use App\Http\Controllers\LetterheadController;
use App\Http\Controllers\MoneyReceiptController;
use App\Http\Controllers\MoneyReceiptVerificationController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\PdfVerificationController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\AllowVerificationApiCors;
use App\Http\Middleware\ResolveSanctumToken;
use App\Http\Middleware\TrackApiTokenUsage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Back-office app: send the bare root to the admin panel instead of the
    // default Laravel welcome page. (The 'web' middleware group still runs,
    // so SetCurrentOrganization session-stamping is unaffected.)
    return redirect('/admin');
});

// Design-time template previews — super_admin only.
Route::middleware(['web', 'auth', 'role:super_admin'])->group(function () {
    Route::get('/certificate-preview', [CertificateController::class, 'show'])
        ->name('certificate.preview');

    Route::get('/certificate-preview/chrsd', [CertificateController::class, 'showChrsd'])
        ->name('certificate.preview.chrsd');

    Route::get('/certificate-preview/premium', [CertificateController::class, 'showPremium'])
        ->name('certificate.preview.premium');

    Route::get('/letterhead-preview', [LetterheadController::class, 'show'])
        ->name('letterhead.preview');
});

/*
 * HTML verify page — anonymous only. IP-based throttle keeps hash enumeration cheap.
 */
Route::middleware('throttle:verify')->group(function () {
    Route::get('/verify/{hash}', [VerificationController::class, 'show'])
        ->where('hash', '[a-f0-9]{64}')
        ->name('verify.show');

    // Serial-number verify — e.g. /verify/ref/LTR-2026-000004.
    // Same throttle bucket as hash-based verify to keep abuse economics equal.
    Route::get('/verify/ref/{serial}', [VerificationController::class, 'showBySerial'])
        ->where('serial', '[A-Z]{2,5}-\d{4}-\d{4,10}')
        ->name('verify.ref');

    // Money receipts — dedicated verification page (embedded in the QR code
    // printed on each receipt). Sits under /verify/receipt/... so it never
    // collides with the generic /verify/{hash} or /verify/ref/... routes.
    Route::get('/verify/receipt/{serial}', [MoneyReceiptVerificationController::class, 'show'])
        ->where('serial', 'MR-\d{4}-\d{4,10}')
        ->name('verify.receipt');
});

// Authenticated printable receipt (Filament "Print" action opens this in a new
// tab). Public scans use /verify/receipt/{serial} instead.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/money-receipts/{receipt}/print', [MoneyReceiptController::class, 'print'])
        ->name('money-receipts.print');

    // ID card print — HTML optimized for browser print-to-PDF (CR80 card format)
    Route::get('/id-cards/{idCard}/print', function (App\Models\IdCard $idCard) {
        return view('documents.id_cards.print', compact('idCard'));
    })->name('id-cards.print');
});

/*
 * JSON API verify endpoints — soft-authenticate against Sanctum. Present a
 * valid bearer token → caller gets a much higher rate limit (keyed on token
 * id) and their usage is stamped onto the personal_access_tokens row.
 * Anonymous callers still work, they just get the IP-based limit.
 *
 * CORS is enabled on these endpoints so they can be called from browsers
 * (integration dashboards, kiosks, verification portals) and external systems.
 */
Route::middleware([
    AllowVerificationApiCors::class,
    ResolveSanctumToken::class,
    'throttle:verify_authed',
    TrackApiTokenUsage::class,
])->group(function () {
    Route::get('/api/verify/{hash}', [VerificationController::class, 'api'])
        ->where('hash', '[a-f0-9]{64}')
        ->name('verify.api');

    Route::get('/api/verify/ref/{serial}', [VerificationController::class, 'apiBySerial'])
        ->where('serial', '[A-Z]{2,5}-\d{4}-\d{4,10}')
        ->name('verify.api.ref');

    Route::post('/api/verify/pdf', PdfVerificationController::class)
        ->name('verify.pdf');

    // Handle CORS preflight requests
    Route::options('/api/verify/{hash}', fn () => response()->noContent())
        ->where('hash', '[a-f0-9]{64}');

    Route::options('/api/verify/ref/{serial}', fn () => response()->noContent())
        ->where('serial', '[A-Z]{2,5}-\d{4}-\d{4,10}');

    Route::options('/api/verify/pdf', fn () => response()->noContent());
});

Route::middleware('throttle:verify_kiosk')->group(function () {
    Route::get('/verify/kiosk', [KioskVerifyController::class, 'show'])->name('kiosk.show');
    Route::post('/verify/kiosk', [KioskVerifyController::class, 'verify'])->name('kiosk.verify');
});

// OpenAPI — restricted to authenticated users. Integrators must hold a valid
// Sanctum token (or an active session) before they can read the spec.
Route::middleware(['auth:sanctum,web'])->group(function () {
    Route::get('/api/openapi.json', [OpenApiController::class, 'json'])->name('openapi.json');
    Route::get('/api/docs', [OpenApiController::class, 'docs'])->name('openapi.docs');
});
