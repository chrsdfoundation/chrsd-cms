<x-layouts.print :title="`Letter: {$letter->serial_number}`">
    <style>
        {{ file_get_contents(resource_path('css/print/letter.css')) }}
    </style>

    <div class="letter">
        @if($letterhead_uri ?? null)
            <div class="letter-letterhead">
                <img src="{{ $letterhead_uri }}" alt="Letterhead" style="width: 100%; height: auto;">
            </div>
        @endif

        <div class="letter-body">
            {{-- Recipient block --}}
            @if($letter->recipient_name ?? null)
                <p>
                    <strong>{{ $letter->recipient_name }}</strong><br>
                    {{ $letter->recipient_title ?? '' }}<br>
                    {{ $letter->recipient_address ?? '' }}
                </p>
            @endif

            {{-- Subject --}}
            @if($letter->subject ?? null)
                <p><strong>Subject: {{ $letter->subject }}</strong></p>
            @endif

            {{-- Body --}}
            <div class="letter-content">
                {!! $body_html ?? $letter->body ?? '' !!}
            </div>
        </div>

        {{-- Signature --}}
        <div class="letter-signature">
            @if(($signatureImage ?? null) && ($signatory ?? null))
                <img src="{{ $signatureImage }}" alt="Signature" class="signature-image">
            @else
                <div class="signature-line"></div>
            @endif

            <div class="signature-name">{{ $signatory->full_name ?? '' }}</div>
            <div class="signature-title">{{ $signatory->position->title ?? '' }}</div>
        </div>

        {{-- Footer with QR --}}
        <div class="letter-footer">
            @if($qr_svg ?? null)
                <div style="display: inline-block; width: 15mm; height: 15mm; margin-right: 0.5cm;">
                    {!! $qr_svg !!}
                </div>
            @endif
            <span style="vertical-align: middle;">
                Serial: {{ $letter->serial_number }}<br>
                Verify: {{ $verify_url ?? '' }}
            </span>
        </div>
    </div>
</x-layouts.print>
