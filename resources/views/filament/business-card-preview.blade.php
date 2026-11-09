<div class="business-card-preview" style="font-family: system-ui, sans-serif;">
    <style>
        @font-face { font-family: 'CardSans'; src: url('{{ asset('fonts/business-card/Montserrat-Regular.ttf') }}') format('truetype'); font-weight: 400; }
        @font-face { font-family: 'CardSans'; src: url('{{ asset('fonts/business-card/Montserrat-SemiBold.ttf') }}') format('truetype'); font-weight: 600; }
        @font-face { font-family: 'CardSerif'; src: url('{{ asset('fonts/business-card/CormorantGaramond-SemiBold.ttf') }}') format('truetype'); font-weight: 600; }
        .business-card-preview svg { display: block; width: 100%; height: auto; border: 1px solid rgba(0,0,0,.12); border-radius: 6px; background: #fff; }
        .business-card-preview .sides { display: grid; gap: 12px; grid-template-columns: 1fr; }
        @media (min-width: 900px) { .business-card-preview .sides { grid-template-columns: 1fr 1fr; } }
    </style>

    @if ($error)
        <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 text-sm text-danger-700">
            <strong>Preview unavailable.</strong> {{ $error }}
        </div>
    @else
        <div class="sides">
            <figure>
                <figcaption class="mb-1 text-xs font-semibold uppercase text-gray-500">Front</figcaption>
                {!! $front !!}
            </figure>
            <figure>
                <figcaption class="mb-1 text-xs font-semibold uppercase text-gray-500">Back</figcaption>
                {!! $back !!}
            </figure>
        </div>

        <div class="mt-4 text-sm">
            @php
                $errors = array_values(array_filter($issues, fn ($i) => $i['severity'] === 'error'));
                $warnings = array_values(array_filter($issues, fn ($i) => $i['severity'] === 'warning'));
            @endphp

            @if (empty($errors) && empty($warnings))
                <div class="rounded-lg border border-success-300 bg-success-50 p-3 text-success-700">
                    No layout or QR issues. QR: {{ $qr['modules'] }} modules, {{ number_format($qr['module_mm'], 2) }} mm per module, {{ $qr['profile'] }} profile. Spec {{ $specVersion }}.
                </div>
            @endif

            @foreach ($errors as $issue)
                <div class="mt-2 rounded-lg border border-danger-300 bg-danger-50 p-3 text-danger-700">
                    <strong>Error</strong> ({{ $issue['code'] }}). {{ $issue['message'] }}
                </div>
            @endforeach

            @foreach ($warnings as $issue)
                <div class="mt-2 rounded-lg border border-warning-300 bg-warning-50 p-3 text-warning-700">
                    <strong>Warning</strong> ({{ $issue['code'] }}). {{ $issue['message'] }}
                    @if ($issue['requires_acknowledgement'] ?? false)
                        <div class="mt-1 text-xs">Export needs the QR acknowledgement.</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
