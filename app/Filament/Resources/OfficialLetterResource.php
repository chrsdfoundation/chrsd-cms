<?php

namespace App\Filament\Resources;

use App\Enums\DocumentTemplateType;
use App\Enums\OfficialLetterStatus;
use App\Enums\VerificationStatus;
use App\Filament\Clusters\Documents;
use App\Filament\Resources\OfficialLetterResource\Pages;
use App\Models\Author;
use App\Models\DocumentTemplate;
use App\Models\OfficialLetter;
use App\Services\Documents\LetterGeneratorService;
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

class OfficialLetterResource extends Resource
{
    protected static ?string $model = OfficialLetter::class;

    protected static ?string $cluster = Documents::class;

    protected static ?string $slug = 'official-letters';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->serial_number . ' — ' . $record->subject;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_number', 'subject', 'recipient_name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Auto-assign organization to ensure multi-tenant scoping works
            // even if session('current_organization_id') isn't set
            Forms\Components\Hidden::make('organization_id')
                ->default(fn () => config('chrsd.org_id') ?? 1)
                ->dehydrated(true),

            Forms\Components\Section::make('Classification')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('letter_category_id')
                        ->relationship('category', 'name')
                        ->searchable()->preload()
                        ->helperText('Optional — leave blank if the letter does not fit an existing category.'),
                    Forms\Components\Select::make('letter_status')
                        ->options(OfficialLetterStatus::class)
                        ->default(OfficialLetterStatus::Draft->value)->required(),
                    Forms\Components\Select::make('letter_author_id')
                        ->label('Author')
                        ->relationship('letterAuthor', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('name'))
                        ->searchable()->preload()->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required()->maxLength(255),
                            Forms\Components\TextInput::make('designation')->required()->maxLength(255),
                            Forms\Components\TextInput::make('department')->maxLength(255),
                            Forms\Components\TextInput::make('organization')->maxLength(255)->default(config('app.name')),
                            Forms\Components\TextInput::make('email')->email()->maxLength(255),
                            Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return Author::create(array_merge($data, ['is_active' => true]))->getKey();
                        })
                        ->helperText('Select an existing author or create a new one inline.'),
                    Forms\Components\Select::make('signed_by_id')
                        ->label('Signatory (Employee, optional)')
                        ->relationship('signedBy', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                        ->searchable(['first_name', 'last_name'])->preload()
                        ->helperText('Optional. Override the PDF signatory with a specific employee.'),
                    Forms\Components\DatePicker::make('dated_on')->native(false),
                    Forms\Components\DatePicker::make('released_on')->native(false),
                    Forms\Components\Select::make('document_template_id')
                        ->label('Template')
                        ->options(function (Forms\Get $get) {
                            $q = DocumentTemplate::query()
                                ->where('document_type', DocumentTemplateType::Letter->value);
                            $catId = $get('letter_category_id');
                            if ($catId) {
                                $q->where(fn ($qq) => $qq->whereNull('letter_category_id')->orWhere('letter_category_id', $catId));
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->searchable()
                        ->live()
                        // When a template is selected on a NEW letter, pre-fill
                        // subject / recipient / body / signatory from the
                        // template's sample_context so the admin edits in place
                        // instead of drafting from scratch. Only applies when
                        // those fields are empty (respects manual overrides).
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, ?OfficialLetter $record) {
                            if (! $state || $record !== null) {
                                return;
                            }
                            $tpl = DocumentTemplate::find($state);
                            $sample = (array) ($tpl?->sample_context ?? []);
                            foreach ([
                                'subject', 'body',
                                'recipient_name', 'recipient_title', 'recipient_address',
                            ] as $key) {
                                if (! empty($sample[$key]) && empty($get($key))) {
                                    $set($key, $sample[$key]);
                                }
                            }
                        })
                        ->helperText('Optional — leave blank to use the built-in Browsershot Blade template. Selecting one pre-fills recipient, subject and body from the template sample.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Recipient')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('recipient_name')->maxLength(255),
                    Forms\Components\TextInput::make('recipient_title')->maxLength(255),
                    Forms\Components\Textarea::make('recipient_address')->rows(3)->columnSpanFull(),
                ]),

            // Visa Support Letter — quick-fill fields. Values are stripped
            // from the payload in mutateFormData*() and used to substitute the
            // [BRACKETED] placeholders in the body BEFORE it is stored, so the
            // saved letter never contains unfilled markers.
            Forms\Components\Section::make('Visa Support Letter — Quick Fill')
                ->description('Fill these once and the placeholders in the body are substituted automatically on save.')
                ->columns(2)
                ->collapsible()
                ->visible(function (Forms\Get $get) {
                    $tplId = $get('document_template_id');
                    if (! $tplId) {
                        return false;
                    }
                    $name = DocumentTemplate::whereKey($tplId)->value('name');

                    return str_contains(strtolower((string) $name), 'visa');
                })
                ->schema([
                    Forms\Components\TextInput::make('visa_employee_full_name')
                        ->label("Employee's full name")->maxLength(255),
                    Forms\Components\TextInput::make('visa_passport_no')
                        ->label('Passport number')->maxLength(64),
                    Forms\Components\TextInput::make('visa_job_title')
                        ->label('Job title at CHRSD')->maxLength(255),
                    Forms\Components\DatePicker::make('visa_start_date_at_chrsd')
                        ->label('Start date at CHRSD')->native(false),
                    Forms\Components\Select::make('visa_type')
                        ->label('Visa type')
                        ->options(['Business' => 'Business', 'Short-Stay' => 'Short-Stay', 'Tourist' => 'Tourist', 'Transit' => 'Transit'])
                        ->default('Business'),
                    Forms\Components\Select::make('visa_pronoun')
                        ->label('Pronouns')
                        ->options([
                            'he' => 'He / him / his',
                            'she' => 'She / her / hers',
                            'they' => 'They / them / their',
                        ])->default('he'),
                    Forms\Components\TextInput::make('visa_event_name')
                        ->label('Event / seminar name')->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('visa_event_location')
                        ->label('Event location (City, Country)')->maxLength(255),
                    Forms\Components\TextInput::make('visa_destination_country')
                        ->label('Destination country')->maxLength(100),
                    Forms\Components\DatePicker::make('visa_event_start')
                        ->label('Event start')->native(false),
                    Forms\Components\DatePicker::make('visa_event_end')
                        ->label('Event end')->native(false),
                    Forms\Components\DatePicker::make('visa_return_date')
                        ->label('Return date')->native(false),
                    Forms\Components\TextInput::make('visa_purpose_statement')
                        ->label('Purpose of participation')->maxLength(500)
                        ->helperText('One or two clauses — e.g. "strengthen CHRSD\'s institutional partnerships and represent our programmes at an international forum".')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('visa_enclosures')
                        ->label('Enclosures')->maxLength(500)
                        ->default('the official invitation letter, employment certificate, bank statement, and travel itinerary')
                        ->columnSpanFull(),
                ]),

            // Digital signature (PNG). Optional per-letter override — if not
            // supplied and a signatory is chosen, the shell falls back to no
            // signature graphic (just the printed name + title).
            //
            // The FilePond upload widget is hidden on the VIEW page because it
            // renders a full uploader that gets stuck in "Waiting for size"
            // when previewing an existing file over `php artisan serve`. On
            // view, we show a plain read-only image preview instead.
            Forms\Components\Section::make('Signature Image')
                ->description('Optional PNG signature (transparent background works best). Renders above the signatory name on the letter.')
                ->collapsed(fn (?OfficialLetter $record) => $record === null || ! $record->hasMedia('signature'))
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('signature')
                        ->collection('signature')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024)
                        ->imageEditor()
                        ->columnSpanFull()
                        ->hiddenOn('view'),

                    Forms\Components\Placeholder::make('signature_preview')
                        ->label('')
                        ->visibleOn('view')
                        ->content(function (?OfficialLetter $record) {
                            if (! $record?->hasMedia('signature')) {
                                return new HtmlString('<em class="text-gray-500">No signature uploaded.</em>');
                            }
                            $url = e($record->getFirstMediaUrl('signature'));

                            return new HtmlString(
                                '<img src="' . $url . '" alt="Signature" '
                                . 'style="max-height:100px;max-width:280px;background:#fff;padding:6px;border:1px solid #e5e7eb;border-radius:6px;">'
                            );
                        }),
                ]),

            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('subject')->required()->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')->required()
                        ->columnSpanFull()
                        ->helperText(new HtmlString(
                            'Tables: type a pipe-delimited block on separate lines, e.g. <code>| Sl. No. | Name | DOB |</code> then rows below. '
                            . 'The PDF renderer converts these to real tables. '
                            . 'A separator row (<code>| --- | --- | --- |</code>) after the header is optional but recommended.'
                        ))
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike', 'h2', 'h3',
                            'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo',
                        ]),
                ]),

            Forms\Components\Section::make('Verification')
                ->columns(2)
                ->visible(fn (?OfficialLetter $record) => $record !== null)
                ->schema([
                    Forms\Components\TextInput::make('serial_number')->disabled()->dehydrated(false),
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
                Tables\Columns\TextColumn::make('category.code')->badge()->color('info'),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('letterAuthor.name')->label('Author')->toggleable()
                    ->getStateUsing(fn (OfficialLetter $r) => $r->letterAuthor?->name ?? $r->author?->full_name),
                Tables\Columns\TextColumn::make('recipient_name')->toggleable(),
                Tables\Columns\TextColumn::make('letter_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
                Tables\Columns\TextColumn::make('dated_on')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('letter_category_id')
                    ->relationship('category', 'name')->preload()->label('Category'),
                Tables\Filters\SelectFilter::make('letter_status')->options(OfficialLetterStatus::class),
                Tables\Filters\SelectFilter::make('status')->options(VerificationStatus::class)
                    ->label('Verification'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                self::generateAction(),
                self::fileAction(),
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
            'index' => Pages\ListOfficialLetters::route('/'),
            'create' => Pages\CreateOfficialLetter::route('/create'),
            'view' => Pages\ViewOfficialLetter::route('/{record}'),
            'edit' => Pages\EditOfficialLetter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['category', 'letterAuthor', 'author']);
    }

    // ---- Step 4 actions ------------------------------------------------

    public static function generateAction(): Tables\Actions\Action
    {
        return self::applyGenerateShape(Tables\Actions\Action::make('generate'));
    }

    public static function generateHeaderAction(): Action
    {
        return self::applyGenerateShape(Action::make('generate'));
    }

    private static function applyGenerateShape($action)
    {
        return $action
            ->label('🖨️ Print / Save as PDF')->icon('heroicon-o-printer')->color('primary')
            ->url(fn (OfficialLetter $record) => route('print.letter', $record))
            ->openUrlInNewTab();
    }

    public static function fileAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('file_letter')
            ->label('File')->icon('heroicon-o-archive-box')->color('gray')
            ->requiresConfirmation()
            ->action(function (OfficialLetter $record) {
                $record->forceFill(['letter_status' => OfficialLetterStatus::Filed])->save();
                Notification::make()->success()->title('Letter filed')->send();
            })
            ->visible(fn (OfficialLetter $record) => $record->letter_status === OfficialLetterStatus::Released);
    }

    public static function qrPreviewAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('qr_preview')
            ->label('QR')->icon('heroicon-o-qr-code')->color('info')
            ->modalHeading('Verification QR')
            ->modalContent(fn (OfficialLetter $record) => new HtmlString(
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
            ->action(function (OfficialLetter $record, array $data) {
                $record->revoke($data['reason']);
                Notification::make()->danger()->title('Letter revoked')->send();
            })
            ->visible(fn (OfficialLetter $record) => $record->status === VerificationStatus::Valid);
    }
}
