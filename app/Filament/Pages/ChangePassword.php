<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 65;

    protected static ?string $title = 'Change Password';

    protected static ?string $slug = 'change-password';

    protected static string $view = 'filament.pages.change-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current password')
                            ->password()->revealable()
                            ->required()
                            ->autocomplete('current-password'),
                        Forms\Components\TextInput::make('new_password')
                            ->label('New password')
                            ->password()->revealable()
                            ->required()
                            ->rule(Password::default())
                            ->helperText('At least 12 characters, mixing upper + lower case, numbers, and symbols.')
                            ->autocomplete('new-password'),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirm new password')
                            ->password()->revealable()
                            ->required()
                            ->same('new_password')
                            ->autocomplete('new-password'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            $this->addError('data.current_password', 'Current password does not match.');

            return;
        }

        if (Hash::check($data['new_password'], $user->password)) {
            $this->addError('data.new_password', 'New password must differ from the current one.');

            return;
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->save();

        // Reset the form so nothing lingers in Livewire state.
        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Password updated')
            ->body('You can keep working. Sign out on shared devices to force other sessions to re-authenticate.')
            ->send();

        if (! $user->must_change_password) {
            $this->redirect('/admin');
        }
    }
}
