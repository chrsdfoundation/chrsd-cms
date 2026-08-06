<?php

namespace App\Http\Controllers;

use App\Models\OfficialLetter;
use Illuminate\View\View;

class PrintLetterController extends Controller
{
    public function show(OfficialLetter $letter): View
    {
        $letter->loadMissing(['signedBy.position', 'documentTemplate']);

        return view('print.letter', [
            'letter' => $letter,
            'signatory' => $letter->signedBy,
        ]);
    }
}
