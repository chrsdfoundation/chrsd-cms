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

            img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: high-quality;
                break-inside: avoid;
            }

            @page {
                size: A4 portrait;
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
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 8mm;
                padding: 0;
                background: white;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                width: 210mm;
                height: 297mm;
            }

            .card {
                border: 0.75px solid #c0c0c0;
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
                page-break-after: avoid;
                border-radius: 3mm;
            }
        }

        @media screen {
            .card-page {
                border: 1px dashed #ccc;
                margin-bottom: 4mm;
                border-radius: 3mm;
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
            border-radius: 3mm;
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
        .crop-tl::before { width: 2.5mm; height: 0.25mm; top: 3mm; left: 0; }
        .crop-tl::after  { width: 0.25mm; height: 2.5mm; top: 0; left: 3mm; }
        /* Top-right */
        .crop-tr::before { width: 2.5mm; height: 0.25mm; top: 3mm; right: 0; }
        .crop-tr::after  { width: 0.25mm; height: 2.5mm; top: 0; right: 3mm; }
        /* Bottom-left */
        .crop-bl::before { width: 2.5mm; height: 0.25mm; bottom: 3mm; left: 0; }
        .crop-bl::after  { width: 0.25mm; height: 2.5mm; bottom: 0; left: 3mm; }
        /* Bottom-right */
        .crop-br::before { width: 2.5mm; height: 0.25mm; bottom: 3mm; right: 0; }
        .crop-br::after  { width: 0.25mm; height: 2.5mm; bottom: 0; right: 3mm; }
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
            width: calc(3mm + 5mm);
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
            width: 5mm;
            height: 100%;
            background: linear-gradient(180deg, var(--navy-primary) 0%, var(--navy-secondary) 100%);
            border-right: 2.5px solid var(--gold-soft);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 1.2mm,
                rgba(255,255,255,0.06) 1.2mm,
                rgba(255,255,255,0.06) 1.5mm
            );
        }

        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2.5mm 3mm 5.5mm;
            position: relative;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
        }

        .card-content::after {
            content: '';
            position: absolute;
            top: 1mm;
            left: 0.5mm;
            right: 1mm;
            bottom: 1mm;
            border: 0.3px solid rgba(201, 162, 39, 0.15);
            border-radius: 1.5mm;
            pointer-events: none;
            z-index: 0;
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
            align-items: flex-start;
            z-index: 1;
            flex: 1;
        }

        .logo-block {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
            justify-content: flex-start;
        }

        .logo {
            width: 7.5mm;
            height: 7.5mm;
            margin-bottom: 0.4mm;
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
            font-size: 7.5pt;
            font-weight: 900;
            color: var(--navy-primary);
            line-height: 1;
            margin-bottom: 0.2mm;
            letter-spacing: 0.5px;
        }

        .org-full {
            font-size: 4.2pt;
            color: var(--navy-secondary);
            line-height: 1.2;
            margin-bottom: 0.3mm;
            font-weight: 600;
        }

        .card-type-label {
            font-size: 5.5pt;
            font-weight: 800;
            background: rgba(13, 27, 42, 0.04);
            color: var(--navy-primary);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 0.4mm 1.2mm 0.4mm 1.4mm;
            border-left: 2.5px solid var(--gold-bright);
            display: inline-block;
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
            border: 2px solid var(--gold-soft);
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

        .photo-box {
            aspect-ratio: 0.82;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 20%;
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
            font-size: 4pt;
            color: var(--text-light);
            text-align: center;
            font-weight: 500;
        }

        /* DIVIDER */
        .divider-gold {
            height: 1px;
            background: linear-gradient(90deg, var(--gold-soft), var(--gold-bright), var(--gold-soft));
            margin: 1mm 0;
            opacity: 0.8;
        }

        /* EMPLOYEE SECTION (now inline within logo-block) */

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
            color: var(--navy-primary);
            font-style: italic;
            line-height: 1.2;
            margin-bottom: 0.6mm;
            font-weight: 600;
        }

        /* DETAILS GRID */
        .details-grid {
            font-size: 4.6pt;
            line-height: 1.4;
            color: var(--text-dark);
        }

        .detail-row {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 0.5mm;
            margin: 0.3mm 0;
            align-items: center;
        }

        .detail-label {
            font-weight: 400;
            color: var(--text-light);
            font-size: 4pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .detail-value {
            font-weight: 800;
            color: #1a1a1a;
            font-size: 5pt;
            letter-spacing: 0.05px;
        }

        /* QR CODE BOX (front) */
        .qr-box {
            position: absolute;
            bottom: 1.8mm;
            right: 1.8mm;
            width: 10mm;
            height: 10mm;
            border: 1.5px solid var(--gold-soft);
            border-radius: 3px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            padding: 0.6mm;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .qr-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* DISCLAIMER */
        .disclaimer {
            position: absolute;
            bottom: 2mm;
            left: 3mm;
            right: 12mm;
            font-size: 4pt;
            line-height: 1.25;
            color: var(--text-muted);
            text-align: center;
            z-index: 1;
            font-weight: 500;
            border-top: 0.5px solid var(--text-light);
            padding-top: 0.8mm;
        }

        /* ========== BACK SIDE ========== */
        .card-back {
            display: flex;
            flex-direction: column;
            position: relative;
            background: linear-gradient(135deg, white 0%, var(--cream-bg) 100%);
        }

        .card-back::after {
            content: '';
            position: absolute;
            inset: 1mm;
            border: 0.3px solid rgba(201, 162, 39, 0.12);
            border-radius: 1.5mm;
            pointer-events: none;
            z-index: 0;
        }

        /* GHOST IMAGE — semi-transparent greyscale copy of the holder's photo */
        .ghost-photo {
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 12mm;
            height: 15mm;
            opacity: 0.30;
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
            font-size: 4pt;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.2;
            text-align: center;
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
            background: rgba(13, 27, 42, 0.1);
            color: var(--navy-primary);
            border: 1px solid rgba(13, 27, 42, 0.3);
            padding: 0.4mm 1.2mm;
            border-radius: 10px;
            font-size: 4.8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
            margin-bottom: 0.4mm;
        }

        .instructions-list {
            font-size: 4pt;
            line-height: 1.45;
            color: var(--text-muted);
            list-style: none;
            counter-reset: resp;
            padding: 0.4mm 0;
        }

        .instructions-list li {
            counter-increment: resp;
            position: relative;
            padding-left: 3.5mm;
            margin-bottom: 0.5mm;
            text-align: justify;
            font-weight: 500;
        }

        .instructions-list li::before {
            content: counter(resp);
            position: absolute;
            left: 0;
            top: 0.1mm;
            width: 2.5mm;
            height: 2.5mm;
            border-radius: 50%;
            background: var(--navy-primary);
            color: #fff;
            font-size: 3.5pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
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
            width: 15mm;
            height: 15mm;
            border: 1.5px solid var(--gold-soft);
            border-radius: 4px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
            padding: 1.2mm;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .qr-label {
            font-size: 4pt;
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
            font-size: 4pt;
            font-weight: 500;
            color: var(--text-light);
            font-style: italic;
            line-height: 1.2;
        }

        /* FOOTER CONTACT ROW */
        .footer-contact {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.4mm 1.2mm;
            font-size: 4pt;
            line-height: 1.35;
            color: var(--text-muted);
            border-top: 0.8px solid rgba(13, 27, 42, 0.15);
            border-bottom: 0.8px solid rgba(13, 27, 42, 0.15);
            padding: 0.6mm 0.3mm;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.4mm;
        }

        .contact-icon {
            width: 2.8mm;
            height: 2.8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(13, 27, 42, 0.1);
            border: 0.3px solid rgba(13, 27, 42, 0.25);
            border-radius: 50%;
            padding: 0.2mm;
        }

        .contact-icon svg {
            width: 100%;
            height: 100%;
            fill: var(--navy-primary);
        }

        .contact-text {
            font-weight: 500;
            flex: 1;
            word-break: break-word;
        }

        /* FOUND-CARD RETURN BAR */
        .found-bar {
            background: rgba(13, 27, 42, 0.05);
            border: 0.8px solid var(--navy-primary);
            border-radius: 2px;
            padding: 0.5mm 1mm;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.8mm;
            margin-top: 0.5mm;
            font-size: 3.8pt;
            line-height: 1.35;
            color: var(--navy-primary);
            text-align: center;
        }

        .found-bar-icon {
            width: 2.2mm;
            height: 2.2mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .found-bar-icon svg {
            width: 100%;
            height: 100%;
            fill: var(--navy-primary);
        }

        .found-bar-text strong {
            color: var(--navy-primary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 800;
        }

        /* BOTTOM ACCENT BAR */
        .back-accent-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1.5px;
            background: linear-gradient(90deg, var(--navy-primary), var(--gold-soft), var(--navy-primary));
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

                            <div class="divider-gold"></div>

                            <div class="employee-name" style="font-size: {{ $card->nameFontSize() }};">{{ $card->displayName() }}</div>
                            <div class="employee-designation">{{ $card->designation ?? ($employee->position?->title ?? '') }}</div>

                            <div class="details-grid">
                                <div class="detail-row">
                                    <div class="detail-label">ID No</div>
                                    <div class="detail-value">{{ $card->serial_number ?? 'N/A' }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Blood Group</div>
                                    <div class="detail-value">{{ $card->blood_group }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Nationality</div>
                                    <div class="detail-value">{{ $card->nationality }}</div>
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
                            <div class="contact-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.24 1.01l-2.2 2.2z"/></svg></div>
                            <div class="contact-text">+880-2-47122566</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17 1.01L7 1a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zM17 19H7V5h10v14zm-4.2-5.78v1.75a6.26 6.26 0 0 1-3.7-3.7h1.75L12 9.12 10.85 7H8.1a5 5 0 0 0 8.8 3.17l-1.27-1.28a3.45 3.45 0 0 1-2.83 1.33z"/></svg></div>
                            <div class="contact-text">+880-1716-610665</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></div>
                            <div class="contact-text">info@chrsd.org</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1a2 2 0 0 0 2 2v1.93zm6.9-2.54A1.99 1.99 0 0 0 16 16h-1v-3a1 1 0 0 0-1-1H8v-2h2a1 1 0 0 0 1-1V7h2a2 2 0 0 0 2-2v-.41a7.98 7.98 0 0 1 2.9 12.8z"/></svg></div>
                            <div class="contact-text">www.chrsd.org</div>
                        </div>
                    </div>
                    <div class="found-bar">
                        <span class="found-bar-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg></span>
                        <span class="found-bar-text"><strong>Found this card?</strong> Return to: 29 Toyenbee Circular Road (5th Floor), Motijheel C/A, Dhaka-1000, Bangladesh.</span>
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
