<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Issuance — {{ $period->format('Y-m') }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; }
        h1 { font-size: 16pt; margin: 0 0 4px; }
        .meta { color: #666; font-size: 9pt; margin-bottom: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        h2 { font-size: 11pt; margin: 18px 0 4px; border-bottom: 1px solid #999; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 12px; }
        th { text-align: left; background: #f3f4f6; padding: 4px 6px; border-bottom: 1px solid #ccc; }
        td { padding: 3px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #555; }
        .col2 { width: 48%; float: left; }
        .col2:last-child { float: right; }
        .clear { clear: both; }
        .totals { display: block; margin: 12px 0; font-size: 12pt; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; text-align: center; color: #888; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Monthly Issuance</h1>
    <div class="meta">
        Period {{ $period->format('F Y') }} —
        generated {{ $generated_at->format('Y-m-d H:i') }}
    </div>

    <div class="totals">
        <strong>{{ $totals['certificates'] }}</strong> certificates
        · <strong>{{ $totals['letters'] }}</strong> letters
    </div>

    <div class="col2">
        <h2>By certificate type</h2>
        <table>
            <thead><tr><th>Type</th><th style="text-align:right">Count</th></tr></thead>
            <tbody>
                @forelse ($by_type as $code => $count)
                    <tr>
                        <td class="serial">{{ $code }}</td>
                        <td style="text-align:right">{{ $count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="color:#888">No certificates.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="col2">
        <h2>By department</h2>
        <table>
            <thead><tr><th>Department</th><th style="text-align:right">Count</th></tr></thead>
            <tbody>
                @forelse ($by_dept as $name => $count)
                    <tr><td>{{ $name }}</td><td style="text-align:right">{{ $count }}</td></tr>
                @empty
                    <tr><td colspan="2" style="color:#888">—</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="clear"></div>

    @if ($certificates->isNotEmpty())
        <h2>Certificates issued</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:110px">Serial</th>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Purpose</th>
                    <th style="width:80px">Issued</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($certificates as $c)
                    <tr>
                        <td class="serial">{{ $c->serial_number }}</td>
                        <td>{{ optional($c->employee)->full_name }}</td>
                        <td class="serial">{{ optional($c->type)->code }}</td>
                        <td>{{ $c->purpose ?? '—' }}</td>
                        <td>{{ optional($c->issued_on)->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($letters->isNotEmpty())
        <h2>Letters released</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:110px">Serial</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th style="width:80px">Released</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($letters as $l)
                    <tr>
                        <td class="serial">{{ $l->serial_number }}</td>
                        <td>{{ $l->subject }}</td>
                        <td class="serial">{{ optional($l->category)->code }}</td>
                        <td>{{ optional($l->author)->full_name }}</td>
                        <td>{{ optional($l->released_on)->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Confidential — {{ config('app.name') }} · {{ $generated_at->format('Y-m-d') }}</div>
</body>
</html>
