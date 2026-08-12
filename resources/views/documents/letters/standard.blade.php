@extends('layouts.letterhead', [
    'headerImageUrl' => $headerImageUrl ?? asset('images/brand/letterhead-header.png'),
    'footerImageUrl' => $footerImageUrl ?? asset('images/brand/letterhead-footer.png'),
    'watermarkUrl' => $watermarkUrl ?? asset('images/brand/letterhead-watermark.png'),
    'qrCodePng' => $qrCodePng ?? null,
    'verifyUrl' => $verifyUrl ?? null,
    'footerText' => $footerText ?? null,
])

@section('title', $subject ?? 'Letter')

@section('content')
    <div class="letter-content">
        <!-- ===== DATE & REF ===== -->
        <div style="margin-bottom: 20px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <strong>Ref:</strong> {{ $serial_number ?? 'N/A' }}
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <strong>Dated:</strong> {{ $dateFormatted ?? date('F j, Y') }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- ===== RECIPIENT BLOCK ===== -->
        @if($recipientName || $recipientTitle || $recipientAddress)
            <div class="recipient-block space-bottom" style="margin-bottom: 20px;">
                @if($recipientName)
                    <div><strong>{{ $recipientName }}</strong></div>
                @endif
                @if($recipientTitle)
                    <div>{{ $recipientTitle }}</div>
                @endif
                @if($recipientAddress)
                    <div>{{ $recipientAddress }}</div>
                @endif
            </div>
        @endif

        <!-- ===== SUBJECT ===== -->
        @if($subject)
            <div style="margin-bottom: 15px; page-break-inside: avoid;">
                <strong>Subject: {{ $subject }}</strong>
            </div>
        @endif

        <!-- ===== SALUTATION ===== -->
        @if($salutation)
            <p style="margin-bottom: 15px;">{{ $salutation }}</p>
        @endif

        <!-- ===== LETTER BODY ===== -->
        <div class="letter-body-content" style="margin-bottom: 20px;">
            {!! $bodyHtml ?? '<p>Letter content goes here.</p>' !!}
        </div>

        <!-- ===== CLOSING ===== -->
        @if($closing)
            <p style="margin-bottom: 30px;">{{ $closing }}</p>
        @endif

        <!-- ===== SIGNATURE BLOCK ===== -->
        @if($signatoryName || $signatoryTitle || $signatoryImage)
            <div class="signature-block avoid-break">
                @if($signatoryImage)
                    <img src="{{ $signatoryImage }}" alt="Signature" class="signature-image" />
                @else
                    <div class="signature-line"></div>
                @endif

                @if($signatoryName)
                    <div class="signature-name">{{ $signatoryName }}</div>
                @endif

                @if($signatoryTitle)
                    <div class="signature-title">{{ $signatoryTitle }}</div>
                @endif
            </div>
        @endif

        <!-- ===== ENCLOSURES & CC ===== -->
        @if($enclosures || $ccList)
            <div style="margin-top: 30px; page-break-inside: avoid;">
                @if($enclosures)
                    <p><strong>Enclosures:</strong> {{ $enclosures }}</p>
                @endif
                @if($ccList)
                    <p><strong>cc:</strong> {{ $ccList }}</p>
                @endif
            </div>
        @endif
    </div>
@endsection
