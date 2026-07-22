<x-filament-panels::page>
    @if ($this->isEnabled() && ! $recoveryCodes)

        <x-filament::section>
            <x-slot name="heading">Two-factor authentication is enabled</x-slot>

            <p class="text-sm text-gray-700">
                Your account requires a second factor at login. Use the "Disable 2FA" button
                above to remove it (not recommended for admin accounts).
            </p>
        </x-filament::section>

    @elseif ($recoveryCodes)

        <x-filament::section>
            <x-slot name="heading">Recovery codes — save these now</x-slot>

            <div class="rounded-lg bg-warning-50 border border-warning-200 p-4 mb-4 text-sm">
                <strong>These codes are shown only once.</strong> Each one is a single-use
                fallback for when you don't have access to your authenticator app.
                Store them in a password manager or print this page.
            </div>

            <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach ($recoveryCodes as $c)
                    <div class="bg-gray-100 rounded px-3 py-2">{{ $c }}</div>
                @endforeach
            </div>
        </x-filament::section>

    @elseif ($pendingSecret)

        <x-filament::section>
            <x-slot name="heading">Scan the QR with your authenticator</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col items-center gap-2">
                    <img src="{{ $qrDataUri }}" alt="QR" style="width:220px;height:220px;" class="border rounded">
                    <div class="text-xs text-gray-500">Cannot scan? Type the secret manually:</div>
                    <code class="font-mono text-xs break-all bg-gray-100 px-2 py-1 rounded">{{ $pendingSecret }}</code>
                </div>

                <form wire:submit="confirm" class="space-y-4">
                    {{ $this->form }}

                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Confirm and enable
                    </x-filament::button>

                    <p class="text-xs text-gray-500">
                        The secret is not saved to your account until you confirm with a valid code.
                    </p>
                </form>
            </div>
        </x-filament::section>

    @else

        <x-filament::section>
            <x-slot name="heading">Add a second factor to your account</x-slot>

            <p class="text-sm text-gray-700 mb-4">
                With 2FA enabled, logging in requires both your password and a 6-digit code
                from an authenticator app (Google Authenticator, Authy, 1Password, etc.).
            </p>

            <x-filament::button wire:click="beginEnroll" icon="heroicon-o-shield-check">
                Begin enrollment
            </x-filament::button>
        </x-filament::section>

    @endif
</x-filament-panels::page>
