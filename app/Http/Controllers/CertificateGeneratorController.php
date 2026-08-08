<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateRenderer;

class CertificateGeneratorController extends Controller
{
    public function __construct(protected CertificateRenderer $renderer) {}

    /**
     * Preview certificate HTML (for browser printing).
     */
    public function preview(Certificate $certificate)
    {
        $html = $this->renderer->html($certificate);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
