<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LetterheadController extends Controller
{
    /**
     * Render the CHRSD letterhead shell with a sample letter body so the design
     * can be inspected in the browser at /letterhead-preview before it is used
     * for real letter issuance via the OfficialLetterResource pipeline.
     */
    public function show(): View
    {
        $bodyHtml = <<<'HTML'
<p style="text-align:right; color:#6b7280; margin-bottom:6mm;">4 July 2026</p>

<p><strong>To,</strong><br>
Ms. Fatima Hossain<br>
Programme Officer<br>
BRAC Health, Nutrition and Population Programme<br>
75 Mohakhali, Dhaka-1212</p>

<p><strong>Subject: Confirmation of Partnership on Community Health Outreach 2026</strong></p>

<p>Dear Ms. Hossain,</p>

<p>On behalf of the Centre for Humanitarian Research and Social Development Foundation
(CHRSD), I am pleased to confirm our partnership with BRAC on the upcoming
<em>Community Health Outreach 2026</em> initiative. As discussed on 28 June, the
programme will run for twelve weeks across four upazilas in Rangpur Division and will
serve an estimated 8,400 direct beneficiaries.</p>

<p>The scope of collaboration includes:</p>
<ul>
    <li>Joint field-team deployment (six CHRSD community health workers and four BRAC nurses)</li>
    <li>Shared monitoring dashboard hosted on CHRSD's verification platform</li>
    <li>Quarterly review workshops in Dhaka, funded on a 60/40 CHRSD/BRAC split</li>
    <li>End-of-programme independent evaluation by a mutually appointed third party</li>
</ul>

<p>Our Programme Director, Md. Wasim Jabber, will be your primary liaison and can be
reached at <a href="mailto:wasim@chrsd.org">wasim@chrsd.org</a>. A draft
memorandum of understanding will follow within ten working days for your legal team's
review.</p>

<p>We look forward to a productive partnership and to the impact this programme will
have on the communities we jointly serve.</p>

<p style="margin-top:10mm;">Sincerely,</p>
<p style="margin-top:12mm;"><strong>Md. Abdur Rauf</strong><br>
Executive Director<br>
Centre for Humanitarian Research and Social Development Foundation</p>
HTML;

        return view('documents.templates.letter-shell', [
            'bodyHtml'           => $bodyHtml,
            'orientation'        => 'portrait',
            'letter_reference'   => 'CHRSD-LTR-2026-000047',
            'certificate_number' => null,
            'qr_raw'             => '',
            'template'           => null,
            'shell_variant'      => null,
        ]);
    }
}
