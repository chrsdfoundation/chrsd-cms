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
                Preview — {{ $preview['totals']['departments'] }} departments,
                {{ $preview['totals']['employees'] }} employees
            </x-slot>

            <div class="space-y-6">
                @foreach ($preview['departments'] as $dept)
                    <div>
                        <h3 class="font-semibold text-base flex items-center gap-2">
                            <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 rounded font-mono">{{ $dept->code }}</span>
                            {{ $dept->name }}
                            <span class="text-sm text-gray-500">— {{ $dept->employees->count() }} people</span>
                            @if ($dept->head)
                                <span class="text-sm text-gray-500">| Head: {{ $dept->head->full_name }}</span>
                            @endif
                        </h3>

                        @if ($dept->employees->isNotEmpty())
                            <table class="w-full mt-2 text-sm">
                                <thead class="text-left text-gray-500 border-b">
                                    <tr>
                                        <th class="py-1 w-40 font-mono text-xs">Serial</th>
                                        <th class="py-1">Name</th>
                                        <th class="py-1">Position</th>
                                        <th class="py-1">Email</th>
                                        <th class="py-1">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dept->employees as $e)
                                        <tr class="border-b border-gray-50">
                                            <td class="py-1 font-mono text-xs">{{ $e->serial_number }}</td>
                                            <td class="py-1">{{ $e->full_name }}</td>
                                            <td class="py-1 text-gray-600">{{ optional($e->position)->title ?? '—' }}</td>
                                            <td class="py-1 text-gray-600">{{ $e->email }}</td>
                                            <td class="py-1">
                                                <span class="text-xs px-2 py-0.5 rounded
                                                    @if($e->employee_status?->value === 'active') bg-success-100 text-success-800
                                                    @else bg-gray-100 text-gray-700 @endif">
                                                    {{ ucfirst($e->employee_status?->value ?? '—') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm text-gray-500 mt-1 italic">No employees.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
