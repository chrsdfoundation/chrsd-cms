<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate: {{ $certificate->serial_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            font-family: Georgia, serif;
            color: #000;
            background: #fff;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .print-button {
                display: none !important;
            }
        }

        @media screen {
            body {
                background: #f5f5f5;
                padding: 1rem;
            }

            .print-button {
                display: flex;
                justify-content: center;
                margin: 1rem 0;
                padding: 1rem;
            }

            .print-button button {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
                background: #0ea5e9;
                color: #fff;
                border: none;
                border-radius: 0.375rem;
                cursor: pointer;
            }

            .print-button button:hover {
                background: #0284c7;
            }
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        .certificate {
            width: 297mm;
            height: 210mm;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-after: always;
        }

        .certificate-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
        }

        .certificate-content {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 85%;
        }

        .certificate-content h1 {
            font-size: 2.5cm;
            margin: 0.5cm 0;
            font-weight: 700;
            color: #1a5490;
        }

        .certificate-content p {
            font-size: 1cm;
            line-height: 1.6;
            margin: 0.3cm 0;
            color: #333;
        }

        .recipient-name {
            font-size: 1.5cm;
            font-weight: bold;
            margin: 1cm 0;
            color: #1a5490;
        }

        .certificate-type {
            font-size: 1.2cm;
            font-weight: bold;
            margin: 0.5cm 0;
            color: #333;
        }

        .signature-block {
            display: flex;
            justify-content: space-around;
            margin-top: 1.5cm;
            gap: 1cm;
            width: 100%;
        }

        .signature {
            text-align: center;
            width: 40mm;
            font-size: 0.9cm;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 0.2cm;
            margin: 0.3cm 0;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 0.2cm;
        }

        .qr-code {
            position: absolute;
            bottom: 1cm;
            right: 1cm;
            width: 25mm;
            height: 25mm;
        }

        .serial-number {
            position: absolute;
            bottom: 1cm;
            left: 1cm;
            font-size: 0.8cm;
            color: #666;
        }

        @media screen {
            .certificate {
                background: #fff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="certificate-background"></div>

        <div class="certificate-content">
            <h1>Certificate of Achievement</h1>
            <p>This is to certify that</p>
            <div class="recipient-name">{{ $certificate->recipient_name ?? $employee->full_name ?? 'Recipient' }}</div>
            <p>has successfully completed the requirements for</p>
            <div class="certificate-type">{{ $type->name ?? 'Certificate' }}</div>

            <div class="signature-block">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ config('app.name') }}</div>
                </div>
            </div>
        </div>

        <div class="serial-number">{{ $certificate->serial_number }}</div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
