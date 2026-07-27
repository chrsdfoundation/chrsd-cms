<?php

// Auto-generated from the local CMS database (2026-07-27) to sync
// document data into version control. Consumed by the seeders.

return [
    0 => [
        'name' => 'Certificate of Service',
        'document_type' => 'certificate',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => 'SR',
        'letter_category_code' => null,
        'id_card_type_code' => null,
        'body_markdown' => '# Certificate of Service

<div class="divider"></div>

### To Whom It May Concern

This is to certify that **{{name}}** was employed by the **Centre for Humanitarian Research and Social Development Foundation (CHRSD)** as **{{designation}}** in the **{{department}}** Department during the period of service stated below.

### Employment Details

| Item | Detail |
|---|---|
| **Employee ID** | {{employee_id}} |
| **Designation** | {{designation}} |
| **Department** | {{department}} |
| **Duration of Service** | {{duration}} |
| **Employment Type** | {{position}} |

During this tenure, {{name}} demonstrated professionalism, integrity, dedication, and a strong commitment to CHRSD\'s humanitarian mission throughout the period of service. Their contribution to programme delivery, teamwork, and organisational objectives is gratefully acknowledged.

We sincerely wish {{name}} every success in all future professional endeavours.

<table class="sig-tbl">
  <tr>
    <td class="sig-l">
      <img class="sig-img" src="{{{signatory_1_sig}}}" alt="">
      <div class="line">{{signatory_1_name}}</div>
      <div class="role">{{signatory_1_title}}</div>
    </td>
    <td class="sig-c">
      <div class="line">{{date}}</div>
      <div class="role">Date of Issue</div>
    </td>
    <td class="sig-r">
      <img class="sig-img" src="{{{signatory_2_sig}}}" alt="">
      <div class="line">{{signatory_2_name}}</div>
      <div class="role">{{signatory_2_title}}</div>
    </td>
  </tr>
</table>',
        'sample_context' => [
            'name' => '[Employee Full Name]',
            'employee_id' => 'CHRSD-EMP-YYYY-####',
            'designation' => '[Designation]',
            'department' => '[Programme / Division]',
            'duration' => '[DD Month YYYY] – [DD Month YYYY]',
            'position' => 'Full-Time',
            'signatory_1_name' => 'Farhana Kabir',
            'signatory_1_title' => 'HR Manager',
            'signatory_2_name' => 'A. Rauf',
            'signatory_2_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
        ],
    ],
    1 => [
        'name' => 'Training / Internship Completion Certificate',
        'document_type' => 'certificate',
        'orientation' => 'landscape',
        'shell_variant' => 'course-completion',
        'is_default' => false,
        'certificate_type_code' => 'TRN',
        'letter_category_code' => null,
        'id_card_type_code' => null,
        'body_markdown' => '<div class="certify-line">This is to certify that</div>

<div class="name">{{name}}</div>

<div class="divider-ornament"><span class="diamond"></span></div>

<div class="course-lead">has successfully completed the</div>

### {{course_name}}

conducted by **{{organization}}** from **{{valid_from}}** to **{{valid_until}}** ({{duration}}).

*Awarded on {{date}} in recognition of demonstrated learning, engagement, and professional conduct throughout the programme.*',
        'sample_context' => [
            'name' => '[Trainee / Intern Full Name]',
            'course_name' => '[Programme Name — e.g., Internship in Environmental Monitoring]',
            'duration' => '[e.g., 3 months]',
            'valid_from' => '[DD Month YYYY]',
            'valid_until' => '[DD Month YYYY]',
            'organization' => 'CHRSD Foundation',
            'signatory_name' => 'A. Rauf',
            'signatory_title' => 'Executive Director',
        ],
    ],
    2 => [
        'name' => 'Authorization Letter',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/CORP/AUTH',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Authorization to Act on Behalf of CHRSD — [Matter / Transaction]',
            'body' => '<p><strong>To Whom It May Concern,</strong></p>
<p>The Centre for Humanitarian Research and Social Development Foundation (CHRSD) hereby authorises <strong>[Authorised Person\'s Full Name]</strong> (NID / Passport No. <strong>[NID / Passport No.]</strong>), holding the position of <strong>[Designation]</strong> at CHRSD, to <strong>act and sign on behalf of CHRSD</strong> for the following purpose:</p>
<blockquote><strong>[Describe the matter clearly — e.g., collect the shipment from customs, sign the vendor agreement with X, operate the CHRSD account No. 000000 at Bank Y, represent CHRSD at Meeting Z on DD Month YYYY.]</strong></blockquote>
<p>This authorisation is valid from <strong>[Start Date]</strong> to <strong>[End Date]</strong>, and is limited strictly to the matter described above. Actions taken beyond this scope are not authorised and do not bind CHRSD.</p>
<p>Any counter-party dealing with the authorised person may verify this authorisation by scanning the QR code below, visiting the verification URL, or writing to <strong>info@chrsd.org</strong>.</p>
<p>Please extend all necessary cooperation to the authorised person in carrying out the above matter.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => 'To Whom It May Concern',
            'recipient_title' => '',
            'recipient_address' => '',
        ],
    ],
    3 => [
        'name' => 'Internship Offer Letter',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/INT',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Offer of Internship — [Programme Name]',
            'body' => '<p>We are pleased to offer you an internship position with the Centre for Humanitarian Research and Social Development Foundation (CHRSD) under the <strong>[Programme Name]</strong>, following your application and interview.</p>
<p><strong>1. Terms of the Internship</strong></p>
<p>| Field | Detail |<br>| --- | --- |<br>| Internship Title | [Internship Title] |<br>| Programme / Division | [Programme / Division Name] |<br>| Supervisor | [Supervisor Name &amp; Title] |<br>| Duty Station | [Duty Station — e.g., Dhaka Head Office] |<br>| Duration | [Start Date] – [End Date] |<br>| Weekly Hours | [e.g., 30 hours, flexible] |<br>| Stipend | BDT [Amount] per month / not applicable |</p>
<p><strong>2. Scope of Work</strong></p>
<p>You will support the [Programme] team with research assistance, field data collection, report drafting, and other tasks assigned by your supervisor. A structured learning plan will be shared in your first week.</p>
<p><strong>3. Conduct &amp; Confidentiality</strong></p>
<p>You will comply with CHRSD\'s Code of Conduct, Safeguarding Policy, and confidentiality obligations for all beneficiary, donor, and programmatic information — both during and after the internship.</p>
<p><strong>4. Completion</strong></p>
<p>Upon satisfactory completion, you will receive a <em>Training / Internship Completion Certificate</em> and a written performance summary from your supervisor.</p>
<p><strong>5. Acceptance</strong></p>
<p>Please sign and return the duplicate copy of this letter as your acceptance of the above terms and report to the HR Department on your start date with the following: NID/passport photocopy, two passport-size photographs, and academic transcript.</p>
<p>We welcome you to CHRSD and look forward to a productive engagement.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
        ],
    ],
    4 => [
        'name' => 'Joining Letter — CHRSD HR',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/JL',
        'id_card_type_code' => null,
        'body_markdown' => '<div class="ref-line" style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p style="text-align:center;font-weight:bold;color:#0f3b1c;letter-spacing:0.5px;margin-bottom:12px;">Strictly Private &amp; Confidential</p>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

We warmly welcome you to CHRSD and look forward to a long, productive, and mutually rewarding association as we advance humanitarian research and social development together.

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>

<hr style="margin:40px 0 20px;border:0;border-top:1px dashed #999;">

**Acceptance by Employee**

I, **{{recipient_name}}**, have read and understood the above terms and conditions of appointment and hereby accept the same.

<div style="margin-top:24px;line-height:1.9;">
______________________________<br>
Signature: <strong>{{recipient_name}}</strong><br>
Date: ______________________
</div>',
        'sample_context' => [
            'subject' => 'Appointment as [Designation] at Centre for Humanitarian Research and Social Development Foundation (CHRSD)',
            'recipient_name' => '[Candidate Full Name]',
            'recipient_title' => '',
            'recipient_address' => '[Candidate Present Address]
[City, Country]',
            'body' => '<p><strong>1. Opening &amp; Offer of Appointment</strong></p>
<p>We are delighted to offer you appointment as <strong>[Designation]</strong> at the Centre for Humanitarian Research and Social Development Foundation (CHRSD), following your interview and selection. Your background, skills, and commitment to humanitarian and social-development work fit CHRSD\'s mission closely, and we are confident you will make a real contribution to our team.</p>
<p>This letter sets out the key terms and conditions of your appointment, effective from the date of joining confirmed below.</p>

<p><strong>2. Position &amp; Reporting</strong></p>
<p>| Field | Detail |</p>
<p>| --- | --- |</p>
<p>| Employee Name | [Candidate Full Name] |</p>
<p>| Designation | [Designation] |</p>
<p>| Department / Division | [Programme / Division Name] |</p>
<p>| Reporting To | [Line Manager Name &amp; Title — e.g., Executive Director] |</p>
<p>| Duty Station | [Duty Station — e.g., Dhaka Head Office, with field travel] |</p>
<p>| Employment Type | [Full-Time / Contractual / Project-Based] |</p>
<p>| Date of Joining | [DD Month YYYY] |</p>

<p><strong>3. Probation Period</strong></p>
<p>You will serve a probation period of <strong>[three (3) / six (6)]</strong> months from your date of joining. During this time your performance and conduct will be reviewed, and either party may end the appointment with <strong>[one/two]</strong> week[s] written notice without assigning reason. On satisfactory completion, your employment will be confirmed in writing.</p>

<p><strong>4. Remuneration &amp; Benefits</strong></p>
<p>| Component | Detail |</p>
<p>| --- | --- |</p>
<p>| Gross Monthly Salary | BDT <strong>[Amount in figures]</strong> (Taka <em>[amount in words]</em> only) |</p>
<p>| Salary Breakdown | Basic [Amount] · House Rent [Amount] · Medical &amp; Other Allowances [Amount] |</p>
<p>| Payment Cycle | Monthly, by bank transfer, on the last working day of each month |</p>
<p>| Provident Fund | [Applicable / Not Applicable — per CHRSD HR Policy] |</p>
<p>| Festival Bonus | [Two (2) festival bonuses per year, per policy] |</p>
<p>| Other Benefits | [Health / life cover, mobile, travel / field allowance, as applicable] |</p>
<p>All salary and benefits are subject to applicable statutory deductions and to periodic review in line with CHRSD\'s compensation policy and available donor / project funding.</p>

<p><strong>5. Working Hours &amp; Leave</strong></p>
<ul>
  <li>Standard hours: <strong>[Sunday–Thursday, 9:00 AM – 5:00 PM]</strong>, with flexibility as programme and field commitments require.</li>
  <li>You are entitled to annual, sick, casual, and other leave categories set out in the CHRSD Human Resources Policy Manual.</li>
  <li>Field travel, including to project sites outside <strong>[Dhaka]</strong>, may be required from time to time.</li>
</ul>

<p><strong>6. Duties &amp; Responsibilities</strong></p>
<p>As <strong>[Designation]</strong>, you will plan, implement, monitor, and report on assigned programmes and projects; coordinate with internal teams, partners, and donors; ensure compliance with organisational and donor policies; and supervise programme staff as required. A detailed Job Description forms Annex A to this letter and will be discussed at joining.</p>

<p><strong>7. Code of Conduct, Policies &amp; Confidentiality</strong></p>
<ul>
  <li>You will comply with CHRSD\'s Code of Conduct, Safeguarding Policy, Anti-Fraud and Anti-Corruption Policy, and all other policies in force from time to time.</li>
  <li>You will keep all organisational, programmatic, beneficiary, and donor information strictly confidential, during and after your employment.</li>
  <li>Any conflict of interest must be disclosed promptly to your supervisor and the HR Department.</li>
</ul>

<p><strong>8. Termination of Employment</strong></p>
<p>After confirmation, either party may end this appointment with <strong>[one (1) month\'s]</strong> written notice, or salary in lieu. CHRSD may terminate employment without notice for gross misconduct, breach of the Code of Conduct or Safeguarding Policy, or as otherwise permitted under applicable law and CHRSD\'s HR Policy.</p>

<p><strong>9. Documents Required at Joining</strong></p>
<ul>
  <li>Signed copy of this Joining Letter as acceptance of terms</li>
  <li>Attested copies of academic certificates and transcripts</li>
  <li>National ID Card / Passport (photocopy)</li>
  <li>Two recent passport-size photographs</li>
  <li>Experience certificate / release letter from previous employer (if applicable)</li>
  <li>Bank account details for salary disbursement</li>
  <li>Two reference / character certificates</li>
</ul>

<p><strong>10. Acceptance &amp; Next Steps</strong></p>
<p>Please sign and return the duplicate copy of this letter as your acceptance of the above terms, and report to the HR Department on your date of joining with the documents listed above. Kindly return the signed acceptance by <strong>[Response Date]</strong>. For any questions, please contact <strong>[HR Contact Name]</strong> at <strong>[HR Email / Phone]</strong>.</p>',
            'signatory_name' => 'A. Rauf',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
        ],
    ],
    5 => [
        'name' => 'Leave Approval / Travel Authorization',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/LTA',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Approval of Leave / Travel Authorization — [DD Month YYYY] to [DD Month YYYY]',
            'body' => '<p>Reference: your leave / travel application dated <strong>[Application Date]</strong>.</p>
<p>This is to confirm that your <strong>[Annual / Casual / Sick / Study / Personal]</strong> leave has been <strong>approved</strong> from <strong>[Start Date]</strong> to <strong>[End Date]</strong>, totalling <strong>[Number]</strong> working days.</p>
<p><strong>Travel authorisation:</strong> [If applicable] You are further authorised to travel to <strong>[Destination]</strong> during this period in [official / personal] capacity. Where the travel is official, CHRSD will meet reasonable expenses per the Travel &amp; Reimbursement Policy; where it is personal, no CHRSD funds are committed.</p>
<p>Please ensure that:</p>
<ul>
<li>your handover note is shared with <strong>[Alternate Contact / Line Manager]</strong> before your last working day;</li>
<li>you remain reachable for any critical matter at <strong>[Contact Details During Leave]</strong>;</li>
<li>upon return, you submit a brief trip report (for official travel) within five (5) working days.</li>
</ul>
<p>Kindly acknowledge receipt of this authorisation by return email. We wish you a productive and safe trip.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => '[Employee Full Name]',
            'recipient_title' => '[Designation]',
            'recipient_address' => '[Department / Programme]
CHRSD, Dhaka',
        ],
    ],
    6 => [
        'name' => 'MoU / Partnership Letter',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/CORP/MOU',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Proposal for a Memorandum of Understanding — [Partner Name]',
            'body' => '<p>The Centre for Humanitarian Research and Social Development Foundation (CHRSD) is pleased to propose a formal partnership with <strong>[Partner Organisation Name]</strong> through a Memorandum of Understanding (MoU) aimed at [briefly describe shared purpose — e.g., advancing community-based environmental monitoring, expanding educational access for underserved learners, or strengthening humanitarian research collaboration in Bangladesh].</p>
<p><strong>1. Areas of Collaboration</strong></p>
<ul>
<li>Joint programme design and delivery in <strong>[Thematic Area(s)]</strong></li>
<li>Shared research, data collection, and publications</li>
<li>Capacity-building — training, exchanges, and technical assistance</li>
<li>Joint fundraising, donor engagement, and communication</li>
</ul>
<p><strong>2. Term &amp; Governance</strong></p>
<p>The proposed MoU will be effective from <strong>[Start Date]</strong> for a period of <strong>[e.g., two (2) years]</strong>, renewable by mutual written consent. A joint steering group of two representatives from each side will meet quarterly to review progress and address issues.</p>
<p><strong>3. Financial Arrangements</strong></p>
<p>Unless separately agreed in writing, each partner shall bear its own costs of participation. Any joint budgets, cost-sharing, or transfers will be documented through specific activity agreements attached to this MoU.</p>
<p><strong>4. Confidentiality, Ethics &amp; Safeguarding</strong></p>
<p>Both partners agree to observe strict confidentiality of shared information and to comply with each other\'s safeguarding, anti-fraud, and data-protection policies during the term of the MoU.</p>
<p><strong>5. Next Steps</strong></p>
<p>We would welcome the opportunity to discuss this proposal at a meeting convenient to you, following which our legal teams can finalise the MoU text. For any clarification please contact the undersigned directly at <strong>info@chrsd.org</strong>.</p>
<p>We look forward to building a productive and mutually rewarding partnership.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => '[Partner Contact Name]',
            'recipient_title' => '[Partner Contact Title]',
            'recipient_address' => '[Partner Organisation]
[Address]
[City, Country]',
        ],
    ],
    7 => [
        'name' => 'No Objection Certificate — Travel',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/NOC',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'No Objection Certificate for International Travel — [Employee Full Name]',
            'body' => '<p><strong>To Whom It May Concern,</strong></p>
<p>This is to certify that <strong>[Employee Full Name]</strong> (Passport No. <strong>[Passport No.]</strong>), holding Employee ID <strong>[Employee ID]</strong>, is a full-time employee of the Centre for Humanitarian Research and Social Development Foundation (CHRSD), currently serving as <strong>[Designation]</strong> in the <strong>[Department / Programme]</strong>.</p>
<p>CHRSD has <strong>no objection</strong> to [Employee First Name] travelling to <strong>[Destination Country]</strong> during the period <strong>[DD Month YYYY]</strong> to <strong>[DD Month YYYY]</strong> for <strong>[Purpose of travel — personal / official / conference / training]</strong>.</p>
<p>The employee will remain on the rolls of CHRSD during this period and will resume duties on return. All travel-related expenses, unless separately confirmed by CHRSD in writing, shall be borne by the employee.</p>
<p>This certificate is issued at the employee\'s request and does not confer any additional financial or legal obligations upon CHRSD.</p>
<p>Should any authority require verification, please contact at the top right hand corner of this letter.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'Centre for Humanitarian Research and Social Development Foundation',
            'recipient_name' => 'Visa Officer',
            'recipient_title' => '',
            'recipient_address' => '',
        ],
    ],
    8 => [
        'name' => 'Recommendation Letter',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/REC',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Letter of Recommendation for [Candidate Full Name]',
            'body' => '<p><strong>To Whom It May Concern,</strong></p>
<p>It is my pleasure to recommend <strong>[Candidate Full Name]</strong> for <strong>[Purpose — e.g., admission to your Master\'s programme in Public Health / a position at your organisation / a partnership opportunity]</strong>. I have known [him/her/them] in my capacity as <strong>[Your Relationship — e.g., their supervisor / programme lead / faculty mentor]</strong> at the Centre for Humanitarian Research and Social Development Foundation (CHRSD) from <strong>[Start Date]</strong> to <strong>[End Date]</strong>.</p>
<p>During this time [Candidate First Name] contributed to <strong>[key programme / project]</strong>, where [he/she/they] [briefly describe two or three specific achievements — e.g., led a household survey of 400 respondents, co-authored a policy brief on climate adaptation, coordinated volunteers for a community health drive]. [He/She/They] combines <strong>[two or three qualities — e.g., analytical rigour, empathy, and clear communication]</strong>, and consistently demonstrates <strong>[work ethic — e.g., initiative, follow-through, and integrity]</strong> in a demanding operational environment.</p>
<p>Beyond the technical work, [Candidate First Name] is respected by peers and beneficiaries alike for [personal quality — e.g., warmth and cultural sensitivity], and has taken on informal mentoring of newer colleagues. [He/She/They] is, in my view, well-suited to the opportunity for which this recommendation is offered and will bring the same standards to that role.</p>
<p>I recommend [Candidate First Name] without reservation. Should you wish to discuss this recommendation further, please contact me directly at the details on this letterhead.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => 'To Whom It May Concern',
            'recipient_title' => '',
            'recipient_address' => '',
        ],
    ],
    9 => [
        'name' => 'Salary Certificate',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/SC',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Salary Certificate for [Employee Full Name]',
            'body' => '<p><strong>To Whom It May Concern,</strong></p>
<p>This is to certify that <strong>[Employee Full Name]</strong>, holding Employee ID <strong>[Employee ID]</strong>, is a full-time employee of the Centre for Humanitarian Research and Social Development Foundation (CHRSD), serving as <strong>[Designation]</strong> in the <strong>[Department / Programme]</strong> team since <strong>[Date of Joining]</strong>.</p>
<p>Their current monthly remuneration is as follows:</p>
<p>| Component | Amount (BDT) |<br>| --- | --- |<br>| Basic Salary | [Basic Amount] |<br>| House Rent Allowance | [HRA Amount] |<br>| Medical &amp; Other Allowances | [Other Amount] |<br>| <strong>Gross Monthly Salary</strong> | <strong>[Gross Amount]</strong> (in words: [Amount in words] only) |</p>
<p>All amounts are gross of applicable statutory deductions. The salary is disbursed monthly by bank transfer.</p>
<p>This certificate is issued at [Employee First Name]\'s request for <strong>[Purpose — e.g., bank loan / visa application / rental agreement]</strong> and does not confer any additional obligations upon CHRSD.</p>
<p>For any verification, please contact the HR Department at hr@chrsd.org or +880-2-47122566.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => 'To Whom It May Concern',
            'recipient_title' => '',
            'recipient_address' => '',
        ],
    ],
    10 => [
        'name' => 'Visa Support Letter — Employee',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/VISA',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Visa Support Letter for [Employee\'s Full Name], [Passport No.] — [Seminar / Event Title]',
            'body' => '<p>On behalf of the Centre for Humanitarian Research and Social Development Foundation (CHRSD), a registered humanitarian and development organisation based in Bangladesh, I am writing to formally support the <strong>[Business / Short-Stay]</strong> visa application of our employee, <strong>[Employee\'s Full Name]</strong> (Passport No. <strong>[Passport No.]</strong>), who serves as <strong>[Job Title]</strong> at our organisation.</p>
<p>[Employee\'s First Name] has been a valued member of CHRSD since <strong>[Start Date at CHRSD]</strong> and holds a position of professional standing within our team. We are nominating [him/her/them] to attend <strong>[Seminar / Event Name]</strong>, to be held in <strong>[City, Country]</strong> from <strong>[Event Start Date]</strong> to <strong>[Event End Date]</strong>.</p>
<p>The purpose of [his/her/their] participation is to [briefly state the benefit — e.g. strengthen CHRSD\'s capacity in environmental monitoring, build institutional partnerships, and represent our programmes at an international forum]. This engagement is directly aligned with [his/her/their] current responsibilities at CHRSD and supports our ongoing work in humanitarian research, education governance, and community health.</p>
<p>Throughout the visit to <strong>[Destination Country]</strong>, [Employee\'s Full Name] will be travelling in an official capacity as a representative of CHRSD.</p>
<p>We further confirm that [Employee\'s Full Name] maintains stable, continuing employment with CHRSD and will return to Bangladesh upon the conclusion of the event on <strong>[Return Date]</strong> to resume [his/her/their] regular duties. [He/She/They] will fully comply with all immigration laws and visa conditions of [Destination Country] for the duration of the stay.</p>
<p>We respectfully request that you grant [him/her/them] the appropriate visa to facilitate this professional engagement. Enclosed for your review are <strong>[list enclosures — e.g. official invitation letter, employment certificate, bank statement, travel itinerary]</strong>. Should you require any verification or further information, I would be glad to assist directly at the contact below.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
            'recipient_name' => 'The Visa Officer',
            'recipient_title' => '[Name of the Embassy / Consulate / High Commission]',
            'recipient_address' => '[Address]
[City, Postal Code]',
        ],
    ],
    11 => [
        'name' => 'Volunteer Engagement Letter',
        'document_type' => 'letter',
        'orientation' => 'portrait',
        'shell_variant' => null,
        'is_default' => true,
        'certificate_type_code' => null,
        'letter_category_code' => 'CHRSD/HR/VOL',
        'id_card_type_code' => null,
        'body_markdown' => '<div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px;color:#555;margin-bottom:14px;">
<span>Ref: <strong style="font-family:\'Courier New\',Courier,monospace;color:#0f3b1c;">{{letter_reference}}</strong></span>
<span>Date: <strong>{{issue_date}}</strong></span>
</div>

<p>To,<br>
<strong style="color:#0f3b1c;">{{recipient_name}}</strong><br>
{{recipient_title}}<br>
{{recipient_address}}</p>

### Subject: {{subject}}

Dear {{recipient_name}},

{{{body}}}

Yours sincerely,

<div class="signature" style="margin-top:32px;">
{{{signature_image}}}
<div class="sig-name">{{signatory_name}}</div>
<div class="sig-title">{{signatory_title}}</div>
<div class="sig-org" style="font-size:12px;color:#6b7280;margin-top:2px;">For Centre for Humanitarian Research and Social Development Foundation (CHRSD)</div>
</div>',
        'sample_context' => [
            'subject' => 'Volunteer Engagement — [Volunteer Programme Name]',
            'body' => '<p>Thank you for your interest in volunteering with the Centre for Humanitarian Research and Social Development Foundation (CHRSD). We are delighted to confirm your engagement as a volunteer under the <strong>[Volunteer Programme]</strong> team.</p>
<p><strong>1. Engagement Details</strong></p>
<p>| Field | Detail |<br>| --- | --- |<br>| Volunteer Role | [Role Title] |<br>| Programme | [Programme / Division Name] |<br>| Coordinator | [Coordinator Name &amp; Title] |<br>| Duty Location | [Duty Station — Dhaka / field] |<br>| Period | [Start Date] – [End Date] |<br>| Weekly Commitment | [e.g., 8–12 hours per week] |</p>
<p><strong>2. Nature of Engagement</strong></p>
<p>This is a voluntary, unpaid engagement. It does not create an employer-employee relationship between you and CHRSD. Reasonable field-related expenses (transport, meals during field trips) will be reimbursed per policy.</p>
<p><strong>3. Code of Conduct &amp; Safeguarding</strong></p>
<p>You will follow CHRSD\'s Code of Conduct, Safeguarding Policy, and all programme-specific protocols. You will keep all beneficiary and organisational information confidential.</p>
<p><strong>4. Recognition</strong></p>
<p>Upon completion of a minimum engagement period (typically three months), you may request a <em>Volunteer Certificate</em> summarising your role and contribution.</p>
<p><strong>5. Insurance &amp; Safety</strong></p>
<p>Volunteers are covered under CHRSD\'s activity insurance for the scope of the assigned volunteer duties. Personal risks outside the scope of the engagement are not covered.</p>
<p>We look forward to your active participation and thank you for choosing CHRSD to give back to the community.</p>',
            'signatory_name' => '[Authorised Signatory Name]',
            'signatory_title' => 'Executive Director',
            'organization' => 'CHRSD Foundation',
        ],
    ],
];
