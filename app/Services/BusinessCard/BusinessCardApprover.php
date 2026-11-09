<?php

namespace App\Services\BusinessCard;

use App\Enums\BusinessCardStatus;
use App\Models\BusinessCard;
use App\Models\BusinessCardExport;
use App\Models\User;
use RuntimeException;

/**
 * Approval for press and the "sent to press" step.
 *
 * Approval needs three things: the card has no layout or QR errors, an artwork export
 * exists for its exact current content (so the approval covers the file that was
 * checked), and any QR acknowledgement was recorded on that export. Both steps write
 * the approver and the time to the card.
 */
final class BusinessCardApprover
{
    public function __construct(
        private readonly BusinessCardContactFactory $contacts,
        private readonly CardVCardBuilder $vcard,
        private readonly CardLayoutEngine $engine,
        private readonly CardConsistencyChecker $consistency,
        private readonly CardSpec $spec,
    ) {}

    /**
     * @throws CardExportBlockedException
     * @throws RuntimeException
     */
    public function approve(BusinessCard $card, User $approver): void
    {
        $this->assertNoBlockingIssues($card);

        $contact = $this->contacts->fromCard($card);
        $profile = $card->qr_profile ?: CardVCardBuilder::PROFILE_COMPACT;
        $hash = $this->contacts->contentHash($contact, $profile, $this->spec->version);

        $export = BusinessCardExport::query()
            ->where('business_card_id', $card->id)
            ->where('kind', BusinessCardExport::KIND_ARTWORK)
            ->where('content_hash', $hash)
            ->latest('id')
            ->first();

        if ($export === null) {
            throw new RuntimeException('Export the print artwork for the current card data before approving it.');
        }
        if (($export->preflight_report['ok'] ?? false) !== true) {
            throw new RuntimeException('The last artwork export failed preflight, so the card cannot be approved.');
        }
        if ($export->qr_module_mm !== null && $export->qr_module_mm < (float) config('business_card.qr_min_module_mm') && ! $export->qr_acknowledged) {
            throw new RuntimeException('The QR modules are below the minimum size and the export was not acknowledged.');
        }

        $card->forceFill([
            'status' => BusinessCardStatus::Approved,
            'approved_by_id' => $approver->id,
            'approved_at' => now(),
        ])->save();
    }

    public function markSentToPress(BusinessCard $card): void
    {
        if ($card->status !== BusinessCardStatus::Approved) {
            throw new RuntimeException('Only an approved card can be sent to press.');
        }

        $card->forceFill([
            'status' => BusinessCardStatus::SentToPress,
            'sent_to_press_at' => now(),
        ])->save();
    }

    private function assertNoBlockingIssues(BusinessCard $card): void
    {
        $contact = $this->contacts->fromCard($card);
        $profile = $card->qr_profile ?: CardVCardBuilder::PROFILE_COMPACT;
        $vcard = $this->vcard->build($contact, $profile);
        $layout = $this->engine->build($contact, $vcard, $profile, 'artwork');

        $issues = $layout->errors();
        foreach ($this->consistency->check($contact, $vcard, $profile) as $message) {
            $issues[] = ['severity' => 'error', 'code' => 'qr_mismatch', 'side' => null, 'message' => $message, 'requires_acknowledgement' => false];
        }

        if ($issues !== []) {
            throw new CardExportBlockedException($issues);
        }
    }
}
