<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
/*
 * CHRSD Official Letter — DomPDF shell (DB-template path).
 *
 * Letterhead strategy ("Fixed Background Hack"):
 *   @page margins define the safe text zone on every page.
 *   Letterhead elements use position:fixed with NEGATIVE offsets matching
 *   the @page margins so they are anchored to the physical paper edge (0,0).
 *
 *   Header:    @page margin-top:103mm   →  .lh-header { top:-103mm }
 *   Footer:    @page margin-bottom:26mm →  .lh-footer { bottom:-26mm }
 *   Left edge: @page margin-left:22mm  →  both         { left:-22mm }
 */

@page { size: A4 {{ $orientation }}; margin-top: 103mm; margin-bottom: 26mm; margin-left: 22mm; margin-right: 18mm; }

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
    font-size: 10.5pt; line-height: 1.6; color: #1a2133;
}

/* Full-page Letterhead-dompdf.png background on every page.
   Negative offsets cancel the @page margins so the image anchors to
   the physical paper edge (0,0) on every page in DomPDF. */
.lh-bg {
    position: fixed;
    top: -103mm; left: -22mm;
    width: 210mm; height: 297mm;
    z-index: 0;
}
.lh-bg img { display: block; width: 100%; height: 100%; }

.body { position: relative; z-index: 2; }
.body p  { margin: 0 0 3.5mm; page-break-inside: avoid; text-align: justify; }
.body h1 { font-size: 15pt; color: #0f3b1c; margin: 0 0 3mm; }
.body h2 { font-size: 12pt; color: #0f3b1c; margin: 0 0 2.5mm; }
.body h3 { font-size: 10.5pt; font-weight: bold; color: #0f3b1c; margin: 0 0 2mm; }
.body strong { color: #163e22; }
.body em     { color: #4b5563; }
.body a      { color: #1c6d3a; text-decoration: none; }
.body ul, .body ol { margin: 0 0 3.5mm 6mm; }
.body li { margin-bottom: 1mm; }
.body blockquote { border-left: 3px solid #c8962a; padding-left: 4mm; color: #4b5563; margin: 3mm 0; }
.body table { width: 100%; border-collapse: collapse; margin: 2mm 0 3.5mm; }
.body table th { background: #f4f4f5; font-size: 9.5pt; padding: 1.5mm 3mm; text-align: left; border-bottom: 1px solid #d4d4d8; }
.body table td { padding: 1.5mm 3mm; font-size: 10.5pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }

.meta { margin-top: 8mm; border-top: 0.5pt solid #c8962a; padding-top: 2mm; page-break-inside: avoid; }
.meta table { width: 100%; border-collapse: collapse; }
.meta td { vertical-align: bottom; padding: 0; }
.meta td.ref { font-size: 7.5pt; color: #6b7280; line-height: 1.4; padding-right: 5mm; }
.meta td.ref strong { color: #0f3b1c; }
.meta td.qr { width: 22mm; text-align: right; vertical-align: bottom; }
.meta td.qr svg { display: block; width: 22mm; height: 22mm; margin-left: auto; }
.meta td.qr img { display: block; width: 22mm; height: 22mm; margin-left: auto; }
</style>
</head>
<body>

    <div class="lh-bg" aria-hidden="true">
        <img src="{{ public_path('images/brand/Letterhead-dompdf.png') }}" alt="">
    </div>

    <div class="body">{!! $bodyHtml !!}</div>

    <div class="meta">
        <table><tr>
            <td class="ref">
                @if (!empty($letter_reference) || !empty($certificate_number))
                    Ref: <strong>{{ $letter_reference ?? $certificate_number }}</strong><br>
                @endif
                @if (!empty($verification_url ?? null))
                    Verify at: {{ $verification_url }}
                @endif
            </td>
            <td class="qr">@if(!empty($qr_raw)){!! $qr_raw !!}@endif</td>
        </tr></table>
    </div>

</body>
</html>
