@php
    $idTypeLabel = $card->id_type_label ?: (optional($card->idCardType)->name ?: 'Identity Card');
    $isBleed = ($mode ?? 'bleed') === 'bleed';
    // Bleed adds 3mm on each side: 85.6+6=91.6mm × 53.98+6=59.98mm
    $pageW = $isBleed ? '91.60mm' : '85.60mm';
    $pageH = $isBleed ? '59.98mm' : '53.98mm';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card: {{ $card->serial_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 24px;
            min-height: 100vh;
        }

        @media print {
            html, body {
                background: white;
                padding: 0;
                margin: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            @page {
                size: {{ $pageW }} {{ $pageH }};
                margin: 0;
                padding: 0;
            }

            .print-button, .print-section {
                display: none !important;
            }
        }

        /* ========== COLOR PALETTE ========== */
        :root {
            --navy-primary: #0d1b2a;
            --navy-secondary: #1a237e;
            --gold-soft: #c9a227;
            --gold-bright: #d4af37;
            --cream-bg: #faf9f7;
            --text-dark: #1a1a1a;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
        }

        /* ========== CARD SHEET CONTAINER ========== */
        .card-sheet {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12mm;
            width: 100%;
            max-width: 220mm;
            margin: 0 auto;
            padding: 12mm;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        @media print {
            .card-sheet {
                display: block;
                gap: 0;
                padding: 0;
                background: white;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
        }

        /* ========== BLEED / TRIM WRAPPER ========== */
        .card-page {
            width: {{ $pageW }};
            height: {{ $pageH }};
            position: relative;
            overflow: hidden;
        }

        @media print {
            .card-page {
                page-break-after: always;
            }
            .card-page:last-child {
                page-break-after: auto;
            }
        }

        @media screen {
            .card-page {
                border: 1px dashed #ccc;
                margin-bottom: 4mm;
            }
        }

        /* ========== CARD BASE ========== */
        .card {
            width: 85.60mm;
            height: 53.98mm;
            position: absolute;
            display: flex;
            overflow: hidden;
            page-break-inside: avoid;
            background: white;
        }

        @if($isBleed)
        .card {
            top: 3mm;
            left: 3mm;
        }
        @else
        .card {
            top: 0;
            left: 0;
        }
        @endif

        /* ========== CROP MARKS (bleed mode only) ========== */
        @if($isBleed)
        .crop-mark {
            position: absolute;
            z-index: 10;
        }
        .crop-mark::before,
        .crop-mark::after {
            content: '';
            position: absolute;
            background: #000;
        }
        /* Top-left */
        .crop-tl::before { width: 2.5mm; height: 0.2mm; top: 3mm; left: 0; }
        .crop-tl::after  { width: 0.2mm; height: 2.5mm; top: 0; left: 3mm; }
        /* Top-right */
        .crop-tr::before { width: 2.5mm; height: 0.2mm; top: 3mm; right: 0; }
        .crop-tr::after  { width: 0.2mm; height: 2.5mm; top: 0; right: 3mm; }
        /* Bottom-left */
        .crop-bl::before { width: 2.5mm; height: 0.2mm; bottom: 3mm; left: 0; }
        .crop-bl::after  { width: 0.2mm; height: 2.5mm; bottom: 0; left: 3mm; }
        /* Bottom-right */
        .crop-br::before { width: 2.5mm; height: 0.2mm; bottom: 3mm; right: 0; }
        .crop-br::after  { width: 0.2mm; height: 2.5mm; bottom: 0; right: 3mm; }
        @endif

        /* ========== BLEED BACKGROUND EXTENSION ========== */
        @if($isBleed)
        .bleed-bg-front {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .bleed-bg-front .bleed-sidebar {
            position: absolute;
            top: 0; bottom: 0; left: 0;
            width: calc(3mm + 2.5mm);
            background: linear-gradient(180deg, var(--navy-primary) 0%, var(--navy-secondary) 100%);
        }
        .bleed-bg-front .bleed-main {
            position: absolute;
            top: 0; bottom: 0;
            left: calc(3mm + 2.5mm);
            right: 0;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
        }

        .bleed-bg-back {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
            z-index: 0;
        }
        @endif

        /* ========== FRONT SIDE ========== */
        .card-front {
            display: flex;
            background: white;
        }

        .sidebar {
            width: 2.5mm;
            height: 100%;
            background: linear-gradient(180deg, var(--navy-primary) 0%, var(--navy-secondary) 100%);
            border-right: 2px solid var(--gold-soft);
            flex-shrink: 0;
            position: relative;
        }

        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2.5mm 3mm 5.5mm;
            position: relative;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
        }

        /* WATERMARK SEAL */
        .watermark {
            position: absolute;
            bottom: 2mm;
            left: 2mm;
            width: 18mm;
            height: 18mm;
            opacity: 0.04;
            z-index: 0;
        }

        .watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* HEADER SECTION */
        .card-header {
            display: flex;
            gap: 1.6mm;
            margin-bottom: 1.4mm;
            align-items: flex-start;
            z-index: 1;
        }

        .logo-block {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .logo {
            width: 6mm;
            height: 6mm;
            margin-bottom: 0.3mm;
        }

        @media screen {
            .logo {
                filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.08));
            }
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .org-short {
            font-size: 6.5pt;
            font-weight: 800;
            color: var(--navy-primary);
            line-height: 1;
            margin-bottom: 0.2mm;
            letter-spacing: 0.3px;
        }

        .org-full {
            font-size: 3.6pt;
            color: var(--text-muted);
            line-height: 1.2;
            margin-bottom: 0.3mm;
            font-weight: 500;
        }

        .card-type-label {
            font-size: 5.5pt;
            font-weight: 700;
            background: rgba(201, 162, 39, 0.1);
            color: var(--gold-soft);
            letter-spacing: 0.4px;
            text-transform: uppercase;
            padding: 0.3mm 0.7mm;
            border-radius: 3px;
            display: inline-block;
            border: 0.5px solid rgba(201, 162, 39, 0.3);
        }

        /* RIGHT COLUMN: PHOTO + BEARER SIGNATURE */
        .right-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.8mm;
            flex-shrink: 0;
        }

        /* PHOTO BOX */
        .photo-box {
            width: 16mm;
            height: 19.5mm;
            border: 1.5px solid var(--gold-soft);
            border-radius: 4px;
            background: white;
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08), inset 0 0 0 0.5px rgba(201, 162, 39, 0.2);
            position: relative;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 1;
        }

        .photo-placeholder {
            font-size: 2pt;
            color: #e5e7eb;
            position: relative;
            z-index: 1;
        }

        /* BEARER'S SIGNATURE (front) */
        .bearer-signature {
            width: 16mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2mm;
        }

        .bearer-sig-image {
            width: 14mm;
            height: 4mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bearer-sig-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .bearer-sig-line {
            width: 14mm;
            height: 0.3px;
            background: var(--text-muted);
            opacity: 0.5;
        }

        .bearer-sig-caption {
            font-size: 2.8pt;
            color: var(--text-light);
            text-align: center;
            font-weight: 500;
        }

        /* DIVIDER */
        .divider-gold {
            height: 0.6px;
            background: linear-gradient(90deg, transparent, var(--gold-soft), transparent);
            margin: 0.8mm 0;
            opacity: 0.5;
        }

        /* EMPLOYEE SECTION */
        .employee-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            z-index: 1;
            min-width: 0;
        }

        .employee-name {
            font-weight: 900;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 0.4mm;
            word-break: break-word;
            letter-spacing: -0.3px;
        }

        .employee-designation {
            font-size: 5pt;
            color: var(--text-muted);
            font-style: italic;
            line-height: 1.2;
            margin-bottom: 0.6mm;
            font-weight: 400;
        }

        /* DETAILS GRID */
        .details-grid {
            font-size: 4.6pt;
            line-height: 1.4;
            color: var(--text-dark);
            flex: 1;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 0.5mm;
            margin: 0.3mm 0;
            align-items: center;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 4.4pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .detail-value {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 4.7pt;
            letter-spacing: 0.05px;
        }

        /* QR CODE BOX (front) */
        .qr-box {
            position: absolute;
            bottom: 1.8mm;
            right: 1.8mm;
            width: 10.5mm;
            height: 10.5mm;
            border: 1.5px solid var(--gold-soft);
            border-radius: 4px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            padding: 0.35mm;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .qr-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* DISCLAIMER */
        .disclaimer {
            position: absolute;
            bottom: 1mm;
            left: 3mm;
            right: 14mm;
            font-size: 2.6pt;
            line-height: 1.25;
            color: var(--text-light);
            text-align: left;
            z-index: 1;
            font-weight: 500;
        }

        /* ========== BACK SIDE ========== */
        .card-back {
            display: flex;
            flex-direction: column;
            position: relative;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
        }

        /* GHOST IMAGE — semi-transparent greyscale copy of the holder's photo */
        .ghost-photo {
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 12mm;
            height: 15mm;
            opacity: 0.18;
            filter: grayscale(100%);
            z-index: 0;
            overflow: hidden;
            border-radius: 2px;
        }

        .ghost-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* BACK WATERMARK */
        .back-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 36mm;
            height: 36mm;
            opacity: 0.04;
            z-index: 0;
        }

        .back-watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* BACK CONTENT */
        .back-content {
            display: flex;
            flex-direction: column;
            padding: 2mm 2.4mm;
            position: relative;
            z-index: 1;
            height: 100%;
            justify-content: space-between;
        }

        /* BACK TOP SECTION */
        .back-top-section {
            display: flex;
            flex-direction: column;
            gap: 0.6mm;
        }

        .back-header-main {
            display: flex;
            flex-direction: column;
            gap: 0.3mm;
        }

        .back-org-title {
            font-size: 5.5pt;
            font-weight: 700;
            color: var(--navy-primary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }

        .back-reg-no {
            font-size: 3.8pt;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.2;
        }

        /* INSTRUCTIONS AREA */
        .instructions-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .instructions-badge {
            display: inline-block;
            background: rgba(201, 162, 39, 0.1);
            color: var(--gold-soft);
            border: 0.8px solid rgba(201, 162, 39, 0.4);
            padding: 0.4mm 0.9mm;
            border-radius: 10px;
            font-size: 4.6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            width: fit-content;
            margin-bottom: 0.4mm;
        }

        .instructions-list {
            font-size: 4pt;
            line-height: 1.45;
            color: var(--text-muted);
            list-style: decimal;
            padding-left: 2.2mm;
            background: rgba(201, 162, 39, 0.05);
            padding: 0.6mm 0.8mm 0.6mm 2.2mm;
            border-radius: 3px;
            border-left: 2px solid rgba(201, 162, 39, 0.2);
        }

        .instructions-list li {
            margin-bottom: 0.35mm;
            text-align: justify;
            font-weight: 500;
        }

        .instructions-list li::marker {
            color: var(--gold-soft);
            font-weight: 700;
        }

        /* BOTTOM SECTION */
        .back-bottom-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.8mm;
            align-items: flex-end;
        }

        /* QR CODE SECURITY BOX */
        .qr-security-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25mm;
        }

        .qr-container {
            width: 11mm;
            height: 11mm;
            border: 1.5px solid var(--gold-soft);
            border-radius: 4px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
            padding: 0.3mm;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .qr-label {
            font-size: 2.8pt;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: none;
            letter-spacing: 0.1px;
            text-align: center;
            word-break: break-all;
        }

        /* SIGNATURE SECTION */
        .signature-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35mm;
            text-align: center;
        }

        .signature-image-box {
            width: 18mm;
            height: 6mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .signature-image-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 1px 1.5px rgba(0, 0, 0, 0.08));
        }

        .signature-divider {
            width: 18mm;
            height: 0.5px;
            background: var(--text-muted);
            opacity: 0.6;
        }

        .signature-caption {
            font-size: 4.4pt;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.15px;
            margin-top: 0.25mm;
            line-height: 1.3;
        }

        .signature-designation {
            font-size: 3.6pt;
            font-weight: 500;
            color: var(--text-light);
            font-style: italic;
            line-height: 1.2;
        }

        /* FOOTER CONTACT GRID */
        .footer-contact {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8mm;
            font-size: 2.7pt;
            line-height: 1.35;
            color: var(--text-muted);
            border-top: 0.8px solid;
            border-image: linear-gradient(90deg, transparent, var(--gold-soft), transparent) 1;
            padding-top: 0.8mm;
            padding-bottom: 0.4mm;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.35mm;
        }

        .contact-icon {
            width: 2.2mm;
            height: 2.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.8pt;
            color: var(--gold-soft);
            font-weight: 700;
        }

        .contact-text {
            font-weight: 500;
            flex: 1;
            word-break: break-word;
        }

        /* BOTTOM ACCENT BAR */
        .back-accent-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 0.8px;
            background: linear-gradient(90deg, transparent, var(--gold-soft), transparent);
            z-index: 2;
        }

        /* PRINT BUTTONS */
        .print-section {
            display: flex;
            justify-content: center;
            margin-top: 16mm;
            gap: 1rem;
        }

        .print-btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.025rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(14, 165, 233, 0.4);
        }

        @media print {
            .print-section { display: none; }
        }
    </style>
</head>
<body>
    <div class="card-sheet">
        <!-- ========== FRONT SIDE ========== -->
        <div class="card-page">
            @if($isBleed)
                <div class="bleed-bg-front">
                    <div class="bleed-sidebar"></div>
                    <div class="bleed-main"></div>
                </div>
                <div class="crop-mark crop-tl"></div>
                <div class="crop-mark crop-tr"></div>
                <div class="crop-mark crop-bl"></div>
                <div class="crop-mark crop-br"></div>
            @endif

            <div class="card card-front">
                <div class="sidebar"></div>

                <div class="card-content">
                    <div class="watermark">
                        <img src="{{ asset('images/chrsd-seal.png') }}" alt="">
                    </div>

                    <div class="card-header">
                        <div class="logo-block">
                            <div class="logo">
                                <img src="{{ asset('images/chrsd-emblem.png') }}" alt="CHRSD">
                            </div>
                            <div class="org-short">CHRSD</div>
                            <div class="org-full">CENTRE FOR HUMANITARIAN RESEARCH & SOCIAL DEVELOPMENT FOUNDATION</div>
                            <div class="card-type-label">{{ $idTypeLabel }}</div>
                        </div>
                        <div class="right-column">
                            <div class="photo-box">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="Photo">
                                @else
                                    <div class="photo-placeholder">—</div>
                                @endif
                            </div>
                            <div class="bearer-signature">
                                @if($bearerSignature)
                                    <div class="bearer-sig-image">
                                        <img src="{{ $bearerSignature }}" alt="Bearer's signature">
                                    </div>
                                @endif
                                <div class="bearer-sig-line"></div>
                                <div class="bearer-sig-caption">Bearer's signature</div>
                            </div>
                        </div>
                    </div>

                    <div class="divider-gold"></div>

                    <div class="employee-section">
                        <div class="employee-name" style="font-size: {{ $card->nameFontSize() }};">{{ $card->displayName() }}</div>
                        <div class="employee-designation">{{ $card->designation ?? ($employee->position?->title ?? '') }}</div>

                        <div class="details-grid">
                            <div class="detail-row">
                                <div class="detail-label">ID No</div>
                                <div class="detail-value">{{ $card->serial_number ?? 'N/A' }}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Blood Group</div>
                                <div class="detail-value">{{ $card->blood_group ?? 'O+' }}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Nationality</div>
                                <div class="detail-value">{{ $card->nationality ?? 'Bangladeshi' }}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Valid From</div>
                                <div class="detail-value">{{ $card->valid_from?->format('d M Y') ?? 'N/A' }}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Expires</div>
                                <div class="detail-value">{{ $card->valid_until?->format('d M Y') ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="qr-box">
                        @if($qrUrl)
                            <img src="{{ $qrUrl }}" alt="QR">
                        @endif
                    </div>

                    <div class="disclaimer">
                        This card certifies that the bearer is an authorized representative of CHRSD. All concerned are requested to extend necessary cooperation.
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== BACK SIDE ========== -->
        <div class="card-page">
            @if($isBleed)
                <div class="bleed-bg-back"></div>
                <div class="crop-mark crop-tl"></div>
                <div class="crop-mark crop-tr"></div>
                <div class="crop-mark crop-bl"></div>
                <div class="crop-mark crop-br"></div>
            @endif

            <div class="card card-back">
                @if($photo)
                    <div class="ghost-photo">
                        <img src="{{ $photo }}" alt="">
                    </div>
                @endif

                <div class="back-watermark">
                    <img src="{{ asset('images/chrsd-seal.png') }}" alt="">
                </div>

                <div class="back-content">
                    <!-- TOP SECTION -->
                    <div class="back-top-section">
                        <div class="back-header-main">
                            <div class="back-org-title">CENTRE FOR HUMANITARIAN RESEARCH & SOCIAL DEVELOPMENT FOUNDATION</div>
                            @if($registrationNo)
                                <div class="back-reg-no">Reg. No. {{ $registrationNo }}</div>
                            @endif
                        </div>

                        <div class="instructions-area">
                            <div class="instructions-badge">Cardholder Responsibilities</div>
                            <ol class="instructions-list">
                                <li>This card is the property of CHRSD and must be surrendered upon request.</li>
                                <li>Must be worn/carried at all times while on duty.</li>
                                <li>Must be returned upon resignation, termination, or upon request.</li>
                                <li>If found, please return to the address below.</li>
                            </ol>
                        </div>
                    </div>

                    <!-- BOTTOM SECTION -->
                    <div class="back-bottom-section">
                        <div class="qr-security-box">
                            <div class="qr-container">
                                @if($qrUrl)
                                    <img src="{{ $qrUrl }}" alt="QR">
                                @endif
                            </div>
                            <div class="qr-label">{{ $printedVerifyText }}</div>
                        </div>

                        <div class="signature-section">
                            <div class="signature-image-box">
                                @if($signature)
                                    <img src="{{ $signature }}" alt="Signature">
                                @endif
                            </div>
                            <div class="signature-divider"></div>
                            <div class="signature-caption">{{ $signatoryCaption }}</div>
                            @if($signatoryDesignation)
                                <div class="signature-designation">{{ $signatoryDesignation }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- FOOTER CONTACT -->
                    <div class="footer-contact">
                        <div class="contact-item">
                            <div class="contact-icon">☎</div>
                            <div class="contact-text">+880-2-47122566</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">☎</div>
                            <div class="contact-text">+880-1716-610665</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">✉</div>
                            <div class="contact-text">info@chrsd.org</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">🌐</div>
                            <div class="contact-text">www.chrsd.org</div>
                        </div>
                        <div class="contact-item" style="grid-column: 1 / -1;">
                            <div class="contact-icon">📍</div>
                            <div class="contact-text">29 Toyenbee Circular Road (5th Floor), Motijheel C/A<br>Dhaka-1000.</div>
                        </div>
                    </div>
                </div>

                <div class="back-accent-bar"></div>
            </div>
        </div>
    </div>

    <!-- PRINT BUTTONS -->
    <div class="print-section">
        <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        @if($isBleed)
            <a class="print-btn" href="{{ route('print.id-card', $card) }}?mode=trim" style="text-decoration:none;">Card Printer (no bleed)</a>
        @else
            <a class="print-btn" href="{{ route('print.id-card', $card) }}" style="text-decoration:none;">Print Shop (with bleed)</a>
        @endif
    </div>
</body>
</html>
