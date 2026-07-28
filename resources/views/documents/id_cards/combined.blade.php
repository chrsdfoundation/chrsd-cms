{{--
  CHRSD ID — combined front + back on ONE A4 landscape sheet, side-by-side.
  All assets arrive as base64 data URIs from the IdCardGeneratorService.

  Vars:
    $idCard, $employee (nullable), $photoUrl (nullable, data URI)
    $qr_svg (raw SVG string), $verify_url
    $signatureUrl (nullable, data URI)
    $logoUrl, $roundLogoUrl (data URIs, not public_path strings)
--}}
@php
    $displayName  = $idCard->displayName();
    $designation  = $idCard->designation
        ?: $idCard->program_name
        ?: (optional(optional($employee)->position)->title ?? '');
    $bloodGroup   = $idCard->blood_group ?: 'N/A';
    $nationality  = $idCard->nationality ?: optional($employee)->nationality ?: '—';
    $idTypeLabel  = $idCard->id_type_label
        ?: (optional($idCard->idCardType)->name ?: 'Identity Card');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    @page { size: A4 landscape; margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box;
        -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background: #f6f5ee; font-family: 'Helvetica', 'Arial', 'DejaVu Sans', sans-serif; color: #163E22; }

    .sheet {
        position: relative;
        width: 297mm;
        height: 210mm;
        margin: 0 auto;
        background: #f6f5ee;
    }

    /* Header caption — small label above each card so the print shop knows
       what to cut. Printed lightly so it won't survive a trim. */
    .caption {
        position: absolute;
        top: 22mm;
        width: 85.6mm;
        text-align: center;
        font-size: 10pt;
        font-weight: 600;
        color: #6b6053;
        letter-spacing: 3px;
        text-transform: uppercase;
    }
    .caption.front { left: 46mm; }
    .caption.back  { left: 165.4mm; }

    /* Each card is a CR80-landscape rectangle rendered as a mini-page.
       Fixed dimensions (mPDF doesn't reflow in absolute positioning). */
    .card {
        position: absolute;
        top: 32mm;
        width: 85.6mm;
        height: 54mm;
        background: #FFFFFF;
        overflow: hidden;
    }
    .card.front { left: 46mm; }
    .card.back  { left: 165.4mm; }

    /* --- SHARED CHROME ------------------------------------------------- */
    .guilloche {
        position: absolute; top: 0; right: 0; bottom: 0; left: 0; opacity: 0.09;
        pointer-events: none; z-index: 0;
    }
    .guilloche svg { width: 100%; height: 100%; display: block; }

    .sidebar { position: absolute; top: 0; left: 0; width: 10mm; height: 54mm;
               background: #123420; z-index: 2; overflow: hidden; }
    .sidebar .edge { position: absolute; top: 0; right: 0; width: 0.6mm; height: 54mm; background: #C09020; }
    .sidebar .stack { position: absolute; top: 8mm; left: 0; width: 10mm; text-align: center; }
    .sidebar .stack div { font-size: 15px; font-weight: 700; letter-spacing: 1px;
                          color: #C9A14A; line-height: 7.8mm; }

    .inner { position: absolute; top: 0; left: 10mm; width: 75.6mm; height: 54mm; z-index: 3; }

    /* --- FRONT --------------------------------------------------------- */
    .front-logo    { position: absolute; top: 2mm; left: 2mm; width: 13mm; height: 13mm; }
    .front-logo img{ width: 100%; height: 100%; display: block; }

    .brand         { position: absolute; top: 2.5mm; left: 16mm; width: 34mm; }
    .brand .wm     { font-size: 12px; font-weight: 700; letter-spacing: 0.5px; color: #163E22; line-height: 1.1; }
    .brand .sub    { font-size: 5px;  color: #186D3B; line-height: 1.35; margin-top: 0.3mm; }
    .brand .type   { font-size: 6.5px; font-weight: 700; letter-spacing: 1.2px;
                     color: #C09020; text-transform: uppercase; margin-top: 0.8mm; }

    .hdr-rule      { position: absolute; top: 17mm; left: 2mm; width: 47mm; height: 0;
                     border-top: 0.4mm solid #C09020; }

    .photo {
        position: absolute; top: 3mm; right: 3mm; width: 20mm; height: 24mm;
        border: 0.7mm solid #C09020;
        background-color: #F1F1EC;
        overflow: hidden;
    }
    .photo img { width: 100%; height: 100%; display: block; }
    .photo .ph { text-align: center; line-height: 24mm; font-size: 7px; color: #8A8A82; }
    /* Hologram seal: straddles photo bottom-left corner (half on photo, half on white) */
    .hologram-seal {
        position: absolute; top: 20mm; left: 46mm;
        width: 12mm; height: 12mm;
        opacity: 0.55; pointer-events: none; z-index: 4;
    }
    .hologram-seal img { width: 100%; height: 100%; display: block; }

    .name  { position: absolute; top: 19mm; left: 2mm; width: 46mm;
             font-size: 13px; font-weight: 700; color: #163E22;
             line-height: 1.15; word-wrap: break-word; }
    .desig { position: absolute; top: 24mm; left: 2mm; width: 46mm;
             font-size: 8.5px; font-style: italic; color: #186D3B; line-height: 1.2; }
    .name-rule { position: absolute; top: 28mm; left: 2mm; width: 47mm; height: 0;
                 border-top: 0.4mm solid #C09020; }

    .fields { position: absolute; top: 29.5mm; left: 2mm; width: 50mm; border-collapse: collapse; }
    .fields td { padding: 0.3mm 0; font-size: 8px; vertical-align: top; line-height: 1.15; }
    .fields .lbl { color: #186D3B; width: 18mm; }
    .fields .sep { width: 2mm; color: #186D3B; }
    .fields .val { color: #163E22; font-weight: 700; }

    .foot-rule { position: absolute; bottom: 8mm; left: 2mm; right: 2mm; height: 0;
                 border-top: 0.4mm solid #C09020; }
    .certify {
        position: absolute; bottom: 1.2mm; left: 2mm; right: 2mm;
        font-size: 6px; font-style: italic; color: #186D3B; line-height: 1.35;
        text-align: center;
    }

    /* --- BACK ---------------------------------------------------------- */
    .emblem { position: absolute; top: 2mm; left: 32.5mm; width: 11mm; height: 11mm; }
    .emblem img { width: 100%; height: 100%; display: block; }

    .org-name {
        position: absolute; top: 13.5mm; left: 4mm; right: 4mm;
        text-align: center; font-size: 5.5px; color: #186D3B;
        line-height: 1.4; letter-spacing: 0.3px;
    }
    .header-rule {
        position: absolute; top: 17.5mm; left: 12mm; right: 12mm;
        height: 0; border-top: 0.4mm solid #C09020;
    }

    .left-col { position: absolute; top: 19mm; left: 2mm; width: 40mm; }
    .notice-h { font-size: 8.5px; font-weight: 700; color: #163E22; margin-bottom: 1mm; }
    .notice { font-size: 6.5px; color: #186D3B; line-height: 1.55; }
    .contact { margin-top: 2mm; font-size: 6.3px; color: #163E22; line-height: 1.45; }
    .contact .row { margin-bottom: 0.5mm; }
    .contact .icon {
        display: block; width: 3mm; text-align: center;
        color: #C09020; font-weight: 700;
    }

    .sig-block {
        position: absolute; top: 19mm; right: 2mm; width: 27mm; text-align: center;
    }
    .sig-image { display: block; width: 22mm; height: 8mm; margin: 0 auto -1.5mm auto; }
    .sig-line  { width: 24mm; height: 0; margin: 0 auto 0.8mm auto;
                 border-bottom: 0.35mm solid #1A1C1E; }
    .sig-lbl   { font-size: 6.5px; font-style: italic; color: #163E22; letter-spacing: 0.3px; }

    /* QR — white chip so modules stay legible over the guilloche pattern. */
    .qr {
        position: absolute; bottom: 3mm; right: 3mm;
        width: 17mm; height: 17mm;
        background: #ffffff;
        border: 0.25mm solid #C09020;
        padding: 0.6mm;
        z-index: 5;
    }
    .qr img, .qr svg { width: 100%; height: 100%; display: block; }

    .web {
        position: absolute; bottom: 1.2mm; left: 12mm; right: 12mm;
        text-align: center; font-size: 7px; font-weight: 700;
        color: #186D3B; letter-spacing: 0.5px;
    }
</style>
</head>
<body>
<div class="sheet">

    <div class="caption front">Front</div>
    <div class="caption back">Back</div>

    {{-- ================================================================
         FRONT SIDE
         ================================================================ --}}
    <div class="card front">
        <div class="guilloche" aria-hidden="true">
            <svg viewBox="0 0 856 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <defs>
                    <pattern id="gu1" x="0" y="0" width="34" height="34" patternUnits="userSpaceOnUse">
                        <path d="M0,17 Q8.5,0 17,17 T34,17" fill="none" stroke="#C09020" stroke-width="0.5"/>
                        <path d="M0,17 Q8.5,34 17,17 T34,17" fill="none" stroke="#123420" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#gu1)"/>
            </svg>
        </div>

        <div class="sidebar">
            <div class="edge"></div>
            <div class="stack"><div>C</div><div>H</div><div>R</div><div>S</div><div>D</div></div>
        </div>

        <div class="inner">
            <div class="front-logo">
                <img src="{{ $logoUrl }}" alt="CHRSD logo">
            </div>
            <div class="brand">
                <div class="wm">CHRSD</div>
                <div class="sub">CENTRE FOR HUMANITARIAN RESEARCH AND<br>SOCIAL DEVELOPMENT FOUNDATION</div>
                <div class="type">{{ $idTypeLabel }}</div>
            </div>
            <div class="hdr-rule"></div>

            <div class="photo" @if($photoUrl) style="background-image:url('{{ $photoUrl }}');" @endif>
                @unless($photoUrl)<div class="ph">PHOTO</div>@endunless
            </div>

            {{-- Hologram seal — outside .photo so it can bleed onto white --}}
            @if(! empty($roundLogoUrl))
            <div class="hologram-seal" aria-hidden="true">
                <img src="{{ $roundLogoUrl }}" alt="">
            </div>
            @endif

            <div class="name">{{ $displayName }}</div>
            <div class="desig">{{ $designation }}</div>
            <div class="name-rule"></div>

            <table class="fields">
                <tr><td class="lbl">ID No</td><td class="sep">:</td><td class="val">{{ $idCard->serial_number }}</td></tr>
                <tr><td class="lbl">Blood Group</td><td class="sep">:</td><td class="val">{{ $bloodGroup }}</td></tr>
                <tr><td class="lbl">Nationality</td><td class="sep">:</td><td class="val">{{ $nationality }}</td></tr>
                <tr><td class="lbl">Issue Date</td><td class="sep">:</td><td class="val">{{ optional($idCard->valid_from)->format('d M Y') }}</td></tr>
                <tr><td class="lbl">Expiry Date</td><td class="sep">:</td><td class="val">{{ optional($idCard->valid_until)->format('d M Y') }}</td></tr>
            </table>

            <div class="foot-rule"></div>
            <div class="certify">
                This ID certifies that the holder is an authorised personnel of CHRSD.
                All authorities are requested to extend necessary cooperation.
            </div>
        </div>
    </div>

    {{-- ================================================================
         BACK SIDE
         ================================================================ --}}
    <div class="card back">
        <div class="guilloche" aria-hidden="true">
            <svg viewBox="0 0 856 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <defs>
                    <pattern id="gu2" x="0" y="0" width="34" height="34" patternUnits="userSpaceOnUse">
                        <path d="M0,17 Q8.5,0 17,17 T34,17" fill="none" stroke="#C09020" stroke-width="0.5"/>
                        <path d="M0,17 Q8.5,34 17,17 T34,17" fill="none" stroke="#123420" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#gu2)"/>
            </svg>
        </div>

        <div class="sidebar">
            <div class="edge"></div>
            <div class="stack"><div>C</div><div>H</div><div>R</div><div>S</div><div>D</div></div>
        </div>

        <div class="inner">
            <div class="emblem">
                <img src="{{ $roundLogoUrl }}" alt="CHRSD emblem">
            </div>

            <div class="org-name">
                CENTRE FOR HUMANITARIAN RESEARCH AND<br>
                SOCIAL DEVELOPMENT FOUNDATION
            </div>
            <div class="header-rule"></div>

            <div class="left-col">
                <div class="notice-h">Instructions &amp; Notice</div>
                <div class="notice">
                    &bull; This card is property of CHRSD.<br>
                    &bull; Must be carried during duty.<br>
                    &bull; Must be returned upon request.
                </div>
                <div class="contact">
                    <div class="row">
                        <span class="icon">&#9678;</span>
                        <span>CHRSD, 29 Toyenbee Circular Road (5th Floor),<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;Motijheel C/A, Dhaka-1000, Bangladesh.</span>
                    </div>
                    <div class="row">
                        <span class="icon">&#9742;</span>
                        <span>+880-2-47122566, +880-1714781490.</span>
                    </div>
                </div>
            </div>

            <div class="sig-block">
                @if (! empty($signatureUrl))
                    <img class="sig-image" src="{{ $signatureUrl }}" alt="Authorised signature">
                @endif
                <div class="sig-line"></div>
                <div class="sig-lbl">Authorized Signatory</div>
            </div>

            <div class="qr">
                {!! $qr_svg !!}
            </div>

            <div class="web">www.chrsd.org</div>
        </div>
    </div>

</div>
</body>
</html>
