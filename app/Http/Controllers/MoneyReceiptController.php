<?php

namespace App\Http\Controllers;

use App\Models\MoneyReceipt;
use Illuminate\View\View;

class MoneyReceiptController extends Controller
{
    /**
     * Server-rendered printable receipt. Route middleware enforces auth +
     * role — anonymous scanners hit /verify/receipt/{serial} instead.
     */
    public function print(MoneyReceipt $receipt): View
    {
        return view('documents.money-receipts.default', [
            'receipt' => $receipt,
        ]);
    }
}
