@php
    use Carbon\CarbonInterface;

    $recipientName   = $recipientName   ?? 'Recipient Name';
    $courseName      = $courseName      ?? 'Course Name';
    $completionDate  = $completionDate  ?? now();
    $dateText        = $completionDate instanceof CarbonInterface
        ? $completionDate->format('F d, Y')
        : (string) $completionDate;

    // Optional signatures override — each entry: ['name' => ..., 'title' => ..., 'image' => ...].
    $signatures      = $signatures ?? [
        ['name' => null, 'title' => 'Program Director',              'image' => null],
        ['name' => null, 'title' => 'Training Coordinator',          'image' => null],
        ['name' => null, 'title' => 'Frank Foundation Representative', 'image' => null],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Certificate of Course Completion</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@400;500&display=swap');

    @page {
        size: A4 landscape;
        margin: 0;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        background: #FDFBF7;
        font-family: 'Inter', Arial, sans-serif;
        color: #333;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .certificate {
        position: relative;
        width: 297mm;
        height: 210mm;
        margin: 0 auto;
        background: #FDFBF7;
        border: 2px solid #1a3c6c;
        border-radius: 2px;
        padding: 8mm;
        overflow: hidden;
    }

    .inner-frame {
        position: relative;
        width: 100%;
        height: 100%;
        border: 1px solid #c9a227;
        border-radius: 2px;
        padding: 14mm 18mm 18mm 18mm;
    }

    /* Faint circular seal watermark behind the main text. */
    .seal-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 130mm;
        height: 130mm;
        margin-top: -65mm;
        margin-left: -65mm;
        opacity: 0.08;
        z-index: 0;
        pointer-events: none;
    }

    .seal-watermark svg { width: 100%; height: 100%; display: block; }

    .header {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .header .org-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 18px;
        color: #1a3c6c;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .header .powered-by {
        font-family: 'Inter', Arial, sans-serif;
        font-size: 11px;
        color: #555;
        margin-top: 2mm;
    }

    .title-block {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 15mm;
    }

    .main-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 32px;
        color: #1a3c6c;
        letter-spacing: 3px;
        text-transform: uppercase;
        font-weight: 700;
        margin: 0;
    }

    .divider {
        width: 60mm;
        height: 0;
        border-top: 1px solid #c9a227;
        margin: 8mm auto 0 auto;
    }

    .date-line {
        text-align: center;
        font-family: 'Playfair Display', Georgia, serif;
        font-style: italic;
        font-size: 14px;
        color: #444;
        margin-top: 10mm;
        position: relative;
        z-index: 1;
    }

    .recipient-block {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 12mm;
    }

    .recipient-block .prelude,
    .recipient-block .interlude {
        font-family: 'Playfair Display', Georgia, serif;
        font-style: italic;
        font-size: 13px;
        color: #555;
    }

    .recipient-block .recipient-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: 28px;
        color: #1a3c6c;
        margin: 6mm 0;
        line-height: 1.15;
    }

    .recipient-block .course-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: 22px;
        color: #1a3c6c;
        margin: 4mm 0 0 0;
        line-height: 1.2;
    }

    .issuer-block {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 10mm;
    }

    .issuer-block .issuer-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 14px;
        color: #1a3c6c;
    }

    .issuer-block .issuer-tag {
        font-family: 'Inter', Arial, sans-serif;
        font-size: 10px;
        color: #555;
        margin-top: 1mm;
    }

    /* Signature row uses display: table for DomPDF compatibility. */
    .signature-row {
        position: absolute;
        left: 18mm;
        right: 18mm;
        bottom: 25mm;
        display: table;
        width: calc(100% - 36mm);
        z-index: 1;
    }

    .signature-cell {
        display: table-cell;
        width: 33.33%;
        text-align: center;
        vertical-align: bottom;
    }

    .signature-line {
        width: 40mm;
        height: 0;
        border-top: 1px solid #333;
        margin: 0 auto 2mm auto;
    }

    .signature-image {
        display: block;
        height: 12mm;
        margin: 0 auto 1mm auto;
    }

    .signature-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 12px;
        color: #1a3c6c;
        margin-bottom: 1mm;
    }

    .signature-title {
        font-family: 'Inter', Arial, sans-serif;
        font-size: 10px;
        color: #555;
    }

    .footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 8mm;
        text-align: center;
        font-family: 'Inter', Arial, sans-serif;
        font-size: 9px;
        color: #666;
        letter-spacing: 1.5px;
        z-index: 1;
    }

    .footer .usaid-logo {
        display: inline-block;
        vertical-align: middle;
        width: 9px;
        height: 9px;
        line-height: 9px;
        text-align: center;
        margin-right: 5px;
        color: #b7472a;
        font-size: 12px;
    }

    /* Print: strip browser default margins, force white body so cream shows only on the certificate. */
    @media print {
        html, body { background: #fff; }
        body { margin: 0; }
        .certificate { box-shadow: none; }
    }
</style>
</head>
<body>
    <div class="certificate">
        <div class="inner-frame">

            {{-- Faint circular seal watermark behind the main text (opacity 0.08). --}}
            <div class="seal-watermark" aria-hidden="true">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <path id="sealCircle" d="M 100,100 m -80,0 a 80,80 0 1,1 160,0 a 80,80 0 1,1 -160,0" fill="none"/>
                    </defs>
                    <circle cx="100" cy="100" r="88" fill="none" stroke="#1a3c6c" stroke-width="2"/>
                    <circle cx="100" cy="100" r="80" fill="none" stroke="#c9a227" stroke-width="1"/>
                    <text font-family="Playfair Display, Georgia, serif" font-size="10" fill="#1a3c6c" letter-spacing="3">
                        <textPath href="#sealCircle" startOffset="0">
                            GLOBALHEALTH LEARNINGCENTER · GLOBALHEALTH LEARNINGCENTER ·
                        </textPath>
                    </text>
                    {{-- Generic crest at the middle of the seal --}}
                    <g transform="translate(100 100)">
                        <path d="M -22,-24 L 22,-24 L 22,6 C 22,20 0,30 0,30 C 0,30 -22,20 -22,6 Z"
                              fill="none" stroke="#1a3c6c" stroke-width="2"/>
                        <path d="M -14,-16 L 14,-16 L 14,4 C 14,14 0,20 0,20 C 0,20 -14,14 -14,4 Z"
                              fill="#1a3c6c" opacity="0.15"/>
                        <text x="0" y="4" text-anchor="middle"
                              font-family="Playfair Display, Georgia, serif"
                              font-size="14" font-weight="700" fill="#1a3c6c">GH</text>
                    </g>
                </svg>
            </div>

            {{-- Top header --}}
            <div class="header">
                <div class="org-name">GlobalHealth LearningCenter</div>
                <div class="powered-by">Powered by the Frank Foundation</div>
            </div>

            {{-- Main title + decorative line --}}
            <div class="title-block">
                <h1 class="main-title">Certificate of Course Completion</h1>
                <div class="divider"></div>
            </div>

            {{-- Date --}}
            <div class="date-line">{{ $dateText }}</div>

            {{-- Recipient + course --}}
            <div class="recipient-block">
                <div class="prelude">This is to certify that</div>
                <div class="recipient-name">{{ $recipientName }}</div>
                <div class="interlude">has successfully completed the course:</div>
                <div class="course-name">{{ $courseName }}</div>
            </div>

            {{-- Issuing body --}}
            <div class="issuer-block">
                <div class="issuer-name">GlobalHealth LearningCenter</div>
                <div class="issuer-tag">Powered by the Frank Foundation</div>
            </div>

            {{-- Signature block. Replace <div class="signature-line"> with
                 <img class="signature-image" src="..." alt="..."> when a scanned
                 signature image is available for a given role. --}}
            <div class="signature-row">
                @foreach ($signatures as $sig)
                    <div class="signature-cell">
                        @if (! empty($sig['image']))
                            {{-- TODO: replace placeholder line with actual signature image --}}
                            <img class="signature-image" src="{{ $sig['image'] }}" alt="Signature">
                        @else
                            <div class="signature-line"></div>
                        @endif
                        @if (! empty($sig['name']))
                            <div class="signature-name">{{ $sig['name'] }}</div>
                        @endif
                        <div class="signature-title">{{ $sig['title'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Footer: USAID-style attribution --}}
            <div class="footer">
                <span class="usaid-logo">&#9733;</span>USAID FROM THE AMERICAN PEOPLE
            </div>

        </div>
    </div>
</body>
</html>
