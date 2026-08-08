<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateVerificationController extends Controller
{
    /**
     * Verify a certificate by hash (anonymous, rate-limited).
     */
    public function verify(string $hash)
    {
        $certificate = Certificate::where('verification_hash', $hash)->first();

        if (! $certificate) {
            return response()->view('certificates.verify-not-found', [], 404);
        }

        if ($certificate->revoked_at) {
            return response()->view('certificates.verify-revoked', [
                'certificate' => $certificate,
            ]);
        }

        return view('certificates.verify-valid', [
            'certificate' => $certificate,
        ]);
    }
}
