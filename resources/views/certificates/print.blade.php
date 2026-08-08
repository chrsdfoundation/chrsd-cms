<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Certificate - {{ $certificateNo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #fff;
            color: #1a365d;
        }

        .certificate-container {
            width: 297mm;
            height: 210mm;
            position: relative;
            background: #fff;
            margin: 0 auto;
            page-break-after: always;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Watermark - LARGE AND VISIBLE */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 550px;
            height: 550px;
            opacity: 0.25;
            z-index: 1;
            pointer-events: none;
        }

        .watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Gold Border */
        .certificate-border {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 16px solid #c9a961;
            box-sizing: border-box;
            pointer-events: none;
            z-index: 10;
        }

        /* Inner border line */
        .certificate-inner-border {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            bottom: 16px;
            border: 1px solid #c9a961;
            box-sizing: border-box;
            pointer-events: none;
            z-index: 9;
        }

        /* Main Content */
        .certificate-content {
            position: relative;
            z-index: 2;
            text-align: center;
            width: calc(100% - 32px);
            height: calc(100% - 32px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 25px 30px;
        }

        /* Header: QR and Logo */
        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
            margin-bottom: 15px;
        }

        /* QR Code - Top Left */
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .qr-code {
            width: 110px;
            height: 110px;
            border: 3px solid #c9a961;
            padding: 5px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .qr-code svg {
            width: 100%;
            height: 100%;
        }

        .qr-text {
            font-size: 9pt;
            color: #666;
            font-weight: bold;
        }

        /* Logo section - Top Right */
        .logo-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .logo-section img {
            width: 110px;
            height: auto;
            object-fit: contain;
        }

        .logo-text {
            display: none;
        }

        /* Organization Name */
        .org-name {
            font-size: 11pt;
            color: #c9a961;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        /* Title */
        .certificate-title {
            margin: 8px 0;
        }

        .certificate-title h1 {
            font-size: 52pt;
            color: #5d4e37;
            font-weight: 700;
            letter-spacing: 3px;
        }

        /* Body */
        .certificate-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            margin: 15px 0;
        }

        .body-line {
            font-size: 12pt;
            color: #333;
            font-style: italic;
        }

        .recipient-name {
            font-size: 36pt;
            color: #1a365d;
            font-weight: 700;
            margin: 8px 0;
            letter-spacing: 1px;
        }

        .course-name {
            font-size: 18pt;
            color: #a68860;
            font-weight: 600;
            margin: 8px 0;
        }

        .issue-date {
            font-size: 11pt;
            color: #666;
        }

        /* Signatures and Seal */
        .signature-section {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 12px;
        }

        .signature-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            width: 110px;
        }

        .signature-image {
            max-width: 90px;
            max-height: 50px;
            object-fit: contain;
            margin-bottom: 2px;
        }

        .signature-line {
            width: 100%;
            height: 1.5px;
            background: #333;
            margin-bottom: 3px;
            margin-top: 2px;
        }

        .signature-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1a365d;
        }

        .signature-title {
            font-size: 7.5pt;
            color: #333;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .seal-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        /* Bottom Info */
        .certificate-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            border-top: 1.5px solid #c9a961;
            padding-top: 8px;
            padding-bottom: 0;
            font-size: 10pt;
            flex-shrink: 0;
        }

        .cert-number {
            color: #c9a961;
            font-weight: bold;
            font-size: 10pt;
        }

        .cert-divider {
            width: 15px;
            height: 15px;
            background: #c9a961;
            clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        }

        .cert-info {
            color: #333;
            font-size: 10pt;
        }

        /* Print Button */
        .print-button-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 12pt;
            border-radius: 4px;
            cursor: pointer;
        }

        .print-button:hover {
            background: #0056b3;
        }

        @media print {
            .print-button-container {
                display: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }

            .certificate-container {
                margin: 0 auto 20px;
                box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            }
        }
    </style>
</head>
<body>
    <div class="print-button-container">
        <button class="print-button" onclick="window.print();">🖨️ Print / PDF</button>
    </div>

    <div class="certificate-container">
        <!-- Watermark - LARGE AND VISIBLE -->
        <div class="watermark">
            <img src="{{ asset('images/brand/letterhead-watermark.png') }}" alt="Watermark" />
        </div>

        <!-- Borders -->
        <div class="certificate-border"></div>
        <div class="certificate-inner-border"></div>

        <!-- Content -->
        <div class="certificate-content">
            <!-- Header: QR and Logo -->
            <div class="certificate-header">
                <div class="qr-section">
                    <div class="qr-code">
                        {!! $qrCodeSvg !!}
                    </div>
                    <div class="qr-text">Scan to Verify</div>
                </div>

                <div style="flex: 1;"></div>

                <div class="logo-section">
                    <img src="{{ asset('images/brand/chrsd-full-logo.png') }}" alt="CHRSD Logo" />
                    <div class="logo-text">CHRS DEVELOPMENT<br/>CENTRE FOR HUMANITARIAN RESEARCH AND<br/>SOCIAL DEVELOPMENT FOUNDATION</div>
                </div>
            </div>

            <!-- Organization Name -->
            <div class="org-name">CENTRE FOR HUMANITARIAN RESEARCH AND SOCIAL DEVELOPMENT FOUNDATION</div>

            <!-- Title -->
            <div class="certificate-title">
                <h1>CERTIFICATE OF ACHIEVEMENT</h1>
            </div>

            <!-- Body -->
            <div class="certificate-body">
                <div class="body-line">This is to certify that</div>
                <div class="recipient-name">{{ $recipientName }}</div>
                <div class="body-line">has successfully completed</div>
                <div class="course-name">{{ $courseName }}</div>
                <div class="issue-date">Issued on {{ $issuedDate }}</div>
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="signature-block">
                    @if($signatory1Image)
                        <img src="{{ $signatory1Image }}" alt="Signature" class="signature-image" />
                    @endif
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $signatory1Name }}</div>
                    <div class="signature-title">{{ $signatory1Title }}</div>
                </div>

                <div style="flex: 1; display: flex; align-items: flex-end; justify-content: center;">
                    <img src="{{ asset('images/brand/chrsd-rosette-seal.png') }}" alt="Seal" class="seal-image" />
                </div>

                <div class="signature-block">
                    @if($signatory2Image)
                        <img src="{{ $signatory2Image }}" alt="Signature" class="signature-image" />
                    @endif
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $signatory2Name }}</div>
                    <div class="signature-title">{{ $signatory2Title }}</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="certificate-bottom">
                <div class="cert-number">Certificate No. {{ $certificateNo }}</div>
                <div class="cert-divider"></div>
                <div class="cert-info">{{ $websiteUrl }} | {{ $contactEmail }}</div>
            </div>
        </div>
    </div>
</body>
</html>
