<x-layouts.print :title="`Certificate: {$certificate->serial_number}`">
    <style>
        {{ file_get_contents(resource_path('css/print/certificate.css')) }}
    </style>

    <div class="certificate">
        @if($backgroundImage ?? null)
            <img src="{{ $backgroundImage }}" alt="" class="certificate-background">
        @endif

        <div class="watermark">
            {!! $watermarkSvg ?? '' !!}
        </div>

        <div class="certificate-content">
            <h1>Certificate of Achievement</h1>
            <p>This is to certify that</p>
            <h2 style="font-size: 2cm; margin: 1cm 0;">{{ $certificate->recipient_name ?? $certificate->employee?->full_name }}</h2>
            <p>Has successfully completed the requirements for</p>
            <p style="font-size: 1.2cm; font-weight: bold;">{{ $certificate->type->name ?? $certificate->purpose }}</p>

            @if($signatory ?? null)
                <div class="signature-block">
                    <div class="signature">
                        @if($signatureImage ?? null)
                            <img src="{{ $signatureImage }}" alt="Signature" style="width: 30mm; height: 20mm;">
                        @else
                            <div class="signature-line"></div>
                        @endif
                        <div style="font-size: 9pt; margin-top: 2mm;">
                            <strong>{{ $signatory->full_name ?? '' }}</strong><br>
                            {{ $signatory->position->title ?? '' }}
                        </div>
                    </div>
                </div>
            @endif

            @if($qr_svg ?? null)
                <div class="qr-code">
                    {!! $qr_svg !!}
                </div>
            @endif
        </div>
    </div>
</x-layouts.print>
