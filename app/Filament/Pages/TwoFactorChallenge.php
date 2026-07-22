<?php

namespace App\Filament\Pages;

use App\Services\Auth\TotpService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallenge extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'two-factor-challenge';

    protected static ?string $title = 'Two-Factor Challenge';

    protected static string $view = 'filament.pages.two-factor-challenge';

    /** Hide from navigation — users only reach this via redirect. */
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        // Anyone who somehow lands here without 2FA needed is bounced away.
        $user = Auth::user();
        abort_unless($user, 403);

        if (! $user->hasEnabledTwoFactor()) {
            redirect('/admin')->send();

            return;
        }

        if (session()->has('two_factor_passed_at')) {
            redirect('/admin')->send();

            return;
        }

        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('6-digit code or 10-char recovery code')
                    ->required()
                    ->autofocus()
                    ->extraInputAttributes(['inputmode' => 'text', 'autocomplete' => 'one-time-code']),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $code = trim($data['code']);

        $user = Auth::user();
        $svc = app(TotpService::class);

        // Try TOTP first — the common case.
        if ($svc->verify($user->two_factor_secret, $code)) {
            $this->passChallenge();

            return;
        }

        // Fall back to recovery codes — single-use.
        $recovery = $user->two_factor_recovery_codes ?? [];
        $normalized = strtolower(preg_replace('/\s+/', '', $code));

        if (in_array($normalized, $recovery, true)) {
            // Consume the code so it can't be used again.
            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_diff($recovery, [$normalized])),
            ])->save();

            $this->passChallenge('Recovery code accepted — one fewer remaining.');

            return;
        }

        Notification::make()->danger()
            ->title('Code did not match')
            ->body('Try the current 6-digit code from your app, or a fresh recovery code.')
            ->send();
    }

    protected function passChallenge(?string $message = null): void
    {
        session(['two_factor_passed_at' => now()->timestamp]);

        Notification::make()->success()
            ->title('Two-factor challenge passed')
            ->body($message)
            ->send();

        redirect('/admin')->send();
    }
}
