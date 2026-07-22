@php
    /* =========================================================================
     |  Premium certificate LAYOUT (blank background).
     |
     |  This is the reusable shell — it draws the dark-navy premium background,
     |  frames, sheen lines, corner ornaments, atmospheric bloom, cross grid,
     |  watermark, and CHRSD brand mark. Child views extend it and fill the
     |  named sections:
     |
     |     @extends('certificates.layouts.premium')
     |     @section('issuer', 'CHRSD Foundation')
     |     @section('title',  'Certificate')
     |     @section('subtitle', 'of Recognition & Achievement')
     |     @section('presented', 'This is to certify that')
     |     @section('recipient', 'Alexander James Whitfield')
     |     @section('description', 'Has demonstrated …')
     |     @section('sig-1-name', 'Dr. Michael Rhodes')
     |     @section('sig-1-role', 'Chairman, Board of Trustees')
     |     @section('sig-2-name', 'Rev. Sarah Mitchell')
     |     @section('sig-2-role', 'Executive Director')
     |     @section('date-label', 'Date of Issuance')
     |     @section('date-value', '15th August 2025')
     |     @section('cert-no',    'CERT-2026-000042')
     |     @section('verify-url', 'https://chrsd.org/verify/ref/CERT-2026-000042')
     |
     |  Compatible with DomPDF, Browsershot, Snappy, and modern browsers.
     ========================================================================= */

    // Brand mark asset. PNG chosen over inline SVG because DomPDF's SVG
    // rasteriser strips gradients (the CHRSD logo relies on 20+ gradients),
    // while Browsershot / Snappy / browsers render PNG identically. The
    // source PNG is 3× the display size, so it stays crisp on print.
    $brandLogoUrl = asset('images/brand/chrsd-full-logo.png');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>@yield('page-title', 'Certificate')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    @page { size: A4 landscape; margin: 0; }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    html, body {
        width: 297mm; height: 210mm;
        overflow: hidden;
        font-family: 'Montserrat', 'Helvetica', 'Arial', 'DejaVu Sans', sans-serif;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    body {
        background: #0e1221;
        background-image: linear-gradient(155deg,
            #141928 0%, #0e1221 30%, #131829 55%, #0b0f1d 80%, #141928 100%);
        color: rgba(240,235,220,0.9);
        position: relative;
        page-break-inside: avoid;
        page-break-after: avoid;
    }

    /* ── Atmospheric bloom + noise (Browsershot/Chromium picks this up nicely;
       DomPDF ignores radial-gradient and shows the flat navy base — still
       reads as premium). ─────────────────────────────────────────────── */
    .atmosphere {
        position: absolute; inset: 0; pointer-events: none;
        background:
            radial-gradient(ellipse 70% 55% at 50% 48%, rgba(184,154,98,0.035) 0%, transparent 70%),
            radial-gradient(ellipse 35% 45% at 15% 25%, rgba(80,110,170,0.025) 0%, transparent 60%),
            radial-gradient(ellipse 35% 45% at 85% 75%, rgba(140,110,60,0.025) 0%, transparent 60%);
    }
    .bloom {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 65%; height: 65%; pointer-events: none;
        background: radial-gradient(ellipse, rgba(184,154,98,0.02) 0%, transparent 65%);
    }
    .vignette {
        position: absolute; inset: 0; pointer-events: none; z-index: 2;
        background: radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,0.4) 100%);
    }
    .cross-pattern {
        position: absolute; inset: 18mm; opacity: 0.012;
        pointer-events: none; z-index: 1;
        background-image:
            linear-gradient(rgba(184,154,98,1) 0.5px, transparent 0.5px),
            linear-gradient(90deg, rgba(184,154,98,1) 0.5px, transparent 0.5px);
        background-size: 8mm 8mm;
    }

    /* ── Frame system (four stacked hairline frames). ─────────────────── */
    .frame-1 { position: absolute; top: 7mm;  left: 7mm;  right: 7mm;  bottom: 7mm;
               border: 0.4px solid rgba(184,154,98,0.20); }
    .frame-2 { position: absolute; top: 9mm;  left: 9mm;  right: 9mm;  bottom: 9mm;
               border: 1.5px solid rgba(184,154,98,0.45); }
    .frame-3 { position: absolute; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm;
               border: 0.4px solid rgba(184,154,98,0.18); }
    .frame-4 { position: absolute; top: 15mm; left: 15mm; right: 15mm; bottom: 15mm;
               border: 0.3px solid rgba(184,154,98,0.08); }

    /* ── Gold sheen lines running horizontally and vertically. ────────── */
    .sh {
        position: absolute; height: 0.8px;
        left: 28mm; right: 28mm;
        background: linear-gradient(90deg,
            transparent 0%, rgba(184,154,98,0.06) 8%,
            rgba(212,175,55,0.18) 30%, rgba(244,229,168,0.28) 50%,
            rgba(212,175,55,0.18) 70%, rgba(184,154,98,0.06) 92%,
            transparent 100%);
    }
    .sh-t1 { top: 18mm; }
    .sh-t2 { top: 19mm; opacity: 0.45; }
    .sh-b1 { bottom: 18mm; }
    .sh-b2 { bottom: 19mm; opacity: 0.45; }

    .sv {
        position: absolute; width: 0.8px;
        top: 28mm; bottom: 28mm;
        background: linear-gradient(180deg,
            transparent 0%, rgba(184,154,98,0.06) 8%,
            rgba(212,175,55,0.18) 30%, rgba(244,229,168,0.28) 50%,
            rgba(212,175,55,0.18) 70%, rgba(184,154,98,0.06) 92%,
            transparent 100%);
    }
    .sv-l1 { left: 18mm; }
    .sv-l2 { left: 19mm; opacity: 0.45; }
    .sv-r1 { right: 18mm; }
    .sv-r2 { right: 19mm; opacity: 0.45; }

    /* ── Corner ornaments (inline SVG in body). ───────────────────────── */
    .co { position: absolute; width: 16mm; height: 16mm; z-index: 5; }
    .co-tr { top: 16mm; right: 16mm; }
    .co-tl { top: 16mm; left: 16mm;  transform: scaleX(-1); }
    .co-br { bottom: 16mm; right: 16mm; transform: scaleY(-1); }
    .co-bl { bottom: 16mm; left: 16mm;  transform: scale(-1,-1); }

    /* ── Watermark: soft circular seal + crosshair. ───────────────────── */
    .watermark {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%,-50%);
        width: 110mm; height: 110mm; z-index: 1;
        opacity: 0.02;
        pointer-events: none;
    }

    /* ── Ribbon flourishes tucked into the four corners. ──────────────── */
    .flourish {
        position: absolute; width: 180mm; height: 180mm;
        pointer-events: none; z-index: 3;
    }
    .fl-tr { top: -35mm; right: -35mm; opacity: 0.10; }
    .fl-bl { bottom: -35mm; left: -35mm; opacity: 0.06; }
    .fl-tl { top: -35mm; left: -35mm;  opacity: 0.045; }
    .fl-br { bottom: -35mm; right: -35mm; opacity: 0.045; }

    /* ── Ornament dividers (line — diamond — line). ───────────────────── */
    .orn {
        position: absolute; left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        white-space: nowrap;
    }
    .orn-top { top: 30mm; }
    .orn-bot { bottom: 26mm; }
    .orn .orn-line {
        display: inline-block; vertical-align: middle;
        width: 32mm; height: 0.4px;
        background: linear-gradient(90deg, transparent, rgba(184,154,98,0.35), transparent);
    }
    .orn .orn-dot {
        display: inline-block; vertical-align: middle;
        width: 3mm; height: 3mm;
        border: 0.4px solid rgba(184,154,98,0.45);
        transform: rotate(45deg);
        margin: 0 2.5mm;
    }

    /* ── CHRSD brand mark — small logo in a subtle gold-frame cartouche
         placed at the top. Uses inline SVG for maximum crispness. ─────── */
    .brand {
        position: absolute;
        top: 20mm;
        left: 50%;
        transform: translateX(-50%);
        width: 20mm; height: 20mm;
        z-index: 11;
        padding: 2mm;
        background: rgba(240,235,220,0.08);
        border: 0.4px solid rgba(184,154,98,0.35);
        border-radius: 50%;
        text-align: center;
    }
    .brand img { width: 100%; height: 100%; display: block; object-fit: contain; }
    .brand svg { width: 100%; height: 100%; display: block; }

    /* ── Main content column (issuer, title, recipient, description). ── */
    .content {
        position: absolute;
        top: 46mm;
        left: 45mm;
        right: 45mm;
        z-index: 10;
        text-align: center;
    }

    .issuer {
        font-family: 'Montserrat', sans-serif;
        font-size: 6.5pt; font-weight: 500;
        letter-spacing: 5.5px;
        text-transform: uppercase;
        color: rgba(184,154,98,0.55);
        margin-bottom: 2.5mm;
    }
    .title {
        font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
        font-size: 36pt; font-weight: 500;
        color: rgba(212,175,55,0.88);
        letter-spacing: 9px;
        text-transform: uppercase;
        margin-bottom: 1mm;
    }
    .title-sub {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-size: 10.5pt; font-weight: 300; font-style: italic;
        color: rgba(184,154,98,0.55);
        letter-spacing: 3px;
        margin-bottom: 6mm;
    }
    .presented {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-size: 10pt; font-weight: 400;
        color: rgba(200,195,185,0.55);
        letter-spacing: 1.5px;
        margin-bottom: 2mm;
    }
    .recipient {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 28pt; font-weight: 600;
        color: rgba(240,235,220,0.95);
        letter-spacing: 2.5px;
        margin-bottom: 4mm;
        line-height: 1.05;
        position: relative;
        display: inline-block;
    }
    .recipient::after {
        content: '';
        position: absolute;
        bottom: -1.5mm;
        left: 50%;
        transform: translateX(-50%);
        width: 55%;
        height: 0.4px;
        background: linear-gradient(90deg, transparent, rgba(184,154,98,0.35), transparent);
    }
    .description {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-size: 9.5pt; font-weight: 400;
        color: rgba(200,195,185,0.55);
        line-height: 1.75;
        margin: 6mm auto 0 auto;
        max-width: 175mm;
        letter-spacing: 0.3px;
    }

    /* ── Signature row (two columns, table for DomPDF reliability). ──── */
    .signatures {
        position: absolute;
        left: 30mm; right: 30mm;
        bottom: 31mm;
        height: 15mm;
        display: table; width: auto;
        z-index: 10;
    }
    .signatures .sig {
        display: table-cell;
        width: 50%; vertical-align: top;
        text-align: center;
    }
    .sig-line {
        width: 40mm; height: 0.4px;
        margin: 0 auto 1.5mm auto;
        background: linear-gradient(90deg, transparent, rgba(184,154,98,0.35), transparent);
    }
    .sig-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 9pt; font-weight: 500;
        color: rgba(212,175,55,0.80);
        letter-spacing: 1.5px;
    }
    .sig-role {
        font-family: 'Montserrat', sans-serif;
        font-size: 5.5pt; font-weight: 400;
        color: rgba(200,195,185,0.55);
        letter-spacing: 1.8px;
        text-transform: uppercase;
        margin-top: 0.5mm;
    }

    /* ── Date block (bottom-right). ───────────────────────────────────── */
    .date {
        position: absolute;
        bottom: 31mm; right: 22mm;
        z-index: 10; text-align: right;
    }
    .date-label {
        font-family: 'Montserrat', sans-serif;
        font-size: 5pt; font-weight: 400;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: rgba(200,195,185,0.45);
    }
    .date-val {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-size: 10pt; font-weight: 400;
        color: rgba(184,154,98,0.75);
        letter-spacing: 0.8px;
    }

    /* ── Seal (bottom-left). Uses inline SVG. ─────────────────────────── */
    .seal {
        position: absolute;
        bottom: 22mm; left: 22mm;
        width: 20mm; height: 20mm;
        z-index: 10;
        opacity: 0.7;
    }

    /* ── Foot strip (certificate number + verify URL). ────────────────── */
    .foot {
        position: absolute;
        left: 50%; transform: translateX(-50%);
        bottom: 12mm;
        z-index: 10;
        text-align: center;
        font-family: 'Montserrat', sans-serif;
    }
    .foot .cert-no {
        font-size: 7pt;
        letter-spacing: 2px;
        color: rgba(184,154,98,0.70);
        text-transform: uppercase;
    }
    .foot .cert-no strong { color: rgba(244,229,168,0.90); font-weight: 500; }
    .foot .verify {
        font-size: 5.5pt;
        color: rgba(200,195,185,0.35);
        letter-spacing: 1px;
        margin-top: 0.5mm;
    }

    /* Browser-only chrome (drop-shadow + surrounding tint). @supports
       gate hides this from DomPDF (which has no @supports parser). */
    @supports (backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px)) {
        html { background: #1a1e2c; padding: 20px 0; }
        body { margin: 0 auto; box-shadow: 0 25px 65px rgba(0,0,0,0.55); }
    }

    @media print {
        html, body { width: 297mm; height: 210mm; }
        body { margin: 0; }
    }
</style>
@stack('extra-styles')
</head>
<body>

<div class="atmosphere"></div>
<div class="cross-pattern"></div>
<div class="bloom"></div>

<div class="frame-1"></div>
<div class="frame-2"></div>
<div class="frame-3"></div>
<div class="frame-4"></div>

<div class="sh sh-t1"></div>
<div class="sh sh-t2"></div>
<div class="sh sh-b1"></div>
<div class="sh sh-b2"></div>
<div class="sv sv-l1"></div>
<div class="sv sv-l2"></div>
<div class="sv sv-r1"></div>
<div class="sv sv-r2"></div>

<div class="vignette"></div>

<!-- Watermark: soft concentric circles + crosshair -->
<svg class="watermark" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="200" cy="200" r="175" fill="none" stroke="#b89a62" stroke-width="1.2"/>
    <circle cx="200" cy="200" r="155" fill="none" stroke="#b89a62" stroke-width="0.4"/>
    <circle cx="200" cy="200" r="135" fill="none" stroke="#b89a62" stroke-width="0.25"/>
    <line x1="200" y1="35" x2="200" y2="365" stroke="#b89a62" stroke-width="0.7"/>
    <line x1="35" y1="200" x2="365" y2="200" stroke="#b89a62" stroke-width="0.7"/>
</svg>

{{-- Corner curved ornaments --}}
@php
    $cornerSvg = '<svg viewBox="0 0 70 70" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%"><path d="M70,0 L70,7 Q70,70 7,70 L0,70" fill="none" stroke="rgba(184,154,98,0.35)" stroke-width="0.5"/><path d="M70,0 L70,4 Q70,66 4,66 L0,66" fill="none" stroke="rgba(184,154,98,0.15)" stroke-width="0.4"/><circle cx="66" cy="4" r="0.8" fill="rgba(184,154,98,0.35)"/></svg>';
    $flourishSvg = '<svg viewBox="0 0 600 600" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%"><defs><linearGradient id="fg" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#f4e5a8"/><stop offset="50%" stop-color="#b8941f"/><stop offset="100%" stop-color="#c9a84c"/></linearGradient></defs><path d="M600,0 L600,110 Q600,600 110,600 L0,600" fill="none" stroke="url(#fg)" stroke-width="1.2"/><path d="M600,25 L600,90 Q590,570 120,570 L55,570" fill="none" stroke="url(#fg)" stroke-width="0.4"/></svg>';
@endphp
<div class="co co-tr">{!! $cornerSvg !!}</div>
<div class="co co-tl">{!! $cornerSvg !!}</div>
<div class="co co-br">{!! $cornerSvg !!}</div>
<div class="co co-bl">{!! $cornerSvg !!}</div>

<div class="flourish fl-tr">{!! $flourishSvg !!}</div>
<div class="flourish fl-bl">{!! $flourishSvg !!}</div>
<div class="flourish fl-tl">{!! $flourishSvg !!}</div>
<div class="flourish fl-br">{!! $flourishSvg !!}</div>

<div class="orn orn-top"><span class="orn-line"></span><span class="orn-dot"></span><span class="orn-line"></span></div>
<div class="orn orn-bot"><span class="orn-line"></span><span class="orn-dot"></span><span class="orn-line"></span></div>

{{-- CHRSD brand mark. Child views can override with `@section('brand') …
     @endsection` if the certificate is issued for another organisation. --}}
<div class="brand">
    @hasSection('brand')
        @yield('brand')
    @else
        <img src="{{ $brandLogoUrl }}" alt="CHRSD">
    @endif
</div>

{{-- Main content column. Text is passed as view variables (never through
     @section) so ampersands and other HTML characters escape exactly once. --}}
<div class="content">
    <div class="issuer">{{ $issuer ?? 'CHRSD Foundation' }}</div>
    <div class="title">{{ $title ?? 'Certificate' }}</div>
    <div class="title-sub">{{ $subtitle ?? 'of Recognition & Achievement' }}</div>

    <div class="presented">{{ $presented ?? 'This is to certify that' }}</div>
    <div class="recipient">{{ $name ?? 'Recipient Name' }}</div>

    @if (! empty($description))
        <div class="description">{{ $description }}</div>
    @endif

    @yield('content-extra')
</div>

<div class="signatures">
    <div class="sig">
        <div class="sig-line"></div>
        <div class="sig-name">{{ $signatory_1_name ?? 'Signatory Name' }}</div>
        <div class="sig-role">{{ $signatory_1_role ?? 'Signatory Title' }}</div>
    </div>
    <div class="sig">
        <div class="sig-line"></div>
        <div class="sig-name">{{ $signatory_2_name ?? 'Signatory Name' }}</div>
        <div class="sig-role">{{ $signatory_2_role ?? 'Signatory Title' }}</div>
    </div>
</div>

<div class="date">
    <div class="date-label">{{ $date_label ?? 'Date of Issuance' }}</div>
    <div class="date-val">{{ $issue_date_text ?? now()->format('jS F Y') }}</div>
</div>

{{-- Bottom-left circular seal (inline SVG). Child views can @section('seal')
     to swap in a different mark; leave blank to hide. --}}
<div class="seal">
    @hasSection('seal')
        @yield('seal')
    @else
        {{-- Solid-stroke seal — no gradients, no textPath — so DomPDF renders
             it identically to Chromium. Text sits along the diameter to
             avoid DomPDF's textPath limitations. --}}
        <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
            <circle cx="60" cy="60" r="54" fill="none" stroke="#c9a84c" stroke-width="1.1"/>
            <circle cx="60" cy="60" r="47" fill="none" stroke="#c9a84c" stroke-width="0.35" stroke-dasharray="1.8 1.8"/>
            <circle cx="60" cy="60" r="41" fill="none" stroke="#c9a84c" stroke-width="0.25"/>
            <line x1="60" y1="28" x2="60" y2="92" stroke="#c9a84c" stroke-width="0.9"/>
            <line x1="28" y1="60" x2="92" y2="60" stroke="#c9a84c" stroke-width="0.9"/>
            <circle cx="60" cy="60" r="7" fill="none" stroke="#c9a84c" stroke-width="0.5"/>
            <text x="60" y="63" text-anchor="middle"
                  font-family="Montserrat, Arial, sans-serif" font-size="4.5"
                  fill="#c9a84c" letter-spacing="1.2">VERIFIED</text>
        </svg>
    @endif
</div>

{{-- Foot: certificate number + verify URL. --}}
@if (! empty($certificate_no) || ! empty($verify_url))
    <div class="foot">
        @if (! empty($certificate_no))
            <div class="cert-no">CERTIFICATE No. <strong>{{ $certificate_no }}</strong></div>
        @endif
        @if (! empty($verify_url))
            <div class="verify">Verify at {{ $verify_url }}</div>
        @endif
    </div>
@endif

</body>
</html>
