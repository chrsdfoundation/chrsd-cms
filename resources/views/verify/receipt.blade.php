<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Money Receipt Verification — CHRSD Foundation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #14342B;
            --ink-2: #1f4a3e;
            --paper: #FBFAF6;
            --brass: #B5894E;
            --brass-soft: #cdaa78;
            --rule: #D8D3C4;
            --muted: #5A5550;
            --bg: #0d211c;
            --ok: #1f6b4a;
            --danger: #9b3025;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background:
                radial-gradient(900px 500px at 12% -8%, #16413444 0%, transparent 60%),
                var(--bg);
            color: #eceae3;
            min-height: 100vh;
            padding: 40px 20px 60px;
            -webkit-font-smoothing: antialiased;
        }
        .wrap { max-width: 640px; margin: 0 auto; }

        .app-head {
            display: flex; align-items: center; gap: 14px;
            padding-bottom: 22px; margin-bottom: 28px;
            border-bottom: 1px solid #ffffff1a;
        }
        .app-head .mark {
            width: 44px; height: 44px; border-radius: 50%;
            border: 1.5px solid var(--brass-soft);
            display: grid; place-items: center;
            font-family: 'Spectral', serif; font-weight: 700;
            color: var(--brass-soft); font-size: 21px;
        }
        .app-head h1 {
            font-family: 'Spectral', serif; font-weight: 600;
            font-size: 20px; margin: 0; color: #f2efe6;
        }
        .app-head p { margin: 3px 0 0; font-size: 12.5px; color: #aebdb2; }

        .banner {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 18px; border-radius: 12px;
            margin-bottom: 18px;
        }
        .banner .ico {
            width: 42px; height: 42px; border-radius: 50%;
            display: grid; place-items: center; flex: none;
        }
        .banner h2 {
            font-family: 'Spectral', serif; font-size: 17px;
            font-weight: 700; margin: 0 0 2px; letter-spacing: .2px;
        }
        .banner p { margin: 0; font-size: 13.5px; opacity: .9; }
        .banner.ok   { background: #163c2e; border: 1px solid #275b45; }
        .banner.ok .ico { background: #1f6b4a; color: #fff; }
        .banner.ok h2   { color: #ceecdb; }
        .banner.ok p    { color: #b8d4c4; }
        .banner.bad  { background: #3d1a15; border: 1px solid #6a2a22; }
        .banner.bad .ico { background: #9b3025; color: #fff; }
        .banner.bad h2   { color: #f5c9c2; }
        .banner.bad p    { color: #dfb5ad; }
        .banner.warn { background: #3c2f14; border: 1px solid #6a5324; }
        .banner.warn .ico { background: var(--brass); color: #23160a; }
        .banner.warn h2   { color: #f0dab1; }
        .banner.warn p    { color: #d5be96; }

        .card {
            background: var(--paper);
            color: #26221c;
            border-radius: 12px;
            padding: 28px 30px 26px;
            box-shadow: 0 24px 60px -24px #000a;
            position: relative; overflow: hidden;
        }
        .card::before {
            content: ""; position: absolute; inset: 0 0 auto 0; height: 5px;
            background: linear-gradient(90deg, var(--ink) 0 33%, var(--brass) 33% 66%, var(--ink) 66% 100%);
        }

        .head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 16px; border-bottom: 2px solid var(--ink);
            padding-bottom: 14px; margin-bottom: 20px;
        }
        .org { display: flex; gap: 12px; align-items: flex-start; }
        .logo {
            width: 48px; height: 48px; border-radius: 50%; flex: none;
            border: 2px solid var(--ink); background: #fff;
            display: grid; place-items: center; overflow: hidden;
        }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
        .org h3 {
            font-family: 'Spectral', serif; font-size: 18px; margin: 0;
            color: var(--ink); line-height: 1.15; font-weight: 700;
        }
        .org .sub {
            font-size: 11px; color: #6c655a; margin-top: 3px;
            line-height: 1.4; max-width: 240px;
        }
        .badge-title {
            text-align: right;
        }
        .badge-title .lbl {
            font-family: 'Spectral', serif; font-size: 15px; font-weight: 700;
            color: var(--ink); letter-spacing: 1.3px; text-transform: uppercase;
        }
        .badge-title .no {
            font-family: 'JetBrains Mono', monospace; font-size: 12px;
            color: #7a3b1f; margin-top: 4px; font-weight: 600;
        }

        dl { margin: 0; }
        .row {
            display: grid;
            grid-template-columns: minmax(120px, 34%) 1fr;
            gap: 14px;
            padding: 11px 0;
            border-bottom: 1px dashed var(--rule);
        }
        .row:last-of-type { border-bottom: 0; }
        dt {
            font-size: 11px; letter-spacing: .8px; text-transform: uppercase;
            color: #6c655a; font-weight: 700;
        }
        dd { margin: 0; color: #26221c; font-size: 14px; word-break: break-word; }
        dd.mono {
            font-family: 'JetBrains Mono', monospace; font-size: 12.5px;
            color: #4d4639; letter-spacing: -0.01em;
        }
        dd.strong { font-weight: 700; }
        dd.amount {
            font-family: 'JetBrains Mono', monospace; font-size: 20px;
            font-weight: 600; color: var(--ink);
        }

        .foot {
            margin-top: 22px; padding-top: 14px;
            border-top: 1px solid var(--rule);
            font-size: 10.5px; color: #9a9182;
            text-align: center; letter-spacing: .3px;
        }

        .verify-foot {
            text-align: center; color: #7fa393; font-size: 12px;
            margin-top: 24px; letter-spacing: .3px;
        }
    </style>
</head>
<body>
<div class="wrap">

    <div class="app-head">
        <div class="mark">C</div>
        <div>
            <h1>CHRSD Foundation</h1>
            <p>Money Receipt · Verification Portal</p>
        </div>
    </div>

    @php
        use App\Enums\VerificationStatus;
        $isFound   = ! empty($found);
        $isValid   = $isFound && $receipt->status === VerificationStatus::Valid;
        $isRevoked = $isFound && $receipt->status === VerificationStatus::Revoked;
    @endphp

    @if (! $isFound)

        <div class="banner bad" role="status">
            <span class="ico" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </span>
            <div>
                <h2>Receipt Not Found</h2>
                <p>No receipt matches <span style="font-family:'JetBrains Mono',monospace;">{{ $serial }}</span>. It may be tampered with, or the serial is invalid.</p>
            </div>
        </div>

    @else

        @if ($isValid)
            <div class="banner ok" role="status">
                <span class="ico" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <h2>Authentic Receipt Verified</h2>
                    <p>This money receipt was officially issued by CHRSD Foundation.</p>
                </div>
            </div>
        @elseif ($isRevoked)
            <div class="banner bad" role="status">
                <span class="ico" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <h2>Receipt Revoked</h2>
                    <p>This receipt has been revoked and is no longer valid.@if($receipt->revocation_reason) Reason: {{ $receipt->revocation_reason }}.@endif</p>
                </div>
            </div>
        @else
            <div class="banner warn" role="status">
                <span class="ico" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <h2>Receipt — Status: {{ $receipt->status?->value }}</h2>
                    <p>This receipt record exists in the system but is not currently valid.</p>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="head">
                <div class="org">
                    <div class="logo">
                        <img src="{{ asset('images/chrsd-watermark.svg') }}" alt="CHRSD">
                    </div>
                    <div>
                        <h3>CHRSD Foundation</h3>
                        <div class="sub">Centre for Humanitarian Research &amp; Social Development · Dhaka, Bangladesh</div>
                    </div>
                </div>
                <div class="badge-title">
                    <div class="lbl">Money Receipt</div>
                    <div class="no">{{ $receipt->serial_number }}</div>
                </div>
            </div>

            <dl>
                <div class="row">
                    <dt>Date of Receipt</dt>
                    <dd class="strong">{{ optional($receipt->receipt_date)->format('F j, Y') ?? '—' }}</dd>
                </div>
                <div class="row">
                    <dt>Received From</dt>
                    <dd class="strong">{{ $receipt->payer_name }}</dd>
                </div>
                <div class="row">
                    <dt>On Account Of</dt>
                    <dd>{{ $receipt->purpose }}</dd>
                </div>
                <div class="row">
                    <dt>Payment Method</dt>
                    <dd>
                        {{ $receipt->payment_method?->getLabel() }}
                        @if ($receipt->reference_no) · <span class="mono">{{ $receipt->reference_no }}</span>@endif
                    </dd>
                </div>
                <div class="row">
                    <dt>Amount</dt>
                    <dd class="amount">৳ {{ number_format((float) $receipt->amount, 2) }}</dd>
                </div>
                @if ($receipt->received_by)
                <div class="row">
                    <dt>Received By</dt>
                    <dd>{{ $receipt->received_by }}</dd>
                </div>
                @endif
                <div class="row">
                    <dt>Verification Hash</dt>
                    <dd class="mono">{{ $receipt->verification_hash }}</dd>
                </div>
            </dl>

            <div class="foot">
                This page contains only the data encoded in the receipt's QR code.
                Report discrepancies to <a href="mailto:info@chrsd.org" style="color:#4d4639;text-decoration:underline;">info@chrsd.org</a>.
            </div>
        </div>

    @endif

    <p class="verify-foot">Verification powered by CHRSD Foundation · localhost:8000</p>

</div>
</body>
</html>
