<?php

use App\Filament\Resources\DocumentTemplateResource;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateGeneratorController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\KioskVerifyController;
use App\Http\Controllers\LetterheadController;
use App\Http\Controllers\MoneyReceiptController;
use App\Http\Controllers\MoneyReceiptVerificationController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\PrintCertificateController;
use App\Http\Controllers\PrintIdCardController;
use App\Http\Controllers\PrintLetterController;
use App\Http\Controllers\PrintReportController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\AllowVerificationApiCors;
use App\Http\Middleware\ResolveSanctumToken;
use App\Http\Middleware\TrackApiTokenUsage;
use App\Models\IdCard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Back-office app: send the bare root to the admin panel instead of the
    // default Laravel welcome page. (The 'web' middleware group still runs,
    // so SetCurrentOrganization session-stamping is unaffected.)
    return redirect('/admin');
});

// Design-time template previews — super_admin only.
Route::middleware(['web', 'auth', 'role:super_admin'])->group(function () {
    // DocumentTemplate preview: renders the shell as HTML with a floating
    // Print / Save-as-PDF button. Filament's Preview action opens this in a
    // new tab (see DocumentTemplateResource actions). Mirrors the print.letter
    // pipeline — no server-side PDF library required.
    Route::get('/admin/document-templates/{template}/preview', fn (\App\Models\DocumentTemplate $template) =>
        DocumentTemplateResource::streamPreview($template)
    )->name('document-templates.preview');

    Route::get('/certificate-preview', [CertificateController::class, 'show'])
        ->name('certificate.preview');

    Route::get('/certificate-preview/chrsd', [CertificateController::class, 'showChrsd'])
        ->name('certificate.preview.chrsd');

    Route::get('/certificate-preview/premium', [CertificateController::class, 'showPremium'])
        ->name('certificate.preview.premium');

    Route::get('/letterhead-preview', [LetterheadController::class, 'show'])
        ->name('letterhead.preview');
});

// Print-preview routes (HTML printing - accessible for document preview)
Route::middleware(['web'])->group(function () {
    Route::get('/print/certificate/{certificate}', [PrintCertificateController::class, 'show'])
        ->name('print.certificate');

    Route::get('/print/letter/{letter}', [PrintLetterController::class, 'show'])
        ->name('print.letter');

    Route::get('/print/id-card/{card}', [PrintIdCardController::class, 'show'])
        ->name('print.id-card')
        ->withTrashed();

    Route::get('/print/report/monthly-issuance', [PrintReportController::class, 'monthlyIssuance'])
        ->name('print.report.monthly');

    Route::get('/print/report/compliance-export', [PrintReportController::class, 'complianceExport'])
        ->name('print.report.compliance');

    Route::get('/print/report/department-roster', [PrintReportController::class, 'departmentRoster'])
        ->name('print.report.roster');
});

/*
 * Certificate generator — HTML preview with verification hash.
 */
Route::middleware(['web'])->group(function () {
    // Preview HTML
    Route::get('/certificates/{certificate}/preview', [CertificateGeneratorController::class, 'preview'])
        ->name('certificates.preview');
});

/*
 * Certificate verification — anonymous only. IP-based throttle keeps hash enumeration cheap.
 */
Route::middleware('throttle:verify')->group(function () {
    Route::get('/certificates/verify/{hash}', [CertificateVerificationController::class, 'verify'])
        ->where('hash', '[a-f0-9]{64}')
        ->name('certificates.verify');
});

/*
 * HTML verify page — anonymous only. IP-based throttle keeps hash enumeration cheap.
 */
Route::middleware('throttle:verify')->group(function () {
    // Accepts either a 64-char hash (QR-scanned path) or a PREFIX-YYYY-NNNN
    // serial number (manually-typed short URL printed on cards/receipts).
    Route::get('/verify/{hash}', [VerificationController::class, 'show'])
        ->where('hash', '(?:[a-f0-9]{64}|[A-Z]{2,5}-\d{4}-\d{4,10})')
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
    Route::get('/id-cards/{idCard}/print', function (IdCard $idCard) {
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
        ->where('hash', '(?:[a-f0-9]{64}|[A-Z]{2,5}-\d{4}-\d{4,10})')
        ->name('verify.api');

    Route::get('/api/verify/ref/{serial}', [VerificationController::class, 'apiBySerial'])
        ->where('serial', '[A-Z]{2,5}-\d{4}-\d{4,10}')
        ->name('verify.api.ref');
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
