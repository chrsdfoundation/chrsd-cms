<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificateNo }}</title>
    <style>
        @font-face {
            font-family: 'Cinzel';
            src: url('data:font/woff2;base64,{{ base64_encode(Storage::get('resources/fonts/cinzel-600.woff2')) }}') format('woff2');
            font-weight: 600;
        }
        @font-face {
            font-family: 'Cinzel';
            src: url('data:font/woff2;base64,{{ base64_encode(Storage::get('resources/fonts/cinzel-700.woff2')) }}') format('woff2');
            font-weight: 700;
        }
        @font-face {
            font-family: 'Cormorant Garamond';
            src: url('data:font/woff2;base64,{{ base64_encode(Storage::get('resources/fonts/cormorant-400.woff2')) }}') format('woff2');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Cormorant Garamond';
            src: url('data:font/woff2;base64,{{ base64_encode(Storage::get('resources/fonts/cormorant-700.woff2')) }}') format('woff2');
            font-weight: 700;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('data:font/woff2;base64,{{ base64_encode(Storage::get('resources/fonts/montserrat-400.woff2')) }}') format('woff2');
            font-weight: 400;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 1123px;
            height: 794px;
            overflow: hidden;
            background: #FAF9F5;
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: #1E293B;
        }

        .page {
            position: relative;
            width: 100%;
            height: 100%;
            background: radial-gradient(120% 90% at 50% 0%, #FFFFFF 0%, #FAF9F5 55%, #F3F1E9 100%);
            overflow: hidden;
        }

        /* SVG Frame */
        .frame-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 620px;
            height: 620px;
            opacity: 0.055;
            pointer-events: none;
            z-index: 1;
        }

        /* Content shell */
        .content {
            position: absolute;
            inset: 46px;
            display: flex;
            flex-direction: column;
            padding: 34px 62px 26px;
            box-sizing: border-box;
            z-index: 2;
        }

        /* Header row */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .qr-column {
            width: 132px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 7px;
        }

        .qr-code {
            width: 85px;
            height: 85px;
            padding: 6px;
            background: white;
            border: 1px solid #D8C79A;
        }

        .qr-code svg {
            width: 100%;
            height: 100%;
        }

        .qr-caption {
            font-family: 'Montserrat', sans-serif;
            font-size: 8.5px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #64748B;
            white-space: nowrap;
        }

        .logo-image {
            width: 132px;
            height: auto;
        }

        /* Org line */
        .org-line {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.34em;
            text-transform: uppercase;
            color: #B08D3E;
            margin-top: 8px;
        }

        /* Title */
        h1 {
            font-family: 'Cinzel', serif;
            font-size: 46px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6B4E16;
            line-height: 1;
            margin: 16px 0 0;
            text-align: center;
        }

        /* Divider */
        .divider {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
        }

        .divider-line {
            width: 168px;
            height: 1px;
            background: linear-gradient(to right, transparent, #BF953F, transparent);
        }

        .divider-diamond {
            width: 34px;
            height: 12px;
            flex-shrink: 0;
        }

        .divider-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #D8C79A;
            flex-shrink: 0;
        }

        /* Body */
        .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 9px;
            margin-top: -8px;
            text-align: center;
        }

        .certify-line {
            font-style: italic;
            font-size: 20px;
            color: #475569;
        }

        .recipient-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: #14532D;
            line-height: 1.05;
            text-align: center;
        }

        .body-line {
            font-style: italic;
            font-size: 19px;
            color: #475569;
        }

        .program-name {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            font-size: 27px;
            letter-spacing: 0.02em;
            color: #6B4E16;
        }

        .issued-line {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            letter-spacing: 0.06em;
            color: #64748B;
            margin-top: 6px;
        }

        .hairline {
            width: 380px;
            height: 1px;
            background: linear-gradient(to right, transparent, #C9A961, transparent);
            margin: 0 auto;
        }

        /* Signature row */
        .signature-row {
            display: grid;
            grid-template-columns: 1fr 150px 1fr;
            align-items: end;
            gap: 16px;
            margin-top: 20px;
        }

        .signature-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
        }

        .signature-image {
            max-width: 150px;
            max-height: 54px;
            object-fit: contain;
            margin-bottom: -6px;
        }

        .signature-rule {
            width: 290px;
            height: 1px;
            background: #1E293B;
            margin-bottom: 4px;
            align-self: center;
        }

        .signature-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 19px;
            color: #0F172A;
            margin-top: 4px;
        }

        .signature-title {
            font-family: 'Cinzel', serif;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #64748B;
        }

        .seal-image {
            width: 112px;
            height: auto;
            filter: drop-shadow(0 6px 12px rgba(107, 78, 22, 0.28));
            align-self: flex-end;
        }

        /* Footer */
        .footer {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            font-family: 'Montserrat', sans-serif;
            font-size: 10.5px;
            letter-spacing: 0.04em;
            color: #64748B;
        }

        .cert-number {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .cert-number-label {
            color: #64748B;
        }

        .cert-number-value {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 10.5px;
            letter-spacing: 0.08em;
            color: #6B4E16;
        }

        .footer-divider {
            width: 260px;
            height: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-line {
            flex: 1;
            height: 1px;
            background: #C9A961;
        }

        .footer-diamond {
            width: 6px;
            height: 6px;
            margin: 0 12px;
            background: #BF953F;
            clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        }

        .verification-info {
            text-align: right;
        }

        .verification-info a {
            color: #C9A961;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- SVG Frame -->
        <svg class="frame-svg" viewBox="0 0 1123 794" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Gold gradients -->
                <linearGradient id="goldH" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#B38728;stop-opacity:1" />
                    <stop offset="25%" style="stop-color:#FCF6BA;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#BF953F;stop-opacity:1" />
                    <stop offset="75%" style="stop-color:#FBF5B7;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#B38728;stop-opacity:1" />
                </linearGradient>
                <linearGradient id="goldV" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" style="stop-color:#B38728;stop-opacity:1" />
                    <stop offset="25%" style="stop-color:#FCF6BA;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#BF953F;stop-opacity:1" />
                    <stop offset="75%" style="stop-color:#FBF5B7;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#B38728;stop-opacity:1" />
                </linearGradient>
                <linearGradient id="goldD" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#B38728;stop-opacity:1" />
                    <stop offset="25%" style="stop-color:#FCF6BA;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#BF953F;stop-opacity:1" />
                    <stop offset="75%" style="stop-color:#FBF5B7;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#B38728;stop-opacity:1" />
                </linearGradient>
                <!-- Guilloche pattern -->
                <pattern id="guilloche" x="0" y="0" width="46" height="34" patternUnits="userSpaceOnUse">
                    <path d="M0 17 C 11.5 -3, 34.5 -3, 46 17" stroke="#BF953F" stroke-width="0.7" fill="none" opacity="0.55" />
                    <path d="M0 17 C 11.5 37, 34.5 37, 46 17" stroke="#BF953F" stroke-width="0.7" fill="none" opacity="0.55" />
                    <path d="M0 17 C 15 4, 31 30, 46 17" stroke="#8C6A22" stroke-width="0.5" fill="none" opacity="0.4" />
                    <path d="M0 17 C 15 30, 31 4, 46 17" stroke="#8C6A22" stroke-width="0.5" fill="none" opacity="0.4" />
                </pattern>
            </defs>

            <!-- Layer 1: Diagonal gradient border -->
            <rect x="20" y="20" width="1083" height="754" fill="none" stroke="url(#goldD)" stroke-width="6" />

            <!-- Layer 2: Guilloche pattern fill -->
            <rect x="33" y="33" width="1057" height="728" fill="url(#guilloche)" opacity="0.6" />

            <!-- Layer 3: Inner mask -->
            <rect x="46" y="46" width="1031" height="702" fill="#FAF9F5" />

            <!-- Layer 4: Inner hairline -->
            <rect x="45.5" y="45.5" width="1032" height="703" fill="none" stroke="url(#goldH)" stroke-width="1.4" />

            <!-- Layer 5: Light border -->
            <rect x="52" y="52" width="1019" height="690" fill="none" stroke="#D8C79A" stroke-width="0.7" opacity="0.8" />

            <!-- Corner brackets -->
            <!-- Top-left -->
            <path d="M46 92 L46 46 L92 46 M60 46 L60 60 L46 60" stroke="url(#goldD)" stroke-width="1.2" fill="none" opacity="0.9" />
            <!-- Top-right -->
            <path d="M1077 92 L1077 46 L1031 46 M1063 46 L1063 60 L1077 60" stroke="url(#goldD)" stroke-width="1.2" fill="none" opacity="0.9" />
            <!-- Bottom-left -->
            <path d="M46 702 L46 748 L92 748 M60 748 L60 734 L46 734" stroke="url(#goldD)" stroke-width="1.2" fill="none" opacity="0.9" />
            <!-- Bottom-right -->
            <path d="M1077 702 L1077 748 L1031 748 M1063 748 L1063 734 L1077 734" stroke="url(#goldD)" stroke-width="1.2" fill="none" opacity="0.9" />
        </svg>

        <!-- Watermark -->
        <div class="watermark">
            <img src="{{ $watermarkImage }}" alt="" style="width: 100%; height: 100%;">
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <div class="qr-column">
                    <div class="qr-code">
                        {!! $qrCodeSvg !!}
                    </div>
                    <div class="qr-caption">Scan to Verify</div>
                </div>
                <img src="{{ $logoImage }}" alt="CHRSD Logo" class="logo-image">
            </div>

            <!-- Org line -->
            <div class="org-line">Centre for Humanitarian Research and Social Development Foundation</div>

            <!-- Title -->
            <h1>{{ $certificateTitle }}</h1>

            <!-- Divider -->
            <div class="divider">
                <div class="divider-line"></div>
                <svg class="divider-diamond" viewBox="0 0 34 12" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 0 L23 6 L17 12 L11 6 Z" fill="#BF953F" />
                </svg>
                <div class="divider-line"></div>
            </div>

            <!-- Body -->
            <div class="body">
                <div class="certify-line">This is to certify that</div>
                <div class="recipient-name" style="font-size: {{ $nameFontSize }}px;">{{ $recipientName }}</div>
                <div class="hairline"></div>
                <div class="body-line">{{ $awardLeadIn }}</div>
                <div class="program-name">{{ $programName }}</div>
                <div class="issued-line">Issued on {{ $issuedOn }}</div>
            </div>

            <!-- Signatures -->
            <div class="signature-row">
                <div class="signature-block">
                    <img src="{{ $signatory1Image }}" alt="Signature" class="signature-image">
                    <div class="signature-rule"></div>
                    <div class="signature-name">{{ $signatory1Name }}</div>
                    <div class="signature-title">{{ $signatory1Title }}</div>
                </div>
                <div style="text-align: center;">
                    <img src="{{ $sealImage }}" alt="Seal" class="seal-image">
                </div>
                <div class="signature-block">
                    <img src="{{ $signatory2Image }}" alt="Signature" class="signature-image">
                    <div class="signature-rule"></div>
                    <div class="signature-name">{{ $signatory2Name }}</div>
                    <div class="signature-title">{{ $signatory2Title }}</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="cert-number">
                    <span class="cert-number-label">Certificate No.</span>
                    <span class="cert-number-value">{{ $certificateNo }}</span>
                </div>
                <div class="footer-divider">
                    <div class="footer-line"></div>
                    <div class="footer-diamond"></div>
                    <div class="footer-line"></div>
                </div>
                <div class="verification-info">
                    <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a> | info@chrsd.org
                </div>
            </div>
        </div>
    </div>
</body>
</html>
