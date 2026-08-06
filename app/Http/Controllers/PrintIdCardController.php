<?php

namespace App\Http\Controllers;

use App\Models\IdCard;
<<<<<<< HEAD
use App\Services\QrCodeService;
use Illuminate\View\View;
=======
use App\Services\Documents\IdCardGeneratorService;
>>>>>>> feat/migrate-pdf-to-mpdf

class PrintIdCardController extends Controller
{
    public function __construct(
<<<<<<< HEAD
        protected QrCodeService $qrCode,
    ) {}

    public function show(IdCard $card): View
    {
        $card->loadMissing(['employee.position', 'employee.department', 'signedBy.position', 'idCardType']);

        // Set defaults for missing data
        $card->blood_group = $card->blood_group ?? 'O+';
        $card->nationality = $card->nationality ?? 'Bangladeshi';
        $card->valid_from = $card->valid_from ?? now();
        $card->valid_until = $card->valid_until ?? now()->addYears(2);

        // Get photo - try card media first, then employee avatar
        $photo = null;
        if ($card->hasMedia('photo')) {
            $photo = $card->getFirstMedia('photo')->getFullUrl();
        } elseif ($card->employee?->hasMedia('avatar')) {
            $photo = $card->employee->getFirstMediaUrl('avatar');
        }

        // Get signature - try card media first, then public images folder
        $signature = null;
        if ($card->hasMedia('signature')) {
            $signature = $card->getFirstMedia('signature')->getFullUrl();
        }

        // Generate QR code pointing to public website verification
        // This allows anyone to scan and verify documents without CMS access
        $verifyUrl = config('app.website_url', 'https://chrsd.org') . '/verify/ref/' . $card->serial_number;
        $qrUrl = $this->qrCode->generateUrl($verifyUrl, 256);

        // Compute and store HTML hash for verification (comparing serial number lookup)
        $htmlHash = hash('sha256', serialize($card->verificationPayload()));
=======
        protected IdCardGeneratorService $generator,
    ) {}

    public function show(IdCard $card)
    {
        $html = $this->generator->renderCombined($card);
        $htmlHash = $this->generator->computeHtmlHash($html);

>>>>>>> feat/migrate-pdf-to-mpdf
        if ($card->pdf_content_hash_front !== $htmlHash) {
            $card->update(['pdf_content_hash_front' => $htmlHash]);
        }

<<<<<<< HEAD
        return view('print.id-card', [
            'card' => $card,
            'employee' => $card->employee,
            'signatory' => $card->signedBy,
            'photo' => $photo,
            'signature' => $signature,
            'qrUrl' => $qrUrl,
            'verifyUrl' => $verifyUrl,
            'orgName' => config('app.name', 'CHRSD'),
        ]);
=======
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
>>>>>>> feat/migrate-pdf-to-mpdf
    }
}
