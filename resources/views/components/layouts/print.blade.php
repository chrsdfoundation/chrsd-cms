<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'Print' }}</title>

    {{-- Print-specific meta tags --}}
    <meta name="color-scheme" content="light">

    {{-- Base print CSS --}}
    <link rel="stylesheet" href="{{ asset('css/print/common.css') }}">

    {{-- Type-specific CSS (certificate.css, letter.css, etc.) --}}
    @isset($printCss)
        <link rel="stylesheet" href="{{ asset($printCss) }}">
    @endisset

    {{-- Inline print styles for overrides --}}
    <style>
        {!! $inlineStyles ?? '' !!}
    </style>
</head>
<body>
    {{-- Print content --}}
    {{ $slot }}

    {{-- Print button (shown on screen, hidden in print) --}}
    <div class="print-action-bar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
