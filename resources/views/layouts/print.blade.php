<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'Print' }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Inter', 'Noto Sans', 'Roboto', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .print-action-bar,
            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
                break-after: page;
            }

            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            h1, h2, h3, h4, h5, h6, p {
                page-break-inside: avoid;
                orphans: 3;
                widows: 3;
            }
        }

        @media screen {
            body {
                background: #f5f5f5;
                padding: 1rem;
            }

            .page {
                background: #fff;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                margin: 1rem auto;
            }

            .print-action-bar {
                display: flex;
                justify-content: center;
                gap: 1rem;
                margin: 1rem 0;
                padding: 1rem;
            }

            .print-action-bar button {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
                cursor: pointer;
                background: #0ea5e9;
                color: #fff;
                border: none;
                border-radius: 0.375rem;
            }

            .print-action-bar button:hover {
                background: #0284c7;
            }
        }

        img {
            max-width: 100%;
            height: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5cm 0;
        }

        td, th {
            border: 1px solid #ccc;
            padding: 0.3cm;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    {{ $slot }}

    <div class="print-action-bar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
