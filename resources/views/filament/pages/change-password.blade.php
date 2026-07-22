<x-filament-panels::page>
    @if (auth()->user()->must_change_password)
        <div class="rounded-lg bg-warning-50 border border-warning-200 p-4 mb-6 text-sm">
            <strong>Password reset required.</strong>
            An administrator has flagged your account to require a password change before you continue.
        </div>
    @endif

    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-check">
            Update password
        </x-filament::button>
    </form>
</x-filament-panels::page>
