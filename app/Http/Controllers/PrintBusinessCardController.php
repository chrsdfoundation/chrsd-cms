<?php

namespace App\Http\Controllers;

use App\Models\BusinessCard;
use App\Services\QrCodeService;
use App\Services\VCardBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintBusinessCardController extends Controller
{
    public function __construct(
        protected VCardBuilder $vcard,
        protected QrCodeService $qrCode,
    ) {}

    public function show(Request $request, BusinessCard $card): View
    {
        $vcardPayload = $this->vcard->build($card);

        $qrSvg = $this->qrCode->svg($vcardPayload, 4);
        $qrUrl = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        $logoUrl = null;
        if ($card->hasMedia('logo')) {
            $logoUrl = $card->getFirstMedia('logo')->getFullUrl();
        }

        $mode = $request->query('mode', $card->include_bleed ? 'bleed' : 'trim');

        return view('print.business-card', [
            'card' => $card,
            'qrUrl' => $qrUrl,
            'logoUrl' => $logoUrl,
            'mode' => $mode,
        ]);
    }
}
