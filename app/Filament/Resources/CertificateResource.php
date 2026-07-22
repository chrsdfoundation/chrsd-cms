<?php

namespace App\Filament\Resources;

use App\Enums\CertificateIssuance;
use App\Enums\VerificationStatus;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Services\Documents\CertificateGeneratorService;
use App\Services\Verification\QrCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $cluster = \App\Filament\Clusters\Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->serial_number . ' — ' . optional($record->employee)->full_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_number', 'purpose', 'employee.first_name', 'employee.last_name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Recipient')
                ->description('Certificate can be issued to any recipient — an existing employee OR a free-text name (partner-org staff, board members, community volunteers). If both are set the free-text name wins on the printed certificate.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('recipient_name')
                        ->label('Recipient full name')
                        ->maxLength(160)
                        ->placeholder('e.g. Dr. Fatima Hossain')
                        ->helperText('This is what prints on the certificate.'),
                    Forms\Components\Select::make('employee_id')
                        ->label('Link to employee (optional)')
                        ->relationship('employee', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' — ' . $record->serial_number)
                        ->searchable(['first_name', 'last_name', 'email', 'serial_number'])->preload()
                        ->helperText('Only when the recipient is a CHRSD employee — links to their record for reporting.'),
                ]),

            Forms\Components\Section::make('Issuance')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('certificate_type_id')
                        ->label('Certificate type')
                        ->relationship('type', 'name')
                        ->searchable()->preload()->required()
                        ->live()
                        ->default(fn () => \App\Models\CertificateType::query()->where('name', 'Certificate of Achievement')->value('id')),
                    Forms\Components\Select::make('issuance_status')
                        ->options(CertificateIssuance::class)
                        ->default(CertificateIssuance::Draft->value)->required(),
                    // Template selector — mirrors OfficialLetterResource so
                    // certificate templates (Experience/Service, Training,
                    // Course Completion, …) are actually pickable when
                    // creating a certificate. Filtered by the chosen type,
                    // plus type-agnostic templates.
                    Forms\Components\Select::make('document_template_id')
                        ->label('Template')
                        ->options(function (Forms\Get $get) {
                            $q = \App\Models\DocumentTemplate::query()
                                ->where('document_type', \App\Enums\DocumentTemplateType::Certificate->value);
                            $typeId = $get('certificate_type_id');
                            if ($typeId) {
                                $q->where(fn ($qq) => $qq->whereNull('certificate_type_id')
                                    ->orWhere('certificate_type_id', $typeId));
                            }
                            return $q->orderBy('name')->pluck('name', 'id');
                        })
                        // No searchable() + no preload() — the list is short and
                        // preload+searchable together used to leave the dropdown
                        // showing "Start typing to search…". Native <select>-style
                        // opens immediately on click.
                        ->live()
                        // Pre-fill purpose/signatories from sample_context on
                        // new certificates so admins edit-in-place. Only
                        // touches fields currently empty (respects manual
                        // overrides).
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, ?Certificate $record) {
                            if (! $state || $record !== null) {
                                return;
                            }
                            $tpl = \App\Models\DocumentTemplate::find($state);
                            $sample = (array) ($tpl?->sample_context ?? []);
                            $map = [
                                'purpose'           => ['course_name', 'event_name', 'purpose'],
                                'recipient_name'    => ['name', 'recipient_name'],
                                'signatory_1_name'  => ['signatory_left', 'signatory_name'],
                                'signatory_1_title' => ['signatory_left_title', 'signatory_title'],
                                'signatory_2_name'  => ['signatory_right', 'signatory_name'],
                                'signatory_2_title' => ['signatory_right_title', 'signatory_title'],
                            ];
                            foreach ($map as $field => $sources) {
                                if (! empty($get($field))) continue;
                                foreach ($sources as $s) {
                                    if (! empty($sample[$s])) { $set($field, $sample[$s]); break; }
                                }
                            }
                        })
                        ->helperText('Optional — leave blank to use the built-in default. Selecting one pre-fills purpose and signatories from the template sample.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('signed_by_id')
                        ->label('Primary signatory (audit trail)')
                        ->helperText('Employee whose identity backs the verification hash. Display names/titles below are what actually print on the certificate.')
                        ->relationship('signedBy', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                        ->searchable(['first_name', 'last_name'])->preload(),
                    Forms\Components\TextInput::make('purpose')
                        ->label('Course / achievement title')
                        ->maxLength(255)
                        ->placeholder('e.g. Advanced Humanitarian Response & Field Coordination')
                        ->helperText('Appears as the course line on the certificate.'),
                    Forms\Components\DatePicker::make('issued_on')->native(false),
                    Forms\Components\DatePicker::make('valid_until')->native(false),
                ]),

            // Fields consumed by templates that reference {{employee_id}},
            // {{designation}}, {{department}}, {{duration}}, {{position}} —
            // Experience/Service Certificate, Training Certificate, etc.
            // Persisted into the `payload` JSON so we don't schema-migrate
            // every time a template surfaces a new placeholder.
            Forms\Components\Section::make('Certificate details (template fields)')
                ->description('Filled into the certificate body when the selected template references these fields. Leave any blank when a template does not need them.')
                ->columns(2)
                ->collapsed(fn (?Certificate $record) => $record === null)
                ->schema([
                    Forms\Components\TextInput::make('payload.employee_id')
                        ->label('Employee ID')
                        ->placeholder('e.g. CHRSD-EMP-2026-0007')
                        ->maxLength(64),
                    Forms\Components\TextInput::make('payload.designation')
                        ->label('Designation')
                        ->placeholder('e.g. Programme Manager')
                        ->maxLength(160),
                    Forms\Components\TextInput::make('payload.department')
                        ->label('Department / Division')
                        ->placeholder('e.g. Environmental Monitoring')
                        ->maxLength(160),
                    Forms\Components\TextInput::make('payload.duration')
                        ->label('Duration of Service')
                        ->placeholder('e.g. 01 Jan 2024 — 30 Jun 2026 (2 years, 6 months)')
                        ->maxLength(160),
                    Forms\Components\TextInput::make('payload.position')
                        ->label('Employment Type')
                        ->placeholder('e.g. Full-Time / Contractual')
                        ->maxLength(64),
                    Forms\Components\TextInput::make('payload.event_name')
                        ->label('Programme / Event name')
                        ->placeholder('Used by Training / Course-completion templates')
                        ->maxLength(160),
                ]),

            Forms\Components\Section::make('Signatories on the certificate')
                ->description('Two signature blocks print on the certificate — left cell (typically the Coordinator) and right cell (typically the Executive Director). Both name and designation are free-text so you can override for special issuances.')
                ->columns(2)
                ->schema([
                    Forms\Components\Fieldset::make('First signatory (left)')
                        ->schema([
                            Forms\Components\TextInput::make('signatory_1_name')
                                ->label('Name')
                                ->maxLength(128)
                                ->placeholder('e.g. Razib Mustafiz'),
                            Forms\Components\TextInput::make('signatory_1_title')
                                ->label('Designation')
                                ->maxLength(128)
                                ->placeholder('e.g. Project Coordinator'),
                            Forms\Components\SpatieMediaLibraryFileUpload::make('signature_1_upload')
                                ->label('Signature image (PNG with transparent background is ideal)')
                                ->collection('signature_1')
                                ->image()->imageEditor()
                                ->maxSize(2048)
                                ->columnSpanFull(),
                        ]),
                    Forms\Components\Fieldset::make('Second signatory (right)')
                        ->schema([
                            Forms\Components\TextInput::make('signatory_2_name')
                                ->label('Name')
                                ->maxLength(128)
                                ->placeholder('e.g. M.A. Ramim'),
                            Forms\Components\TextInput::make('signatory_2_title')
                                ->label('Designation')
                                ->maxLength(128)
                                ->placeholder('e.g. Executive Director'),
                            Forms\Components\SpatieMediaLibraryFileUpload::make('signature_2_upload')
                                ->label('Signature image (PNG with transparent background is ideal)')
                                ->collection('signature_2')
                                ->image()->imageEditor()
                                ->maxSize(2048)
                                ->columnSpanFull(),
                        ]),
                ]),

            // Template Payload section removed — only one canonical
            // certificate template exists now, so per-cert key-value
            // overrides are no longer surfaced in the form.

            Forms\Components\Section::make('Verification')
                ->columns(2)
                ->visible(fn (?Certificate $record) => $record !== null)
                ->schema([
                    Forms\Components\TextInput::make('serial_number')
                        ->disabled()->dehydrated(false),
                    Forms\Components\Select::make('status')
                        ->options(VerificationStatus::class)->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('verification_hash')
                        ->disabled()->dehydrated(false)->columnSpanFull(),
                    Forms\Components\Textarea::make('revocation_reason')
                        ->disabled()->dehydrated(false)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('Serial')
                    ->searchable()->sortable()->fontFamily('mono')->size('xs')
                    ->badge()->color('gray'),
                Tables\Columns\TextColumn::make('recipient_display')
                    ->label('Recipient')
                    ->getStateUsing(fn (Certificate $r) => $r->recipient_name
                        ?: optional($r->employee)->full_name
                        ?: '—')
                    ->searchable(query: fn (Builder $q, string $search) => $q
                        ->where('recipient_name', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($eq) => $eq
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))
                    ),
                Tables\Columns\TextColumn::make('type.code')->label('Type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('purpose')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('issuance_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
                Tables\Columns\TextColumn::make('issued_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('valid_until')->date()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('certificate_type_id')
                    ->relationship('type', 'name')->preload()->label('Type'),
                Tables\Filters\SelectFilter::make('issuance_status')->options(CertificateIssuance::class),
                Tables\Filters\SelectFilter::make('status')->options(VerificationStatus::class)
                    ->label('Verification'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                self::generatePdfAction(),
                self::downloadPdfAction(),
                self::markIssuedAction(),
                self::markDeliveredAction(),
                self::qrPreviewAction(),
                self::revokeAction(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->label('More'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'view'   => Pages\ViewCertificate::route('/{record}'),
            'edit'   => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['employee', 'type']);
    }

    // ---- Step 4 actions ------------------------------------------------

    public static function generatePdfAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate_pdf')
            ->label('Generate PDF')->icon('heroicon-o-document-arrow-down')->color('primary')
            ->requiresConfirmation()
            ->modalDescription('This will render the certificate to PDF via DomPDF and attach it to the record.')
            ->action(function (Certificate $record) {
                app(CertificateGeneratorService::class)->generate($record);
                Notification::make()->success()->title('Certificate PDF generated')->send();
            })
            ->visible(fn (Certificate $record) => $record->isValid());
    }

    public static function downloadPdfAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('download_pdf')
            ->label('Download')->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->url(fn (Certificate $record) => $record->getFirstMediaUrl('rendered'))
            ->openUrlInNewTab()
            ->visible(fn (Certificate $record) => $record->hasMedia('rendered'));
    }

    public static function markIssuedAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('mark_issued')
            ->label('Mark Issued')->icon('heroicon-o-check-badge')->color('success')
            ->requiresConfirmation()
            ->action(function (Certificate $record) {
                app(CertificateGeneratorService::class)->markIssued($record);
                Notification::make()->success()->title('Marked as Issued')->send();
            })
            ->visible(fn (Certificate $record) => $record->issuance_status === CertificateIssuance::Generated);
    }

    public static function markDeliveredAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('mark_delivered')
            ->label('Mark Delivered')->icon('heroicon-o-truck')->color('warning')
            ->requiresConfirmation()
            ->action(function (Certificate $record) {
                app(CertificateGeneratorService::class)->markDelivered($record);
                Notification::make()->success()->title('Marked as Delivered')->send();
            })
            ->visible(fn (Certificate $record) => $record->issuance_status === CertificateIssuance::Issued);
    }

    public static function qrPreviewAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('qr_preview')
            ->label('QR')->icon('heroicon-o-qr-code')->color('info')
            ->modalHeading('Verification QR')
            ->modalContent(fn (Certificate $record) => new HtmlString(
                '<div class="flex justify-center p-6">'
                . app(QrCodeService::class)->svg($record, 6)
                . '</div>'
                . '<p class="text-center text-sm text-gray-600 break-all">'
                . e(app(QrCodeService::class)->verificationUrl($record))
                . '</p>'
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    public static function revokeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('revoke')
            ->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
            ->form([
                Forms\Components\Textarea::make('reason')->required()
                    ->label('Reason for revocation')->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (Certificate $record, array $data) {
                $record->revoke($data['reason']);
                Notification::make()->danger()->title('Certificate revoked')->send();
            })
            ->visible(fn (Certificate $record) => $record->status === VerificationStatus::Valid);
    }
}
