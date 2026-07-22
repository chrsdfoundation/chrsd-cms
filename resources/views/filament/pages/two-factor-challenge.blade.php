<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Verify your identity</x-slot>

        <p class="text-sm text-gray-700 mb-6">
            Enter the current code from your authenticator app to continue into the admin panel.
            If you've lost access, use one of your one-time recovery codes.
        </p>

        <form wire:submit="submit" class="space-y-4 max-w-sm">
            {{ $this->form }}

            <x-filament::button type="submit" size="lg" icon="heroicon-o-shield-check">
                Continue
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
