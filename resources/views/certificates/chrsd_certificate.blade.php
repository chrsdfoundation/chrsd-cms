@php
    use Carbon\CarbonInterface;

    /* =========================================================================
     |  CHRSD Certificate of Achievement — PDF-safe premium template.
     |
     |  Rendering contract (identical output on DomPDF, Browsershot,
     |  wkhtmltopdf/Snappy, and any modern browser):
     |
     |   • Rigid canvas — exactly A4 landscape (297 × 210 mm, i.e.
     |     1123 × 794 px at 96 dpi). No child element flexes.
     |
     |   • Every column layout uses <table> — the one primitive whose width
     |     distribution is honoured identically by every PDF engine. Flex,
     |     grid, and gap are avoided for anything structural.
     |
     |   • All absolute-positioned decorations sit inside the single
     |     `.certificate` container which is the only `position: relative`
     |     ancestor. Nothing floats loose.
     |
     |   • The Certificate No line and web/email line sit 12 mm above the
     |     bottom edge — well inside the 10 mm inner gold frame with a full
     |     2 mm breathing gap. Same story on top/left/right.
     |
     |   • Vertical rhythm is planned so a two-line course title cannot
     |     collide with either the header above or the signatures below —
     |     the body block has 55 mm of headroom before the signature row.
     |
     |   • Fonts: Playfair Display (serif) for the anchor text — the title
     |     and recipient's name — with Georgia as the fallback that DomPDF
     |     ships with. Inter (sans-serif) for every piece of administrative
     |     copy, with Helvetica / Arial as fallbacks.
     |
     |   • Signature ink lines are CSS `border-bottom` — never underscore
     |     characters (which fracture in some PDF engines).
     |
     |  Accepted variables (all optional — sensible defaults below):
     |    $recipient_name / $name              — printed recipient
     |    $course_title   / $course_name       — printed achievement line
     |    $certificate_no                      — serial (becomes verify link)
     |    $issued_date    / $issue_date        — Carbon or string
     |    $signatory_1_name / $signatory_1_title / $signatory_1_sig
     |    $signatory_2_name / $signatory_2_title / $signatory_2_sig
     |    $qr_svg | $qr_data_uri               — inline SVG or base64 PNG QR
     |    $verification_url                    — used by the cert-no <a>
     |    $org_name / $org_full
     |    $contact_web / $contact_email
     |    $logoUrl / $sealUrl / $watermarkUrl  — brand-asset overrides
     |      (server-side callers pass filesystem paths so DomPDF can read
     |      them directly without HTTP)
     ========================================================================= */

    $recipient_name = $recipient_name ?? $name        ?? 'Recipient Name';
    $course_title   = $course_title   ?? $course_name ?? 'Course or Achievement Title';
    $certificate_no = $certificate_no ?? 'CERT-2026-000001';

    $issued_raw     = $issued_date ?? $issue_date ?? now();
    $issued_date    = $issued_raw instanceof CarbonInterface
        ? $issued_raw->format('F d, Y')
        : (string) $issued_raw;

    $signatory_1_name  = $signatory_1_name  ?? $signatory_name    ?? 'Razib Mustafiz';
    $signatory_1_title = $signatory_1_title ?? $signatory_title   ?? 'Project Coordinator';
    $signatory_1_sig   = $signatory_1_sig   ?? $signatory_sig_url ?? asset('images/brand/signatures/razib-mustafiz.png');

    $signatory_2_name  = $signatory_2_name  ?? $countersign_name    ?? 'M.A. Ramim';
    $signatory_2_title = $signatory_2_title ?? $countersign_title   ?? 'Executive Director';
    $signatory_2_sig   = $signatory_2_sig   ?? $countersign_sig_url ?? asset('images/brand/signatures/ma-ramim.png');

    $qr_svg           = $qr_svg           ?? null;
    $qr_data_uri      = $qr_data_uri      ?? null;
    $verification_url = $verification_url ?? url('/verify/ref/' . $certificate_no);

    $org_name      = $org_name      ?? 'CHRSD';
    $org_full      = $org_full      ?? 'Centre for Humanitarian Research and Social Development Foundation';
    $contact_web   = $contact_web   ?? 'www.chrsd.org';
    $contact_email = $contact_email ?? 'info@chrsd.org';

    $logoUrl      = $logoUrl      ?? asset('images/brand/chrsd-full-logo.png');
    $sealUrl      = $sealUrl      ?? asset('images/brand/chrsd-rosette-seal.png');
    $watermarkUrl = $watermarkUrl ?? asset('images/brand/chrsd-watermark.svg');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $org_name }} — Certificate of Achievement</title>
<style>
    /* Fonts. Browsershot / Snappy / browsers all download them; DomPDF
       silently ignores @import and falls through to Georgia / Helvetica. */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@400;500;600&display=swap');

    @page { size: A4 landscape; margin: 0; }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    html, body {
        background: #FFFEF9;
        color: #2b2620;
        font-family: 'Inter', Helvetica, Arial, 'DejaVu Sans', sans-serif;
        font-size: 10.5pt;
        line-height: 1.5;
    }

    /* ================================================================
       ROOT CANVAS — rigid A4 landscape. Only `position: relative`
       ancestor; every absolute child stays bound to this box.
       ================================================================ */
    .certificate {
        position: relative;
        width: 297mm;
        height: 210mm;
        background: #FFFEF9;
        margin: 0 auto;
        overflow: hidden;
        page-break-inside: avoid;
        page-break-after: avoid;
    }

    /* ================================================================
       DOUBLE GOLD FRAME
       ================================================================ */
    .frame-outer,
    .frame-inner {
        position: absolute;
        pointer-events: none;
        z-index: 1;
    }
    .frame-outer {
        top: 6mm; left: 6mm; right: 6mm; bottom: 6mm;
        border: 1.4mm solid #C8962A;
        border-radius: 2mm;
    }
    .frame-inner {
        top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
        border: 0.25mm solid #A67718;
        border-radius: 1mm;
    }

    /* Four corner gold flourishes (inline SVG). */
    .corner {
        position: absolute;
        width: 26mm;
        height: 26mm;
        pointer-events: none;
        z-index: 2;
    }
    .corner.tl { top: 10mm;    left:  10mm; }
    .corner.tr { top: 10mm;    right: 10mm; transform: scaleX(-1); }
    .corner.bl { bottom: 10mm; left:  10mm; transform: scaleY(-1); }
    .corner.br { bottom: 10mm; right: 10mm; transform: scale(-1,-1); }

    /* Faint rosette watermark. */
    .watermark {
        position: absolute;
        top:  30mm;                    /* fixed inset (no % translate — safer) */
        left: 73mm;
        width:  150mm;
        height: 150mm;
        opacity: 0.05;
        z-index: 1;
        pointer-events: none;
    }
    .watermark img { display: block; width: 100%; height: 100%; }

    /* ================================================================
       HEADER — three-cell table (QR | spacer | logo).
       Anchored 22 mm from the top and 22 mm from each side, so it
       sits 12 mm INSIDE the inner gold frame.
       ================================================================ */
    .header-row {
        position: absolute;
        top:  20mm;
        left: 22mm;
        width: calc(100% - 44mm);
        border-collapse: collapse;
        z-index: 3;
    }
    .header-row td { padding: 0; vertical-align: top; }

    .header-qr    { width: 30mm; text-align: left;  }
    .header-logo  { width: 52mm; text-align: right; }

    .qr-box {
        width:  28mm;
        height: 28mm;
        border: 0.5mm solid #C8962A;
        background: #ffffff;
        padding: 2mm;
    }
    .qr-box img, .qr-box svg { display: block; width: 100%; height: 100%; }
    .qr-box .qr-placeholder {
        display: block;
        width: 100%;
        height: 100%;
        line-height: 24mm;
        text-align: center;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-size: 9pt;
        color: #C8962A;
        letter-spacing: 2px;
        border: 0.4mm dashed #C8962A;
    }
    .qr-caption {
        display: block;
        width: 28mm;
        margin-top: 2mm;
        text-align: center;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-size: 7pt;
        line-height: 1.3;
        color: #6b6053;
        letter-spacing: 1px;
    }

    .header-logo img { display: inline-block; max-width: 52mm; max-height: 34mm; }

    /* ================================================================
       ORG NAME (sans-serif, generous line-height for two-line safety)
       ================================================================ */
    .org-name {
        position: absolute;
        top: 62mm;
        left: 30mm;
        width: calc(100% - 60mm);
        text-align: center;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-size: 9pt;
        font-weight: 500;
        letter-spacing: 4px;
        line-height: 1.4;
        color: #C8962A;
        text-transform: uppercase;
        z-index: 3;
    }

    /* ================================================================
       TITLE (serif anchor)
       ================================================================ */
    .title {
        position: absolute;
        top: 72mm;
        left: 20mm;
        width: calc(100% - 40mm);
        text-align: center;
        font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
        font-weight: 700;
        font-size: 32pt;
        letter-spacing: 4px;
        line-height: 1.1;
        color: #6E4A0E;
        text-transform: uppercase;
        z-index: 3;
    }

    .filigree {
        position: absolute;
        top: 97mm;
        left: 50%;
        margin-left: -85mm;
        width: 170mm;
        height: 6mm;
        z-index: 3;
    }
    .filigree svg { display: block; width: 100%; height: 100%; }

    /* ================================================================
       BODY BLOCK
       55 mm of vertical headroom before the signature row — a two-line
       course title still leaves ≥ 8 mm buffer.
       ================================================================ */
    .body-block {
        position: absolute;
        top: 104mm;
        left: 30mm;
        width: calc(100% - 60mm);
        text-align: center;
        z-index: 3;
    }
    .lede,
    .interlude {
        font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
        font-style: italic;
        font-size: 11pt;
        line-height: 1.4;
        color: #4b4034;
    }
    .lede      { margin: 0 0 2mm 0; }
    .interlude { margin: 3mm 0 1mm 0; }

    /* Recipient name — PREMIUM SERIF anchor.
       Uses `display: table` + `margin: 0 auto` to force dead-centre
       positioning irrespective of the parent's calc()-based width. The
       "table" collapses the box to text width, then auto-margins on
       both sides balance mathematically. This is the most portable way
       to centre across DomPDF, Browsershot, wkhtmltopdf, and browsers. */
    .recipient-block { width: 100%; text-align: center; }
    .recipient-name {
        display: table;
        margin: 0 auto;
        padding: 0;
        font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
        font-weight: 700;
        font-size: 26pt;
        line-height: 1.1;
        letter-spacing: 0;
        color: #0F3D1E;
        text-align: center;
    }

    /* Course title — SANS-SERIF (administrative / structural copy per the
       typography contract; serif is reserved for the title + recipient
       anchors only). Same `display: table` centring trick as the recipient. */
    .course-block { width: 100%; text-align: center; }
    .course-title {
        display: table;
        margin: 0 auto;
        padding: 0;
        font-family: 'Inter', Helvetica, Arial, 'DejaVu Sans', sans-serif;
        font-weight: 600;
        font-size: 16pt;
        line-height: 1.3;                     /* two-line safety */
        letter-spacing: 0;
        color: #6E4A0E;
        text-align: center;
    }

    .issued-line {
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-size: 10pt;
        line-height: 1.4;
        letter-spacing: 0.5px;
        color: #6b6053;
        margin: 2mm auto 0 auto;
        text-align: center;
    }

    /* ================================================================
       SIGNATURE ROW — two signature blocks + a centred rosette seal
       between them (rosette lives in its OWN absolute box so it never
       affects table-cell height calculation).
       ================================================================ */
    /* Signature row — 32mm from the bottom. Combined with footer at 18mm,
       that leaves ~10mm safe gap between sig content and the footer line. */
    .signature-row {
        position: absolute;
        left: 22mm;
        bottom: 32mm;
        width: calc(100% - 44mm);
        height: 28mm;
        border-collapse: collapse;
        z-index: 3;
    }
    .signature-row td {
        padding: 0;
        vertical-align: bottom;
        height: 28mm;
    }
    .sig-cell.left  { width: 40%; text-align: left;  }
    .sig-cell.mid   { width: 20%; }
    .sig-cell.right { width: 40%; text-align: right; }

    .sig-image {
        display: block;
        width: 45mm;
        height: 14mm;
        margin-bottom: -3mm;                  /* image overlaps line naturally */
        position: relative;
        z-index: 1;
    }
    .sig-cell.left  .sig-image { margin-left: 0;  margin-right: auto; }
    .sig-cell.right .sig-image { margin-left: auto; margin-right: 0;  }

    .sig-line-wrap { width: 55mm; }
    .sig-cell.left  .sig-line-wrap { margin: 0 auto 0 0; }
    .sig-cell.right .sig-line-wrap { margin: 0 0 0 auto; }

    /* CSS border for the ink line — never underscore characters. */
    .sig-line {
        width: 55mm;
        height: 0;
        border-bottom: 0.5mm solid #333333;
        margin: 0 0 2mm 0;
    }

    /* Signatory name + title — SANS-SERIF (structural copy). */
    .sig-name {
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-weight: 600;
        font-size: 11pt;
        line-height: 1.3;
        letter-spacing: 0.5px;
        color: #0F3D1E;
    }
    .sig-title {
        margin-top: 1mm;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        font-weight: 500;
        font-size: 8pt;
        line-height: 1.4;
        letter-spacing: 1px;
        color: #6b6053;
        text-transform: uppercase;
    }

    /* Rosette seal — absolute, centred, fixed dimensions.
       Sits alongside the signature row (bottom:32mm) with its top well
       clear of the body's "Issued on" line. */
    .rosette {
        position: absolute;
        left: 50%;
        bottom: 30mm;
        width: 28mm;
        height: 32mm;
        margin-left: -14mm;                   /* centre without translate */
        z-index: 3;
    }
    .rosette img { display: block; width: 100%; height: 100%; }

    /* ================================================================
       FOOTER — cert-no (LEFT) · gold diamond rule (CENTER) · web|email (RIGHT)
       12 mm from the bottom of the page → 2 mm gap past the inner frame.
       ================================================================ */
    /* Footer — pinned 18mm from the bottom of the page. Inner gold frame is
       at 10mm, so this leaves an ~8mm safe gap between the cert-no line and
       the frame border. No more crowding. */
    .footer-row {
        position: absolute;
        left: 22mm;
        bottom: 18mm;
        width: calc(100% - 44mm);
        border-collapse: collapse;
        z-index: 3;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
    }
    .footer-row td {
        padding: 0;
        vertical-align: middle;
        font-size: 8.5pt;
        line-height: 1.4;
        color: #6E4A0E;
    }
    .foot-left  { width: 32%; text-align: left;  padding-right: 6mm; }
    .foot-mid   { width: 36%; text-align: center; }
    .foot-right { width: 32%; text-align: right; padding-left: 6mm; letter-spacing: 0.5px; }

    .foot-left strong { color: #C8962A; font-weight: 600; letter-spacing: 1px; }

    /* Cert-no hyperlink — invisible in ink, real /URI in the PDF. */
    .cert-link,
    .cert-link:visited,
    .cert-link:hover { color: inherit; text-decoration: none; }

    .foot-right a { color: #6E4A0E; text-decoration: none; }

    /*
     * CSS-only decorative rule with a diamond node.
     * IMPORTANT: `.deco-line` must be block-level so its `height: 3mm` and
     * `width: 100%` actually apply. As a <span> (inline) it collapsed to the
     * line-height of the parent cell, so the absolutely-positioned .rule and
     * .diamond had no meaningful vertical anchor and rendered as a hairline
     * near the baseline — which is exactly the "line & diamond not visible"
     * symptom users saw. `inline-block` keeps it inside the table-cell flow
     * while honouring the box dimensions.
     */
    .deco-line {
        position: relative;
        display: inline-block;
        vertical-align: middle;
        width: 100%;
        height: 5mm;
    }
    .deco-line .rule {
        position: absolute;
        top: 50%; left: 0; right: 0;
        height: 0;
        border-top: 0.5mm solid #C8962A;
    }
    .deco-line .diamond {
        position: absolute;
        top: 50%; left: 50%;
        width: 3mm; height: 3mm;
        margin-top:  -1.5mm;
        margin-left: -1.5mm;
        background: #FFFEF9;
        border: 0.5mm solid #C8962A;
        transform: rotate(45deg);
    }

    /* ================================================================
       Browser-only chrome (drop shadow + tint).
       Scoped to @media screen so it NEVER applies to PDF output.
       Chromium emulates print media by default under Puppeteer, but the
       old @supports rule leaked its 20px body padding into the PDF anyway
       — pushing the 210mm canvas onto a second page and slicing the
       footer diamond/rule off the bottom. Nesting @supports inside
       @media screen keeps the browser preview pretty AND keeps the PDF
       strictly single-page.
       ================================================================ */
    @media screen {
        @supports (backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px)) {
            body { background: #eae5d8; padding: 20px 0; }
            .certificate { box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15); }
        }
    }

    @media print {
        html, body { background: #FFFEF9; margin: 0; padding: 0; }
        .certificate { box-shadow: none; page-break-after: avoid; }
    }
</style>
</head>
<body>

<div class="certificate">

    {{-- Decorative frame + corner flourishes --}}
    <div class="frame-outer"></div>
    <div class="frame-inner"></div>

    @php
        $cornerSvg = <<<'SVG'
<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" preserveAspectRatio="xMinYMin meet">
  <g fill="none" stroke="#C8962A" stroke-linecap="round" stroke-linejoin="round">
    <path d="M 4 40 Q 4 4 40 4" stroke-width="1.3"/>
    <path d="M 10 32 Q 10 10 32 10" stroke-width="0.9"/>
    <path d="M 8 60 Q 20 60 20 48 T 32 36" stroke-width="0.7"/>
    <circle cx="42" cy="6"  r="1.2" fill="#C8962A" stroke="none"/>
    <circle cx="6"  cy="42" r="1.2" fill="#C8962A" stroke="none"/>
    <circle cx="20" cy="20" r="1"   fill="#A67718" stroke="none"/>
  </g>
  <path d="M 14 14 L 22 12 L 20 20 Z" fill="#A67718" opacity="0.55"/>
</svg>
SVG;
    @endphp
    <div class="corner tl">{!! $cornerSvg !!}</div>
    <div class="corner tr">{!! $cornerSvg !!}</div>
    <div class="corner bl">{!! $cornerSvg !!}</div>
    <div class="corner br">{!! $cornerSvg !!}</div>

    <div class="watermark" aria-hidden="true">
        <img src="{{ $watermarkUrl }}" alt="">
    </div>

    {{-- HEADER --}}
    <table class="header-row">
        <tr>
            <td class="header-qr">
                <div class="qr-box" aria-label="Verification QR code">
                    @if (! empty($qr_svg))
                        {!! $qr_svg !!}
                    @elseif (! empty($qr_data_uri))
                        <img src="{{ $qr_data_uri }}" alt="Verification QR">
                    @else
                        <span class="qr-placeholder">QR</span>
                    @endif
                </div>
                <span class="qr-caption">Scan to Verify</span>
            </td>
            <td>&nbsp;</td>
            <td class="header-logo">
                <img src="{{ $logoUrl }}" alt="{{ $org_name }} logo">
            </td>
        </tr>
    </table>

    {{-- ORG NAME + TITLE --}}
    <div class="org-name">{{ $org_full }}</div>

    <h1 class="title">Certificate of Achievement</h1>

    <div class="filigree" aria-hidden="true">
        <svg viewBox="0 0 400 20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
            <g stroke="#C8962A" fill="none" stroke-linecap="round">
                <line x1="20"  y1="10" x2="170" y2="10" stroke-width="0.8"/>
                <line x1="230" y1="10" x2="380" y2="10" stroke-width="0.8"/>
            </g>
            <g fill="#C8962A">
                <circle cx="180" cy="10" r="2"/>
                <circle cx="220" cy="10" r="2"/>
                <path d="M 190 10 L 200 4 L 200 16 Z" opacity="0.85"/>
                <path d="M 210 10 L 200 4 L 200 16 Z" opacity="0.85"/>
            </g>
        </svg>
    </div>

    {{-- BODY --}}
    <div class="body-block">
        <p class="lede">This is to certify that</p>

        <div class="recipient-block">
            <p class="recipient-name">{{ $recipient_name }}</p>
        </div>

        <p class="interlude">has successfully completed</p>

        <div class="course-block">
            <p class="course-title">{{ $course_title }}</p>
        </div>

        <p class="issued-line">Issued on {{ $issued_date }}</p>
    </div>

    {{-- ROSETTE SEAL (absolute, own box — doesn't inflate signature-row cells) --}}
    <div class="rosette" aria-label="{{ $org_name }} Seal">
        <img src="{{ $sealUrl }}" alt="">
    </div>

    {{-- SIGNATURE ROW --}}
    <table class="signature-row">
        <tr>
            <td class="sig-cell left">
                @if (! empty($signatory_1_sig))
                    <img class="sig-image" src="{{ $signatory_1_sig }}" alt="Signature of {{ $signatory_1_name }}">
                @endif
                <div class="sig-line-wrap">
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $signatory_1_name }}</div>
                    <div class="sig-title">{{ $signatory_1_title }}</div>
                </div>
            </td>
            <td class="sig-cell mid">&nbsp;</td>
            <td class="sig-cell right">
                @if (! empty($signatory_2_sig))
                    <img class="sig-image" src="{{ $signatory_2_sig }}" alt="Signature of {{ $signatory_2_name }}">
                @endif
                <div class="sig-line-wrap">
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $signatory_2_name }}</div>
                    <div class="sig-title">{{ $signatory_2_title }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <table class="footer-row">
        <tr>
            <td class="foot-left">
                <a class="cert-link" href="{{ $verification_url }}">Certificate No. <strong>{{ $certificate_no }}</strong></a>
            </td>
            <td class="foot-mid">
                <span class="deco-line">
                    <span class="rule"></span>
                    <span class="diamond"></span>
                </span>
            </td>
            <td class="foot-right">
                <a href="https://{{ $contact_web }}">{{ $contact_web }}</a>
                &nbsp;|&nbsp;
                <a href="mailto:{{ $contact_email }}">{{ $contact_email }}</a>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
