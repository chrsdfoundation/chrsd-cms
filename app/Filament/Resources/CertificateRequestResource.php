<?php

namespace App\Filament\Resources;

use App\Enums\CertificateRequestStatus;
use App\Filament\Resources\CertificateRequestResource\Pages;
use App\Models\CertificateRequest;
use App\Models\Employee;
use App\Services\Documents\CertificateRequestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CertificateRequestResource extends Resource
{
    protected static ?string $model = CertificateRequest::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 35;

    protected static ?string $recordTitleAttribute = 'purpose';

    /** Pending count as a nav badge — HR can see workload at a glance. */
    public static function getNavigationBadge(): ?string
    {
        $c = static::getModel()::query()
            ->where('status', CertificateRequestStatus::Pending->value)
            ->count();

        return $c > 0 ? (string) $c : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->relationship('employee', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn (Employee $r) => $r->full_name . ' — ' . $r->serial_number)
                        ->searchable(['first_name', 'last_name', 'email', 'serial_number'])
                        ->preload()->required()
                        ->disabledOn('edit'),
                    Forms\Components\Select::make('certificate_type_id')
                        ->relationship('type', 'name')
                        ->preload()->required()
                        ->disabledOn('edit'),
                    Forms\Components\TextInput::make('purpose')->required()->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull()
                        ->helperText('Employee-supplied context.'),
                ]),

            Forms\Components\Section::make('Review')
                ->columns(2)
                ->visible(fn (?CertificateRequest $record) => $record && ! $record->isPending())
                ->schema([
                    Forms\Components\TextInput::make('status')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('reviewedBy.name')->label('Reviewed by')
                        ->disabled()->dehydrated(false),
                    Forms\Components\DateTimePicker::make('reviewed_at')->disabled()->dehydrated(false),
                    Forms\Components\Textarea::make('review_notes')->disabled()->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->since()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')
                    ->searchable(['employee.first_name', 'employee.last_name']),
                Tables\Columns\TextColumn::make('type.code')->label('Type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('purpose')->limit(40),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('reviewedBy.name')->label('Reviewed by')->toggleable(),
                Tables\Columns\TextColumn::make('resultingCertificate.serial_number')
                    ->label('Issued as')->fontFamily('mono')->size('xs')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CertificateRequestStatus::class),
                Tables\Filters\SelectFilter::make('certificate_type_id')
                    ->relationship('type', 'name')->preload()->label('Type'),
            ])
            ->actions([
                self::approveAction(),
                self::rejectAction(),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function approveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approve')
            ->label('Approve & Issue')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->form([
                Forms\Components\Select::make('signed_by_id')
                    ->label('Signatory')
                    ->options(fn () => Employee::query()
                        ->orderBy('last_name')->get()
                        ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name])
                        ->all())
                    ->searchable(),
                Forms\Components\Textarea::make('review_notes')
                    ->label('Review notes (optional)')->rows(2),
            ])
            ->requiresConfirmation()
            ->modalDescription('This creates a Certificate for the employee and generates the PDF immediately.')
            ->action(function (CertificateRequest $record, array $data) {
                $cert = app(CertificateRequestService::class)->approveAndIssue(
                    request:      $record,
                    reviewer:     Auth::user(),
                    signatoryId:  $data['signed_by_id'] ?? null,
                    reviewNotes:  $data['review_notes'] ?? null,
                );
                Notification::make()
                    ->success()
                    ->title('Request approved and certificate issued')
                    ->body("Serial: {$cert->serial_number}")
                    ->send();
            })
            ->visible(fn (CertificateRequest $r) => $r->isPending());
    }

    public static function rejectAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->form([
                Forms\Components\Textarea::make('reason')->required()
                    ->label('Reason (shared with employee)')->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (CertificateRequest $record, array $data) {
                app(CertificateRequestService::class)->reject(
                    request:  $record,
                    reviewer: Auth::user(),
                    reason:   $data['reason'],
                );
                Notification::make()->danger()->title('Request rejected')->send();
            })
            ->visible(fn (CertificateRequest $r) => $r->isPending());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateRequests::route('/'),
            'view'  => Pages\ViewCertificateRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'type', 'reviewedBy', 'resultingCertificate']);
    }
}
