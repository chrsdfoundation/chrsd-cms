{{--
  CHRSD ID — BACK (Simplified for mPDF compatibility)

  Pure mPDF render with simple, clean layout.
  All images as base64 data URIs.

  Vars: $idCard, $signatureUrl, $qr_svg, $verify_url, $roundLogoUrl
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { size: 85.6mm 54mm; margin: 0; }
    body { margin: 0; padding: 2mm; font-family: 'DejaVu Sans', Arial, sans-serif; color: #163E22; font-size: 7pt; background: #fafafa; }

    .back-card { width: 100%; height: 50mm; display: flex; flex-direction: column; }

    .instructions { flex: 1; padding: 2mm; font-size: 6pt; line-height: 1.3; color: #186D3B; }
    .instructions p { margin: 1mm 0; }

    .qr-section { display: flex; justify-content: space-between; align-items: flex-end; border-top: 0.3mm solid #C09020; padding-top: 1mm; gap: 2mm; }

    .qr-code { width: 18mm; height: 18mm; text-align: center; }
    .qr-code svg { width: 100%; height: 100%; }

    .info { flex: 1; font-size: 6pt; }
    .info p { margin: 0.5mm 0; }
    .serial { font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; }
    .verify { font-size: 5.5pt; color: #186D3B; }

    .signature { width: 12mm; text-align: center; border-top: 0.3mm solid #163E22; padding-top: 0.5mm; }
    .signature img { width: 100%; max-height: 8mm; }
    .signature-label { font-size: 5pt; margin-top: 0.3mm; }
</style>
</head>
<body>

<div class="back-card">

    <div class="instructions">
        <p><strong>IMPORTANT:</strong> This card must be returned to CHRSD upon expiry or cessation of affiliation.</p>
        <p><strong>Contact:</strong> For verification: chrsd.org/verify</p>
        <p><strong>Security:</strong> This card is non-transferable. Unauthorized use is strictly prohibited.</p>
    </div>

    <div class="qr-section">
        <div class="qr-code">
            {!! $qr_svg !!}
        </div>

        <div class="info">
            <p class="serial">{{ $idCard->serial_number }}</p>
            <p class="verify">{{ $verify_url }}</p>
        </div>

        @if($signatureUrl)
            <div class="signature">
                <img src="{{ $signatureUrl }}" alt="Authorized Signatory">
                <div class="signature-label">Authorized<br>Signatory</div>
            </div>
        @endif
    </div>

</div>

</body>
</html>
