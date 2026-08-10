<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHRSD ID Card - {{ $idCard->serial_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 85.6mm 54mm;
            margin: 0;
        }

        body {
            width: 85.6mm;
            height: 54mm;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: #163E22;
            overflow: hidden;
        }

        .card {
            width: 100%;
            height: 100%;
            display: flex;
            background: white;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 60mm;
            height: 60mm;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="90" fill="none" stroke="gray" stroke-width="2"/><text x="100" y="105" text-anchor="middle" font-size="12" fill="gray">CHRSD</text></svg>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
            z-index: 0;
        }

        .sidebar {
            width: 8mm;
            height: 100%;
            background: #123420;
            display: flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            color: #C9A14A;
            font-weight: bold;
            font-size: 7pt;
            padding: 4mm 0;
            position: relative;
            z-index: 2;
        }

        .content {
            flex: 1;
            padding: 1.8mm 1.8mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: white;
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            gap: 1.2mm;
            align-items: flex-start;
            margin-bottom: 0.8mm;
        }

        .logo {
            width: 9mm;
            height: 9mm;
            flex-shrink: 0;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        .org-info {
            flex: 1;
        }

        .org-name {
            font-size: 8pt;
            font-weight: bold;
            color: #123420;
            margin: 0;
            line-height: 1.1;
        }

        .org-sub {
            font-size: 2.8pt;
            color: #186D3B;
            margin: 0;
            line-height: 1.2;
        }

        .card-type {
            font-size: 5.5pt;
            font-weight: bold;
            color: #C9A14A;
            text-transform: uppercase;
            margin-top: 0.3mm;
            letter-spacing: 0.5pt;
        }

        .divider {
            border-top: 0.8mm solid #C9A14A;
            margin: 0.4mm 0;
        }

        .main-content {
            display: flex;
            gap: 1.2mm;
            flex: 1;
            align-items: center;
        }

        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .person-info {
            margin-bottom: 0.4mm;
        }

        .name {
            font-size: 8pt;
            font-weight: bold;
            color: #123420;
            margin: 0;
            line-height: 1.2;
        }

        .designation {
            font-size: 3.8pt;
            font-style: italic;
            color: #186D3B;
            margin: 0;
        }

        .details-table {
            font-size: 3.2pt;
            width: 100%;
            margin-bottom: 0.3mm;
            border-spacing: 0;
        }

        .details-table tr {
            height: 2.2mm;
        }

        .details-label {
            color: #186D3B;
            font-weight: normal;
            width: 13mm;
            padding-right: 0.4mm;
            text-align: left;
        }

        .details-value {
            color: #123420;
            font-weight: bold;
            text-align: left;
        }

        .photo-section {
            width: 16mm;
            height: 20mm;
            border: 0.5mm solid #C9A14A;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            flex-shrink: 0;
            overflow: hidden;
        }

        .photo-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            font-size: 3pt;
            color: #999;
        }

        .footer {
            font-size: 2.3pt;
            color: #186D3B;
            text-align: center;
            line-height: 1.3;
            border-top: 0.5mm solid #C9A14A;
            padding-top: 0.2mm;
        }

        .back-card {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .back-sidebar {
            width: 8mm;
            height: 100%;
            background: #123420;
            display: flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            color: #C9A14A;
            font-weight: bold;
            font-size: 7pt;
            position: absolute;
            left: 0;
        }

        .back-content {
            margin-left: 8mm;
            padding: 1.5mm 1.8mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            background: #fafafa;
            position: relative;
        }

        .back-header {
            display: flex;
            align-items: center;
            gap: 0.8mm;
            margin-bottom: 0.6mm;
            border-bottom: 0.5mm solid #C9A14A;
            padding-bottom: 0.4mm;
        }

        .back-logo {
            width: 5.5mm;
            height: 5.5mm;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .back-title {
            font-size: 5.5pt;
            font-weight: bold;
            color: #123420;
        }

        .instructions {
            flex: 1;
            font-size: 3.5pt;
            line-height: 1.35;
            color: #186D3B;
            margin-bottom: 0.4mm;
        }

        .instructions-title {
            font-weight: bold;
            font-size: 4pt;
            margin-bottom: 0.2mm;
            color: #123420;
        }

        .instructions ul {
            margin-left: 1.2mm;
            padding: 0;
            list-style: disc;
        }

        .instructions li {
            margin: 0.1mm 0;
        }

        .contact-section {
            font-size: 3pt;
            margin-bottom: 0.5mm;
            color: #186D3B;
        }

        .contact-item {
            margin: 0.15mm 0;
        }

        .divider-back {
            border-top: 0.4mm solid #C9A14A;
            margin: 0.3mm 0;
        }

        .footer-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 0.8mm;
            height: 14mm;
        }

        .url {
            font-size: 3pt;
            color: #186D3B;
            font-weight: bold;
        }

        .qr-signature {
            display: flex;
            gap: 1mm;
            align-items: flex-end;
            height: 100%;
        }

        .qr-code {
            width: 11mm;
            height: 11mm;
            border: 0.5mm solid #C9A14A;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            flex-shrink: 0;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
        }

        .signature-block {
            text-align: center;
            font-size: 2.5pt;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2mm;
        }

        .signature-image {
            width: 11mm;
            height: 6mm;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        .signature-label {
            font-style: italic;
            color: #186D3B;
            white-space: nowrap;
        }

        .print-button-container {
            display: flex;
            justify-content: center;
            padding: 20px 0;
            gap: 10px;
        }

        .print-button {
            padding: 10px 20px;
            background-color: #123420;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .print-button:hover {
            background-color: #186D3B;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .card, .back-card {
                page-break-after: avoid;
            }
            .print-button-container {
                display: none;
            }
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="print-button-container">
        <button class="print-button" onclick="window.print()" title="Print ID Card to PDF">
            🖨️ Print to PDF
        </button>
    </div>
    <div class="card">
        <div class="watermark"></div>
        <div class="sidebar">CHRSD</div>
        <div class="content">
            <div>
                <div class="header">
                    <div class="logo" style="background-image: url('{{ asset('images/brand/chrsd-round-logo.png') }}');"></div>
                    <div class="org-info">
                        <div class="org-name">CHRSD</div>
                        <div class="org-sub">CENTRE FOR HUMANITARIAN RESEARCH &<br>SOCIAL DEVELOPMENT FOUNDATION</div>
                        <div class="card-type">EMPLOYEE IDENTITY</div>
                    </div>
                </div>
                <div class="divider"></div>
            </div>

            <div class="main-content">
                <div class="left-section">
                    <div class="person-info">
                        <div class="name">{{ $idCard->displayName() }}</div>
                        <div class="designation">{{ $idCard->designation ?: $idCard->program_name ?: optional(optional($idCard->employee)->position)->title }}</div>
                    </div>

                    <table class="details-table">
                        <tr>
                            <td class="details-label">ID No</td>
                            <td class="details-value">{{ $idCard->serial_number }}</td>
                        </tr>
                        <tr>
                            <td class="details-label">Blood Group</td>
                            <td class="details-value">{{ $idCard->blood_group ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="details-label">Nationality</td>
                            <td class="details-value">{{ $idCard->nationality ?: optional($idCard->employee)->nationality ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="details-label">Valid From</td>
                            <td class="details-value">{{ optional($idCard->valid_from)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="details-label">Expires</td>
                            <td class="details-value">{{ optional($idCard->valid_until)->format('d M Y') }}</td>
                        </tr>
                    </table>
                </div>

                @if ($idCard->hasMedia('photo'))
                    <div class="photo-section">
                        <img src="{{ $idCard->getFirstMediaUrl('photo') }}" alt="Photo">
                    </div>
                @else
                    <div class="photo-section">
                        <div class="photo-placeholder">PHOTO</div>
                    </div>
                @endif
            </div>

            <div class="divider"></div>
            <div class="footer">This card certifies that the bearer is an authorized representative of CHRSD. All concerned are requested to extend necessary cooperation.</div>
        </div>
    </div>

    <div class="page-break back-card">
        <div class="back-sidebar">CHRSD</div>
        <div class="back-content">
            <div class="back-header">
                <div class="back-logo" style="background-image: url('{{ asset('images/brand/chrsd-round-logo.png') }}');"></div>
                <div class="back-title">CHRSD</div>
            </div>

            <div class="instructions">
                <div class="instructions-title">INSTRUCTIONS & NOTICE</div>
                <ul>
                    <li>This card is the property of CHRSD.</li>
                    <li>Must be worn/carried at all times while on duty.</li>
                    <li>Must be returned upon resignation, termination, or upon request.</li>
                    <li>If found, please return to the address below.</li>
                </ul>
            </div>

            <div class="contact-section">
                <div class="contact-item">📞 +880 2-47122566 | +880 1714-781490</div>
                <div class="contact-item">✉ info@chrsd.org</div>
                <div class="contact-item">📍 29 Toyenbee Cir. Rd (5th Fl), Motijheel, Dhaka-1000</div>
            </div>

            <div class="divider-back"></div>

            <div class="footer-section">
                <div class="url">🌐 www.chrsd.org</div>
                <div class="qr-signature">
                    @if ($idCard->verification_hash)
                        <div class="qr-code">
                            {!! app(\App\Services\QrCodeService::class)->svg($idCard) !!}
                        </div>
                    @else
                        <div class="qr-code" style="background: white; font-size: 2pt; color: #999;">QR</div>
                    @endif

                    <div class="signature-block">
                        @if ($idCard->hasMedia('signature'))
                            <div class="signature-image" style="background-image: url('{{ $idCard->getFirstMediaUrl('signature') }}');"></div>
                        @else
                            <div style="width: 11mm; border-top: 0.4mm solid #163E22;"></div>
                        @endif
                        <div class="signature-label">Authorized<br>Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
