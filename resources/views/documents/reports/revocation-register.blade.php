<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Revocation Register — {{ $from->format('Y-m-d') }} to {{ $to->format('Y-m-d') }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; }
        h1 { font-size: 16pt; margin: 0 0 4px; }
        .meta { color: #666; font-size: 9pt; margin-bottom: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        th { text-align: left; background: #f3f4f6; padding: 4px 6px; border-bottom: 1px solid #ccc; }
        td { padding: 4px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #555; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 7pt; color: #999; word-break: break-all; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; text-align: center; color: #888; font-size: 8pt; }
        .empty { color: #888; font-style: italic; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Revocation Register</h1>
    <div class="meta">
        Range {{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }} —
        generated {{ $generated_at->format('Y-m-d H:i') }} —
        <strong>{{ $total }}</strong> revocations
    </div>

    @if ($rows->isEmpty())
        <p class="empty">No revocations in the selected range — audit register is clean.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:110px">When</th>
                    <th style="width:80px">Kind</th>
                    <th style="width:110px">Serial</th>
                    <th>Subject</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td>{{ optional($r['when'])->format('Y-m-d H:i') }}</td>
                        <td>{{ $r['kind'] }}</td>
                        <td class="serial">{{ $r['serial'] }}</td>
                        <td>{{ $r['label'] }}</td>
                        <td>{{ $r['reason'] ?? '—' }}</td>
                    </tr>
                    <tr><td colspan="5" class="hash">hash: {{ $r['hash'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Confidential — {{ config('app.name') }} · {{ $generated_at->format('Y-m-d') }}</div>
</body>
</html>
