<?php

namespace App\Services\BusinessCard;

use App\Models\BusinessCard;
use App\Models\BusinessCardExport;
use App\Models\User;
use App\Services\BusinessCard\Pdf\CustomPdfCardRenderer;
use App\Services\BusinessCard\Pdf\PdfPreflight;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Produces the print artwork and the foil plate for one card, and writes the audit row.
 *
 * Every export runs the same pipeline: contact facts, vCard, consistency check,
 * layout validation, render, preflight. An export that fails any step is refused and
 * writes nothing. A successful export is stored on the private disk and audited.
 */
final class BusinessCardExporter
{
    public function __construct(
        private readonly BusinessCardContactFactory $contacts,
        private readonly CardVCardBuilder $vcard,
        private readonly CardLayoutEngine $engine,
        private readonly CardConsistencyChecker $consistency,
        private readonly CustomPdfCardRenderer $renderer,
        private readonly CardSpec $spec,
    ) {}

    /**
     * @throws CardExportBlockedException when the card cannot be printed as it stands
     */
    public function export(BusinessCard $card, string $kind, ?User $by, bool $acknowledgeQr = false): BusinessCardExport
    {
        if (! in_array($kind, [BusinessCardExport::KIND_ARTWORK, BusinessCardExport::KIND_FOIL_PLATE], true)) {
            throw new RuntimeException("Unknown export kind [{$kind}].");
        }

        $contact = $this->contacts->fromCard($card);
        $profile = $card->qr_profile ?: CardVCardBuilder::PROFILE_COMPACT;
        $vcard = $this->vcard->build($contact, $profile);
        $mode = $kind === BusinessCardExport::KIND_FOIL_PLATE ? 'plate' : 'artwork';
        $layout = $this->engine->build($contact, $vcard, $profile, $mode);

        // The QR must encode what the card prints. This check is part of every export.
        $consistencyErrors = $this->consistency->check($contact, $vcard, $profile);
        $issues = $layout->issues;
        foreach ($consistencyErrors as $message) {
            $issues[] = ['severity' => 'error', 'code' => 'qr_mismatch', 'side' => null, 'message' => $message, 'requires_acknowledgement' => false];
        }
        $blocking = array_values(array_filter($issues, fn (array $i) => $i['severity'] === 'error'));
        if ($blocking !== []) {
            throw new CardExportBlockedException($blocking);
        }

        $needsAck = $layout->requiresAcknowledgement();
        if ($needsAck && ! $acknowledgeQr) {
            throw new CardExportBlockedException(array_values(array_filter($issues, fn (array $i) => $i['requires_acknowledgement'] ?? false)), acknowledgementRequired: true);
        }

        $bytes = $kind === BusinessCardExport::KIND_FOIL_PLATE
            ? $this->renderer->renderFoilPlate($layout)
            : $this->renderer->renderArtwork($layout);

        $required = $kind === BusinessCardExport::KIND_FOIL_PLATE ? ['All'] : ['Silver_Foil', 'All'];
        $report = PdfPreflight::inspect($bytes, $required);
        if (! $report['ok']) {
            throw new CardExportBlockedException([[
                'severity' => 'error', 'code' => 'preflight_failed', 'side' => null,
                'message' => 'The PDF failed preflight: ' . implode(' ', $report['errors']),
                'requires_acknowledgement' => false,
            ]]);
        }

        $sha = hash('sha256', $bytes);
        $contentHash = $this->contacts->contentHash($contact, $profile, $this->spec->version);
        $path = sprintf(
            '%s/%d/%s-%s-%s.pdf',
            (string) config('business_card.export_directory'),
            $card->id,
            $kind,
            now()->format('Ymd-His'),
            substr($sha, 0, 12),
        );

        Storage::disk((string) config('business_card.export_disk'))->put($path, $bytes);

        $engine = $this->renderer->engine();

        return BusinessCardExport::create([
            'business_card_id' => $card->id,
            'kind' => $kind,
            'file_path' => $path,
            'sha256' => $sha,
            'content_hash' => $contentHash,
            'engine_name' => $engine['name'],
            'engine_version' => $engine['version'],
            'spec_version' => $this->spec->version,
            'qr_profile' => $profile,
            'qr_module_mm' => $layout->qr['module_mm'],
            'qr_acknowledged' => $needsAck && $acknowledgeQr,
            'preflight_report' => $report,
            'issues' => $issues,
            'created_by' => $by?->id,
        ]);
    }
}
