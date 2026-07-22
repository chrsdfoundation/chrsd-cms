<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { size: A4 {{ $orientation }}; margin: 0; }
  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body { margin: 0; padding: 0; font-family: 'Helvetica Neue','Helvetica','Arial',sans-serif; color: #1f2937; }
  .sheet {
    position: relative;
    width: {{ $orientation === 'landscape' ? '297mm' : '210mm' }};
    height: {{ $orientation === 'landscape' ? '210mm' : '297mm' }};
    box-sizing: border-box;
    background: #ffffff;
    overflow: hidden;
  }

  /* Double gold frame — clean, no corner rectangles overlapping the seal. */
  .frame-outer { position: absolute; top: 8mm; left: 8mm; right: 8mm; bottom: 8mm;
                 border: 1.2mm solid #C09020; border-radius: 2mm; z-index: 1; }
  .frame-inner { position: absolute; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm;
                 border: 0.25mm solid #A67718; border-radius: 1mm; z-index: 1; }

  /* Big centred round-seal watermark. Behind everything. */
  .watermark {
    position: absolute;
    top: 50%; left: 50%;
    width: {{ $orientation === 'landscape' ? '150mm' : '130mm' }};
    height: {{ $orientation === 'landscape' ? '150mm' : '130mm' }};
    margin-top: {{ $orientation === 'landscape' ? '-75mm' : '-65mm' }};
    margin-left: {{ $orientation === 'landscape' ? '-75mm' : '-65mm' }};
    opacity: 0.07;
    pointer-events: none;
    z-index: 1;
  }
  .watermark img { width: 100%; height: 100%; display: block; object-fit: contain; }

  /* Header — full logo centred at the top. Compacted vertically to give
     the body more room for long copies like the Certificate of Service. */
  .header {
    position: absolute;
    top: 14mm;
    left: {{ $orientation === 'landscape' ? '80mm' : '45mm' }};
    right: {{ $orientation === 'landscape' ? '80mm' : '45mm' }};
    text-align: center;
    z-index: 3;
  }
  .header .logo img { width: {{ $orientation === 'landscape' ? '34mm' : '30mm' }}; display: block; margin: 0 auto; }
  .header .org-name { font-size: 9pt; color: #123420; font-weight: 700;
                      letter-spacing: 1.8px; margin-top: 2mm; line-height: 1.35; }

  /*
   * Body — occupies ONLY the middle band between the header and the
   * fixed-position signature strip below. overflow:hidden guarantees a
   * long-content template can never spill into the signature/QR zones.
   * top:68mm (portrait) — clears the header.
   * bottom:80mm — reserves 80mm at the bottom for:
   *   • signature strip at bottom:52mm..70mm  (18mm slot)
   *   • QR badge at bottom:22mm..38mm         (16mm slot)
   *   • info line at bottom:12mm..16mm        (4mm slot)
   *   with generous safety gaps between each layer.
   */
  .body {
    position: absolute;
    top: {{ $orientation === 'landscape' ? '56mm' : '58mm' }};
    left: {{ $orientation === 'landscape' ? '30mm' : '22mm' }};
    right: {{ $orientation === 'landscape' ? '30mm' : '22mm' }};
    bottom: {{ $orientation === 'landscape' ? '62mm' : '70mm' }};
    text-align: center;
    z-index: 3;
    overflow: hidden;
  }
  .body h1 {
    font-family: 'Playfair Display','Times New Roman',serif;
    color: #123420; font-weight: 700;
    font-size: {{ $orientation === 'landscape' ? '24pt' : '20pt' }};
    letter-spacing: 3px; margin: 0 0 2mm;
    text-transform: uppercase; line-height: 1.1;
  }
  .body h2 { color: #4b5563; font-weight: 400; font-size: 12pt;
             letter-spacing: 1.2px; margin: 0 0 2mm; }
  .body h3 { color: #163E22; font-weight: 700; font-size: 10.5pt; margin: 2mm 0 1mm; }
  .body .divider { width: 20%; margin: 1.5mm auto 3mm; border-top: 0.5mm solid #C09020; }
  .body p { font-size: 10pt; line-height: 1.55; margin: 0 0 3mm; color: #374151; text-align: justify; }
  .body p:first-of-type, .body p.center { text-align: center; }
  .body strong { color: #123420; }
  .body em     { color: #4b5563; }
  .body .name {
    font-family: 'Playfair Display','Times New Roman',serif;
    font-style: italic; font-size: 20pt; color: #123420; font-weight: 700;
    border-bottom: 0.4mm solid #C09020; padding-bottom: 2mm; margin: 3mm 15mm;
  }
  .body ul, .body ol { text-align: left; margin: 3mm 20mm; font-size: 10pt; }

  /* Body tables — labels left-aligned, values right-aligned. */
  .body table {
    width: 76%; margin: 2mm auto 3mm; border-collapse: collapse; font-size: 9.5pt;
    text-align: left;
    table-layout: fixed;
  }
  .body table th, .body table td {
    padding: 1.3mm 3.5mm; border-bottom: 0.2mm solid #E5E7EB; vertical-align: top;
    line-height: 1.35;
  }
  .body table th { background: #FAF5E5; color: #123420; font-weight: 700; letter-spacing: 0.3px; }
  .body table td:first-child  { width: 40%; color: #123420; font-weight: 600; }
  .body table td:nth-child(2) { width: 60%; color: #374151; font-weight: 400; }

  /*
   * Legacy inline sig-tbl support — some existing templates still embed
   * a `<table class="sig-tbl">` in body_markdown. Hide it: the shell now
   * renders its own fixed-position signature strip below.
   */
  .body .sig-tbl { display: none; }

  /*
   * FIXED-POSITION signature strip — outside body flow, so no amount of
   * body content can push it into the QR area. Renders only when at
   * least one signatory name is present.
   */
  .signature-strip {
    position: absolute;
    bottom: 44mm;
    left: {{ $orientation === 'landscape' ? '30mm' : '22mm' }};
    right: {{ $orientation === 'landscape' ? '30mm' : '22mm' }};
    height: 20mm;
    z-index: 3;
  }
  .signature-strip table {
    width: 100%; height: 100%; border-collapse: collapse;
    font-size: 9.5pt; color: #374151;
  }
  .signature-strip td {
    width: 33%;
    padding: 0 3mm;
    vertical-align: bottom;
  }
  .signature-strip .sig-l { text-align: left; }
  .signature-strip .sig-c { text-align: center; }
  .signature-strip .sig-r { text-align: right; }
  .signature-strip .sig-img {
    display: block; max-height: 12mm; max-width: 46mm;
    width: auto; height: auto; object-fit: contain;
    margin-bottom: 0.5mm;
  }
  .signature-strip .sig-l .sig-img { margin-left: 0;    margin-right: auto; }
  .signature-strip .sig-c .sig-img { margin-left: auto; margin-right: auto; }
  .signature-strip .sig-r .sig-img { margin-left: auto; margin-right: 0;    }
  .signature-strip .line {
    border-top: 0.35mm solid #123420; padding-top: 1mm;
    min-height: 5mm; margin-bottom: 0.5mm;
    font-family: 'Playfair Display','Times New Roman',serif;
    font-weight: 700; color: #123420;
  }
  .signature-strip .role {
    font-size: 8pt; color: #6b7280; letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  /*
   * QR badge — free-standing, 14mm × 14mm at bottom:22mm.
   * TOP of QR at bottom:36mm.
   * BOTTOM of signature strip at bottom:52mm.
   * Guaranteed gap: 16mm. Nothing above can reach into this zone because
   * .body has overflow:hidden and the signature strip has a fixed y.
   */
  .qr-badge {
    position: absolute;
    bottom: 22mm;
    left: 50%;
    width: 14mm; height: 14mm;
    margin-left: -7mm;
    background: #ffffff;
    border: 0.25mm solid #C09020;
    border-radius: 1mm;
    padding: 0.6mm;
    z-index: 4;
  }
  .qr-badge img, .qr-badge svg { width: 100%; height: 100%; display: block; }

  /*
   * Footer info line — sits BELOW the QR (at bottom:14mm) with cert-id on
   * the left and website / email on the right. Both capped at max-width:45%
   * so they can never encroach on the centred QR chip.
   */
  .footer {
    position: absolute; bottom: 14mm; left: 24mm; right: 24mm;
    font-size: 8.5pt; color: #6b7280; z-index: 3;
    height: 6mm;
  }
  .footer .left  { position: absolute; left:  0; bottom: 0; max-width: 45%; }
  .footer .right { position: absolute; right: 0; bottom: 0; text-align: right; max-width: 45%; }
  .footer a { color: #0F3D1E; text-decoration: none; }
  .footer .cert-link { border-bottom: 0.2mm dotted #C09020; }
  .footer .cert-link strong { color: #C09020; letter-spacing: 0.5px; }
</style>
</head>
<body>
  <div class="sheet">
    <div class="frame-outer"></div>
    <div class="frame-inner"></div>

    @php
        $watermarkPath = public_path('images/brand/chrsd-round-logo.png');
        if (! file_exists($watermarkPath)) {
            $watermarkPath = public_path('images/chrsd-round-seal.png');
        }
    @endphp
    <div class="watermark" aria-hidden="true">
      <img src="{{ $watermarkPath }}" alt="">
    </div>

    <div class="header">
      <div class="logo"><img src="{{ public_path('images/chrsd-full-logo.png') }}" alt="CHRSD"></div>
      <div class="org-name">{{ strtoupper($org_full_name ?? 'CENTRE FOR HUMANITARIAN RESEARCH AND SOCIAL DEVELOPMENT FOUNDATION') }}</div>
    </div>

    <div class="body">
      {!! $bodyHtml !!}
    </div>

    {{-- Fixed-position signature strip. Rendered only when we actually
         have signatory info in context. --}}
    @if (! empty($signatory_1_name) || ! empty($signatory_2_name))
    <div class="signature-strip">
      <table>
        <tr>
          <td class="sig-l">
            @if (! empty($signatory_1_sig))
              <img class="sig-img" src="{{ $signatory_1_sig }}" alt="">
            @endif
            <div class="line">{{ $signatory_1_name ?? '' }}</div>
            <div class="role">{{ $signatory_1_title ?? '' }}</div>
          </td>
          <td class="sig-c">
            <div class="line">{{ $issue_date ?? '' }}</div>
            <div class="role">Date of Issue</div>
          </td>
          <td class="sig-r">
            @if (! empty($signatory_2_sig))
              <img class="sig-img" src="{{ $signatory_2_sig }}" alt="">
            @endif
            <div class="line">{{ $signatory_2_name ?? '' }}</div>
            <div class="role">{{ $signatory_2_title ?? '' }}</div>
          </td>
        </tr>
      </table>
    </div>
    @endif

    {{-- QR badge — dedicated fixed position; nothing above can reach it. --}}
    <div class="qr-badge">{!! $qr_raw ?? '' !!}</div>

    <div class="footer">
      <div class="left">
        <a class="cert-link" href="{{ $verification_url ?? '#' }}">Certificate ID: <strong>{{ $certificate_number ?? '' }}</strong></a>
      </div>
      <div class="right">www.chrsd.org &nbsp;|&nbsp; info@chrsd.org</div>
    </div>
  </div>
</body>
</html>
