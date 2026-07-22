<x-filament-panels::page>
    <form wire:submit="lookup" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Look up
            </x-filament::button>
        </div>
    </form>

    @if ($resolved)
        @php
            $kind = class_basename($resolved);
            $statusValue = $resolved->status?->value;
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                {{ $kind }} — {{ $resolved->serial_number }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                            @if($statusValue === 'valid') bg-success-100 text-success-800
                            @elseif($statusValue === 'revoked') bg-danger-100 text-danger-800
                            @elseif($statusValue === 'expired') bg-warning-100 text-warning-800
                            @else bg-gray-200 text-gray-800 @endif">
                            {{ strtoupper($statusValue ?? '—') }}
                        </span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-gray-500">Serial</dt>
                        <dd class="font-mono">{{ $resolved->serial_number }}</dd>

                        <dt class="text-gray-500">Type</dt>
                        <dd>{{ $kind }}</dd>

                        <dt class="text-gray-500">Verification hash</dt>
                        <dd class="font-mono text-xs break-all">{{ $resolved->verification_hash }}</dd>

                        <dt class="text-gray-500">Verified at</dt>
                        <dd>{{ optional($resolved->verified_at)->toDayDateTimeString() ?? '—' }}</dd>

                        @if ($resolved->revoked_at)
                            <dt class="text-gray-500">Revoked at</dt>
                            <dd>{{ $resolved->revoked_at->toDayDateTimeString() }}</dd>

                            <dt class="text-gray-500">Revocation reason</dt>
                            <dd>{{ $resolved->revocation_reason }}</dd>
                        @endif

                        <dt class="text-gray-500">Public verify URL</dt>
                        <dd>
                            <a href="{{ $publicUrl }}" target="_blank"
                               class="text-primary-600 hover:underline break-all">
                                {{ $publicUrl }}
                            </a>
                        </dd>
                    </dl>
                </div>

                <div class="flex flex-col items-center gap-2 border border-gray-200 rounded-lg p-4">
                    {!! $qrSvg !!}
                    <p class="text-xs text-gray-500">Scan or click the URL to open the public verification page.</p>
                </div>
            </div>
        </x-filament::section>
    @elseif ($notFoundQuery)
        <x-filament::section>
            <x-slot name="heading">No match</x-slot>
            <p class="text-sm text-gray-700">
                Nothing found for
                <code class="font-mono bg-gray-100 px-1 rounded">{{ $notFoundQuery }}</code>.
                Try again with the full serial (e.g. <code class="font-mono">EMP-2026-000123</code>)
                or the 64-char hash from the QR code.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
