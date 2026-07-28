{{--
  CHRSD ID — BACK · CR80 landscape (85.6 × 54 mm).
  mPDF render (CSS 2.1 only). All images arrive as base64 data URIs from the service.
  Vars: $idCard, $qr_svg (raw SVG string), $verify_url,
        $signatureUrl (data URI|null), $roundLogoUrl (data URI).
--}}
@php
    $signatureUrl = $signatureUrl ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; size: 85.6mm 54mm; }
    * { margin: 0; padding: 0; box-sizing: border-box;
        -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { width: 85.6mm; height: 54mm; overflow: hidden; }
    body { font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif; color: #163E22; }

    .card {
        position: relative;
        width: 85.6mm; height: 54mm;
        background: #FFFFFF;
        overflow: hidden;
    }

    /* ── Guilloche security background ── */
    .guilloche {
        position: absolute; top: 0; right: 0; bottom: 0; left: 0;
        opacity: 0.07; pointer-events: none; z-index: 0;
    }
    .guilloche svg { width: 100%; height: 100%; display: block; }

    /* ── Centre watermark (using absolute positioning instead of transform) ── */
    .watermark {
        position: absolute;
        top: 50%; left: 50%;
        width: 28mm; height: 28mm;
        margin-top: -14mm;
        margin-left: -14mm;
        opacity: 0.045; pointer-events: none; z-index: 1;
    }
    .watermark img { width: 100%; height: 100%; display: block; }

    /* ── Left sidebar — absolute positioning for vertical text ── */
    .sidebar {
        position: absolute; top: 0; left: 0;
        width: 10mm; height: 54mm;
        background: #123420;
        z-index: 2;
    }
    .sidebar .edge {
        position: absolute; top: 0; right: 0;
        width: 0.6mm; height: 54mm; background: #C09020;
    }
    .sidebar-text {
        position: absolute;
        top: 50%; left: 50%;
        width: 8mm; height: 30mm;
        margin-top: -15mm;
        margin-left: -4mm;
        font-size: 10px; font-weight: 700;
        letter-spacing: 2px; color: #C9A14A;
        text-align: center;
        white-space: normal;
        word-break: break-all;
        line-height: 1.8;
        user-select: none;
    }

    /* ── Card body ── */
    .body { position: absolute; top: 0; left: 10mm; width: 75.6mm; height: 54mm; z-index: 3; }

    /* Header: centred round emblem + org name */
    .emblem {
        position: absolute; top: 1.5mm; left: 32.5mm;
        width: 11mm; height: 11mm;
    }
    .emblem img { width: 100%; height: 100%; display: block; }

    .org-name {
        position: absolute;
        top: 13mm; left: 2mm; right: 2mm;
        text-align: center; font-size: 5.2px;
        color: #186D3B; line-height: 1.5; letter-spacing: 0.3px;
        text-transform: uppercase;
    }
    .header-rule {
        position: absolute; top: 17mm; left: 10mm; right: 10mm;
        height: 0; border-top: 0.4mm solid #C09020;
    }

    /* ── LEFT column: instructions + contact ── */
    .left-col {
        position: absolute; top: 18.5mm; left: 2mm;
        width: 42mm;
    }
    .notice-h {
        font-size: 7.5px; font-weight: 700;
        color: #163E22; margin-bottom: 0.8mm;
        text-transform: uppercase; letter-spacing: 0.4px;
    }
    .notice {
        font-size: 6px; color: #186D3B; line-height: 1.6;
    }
    .contact {
        margin-top: 1.8mm; font-size: 5.8px;
        color: #163E22; line-height: 1.5;
    }
    .contact .row { margin-bottom: 0.6mm; display: flex; align-items: flex-start; gap: 1mm; }
    .contact .icon { color: #C09020; flex-shrink: 0; width: 3mm; text-align: center; }
    .contact .txt { flex: 1; }

    /* ── RIGHT column: signature + QR ── */
    .sig-block {
        position: absolute; top: 19mm; right: 2mm;
        width: 24mm; text-align: center;
    }
    .sig-image {
        display: block; width: 22mm; height: 8mm;
        margin: 0 auto 0 auto;
    }
    .sig-line {
        width: 22mm; height: 0;
        margin: 0 auto 0.6mm auto;
        border-bottom: 0.35mm solid #1A1C1E;
    }
    .sig-lbl {
        font-size: 5.8px; font-style: italic;
        color: #163E22; letter-spacing: 0.3px;
    }

    /* QR code — bottom-right, on a solid white chip so the modules stay
     * legible over the guilloche pattern. z-index lifts it above the
     * watermark + guilloche layers below. */
    .qr {
        position: absolute; bottom: 3mm; right: 2mm;
        width: 17mm; height: 17mm;
        background: #ffffff;
        border: 0.25mm solid #C09020;
        padding: 0.6mm;
        z-index: 5;
    }
    .qr img, .qr svg {
        width: 100%; height: 100%; display: block;
    }

    /* Website — bottom, left-aligned to stay clear of QR */
    .web {
        position: absolute;
        bottom: 1.2mm; left: 2mm;
        font-size: 6.5px; font-weight: 700;
        color: #186D3B; letter-spacing: 0.4px;
    }
</style>
</head>
<body>
<div class="card">

    {{-- Guilloche security background --}}
    <div class="guilloche" aria-hidden="true">
        <svg viewBox="0 0 856 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <pattern id="gu" x="0" y="0" width="34" height="34" patternUnits="userSpaceOnUse">
                    <path d="M0,17 Q8.5,0 17,17 T34,17" fill="none" stroke="#C09020" stroke-width="0.6"/>
                    <path d="M0,17 Q8.5,34 17,17 T34,17" fill="none" stroke="#123420" stroke-width="0.6"/>
                    <path d="M17,0 Q34,8.5 17,17 T17,34" fill="none" stroke="#C09020" stroke-width="0.3"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#gu)"/>
        </svg>
    </div>

    {{-- Centre watermark --}}
    @if($roundLogoUrl)
    <div class="watermark" aria-hidden="true">
        <img src="{{ $roundLogoUrl }}" alt="">
    </div>
    @endif

    {{-- Sidebar — single CSS-rotated string --}}
    <div class="sidebar">
        <div class="edge"></div>
        <span class="sidebar-text">CHRSD</span>
    </div>

    <div class="body">

        {{-- Emblem header --}}
        <div class="emblem">
            @if($roundLogoUrl)<img src="{{ $roundLogoUrl }}" alt="CHRSD">@endif
        </div>
        <div class="org-name">
            Centre for Humanitarian Research &amp; Social Development Foundation
        </div>
        <div class="header-rule"></div>

        {{-- LEFT: instructions + contact --}}
        <div class="left-col">
            <div class="notice-h">Instructions &amp; Notice</div>
            <div class="notice">
                &bull; This card is the property of CHRSD.<br>
                &bull; Must be worn/carried at all times while on duty.<br>
                &bull; Must be returned upon resignation, termination, or upon request.<br>
                &bull; If found, please return to the address below.
            </div>
            <div class="contact">
                <div class="row">
                    <span class="icon">&#9742;</span>
                    <span class="txt">+880 2-47122566 | +880 1714-781490</span>
                </div>
                <div class="row">
                    <span class="icon">&#9993;</span>
                    <span class="txt">info@chrsd.org</span>
                </div>
                <div class="row">
                    <span class="icon">&#9675;</span>
                    <span class="txt">29 Toyenbee Circular Rd (5th Fl), Motijheel C/A, Dhaka-1000</span>
                </div>
            </div>
        </div>

        {{-- RIGHT: authorised signatory (only when signature image exists) --}}
        @if(! empty($signatureUrl))
        <div class="sig-block">
            <img class="sig-image" src="{{ $signatureUrl }}" alt="Signature">
            <div class="sig-line"></div>
            <div class="sig-lbl">Authorized Signatory</div>
        </div>
        @endif

        {{-- QR code — raw SVG for crispness at ID-card scale --}}
        <div class="qr">
            {!! $qr_svg !!}
        </div>

        {{-- Website footer --}}
        <div class="web">&#127760; www.chrsd.org</div>

    </div>
</div>
</body>
</html>
