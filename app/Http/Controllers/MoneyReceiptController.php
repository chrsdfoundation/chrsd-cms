<?php

namespace App\Http\Controllers;

use App\Models\MoneyReceipt;
use App\Services\QrCodeService;
use Illuminate\View\View;

class MoneyReceiptController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCode,
    ) {}

    /**
     * Server-rendered printable receipt. Route middleware enforces auth +
     * role — anonymous scanners hit /verify/receipt/{serial} instead.
     */
    public function print(MoneyReceipt $receipt): View
    {
        $verifyUrl = $this->qrCode->verificationUrl($receipt);
        $qrSvg = $this->qrCode->svg($receipt);

        return view('documents.money-receipts.default', [
            'receipt' => $receipt,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
        ]);
    }
}
