<?php

namespace App\Services\Documents;

use App\Enums\IdCardIssuance;
use App\Models\IdCard;
use App\Services\Verification\QrCodeService;
use Illuminate\Support\Facades\DB;

class IdCardGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
        protected PdfSignatureService $signer,
        protected TemplateRenderer $templates,
        protected MpdfPdfService $pdf,
    ) {}

    /**
     * Render both sides of the ID card to CR80-landscape PDFs, attach them to
     * the model's `rendered` media collection, HMAC-sign the bytes of each
     * side, and flip issuance_status → Printed.
     *
     * If the card is linked to a DocumentTemplate (document_template_id set),
     * the FRONT side is rendered from that template's Markdown body. The BACK
     * side always uses the code-driven Blade (instructions, return address,
     * signatory line) — those are boilerplate that shouldn't be reinvented per
     * design.
     *
     * We wrap the whole thing in a transaction so a mid-write failure leaves
     * no half-issued card on disk.
     */
    public function generate(IdCard $card): IdCard
    {
        return DB::transaction(function () use ($card) {
            $card->loadMissing(['employee.department', 'employee.position', 'documentTemplate']);

            $photoUrl = $card->photoUrl();
            $signatureUrl = $card->signatureUrl();
            // Vector QR — Chromium rasterises it at print resolution, so the
            // modules stay crisp at 17mm even when scanned from paper. The
            // Milon\Barcode PNG we used before was 1-bit indexed and washed
            // out over the ID-card guilloche background.
            $qrSvg = $this->qr->svg($card, 4);
            $verifyUrl = $this->qr->verificationUrl($card);

            // mPDF needs images as base64 data URIs for reliable rendering without
            // HTTP round-trips or filesystem path resolution issues.
            $logoUrl = $this->toDataUri(public_path('images/brand/chrsd-full-logo.png'));
            $roundLogoUrl = $this->toDataUri(public_path('images/brand/chrsd-round-logo.png'));
            $photoUrl = $this->toDataUri($card->photoUrl());
            $signatureUrl = $this->toDataUri($card->signatureUrl());

            $sharedViewData = [
                'idCard' => $card,
                'employee' => $card->employee,
                'photoUrl' => $photoUrl,
                'signatureUrl' => $signatureUrl,
                'qr_svg' => $qrSvg,
                'verify_url' => $verifyUrl,
                'logoUrl' => $logoUrl,
                'roundLogoUrl' => $roundLogoUrl,
            ];

            // --- Individual CR80 pages (kept for print-shop workflows) --------
            if ($card->document_template_id) {
                $frontHtml = $this->templates->render($card->documentTemplate, $this->buildContext($card));
            } else {
                $frontHtml = view('documents.id_cards.default-front', $sharedViewData)->render();
            }

            $frontBytes = $this->renderCardPdf($frontHtml);
            $backBytes = $this->renderCardPdf(view('documents.id_cards.default-back', $sharedViewData)->render());
            $combinedBytes = $this->renderCardPdf(view('documents.id_cards.combined', $sharedViewData)->render(), false);

            $card->addMediaFromString($combinedBytes)
                ->usingFileName($card->serial_number . '.pdf')
                ->usingName($card->serial_number)
                ->toMediaCollection('rendered');

            $card->addMediaFromString($frontBytes)
                ->usingFileName($card->serial_number . '-front.pdf')
                ->usingName($card->serial_number . ' (front)')
                ->toMediaCollection('rendered');

            $card->addMediaFromString($backBytes)
                ->usingFileName($card->serial_number . '-back.pdf')
                ->usingName($card->serial_number . ' (back)')
                ->toMediaCollection('rendered');

            $card->forceFill([
                'issuance_status' => IdCardIssuance::Printed,
                'pdf_content_hash_front' => $this->signer->sign($frontBytes),
                'pdf_content_hash_back' => $this->signer->sign($backBytes),
            ])->save();

            return $card->refresh();
        });
    }

    private function toDataUri(?string $path): ?string
    {
        if (! $path || ! file_exists($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /**
     * Two page sizes: CR80 landscape (85.6×54mm) for the individual card
     * faces, A4 for the combined print-shop sheet. Zero margins because the
     * card artwork bleeds edge-to-edge.
     */
    private function renderCardPdf(string $html, bool $cr80 = true): string
    {
        return $this->pdf->render($html, [
            'pageSize' => $cr80
                ? ['width' => '85.6mm', 'height' => '54mm']
                : ['width' => '210mm', 'height' => '297mm'],
            'margin' => ['top' => '0mm', 'right' => '0mm', 'bottom' => '0mm', 'left' => '0mm'],
        ]);
    }

    /** Standard placeholder context for an ID card. */
    public function buildContext(IdCard $card): array
    {
        $employee = $card->employee;
        $qrImg = sprintf('<img src="%s" alt="QR" />', $this->qr->pngDataUri($card));

        return [
            'name' => $card->recipient_name
                                     ?: (optional($employee)->full_name ?? ''),
            'designation' => $card->designation ?: optional(optional($employee)->position)->title ?? '',
            'organization' => optional($card->organization)->name ?? config('app.name'),
            'certificate_number' => $card->serial_number,
            'letter_reference' => $card->serial_number,
            'date' => optional($card->valid_from ?? $card->created_at)->toFormattedDateString(),
            'issue_date' => optional($card->valid_from ?? $card->created_at)->toFormattedDateString(),
            'position' => optional(optional($employee)->position)->title ?? '',
            'blood_group' => $card->blood_group ?? '',
            'nationality' => $card->nationality ?? '',
            'program_name' => $card->program_name ?? '',
            'valid_from' => optional($card->valid_from)->toFormattedDateString() ?? '',
            'valid_until' => optional($card->valid_until)->toFormattedDateString() ?? '',
            'verification_url' => $this->qr->verificationUrl($card),
            'qr_code' => $qrImg,
            'photo_url' => $card->photoUrl(),
        ];
    }

    public function markDelivered(IdCard $card): IdCard
    {
        $card->forceFill(['issuance_status' => IdCardIssuance::Delivered])->save();

        return $card;
    }
}
