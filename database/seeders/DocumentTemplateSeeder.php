<?php

namespace Database\Seeders;

use App\Enums\DocumentTemplateType;
use App\Models\CertificateType;
use App\Models\DocumentTemplate;
use App\Models\IdCardType;
use App\Models\LetterCategory;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCertificates();
        $this->seedLetters();
        $this->seedIdCards();
    }

    protected function seedCertificates(): void
    {
        $trn = CertificateType::where('code', 'TRN')->first();

        // Course Completion (CHRSD green wave variant)
        DocumentTemplate::updateOrCreate(
            ['name' => 'Course Completion Certificate (Green)', 'organization_id' => null],
            [
                'document_type'       => DocumentTemplateType::Certificate->value,
                'certificate_type_id' => $trn?->id,
                'orientation'         => 'landscape',
                'shell_variant'       => 'course-completion',
                'is_default'          => false,
                'body_markdown'       => <<<'MD'
<div class="certify-line">This is to certify that on</div>

<div class="date-line">{{date}}</div>

<div class="name">{{name}}</div>

<div class="divider-ornament"><span class="diamond"></span></div>

<div class="course-lead">successfully completed the course:</div>

### {{course_name}}
MD,
                'sample_context' => [
                    'name'         => 'Mohammed Abu Ramim',
                    'course_name'  => 'M&E Fundamentals',
                    'course'       => 'M&E Fundamentals',
                    'organization' => 'CHRSD Foundation',
                    'signatory_name'  => 'A. Rauf',
                    'signatory_title' => 'Chief Executive Officer',
                    'verify_code'  => '056vHAQgDY',
                ],
            ],
        );

        // Course Completion (Blue wave variant) — matches the Frank Foundation-style reference
        DocumentTemplate::updateOrCreate(
            ['name' => 'Course Completion Certificate (Blue)', 'organization_id' => null],
            [
                'document_type'       => DocumentTemplateType::Certificate->value,
                'certificate_type_id' => $trn?->id,
                'orientation'         => 'landscape',
                'shell_variant'       => 'course-completion-blue',
                'is_default'          => false,
                'body_markdown'       => <<<'MD'
<div class="certify-line">This is to certify that on</div>

<div class="date-line">{{date}}</div>

<div class="name">{{name}}</div>

<div class="divider-ornament"><span class="diamond"></span></div>

<div class="course-lead">successfully completed the course:</div>

### {{course_name}}
MD,
                'sample_context' => [
                    'name'         => 'Mohammed Abu Ramim',
                    'course_name'  => 'M&E Fundamentals',
                    'organization' => 'CHRSD Foundation',
                    'signatory_name'  => 'Miriam Chickering',
                    'signatory_title' => 'Chief Executive Officer',
                    'verify_code'  => '056vHAQgDY',
                    'issuer_name'    => 'CHRSD LEARNING',
                    'issuer_tagline' => 'Global Health Learning Centre',
                ],
            ],
        );


        // 1. Certificate of Appreciation — matches Certificate_sample2.pdf reference
        DocumentTemplate::updateOrCreate(
            ['name' => 'Certificate of Appreciation', 'organization_id' => null],
            [
                'document_type' => DocumentTemplateType::Certificate->value,
                'orientation'   => 'landscape',
                'is_default'    => true,
                'body_markdown' => <<<'MD'
# Certificate of Appreciation

## For Outstanding {{event_name}}

<div class="divider"></div>

*This certificate is proudly presented to*

<div class="name">{{name}}</div>

In recognition of dedicated service as a

### {{designation}}

At the **{{event_name}}** organised by **{{organization}}**
in collaboration with our partners and community.

*Your contribution helped bring meaningful impact to underserved communities and supported our vision of a more resilient nation.*

<table class="sig-tbl">
  <tr>
    <td class="sig-l"><div class="line">{{signatory_left}}</div>Project Coordinator</td>
    <td class="sig-c"><div class="line">{{date}}</div>Date</td>
    <td class="sig-r"><div class="line">{{signatory_right}}</div>Executive Director</td>
  </tr>
</table>
MD,
                'sample_context' => [
                    'name'             => 'MD Mutalib Hossain',
                    'designation'      => 'Logistics & Operations',
                    'event_name'       => 'Volunteer Service',
                    'organization'     => 'CHRSD Foundation',
                    'signatory_left'   => '',
                    'signatory_right'  => '',
                ],
            ],
        );

        // 2. Volunteer Training Certificate — matches Certificxate copy.pdf tone
        DocumentTemplate::updateOrCreate(
            ['name' => 'Volunteer Training Certificate', 'organization_id' => null],
            [
                'document_type'       => DocumentTemplateType::Certificate->value,
                'certificate_type_id' => $trn?->id,
                'orientation'         => 'landscape',
                'is_default'          => false,
                'body_markdown'       => <<<'MD'
# Certificate

## of Training Completion

<div class="divider"></div>

This is to certify that

<div class="name">{{name}}</div>

has successfully completed the **{{event_name}}** programme
conducted over **{{duration}}** by **{{organization}}**.

*Awarded on {{date}} in recognition of their commitment and demonstrated competence.*

<table class="sig-tbl">
  <tr>
    <td class="sig-l"><div class="line">&nbsp;</div>Training Coordinator</td>
    <td class="sig-c"><div class="line">{{date}}</div>Date</td>
    <td class="sig-r"><div class="line">&nbsp;</div>Executive Director</td>
  </tr>
</table>
MD,
                'sample_context' => [
                    'name'         => 'Jane Doe',
                    'event_name'   => 'Community Outreach Training Programme',
                    'duration'     => '3 weeks',
                    'organization' => 'CHRSD Foundation',
                ],
            ],
        );

        // 3. Achievement Certificate (portrait variant)
        DocumentTemplate::updateOrCreate(
            ['name' => 'Certificate of Achievement', 'organization_id' => null],
            [
                'document_type' => DocumentTemplateType::Certificate->value,
                'orientation'   => 'portrait',
                'is_default'    => false,
                'body_markdown' => <<<'MD'
# Certificate

## of Achievement

<div class="divider"></div>

Presented to

<div class="name">{{name}}</div>

for outstanding achievement in **{{event_name}}**.

*Your dedication and excellence set a standard for others to follow.
{{organization}} recognises and celebrates your contribution.*

<table class="sig-tbl">
  <tr>
    <td class="sig-l"><div class="line">&nbsp;</div>Programme Coordinator</td>
    <td class="sig-c"><div class="line">{{date}}</div>Date</td>
    <td class="sig-r"><div class="line">&nbsp;</div>Executive Director</td>
  </tr>
</table>
MD,
                'sample_context' => [
                    'name'         => 'Alex Rahman',
                    'event_name'   => 'the Annual Volunteer Awards 2026',
                    'organization' => 'CHRSD Foundation',
                ],
            ],
        );
    }

    protected function seedLetters(): void
    {
        $appointmentCategory = LetterCategory::where('code', 'like', '%APP%')
            ->orWhere('name', 'like', '%Appointment%')->first();

        // 1. Advisory Board Invitation — matches Letter to Md Wasim Jabber.pdf
        DocumentTemplate::updateOrCreate(
            ['name' => 'Advisory Board Invitation', 'organization_id' => null],
            [
                'document_type' => DocumentTemplateType::Letter->value,
                'orientation'   => 'portrait',
                'is_default'    => false,
                'body_markdown' => <<<'MD'
Date: **{{date}}**

<p><strong>{{recipient_name}}</strong><br>
<em>{{recipient_title}}</em><br>
{{recipient_address}}</p>

### {{subject}}

Dear {{recipient_name}},

On behalf of the **Centre for Humanitarian Research and Social Development Foundation (CHRSD)**, I am honoured to extend to you a formal invitation to join our esteemed Advisory Board. Your distinguished career and institutional experience have earned you remarkable recognition in Bangladesh's academic and development sectors.

CHRSD is committed to advancing evidence-based humanitarian research, implementing impactful social development programmes, and advocating for policy reforms that empower vulnerable communities across Bangladesh. Our mission is rooted in the belief that sustainable development requires both rigorous research and compassionate action.

As a member of our Advisory Board, you would play a pivotal role in shaping the strategic direction of CHRSD — providing counsel on programmatic priorities, financial oversight, and partnership development. Your participation would involve attending quarterly board meetings, reviewing organisational reports, and offering expert recommendations on critical decisions affecting our humanitarian initiatives.

We would be delighted to discuss the role and its expectations at your convenience.

Yours sincerely,

**{{signatory_name}}**
*{{signatory_title}}*
{{organization}}
MD,
                'sample_context' => [
                    'recipient_name'   => 'Md. Wasim Jabber',
                    'recipient_title'  => 'Treasurer',
                    'recipient_address'=> "Independent University, Bangladesh\nPlot 16, Block B, Aftabuddin Ahmed Road\nBashundhara R/A, Dhaka 1245, Bangladesh.",
                    'subject'          => 'Invitation to Join the Advisory Board of CHRSD.',
                    'signatory_name'   => 'A. Rauf',
                    'signatory_title'  => 'Executive Director',
                    'organization'     => 'CHRSD Foundation',
                ],
            ],
        );

        // 2. Standard Appointment Letter
        DocumentTemplate::updateOrCreate(
            ['name' => 'Standard Appointment Letter', 'organization_id' => null],
            [
                'document_type'      => DocumentTemplateType::Letter->value,
                'letter_category_id' => $appointmentCategory?->id,
                'orientation'        => 'portrait',
                'is_default'         => true,
                'body_markdown'      => <<<'MD'
Date: **{{date}}**

<p><strong>{{recipient_name}}</strong><br>
<em>{{recipient_title}}</em><br>
{{recipient_address}}</p>

### {{subject}}

Dear {{recipient_name}},

{{body}}

We look forward to your acceptance and to your contribution towards the mission of {{organization}}.

Yours sincerely,

**{{signatory_name}}**
*{{signatory_title}}*
{{organization}}
MD,
                'sample_context' => [
                    'recipient_name'   => 'Alex Rahman',
                    'recipient_title'  => 'Field Officer',
                    'recipient_address'=> '123 Sample Road, Dhaka.',
                    'subject'          => 'Offer of Appointment',
                    'body'             => 'We are pleased to appoint you to the position of **Field Officer**, effective from the date of this letter. Your reporting supervisor will be the Programme Coordinator, and your primary responsibilities will include participation in community outreach programmes, field data collection, and volunteer coordination.',
                    'signatory_name'   => 'A. Rauf',
                    'signatory_title'  => 'Executive Director',
                    'organization'     => 'CHRSD Foundation',
                ],
            ],
        );

        // 3. Experience Letter
        DocumentTemplate::updateOrCreate(
            ['name' => 'Experience Letter', 'organization_id' => null],
            [
                'document_type'  => DocumentTemplateType::Letter->value,
                'orientation'    => 'portrait',
                'is_default'     => false,
                'body_markdown'  => <<<'MD'
Date: **{{date}}**

### To Whom It May Concern

This is to certify that **{{name}}** was engaged with **{{organization}}** as a **{{designation}}** during the period stated below.

| | |
|---|---|
| **Employee ID** | {{employee_id}} |
| **Position held** | {{position}} |
| **Duration** | {{duration}} |
| **Department** | {{department}} |

During this tenure, {{name}} demonstrated professionalism, a strong work ethic, and a collaborative approach. They contributed meaningfully to our humanitarian initiatives and were regarded as a valued member of the team.

We wish {{name}} continued success in their future endeavours.

Yours sincerely,

**{{signatory_name}}**
*{{signatory_title}}*
{{organization}}
MD,
                'sample_context' => [
                    'name'            => 'Alex Rahman',
                    'designation'     => 'Field Officer',
                    'position'        => 'Field Officer',
                    'department'      => 'Programmes',
                    'employee_id'     => 'CHRSD-EMP-2026-0007',
                    'duration'        => '1 Jan 2024 – 30 Jun 2026',
                    'organization'    => 'CHRSD Foundation',
                    'signatory_name'  => 'A. Rauf',
                    'signatory_title' => 'Executive Director',
                ],
            ],
        );
    }

    protected function seedIdCards(): void
    {
        $empType = IdCardType::where('code', 'EMP')->first();
        $volType = IdCardType::where('code', 'VOL')->first();
        $visType = IdCardType::where('code', 'VIS')->first();

        // 1. Employee ID Card — matches ID CARD-102-RAMIM design
        DocumentTemplate::updateOrCreate(
            ['name' => 'Employee ID Card', 'organization_id' => null],
            [
                'document_type'   => DocumentTemplateType::IdCard->value,
                'id_card_type_id' => $empType?->id,
                'orientation'     => 'landscape',
                'is_default'      => true,
                'body_markdown'   => <<<'MD'
## {{name}}

*{{designation}}*

<table>
<tr><td class="k">ID No</td><td>: <strong>{{certificate_number}}</strong></td></tr>
<tr><td class="k">Blood Group</td><td>: <strong>{{blood_group}}</strong></td></tr>
<tr><td class="k">Nationality</td><td>: <strong>{{nationality}}</strong></td></tr>
<tr><td class="k">Issue Date</td><td>: <strong>{{valid_from}}</strong></td></tr>
<tr><td class="k">Expiry Date</td><td>: <strong>{{valid_until}}</strong></td></tr>
</table>
MD,
                'sample_context' => [
                    'name'         => 'M.A. Ramim',
                    'designation'  => 'Executive Director',
                    'blood_group'  => 'O Positive',
                    'nationality'  => 'Bangladeshi',
                ],
            ],
        );

        // 2. Volunteer ID Card — programme-focused
        DocumentTemplate::updateOrCreate(
            ['name' => 'Volunteer ID Card', 'organization_id' => null],
            [
                'document_type'   => DocumentTemplateType::IdCard->value,
                'id_card_type_id' => $volType?->id,
                'orientation'     => 'landscape',
                'is_default'      => true,
                'body_markdown'   => <<<'MD'
## {{name}}

*Volunteer — {{program_name}}*

<table>
<tr><td class="k">Volunteer ID</td><td>: <strong>{{certificate_number}}</strong></td></tr>
<tr><td class="k">Programme</td><td>: <strong>{{program_name}}</strong></td></tr>
<tr><td class="k">Blood Group</td><td>: <strong>{{blood_group}}</strong></td></tr>
<tr><td class="k">Valid Until</td><td>: <strong>{{valid_until}}</strong></td></tr>
</table>
MD,
                'sample_context' => [
                    'name'         => 'Jane Doe',
                    'program_name' => 'Community Outreach',
                    'blood_group'  => 'A+',
                ],
            ],
        );

        // 3. Visitor / Temporary ID Card — short validity, purpose-focused
        DocumentTemplate::updateOrCreate(
            ['name' => 'Visitor / Temporary ID Card', 'organization_id' => null],
            [
                'document_type'   => DocumentTemplateType::IdCard->value,
                'id_card_type_id' => $visType?->id,
                'orientation'     => 'landscape',
                'is_default'      => true,
                'body_markdown'   => <<<'MD'
## {{name}}

*Visitor Pass*

<table>
<tr><td class="k">Visitor No</td><td>: <strong>{{certificate_number}}</strong></td></tr>
<tr><td class="k">Purpose</td><td>: <strong>{{program_name}}</strong></td></tr>
<tr><td class="k">Issue Date</td><td>: <strong>{{valid_from}}</strong></td></tr>
<tr><td class="k">Valid Until</td><td>: <strong>{{valid_until}}</strong></td></tr>
</table>
MD,
                'sample_context' => [
                    'name'         => 'Karim Uddin',
                    'program_name' => 'Site Visit — Q3 Audit',
                    'nationality'  => 'Bangladeshi',
                    'valid_from'   => now()->toFormattedDateString(),
                    'valid_until'  => now()->addWeek()->toFormattedDateString(),
                ],
            ],
        );
    }
}
