<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { size: A4 {{ $orientation }}; margin: 0; }
  body { margin: 0; padding: 0; font-family: 'DejaVu Sans','Helvetica',sans-serif; color: #1f2937; }

  /* Palette resolves from `variant_palette` (course-green | course-blue) */
  @php
    $isBlue = ($shell_variant ?? '') === 'course-completion-blue';
    $primary   = $isBlue ? '#1E40AF' : '#123420'; // deep blue vs CHRSD green
    $secondary = $isBlue ? '#3B5FE6' : '#186D3B'; // lighter accent
    $gold      = '#C09020';
    $goldLight = '#DFC067';
    $textDim   = '#4b5563';
    $courseColor = $isBlue ? '#1E3A8A' : '#123420';
  @endphp

  .sheet {
    position: relative;
    width: {{ $orientation === 'landscape' ? '297mm' : '210mm' }};
    height: {{ $orientation === 'landscape' ? '210mm' : '297mm' }};
    box-sizing: border-box;
    background: #ffffff;
  }

  /* Faint frame */
  .frame { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 0.6mm solid {{ $gold }}; }

  /* Wave motif — solid organic-shaped blocks in top-left + bottom-right.
     DomPDF renders asymmetric border-radius as rounded corners (best it can
     do). Shapes stay OUTSIDE the frame's content area so they don't overlap
     the QR/logo/seal/signature blocks. */
  .wave-tl-1 {
    position: absolute; top: -30mm; left: -40mm;
    width: 130mm; height: 45mm;
    background: {{ $primary }};
    border-radius: 0 0 80mm 20mm / 0 0 40mm 20mm;
  }
  .wave-tl-2 {
    position: absolute; top: -20mm; left: -40mm;
    width: 100mm; height: 32mm;
    background: {{ $secondary }};
    border-radius: 0 0 60mm 20mm / 0 0 32mm 20mm;
    opacity: 0.8;
  }

  .wave-br-1 {
    position: absolute; bottom: -30mm; right: -40mm;
    width: 130mm; height: 45mm;
    background: {{ $primary }};
    border-radius: 80mm 0 0 0 / 40mm 0 0 0;
  }
  .wave-br-2 {
    position: absolute; bottom: -20mm; right: -40mm;
    width: 100mm; height: 32mm;
    background: {{ $secondary }};
    border-radius: 60mm 0 0 0 / 32mm 0 0 0;
    opacity: 0.8;
  }

  /* Watermark seal — subtle circular seal behind body content */
  .watermark {
    position: absolute;
    top: {{ $orientation === 'landscape' ? '55mm' : '85mm' }};
    left: {{ $orientation === 'landscape' ? '90mm' : '55mm' }};
    width: 110mm; height: 110mm;
    opacity: 0.04;
  }
  .watermark img { width: 100%; height: 100%; }

  /* Top-left: QR + short code */
  .qr-block {
    position: absolute; top: 30mm; left: 26mm;
    text-align: center; z-index: 2;
  }
  .qr-block .qr { width: 30mm; height: 30mm; padding: 2mm; background: #ffffff; border: 0.4mm solid #d4d4d8; }
  .qr-block .qr img { width: 26mm; height: 26mm; }
  .qr-block .code {
    font-family: 'DejaVu Sans Mono', monospace;
    font-size: 9pt; font-weight: 700; color: {{ $courseColor }}; margin-top: 3mm;
    letter-spacing: 1px;
  }

  /* Top-right: partner logo / issuer emblem */
  .issuer {
    position: absolute; top: 24mm; right: 28mm;
    width: 40mm; text-align: center; z-index: 2;
  }
  .issuer img { width: 26mm; }
  .issuer .n1 { font-size: 8pt; color: {{ $primary }}; font-weight: 700; margin-top: 2mm; letter-spacing: 0.5px; }
  .issuer .n2 { font-size: 7pt; color: {{ $textDim }}; margin-top: 1mm; }

  /* Body — centered */
  .body {
    position: absolute;
    top: {{ $orientation === 'landscape' ? '38mm' : '50mm' }};
    left: {{ $orientation === 'landscape' ? '75mm' : '30mm' }};
    right: {{ $orientation === 'landscape' ? '75mm' : '30mm' }};
    text-align: center;
    z-index: 1;
  }
  .body h1 {
    font-family: 'DejaVu Serif', 'Times New Roman', serif;
    color: {{ $primary }}; font-weight: 700;
    font-size: {{ $orientation === 'landscape' ? '30pt' : '26pt' }};
    letter-spacing: 6px; margin: 0 0 1mm; line-height: 1.0;
  }
  .body h2 {
    font-family: 'DejaVu Serif', 'Times New Roman', serif;
    color: {{ $gold }}; font-weight: 700;
    font-size: {{ $orientation === 'landscape' ? '15pt' : '13pt' }};
    letter-spacing: 4px; margin: 1mm 0 6mm;
  }
  .body h3 {
    color: {{ $courseColor }}; font-weight: 700; font-size: 22pt; margin: 3mm 0 3mm;
    letter-spacing: 0.5px;
  }
  .body .certify-line {
    font-size: 10pt; color: {{ $primary }}; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    margin-bottom: 2mm;
  }
  .body .date-line {
    font-family: 'DejaVu Serif', 'Times New Roman', serif;
    font-size: 12pt; color: {{ $primary }}; margin-bottom: 6mm;
  }
  .body .name {
    font-family: 'DejaVu Serif', 'Times New Roman', serif;
    font-style: italic; font-size: 30pt; color: #262626; font-weight: 400;
    margin: 4mm 15mm 1mm;
  }
  .body .divider-ornament {
    margin: 2mm 15mm 4mm; text-align: center;
    border-top: 0.4mm solid {{ $gold }};
    position: relative;
    padding-top: 3mm;
  }
  .body .divider-ornament .diamond {
    display: inline-block; width: 4mm; height: 4mm; background: {{ $gold }};
    transform: rotate(45deg); margin: -6mm auto 0;
  }
  .body .divider-ornament-simple { margin: 4mm 30mm; border-top: 0.4mm solid {{ $gold }}; }
  .body .course-lead {
    font-size: 11pt; color: {{ $primary }}; font-style: italic; margin: 2mm 0;
  }
  .body p { font-size: 10.5pt; line-height: 1.5; margin: 0 0 2mm; color: {{ $textDim }}; }
  .body strong { color: #262626; }

  /* Bottom-left: gold seal (uses chrsd-seal.png = the gold ribbon seal from step 22) */
  .seal-bl {
    position: absolute; bottom: 24mm; left: 26mm; width: 24mm; text-align: center; z-index: 2;
  }
  .seal-bl img { width: 24mm; height: 24mm; }

  /* Signature block (center-bottom) */
  .sig-block {
    position: absolute; bottom: 24mm;
    left: {{ $orientation === 'landscape' ? '90mm' : '55mm' }};
    right: {{ $orientation === 'landscape' ? '90mm' : '55mm' }};
    text-align: center; z-index: 2;
  }
  .sig-block .sig-name {
    font-family: 'DejaVu Serif', 'Times New Roman', serif; font-style: italic;
    font-size: 16pt; color: #262626;
  }
  .sig-block .sig-line { border-top: 0.4mm solid #333; margin: 2mm 20mm 0; padding-top: 1.5mm; }
  .sig-block .sig-title { font-size: 9pt; font-weight: 700; color: {{ $primary }}; }
  .sig-block .sig-sub { font-size: 8pt; color: {{ $textDim }}; margin-top: 0.5mm; }

  /* Bottom-right: partner / accreditor logo (uses chrsd-full-logo.png as CHRSD-branded partner) */
  .partner-br {
    position: absolute; bottom: 24mm; right: 26mm; width: 42mm; text-align: center; z-index: 2;
  }
  .partner-br img { width: 32mm; }
  .partner-br .label {
    font-size: 7pt; color: {{ $textDim }}; margin-top: 1mm; letter-spacing: 0.4px;
  }

  /* Footer disclaimer */
  .footer-disclaimer {
    position: absolute; bottom: 14mm; left: 20mm; right: 20mm;
    text-align: center; font-size: 8pt; color: {{ $primary }}; font-style: italic;
  }
</style>
</head>
<body>
  <div class="sheet">
    <div class="wave-tl-1"></div>
    <div class="wave-tl-2"></div>
    <div class="wave-br-1"></div>
    <div class="wave-br-2"></div>

    <div class="frame"></div>

    <div class="watermark"><img src="{{ public_path('images/chrsd-round-seal.png') }}"></div>

    <div class="qr-block">
      <div class="qr">{!! $qr_raw ?? '' !!}</div>
      <div class="code">{{ $verify_code ?? substr($certificate_number ?? '', -12) }}</div>
    </div>

    <div class="issuer">
      <img src="{{ public_path('images/chrsd-full-logo.png') }}">
      <div class="n1">{{ $issuer_name ?? 'CHRSD LEARNING' }}</div>
      <div class="n2">{{ $issuer_tagline ?? 'Centre for Humanitarian Research' }}</div>
    </div>

    <div class="body">
      {!! $bodyHtml !!}
    </div>

    <div class="seal-bl">
      <img src="{{ public_path('images/chrsd-seal.png') }}">
    </div>

    <div class="sig-block">
      <div class="sig-name">{{ $signatory_name ?? '' }}</div>
      <div class="sig-line"></div>
      <div class="sig-title">{{ $signatory_name ?? '' }}</div>
      <div class="sig-sub">{{ $signatory_title ?? '' }}</div>
      <div class="sig-sub">{{ $organization ?? config('app.name') }}</div>
    </div>

    <div class="partner-br">
      <img src="{{ public_path('images/chrsd-full-logo.png') }}">
      <div class="label">Issued by {{ $organization ?? 'CHRSD Foundation' }}</div>
    </div>

    <div class="footer-disclaimer">
      Verified digitally at {{ $verification_url ?? '' }} &nbsp;|&nbsp; Certificate ID: <strong>{{ $certificate_number ?? '' }}</strong>
    </div>
  </div>
</body>
</html>
