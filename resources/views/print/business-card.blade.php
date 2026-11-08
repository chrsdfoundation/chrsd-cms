<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Card — {{ $card->full_name }}</title>
    <style>
        :root {
            --card-w: 85mm;
            --card-h: 55mm;
            --bleed: 3mm;
            --safe: 3mm;
            --navy: #1a2744;
            --gold: #c5952a;
            --light-bg: #f8f7f4;
            --text-primary: #1a2744;
            --text-secondary: #4a5568;
            --text-muted: #718096;
        }

        @if($mode === 'bleed')
        @page {
            size: 91mm 61mm;
            margin: 0;
        }
        @else
        @page {
            size: 85mm 55mm;
            margin: 0;
        }
        @endif

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', 'Nirmala UI', 'Noto Sans', 'Noto Sans Bengali', system-ui, -apple-system, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            background: #e2e8f0;
        }

        .card-container {
            @if($mode === 'bleed')
            width: 91mm;
            height: 61mm;
            padding: var(--bleed);
            @else
            width: var(--card-w);
            height: var(--card-h);
            @endif
            background: var(--light-bg);
            overflow: hidden;
            position: relative;
        }

        .card-inner {
            width: var(--card-w);
            height: var(--card-h);
            padding: var(--safe);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        /* Header: logo + company name */
        .card-header {
            display: flex;
            align-items: center;
            gap: 2mm;
            margin-bottom: 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 0.3mm solid var(--gold);
        }

        .card-logo {
            width: 10mm;
            height: 10mm;
            flex-shrink: 0;
            object-fit: contain;
        }

        .company-name {
            font-size: 8pt;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: 0.02em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        /* Name + title */
        .card-name {
            font-size: {{ $card->nameFontSize() }};
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
            margin-top: 1.5mm;
        }

        .card-title {
            font-size: 6.5pt;
            color: var(--gold);
            font-weight: 600;
            margin-top: 0.3mm;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Contact details */
        .card-contacts {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 0.6mm;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 1.2mm;
            font-size: 5.5pt;
            color: var(--text-secondary);
            line-height: 1.3;
        }

        .contact-icon {
            width: 2.5mm;
            height: 2.5mm;
            flex-shrink: 0;
            fill: var(--gold);
        }

        .contact-row a {
            color: inherit;
            text-decoration: none;
        }

        /* Tagline */
        .card-tagline {
            font-size: 5pt;
            color: var(--text-muted);
            font-style: italic;
            text-align: center;
            margin-top: 1mm;
            padding-top: 0.8mm;
            border-top: 0.2mm solid #e2e8f0;
        }

        /* QR code area */
        .card-qr {
            position: absolute;
            bottom: var(--safe);
            right: var(--safe);
            width: 18mm;
            height: 18mm;
        }

        .card-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Address */
        .card-address {
            font-size: 4.5pt;
            color: var(--text-muted);
            line-height: 1.3;
            max-width: calc(100% - 20mm);
        }

        /* Screen-only: preview shell */
        @media screen {
            body { display: flex; flex-direction: column; align-items: center; padding: 20mm; gap: 10mm; }

            .card-container {
                box-shadow: 0 4px 24px rgba(0,0,0,0.15);
                border-radius: 2mm;
            }

            .print-controls {
                display: flex;
                gap: 8px;
                align-items: center;
                font-family: system-ui, sans-serif;
            }

            .print-btn {
                background: var(--navy);
                color: #fff;
                border: none;
                padding: 10px 24px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
            }

            .print-btn:hover { opacity: 0.9; }

            .mode-link {
                font-size: 13px;
                color: var(--text-secondary);
                text-decoration: underline;
            }

            .qr-help {
                max-width: 400px;
                text-align: center;
                font-size: 12px;
                color: var(--text-muted);
                font-family: system-ui, sans-serif;
                line-height: 1.5;
            }
        }

        @media print {
            body { background: none; padding: 0; margin: 0; }
            .print-controls, .qr-help { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
        @if($mode === 'bleed')
            <a class="mode-link" href="{{ route('print.business-card', ['card' => $card, 'mode' => 'trim']) }}">Switch to trim (no bleed)</a>
        @else
            <a class="mode-link" href="{{ route('print.business-card', ['card' => $card, 'mode' => 'bleed']) }}">Switch to bleed (+3 mm)</a>
        @endif
    </div>

    <div class="card-container">
        <div class="card-inner">
            {{-- Header --}}
            <div class="card-header">
                @if($logoUrl)
                    <img class="card-logo" src="{{ $logoUrl }}" alt="Logo">
                @else
                    <svg class="card-logo" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="4" fill="#1a2744"/>
                        <text x="20" y="26" text-anchor="middle" fill="#c5952a" font-size="18" font-weight="700" font-family="sans-serif">{{ mb_substr($card->company_name, 0, 1) }}</text>
                    </svg>
                @endif
                <span class="company-name">{{ $card->company_name }}</span>
            </div>

            {{-- Name & Title --}}
            <div class="card-name">{{ $card->full_name }}</div>
            @if($card->job_title)
                <div class="card-title">{{ $card->job_title }}</div>
            @endif

            {{-- Contact rows --}}
            <div class="card-contacts">
                @if($card->phone)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.36 11.36 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.36 11.36 0 00.57 3.58 1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                    <span>{{ $card->phone }}</span>
                </div>
                @endif

                @if($card->mobile)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 2a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2H7zm5 18a1 1 0 110-2 1 1 0 010 2zm4-4H8V5h8v11z"/></svg>
                    <span>{{ $card->mobile }}</span>
                </div>
                @endif

                @if($card->whatsapp)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.82.49 3.53 1.34 5L2 22l5.16-1.34A9.93 9.93 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm.03 17.5a7.97 7.97 0 01-4.1-1.13l-.29-.17-3.02.79.81-2.95-.19-.3A7.96 7.96 0 014.5 12a7.5 7.5 0 1115 0 7.5 7.5 0 01-7.47 7.5zm4.13-5.62c-.23-.11-1.35-.66-1.55-.74-.21-.08-.36-.11-.51.12-.15.23-.59.74-.72.89-.13.15-.27.17-.5.06a6.32 6.32 0 01-1.86-1.15 6.98 6.98 0 01-1.29-1.6c-.13-.23-.01-.36.1-.47.1-.1.23-.27.34-.4.11-.14.15-.23.23-.38.08-.15.04-.28-.02-.4-.06-.11-.51-1.23-.7-1.69-.18-.44-.37-.38-.51-.39h-.44a.84.84 0 00-.61.29c-.21.23-.8.78-.8 1.9s.82 2.2.93 2.36c.12.15 1.62 2.47 3.92 3.46.55.24.98.38 1.31.49.55.17 1.05.15 1.45.09.44-.07 1.35-.55 1.54-1.08.19-.53.19-.99.14-1.08-.06-.1-.21-.15-.44-.27z"/></svg>
                    <span>{{ $card->whatsapp }}</span>
                </div>
                @endif

                @foreach(array_filter([$card->work_email, $card->personal_email]) as $emailAddress)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <span>{{ $emailAddress }}</span>
                </div>
                @endforeach

                @if($card->website)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93A8.01 8.01 0 014 12c0-.34.02-.67.06-1h3.19a19.8 19.8 0 000 2H4.06c.2 1.59.83 3.06 1.75 4.29L7.1 18.6A8.07 8.07 0 0011 19.93zm2 0A8.07 8.07 0 0016.9 18.6l1.29-1.29a8 8 0 001.75-4.29h-3.19a19.8 19.8 0 000-2h3.19A8.01 8.01 0 0013 4.07V7h-2V4.07A8.01 8.01 0 004.06 11h3.19c.02.33.03.66.03 1s-.01.67-.03 1H4.06A8.01 8.01 0 0011 19.93z"/></svg>
                    <span>{{ $card->website }}</span>
                </div>
                @endif

                @if($card->linkedin)
                <div class="contact-row">
                    <svg class="contact-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14zm-.5 15.5v-5.3a3.26 3.26 0 00-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 011.4 1.4v4.93h2.79zM6.88 8.56a1.68 1.68 0 001.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 00-1.69 1.69c0 .93.76 1.68 1.69 1.68zm1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                    <span>{{ $card->linkedin }}</span>
                </div>
                @endif

                @if($card->address)
                <div class="card-address">{{ $card->address }}</div>
                @endif
            </div>

            {{-- Tagline --}}
            @if($card->tagline)
            <div class="card-tagline">{{ $card->tagline }}</div>
            @endif

            {{-- QR Code --}}
            <div class="card-qr">
                <img src="{{ $qrUrl }}" alt="vCard QR">
            </div>
        </div>
    </div>

    <div class="qr-help">
        Scan the QR code with your phone camera to save this contact.<br>
        <em>The QR encodes the card's current data. Editing the record later does not change cards already printed.</em>
    </div>
</body>
</html>
