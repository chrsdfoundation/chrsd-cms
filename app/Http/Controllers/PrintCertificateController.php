<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
<<<<<<< HEAD
use Illuminate\View\View;

class PrintCertificateController extends Controller
{
    public function show(Certificate $certificate): View
    {
        $certificate->loadMissing(['employee', 'type', 'documentTemplate']);

        return view('print.certificate', [
            'certificate' => $certificate,
            'employee' => $certificate->employee,
            'type' => $certificate->type,
        ]);
=======
use App\Services\Documents\CertificateGeneratorService;

class PrintCertificateController extends Controller
{
    public function __construct(
        protected CertificateGeneratorService $generator,
    ) {}

    public function show(Certificate $certificate)
    {
        $html = $this->generator->renderHtml($certificate);
        $htmlHash = $this->generator->computeHtmlHash($html);

        if ($certificate->pdf_content_hash !== $htmlHash) {
            $certificate->update(['pdf_content_hash' => $htmlHash]);
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
>>>>>>> feat/migrate-pdf-to-mpdf
    }
}
