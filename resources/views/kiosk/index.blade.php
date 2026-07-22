<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $app }} — Verification Kiosk</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header .brand { font-size: 1.5rem; font-weight: 700; letter-spacing: 2px; }
        header .subtitle { color: #94a3b8; font-size: 0.85rem; }

        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; }

        .card {
            width: 100%; max-width: 720px;
            background: #1e293b;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        h1.giant { font-size: 4.5rem; text-align: center; letter-spacing: 4px; }
        p.hint { color: #94a3b8; margin-bottom: 2rem; }

        form { display: flex; flex-direction: column; gap: 1.5rem; }
        label { display: block; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 0.5rem; }

        input[type=text], input[type=file] {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 1.25rem;
            font-family: ui-monospace, "SF Mono", "Courier New", monospace;
        }
        input[type=text]:focus, input[type=file]:focus { outline: none; border-color: #3b82f6; }

        .row-of-two { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1rem; }
        .row-of-two .or { color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; }

        button {
            padding: 1.25rem 2rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 1px;
        }
        button:hover { background: #2563eb; }
        button.ghost { background: transparent; border: 2px solid #475569; color: #cbd5e1; }
        button.ghost:hover { background: #1e293b; }

        /* Result banner */
        .banner {
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .banner.valid { background: #064e3b; border: 3px solid #10b981; }
        .banner.invalid, .banner.revoked { background: #7f1d1d; border: 3px solid #ef4444; }
        .banner.expired { background: #78350f; border: 3px solid #f59e0b; }
        .banner.missing { background: #334155; border: 3px solid #64748b; }
        .banner .icon { font-size: 4rem; margin-bottom: 1rem; }
        .banner .label { font-size: 3rem; font-weight: 800; letter-spacing: 4px; }

        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .details dl { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; }
        .details dt { color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; padding-top: 0.25rem; }
        .details dd { color: #f1f5f9; font-size: 1.1rem; font-weight: 500; }
        .details dd.mono { font-family: ui-monospace, "SF Mono", "Courier New", monospace; font-size: 0.95rem; }

        .qr-block { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
        .qr-block .qr { background: white; padding: 1rem; border-radius: 8px; }
        .qr-block .qr svg { display: block; }

        footer { padding: 1rem 2rem; border-top: 1px solid #1e293b; color: #64748b; font-size: 0.8rem; text-align: center; }

        .countdown { color: #94a3b8; font-size: 0.9rem; text-align: center; margin-top: 1rem; }
        .actions { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; }

        @media print {
            body { background: white; color: black; }
            header, footer, form, .actions, .countdown { display: none; }
            .card { box-shadow: none; background: white; border: 1px solid #ccc; }
            .banner.valid { background: #d1fae5; color: #064e3b; }
            .banner.revoked { background: #fee2e2; color: #7f1d1d; }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <div class="brand">{{ $app }}</div>
            <div class="subtitle">Verification Kiosk</div>
        </div>
        <div class="subtitle">{{ now()->format('l, F j, Y') }}</div>
    </header>

    <main>
        <div class="card">
            @if (! $attempted)
                <h1>Verify a document</h1>
                <p class="hint">Enter a serial number, paste a 64-character verification hash, or upload the issued PDF.</p>

                <form method="POST" action="{{ route('kiosk.verify') }}" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label for="query">Serial number or verification hash</label>
                        <input type="text" name="query" id="query"
                               placeholder="CERT-2026-000005  or  64-char hex"
                               autocomplete="off"
                               autofocus>
                    </div>

                    <div class="row-of-two">
                        <div style="height: 1px; background: #334155;"></div>
                        <span class="or">or</span>
                        <div style="height: 1px; background: #334155;"></div>
                    </div>

                    <div>
                        <label for="pdf">Upload the PDF file</label>
                        <input type="file" name="pdf" id="pdf" accept="application/pdf">
                    </div>

                    <button type="submit">Verify</button>
                </form>
            @else
                @php
                    $status = $snapshot['status'] ?? 'missing';
                    $iconMap = [
                        'valid'   => '✓',
                        'revoked' => '✕',
                        'expired' => '!',
                        'invalid' => '?',
                        'missing' => '?',
                    ];
                    $labelMap = [
                        'valid'   => 'VERIFIED',
                        'revoked' => 'REVOKED',
                        'expired' => 'EXPIRED',
                        'invalid' => 'INVALID',
                        'missing' => 'NOT FOUND',
                    ];
                @endphp

                <div class="banner {{ $status }}">
                    <div class="icon">{{ $iconMap[$status] ?? '?' }}</div>
                    <div class="label">{{ $labelMap[$status] ?? 'NOT FOUND' }}</div>
                </div>

                @if ($model)
                    <div class="details">
                        <dl>
                            <dt>Serial</dt><dd class="mono">{{ $snapshot['serial'] }}</dd>
                            <dt>Type</dt><dd>{{ $snapshot['kind'] }}</dd>
                            <dt>Issued</dt><dd>{{ $snapshot['issued_on'] ?? '—' }}</dd>
                            @if ($snapshot['valid_until'])
                                <dt>Valid until</dt><dd>{{ $snapshot['valid_until'] }}</dd>
                            @endif
                            @if ($snapshot['revoked_at'])
                                <dt>Revoked</dt><dd>{{ $snapshot['revoked_at'] }}</dd>
                                <dt>Reason</dt><dd>{{ $snapshot['revocation_reason'] }}</dd>
                            @endif
                        </dl>

                        <div class="qr-block">
                            @if ($qr_svg)
                                <div class="qr">{!! $qr_svg !!}</div>
                                <div class="subtitle">Scan to re-verify</div>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="hint" style="text-align:center; font-size: 1.1rem;">
                        No matching document was found for
                        @if ($query)
                            <code style="font-family: ui-monospace, monospace; color: #f1f5f9;">{{ $query }}</code>
                        @else
                            that PDF
                        @endif.
                        The document may not have been issued by this system, or the file may have been altered.
                    </p>
                @endif

                <div class="actions">
                    <a href="{{ route('kiosk.show') }}">
                        <button>Scan another</button>
                    </a>
                    <button type="button" class="ghost" onclick="window.print()">Print</button>
                </div>

                <p class="countdown">Auto-reset in <span id="cd">30</span>s</p>
                <script>
                    (function () {
                        let n = 30;
                        const el = document.getElementById('cd');
                        const tick = setInterval(() => {
                            n--;
                            if (el) el.textContent = String(Math.max(0, n));
                            if (n <= 0) {
                                clearInterval(tick);
                                location.href = @json(route('kiosk.show'));
                            }
                        }, 1000);
                    })();
                </script>
            @endif
        </div>
    </main>

    <footer>
        No data is retained on this kiosk. Every query is rate-limited and audited on our servers.
    </footer>
</body>
</html>
