<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $certificateNo }} - CHRSD Certificate</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        html, body {
            width: 1123px;
            height: 794px;
            background: #FAF9F5;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .certificate-page {
            width: 1123px;
            height: 794px;
            position: relative;
            background: radial-gradient(120% 90% at 50% 0%, #FFFFFF, #FAF9F5 55%, #F3F1E9);
            overflow: hidden;
            page-break-inside: avoid;
        }

        /* Watermark - subtle but visible background security feature */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 620px;
            height: 620px;
            opacity: 0.25;
            z-index: 0;
            pointer-events: none;
        }

        .watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* SVG Frame Layer */
        .frame-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        /* Content Container - inset 46px, padding 20px 62px 14px */
        .content {
            position: absolute;
            top: 46px;
            left: 46px;
            right: 46px;
            bottom: 46px;
            padding: 20px 62px 14px;
            z-index: 2;
            display: flex;
            flex-direction: column;
            background: transparent;
            box-sizing: border-box;
        }

        /* Header - asymmetric layout */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
            width: 100%;
        }

        .qr-block {
            width: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .qr-code {
            width: 160px;
            height: 160px;
            background: white;
            border: 3px solid #1E293B;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 1px #D8C79A inset;
        }

        /* 160 − 2×3px border − 2×8px padding = 138px available;
           140px is inset by the 8px padding, leaving a clear quiet zone
           between the outer modules and the dark navy frame. */
        .qr-code img {
            width: 140px;
            height: 140px;
            display: block;
        }

        .qr-caption {
            font-family: 'Montserrat', sans-serif;
            font-size: 8.5px;
            font-weight: 400;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #64748B;
            white-space: nowrap;
        }

        .logo-block {
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-block img {
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
        }

        /* Title Stack - centred */
        .title-stack {
            text-align: center;
            margin-bottom: 8px;
        }

        .org-line {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.34em;
            color: #B08D3E;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 46px;
            font-weight: 700;
            letter-spacing: 0.06em;
            line-height: 1;
            color: #6B4E16;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .divider-rule {
            flex: 0 1 168px;
            height: 1px;
            background: linear-gradient(to right, transparent, #BF953F, transparent);
        }

        .divider-ornament {
            width: 34px;
            height: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .diamond {
            width: 8px;
            height: 8px;
            background: #BF953F;
            transform: rotate(45deg);
        }

        .dot {
            width: 2px;
            height: 2px;
            background: #D8C79A;
        }

        /* Body - optically centred, flex: 1 for vertical centering */
        .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-align: center;
            margin: 0 -8px; /* optical compensation */
            min-height: 120px;
        }

        .body-line {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-style: italic;
            font-weight: 400;
            color: #475569;
        }

        .body-line.small {
            font-size: 19px;
        }

        .recipient-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 58px;
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: 0.01em;
            color: #14532D;
            text-transform: capitalize;
            margin: 8px 0;
            padding-bottom: 8px;
            width: 100%;
            max-width: 100%;
            white-space: nowrap;
            position: relative;
            display: inline-block;
        }

        .recipient-name::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 380px;
            height: 1px;
            background-color: #C9A961;
        }

        .program-name {
            font-family: 'Cinzel', serif;
            font-size: 27px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #6B4E16;
            text-transform: uppercase;
            margin: 8px 0;
        }

        .issue-date {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.06em;
            color: #64748B;
            margin-top: 4px;
        }

        /* Signature Row - three columns, 1fr 150px 1fr */
        .signature-row {
            display: grid;
            grid-template-columns: 1fr 150px 1fr;
            align-items: flex-end;
            gap: 20px;
            margin-top: 10px;
        }

        .signature-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .signature-image {
            width: 100%;
            max-width: 120px;
            height: 54px;
            object-fit: contain;
            margin-bottom: -6px; /* overlap rule */
        }

        .signature-rule {
            width: 100%;
            max-width: 290px;
            height: 1px;
            background: #1E293B;
            margin-bottom: 4px;
        }

        .signature-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            font-weight: 700;
            color: #0F172A;
        }

        .signature-title {
            font-family: 'Cinzel', serif;
            font-size: 10.5px;
            font-weight: 400;
            letter-spacing: 0.2em;
            color: #64748B;
            text-transform: uppercase;
        }

        .seal-block {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seal-image {
            width: 112px;
            height: 112px;
            object-fit: contain;
            filter: drop-shadow(0 6px 12px rgba(107, 78, 22, 0.28));
        }

        /* Footer - centered ornament with gold hairline */
        .footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10px;
            gap: 6px;
        }

        .footer::before {
            content: '';
            width: 100%;
            height: 1px;
            background-color: #BF953F;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 20px;
        }

        .cert-number-link {
            text-decoration: none;
            color: inherit;
        }

        .cert-number {
            font-family: 'Cinzel', serif;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #6B4E16;
            text-transform: uppercase;
        }

        .footer-ornament {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer-rule {
            width: 60px;
            height: 1px;
            background: #BF953F;
        }

        .footer-info {
            font-family: 'Montserrat', sans-serif;
            font-size: 10.5px;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: #64748B;
            text-align: right;
            flex: 1;
        }

        .footer-info a {
            color: #64748B;
            text-decoration: none;
        }

        /* Print button - hide in print */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
            font-size: 14px;
        }

        @media print {
            .print-button {
                display: none;
            }
        }

        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }
            .certificate-page {
                margin: 0 auto;
                box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print / Save as PDF</button>

    <div class="certificate-page">
        <!-- Watermark -->
        <div class="watermark">
            <img src="{{ asset('images/brand/chrsd-watermark.svg') }}" alt="">
        </div>

        <!-- SVG Frame (complex guilloche, brackets, etc.) -->
        <svg class="frame-layer" viewBox="0 0 1123 794" preserveAspectRatio="none">
            <defs>
                <linearGradient id="goldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#B38728;stop-opacity:1" />
                    <stop offset="25%" style="stop-color:#FCF6BA;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#BF953F;stop-opacity:1" />
                    <stop offset="75%" style="stop-color:#FBF5B7;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#B38728;stop-opacity:1" />
                </linearGradient>
            </defs>

            <!-- Outer frame: 20,20 → 1103,774, 6px gold diagonal-gradient -->
            <rect x="20" y="20" width="1083" height="754" fill="none" stroke="url(#goldGradient)" stroke-width="6" stroke-linejoin="miter"/>

            <!-- Guilloche band: 33,33 → 1090,761 (simplified - 60% opacity security pattern) -->
            <rect x="33" y="33" width="1057" height="728" fill="none" stroke="#BF953F" stroke-width="0.7" opacity="0.55" stroke-linejoin="miter"/>
            <rect x="33" y="33" width="1057" height="728" fill="none" stroke="#8C6A22" stroke-width="0.5" opacity="0.4" stroke-linejoin="miter"/>

            <!-- Cream fill and inner keyline: 46,46 → 1077,748 -->
            <rect x="46" y="46" width="1031" height="702" fill="#FAF9F5"/>

            <!-- Horizontal gradient gold line: 1.4px -->
            <line x1="46" y1="45.5" x2="1077" y2="45.5" stroke="url(#goldGradient)" stroke-width="1.4"/>

            <!-- Keyline: 0.7px #D8C79A at 80% opacity -->
            <line x1="52" y1="52" x2="1071" y2="52" stroke="#D8C79A" stroke-width="0.7" opacity="0.8"/>

            <!-- Corner brackets - L-shapes with 1.2px gold diagonal gradient, 90% opacity -->
            <!-- Top-left -->
            <path d="M 60 60 L 75 60 L 75 65" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>
            <path d="M 60 60 L 65 60 L 65 75" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>

            <!-- Top-right -->
            <path d="M 1048 60 L 1063 60 L 1063 65" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>
            <path d="M 1063 60 L 1058 60 L 1058 75" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>

            <!-- Bottom-left -->
            <path d="M 60 734 L 75 734 L 75 729" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>
            <path d="M 60 734 L 65 734 L 65 719" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>

            <!-- Bottom-right -->
            <path d="M 1048 734 L 1063 734 L 1063 729" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>
            <path d="M 1063 734 L 1058 734 L 1058 719" fill="none" stroke="url(#goldGradient)" stroke-width="1.2" opacity="0.9" stroke-linecap="round"/>
        </svg>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <div class="qr-block">
                    <div class="qr-code">
                        <img src="{{ $qrCodePng }}" alt="Verification QR Code">
                    </div>
                    <div class="qr-caption">Scan to Verify</div>
                </div>
                <div class="logo-block">
                    @if($websiteUrl)
                    <img src="{{ asset('images/brand/chrsd-full-logo.png') }}" alt="CHRSD Logo">
                    @endif
                </div>
            </div>

            <!-- Title Stack -->
            <div class="title-stack">
                <div class="org-line">Centre for Humanitarian Research and Social Development Foundation</div>
                <h1 class="cert-title">Certificate of Achievement</h1>
                <div class="divider">
                    <div class="divider-rule"></div>
                    <div class="divider-ornament">
                        <div class="dot"></div>
                        <div class="diamond"></div>
                        <div class="dot"></div>
                    </div>
                    <div class="divider-rule"></div>
                </div>
            </div>

            <!-- Body -->
            <div class="body">
                <div class="body-line">This is to certify that</div>
                @php
                    $len = mb_strlen($recipientName);
                    $nameSize = $len <= 26 ? 58 : ($len <= 36 ? 48 : ($len <= 48 ? 40 : 34));
                @endphp
                <div class="recipient-name" style="font-size: {{ $nameSize }}px;">{{ $recipientName }}</div>
                <div class="body-line small">has successfully completed</div>
                <div class="program-name">{{ $courseName }}</div>
                <div class="issue-date">Issued on {{ $issuedDate }}</div>
            </div>

            <!-- Signature Row -->
            <div class="signature-row">
                <div class="signature-block">
                    @if($signatory1Image)
                    <img src="{{ $signatory1Image }}" alt="" class="signature-image">
                    @endif
                    <div class="signature-rule"></div>
                    <div class="signature-name">{{ $signatory1Name }}</div>
                    <div class="signature-title">{{ $signatory1Title }}</div>
                </div>
                <div class="seal-block">
                    <img src="{{ asset('images/brand/chrsd-rosette-seal.png') }}" alt="Seal" class="seal-image">
                </div>
                <div class="signature-block">
                    @if($signatory2Image)
                    <img src="{{ $signatory2Image }}" alt="" class="signature-image">
                    @endif
                    <div class="signature-rule"></div>
                    <div class="signature-name">{{ $signatory2Name }}</div>
                    <div class="signature-title">{{ $signatory2Title }}</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-content">
                    <a href="{{ $verifyUrl }}" class="cert-number-link">
                        <div class="cert-number">Certificate No. {{ $certificateNo }}</div>
                    </a>
                    <div class="footer-ornament">
                        <div class="footer-rule"></div>
                        <div class="diamond"></div>
                        <div class="footer-rule"></div>
                    </div>
                    <div class="footer-info">
                        <a href="{{ $verifyUrl }}">www.chrsd.org/verify</a> | <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
