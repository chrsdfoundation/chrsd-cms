<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Service Record — {{ $employee->full_name }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; }
        .header { border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 16px; }
        h1 { font-size: 18pt; margin: 0; letter-spacing: 2px; }
        .subtitle { color: #666; font-size: 9pt; margin-top: 2px; }

        .top { display: table; width: 100%; margin-bottom: 20px; }
        .top .col { display: table-cell; vertical-align: top; }
        .top .col-main { width: 75%; padding-right: 16px; }
        .top .col-qr { width: 25%; text-align: right; }
        .top .col-qr img { width: 100px; height: 100px; }
        .top .col-qr .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #444; margin-top: 4px; }

        dl { margin: 0; }
        dt { color: #666; font-size: 9pt; float: left; clear: left; width: 90px; padding-top: 2px; }
        dd { margin: 0 0 4px 100px; padding-top: 2px; }

        h2 { font-size: 12pt; margin: 20px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        th { text-align: left; background: #f3f4f6; padding: 5px 6px; border-bottom: 1px solid #ccc; }
        td { padding: 4px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .event-type { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; }
        .empty { color: #888; font-style: italic; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; text-align: center; color: #888; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SERVICE RECORD</h1>
        <div class="subtitle">{{ config('app.name') }} · Generated {{ $generated_at->format('F j, Y H:i') }}</div>
    </div>

    <div class="top">
        <div class="col col-main">
            <dl>
                <dt>Name</dt>       <dd><strong>{{ $employee->full_name }}</strong></dd>
                <dt>Email</dt>      <dd>{{ $employee->email }}</dd>
                <dt>Department</dt> <dd>{{ optional($employee->department)->name ?? '—' }}</dd>
                <dt>Position</dt>   <dd>{{ optional($employee->position)->title ?? '—' }}</dd>
                <dt>Status</dt>     <dd>{{ ucfirst($employee->employee_status?->value ?? '—') }}</dd>
                <dt>Hired</dt>      <dd>{{ optional($employee->hired_at)->format('F j, Y') ?? '—' }}</dd>
                @if ($employee->ended_at)
                    <dt>Ended</dt>  <dd>{{ $employee->ended_at->format('F j, Y') }}</dd>
                @endif
            </dl>
        </div>
        <div class="col col-qr">
            <img src="{{ $qr_uri }}" alt="QR">
            <div class="serial">{{ $employee->serial_number }}</div>
        </div>
    </div>

    <h2>Employment history</h2>
    @if ($history->isEmpty())
        <p class="empty">No employment history recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:90px">Date</th>
                    <th style="width:110px">Event</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $ev)
                    <tr>
                        <td>{{ optional($ev->occurred_on)->format('Y-m-d') }}</td>
                        <td class="event-type">{{ $ev->event_type->getLabel() }}</td>
                        <td>
                            {{ $ev->summary }}
                            @if ($ev->notes) <br><span style="color:#666">{{ $ev->notes }}</span> @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Verify online: {{ $verify_url }} · Confidential
    </div>
</body>
</html>
