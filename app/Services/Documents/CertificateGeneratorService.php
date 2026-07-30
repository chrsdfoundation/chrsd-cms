<?php

namespace App\Services\Documents;

use App\Enums\CertificateIssuance;
use App\Models\Certificate;
use App\Notifications\CertificateDelivered;
use App\Services\Verification\QrCodeService;
use Illuminate\Support\Facades\DB;

class CertificateGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
        protected PdfSignatureService $signer,
        protected TemplateRenderer $templates,
        protected BrowsershotPdfService $pdf,
    ) {}

    /**
     * Render a certificate to PDF via Browsershot (Chromium), attach to the model's `rendered`
     * media collection, and flip issuance_status to Generated.
     *
     * Wrapped in a transaction so a mid-write failure doesn't leave the model
     * flagged Generated with no PDF attached.
     */
    public function generate(Certificate $certificate): Certificate
    {
        return DB::transaction(function () use ($certificate) {
            $certificate->loadMissing([
                'employee.department', 'employee.position',
                'type', 'signedBy', 'documentTemplate',
            ]);

            if ($certificate->document_template_id) {
                $html = $this->templates->render(
                    $certificate->documentTemplate,
                    $this->buildContext($certificate),
                );
            } else {
                $view = $certificate->type->template_view ?: 'documents.certificates.default';
                $sig1 = $certificate->getFirstMedia('signature_1');
                $sig2 = $certificate->getFirstMedia('signature_2');

                // Resolve signature image path. Prefer the per-certificate upload
                // (Spatie MediaLibrary). Fallback: brand-kit signature PNGs.
                // All images are converted to data URIs so mPDF can render them
                // directly without HTTP round-trips or filesystem path resolution.
                $sig1Uri = $sig1 ? $this->fileToDataUri($sig1->getPath()) : null;
                $sig2Uri = $sig2 ? $this->fileToDataUri($sig2->getPath()) : null;
                $sig1Uri = $sig1Uri ?: $this->brandSignatureDataUri($certificate->signatory_1_name ?? 'razib-mustafiz');
                $sig2Uri = $sig2Uri ?: $this->brandSignatureDataUri($certificate->signatory_2_name ?? 'ma-ramim');

                $signatoryName = $certificate->signatory_1_name ?: 'Razib Mustafiz';
                $signatoryTitle = $certificate->signatory_1_title ?: 'Project Coordinator';
                $countersignName = $certificate->signatory_2_name ?: 'M.A. Ramim';
                $countersignTitle = $certificate->signatory_2_title ?: 'Executive Director';

                $html = view($view, [
                    'certificate' => $certificate,
                    'employee' => $certificate->employee,
                    'type' => $certificate->type,
                    'signatory' => $certificate->signedBy,

                    // Feed the CHRSD certificate Blade's variable names too.
                    // Recipient — prefer the free-text recipient_name (used for
                    // non-employee awards) over the linked Employee's full name.
                    'name' => $certificate->recipient_name
                                              ?: (optional($certificate->employee)->full_name ?? ''),
                    // Course/achievement — payload.event_name → purpose → type label.
                    'course_name' => data_get($certificate->payload, 'event_name')
                                              ?: ($certificate->purpose
                                                  ?: (optional($certificate->type)->name ?? '')),
                    'issue_date' => $certificate->issued_on ?? $certificate->created_at,
                    'certificate_no' => $certificate->serial_number,

                    'signatory_name' => $signatoryName,
                    'signatory_title' => $signatoryTitle,
                    'signatory_sig_url' => $sig1Uri,
                    'countersign_name' => $countersignName,
                    'countersign_title' => $countersignTitle,
                    'countersign_sig_url' => $sig2Uri,

                    // All brand assets as data URIs for mPDF direct rendering.
                    'logoUrl' => $this->fileToDataUri(public_path('images/brand/chrsd-full-logo.png')),
                    'sealUrl' => $this->fileToDataUri(public_path('images/brand/chrsd-rosette-seal.png')),
                    'watermarkUrl' => $this->fileToDataUri(public_path('images/brand/chrsd-watermark.svg')),

                    // Vector QR — stays as SVG for print resolution crispness.
                    'qr_svg' => $this->qr->svg($certificate, 4),
                    'qr_data_uri' => $this->qr->pngDataUri($certificate), // kept for legacy templates
                    'qr_uri' => $this->qr->pngDataUri($certificate),
                    'verify_url' => $this->qr->verificationUrl($certificate),
                    'verification_url' => $this->qr->verificationUrl($certificate),
                ])->render();
            }

            // Render via Chromium for proper HTML/CSS table support.
            $paperOrientation = $certificate->documentTemplate?->orientation === 'landscape' ? 'landscape' : 'portrait';
            $filename = sprintf('%s.pdf', $certificate->serial_number);
            $pdfBytes = $this->pdf->render($html, [
                'format' => 'A4',
                'orientation' => $paperOrientation,
                // Certificates come with their own visual chrome (borders,
                // seals, ribbons), so give the page all of it — the Blade
                // template controls its own padding.
                'margin' => ['top' => '0mm', 'right' => '0mm', 'bottom' => '0mm', 'left' => '0mm'],
            ]);
            $signature = $this->signer->sign($pdfBytes);

            $certificate
                ->addMediaFromString($pdfBytes)
                ->usingFileName($filename)
                ->usingName($certificate->serial_number)
                ->toMediaCollection('rendered');

            $certificate->forceFill([
                'issuance_status' => CertificateIssuance::Generated,
                'issued_on' => $certificate->issued_on ?? now()->toDateString(),
                'pdf_content_hash' => $signature,
            ])->save();

            return $certificate->refresh();
        });
    }

    /** Standard placeholder context for a certificate. */
    public function buildContext(Certificate $certificate): array
    {
        $employee = $certificate->employee;

        // Vector QR — Chromium rasterises it at print resolution; the palette
        // PNG that used to be here washed out against the certificate stock.
        $qrSvg = $this->qr->svg($certificate, 4);

        // Resolve the two signature-image URLs from Spatie MediaLibrary.
        // Signature images MUST be data URIs (not filesystem paths) for
        // Chromium — path references won't resolve from a setContent() page.
        $sig1Media = $certificate->getFirstMedia('signature_1');
        $sig2Media = $certificate->getFirstMedia('signature_2');
        $sig1Uri = $sig1Media ? $this->fileToDataUri($sig1Media->getPath()) : null;
        $sig2Uri = $sig2Media ? $this->fileToDataUri($sig2Media->getPath()) : null;

        // Brand-kit fallback signatures matching the printed name.
        $sig1Uri = $sig1Uri ?: $this->brandSignatureDataUri($certificate->signatory_1_name ?? '');
        $sig2Uri = $sig2Uri ?: $this->brandSignatureDataUri($certificate->signatory_2_name ?? '');

        $signedBy = $certificate->signedBy;

        // Payload-first resolution — a template that references
        // {{employee_id}}, {{designation}}, {{department}}, {{duration}},
        // {{position}} reads the fields the admin filled in the form.
        // Fallbacks: linked Employee record → blank.
        $payload = (array) ($certificate->payload ?? []);
        $designation = $payload['designation']
            ?? (optional(optional($employee)->position)->title ?? '');
        $department = $payload['department']
            ?? (optional(optional($employee)->department)->name ?? '');
        $employeeId = $payload['employee_id']
            ?? ($employee->serial_number ?? '');
        $duration = $payload['duration'] ?? '';
        $position = $payload['position'] ?? (optional(optional($employee)->position)->title ?? '');
        $eventName = $payload['event_name'] ?? '';

        // Primary signatory alias — templates that only need one signatory
        // (Experience/Service, Recommendation) use {{signatory_name}} /
        // {{signatory_title}}. Fall through: signatory_1 → signatory_2 →
        // signed-by employee.
        $primarySigName = $certificate->signatory_1_name
            ?: ($certificate->signatory_2_name
                ?: (optional($signedBy)->full_name ?? ''));
        $primarySigTitle = $certificate->signatory_1_title
            ?: ($certificate->signatory_2_title
                ?: (optional(optional($signedBy)->position)->title ?? ''));

        return [
            'name' => $certificate->recipient_name
                                     ?: (optional($employee)->full_name ?? ''),
            'designation' => $designation,
            'department' => $department,
            'employee_id' => $employeeId,
            'organization' => optional($certificate->organization)->name ?? config('app.name'),
            'certificate_number' => $certificate->serial_number,
            'letter_reference' => $certificate->serial_number,
            'date' => optional($certificate->issued_on ?? $certificate->created_at)->toFormattedDateString(),
            'issue_date' => optional($certificate->issued_on ?? $certificate->created_at)->toFormattedDateString(),
            'event_name' => $eventName,
            'position' => $position,
            'duration' => $duration,
            'verification_url' => $this->qr->verificationUrl($certificate),
            'qr_code' => $qrSvg,

            // Two-signatory block. Falls back to the primary `signed_by` employee
            // for the second slot when only the first is provided; falls back
            // further to blanks when neither is set.
            'signatory_1_name' => $certificate->signatory_1_name ?? '',
            'signatory_1_title' => $certificate->signatory_1_title ?? '',
            'signatory_1_sig' => $sig1Uri,
            'signatory_2_name' => $certificate->signatory_2_name
                                    ?? optional($signedBy)->full_name ?? '',
            'signatory_2_title' => $certificate->signatory_2_title
                                    ?? optional(optional($signedBy)->position)->title ?? '',
            'signatory_2_sig' => $sig2Uri,

            // Aliases so single-signatory templates (Experience/Service,
            // Recommendation) don't render empty `{{signatory_name}}` bold
            // markers in the middle of the body.
            'signatory_name' => $primarySigName,
            'signatory_title' => $primarySigTitle,
            'signatory_sig' => $sig1Uri ?: $sig2Uri,
        ];
    }

    /** Read a file into a base64 data URI, or return null if it doesn't exist. */
    private function fileToDataUri(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /**
     * Brand-kit fallback for signatures — same convention as
     * LetterGeneratorService: images/brand/signatures/{name-slug}.png.
     */
    private function brandSignatureDataUri(string $name): ?string
    {
        if ($name === '') {
            return null;
        }
        $slug = str($name)->slug()->value();

        return $this->fileToDataUri(public_path("images/brand/signatures/{$slug}.png"));
    }

    public function markIssued(Certificate $certificate): Certificate
    {
        $certificate->forceFill(['issuance_status' => CertificateIssuance::Issued])->save();

        return $certificate;
    }

    public function markDelivered(Certificate $certificate): Certificate
    {
        $certificate->forceFill(['issuance_status' => CertificateIssuance::Delivered])->save();

        // Fire-and-forget: the notification is queued (ShouldQueue), so the UI
        // returns instantly and mail failure never blocks the state transition.
        if ($certificate->employee?->email) {
            $certificate->employee->notify(new CertificateDelivered($certificate));
        }

        return $certificate;
    }
}
