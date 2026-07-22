<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $letter->subject }} — {{ $letter->serial_number }}</title>
<style>
/*
 * CHRSD Official Letter — Browsershot (Chromium) template.
 *
 * Margins and per-page header/footer are handled entirely by Puppeteer:
 *   margin.top=190px  → header image fills the top margin on every page
 *   margin.bottom=160px → footer image fills the bottom margin on every page
 *   margin.left/right=60px → content side gutters
 * No @page CSS margin and no #letterhead div needed.
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
 * Watermark rendered behind every page's content area.
 *
 * `position: fixed` — Puppeteer / Chromium's PDF renderer repaints fixed
 * elements on every printed page, so the same watermark appears on page 1,
 * page 2, page N without needing a template-page-content dance. It sits at
 * z-index:-1 so body text, tables, and the meta strip paint over it.
 *
 * `-webkit-print-color-adjust: exact` — Puppeteer strips background images
 * from print output unless we force color adjustment.
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

/* ── Reference line ── */
.ref-line {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 12px;
    font-size: 12px;
    color: #555;
}
.ref-line .serial {
    font-family: 'Courier New', Courier, monospace;
    font-weight: bold;
    color: #0f3b1c;
}

/* ── Recipient block ── */
.recipient {
    margin-bottom: 12px;
    line-height: 1.5;
    font-size: 14px;
}
.recipient .name {
    font-weight: bold;
    color: #0f3b1c;
}

/* ── Subject ── */
.subject-line {
    margin-bottom: 16px;
    font-weight: bold;
    color: #0f3b1c;
}
.subject-line .subject-text {
    font-weight: normal;
    color: #1d1d1d;
}

/* ── Body content from RichEditor ── */
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
.body table,
.body table.letter-body-table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0 12px;
    font-size: 13px;
}
.body table th,
.body table.letter-body-table th {
    background: #f4f4f5;
    padding: 6px 10px;
    text-align: left;
    border-bottom: 1px solid #d4d4d8;
    border: 1px solid #d4d4d8;
    font-weight: 700;
    color: #0f3b1c;
}
.body table td,
.body table.letter-body-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #e5e7eb;
    border: 1px solid #e5e7eb;
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
.signature .sig-org {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

/* ── Verification meta strip ── */
.meta {
    margin-top: 24px;
    border-top: 1px solid #c8962a;
    padding-top: 8px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    break-inside: avoid;
    font-size: 10px;
    color: #6b7280;
    line-height: 1.4;
}
.meta .ref-info strong { color: #0f3b1c; }
.meta .ref-info .url {
    font-family: 'Courier New', Courier, monospace;
    font-size: 9px;
    color: #0f3b1c;
    word-break: break-all;
}
.meta .qr-code {
    flex-shrink: 0;
    margin-left: 16px;
}
.meta .qr-code svg {
    display: block;
    width: 64px;
    height: 64px;
}
</style>
</head>
<body>

@if (! empty($watermark_uri))
    <div class="letter-watermark" aria-hidden="true"></div>
@endif

<div class="ref-line">
    <span>Ref: <span class="serial">{{ $letter->serial_number }}</span></span>
    <span>{{ optional($letter->dated_on ?? $letter->created_at)->format('F j, Y') }}</span>
</div>

@if ($letter->recipient_name)
<div class="recipient">
    <div class="name">{{ $letter->recipient_name }}</div>
    @if ($letter->recipient_title)<div>{{ $letter->recipient_title }}</div>@endif
    @if ($letter->recipient_address)<div>{!! nl2br(e($letter->recipient_address)) !!}</div>@endif
</div>
@endif

@if ($letter->subject)
<div class="subject-line">Subject: <span class="subject-text">{{ $letter->subject }}</span></div>
@endif

<div class="body">{!! $body_html ?? $letter->body !!}</div>

@php
    // Determine the effective signatory for display.
    // Priority: Employee signatory > new Author > legacy Employee author.
    $displayName  = null;
    $displayTitle = null;
    $displayOrg   = null;
    // $signature_image_uri is resolved centrally in LetterGeneratorService
    // (per-letter Spatie upload → brand-kit fallback). Legacy Author models
    // may still provide their own base64 method — check it as a last resort.
    $sigImageUri  = $signature_image_uri ?? '';

    if (isset($signatory) && $signatory) {
        $displayName  = $signatory->full_name;
        $displayTitle = optional($signatory->position)->title;
        $displayOrg   = null;
    } elseif (isset($letterAuthor) && $letterAuthor) {
        $displayName  = $letterAuthor->name;
        $displayTitle = $letterAuthor->designation;
        $displayOrg   = $letterAuthor->organization;
        if ($sigImageUri === '' && method_exists($letterAuthor, 'signatureBase64')) {
            $sigImageUri = (string) $letterAuthor->signatureBase64();
        }
    } elseif (isset($resolvedAuthor) && $resolvedAuthor) {
        // Legacy Employee author
        $displayName  = $resolvedAuthor->full_name ?? null;
        $displayTitle = optional($resolvedAuthor->position ?? null)->title ?? null;
    }
@endphp

@if ($displayName)
<div class="signature">
    @if ($sigImageUri !== '')
        <img class="sig-image" src="{{ $sigImageUri }}" alt="Signature">
    @endif
    <div class="sig-name">{{ $displayName }}</div>
    @if ($displayTitle)<div class="sig-title">{{ $displayTitle }}</div>@endif
    @if ($displayOrg)<div class="sig-org">{{ $displayOrg }}</div>@endif
</div>
@endif

<div class="meta">
    <div class="ref-info">
        To verify this document scan the QR code or visit:<br>
        <span class="url">{{ $verify_url }}</span><br>
        Serial: <strong>{{ $letter->serial_number }}</strong>
    </div>
    <div class="qr-code">
        @if(!empty($qr_svg)){!! $qr_svg !!}@endif
    </div>
</div>

</body>
</html>
