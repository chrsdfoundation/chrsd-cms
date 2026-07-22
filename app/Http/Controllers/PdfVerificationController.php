<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\OfficialLetter;
use App\Services\Documents\PdfSignatureService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfVerificationController extends Controller
{
    public function __construct(
        protected PdfSignatureService $signer,
        protected VerificationService $verifier,
    ) {}

    /**
     * Accept a PDF upload, compute its keyed signature, and check the DB for
     * an issued document whose stored signature matches. Same rate limit as
     * the URL-based verify endpoints — no separate bucket, since a legitimate
     * verifier scans one document at a time.
     *
     * Returns a JSON envelope that mirrors /api/verify/{hash} so third-party
     * integrators only need to handle one response shape.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'], // 20 MB
        ]);

        $bytes = $request->file('pdf')->get();
        $signature = $this->signer->sign($bytes);

        $model = Certificate::query()->acrossOrganizations()
            ->where('pdf_content_hash', $signature)
            ->first();

        if (! $model) {
            $model = OfficialLetter::query()->acrossOrganizations()
                ->where('pdf_content_hash', $signature)
                ->first();
        }

        if (! $model) {
            return response()->json([
                'found'       => false,
                'signature'   => $this->signer->humanFingerprint($signature),
                'reason'      => 'no_matching_document',
            ], 404);
        }

        return response()->json([
            'found'        => true,
            'signature'    => $this->signer->humanFingerprint($signature),
            'snapshot'     => $this->verifier->publicSnapshot($model),
        ]);
    }
}
