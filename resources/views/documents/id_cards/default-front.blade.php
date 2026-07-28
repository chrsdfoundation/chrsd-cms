{{--
  CHRSD ID — FRONT · CR80 landscape (85.6 × 54 mm).
  mPDF render (CSS 2.1 only). All images must arrive as base64 data URIs from the service.
  Vars: $idCard, $employee (nullable), $photoUrl (data URI|null), $logoUrl (data URI),
        $roundLogoUrl (data URI).
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
    @page { margin: 0; size: 85.6mm 54mm; }
    * { margin: 0; padding: 0; box-sizing: border-box;
        -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { width: 85.6mm; height: 54mm; overflow: hidden; }
    body { font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif; color: #163E22; }

    .card {
        position: relative;
        width: 85.6mm;
        height: 54mm;
        background: #FFFFFF;
        overflow: hidden;
    }

    /* ── Security background: guilloche wave pattern ── */
    .guilloche {
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        opacity: 0.07;
        pointer-events: none;
        z-index: 0;
    }
    .guilloche svg { width: 100%; height: 100%; display: block; }

    /* ── Centre watermark: faint CHRSD emblem behind all content ── */
    .watermark {
        position: absolute;
        top: 50%; left: 50%;
        width: 28mm; height: 28mm;
        transform: translate(-50%, -50%);
        opacity: 0.045;
        pointer-events: none;
        z-index: 1;
    }
    .watermark img { width: 100%; height: 100%; display: block; }

    /* ── Left vertical sidebar ── */
    .sidebar {
        position: absolute; top: 0; left: 0;
        width: 10mm; height: 54mm;
        background: #123420;
        z-index: 2;
    }
    .sidebar .edge {
        position: absolute; top: 0; right: 0;
        width: 0.6mm; height: 54mm;
        background: #C09020;
    }
    /* Vertical text — absolute positioning in sidebar center */
    .sidebar-text {
        position: absolute;
        top: 50%; left: 50%;
        width: 8mm; height: 30mm;
        margin-top: -15mm;
        margin-left: -4mm;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 2px;
        color: #C9A14A;
        text-align: center;
        white-space: normal;
        word-break: break-all;
        line-height: 1.8;
        user-select: none;
    }

    /* ── Card body (right of sidebar) ── */
    .body { position: absolute; top: 0; left: 10mm; width: 75.6mm; height: 54mm; z-index: 3; }

    /* Logo */
    .logo { position: absolute; top: 2mm; left: 2mm; width: 13mm; height: 13mm; }
    .logo img { width: 100%; height: 100%; display: block; object-fit: contain; }

    /* Wordmark + org name + ID type */
    .brand { position: absolute; top: 2.5mm; left: 16mm; width: 34mm; }
    .brand .wm  { font-size: 12px; font-weight: 700; letter-spacing: 0.5px; color: #163E22; line-height: 1.1; }
    .brand .sub { font-size: 4.8px; color: #186D3B; line-height: 1.35; margin-top: 0.3mm; text-transform: uppercase; }
    .brand .type {
        font-size: 6px; font-weight: 700; letter-spacing: 1.5px;
        color: #C09020; text-transform: uppercase; margin-top: 1mm;
        border-top: 0.3mm solid #C09020; padding-top: 0.5mm;
    }

    .hdr-rule { position: absolute; top: 17mm; left: 2mm; width: 47mm; height: 0;
                border-top: 0.4mm solid #C09020; }

    /* Photo — fixed dimensions, no rounded corners for mPDF compatibility */
    .photo {
        position: absolute; top: 3mm; right: 3mm; width: 20mm; height: 24mm;
        border: 0.7mm solid #C09020;
        background-color: #EEEEE8;
        overflow: hidden;
    }
    .photo img.ph-img {
        width: 100%; height: 100%;
        display: block;
    }
    .photo .ph-placeholder {
        width: 100%; height: 100%;
        font-size: 6.5px; color: #999990; text-align: center;
        line-height: 1.3;
        padding: 2mm;
        vertical-align: middle;
    }

    /*
     * Hologram seal — offset to the bottom-left corner of the photo so it
     * straddles the photo/white-area boundary. Sits in .body coordinate
     * space so it can extend beyond the photo (which has overflow:hidden).
     * Photo occupies body-left 52.6mm..72.6mm, top 3mm..27mm; centre the
     * 12mm seal on the bottom-left corner (52.6, 27) → left 46.6mm, top 21mm.
     */
    .hologram-seal {
        position: absolute;
        top: 20mm; left: 46mm;
        width: 12mm; height: 12mm;
        opacity: 0.55;
        pointer-events: none;
        z-index: 4; /* above .photo (z-index inherited from .body = 3) */
    }
    .hologram-seal img {
        width: 100%; height: 100%; display: block;
    }

    /* Name + designation */
    .name  { position: absolute; top: 19mm; left: 2mm; width: 46mm;
             font-size: 12.5px; font-weight: 700; color: #163E22;
             line-height: 1.15; word-break: break-word; }
    .desig { position: absolute; top: 24mm; left: 2mm; width: 46mm;
             font-size: 8px; font-style: italic; color: #186D3B; line-height: 1.2; }
    .name-rule { position: absolute; top: 28mm; left: 2mm; width: 47mm; height: 0;
                 border-top: 0.4mm solid #C09020; }

    /* Data fields */
    .fields { position: absolute; top: 29.5mm; left: 2mm; width: 50mm;
              border-collapse: collapse; }
    .fields td { padding: 0.3mm 0; font-size: 7.5px; vertical-align: top; line-height: 1.2; }
    .fields .lbl { color: #186D3B; width: 18mm; }
    .fields .sep { width: 2mm; color: #186D3B; }
    .fields .val { color: #163E22; font-weight: 700; }

    /* Bottom disclaimer strip */
    .foot-rule { position: absolute; bottom: 8.5mm; left: 2mm; right: 2mm; height: 0;
                 border-top: 0.3mm solid #C09020; }
    .certify {
        position: absolute;
        bottom: 1.5mm; left: 2mm; right: 2mm;
        font-size: 5.5px; font-style: italic; color: #186D3B;
        line-height: 1.4; text-align: center;
    }
</style>
</head>
<body>
<div class="card">

    {{-- Guilloche wave security background --}}
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

    {{-- Centre watermark emblem --}}
    @if($roundLogoUrl)
    <div class="watermark" aria-hidden="true">
        <img src="{{ $roundLogoUrl }}" alt="">
    </div>
    @endif

    {{-- Left sidebar — single string, CSS vertical text --}}
    <div class="sidebar">
        <div class="edge"></div>
        <span class="sidebar-text">CHRSD</span>
    </div>

    {{-- Card body --}}
    <div class="body">

        <div class="logo">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="CHRSD">@endif
        </div>

        <div class="brand">
            <div class="wm">CHRSD</div>
            <div class="sub">Centre for Humanitarian Research &amp;<br>Social Development Foundation</div>
            <div class="type">{{ $idTypeLabel }}</div>
        </div>

        <div class="hdr-rule"></div>

        {{-- Portrait photo — <img> for Puppeteer (no seal overlay inside;
             the hologram lives outside so it can bleed onto white space). --}}
        <div class="photo">
            @if($photoUrl)
                <img class="ph-img" src="{{ $photoUrl }}" alt="Photo">
            @else
                <div class="ph-placeholder">PHOTO</div>
            @endif
        </div>

        {{-- Hologram seal — bottom-left corner of the photo, half on photo /
             half on card white area. Sits in .body coords, not inside .photo,
             because .photo has overflow:hidden. --}}
        @if($roundLogoUrl)
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
            <tr><td class="lbl">Valid From</td><td class="sep">:</td><td class="val">{{ optional($idCard->valid_from)->format('d M Y') }}</td></tr>
            <tr><td class="lbl">Expires</td><td class="sep">:</td><td class="val">{{ optional($idCard->valid_until)->format('d M Y') }}</td></tr>
        </table>

        <div class="foot-rule"></div>
        <div class="certify">
            This card certifies that the bearer is an authorized representative of CHRSD.
            All concerned are requested to extend necessary cooperation.
        </div>

    </div>
</div>
</body>
</html>
