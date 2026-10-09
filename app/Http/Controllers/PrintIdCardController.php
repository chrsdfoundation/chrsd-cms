<?php

namespace App\Http\Controllers;

use App\Models\IdCard;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Milon\Barcode\DNS2D;

class PrintIdCardController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCode,
    ) {}

    public function show(Request $request, IdCard $card): View
    {
        $card->loadMissing(['employee.position', 'employee.department', 'signedBy.position', 'idCardType']);

        $card->blood_group = $card->blood_group ?? 'O+';
        $card->nationality = $card->nationality ?? 'Bangladeshi';
        $card->valid_from = $card->valid_from ?? now();
        $card->valid_until = $card->valid_until ?? now()->addYears(2);

        // Photo — card override, then employee avatar
        $photo = null;
        if ($card->hasMedia('photo')) {
            $photo = $card->getFirstMedia('photo')->getFullUrl();
        } elseif ($card->employee?->hasMedia('avatar')) {
            $photo = $card->employee->getFirstMediaUrl('avatar');
        }

        // Authorised signatory's signature
        $signature = null;
        if ($card->hasMedia('signature')) {
            $signature = $card->getFirstMedia('signature')->getFullUrl() . '?v=' . $card->signature_version;
        }

        // Bearer's own signature (front of card)
        $bearerSignature = null;
        if ($card->hasMedia('bearer_signature')) {
            $bearerSignature = $card->getFirstMedia('bearer_signature')->getFullUrl();
        }

        // QR encodes the full hash-based verification URL (error correction M)
        $verifyBase = rtrim(config('chrsd.verify_base_url') ?: config('app.url'), '/');
        $qrVerifyUrl = $verifyBase . '/verify/' . $card->verification_hash;
        $qrPng = (new DNS2D)->getBarcodePNG($qrVerifyUrl, 'QRCODE,M', 5, 5);
        if (! is_string($qrPng) || $qrPng === '') {
            $qrSvg = (new DNS2D)->getBarcodeSVG($qrVerifyUrl, 'QRCODE,M', 5, 5);
            $qrUrl = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
        } else {
            $qrUrl = 'data:image/png;base64,' . $qrPng;
        }

        // Printed verify text uses the human-readable serial reference
        $printedVerifyText = 'chrsd.org/verify/ref/' . $card->serial_number;

        // Signatory caption: name + designation when linked, else free-text override
        $signatoryCaption = $card->authorized_signatory ?: null;
        if (! $signatoryCaption && $card->signedBy) {
            $signatoryCaption = $card->signedBy->full_name;
        }
        $signatoryCaption = $signatoryCaption ?: 'Authorized Signatory';

        // Compute and store HTML hash for verification
        $htmlHash = hash('sha256', serialize($card->verificationPayload()));
        if ($card->pdf_content_hash_front !== $htmlHash) {
            $card->update(['pdf_content_hash_front' => $htmlHash]);
        }

        // Print mode: 'bleed' (default, print-shop) or 'trim' (card printer)
        $mode = $request->query('mode', 'bleed');

        return view('print.id-card', [
            'card' => $card,
            'employee' => $card->employee,
            'signatory' => $card->signedBy,
            'photo' => $photo,
            'signature' => $signature,
            'bearerSignature' => $bearerSignature,
            'qrUrl' => $qrUrl,
            'qrVerifyUrl' => $qrVerifyUrl,
            'printedVerifyText' => $printedVerifyText,
            'signatoryCaption' => $signatoryCaption,
            'signatoryDesignation' => $card->signatory_designation,
            'registrationNo' => config('chrsd.registration_no'),
            'orgName' => config('app.name', 'CHRSD'),
            'mode' => $mode,
        ]);
    }
}
