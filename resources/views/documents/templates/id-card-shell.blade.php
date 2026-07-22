<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { size: 85.6mm 54mm; margin: 0; }
  body { margin: 0; padding: 0; font-family: 'DejaVu Sans','Helvetica',sans-serif; color: #123420; font-size: 7pt; }
  .card { position: relative; width: 85.6mm; height: 54mm; box-sizing: border-box; background: #ffffff; }

  /* Left dark-green sidebar with vertical letters */
  .sidebar { position: absolute; top: 0; left: 0; width: 7mm; height: 54mm; background: #123420; text-align: center; }
  .sidebar .letter { color: #C9A14A; font-size: 8pt; font-weight: 700; margin-top: 4mm; }
  .sidebar .letter:first-child { margin-top: 6mm; }
  .sidebar-gold { position: absolute; top: 0; left: 7mm; width: 1.2mm; height: 54mm; background: #C9A14A; }

  /* Header row: emblem + wordmark right of sidebar */
  .header { position: absolute; top: 3mm; left: 10mm; right: 3mm; height: 11mm; }
  .header .logo { position: absolute; left: 0; top: 0; width: 9mm; }
  .header .logo img { width: 100%; }
  .header .org { position: absolute; left: 11mm; top: 0; right: 0; }
  .header .n1 { font-size: 11pt; font-weight: 700; color: #123420; letter-spacing: 0.5px; }
  .header .n2 { font-size: 5pt; color: #163E22; margin-top: 0.4mm; line-height: 1.15; }
  .divider { position: absolute; top: 14mm; left: 10mm; right: 27mm; height: 0.4mm; background: #C9A14A; }

  /* Photo top-right */
  .photo {
    position: absolute; top: 15mm; right: 3mm;
    width: 22mm; height: 26mm; border: 0.4mm solid #C9A14A; overflow: hidden; background: #EFEDE6;
  }
  .photo img { width: 100%; height: 100%; }
  .photo .ph {
    text-align: center; padding-top: 9mm; color: #6b7280; font-size: 6pt;
  }

  /* Body area */
  .body {
    position: absolute;
    top: 15.5mm; left: 10mm; right: 28mm;
    font-size: 7pt; line-height: 1.28;
  }
  .body h1, .body h2 { margin: 0 0 0.5mm; }
  .body h1 { font-size: 10.5pt; color: #123420; font-weight: 700; }
  .body h2 { font-size: 8pt; color: #163E22; font-weight: 400; }
  .body p  { margin: 0 0 0.4mm; font-size: 6.8pt; }
  .body table { width: 100%; border-collapse: collapse; font-size: 6.5pt; margin-top: 0.4mm; }
  .body table td { padding: 0.25mm 0; vertical-align: top; }
  .body table td.k { font-weight: 700; color: #163E22; padding-right: 1mm; width: 22mm; }
  .body strong { color: #123420; }

  .footer {
    position: absolute; bottom: 1mm; left: 10mm; right: 3mm; font-size: 5.5pt; color: #163E22;
  }
  .footer .serial { display: inline-block; font-family: 'DejaVu Sans Mono', monospace; }
  .footer .verify { float: right; }
</style>
</head>
<body>
  <div class="card">
    <div class="sidebar">
      <div class="letter">C</div>
      <div class="letter">H</div>
      <div class="letter">R</div>
      <div class="letter">S</div>
      <div class="letter">D</div>
    </div>
    <div class="sidebar-gold"></div>

    <div class="header">
      <div class="logo"><img src="{{ public_path('images/chrsd-full-logo.png') }}"></div>
      <div class="org">
        <div class="n1">{{ $org_short ?? 'CHRS DEVELOPMENT' }}</div>
        <div class="n2">CENTRE FOR HUMANITARIAN RESEARCH AND<br>SOCIAL DEVELOPMENT FOUNDATION</div>
      </div>
    </div>
    <div class="divider"></div>

    @if(!empty($photo_url))
      <div class="photo"><img src="{{ $photo_url }}"></div>
    @else
      <div class="photo"><div class="ph">PHOTO</div></div>
    @endif

    <div class="body">
      {!! $bodyHtml !!}
    </div>

    <div class="footer">
      <span class="serial">{{ $certificate_number ?? '' }}</span>
      <span class="verify">chrsd.org/verify</span>
    </div>
  </div>
</body>
</html>
