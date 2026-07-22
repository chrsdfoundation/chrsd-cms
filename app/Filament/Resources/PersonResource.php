<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonResource\Pages;
use App\Filament\Resources\PersonResource\RelationManagers;
use App\Models\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $cluster = \App\Filament\Clusters\Crm::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'People';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'organization_name', 'email', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('organization_name')
                        ->label('Organisation (if applicable)')->maxLength(255),
                    Forms\Components\TextInput::make('tax_id')
                        ->label('Tax ID / National ID')->maxLength(64),
                    Forms\Components\TextInput::make('email')
                        ->email()->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()->maxLength(50),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)->inline(false),
                ]),

            Forms\Components\Section::make('Address')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('address_line')
                        ->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->maxLength(100),
                    Forms\Components\TextInput::make('country')->maxLength(100)->default('Bangladesh'),
                ]),

            Forms\Components\Section::make('Roles')
                ->description('Enable the role types this person needs. You can edit each profile in detail from the person\'s view page after saving.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('_is_donor')
                        ->label('Donor')->inline(false)->dehydrated(false)
                        ->helperText('Tracks donations, engagement tier, lifetime totals.'),
                    Forms\Components\Toggle::make('_is_volunteer')
                        ->label('Volunteer')->inline(false)->dehydrated(false)
                        ->helperText('Tracks skills, availability, active status.'),
                    Forms\Components\Toggle::make('_is_beneficiary')
                        ->label('Beneficiary')->inline(false)->dehydrated(false)
                        ->helperText('Tracks program enrolment and status.'),
                ])
                ->visible(fn (?Person $record) => $record === null),

            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organisation')->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('phone')->toggleable(),

                Tables\Columns\TextColumn::make('roles')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'donor'       => 'warning',
                        'volunteer'   => 'success',
                        'beneficiary' => 'info',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => str($state)->title()),

                Tables\Columns\TextColumn::make('city')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_donor')
                    ->label('Is a donor')
                    ->query(fn (Builder $q) => $q->whereHas('donorProfile'))
                    ->toggle(),
                Tables\Filters\Filter::make('is_volunteer')
                    ->label('Is a volunteer')
                    ->query(fn (Builder $q) => $q->whereHas('volunteerProfile'))
                    ->toggle(),
                Tables\Filters\Filter::make('is_beneficiary')
                    ->label('Is a beneficiary')
                    ->query(fn (Builder $q) => $q->whereHas('beneficiaryProfile'))
                    ->toggle(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DonorProfileRelationManager::class,
            RelationManagers\VolunteerProfileRelationManager::class,
            RelationManagers\BeneficiaryProfileRelationManager::class,
            RelationManagers\InteractionsRelationManager::class,
            RelationManagers\DonationsRelationManager::class,
            RelationManagers\PledgesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPeople::route('/'),
            'create' => Pages\CreatePerson::route('/create'),
            'view'   => Pages\ViewPerson::route('/{record}'),
            'edit'   => Pages\EditPerson::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['donorProfile', 'volunteerProfile', 'beneficiaryProfile']);
    }
}
