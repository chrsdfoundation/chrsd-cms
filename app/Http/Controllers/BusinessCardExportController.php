<?php

namespace App\Http\Controllers;

use App\Models\BusinessCardExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an exported card PDF from the private disk. Downloads are gated by the card's
 * view permission: a user who cannot open the card cannot open its files either.
 */
class BusinessCardExportController extends Controller
{
    public function download(BusinessCardExport $export): StreamedResponse
    {
        Gate::authorize('view', $export->businessCard);

        $disk = Storage::disk((string) config('business_card.export_disk'));
        abort_unless($disk->exists($export->file_path), 404);

        $name = sprintf('chrsd-business-card-%d-%s-%s.pdf', $export->business_card_id, $export->kind, substr($export->sha256, 0, 12));

        return $disk->download($export->file_path, $name, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
