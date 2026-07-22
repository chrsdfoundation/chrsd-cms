<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Department Roster — {{ $generated_at->format('Y-m-d') }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; }
        h1 { font-size: 16pt; margin: 0 0 4px; }
        .meta { color: #666; font-size: 9pt; margin-bottom: 18px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        h2 { font-size: 11pt; margin: 18px 0 4px; border-bottom: 1px solid #999; padding-bottom: 2px; }
        .dept-meta { color: #666; font-size: 9pt; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        th { text-align: left; background: #f3f4f6; padding: 4px 6px; border-bottom: 1px solid #ccc; }
        td { padding: 3px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; color: #555; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; text-align: center; color: #888; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Department Roster</h1>
    <div class="meta">
        Generated {{ $generated_at->format('F j, Y \a\t H:i') }} —
        {{ $totals['departments'] }} departments,
        {{ $totals['employees'] }} employees
    </div>

    @foreach ($departments as $dept)
        <h2>{{ $dept->code }} — {{ $dept->name }}</h2>
        <div class="dept-meta">
            {{ $dept->employees->count() }} employees
            @if ($dept->head) · Head: {{ $dept->head->full_name }} @endif
        </div>

        @if ($dept->employees->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th style="width:110px">Serial</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Email</th>
                        <th style="width:70px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dept->employees as $e)
                        <tr>
                            <td class="serial">{{ $e->serial_number }}</td>
                            <td>{{ $e->full_name }}</td>
                            <td>{{ optional($e->position)->title ?? '—' }}</td>
                            <td>{{ $e->email }}</td>
                            <td>{{ ucfirst($e->employee_status?->value ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#888;font-style:italic;">No employees assigned.</p>
        @endif
    @endforeach

    <div class="footer">Confidential — {{ config('app.name') }} · {{ $generated_at->format('Y-m-d') }}</div>
</body>
</html>
