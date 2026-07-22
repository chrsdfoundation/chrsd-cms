<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex gap-3">
        <x-filament::button wire:click="exportPdf" icon="heroicon-o-arrow-down-tray">
            Export Compliance Bundle (PDF)
        </x-filament::button>
    </div>

    @if (! empty($preview))
        <x-filament::section>
            <x-slot name="heading">
                Bundle preview — {{ $preview['from']->toDateString() }} to {{ $preview['to']->toDateString() }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="p-4 rounded-lg bg-info-50 border border-info-200">
                    <div class="text-xs uppercase text-info-700">Activity entries</div>
                    <div class="text-2xl font-bold">{{ $preview['totals']['activity'] }}</div>
                </div>
                <div class="p-4 rounded-lg bg-danger-50 border border-danger-200">
                    <div class="text-xs uppercase text-danger-700">Revocations</div>
                    <div class="text-2xl font-bold">{{ $preview['totals']['revocations'] }}</div>
                </div>
                <div class="p-4 rounded-lg bg-success-50 border border-success-200">
                    <div class="text-xs uppercase text-success-700">Verifiable documents</div>
                    <div class="text-2xl font-bold">{{ $preview['totals']['documents'] }}</div>
                </div>
            </div>

            <div class="p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="text-xs uppercase text-gray-600 mb-1">Chain-of-custody manifest (HMAC-SHA256)</div>
                <code class="font-mono text-xs break-all block">{{ $preview['manifest'] }}</code>
                <p class="text-xs text-gray-500 mt-2">
                    The exported PDF will include this hash on its final page. Anyone holding both the bundle
                    and the APP_KEY can recompute it to prove the file has not been altered.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
