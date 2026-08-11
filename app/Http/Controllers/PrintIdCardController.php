<?php

namespace App\Http\Controllers;

use App\Models\IdCard;
use App\Services\QrCodeService;
use Illuminate\View\View;

class PrintIdCardController extends Controller
{
    public function __construct(
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
            $signature = $card->getFirstMedia('signature')->getFullUrl() . '?v=' . $card->signature_version;
        }

        // Generate QR code pointing to public website verification
        // This allows anyone to scan and verify documents without CMS access
        $verifyUrl = $this->qrCode->verificationUrl($card);
        $qrUrl = $this->qrCode->pngDataUri($card);

        // Compute and store HTML hash for verification (comparing serial number lookup)
        $htmlHash = hash('sha256', serialize($card->verificationPayload()));

        if ($card->pdf_content_hash_front !== $htmlHash) {
            $card->update(['pdf_content_hash_front' => $htmlHash]);
        }

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
    }
}
