<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance Bundle — {{ $from->format('Y-m-d') }} to {{ $to->format('Y-m-d') }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; }
        h1 { font-size: 20pt; margin: 0 0 6px; }
        h2 { font-size: 13pt; margin: 24px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        .cover { padding: 40mm 0 20mm 0; text-align: center; border-bottom: 3px double #111; margin-bottom: 20px; }
        .cover .title { font-size: 24pt; letter-spacing: 3px; margin-bottom: 6px; }
        .cover .org { font-size: 13pt; color: #333; }
        .cover .period { margin-top: 30px; font-size: 12pt; }
        .cover .stamp { margin-top: 40px; font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #666; }
        .meta { color: #666; font-size: 9pt; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th { text-align: left; background: #f3f4f6; padding: 4px 6px; border-bottom: 1px solid #ccc; }
        td { padding: 3px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #555; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 7pt; color: #888; word-break: break-all; }
        .status { padding: 1px 5px; border-radius: 3px; font-size: 7.5pt; text-transform: uppercase; }
        .status-valid { background: #d1fae5; color: #065f46; }
        .status-revoked { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #fef3c7; color: #92400e; }
        .empty { color: #888; font-style: italic; }
        .manifest {
            margin-top: 30px; padding: 12px; border: 2px solid #111;
            font-family: DejaVu Sans Mono, monospace; font-size: 9pt;
            background: #fafafa;
        }
        .manifest .label { font-weight: bold; margin-bottom: 4px; font-family: DejaVu Sans, sans-serif; }
        .manifest .hash-value { word-break: break-all; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; text-align: center; color: #888; font-size: 8pt; }
    </style>
</head>
<body>

<div class="cover">
    <div class="title">COMPLIANCE BUNDLE</div>
    <div class="org">{{ config('app.name') }}</div>
    <div class="period">
        Period<br>
        <strong>{{ $from->format('F j, Y') }}</strong> &nbsp;→&nbsp; <strong>{{ $to->format('F j, Y') }}</strong>
    </div>
    <div class="stamp">
        Generated {{ $generated_at->format('Y-m-d H:i:s T') }}<br>
        Contents: {{ $totals['activity'] }} activity entries · {{ $totals['revocations'] }} revocations · {{ $totals['documents'] }} verifiable documents
    </div>
</div>

<h2>Section 1 — Activity Log</h2>
<div class="meta">{{ $totals['activity'] }} entries within the period, chronological order.</div>

@if ($activity->isEmpty())
    <p class="empty">No activity recorded in this range.</p>
@else
    <table>
        <thead>
            <tr>
                <th style="width:130px">When</th>
                <th style="width:80px">Actor</th>
                <th style="width:70px">Event</th>
                <th>Subject</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activity as $a)
                <tr>
                    <td>{{ optional($a->created_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ optional($a->causer)->name ?? 'system' }}</td>
                    <td>{{ $a->event ?? '—' }}</td>
                    <td class="serial">
                        {{ $a->subject_type ? class_basename($a->subject_type) : '—' }}
                        @if($a->subject_id) #{{ $a->subject_id }} @endif
                    </td>
                    <td>{{ $a->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="page-break"></div>

<h2>Section 2 — Revocation Register</h2>
<div class="meta">{{ $revocations['total'] }} revocations within the period.</div>

@if ($revocations['rows']->isEmpty())
    <p class="empty">No revocations in the selected range — register is clean.</p>
@else
    <table>
        <thead>
            <tr>
                <th style="width:120px">When</th>
                <th style="width:80px">Kind</th>
                <th style="width:100px">Serial</th>
                <th>Subject</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($revocations['rows'] as $r)
                <tr>
                    <td>{{ optional($r['when'])->format('Y-m-d H:i') }}</td>
                    <td>{{ $r['kind'] }}</td>
                    <td class="serial">{{ $r['serial'] }}</td>
                    <td>{{ $r['label'] }}</td>
                    <td>{{ $r['reason'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="page-break"></div>

<h2>Section 3 — Document Register (current state, all verifiable docs)</h2>
<div class="meta">{{ $totals['documents'] }} verifiable documents currently in the system, sorted by serial.</div>

<table>
    <thead>
        <tr>
            <th style="width:70px">Kind</th>
            <th style="width:110px">Serial</th>
            <th style="width:70px">Status</th>
            <th>Subject</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($documents as $d)
            <tr>
                <td>{{ $d['kind'] }}</td>
                <td class="serial">{{ $d['serial'] }}</td>
                <td>
                    <span class="status status-{{ $d['status'] }}">{{ strtoupper($d['status']) }}</span>
                </td>
                <td>{{ $d['label'] }}</td>
            </tr>
            <tr>
                <td colspan="4" class="hash">hash: {{ $d['hash'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="manifest">
    <div class="label">CHAIN-OF-CUSTODY MANIFEST</div>
    Generated {{ $generated_at->format('Y-m-d H:i:s T') }} covering {{ $from->toDateString() }} → {{ $to->toDateString() }}.<br>
    Contents: {{ $totals['activity'] }} activity entries, {{ $totals['revocations'] }} revocations, {{ $totals['documents'] }} documents.<br><br>
    <strong>HMAC-SHA256:</strong><br>
    <span class="hash-value">{{ $manifest }}</span><br><br>
    <small>Anyone holding this bundle and the originating APP_KEY can recompute this hash
    from the enumerated activity IDs + revoked serials + document hashes to prove the
    bundle has not been altered since generation.</small>
</div>

<div class="footer">{{ config('app.name') }} · Compliance Bundle · {{ $generated_at->format('Y-m-d') }}</div>

</body>
</html>
