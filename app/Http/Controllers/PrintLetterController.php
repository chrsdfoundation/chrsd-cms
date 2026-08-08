<?php

namespace App\Http\Controllers;

use App\Models\OfficialLetter;
use App\Services\Verification\QrCodeService;
use Illuminate\View\View;

class PrintLetterController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCode,
    ) {}

    public function show(OfficialLetter $letter): View
    {
        $letter->loadMissing(['signedBy.position', 'documentTemplate', 'letterAuthor', 'category']);

        $verifyUrl = config('app.website_url', 'https://chrsd.org') . '/verify/ref/' . $letter->serial_number;
        $qrSvg = $this->qrCode->svg($letter, 4);

        // Resolve signatory (prioritize signedBy employee over letterAuthor)
        $signatoryName = $letter->signedBy?->full_name ?? $letter->letterAuthor?->name ?? '';
        $signatoryTitle = $letter->signedBy?->position?->title ?? $letter->letterAuthor?->designation ?? '';
        $signatoryImage = $letter->getFirstMediaUrl('signature') ?? null;

        return view('documents.letters.standard', [
            // Master template variables
            'headerImageUrl' => asset('images/brand/letterhead-header.png'),
            'footerImageUrl' => asset('images/brand/letterhead-footer.png'),
            'watermarkUrl' => asset('images/brand/letterhead-watermark.png'),
            'qrCodeSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
            'footerText' => '<strong>CHRSD Foundation</strong><br/>Centre for Humanitarian Research and Social Development',

            // Letter-specific variables
            'serial_number' => $letter->serial_number,
            'dateFormatted' => $letter->dated_on?->format('F j, Y') ?? date('F j, Y'),
            'subject' => $letter->subject,
            'recipientName' => $letter->recipient_name,
            'recipientTitle' => $letter->recipient_title,
            'recipientAddress' => $letter->recipient_address,
            'salutation' => $letter->salutation ?? 'Dear Sir/Madam,',
            'bodyHtml' => $letter->body,
            'closing' => $letter->closing ?? 'Sincerely,',
            'signatoryName' => $signatoryName,
            'signatoryTitle' => $signatoryTitle,
            'signatoryImage' => $signatoryImage,
            'enclosures' => $letter->enclosures,
            'ccList' => $letter->cc_list,
        ]);
    }
}
