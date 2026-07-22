<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\MoneyReceipt;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MoneyReceiptVerificationController extends Controller
{
    /**
     * Public receipt verification page. Anonymous, throttled at the route
     * level. Strict serial format (MR-YYYY-NNNNNN) is enforced by the route
     * regex; the format check here is a belt-and-braces defence against
     * anyone bypassing the router constraint.
     */
    public function show(Request $request, string $serial): View|Response
    {
        if (! preg_match('/^MR-\d{4}-\d{4,10}$/', $serial)) {
            return $this->render(
                found: false,
                receipt: null,
                serial: $serial,
                status: 404,
            );
        }

        // acrossOrganizations() so anonymous verifiers (no session tenant)
        // can still find the row — matches the pattern used by
        // VerificationController::showBySerial().
        $receipt = MoneyReceipt::query()
            ->acrossOrganizations()
            ->where('serial_number', $serial)
            ->first();

        return $this->render(
            found: $receipt !== null,
            receipt: $receipt,
            serial: $serial,
            status: $receipt ? 200 : 404,
        );
    }

    protected function render(bool $found, ?MoneyReceipt $receipt, string $serial, int $status): Response
    {
        $response = response()->view('verify.receipt', [
            'found'   => $found,
            'receipt' => $receipt,
            'serial'  => $serial,
        ], $status);

        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Frame-Options', 'DENY')
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
    }
}
