<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
/*
 * CHRSD Official Letter — Browsershot shell for DB-template letters.
 * Margins and per-page letterhead handled by Puppeteer headerTemplate/footerTemplate.
 */

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html, body {
    background: transparent;
}

body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #1d1d1d;
    width: 100%;
    position: relative;
}

/*
 * Watermark — matches default-browsershot: repainted on every page by
 * Chromium because it's position:fixed, needs print-color-adjust to
 * survive PDF export.
 */
@if (! empty($watermark_uri))
.letter-watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 500px;
    height: 500px;
    transform: translate(-50%, -50%);
    background: url('{{ $watermark_uri }}') center center / contain no-repeat;
    opacity: 0.14;
    z-index: -1;
    pointer-events: none;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
@endif

/* Body content from TemplateRenderer */
.body {
    text-align: justify;
    margin-bottom: 8px;
}
.body p       { margin-bottom: 10px; }
.body h1      { font-size: 20px; color: #0f3b1c; margin-bottom: 8px; }
.body h2      { font-size: 17px; color: #0f3b1c; margin-bottom: 6px; }
.body h3      { font-size: 15px; font-weight: bold; color: #0f3b1c; margin-bottom: 5px; }
.body strong  { color: #163e22; }
.body em      { color: #4b5563; }
.body a       { color: #1c6d3a; text-decoration: none; }
.body ul,
.body ol      { margin: 0 0 10px 20px; }
.body li      { margin-bottom: 4px; }
.body blockquote {
    border-left: 3px solid #c8962a;
    padding-left: 14px;
    color: #4b5563;
    margin: 10px 0;
}
.body table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0 12px;
    font-size: 13px;
}
.body table th {
    background: #f4f4f5;
    padding: 6px 10px;
    text-align: left;
    border-bottom: 1px solid #d4d4d8;
}
.body table td {
    padding: 6px 10px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

/* ── Signature block ── */
.signature {
    margin-top: 40px;
    break-inside: avoid;
}
.signature .sig-image {
    display: block;
    max-height: 70px;
    max-width: 200px;
    width: auto;
    height: auto;
    margin-bottom: 4px;
    object-fit: contain;
}
.signature .sig-name {
    display: inline-block;
    min-width: 160px;
    font-weight: bold;
    color: #0f3b1c;
    border-top: 1px solid #0f3b1c;
    padding-top: 5px;
}
.signature .sig-title {
    font-size: 12px;
    color: #186d3b;
    margin-top: 3px;
}

/* Verification meta strip */
.meta {
    margin-top: 24px;
    border-top: 1px solid #c8962a;
    padding-top: 6px;
    display: flex;
    justify-content: space-between;
    /* align-items:flex-start pins ref/verify just under the gold rule instead
     * of dropping them to the bottom of the taller QR chip. */
    align-items: flex-start;
    break-inside: avoid;
    font-size: 10px;
    color: #6b7280;
    line-height: 1.4;
}
.meta .ref-info {
    padding-top: 2px;
}
.meta .ref-info strong { color: #0f3b1c; }
.meta .ref-info .url {
    font-family: 'Courier New', Courier, monospace;
    font-size: 9px;
    color: #0f3b1c;
    word-break: break-all;
}
.meta .qr-col {
    flex-shrink: 0;
    margin-left: 16px;
    padding: 3px;
    background: #ffffff;
    border: 1px solid #d4d4d8;
    border-radius: 3px;
    line-height: 0; /* remove baseline gap under inline SVG */
}
/*
 * QR sizing — SVG scales cleanly to whatever we set. Forced with !important
 * so nothing inline can shrink it below the printable size.
 */
.meta .qr-col img,
.meta .qr-col svg {
    display: block;
    width: 96px !important;
    height: 96px !important;
}
</style>
</head>
<body>

@if (! empty($watermark_uri))
    <div class="letter-watermark" aria-hidden="true"></div>
@endif

<div class="body">{!! $bodyHtml !!}</div>

<div class="meta">
    <div class="ref-info">
        @if (!empty($letter_reference))
            Ref: <strong>{{ $letter_reference }}</strong><br>
        @endif
        @if (!empty($verification_url))
            Verify at: <span class="url">{{ $verification_url }}</span>
        @endif
    </div>
    <div class="qr-col">
        @if (!empty($qr_raw)){!! $qr_raw !!}@endif
    </div>
</div>

</body>
</html>
