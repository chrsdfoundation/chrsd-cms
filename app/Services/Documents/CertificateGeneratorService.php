<?php

namespace App\Services\Documents;

use App\Enums\CertificateIssuance;
use App\Models\Certificate;
use App\Notifications\CertificateDelivered;
use App\Services\Verification\QrCodeService;

class CertificateGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
        protected HtmlSignatureService $signer,
        protected TemplateRenderer $templates,
    ) {}

    /**
     * Render a certificate to HTML string (on-the-fly, no storage).
     */
    public function renderHtml(Certificate $certificate): string
    {
        $certificate->loadMissing([
            'employee.department', 'employee.position',
            'type', 'signedBy', 'documentTemplate',
        ]);

        if ($certificate->document_template_id) {
            return $this->templates->render(
                $certificate->documentTemplate,
                $this->buildContext($certificate),
            );
        }

        $view = $certificate->type->template_view ?: 'documents.certificates.default';

        return view($view, $this->buildContext($certificate))->render();
    }

    /**
     * Compute HMAC-SHA256 hash of rendered HTML.
     */
    public function computeHtmlHash(string $html): string
    {
        return $this->signer->sign($html);
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
            'certificate' => $certificate,
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

            // Image assets (as public URLs for browser rendering)
            'logoUrl' => asset('images/brand/chrsd-full-logo.png'),
            'sealUrl' => asset('images/brand/chrsd-rosette-seal.png'),
            'watermarkUrl' => asset('images/brand/letterhead-watermark.png'),
            'signature_image' => $sig1Uri ?: $sig2Uri,
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
