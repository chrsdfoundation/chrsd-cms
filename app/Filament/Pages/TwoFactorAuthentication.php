<?php

namespace App\Filament\Pages;

use App\Services\Auth\TotpService;
use App\Services\Verification\QrCodeService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Milon\Barcode\DNS2D;

class TwoFactorAuthentication extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Two-Factor Authentication';

    protected static ?string $slug = 'two-factor-authentication';

    protected static string $view = 'filament.pages.two-factor-authentication';

    /** Ephemeral, pre-confirmation secret held in the Livewire component state. */
    public ?string $pendingSecret = null;

    public ?string $qrDataUri = null;

    /** Recovery codes shown ONCE after confirmation. */
    public ?array $recoveryCodes = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('6-digit code from your authenticator')
                    ->numeric()->length(6)
                    ->autofocus()
                    ->extraInputAttributes(['inputmode' => 'numeric', 'autocomplete' => 'one-time-code']),
            ])
            ->statePath('data');
    }

    public function isEnabled(): bool
    {
        return Auth::user()->hasEnabledTwoFactor();
    }

    public function beginEnroll(): void
    {
        $svc = app(TotpService::class);
        $this->pendingSecret = $svc->generateSecret();

        $uri = $svc->provisioningUri(
            secret: $this->pendingSecret,
            account: Auth::user()->email,
            issuer: config('app.name'),
        );

        $png = (new DNS2D)->getBarcodePNG($uri, 'QRCODE', 6, 6);
        $this->qrDataUri = 'data:image/png;base64,' . $png;
    }

    public function confirm(): void
    {
        $this->validate([
            'data.code' => ['required', 'digits:6'],
        ]);

        $svc = app(TotpService::class);
        abort_unless($this->pendingSecret, 422, 'No enrollment in progress.');

        if (! $svc->verify($this->pendingSecret, $this->data['code'])) {
            Notification::make()->danger()->title('Code did not match')
                ->body('Check your device clock or try the next 6 digits.')->send();
            return;
        }

        $recovery = $svc->generateRecoveryCodes();

        $user = Auth::user();
        $user->forceFill([
            'two_factor_secret'         => $this->pendingSecret,
            'two_factor_recovery_codes' => $recovery,
            'two_factor_confirmed_at'   => now(),
        ])->save();

        // Mark THIS session as already 2FA-passed so we don't immediately kick
        // the user out to the challenge page.
        session(['two_factor_passed_at' => now()->timestamp]);

        $this->pendingSecret = null;
        $this->qrDataUri     = null;
        $this->recoveryCodes = $recovery;
        $this->data['code']  = null;

        Notification::make()->success()->title('Two-factor authentication enabled')
            ->body('Store the recovery codes below in a safe place.')->send();
    }

    public function disable(): void
    {
        $user = Auth::user();
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        session()->forget('two_factor_passed_at');

        Notification::make()->warning()->title('Two-factor authentication disabled')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Disable 2FA')
                ->color('danger')
                ->icon('heroicon-o-shield-exclamation')
                ->requiresConfirmation()
                ->modalDescription('This removes the second factor from your account. Anyone with your password will be able to log in.')
                ->visible(fn () => $this->isEnabled())
                ->action('disable'),
        ];
    }
}
