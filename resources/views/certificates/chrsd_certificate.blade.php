@php
    use Carbon\CarbonInterface;

    /* Certificate of Achievement — A4 landscape design */

    $recipient_name = $recipient_name ?? $name        ?? 'Recipient Name';
    $course_title   = $course_title   ?? $course_name ?? 'Course or Achievement Title';
    $certificate_no = $certificate_no ?? 'CERT-2026-000001';

    $issued_raw     = $issued_date ?? $issue_date ?? now();
    $issued_date    = $issued_raw instanceof CarbonInterface
        ? $issued_raw->format('F j, Y')
        : (string) $issued_raw;

    $signatory_1_name  = $signatory_1_name  ?? $signatory_name    ?? 'Razib Mustafiz';
    $signatory_1_title = $signatory_1_title ?? $signatory_title   ?? 'Project Coordinator';
    $signatory_1_sig   = $signatory_1_sig   ?? $signatory_sig_url ?? asset('images/brand/signatures/razib-mustafiz.png');

    $signatory_2_name  = $signatory_2_name  ?? $countersign_name    ?? 'M.A. Ramim';
    $signatory_2_title = $signatory_2_title ?? $countersign_title   ?? 'Executive Director';
    $signatory_2_sig   = $signatory_2_sig   ?? $countersign_sig_url ?? asset('images/brand/signatures/ma-ramim.png');

    $qr_svg           = $qr_svg           ?? null;
    $qr_data_uri      = $qr_data_uri      ?? null;
    $verification_url = $verification_url ?? url('/verify/ref/' . $certificate_no);

    $org_name      = $org_name      ?? 'CHRSD';
    $org_full      = $org_full      ?? 'Centre for Humanitarian Research and Social Development Foundation';
    $contact_web   = $contact_web   ?? 'www.chrsd.org';
    $contact_email = $contact_email ?? 'info@chrsd.org';

    $logoUrl      = $logoUrl      ?? asset('images/brand/chrsd-full-logo.png');
    $sealUrl      = $sealUrl      ?? asset('images/brand/chrsd-rosette-seal.png');
    $watermarkUrl = $watermarkUrl ?? asset('images/brand/chrsd-watermark.svg');

    // Responsive font sizing for recipient name
    $len = mb_strlen($recipient_name);
    $nameSize = $len <= 26 ? 58 : ($len <= 36 ? 48 : ($len <= 48 ? 40 : 34));
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $org_name }} — Certificate of Achievement</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
<style>
    @page { size: A4 landscape; margin: 0; }

    @page { size: A4 landscape; margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { width: 1123px; height: 794px; background: #F5F1EB; margin: 0; padding: 0; overflow: hidden; }
    @media print { html, body { background: #F5F1EB; margin: 0; padding: 0; } }
    @media screen { body { padding: 20px; background: #E8E0D5; } }
</style>
</head>
<body>
<div style="position: relative; width: 1123px; height: 794px; background: linear-gradient(135deg, #FFF8F0 0%, #F5F1EB 100%); overflow: hidden; margin: 0 auto; page-break-inside: avoid; page-break-after: avoid;">

  {{-- Outer frame --}}
  <div style="position: absolute; top: 18px; left: 18px; right: 18px; bottom: 18px; border: 6px solid #B8860B; border-radius: 8px; z-index: 1;"></div>

  {{-- Inner frame --}}
  <div style="position: absolute; top: 28px; left: 28px; right: 28px; bottom: 28px; border: 2px solid #DAA520; border-radius: 4px; z-index: 1;"></div>

  {{-- Watermark --}}
  <div style="position: absolute; top: 180px; left: 380px; width: 380px; height: 380px; opacity: 0.08; z-index: 0; pointer-events: none;">
    <img src="{{ $watermarkUrl }}" alt="" style="width: 100%; height: 100%; display: block;">
  </div>

  {{-- QR Code (top left) --}}
  <div style="position: absolute; top: 45px; left: 45px; z-index: 3;">
    <div style="width: 85px; height: 85px; border: 2px solid #B8860B; background: white; padding: 4px; display: flex; align-items: center; justify-content: center;">
      @if (! empty($qr_svg))
        {!! $qr_svg !!}
      @elseif (! empty($qr_data_uri))
        <img src="{{ $qr_data_uri }}" alt="QR" style="width: 100%; height: 100%; display: block;">
      @else
        <div style="width: 100%; height: 100%; background: #EEE;"></div>
      @endif
    </div>
    <div style="text-align: center; font-family: 'Montserrat', sans-serif; font-size: 10px; color: #888; letter-spacing: 1px; margin-top: 4px;">SCAN TO VERIFY</div>
  </div>

  {{-- CHRS Logo (top right) --}}
  <div style="position: absolute; top: 45px; right: 45px; width: 120px; height: 80px; z-index: 3;">
    <img src="{{ $logoUrl }}" alt="CHRS Logo" style="max-width: 100%; max-height: 100%; display: block;">
  </div>

  {{-- Organization Name --}}
  <div style="position: absolute; top: 145px; left: 60px; right: 60px; text-align: center; z-index: 3;">
    <div style="font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 2px; color: #B8860B; text-transform: uppercase; font-weight: 600;">Centre for Humanitarian Research and Social Development Foundation</div>
  </div>

  {{-- Main Title --}}
  <div style="position: absolute; top: 170px; left: 60px; right: 60px; text-align: center; z-index: 3;">
    <h1 style="font-family: 'Cinzel', serif; font-size: 46px; font-weight: 700; color: #6B4423; letter-spacing: 1px; margin: 0; text-transform: uppercase;">Certificate of Achievement</h1>
  </div>

  {{-- Body Content --}}
  <div style="position: absolute; top: 250px; left: 80px; right: 80px; text-align: center; z-index: 3;">

    {{-- "This is to certify that" --}}
    <div style="font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 16px; color: #6B6B6B; margin-bottom: 8px;">This is to certify that</div>

    {{-- Recipient Name (responsive) --}}
    <div style="font-family: 'Cinzel', serif; font-size: {{ $nameSize }}px; font-weight: 700; color: #1B5E3F; letter-spacing: 0.5px; margin: 12px 0; line-height: 1.2;">{{ $recipient_name }}</div>

    {{-- "has successfully completed" --}}
    <div style="font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 15px; color: #6B6B6B; margin: 8px 0;">has successfully completed</div>

    {{-- Program/Course Title --}}
    <div style="font-family: 'Cinzel', serif; font-size: 28px; font-weight: 700; color: #8B6914; letter-spacing: 1px; text-transform: uppercase; margin: 12px 0;">{{ $course_title }}</div>

    {{-- Issue Date --}}
    <div style="font-family: 'Cormorant Garamond', serif; font-size: 14px; color: #6B6B6B; margin-top: 10px;">Issued on {{ $issued_date }}</div>

  </div>

  {{-- Rosette Seal (center) --}}
  <div style="position: absolute; top: 400px; left: 50%; width: 100px; height: 120px; margin-left: -50px; z-index: 3; text-align: center;">
    <img src="{{ $sealUrl }}" alt="Seal" style="max-width: 100%; max-height: 100%; display: block;">
  </div>

  {{-- Signature Section (left and right) --}}
  <div style="position: absolute; bottom: 120px; left: 50px; right: 50px; display: flex; justify-content: space-between; align-items: flex-end; z-index: 3; width: calc(100% - 100px);">

    {{-- Left Signatory --}}
    <div style="text-align: left; width: 30%;">
      @if (! empty($signatory_1_sig))
        <img src="{{ $signatory_1_sig }}" alt="Signature" style="height: 35px; margin-bottom: -8px; display: block;">
      @endif
      <div style="border-bottom: 1px solid #333; margin: 8px 0; min-width: 150px;"></div>
      <div style="font-family: 'Cormorant Garamond', serif; font-size: 13px; font-weight: 700; color: #1B5E3F;">{{ $signatory_1_name }}</div>
      <div style="font-family: 'Montserrat', sans-serif; font-size: 10px; color: #666; letter-spacing: 0.5px; text-transform: uppercase;">{{ $signatory_1_title }}</div>
    </div>

    {{-- Right Signatory --}}
    <div style="text-align: right; width: 30%;">
      @if (! empty($signatory_2_sig))
        <img src="{{ $signatory_2_sig }}" alt="Signature" style="height: 35px; margin-bottom: -8px; display: block;">
      @endif
      <div style="border-bottom: 1px solid #333; margin: 8px 0; min-width: 150px;"></div>
      <div style="font-family: 'Cormorant Garamond', serif; font-size: 13px; font-weight: 700; color: #1B5E3F;">{{ $signatory_2_name }}</div>
      <div style="font-family: 'Montserrat', sans-serif; font-size: 10px; color: #666; letter-spacing: 0.5px; text-transform: uppercase;">{{ $signatory_2_title }}</div>
    </div>

  </div>

  {{-- Footer --}}
  <div style="position: absolute; bottom: 35px; left: 50px; right: 50px; display: flex; justify-content: space-between; align-items: center; z-index: 3; width: calc(100% - 100px);">

    {{-- Certificate Number --}}
    <div style="font-family: 'Montserrat', sans-serif; font-size: 11px; color: #6B4423;">Certificate No. <strong style="font-weight: 700;">{{ $certificate_no }}</strong></div>

    {{-- Center Diamond --}}
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; margin: 0 20px;">
      <div style="flex: 1; height: 1px; background: #DAA520;"></div>
      <div style="width: 8px; height: 8px; background: #DAA520; margin: 0 8px; transform: rotate(45deg);"></div>
      <div style="flex: 1; height: 1px; background: #DAA520;"></div>
    </div>

    {{-- Website and Email --}}
    <div style="font-family: 'Montserrat', sans-serif; font-size: 11px; color: #6B4423; text-align: right;">
      <a href="https://{{ $contact_web }}" style="color: inherit; text-decoration: none;">{{ $contact_web }}</a> | <a href="mailto:{{ $contact_email }}" style="color: inherit; text-decoration: none;">{{ $contact_email }}</a>
    </div>

  </div>

</div>
</body>
</html>
