<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex gap-3">
        <x-filament::button wire:click="exportPdf" icon="heroicon-o-arrow-down-tray">
            Export PDF
        </x-filament::button>
    </div>

    @if (! empty($preview))
        <x-filament::section>
            <x-slot name="heading">
                {{ $preview['total'] }} revocations
                between {{ $preview['from']->toDateString() }}
                and {{ $preview['to']->toDateString() }}
            </x-slot>

            @if ($preview['rows']->isEmpty())
                <p class="text-sm text-gray-500 italic">No revocations in this range — audit register is clean.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-500 border-b">
                        <tr>
                            <th class="py-1">When</th>
                            <th class="py-1">Kind</th>
                            <th class="py-1">Serial</th>
                            <th class="py-1">Subject</th>
                            <th class="py-1">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $r)
                            <tr class="border-b border-gray-50">
                                <td class="py-1 whitespace-nowrap">{{ optional($r['when'])->toDateTimeString() }}</td>
                                <td class="py-1">{{ $r['kind'] }}</td>
                                <td class="py-1 font-mono text-xs">{{ $r['serial'] }}</td>
                                <td class="py-1">{{ $r['label'] }}</td>
                                <td class="py-1 text-gray-700">{{ $r['reason'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
