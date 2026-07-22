<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $letter->subject }} — {{ $letter->serial_number }}</title>
<style>
/*
 * CHRSD Official Letter — default Blade view.
 *
 * Letterhead strategy ("Fixed Background Hack"):
 *   @page margins define the safe text zone.
 *   position:fixed elements with matching NEGATIVE offsets are placed
 *   relative to the content-area origin, so negative-margin values push
 *   them back to the physical paper edge (0,0) on every page.
 *
 *   Header:    @page margin-top:103mm  →  .lh-header { top:-103mm }
 *   Footer:    @page margin-bottom:26mm →  .lh-footer { bottom:-26mm }
 *   Left edge: @page margin-left:22mm  →  both elements { left:-22mm }
 */

@page {
    size: A4 portrait;
    margin-top:    103mm;
    margin-bottom:  26mm;
    margin-left:    22mm;
    margin-right:   18mm;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
    font-size: 10.5pt;
    line-height: 1.6;
    color: #1a2133;
}

/* Full-page Letterhead-dompdf.png — anchored to physical page edge via negative offsets. */
.lh-bg {
    position: fixed;
    top:  -103mm;
    left:  -22mm;
    width: 210mm;
    height: 297mm;
    z-index: 0;
}
.lh-bg img { display: block; width: 100%; height: 100%; }

/* --- Letter content --- */
.letter-content { position: relative; z-index: 2; }

.ref-line { margin-bottom: 5mm; font-size: 10pt; color: #45474a; }
.ref-line table { width: 100%; border-collapse: collapse; }
.ref-line td { padding: 0; }
.ref-line .serial {
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 9.5pt; font-weight: bold; color: #0f3b1c;
}

.recipient { margin-bottom: 5mm; line-height: 1.5; }
.recipient .name { font-weight: bold; color: #0f3b1c; }

.subject-line { margin-bottom: 5mm; font-weight: bold; color: #0f3b1c; }
.subject-line span { font-weight: normal; color: #1a2133; }

.body { text-align: justify; }
.body p  { margin: 0 0 3.5mm; page-break-inside: avoid; }
.body h1 { font-size: 14pt; color: #0f3b1c; margin: 0 0 3mm; }
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

.signature { margin-top: 14mm; page-break-inside: avoid; }
.signature .name {
    font-weight: bold; color: #0f3b1c;
    border-top: 1px solid #0f3b1c; padding-top: 1.5mm;
    display: inline-block; min-width: 60mm;
}
.signature .title { font-size: 10pt; color: #186d3b; margin-top: 1mm; }

.meta { margin-top: 8mm; border-top: 0.5pt solid #c8962a; padding-top: 2mm; page-break-inside: avoid; }
.meta table { width: 100%; border-collapse: collapse; }
.meta td { vertical-align: bottom; padding: 0; }
.meta td.ref { font-size: 7.5pt; color: #6b7280; line-height: 1.4; padding-right: 5mm; }
.meta td.ref strong { color: #0f3b1c; }
.meta td.ref .url { font-family: 'DejaVu Sans Mono', 'Courier New', monospace; font-size: 7pt; color: #0f3b1c; word-break: break-all; }
.meta td.qr { width: 22mm; text-align: right; vertical-align: bottom; }
.meta td.qr svg { display: block; width: 22mm; height: 22mm; margin-left: auto; }
</style>
</head>
<body>

    <div class="lh-bg" aria-hidden="true">
        <img src="{{ public_path('images/brand/Letterhead-dompdf.png') }}" alt="">
    </div>

    <div class="letter-content">

        <div class="ref-line">
            <table><tr>
                <td>Ref: <span class="serial">{{ $letter->serial_number }}</span></td>
                <td style="text-align:right;">{{ optional($letter->dated_on ?? $letter->created_at)->format('F j, Y') }}</td>
            </tr></table>
        </div>

        @if ($letter->recipient_name)
            <div class="recipient">
                <div class="name">{{ $letter->recipient_name }}</div>
                @if ($letter->recipient_title)<div>{{ $letter->recipient_title }}</div>@endif
                @if ($letter->recipient_address)<div>{!! nl2br(e($letter->recipient_address)) !!}</div>@endif
            </div>
        @endif

        @if ($letter->subject)
            <div class="subject-line">Subject: <span>{{ $letter->subject }}</span></div>
        @endif

        <div class="body">{!! $letter->body !!}</div>

        @php $signer = $signatory ?? $author; @endphp
        @if ($signer)
            <div class="signature">
                <div class="name">{{ $signer->full_name }}</div>
                <div class="title">{{ optional($signer->position)->title }}</div>
            </div>
        @endif

        <div class="meta">
            <table><tr>
                <td class="ref">
                    To verify this document scan the QR code or visit:<br>
                    <span class="url">{{ $verify_url }}</span><br>
                    Serial: <strong>{{ $letter->serial_number }}</strong>
                </td>
                <td class="qr">@if(!empty($qr_svg)){!! $qr_svg !!}@endif</td>
            </tr></table>
        </div>

    </div>

</body>
</html>
