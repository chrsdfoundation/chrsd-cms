<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiTokenResource\Pages;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApiTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'API Tokens';

    protected static ?int $navigationSort = 60;

    /** Super_admin only — tokens grant privileged API access. */
    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    /**
     * The `tokenable_type` on Sanctum's table is polymorphic; we only ever
     * mint User tokens in this app so the resource scopes to those.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tokenable_type', User::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Token')->columns(2)->schema([
                Forms\Components\Select::make('tokenable_id')
                    ->label('User')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('name')
                    ->label('Token label')->required()->maxLength(255)
                    ->helperText('Where will this token be used? e.g. "Payroll Kiosk 03", "Background-check API".'),
                Forms\Components\CheckboxList::make('abilities')
                    ->label('Abilities')
                    ->options([
                        'verify:read' => 'Verify documents (read)',
                        '*'           => 'All (super-token — use sparingly)',
                    ])
                    ->default(['verify:read'])
                    ->helperText('Currently, verify endpoints require `verify:read` or `*`.')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->native(false)
                    ->helperText('Leave blank for a non-expiring token.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tokenable.name')->label('User'),
                Tables\Columns\TextColumn::make('abilities')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->wrap(),
                Tables\Columns\TextColumn::make('last_used_at')->since()->placeholder('Never')->sortable(),
                Tables\Columns\TextColumn::make('last_used_ip')->label('Last IP')->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tokenable_id')
                    ->label('User')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->using(self::createUsing())])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->label('Revoke'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Revoke selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Mint the token via Sanctum so the plaintext value is available to show
     * the operator ONCE. We stash it in the session and read it back on the
     * next page so it survives the Livewire redirect.
     */
    protected static function createUsing(): \Closure
    {
        return function (array $data): PersonalAccessToken {
            /** @var User $user */
            $user = User::findOrFail($data['tokenable_id']);

            $abilities = array_values($data['abilities'] ?? ['verify:read']);
            $expiresAt = ! empty($data['expires_at'])
                ? \Illuminate\Support\Carbon::parse($data['expires_at'])
                : null;

            $newToken = $user->createToken($data['name'], $abilities, $expiresAt);
            $plain = $newToken->plainTextToken;

            // Show it in a persistent Filament notification. This is the ONLY
            // time the operator will ever see the plaintext.
            Notification::make()
                ->success()
                ->title('Token minted — copy it now')
                ->body('This is the only time the plaintext will be shown: **' . $plain . '**')
                ->persistent()
                ->duration(null)
                ->send();

            return $newToken->accessToken;
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiTokens::route('/'),
            'view'  => Pages\ViewApiToken::route('/{record}'),
        ];
    }
}
