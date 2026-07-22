<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section>
        <x-slot name="heading">
            Expiring within {{ $preview['window'] ?? 30 }} days
            — {{ $preview['expiring']->count() ?? 0 }} document{{ ($preview['expiring']->count() ?? 0) === 1 ? '' : 's' }}
        </x-slot>

        @if (empty($preview['expiring']) || $preview['expiring']->isEmpty())
            <p class="text-sm text-gray-500 italic">Nothing in the window — no renewal action needed.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 border-b">
                    <tr>
                        <th class="py-1">Kind</th>
                        <th class="py-1">Serial</th>
                        <th class="py-1">Subject</th>
                        <th class="py-1">Valid until</th>
                        <th class="py-1">Days left</th>
                        <th class="py-1">Last notice</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview['expiring'] as $r)
                        <tr class="border-b border-gray-50">
                            <td class="py-1">{{ $r['kind'] }}</td>
                            <td class="py-1 font-mono text-xs">{{ $r['serial'] }}</td>
                            <td class="py-1">{{ $r['subject'] }}</td>
                            <td class="py-1 whitespace-nowrap">{{ optional($r['valid_until'])->toFormattedDateString() }}</td>
                            <td class="py-1">
                                @if ($r['days_left'] !== null && $r['days_left'] <= 7)
                                    <span class="font-semibold text-amber-600">{{ $r['days_left'] }}</span>
                                @else
                                    {{ $r['days_left'] ?? '—' }}
                                @endif
                            </td>
                            <td class="py-1 text-gray-500">
                                {{ optional($r['expiry_notified_at'])->diffForHumans() ?? 'never' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    @if (! empty($preview['expired']) && $preview['expired']->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                Recently expired — {{ $preview['expired']->count() }} document{{ $preview['expired']->count() === 1 ? '' : 's' }}
            </x-slot>

            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 border-b">
                    <tr>
                        <th class="py-1">Kind</th>
                        <th class="py-1">Serial</th>
                        <th class="py-1">Subject</th>
                        <th class="py-1">Expired on</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview['expired'] as $r)
                        <tr class="border-b border-gray-50">
                            <td class="py-1">{{ $r['kind'] }}</td>
                            <td class="py-1 font-mono text-xs">{{ $r['serial'] }}</td>
                            <td class="py-1">{{ $r['subject'] }}</td>
                            <td class="py-1 whitespace-nowrap text-red-600">
                                {{ optional($r['valid_until'])->toFormattedDateString() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
