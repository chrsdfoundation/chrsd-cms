<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\OfficialLetter;
use App\Services\Documents\HtmlSignatureService;
use App\Services\QrCodeService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskVerifyController extends Controller
{
    public function __construct(
        protected VerificationService $verifier,
        protected HtmlSignatureService $signer,
        protected QrCodeService $qr,
    ) {}

    public function show(): View
    {
        return view('kiosk.index', $this->baseData([]));
    }

    /**
     * One endpoint, three input methods (serial / hash / PDF upload).
     * Resolves the document via the same VerificationService the URL endpoints
     * use, so state (Valid/Revoked/Expired) is guaranteed consistent.
     */
    public function verify(Request $request): View
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:128'],
            'pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:20480'],
        ]);

        $model = null;

        if ($request->hasFile('pdf')) {
            $sig = $this->signer->sign($request->file('pdf')->get());
            $model = Certificate::query()->acrossOrganizations()
                ->where('pdf_content_hash', $sig)->first()
                ?? OfficialLetter::query()->acrossOrganizations()
                    ->where('pdf_content_hash', $sig)->first();
        } elseif (! empty($data['query'])) {
            $q = trim($data['query']);

            if (strlen($q) === 64 && ctype_xdigit($q)) {
                $model = $this->verifier->resolve($q);
            } else {
                foreach ($this->verifier->registry() as $class) {
                    $model = $class::query()->acrossOrganizations()
                        ->where('serial_number', $q)->first();
                    if ($model) {
                        break;
                    }
                }
            }
        }

        return view('kiosk.index', $this->baseData([
            'attempted' => true,
            'model' => $model,
            'snapshot' => $model ? $this->verifier->publicSnapshot($model) : null,
            'qr_svg' => $model ? $this->qr->svg($model, 5) : null,
            'query' => $data['query'] ?? null,
        ]));
    }

    protected function baseData(array $extra): array
    {
        return array_merge([
            'app' => config('app.name'),
            'attempted' => false,
            'model' => null,
            'snapshot' => null,
            'qr_svg' => null,
            'query' => null,
        ], $extra);
    }
}
