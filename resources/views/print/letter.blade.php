<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter: {{ $letter->serial_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
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
            size: A4 portrait;
            margin: 20mm 15mm;
        }

        .letter {
            width: 210mm;
            min-height: 297mm;
            position: relative;
        }

        .letter-letterhead {
            text-align: center;
            font-size: 1.2cm;
            font-weight: bold;
            margin-bottom: 1.5cm;
            color: #1a5490;
        }

        .letter-date {
            margin-bottom: 1.5cm;
            font-size: 0.95cm;
        }

        .letter-recipient {
            margin-bottom: 1.5cm;
            font-size: 0.95cm;
        }

        .letter-subject {
            font-weight: bold;
            margin: 1.5cm 0;
            font-size: 0.95cm;
        }

        .letter-body {
            text-align: justify;
            min-height: 10cm;
            margin: 1.5cm 0;
            font-size: 0.95cm;
            line-height: 1.6;
        }

        .letter-body p {
            margin-bottom: 0.5cm;
        }

        .letter-closing {
            margin: 1.5cm 0 0.5cm 0;
            font-size: 0.95cm;
        }

        .letter-signature {
            margin-top: 1.5cm;
            font-size: 0.95cm;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 50mm;
            height: 0.1cm;
            margin: 0.5cm 0;
        }

        .signature-name {
            font-weight: bold;
        }

        .signature-title {
            font-style: italic;
            font-size: 0.85cm;
        }

        .letter-footer {
            position: absolute;
            bottom: 15mm;
            width: 180mm;
            font-size: 0.75cm;
            color: #666;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 0.3cm;
        }

        @media screen {
            .letter {
                background: #fff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                margin: 1rem auto;
                padding: 20mm 15mm;
            }

            .letter-footer {
                position: relative;
                margin-top: 40mm;
            }
        }
    </style>
</head>
<body>
    <div class="letter">
        <div class="letter-letterhead">
            {{ config('app.name') }}
        </div>

        <div class="letter-date">
            {{ $letter->released_on?->format('d F Y') ?? now()->format('d F Y') }}
        </div>

        @if($letter->recipient_name)
            <div class="letter-recipient">
                <strong>{{ $letter->recipient_name }}</strong><br>
                @if($letter->recipient_title)
                    {{ $letter->recipient_title }}<br>
                @endif
                @if($letter->recipient_address)
                    {{ $letter->recipient_address }}
                @endif
            </div>
        @endif

        @if($letter->subject)
            <div class="letter-subject">
                Subject: {{ $letter->subject }}
            </div>
        @endif

        <div class="letter-body">
            @if($letter->body)
                {!! nl2br(e($letter->body)) !!}
            @else
                <p>Letter content not available.</p>
            @endif
        </div>

        <div class="letter-closing">
            Yours sincerely,
        </div>

        <div class="letter-signature">
            <div class="signature-line"></div>
            @if($signatory)
                <div class="signature-name">{{ $signatory->full_name ?? 'Authorized Signatory' }}</div>
                @if($signatory->position)
                    <div class="signature-title">{{ $signatory->position->title ?? '' }}</div>
                @endif
            @else
                <div class="signature-name">Authorized Signatory</div>
            @endif
        </div>

        <div class="letter-footer">
            <strong>Serial:</strong> {{ $letter->serial_number }}<br>
            <small>This letter has been digitally signed and can be verified at {{ $verifyUrl ?? app(\App\Services\QrCodeService::class)->verificationUrl($letter) }}</small>
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
