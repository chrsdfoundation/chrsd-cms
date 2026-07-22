<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    /* Companion shell for the "course-completion-usaid" DocumentTemplate variant.
       Mirrors the standalone resources/views/certificates/course_completion.blade.php
       design so DB-driven templates render the same certificate shape. */

    @page { size: A4 {{ $orientation }}; margin: 0; }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        background: #FDFBF7;
        font-family: 'DejaVu Sans','Helvetica',sans-serif;
        color: #333;
    }

    .certificate {
        position: relative;
        width: {{ $orientation === 'landscape' ? '297mm' : '210mm' }};
        height: {{ $orientation === 'landscape' ? '210mm' : '297mm' }};
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
    }
    .seal-watermark svg { width: 100%; height: 100%; display: block; }

    .header       { position: relative; z-index: 1; text-align: center; }
    .header .org-name   { font-family: Georgia, 'Times New Roman', serif; font-size: 18px; color: #1a3c6c; font-weight: bold; letter-spacing: 0.5px; }
    .header .powered-by { font-size: 11px; color: #555; margin-top: 2mm; }

    .title-block  { position: relative; z-index: 1; text-align: center; margin-top: 15mm; }
    .main-title   { font-family: Georgia, 'Times New Roman', serif; font-size: 32px; color: #1a3c6c; letter-spacing: 3px; text-transform: uppercase; font-weight: bold; margin: 0; }
    .divider      { width: 60mm; height: 0; border-top: 1px solid #c9a227; margin: 8mm auto 0 auto; }

    /* Body area — this is where the substituted body_markdown HTML lands.
       body_markdown uses .certify-line, .date-line, .name, .course-name so the
       styles here apply to those elements. */
    .body-area          { position: relative; z-index: 1; text-align: center; margin-top: 10mm; }
    .body-area .date-line   { font-family: Georgia, 'Times New Roman', serif; font-style: italic; font-size: 14px; color: #444; margin: 0; }
    .body-area .certify-line,
    .body-area .course-lead { font-family: Georgia, 'Times New Roman', serif; font-style: italic; font-size: 13px; color: #555; margin-top: 12mm; }
    .body-area .name        { font-family: Georgia, 'Times New Roman', serif; font-weight: bold; font-size: 28px; color: #1a3c6c; margin: 6mm 0; line-height: 1.15; }
    .body-area h3           { font-family: Georgia, 'Times New Roman', serif; font-weight: bold; font-size: 22px; color: #1a3c6c; margin: 4mm 0 0 0; line-height: 1.2; }
    .body-area .course-name { font-family: Georgia, 'Times New Roman', serif; font-weight: bold; font-size: 22px; color: #1a3c6c; margin: 4mm 0 0 0; line-height: 1.2; }

    .issuer-block { position: relative; z-index: 1; text-align: center; margin-top: 10mm; }
    .issuer-name  { font-family: Georgia, 'Times New Roman', serif; font-size: 14px; color: #1a3c6c; }
    .issuer-tag   { font-size: 10px; color: #555; margin-top: 1mm; }

    .signature-row { position: absolute; left: 18mm; right: 18mm; bottom: 25mm; display: table; width: auto; z-index: 1; }
    .signature-cell { display: table-cell; width: 33.33%; text-align: center; vertical-align: bottom; }
    .signature-line { width: 40mm; height: 0; border-top: 1px solid #333; margin: 0 auto 2mm auto; }
    .signature-title { font-size: 10px; color: #555; }

    .footer { position: absolute; left: 0; right: 0; bottom: 8mm; text-align: center; font-size: 9px; color: #666; letter-spacing: 1.5px; z-index: 1; }
    .footer .usaid-logo { display: inline-block; vertical-align: middle; width: 9px; height: 9px; margin-right: 5px; color: #b7472a; font-size: 12px; }
</style>
</head>
<body>
    <div class="certificate">
        <div class="inner-frame">

            {{-- Circular seal watermark --}}
            <div class="seal-watermark" aria-hidden="true">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <path id="sealCircle" d="M 100,100 m -80,0 a 80,80 0 1,1 160,0 a 80,80 0 1,1 -160,0" fill="none"/>
                    </defs>
                    <circle cx="100" cy="100" r="88" fill="none" stroke="#1a3c6c" stroke-width="2"/>
                    <circle cx="100" cy="100" r="80" fill="none" stroke="#c9a227" stroke-width="1"/>
                    <text font-family="Georgia, serif" font-size="10" fill="#1a3c6c" letter-spacing="3">
                        <textPath href="#sealCircle" startOffset="0">
                            {{ $issuer_name ?? 'GLOBALHEALTH LEARNINGCENTER' }} · {{ $issuer_name ?? 'GLOBALHEALTH LEARNINGCENTER' }} ·
                        </textPath>
                    </text>
                    <g transform="translate(100 100)">
                        <path d="M -22,-24 L 22,-24 L 22,6 C 22,20 0,30 0,30 C 0,30 -22,20 -22,6 Z" fill="none" stroke="#1a3c6c" stroke-width="2"/>
                        <path d="M -14,-16 L 14,-16 L 14,4 C 14,14 0,20 0,20 C 0,20 -14,14 -14,4 Z" fill="#1a3c6c" opacity="0.15"/>
                        <text x="0" y="4" text-anchor="middle" font-family="Georgia, serif" font-size="14" font-weight="700" fill="#1a3c6c">GH</text>
                    </g>
                </svg>
            </div>

            <div class="header">
                <div class="org-name">{{ $issuer_name ?? 'GlobalHealth LearningCenter' }}</div>
                <div class="powered-by">{{ $issuer_tagline ?? 'Powered by the Frank Foundation' }}</div>
            </div>

            <div class="title-block">
                <h1 class="main-title">Certificate of Course Completion</h1>
                <div class="divider"></div>
            </div>

            {{-- body_markdown rendered here. --}}
            <div class="body-area">
                {!! $bodyHtml !!}
            </div>

            <div class="issuer-block">
                <div class="issuer-name">{{ $issuer_name ?? 'GlobalHealth LearningCenter' }}</div>
                <div class="issuer-tag">{{ $issuer_tagline ?? 'Powered by the Frank Foundation' }}</div>
            </div>

            <div class="signature-row">
                <div class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-title">Program Director</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-title">Training Coordinator</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-title">Frank Foundation Representative</div>
                </div>
            </div>

            <div class="footer">
                <span class="usaid-logo">&#9733;</span>USAID FROM THE AMERICAN PEOPLE
            </div>

        </div>
    </div>
</body>
</html>
