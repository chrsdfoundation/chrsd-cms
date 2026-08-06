<x-layouts.print :title="`ID Card: {$idCard->serial_number}`">
    <style>
        {{ file_get_contents(resource_path('css/print/id-card.css')) }}
    </style>

    {{-- Render combined A4 sheet with both sides --}}
    <div class="sheet">
        {{-- Front side --}}
        <div class="id-card">
            <div class="id-card-content">
                <div class="id-card-header">
                    <img src="{{ $logoUrl }}" alt="Logo" style="width: 15mm;">
                </div>

                @if($photoUrl ?? null)
                    <img src="{{ $photoUrl }}" alt="Photo" class="id-card-photo">
                @endif

                <div class="id-card-name">{{ $idCard->displayName() }}</div>

                <div class="id-card-fields">
                    <div><strong>ID No:</strong> {{ $idCard->serial_number }}</div>
                    <div><strong>Blood Group:</strong> {{ $idCard->blood_group ?? 'N/A' }}</div>
                    @if($idCard->valid_from ?? null)
                        <div><strong>Valid from:</strong> {{ $idCard->valid_from->format('d M Y') }}</div>
                    @endif
                    @if($idCard->valid_until ?? null)
                        <div><strong>Valid until:</strong> {{ $idCard->valid_until->format('d M Y') }}</div>
                    @endif
                </div>

                <div class="id-card-footer">
                    {{ config('app.name') }}
                </div>
            </div>
        </div>

        {{-- Back side --}}
        <div class="id-card">
            <div class="id-card-content">
                <div style="text-align: center; font-size: 6pt;">
                    <strong>EMERGENCY CONTACT</strong>
                </div>

                <div style="font-size: 6pt; line-height: 1.4;">
                    {{-- Back content --}}
                    <p>Contact Information & Return Instructions</p>
                </div>

                @if($qr_svg ?? null)
                    <div style="text-align: center;">
                        <div style="width: 15mm; height: 15mm; margin: 2mm auto;">
                            {!! $qr_svg !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.print>
