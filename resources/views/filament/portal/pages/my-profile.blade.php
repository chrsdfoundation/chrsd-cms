<x-filament-panels::page>
    @php $e = $employee; @endphp

    <x-filament::section>
        <x-slot name="heading">Identity</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            <div class="md:col-span-2">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-gray-500">Serial</dt>
                    <dd class="font-mono">{{ $e->serial_number }}</dd>

                    <dt class="text-gray-500">Full name</dt>
                    <dd class="font-semibold">{{ $e->full_name }}</dd>

                    <dt class="text-gray-500">Email</dt>
                    <dd>{{ $e->email }}</dd>

                    <dt class="text-gray-500">Mobile</dt>
                    <dd>{{ $e->mobile ?? '—' }}</dd>

                    <dt class="text-gray-500">Department</dt>
                    <dd>{{ optional($e->department)->name ?? '—' }}</dd>

                    <dt class="text-gray-500">Position</dt>
                    <dd>{{ optional($e->position)->title ?? '—' }}</dd>

                    <dt class="text-gray-500">Supervisor</dt>
                    <dd>{{ optional($e->supervisor)->full_name ?? '—' }}</dd>

                    <dt class="text-gray-500">Employment type</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $e->employment_type?->value ?? '—')) }}</dd>

                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="inline-block px-2 py-0.5 text-xs rounded
                            @if($e->employee_status?->value === 'active') bg-success-100 text-success-800
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($e->employee_status?->value ?? '—') }}
                        </span>
                    </dd>

                    <dt class="text-gray-500">Hired</dt>
                    <dd>{{ optional($e->hired_at)->format('F j, Y') ?? '—' }}</dd>
                </dl>
            </div>

            <div class="flex flex-col items-center gap-2">
                @if ($e->hasMedia('avatar'))
                    <img src="{{ $e->getFirstMediaUrl('avatar', 'thumb') }}"
                         class="w-32 h-32 rounded-full object-cover border border-gray-200"
                         alt="Avatar">
                @else
                    <div class="w-32 h-32 rounded-full bg-gray-100 border border-gray-200
                                flex items-center justify-center text-gray-400 text-4xl">
                        {{ strtoupper(substr($e->first_name, 0, 1) . substr($e->last_name, 0, 1)) }}
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Employment History</x-slot>

        @if ($e->history->isEmpty())
            <p class="text-sm text-gray-500 italic">No events recorded.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 border-b">
                    <tr>
                        <th class="py-1 w-32">Date</th>
                        <th class="py-1 w-40">Event</th>
                        <th class="py-1">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($e->history->sortByDesc('occurred_on') as $ev)
                        <tr class="border-b border-gray-50">
                            <td class="py-1">{{ optional($ev->occurred_on)->format('Y-m-d') }}</td>
                            <td class="py-1">
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100">
                                    {{ $ev->event_type->getLabel() }}
                                </span>
                            </td>
                            <td class="py-1">
                                {{ $ev->summary }}
                                @if ($ev->notes)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $ev->notes }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
