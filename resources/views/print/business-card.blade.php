<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business card — {{ $card->person_name }}</title>
    <style>
        @font-face { font-family: 'CardSans'; src: url('{{ asset('fonts/business-card/Montserrat-Regular.ttf') }}') format('truetype'); font-weight: 400; }
        @font-face { font-family: 'CardSans'; src: url('{{ asset('fonts/business-card/Montserrat-SemiBold.ttf') }}') format('truetype'); font-weight: 600; }
        @font-face { font-family: 'CardSerif'; src: url('{{ asset('fonts/business-card/CormorantGaramond-SemiBold.ttf') }}') format('truetype'); font-weight: 600; }

        @page { size: 3.5in 2in; margin: 0; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            background: #e2e8f0;
        }

        /* Trim-size window (3.5 x 2 in) onto the 306 x 198 pt artwork; the bleed lies outside it. */
        .side {
            position: relative;
            width: 252pt;
            height: 144pt;
            overflow: hidden;
            background: #fff;
        }

        .side svg {
            position: absolute;
            left: -27pt;
            top: -27pt;
            width: 306pt;
            height: 198pt;
            display: block;
        }

        .notice {
            max-width: 400px;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.4;
            background: #fef3c7;
            color: #92400e;
        }

        .notice.error { background: #fee2e2; color: #991b1b; }

        .print-btn {
            background: #1a2744;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        @media screen {
            body {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                padding: 20px;
            }

            .side { box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15); }
        }

        @media print {
            body { background: none; }
            .no-print { display: none !important; }
            .side + .side { page-break-before: always; }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print / Save as PDF</button>

    @if ($error)
        <div class="notice error no-print"><strong>Preview unavailable.</strong> {{ $error }}</div>
    @else
        @foreach ($issues as $issue)
            <div class="notice no-print">{{ $issue['message'] }}</div>
        @endforeach

        <div class="side">{!! $front !!}</div>
        <div class="side">{!! $back !!}</div>
    @endif
</body>
</html>
