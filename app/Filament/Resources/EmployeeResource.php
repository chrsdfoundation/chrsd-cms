<?php

namespace App\Filament\Resources;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\VerificationStatus;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\CertificateType;
use App\Models\Employee;
use App\Services\Documents\BulkCertificateIssuanceService;
use App\Services\Reports\ReportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $cluster = \App\Filament\Clusters\OrgUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->full_name . ' — ' . $record->serial_number;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'serial_number'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Employee')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Identity')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\TextInput::make('first_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('middle_name')->maxLength(255),
                                Forms\Components\TextInput::make('last_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('suffix')->maxLength(16),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('email')->email()->required()
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('mobile')->tel()->maxLength(32),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Select::make('gender')
                                    ->options(Gender::class)->default(Gender::Other->value)->required(),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->maxDate(now()->subYears(15))->native(false),
                                Forms\Components\TextInput::make('civil_status')->maxLength(32),
                                Forms\Components\TextInput::make('nationality')->maxLength(64),
                            ]),
                            Forms\Components\Textarea::make('address')->rows(3)->columnSpanFull(),

                            Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                                ->collection('avatar')
                                ->image()->imageEditor()->avatar()
                                ->columnSpanFull(),
                            Forms\Components\SpatieMediaLibraryFileUpload::make('signature')
                                ->collection('signature')
                                ->image()
                                ->helperText('PNG with transparent background is best for PDF stamping.')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Employment')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()->preload(),
                                Forms\Components\Select::make('position_id')
                                    ->relationship('position', 'title')
                                    ->searchable()->preload(),
                                Forms\Components\Select::make('supervisor_id')
                                    ->relationship('supervisor', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable(['first_name', 'last_name'])->preload(),
                                Forms\Components\Select::make('employment_type')
                                    ->options(EmploymentType::class)
                                    ->default(EmploymentType::Regular->value)->required(),
                                Forms\Components\Select::make('employee_status')
                                    ->options(EmployeeStatus::class)
                                    ->default(EmployeeStatus::Active->value)->required(),
                                Forms\Components\DatePicker::make('hired_at')->native(false),
                                Forms\Components\DatePicker::make('ended_at')->native(false),
                            ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Verification')
                        ->icon('heroicon-o-shield-check')
                        ->visible(fn (?Employee $record) => $record !== null)
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('serial_number')
                                    ->disabled()->dehydrated(false)
                                    ->helperText('Auto-generated on create by VerifiableObserver'),
                                Forms\Components\Select::make('status')
                                    ->options(VerificationStatus::class)->disabled()->dehydrated(false),
                                Forms\Components\TextInput::make('verification_hash')
                                    ->disabled()->dehydrated(false)
                                    ->columnSpanFull(),
                                Forms\Components\DateTimePicker::make('verified_at')
                                    ->disabled()->dehydrated(false),
                                Forms\Components\DateTimePicker::make('revoked_at')
                                    ->disabled()->dehydrated(false),
                                Forms\Components\Textarea::make('revocation_reason')
                                    ->disabled()->dehydrated(false)->columnSpanFull(),
                            ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatar')->conversion('thumb')->circular(),
                Tables\Columns\TextColumn::make('serial_number')->label('Serial')
                    ->searchable()->sortable()->fontFamily('mono')->size('xs')
                    ->badge()->color('gray'),
                Tables\Columns\TextColumn::make('full_name')->label('Name')
                    ->searchable(['first_name', 'last_name'])->sortable(['last_name']),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('department.name')->sortable()->toggleable()
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('position.title')->toggleable(),
                Tables\Columns\TextColumn::make('employment_type')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('employee_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')
                    ->badge()->toggleable(),
                Tables\Columns\TextColumn::make('hired_at')->date()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('employee_status')->options(EmployeeStatus::class),
                Tables\Filters\SelectFilter::make('employment_type')->options(EmploymentType::class),
                Tables\Filters\SelectFilter::make('status')->options(VerificationStatus::class)
                    ->label('Verification'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                self::serviceRecordAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                self::bulkIssueCertificatesAction(),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    public static function serviceRecordAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('service_record')
            ->label('Service Record')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->action(fn (Employee $record) => app(ReportService::class)->exportServiceRecord($record));
    }

    public static function bulkIssueCertificatesAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('bulk_issue_certificates')
            ->label('Issue Certificate')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->deselectRecordsAfterCompletion()
            ->form([
                Forms\Components\Select::make('certificate_type_id')
                    ->label('Certificate Type')
                    ->options(CertificateType::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\Select::make('signed_by_id')
                    ->label('Signatory')
                    ->options(fn () => Employee::query()
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name])
                        ->all())
                    ->searchable(),
                Forms\Components\TextInput::make('purpose')
                    ->maxLength(255)
                    ->helperText('Same purpose for every certificate. Leave blank if not applicable.'),
                Forms\Components\Toggle::make('auto_generate')
                    ->label('Auto-generate PDFs immediately')
                    ->default(true)
                    ->helperText('If off, certificates are created as Draft — you can generate PDFs later.'),
            ])
            ->action(function (Collection $records, array $data) {
                $type = CertificateType::findOrFail($data['certificate_type_id']);
                $signatory = ! empty($data['signed_by_id']) ? Employee::find($data['signed_by_id']) : null;

                $result = app(BulkCertificateIssuanceService::class)->run(
                    employees:    $records,
                    type:         $type,
                    signatory:    $signatory,
                    purpose:      $data['purpose'] ?? null,
                    autoGenerate: (bool) ($data['auto_generate'] ?? true),
                );

                $ok = count($result['succeeded']);
                $fail = count($result['failed']);

                $body = sprintf(
                    '%d succeeded, %d failed out of %d selected.',
                    $ok, $fail, $result['total']
                );

                if ($fail > 0) {
                    $body .= "\nFirst failure: {$result['failed'][0]['employee']} — {$result['failed'][0]['reason']}";
                }

                Notification::make()
                    ->title('Bulk certificate issuance')
                    ->body($body)
                    ->color($fail > 0 ? 'warning' : 'success')
                    ->duration(10000)
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\HistoryRelationManager::class,
            RelationManagers\CertificatesRelationManager::class,
            RelationManagers\AuthoredLettersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view'   => Pages\ViewEmployee::route('/{record}'),
            'edit'   => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['department', 'position']);
    }
}
