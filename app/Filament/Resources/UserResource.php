<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()->revealable()
                    ->rule(PasswordRule::default())
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('At least 12 characters; mixed case + digit + symbol. Leave blank on edit to keep the current password.'),
                Forms\Components\Toggle::make('must_change_password')
                    ->label('Force change on next login')
                    ->helperText('Ticked automatically after an admin resets the password.')
                    ->default(false),
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->helperText('Grants admin-panel access.'),
                Forms\Components\Select::make('organizations')
                    ->multiple()
                    ->relationship('organizations', 'name')
                    ->preload()
                    ->helperText('Every panel user needs at least one organization.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()->color('info')->separator(',')->label('Roles'),
                Tables\Columns\IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')->falseColor('warning'),
                Tables\Columns\IconColumn::make('must_change_password')
                    ->label('Must change')
                    ->boolean()
                    ->trueColor('warning')->falseColor('gray'),
                Tables\Columns\TextColumn::make('password_changed_at')
                    ->label('Password age')->since()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')->preload(),
                Tables\Filters\TernaryFilter::make('must_change_password'),
            ])
            ->actions([
                self::forceResetAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $r) => $r->id !== Auth::id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    /**
     * Row action (table): flip `must_change_password` + optionally regenerate
     * the password + optionally send a password-reset email. HR routinely does
     * this when an employee reports a compromised or lost credential.
     */
    public static function forceResetAction(): Tables\Actions\Action
    {
        return self::applyForceResetShape(Tables\Actions\Action::make('force_reset'));
    }

    /** Header-scoped equivalent for View/Edit page toolbars. */
    public static function forceResetHeaderAction(): Action
    {
        return self::applyForceResetShape(Action::make('force_reset'));
    }

    /**
     * @template T of Tables\Actions\Action|\Filament\Actions\Action
     *
     * @param  T  $action
     * @return T
     */
    private static function applyForceResetShape($action)
    {
        return $action
            ->label('Reset password')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->requiresConfirmation()
            ->form([
                Forms\Components\Toggle::make('email_link')
                    ->label('Also email a password-reset link')
                    ->default(true)
                    ->helperText('Leave off if the operator will hand-deliver the temporary password.'),
                Forms\Components\Toggle::make('random_temp')
                    ->label('Generate a random temporary password')
                    ->default(true)
                    ->helperText('Shown once in a notification. User is forced to change it on next login.'),
            ])
            ->action(function (User $record, array $data) {
                $notes = [];

                if (! empty($data['random_temp'])) {
                    $temp = Str::password(16);
                    $record->forceFill([
                        'password' => Hash::make($temp),
                        'password_changed_at' => now(),
                    ])->save();

                    Notification::make()
                        ->warning()
                        ->title('Temporary password (shown once)')
                        ->body("Password for {$record->email}: **{$temp}**")
                        ->persistent()
                        ->duration(null)
                        ->send();

                    $notes[] = 'Temporary password minted.';
                }

                $record->forceFill(['must_change_password' => true])->save();
                $notes[] = 'User will be forced to change on next login.';

                if (! empty($data['email_link'])) {
                    Password::sendResetLink(['email' => $record->email]);
                    $notes[] = 'Reset link emailed.';
                }

                Notification::make()->success()->title('Password reset complete')
                    ->body(implode(' ', $notes))->send();
            })
            ->visible(fn () => Auth::user()?->hasRole('super_admin') ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
