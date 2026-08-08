<?php

namespace App\Services\Documents;

use App\Enums\IdCardIssuance;
use App\Models\IdCard;
use App\Services\Verification\QrCodeService;

class IdCardGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
        protected HtmlSignatureService $signer,
        protected TemplateRenderer $templates,
    ) {}

    /**
     * Render combined A4 sheet with both front and back sides to HTML.
     */
    public function renderCombined(IdCard $card): string
    {
        $card->loadMissing(['employee.department', 'employee.position', 'documentTemplate']);

        $qrSvg = $this->qr->svg($card, 4);
        $verifyUrl = $this->qr->verificationUrl($card);

        $sharedViewData = [
            'idCard' => $card,
            'employee' => $card->employee,
            'photoUrl' => $card->photoUrl(),
            'signatureUrl' => $card->signatureUrl(),
            'qr_svg' => $qrSvg,
            'verify_url' => $verifyUrl,
            'logoUrl' => asset('images/brand/chrsd-full-logo.png'),
            'roundLogoUrl' => asset('images/brand/chrsd-round-logo.png'),
        ];

        return view('documents.id_cards.combined', $sharedViewData)->render();
    }

    /**
     * Render front side to HTML.
     */
    public function renderFront(IdCard $card): string
    {
        $card->loadMissing(['employee.department', 'employee.position', 'documentTemplate']);

        $qrSvg = $this->qr->svg($card, 4);
        $verifyUrl = $this->qr->verificationUrl($card);

        $sharedViewData = [
            'idCard' => $card,
            'employee' => $card->employee,
            'photoUrl' => $card->photoUrl(),
            'signatureUrl' => $card->signatureUrl(),
            'qr_svg' => $qrSvg,
            'verify_url' => $verifyUrl,
            'logoUrl' => asset('images/brand/chrsd-full-logo.png'),
            'roundLogoUrl' => asset('images/brand/chrsd-round-logo.png'),
        ];

        if ($card->document_template_id) {
            return $this->templates->render($card->documentTemplate, $this->buildContext($card));
        }

        return view('documents.id_cards.default-front', $sharedViewData)->render();
    }

    /**
     * Render back side to HTML.
     */
    public function renderBack(IdCard $card): string
    {
        $card->loadMissing(['employee.department', 'employee.position', 'documentTemplate']);

        $qrSvg = $this->qr->svg($card, 4);
        $verifyUrl = $this->qr->verificationUrl($card);

        $sharedViewData = [
            'idCard' => $card,
            'employee' => $card->employee,
            'photoUrl' => $card->photoUrl(),
            'signatureUrl' => $card->signatureUrl(),
            'qr_svg' => $qrSvg,
            'verify_url' => $verifyUrl,
            'logoUrl' => asset('images/brand/chrsd-full-logo.png'),
            'roundLogoUrl' => asset('images/brand/chrsd-round-logo.png'),
        ];

        return view('documents.id_cards.default-back', $sharedViewData)->render();
    }

    /**
     * Compute HMAC-SHA256 hash of rendered HTML.
     */
    public function computeHtmlHash(string $html): string
    {
        return $this->signer->sign($html);
    }

    private function toDataUri(?string $path): ?string
    {
        if (! $path || ! file_exists($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
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
