<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class PrintCertificateController extends Controller
{
    public function show(Certificate $certificate): View
    {
        $certificate->loadMissing(['employee', 'type', 'documentTemplate']);

        return view('print.certificate', [
            'certificate' => $certificate,
            'employee' => $certificate->employee,
            'type' => $certificate->type,
        ]);
    }
}
