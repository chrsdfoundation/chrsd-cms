<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class CertificateController extends Controller
{
    /**
     * Render the course-completion certificate with sample data so it can be
     * previewed directly in a browser at /certificate-preview.
     */
    public function show(): View
    {
        return view('certificates.course_completion', [
            'recipientName' => 'Dr. Amina Rahman',
            'courseName' => 'Global Public Health Fundamentals',
            'completionDate' => Carbon::create(2026, 5, 17),
            'signatures' => [
                ['name' => null, 'title' => 'Program Director',                'image' => null],
                ['name' => null, 'title' => 'Training Coordinator',            'image' => null],
                ['name' => null, 'title' => 'Frank Foundation Representative', 'image' => null],
            ],
        ]);
    }

    /**
     * Preview the CHRSD premium-gold certificate at /certificate-preview/chrsd.
     * Every placeholder has a defaulted sample value so the design can be
     * inspected without wiring a real Certificate model.
     */
    /**
     * Preview the premium dark-navy + gold master layout at
     * /certificate-preview/premium. Uses the reusable
     * `certificates.layouts.premium` layout via `premium_sample` which
     * demonstrates how any certificate view can extend the same background.
     */
    public function showPremium(): View
    {
        return view('certificates.premium_sample', [
            'issuer' => 'The CHRSD Foundation',
            'title' => 'Certificate',
            'subtitle' => 'of Recognition & Achievement',
            'name' => 'Alexander James Whitfield',
            'description' => 'Has demonstrated exceptional dedication and outstanding accomplishment in the field of Humanitarian Service, and is hereby awarded this certificate in grateful acknowledgement of exemplary commitment to the betterment of our community.',
            'signatory_1_name' => 'Md. Abdur Rauf',
            'signatory_1_role' => 'Executive Director',
            'signatory_2_name' => 'Prof. Nafisa Islam',
            'signatory_2_role' => 'Chair, Academic Board',
            'issue_date_text' => Carbon::create(2026, 6, 18)->format('jS F Y'),
            'certificate_no' => 'CERT-2026-000042',
            'verify_url' => url('/verify/ref/CERT-2026-000042'),
        ]);
    }

    public function showChrsd(): View
    {
        return view('certificates.chrsd_certificate', [
            'name' => 'Dr. Sarah Ahmed',
            'certificate_no' => 'CERT-2026-000042',
            'course_name' => 'Advanced Humanitarian Response & Field Coordination',
            'issue_date' => Carbon::create(2026, 6, 18),
            'signatory_name' => 'Razib Mustafiz',
            'signatory_title' => 'Project Coordinator',
            'countersign_name' => 'M.A. Ramim',
            'countersign_title' => 'Executive Director',
            'verification_url' => url('/verify/ref/CERT-2026-000042'),
            'org_name' => 'CHRSD',
            'org_full' => 'Centre for Humanitarian Research and Social Development Foundation',
            'reg_no' => 'S-14480/2026',
        ]);
    }
}
