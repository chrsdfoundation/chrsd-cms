<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\Verification\QrCodeService;

class PrintCertificateController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCode,
    ) {}

    public function show(Certificate $certificate)
    {
        // Load all relations needed for the certificate
        $certificate->loadMissing([
            'signedBy.position',
            'employee',
            'type',
        ]);

        $verifyUrl = config('app.website_url', 'https://chrsd.org') . '/verify/ref/' . $certificate->serial_number;
        $qrSvg = $this->qrCode->svg($certificate, 6);

        // Get primary signatory (Project Coordinator)
        $signatory1Name = $certificate->signedBy?->full_name ?? 'Razib Mustafiz';
        $signatory1Title = $certificate->signedBy?->position?->title ?? 'Project Coordinator';
        // Use static signature file
        $signatory1Image = asset('images/brand/signatures/razib-mustafiz.png');

        // Get secondary signatory (Executive Director)
        $signatory2Name = 'M.A. Ramim';
        $signatory2Title = 'Executive Director';
        // Use static signature file
        $signatory2Image = asset('images/brand/signatures/ma-ramim.png');

        // Get course/certificate name (from certificate's program_name field, not type)
        $courseName = $certificate->program_name ?? $certificate->course_name ?? 'M&E Fundamentals';

        // Get recipient name
        $recipientName = $certificate->employee?->full_name ?? $certificate->recipient_name ?? 'Recipient Name';

        // Get issue date
        $issuedDate = $certificate->issued_at?->format('F j, Y') ?? $certificate->dated_on?->format('F j, Y') ?? date('F j, Y');

        return view('certificates.print', [
            'recipientName' => $recipientName,
            'courseName' => $courseName,
            'issuedDate' => $issuedDate,
            'certificateNo' => $certificate->serial_number,
            'signatory1Name' => $signatory1Name,
            'signatory1Title' => $signatory1Title,
            'signatory1Image' => $signatory1Image,
            'signatory2Name' => $signatory2Name,
            'signatory2Title' => $signatory2Title,
            'signatory2Image' => $signatory2Image,
            'qrCodeSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
            'websiteUrl' => 'www.chrsd.org',
            'contactEmail' => 'info@chrsd.org',
            'registrationNo' => 'S-14480/2026',
        ]);
    }
}
