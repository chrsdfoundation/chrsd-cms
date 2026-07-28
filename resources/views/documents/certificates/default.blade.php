{{--
  CHRSD-branded Certificate of Employment / Appreciation — A4 landscape.
  Design ported from the CHRSD Design Refinement Spec v2.
  Vars: $certificate, $employee, $type, $signatory, $qr_uri, $verify_url.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 0; size: A4 landscape; }
  body { margin: 0; padding: 0; }

  .sheet { position:relative; width:297mm; height:210mm; background:#EFEDE6;
           font-family:'Helvetica', sans-serif; color:#45474A; }

  /* Gold corners — CSS-only reconstruction of the ribbon accents */
  .corner-tr  { position:absolute; top:0; right:0; width:46mm; height:6mm; background:#C09020; }
  .corner-tr2 { position:absolute; top:6mm; right:0; width:30mm; height:2mm; background:#DFC067; }
  .corner-br  { position:absolute; bottom:0; right:0; width:46mm; height:6mm; background:#C09020; }
  .corner-br2 { position:absolute; bottom:6mm; right:0; width:30mm; height:2mm; background:#DFC067; }

  .frame { position:absolute; top:10mm; left:10mm; right:10mm; bottom:10mm; border:1.2mm solid #FFFFFF; }

  /* Emblem + wordmark lockup */
  .logo { position:absolute; top:20mm; left:20mm; width:32mm; height:32mm; }
  .logo img { display:block; width:100%; height:100%; object-fit:contain; }
  .lockup { position:absolute; top:24mm; left:56mm; }
  .lockup .wm { font-size:16px; font-weight:700; letter-spacing:.5px; color:#163E22; }
  .lockup .sub { font-size:8px; color:#186D3B; line-height:1.3; margin-top:1mm; }

  .cert-id { position:absolute; top:22mm; right:30mm; font-size:12px; color:#163E22; }
  .cert-id .v { font-weight:700; font-family:'Courier', monospace; }

  .h-cert { position:absolute; top:66mm; left:0; right:0; text-align:center; font-weight:700;
            font-size:60px; letter-spacing:6px; color:#444649; line-height:1; }
  .h-appr { position:absolute; top:88mm; left:0; right:0; text-align:center; font-family:'Times', serif;
            font-weight:700; font-size:26px; letter-spacing:3px; color:#444649; }
  .presented { position:absolute; top:106mm; left:0; right:0; text-align:center; font-size:11px;
               letter-spacing:2px; text-transform:uppercase; color:#7A7C80; }

  /* Auto-shrinking script name */
  .name { position:absolute; top:113mm; left:48mm; width:200mm; height:20mm; overflow:hidden; text-align:center;
          font-family:'Times', serif; font-style:italic; font-weight:700;
          color:#A77C1E; line-height:1.1; word-wrap:break-word; }
  .name-rule { position:absolute; top:134mm; left:88mm; width:120mm; height:0; border-top:.4mm solid #C09020; }

  .body { position:absolute; top:139mm; left:33mm; width:230mm; text-align:center;
          font-size:13px; line-height:1.6; color:#45474A; }
  .body strong { color:#163E22; }

  .seal { position:absolute; bottom:26mm; left:133.5mm; width:30mm; height:30mm; }
  .seal img { display:block; width:100%; height:100%; object-fit:contain; }

  .date { position:absolute; bottom:32mm; left:40mm; text-align:center; }
  .date .v { font-size:14px; font-weight:700; color:#1A1C1E; }
  .date .rule { width:42mm; height:0; margin:2mm auto 1mm; border-top:.4mm solid #1A1C1E; }
  .date .lbl { font-size:10px; color:#7A7C80; }

  .sign { position:absolute; bottom:32mm; right:40mm; text-align:center; }
  .sign .n { font-size:14px; font-weight:700; color:#1A1C1E; }
  .sign .rule { width:52mm; height:0; margin:1mm auto 1mm; border-top:.4mm solid #1A1C1E; }
  .sign .lbl { font-size:10px; color:#45474A; }

  /* Verification footer */
  .verify-block { position:absolute; bottom:8mm; left:20mm; }
  .verify-block .qr img { width:22mm; height:22mm; }
  .verify-block .caption { font-size:8px; color:#7A7C80; margin-top:1mm; max-width:26mm; }
  .serial-footer { position:absolute; bottom:10mm; left:52mm; font-size:9px; color:#7A7C80;
                   font-family:'Courier', monospace; }
</style>
</head>
<body>
  <div class="sheet">
    <div class="corner-tr"></div><div class="corner-tr2"></div>
    <div class="corner-br"></div><div class="corner-br2"></div>
    <div class="frame"></div>

    <div class="logo">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="CHRSD Logo">
      @endif
    </div>
    <div class="lockup">
      <div class="wm">{{ strtoupper(config('app.name')) }}</div>
      <div class="sub">CENTRE FOR HUMANITARIAN RESEARCH AND<br>SOCIAL DEVELOPMENT FOUNDATION</div>
    </div>
    <div class="cert-id">Certificate ID: <span class="v">{{ $certificate->serial_number }}</span></div>

    <div class="h-cert">CERTIFICATE</div>
    <div class="h-appr">{{ strtoupper($type->name) }}</div>
    <div class="presented">This certificate is proudly presented to</div>

    @php $nm = $employee->full_name; $nameSize = mb_strlen($nm) > 22 ? '40px' : '54px'; @endphp
    <div class="name" style="font-size: {{ $nameSize }};">{{ $nm }}</div>
    <div class="name-rule"></div>

    <div class="body">
      This is to certify that <strong>{{ $nm }}</strong>,
      {{ optional($employee->position)->title }} of the {{ optional($employee->department)->name }},
      has been an employee of <strong>{{ config('app.name') }}</strong>
      since <strong>{{ optional($employee->hired_at)->format('F j, Y') }}</strong>.
      @if ($certificate->purpose)
        <br>This certification is issued for <strong>{{ $certificate->purpose }}</strong>.
      @endif
    </div>

    <div class="seal">
      @if($sealUrl)
        <img src="{{ $sealUrl }}" alt="CHRSD Seal">
      @endif
    </div>

    <div class="date">
      <div class="v">{{ now()->format('F j, Y') }}</div>
      <div class="rule"></div>
      <div class="lbl">Date</div>
    </div>
    <div class="sign">
      <div class="n">{{ optional($signatory)->full_name ?? 'Authorized Signatory' }}</div>
      <div class="rule"></div>
      <div class="lbl">{{ optional($signatory)->position?->title ?? 'Program Coordinator' }}</div>
    </div>

    <div class="verify-block">
      <div class="qr"><img src="{{ $qr_uri }}" alt="QR"></div>
      <div class="caption">Scan to verify</div>
    </div>
    <div class="serial-footer">{{ $verify_url }}</div>
  </div>
</body>
</html>
