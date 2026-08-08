<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Letter')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 0;
            padding: 0;
            marks: none;
        }

        html, body {
            width: 100%;
            font-family: 'Calibri', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        body {
            counter-reset: page;
        }

        .page-wrapper {
            width: 210mm;
            position: relative;
            background: #fff;
            margin: 0 auto 10mm;
            page-break-after: always;
            display: flex;
            flex-direction: column;
            min-height: 297mm;
        }

        /* WATERMARK - Behind all content */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            opacity: 0.04;
            z-index: 0;
            pointer-events: none;
        }

        .watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* PAGE CONTENT - Above watermark */
        .page-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
            flex: 1;
        }

        /* ============================================================
           HEADER - Full width at top of page
           ============================================================ */
        header.letter-header {
            width: 100%;
            flex-shrink: 0;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        header.letter-header img {
            width: 100%;
            height: auto;
            display: block;
        }

        .header-container {
            display: none;
        }

        /* ============================================================
           MAIN CONTENT AREA - Flexible height with page break support
           ============================================================ */
        main.letter-body {
            flex: 1;
            padding: 12mm 20mm;
        }

        /* ============================================================
           QR CODE & VERIFICATION - Before footer
           ============================================================ */
        .qr-verification {
            padding: 8mm 20mm;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 10mm;
            flex-shrink: 0;
            page-break-inside: avoid;
        }

        .qr-code-box {
            flex: 0 0 auto;
        }

        .qr-code-box svg,
        .qr-code-box img {
            width: 30mm;
            height: 30mm;
        }

        .verification-link {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3mm;
        }

        .verification-link p {
            margin: 0;
            font-size: 10pt;
        }

        .verification-link a {
            color: #0066cc;
            text-decoration: none;
            word-break: break-all;
            font-size: 9pt;
        }

        .verification-link a:hover {
            text-decoration: underline;
        }

        /* ============================================================
           FOOTER - Full width at bottom of page
           ============================================================ */
        footer.letter-footer {
            width: 100%;
            flex-shrink: 0;
            page-break-after: avoid;
            page-break-inside: avoid;
            margin-top: auto;
        }

        footer.letter-footer img {
            width: 100%;
            height: auto;
            display: block;
        }

        .footer-container {
            display: none;
        }

        .footer-info {
            display: none;
        }

        .footer-page-number {
            display: none;
        }

        p {
            margin-bottom: 12px;
            orphans: 3;
            widows: 3;
        }

        h1, h2, h3, h4, h5, h6 {
            margin-top: 10mm;
            margin-bottom: 5mm;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        h1 { font-size: 14pt; font-weight: bold; }
        h2 { font-size: 12pt; font-weight: bold; }
        h3 { font-size: 11pt; font-weight: bold; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8mm 0;
            page-break-inside: avoid;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 4mm 5mm;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 80mm;
            margin: 15mm 0 3mm 0;
        }

        .signature-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 2mm;
        }

        .signature-title {
            font-size: 10pt;
            color: #666;
        }

        .signature-image {
            max-width: 80mm;
            max-height: 40mm;
            margin-bottom: 3mm;
        }

        .no-print {
            display: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            a {
                color: inherit;
            }

            .page-wrapper {
                margin: 0;
                page-break-after: always;
            }
        }

        @media screen {
            body {
                background: #f0f0f0;
                padding: 10mm;
            }

            .page-wrapper {
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }

            .print-button-container {
                text-align: center;
                margin-bottom: 20px;
            }

            .print-button {
                background: #007bff;
                color: white;
                border: none;
                padding: 12px 24px;
                font-size: 14pt;
                border-radius: 4px;
                cursor: pointer;
            }

            .print-button:hover {
                background: #0056b3;
            }
        }

        counter {
            counter-increment: page;
        }
    </style>
</head>
<body>
    <!-- ===== PRINT BUTTON (Screen only) ===== -->
    <div class="print-button-container no-print">
        <button class="print-button" onclick="window.print();">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <!-- ===== PAGE WRAPPER ===== -->
    <div class="page-wrapper">
        <!-- WATERMARK - Behind all content -->
        <div class="watermark">
            @if($watermarkUrl ?? false)
                <img src="{{ $watermarkUrl }}" alt="Watermark" />
            @endif
        </div>

        <!-- PAGE CONTENT -->
        <div class="page-content">
            <!-- ===== HEADER ===== -->
            <header class="letter-header">
                @if($headerImageUrl ?? false)
                    <img src="{{ $headerImageUrl }}" alt="Letterhead Header" />
                @endif
            </header>

            <!-- ===== MAIN CONTENT ===== -->
            <main class="letter-body">
                @yield('content')
            </main>

            <!-- ===== QR CODE & VERIFICATION ===== -->
            <div class="qr-verification">
                @if($qrCodeSvg ?? false)
                    <div class="qr-code-box">
                        {!! $qrCodeSvg !!}
                    </div>
                @endif

                @if($verifyUrl ?? false)
                    <div class="verification-link">
                        <p><strong>Verify this document:</strong></p>
                        <a href="{{ $verifyUrl }}" target="_blank">{{ $verifyUrl }}</a>
                    </div>
                @endif
            </div>

            <!-- ===== FOOTER ===== -->
            <footer class="letter-footer">
                @if($footerImageUrl ?? false)
                    <img src="{{ $footerImageUrl }}" alt="Letterhead Footer" />
                @endif
            </footer>
        </div>
    </div>

</body>
</html>
