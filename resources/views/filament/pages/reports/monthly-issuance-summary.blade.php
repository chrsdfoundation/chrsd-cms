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
                {{ $preview['period']->format('F Y') }}
                — {{ $preview['totals']['certificates'] }} certs,
                {{ $preview['totals']['letters'] }} letters
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-sm mb-2">By certificate type</h4>
                    <table class="w-full text-sm">
                        <tbody>
                            @forelse ($preview['by_type'] as $code => $count)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1 font-mono text-xs">{{ $code }}</td>
                                    <td class="py-1 text-right">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-2 text-gray-500 italic">No certificates in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    <h4 class="font-semibold text-sm mb-2">By department</h4>
                    <table class="w-full text-sm">
                        <tbody>
                            @forelse ($preview['by_dept'] as $name => $count)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1">{{ $name }}</td>
                                    <td class="py-1 text-right">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-2 text-gray-500 italic">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>

        @if ($preview['certificates']->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Certificates issued</x-slot>
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-500 border-b">
                        <tr>
                            <th class="py-1">Serial</th>
                            <th class="py-1">Employee</th>
                            <th class="py-1">Type</th>
                            <th class="py-1">Purpose</th>
                            <th class="py-1">Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['certificates'] as $c)
                            <tr class="border-b border-gray-50">
                                <td class="py-1 font-mono text-xs">{{ $c->serial_number }}</td>
                                <td class="py-1">{{ optional($c->employee)->full_name }}</td>
                                <td class="py-1 font-mono text-xs">{{ optional($c->type)->code }}</td>
                                <td class="py-1 text-gray-600">{{ $c->purpose ?? '—' }}</td>
                                <td class="py-1 text-gray-600">{{ optional($c->issued_on)->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
