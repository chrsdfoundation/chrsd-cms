<?php

namespace App\Filament\Resources;

use App\Enums\IdCardIssuance;
use App\Enums\VerificationStatus;
use App\Filament\Clusters\Documents;
use App\Filament\Resources\IdCardResource\Pages;
use App\Models\Employee;
use App\Models\IdCard;
use App\Services\Documents\IdCardGeneratorService;
use App\Services\Verification\QrCodeService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class IdCardResource extends Resource
{
    protected static ?string $model = IdCard::class;

    protected static ?string $cluster = Documents::class;

    protected static ?string $slug = 'id-cards';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->serial_number . ' — ' . optional($record->employee)->full_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_number', 'designation', 'employee.first_name', 'employee.last_name'];
    }

    public static function form(Form $form): Form
    {
        // Categorical ID Type labels printed on the front. Pre-populated common
        // values; free text still accepted via ->allowHtml() = false + a
        // custom entry option below.
        $idTypeLabels = [
            'Employee Identity' => 'Employee Identity',
            'Volunteer ID Card' => 'Volunteer ID Card',
            'Consultant ID Card' => 'Consultant ID Card',
            'Visitor Pass' => 'Visitor Pass',
            'Field Officer ID' => 'Field Officer ID',
            'Media Pass' => 'Media Pass',
            'Intern ID Card' => 'Intern ID Card',
        ];

        return $form->schema([
            // Auto-assign organization to ensure multi-tenant scoping works
            // even if session('current_organization_id') isn't set
            Forms\Components\Hidden::make('organization_id')
                ->default(fn () => config('chrsd.org_id') ?? 1)
                ->dehydrated(true),

            Forms\Components\Section::make('Holder')
                ->description('The ID can be issued to any recipient. If the holder is a CHRSD employee, pick them from the dropdown and their name/photo pull in automatically. Otherwise leave the employee blank and type the recipient\'s full name.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('recipient_name')
                        ->label('Recipient full name')
                        ->maxLength(160)
                        ->placeholder('e.g. Rahim Uddin')
                        ->helperText('This is what prints on the card. Required if no employee is linked.'),

                    Forms\Components\Select::make('employee_id')
                        ->label('Link to employee (optional)')
                        ->relationship('employee', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn (Employee $r) => $r->full_name . ' — ' . $r->serial_number)
                        ->searchable(['first_name', 'last_name', 'email', 'serial_number'])
                        ->preload()
                        ->helperText('Only when the holder IS a CHRSD employee.'),
                ]),

            Forms\Components\Section::make('Card')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('id_type_label')
                        ->label('ID type (printed on card)')
                        ->options($idTypeLabels)
                        ->searchable()
                        ->required()
                        ->default('Employee Identity')
                        ->helperText('Appears under the organisation name on the front of the card.'),

                    Forms\Components\Select::make('id_card_type_id')
                        ->label('Card type (category, for reporting)')
                        ->relationship('idCardType', 'name')
                        ->searchable()->preload()
                        ->helperText('Categorical grouping in the admin — separate from the printed label above.'),

                    Forms\Components\Select::make('signed_by_id')
                        ->label('Authorised signatory (audit trail)')
                        ->relationship('signedBy', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn (Employee $r) => $r->full_name)
                        ->searchable(['first_name', 'last_name'])->preload()
                        ->helperText('The employee who signs the card. Upload their signature PNG in the "Signature" section below.'),

                    Forms\Components\TextInput::make('designation')
                        ->maxLength(128)
                        ->helperText('Falls back to the employee\'s position title if blank.'),

                    Forms\Components\TextInput::make('program_name')
                        ->maxLength(128)
                        ->helperText('Optional label shown when there\'s no designation.'),

                    Forms\Components\Select::make('blood_group')
                        ->options(collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                            ->mapWithKeys(fn ($v) => [$v => $v])),

                    Forms\Components\TextInput::make('nationality')
                        ->maxLength(64)
                        ->helperText('Falls back to the employee\'s nationality field.'),

                    Forms\Components\DatePicker::make('valid_from')
                        ->required()->native(false)->default(now()),

                    Forms\Components\DatePicker::make('valid_until')
                        ->required()->native(false)
                        ->default(fn () => now()->addYears(2))
                        ->helperText('Two years from today is the common default.'),

                    Forms\Components\Select::make('issuance_status')
                        ->options(IdCardIssuance::class)
                        ->default(IdCardIssuance::Draft->value)->required(),
                ]),

            Forms\Components\Section::make('Photo')
                ->description('Portrait photo printed on the card front. Leave empty to fall back to the linked employee\'s avatar (if any).')
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('photo')
                        ->collection('photo')
                        ->image()->imageEditor()
                        ->maxSize(4096)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Signature')
                ->description('Authorised signatory\'s handwritten signature. Ideally a transparent-background PNG so it prints cleanly over the signature line.')
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('signature')
                        ->collection('signature')
                        ->image()->imageEditor()
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Verification')
                ->columns(2)
                ->visible(fn (?IdCard $record) => $record !== null)
                ->schema([
                    Forms\Components\TextInput::make('serial_number')->disabled()->dehydrated(false),
                    Forms\Components\Select::make('status')
                        ->options(VerificationStatus::class)->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('verification_hash')
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
                Tables\Columns\TextColumn::make('idCardType.name')->label('Type')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Holder')
                    ->searchable(['employee.first_name', 'employee.last_name'])->sortable(),
                Tables\Columns\TextColumn::make('designation')->toggleable(),
                Tables\Columns\TextColumn::make('valid_from')->date()->toggleable(),
                Tables\Columns\TextColumn::make('valid_until')->date()->sortable(),
                Tables\Columns\TextColumn::make('issuance_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('issuance_status')->options(IdCardIssuance::class),
                Tables\Filters\SelectFilter::make('status')->options(VerificationStatus::class)->label('Verification'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning'),
                self::updatePhotoAction(),
                self::generateAction(),
                self::downloadFrontAction(),
                self::downloadBackAction(),
                self::markDeliveredAction(),
                self::qrPreviewAction(),
                self::revokeAction(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListIdCards::route('/'),
            'create' => Pages\CreateIdCard::route('/create'),
            'view' => Pages\ViewIdCard::route('/{record}'),
            'edit' => Pages\EditIdCard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['idCardType', 'employee']);
    }

    // ---- Row/header actions -------------------------------------------
    // Each action has a Tables\Actions\Action variant (for the row) and a
    // Filament\Actions\Action variant (for the View/Edit page headers).
    // The chained shape is identical between the two variants — only the
    // factory class differs, so the shape lives in a private applier.

    public static function generateAction(): Tables\Actions\Action
    {
        return self::applyGenerateShape(Tables\Actions\Action::make('generate'));
    }

    public static function generateHeaderAction(): Action
    {
        return self::applyGenerateShape(Action::make('generate'));
    }

    public static function updatePhotoAction(): Tables\Actions\Action
    {
        return self::applyUpdatePhotoShape(Tables\Actions\Action::make('update_photo'));
    }

    public static function updatePhotoHeaderAction(): Action
    {
        return self::applyUpdatePhotoShape(Action::make('update_photo'));
    }

    public static function downloadFrontAction(): Tables\Actions\Action
    {
        return self::applyDownloadFrontShape(Tables\Actions\Action::make('download_front'));
    }

    public static function downloadFrontHeaderAction(): Action
    {
        return self::applyDownloadFrontShape(Action::make('download_front'));
    }

    public static function downloadBackAction(): Tables\Actions\Action
    {
        return self::applyDownloadBackShape(Tables\Actions\Action::make('download_back'));
    }

    public static function downloadBackHeaderAction(): Action
    {
        return self::applyDownloadBackShape(Action::make('download_back'));
    }

    public static function markDeliveredAction(): Tables\Actions\Action
    {
        return self::applyMarkDeliveredShape(Tables\Actions\Action::make('mark_delivered'));
    }

    public static function markDeliveredHeaderAction(): Action
    {
        return self::applyMarkDeliveredShape(Action::make('mark_delivered'));
    }

    public static function qrPreviewAction(): Tables\Actions\Action
    {
        return self::applyQrPreviewShape(Tables\Actions\Action::make('qr_preview'));
    }

    public static function qrPreviewHeaderAction(): Action
    {
        return self::applyQrPreviewShape(Action::make('qr_preview'));
    }

    public static function revokeAction(): Tables\Actions\Action
    {
        return self::applyRevokeShape(Tables\Actions\Action::make('revoke'));
    }

    public static function revokeHeaderAction(): Action
    {
        return self::applyRevokeShape(Action::make('revoke'));
    }

    private static function applyGenerateShape($action)
    {
        return $action
            ->label('Generate PDFs')->icon('heroicon-o-printer')->color('primary')
            ->requiresConfirmation()
            ->modalDescription('Renders both sides of the ID via DomPDF and attaches them to the card.')
            ->action(function (IdCard $record) {
                app(IdCardGeneratorService::class)->generate($record);
                Notification::make()->success()->title('ID card PDFs generated')->send();
            })
            ->visible(fn (IdCard $r) => $r->isValid());
    }

    private static function applyDownloadFrontShape($action)
    {
        return $action
            ->label('Download front')->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->url(function (IdCard $r) {
                $media = $r->getMedia('rendered')->firstWhere(fn ($m) => str_contains($m->file_name, '-front.pdf'));

                return $media?->getUrl();
            })
            ->openUrlInNewTab()
            ->visible(fn (IdCard $r) => $r->getMedia('rendered')
                ->contains(fn ($m) => str_contains($m->file_name, '-front.pdf')));
    }

    private static function applyDownloadBackShape($action)
    {
        return $action
            ->label('Download back')->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->url(function (IdCard $r) {
                $media = $r->getMedia('rendered')->firstWhere(fn ($m) => str_contains($m->file_name, '-back.pdf'));

                return $media?->getUrl();
            })
            ->openUrlInNewTab()
            ->visible(fn (IdCard $r) => $r->getMedia('rendered')
                ->contains(fn ($m) => str_contains($m->file_name, '-back.pdf')));
    }

    private static function applyMarkDeliveredShape($action)
    {
        return $action
            ->label('Mark Delivered')->icon('heroicon-o-check-badge')->color('success')
            ->requiresConfirmation()
            ->action(function (IdCard $record) {
                app(IdCardGeneratorService::class)->markDelivered($record);
                Notification::make()->success()->title('Marked as Delivered')->send();
            })
            ->visible(fn (IdCard $r) => $r->issuance_status === IdCardIssuance::Printed);
    }

    private static function applyQrPreviewShape($action)
    {
        return $action
            ->label('QR')->icon('heroicon-o-qr-code')->color('info')
            ->modalHeading('Verification QR')
            ->modalContent(fn (IdCard $record) => new HtmlString(
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

    private static function applyUpdatePhotoShape($action)
    {
        return $action
            ->label('Update photo')->icon('heroicon-o-camera')->color('info')
            ->modalHeading('Attach or replace the card photo')
            ->modalDescription('Upload a formal portrait for this ID card. Leaving it blank clears the override and falls back to the employee\'s avatar. Regenerate the PDFs afterwards to bake the new photo into the card.')
            ->form([
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['11:14', '1:1'])
                    ->disk('local')
                    ->directory('tmp/id-card-photos')
                    ->maxSize(4096)
                    ->helperText('JPG or PNG, up to 4 MB. Portrait orientation works best (roughly 11:14).')
                    ->columnSpanFull(),
            ])
            ->fillForm(fn () => ['photo' => null])
            ->action(function (IdCard $record, array $data) {
                $record->clearMediaCollection('photo');
                if (! empty($data['photo'])) {
                    $absolute = \Storage::disk('local')->path($data['photo']);
                    if (is_file($absolute)) {
                        $record->addMedia($absolute)->toMediaCollection('photo');
                    }
                }
                Notification::make()
                    ->success()
                    ->title(empty($data['photo']) ? 'Photo cleared' : 'Photo updated')
                    ->body('Click "Generate PDFs" to re-render the card with the new photo.')
                    ->send();
            });
    }

    private static function applyRevokeShape($action)
    {
        return $action
            ->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
            ->form([Forms\Components\Textarea::make('reason')->required()->label('Reason for revocation')->rows(3)])
            ->requiresConfirmation()
            ->action(function (IdCard $record, array $data) {
                $record->revoke($data['reason']);
                Notification::make()->danger()->title('ID card revoked')->send();
            })
            ->visible(fn (IdCard $r) => $r->status === VerificationStatus::Valid);
    }
}
