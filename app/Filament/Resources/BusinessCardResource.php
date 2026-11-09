<?php

namespace App\Filament\Resources;

use App\Enums\BusinessCardStatus;
use App\Filament\Clusters\Documents;
use App\Filament\Resources\BusinessCardResource\Pages;
use App\Models\BusinessCard;
use App\Models\BusinessCardExport;
use App\Services\BusinessCard\BusinessCardApprover;
use App\Services\BusinessCard\BusinessCardExporter;
use App\Services\BusinessCard\CardExportBlockedException;
use App\Services\BusinessCard\CardPreviewData;
use App\Services\BusinessCard\LinkedInNormaliser;
use App\Services\BusinessCard\PhoneNumberNormaliser;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class BusinessCardResource extends Resource
{
    protected static ?string $model = BusinessCard::class;

    protected static ?string $cluster = Documents::class;

    protected static ?string $slug = 'business-cards';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 26;

    protected static ?string $recordTitleAttribute = 'person_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['person_name', 'full_name', 'company_name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('organization_id')
                ->default(fn () => config('chrsd.org_id') ?? 1)
                ->dehydrated(true),

            Forms\Components\Section::make('Person')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('person_name')
                        ->label('Name on the card')
                        ->required()->maxLength(160)->live(onBlur: true)
                        ->placeholder('e.g. Mohammed Abu Ramim'),
                    Forms\Components\TextInput::make('person_title')
                        ->label('Title')
                        ->maxLength(128)->live(onBlur: true)
                        ->placeholder('e.g. Executive Director'),
                    Forms\Components\Select::make('qr_profile')
                        ->label('QR profile')
                        ->options([
                            'compact' => 'Compact (office, mobile, work email, website)',
                            'full' => 'Full (everything printed, plus WhatsApp and address)',
                        ])
                        ->default('compact')->required()->live(),
                ]),

            Forms\Components\Section::make('Phones')
                ->description('Office is printed first and is the work number in the QR. Numbers are checked for Bangladesh (+880).')
                ->schema([
                    Forms\Components\Repeater::make('phones')
                        ->hiddenLabel()
                        ->live()
                        ->minItems(1)->maxItems(6)->defaultItems(1)
                        ->addActionLabel('Add phone')
                        ->columns(2)
                        ->formatStateUsing(fn ($state) => collect($state ?? [])->map(fn ($p) => [
                            'type' => $p['type'] ?? 'office',
                            'number' => $p['display'] ?? ($p['number_e164'] ?? ''),
                        ])->values()->all())
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options(['office' => 'Office', 'mobile' => 'Mobile', 'whatsapp' => 'WhatsApp'])
                                ->required(),
                            Forms\Components\TextInput::make('number')
                                ->tel()->required()->maxLength(32)
                                ->placeholder('+880 1602-778984'),
                        ]),
                ]),

            Forms\Components\Section::make('Email')
                ->schema([
                    Forms\Components\Repeater::make('emails')
                        ->hiddenLabel()
                        ->live()
                        ->maxItems(4)->defaultItems(1)
                        ->addActionLabel('Add email')
                        ->columns(2)
                        ->formatStateUsing(fn ($state) => collect($state ?? [])->map(fn ($e) => [
                            'type' => $e['type'] ?? 'work',
                            'address' => $e['address'] ?? '',
                        ])->values()->all())
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options(['work' => 'Work', 'personal' => 'Personal'])
                                ->required(),
                            Forms\Components\TextInput::make('address')
                                ->email()->required()->maxLength(255)
                                ->placeholder('name@chrsd.org'),
                        ]),
                ]),

            Forms\Components\Section::make('Web and LinkedIn')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('website')
                        ->url()->maxLength(255)->live(onBlur: true)
                        ->default('https://chrsd.org'),
                    Forms\Components\TextInput::make('linkedin_handle')
                        ->label('LinkedIn')
                        ->maxLength(255)->live(onBlur: true)
                        ->formatStateUsing(fn (?string $state) => $state ? 'https://www.linkedin.com/in/' . $state : null)
                        ->placeholder('maramim or https://www.linkedin.com/in/maramim')
                        ->helperText('A handle or a profile URL. Saved as one clean profile link.'),
                ]),

            Forms\Components\Section::make('Address')
                ->description('Up to three lines.')
                ->schema([
                    Forms\Components\Repeater::make('address_lines')
                        ->hiddenLabel()
                        ->live()
                        ->maxItems(3)->defaultItems(1)
                        ->addActionLabel('Add line')
                        ->formatStateUsing(fn ($state) => collect($state ?? [])->map(fn ($l) => ['line' => (string) $l])->values()->all())
                        ->schema([
                            Forms\Components\TextInput::make('line')->required()->maxLength(120),
                        ]),
                ]),

            Forms\Components\Section::make('Preview')
                ->description('Live. This is what the artwork will print, using the same layout the PDF is drawn from.')
                ->schema([
                    Forms\Components\Toggle::make('preview_foil')
                        ->label('Foil plate view (black = foil)')
                        ->dehydrated(false)->live(),
                    Forms\Components\Toggle::make('guide_bleed')->label('Bleed')->default(true)->dehydrated(false)->live(),
                    Forms\Components\Toggle::make('guide_trim')->label('Trim')->default(true)->dehydrated(false)->live(),
                    Forms\Components\Toggle::make('guide_safe')->label('Safe area')->default(false)->dehydrated(false)->live(),
                    Placeholder::make('preview')
                        ->hiddenLabel()
                        ->content(fn (Get $get) => new HtmlString(CardPreviewData::html(self::previewState($get)))),
                ]),

            Forms\Components\Section::make('Logo')
                ->collapsed()
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('logo')
                        ->collection('logo')
                        ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(4096)
                        ->helperText('The card prints the official emblem. This upload is kept for the legacy print page only.'),
                ]),

            Forms\Components\Section::make('Notes')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3)->maxLength(2000),
                ]),
        ]);
    }

    /**
     * The form's current state, for the live preview. Filament's Get does not expose the whole
     * form, so the fields the preview uses are read one by one.
     *
     * @return array<string, mixed>
     */
    private static function previewState(Get $get): array
    {
        $keys = ['person_name', 'person_title', 'qr_profile', 'phones', 'emails', 'website', 'linkedin_handle',
            'address_lines', 'preview_foil', 'guide_bleed', 'guide_trim', 'guide_safe'];

        $state = [];
        foreach ($keys as $key) {
            $state[$key] = $get($key);
        }

        return $state;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('person_name')
                    ->label('Name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('person_title')
                    ->label('Title')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof BusinessCardStatus ? $state->label() : (string) $state)
                    ->color(fn ($state) => $state instanceof BusinessCardStatus ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('qr_profile')
                    ->label('QR')->badge()->color('info')->toggleable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(BusinessCardStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                Tables\Filters\SelectFilter::make('qr_profile')
                    ->options(['compact' => 'Compact', 'full' => 'Full']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning'),
                self::exportAction(BusinessCardExport::KIND_ARTWORK),
                self::exportAction(BusinessCardExport::KIND_FOIL_PLATE),
                self::approveAction(),
                self::sentToPressAction(),
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

    public static function exportAction(string $kind): Tables\Actions\Action
    {
        $artwork = $kind === BusinessCardExport::KIND_ARTWORK;

        return Tables\Actions\Action::make('export_' . $kind)
            ->label($artwork ? 'Artwork PDF' : 'Foil plate PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->form([
                Forms\Components\Checkbox::make('acknowledge_qr')
                    ->label('I accept the QR size warning (only needed when the preview shows one)')
                    ->helperText('The acknowledgement is stored with the export.'),
            ])
            ->action(function (BusinessCard $record, array $data) use ($kind) {
                try {
                    $export = app(BusinessCardExporter::class)->export($record, $kind, auth()->user(), (bool) ($data['acknowledge_qr'] ?? false));
                } catch (CardExportBlockedException $e) {
                    Notification::make()->danger()->title('Export refused')
                        ->body(implode("\n", array_map(fn (array $i) => '• ' . $i['message'], $e->issues)))
                        ->persistent()->send();

                    return null;
                }

                return redirect()->route('business-cards.exports.download', $export);
            })
            ->visible(fn (BusinessCard $record) => auth()->user()?->can('view', $record) ?? false);
    }

    public static function approveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approve')
            ->label('Approve for press')
            ->icon('heroicon-o-check-badge')
            ->color('primary')
            ->requiresConfirmation()
            ->modalDescription('Approval covers the file exported for the current card data. Any later edit needs a new export and a new approval.')
            ->action(function (BusinessCard $record) {
                try {
                    app(BusinessCardApprover::class)->approve($record, auth()->user());
                    Notification::make()->success()->title('Approved for press')->send();
                } catch (CardExportBlockedException $e) {
                    Notification::make()->danger()->title('Cannot approve')
                        ->body(implode("\n", array_map(fn (array $i) => '• ' . $i['message'], $e->issues)))
                        ->persistent()->send();
                } catch (\RuntimeException $e) {
                    Notification::make()->danger()->title('Cannot approve')->body($e->getMessage())->persistent()->send();
                }
            })
            ->visible(fn (BusinessCard $record) => auth()->user()?->can('approve', $record) && $record->status !== BusinessCardStatus::SentToPress && $record->status !== BusinessCardStatus::Approved);
    }

    public static function sentToPressAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('sent_to_press')
            ->label('Mark sent to press')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->action(function (BusinessCard $record) {
                try {
                    app(BusinessCardApprover::class)->markSentToPress($record);
                    Notification::make()->success()->title('Marked as sent to press')->send();
                } catch (\RuntimeException $e) {
                    Notification::make()->danger()->title('Cannot mark')->body($e->getMessage())->send();
                }
            })
            ->visible(fn (BusinessCard $record) => auth()->user()?->can('approve', $record) && $record->status === BusinessCardStatus::Approved);
    }

    /**
     * Normalise and validate form input into the stored shape, and mirror the legacy
     * columns that the old print page reads. Invalid input is reported on its field.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareData(array $data): array
    {
        $phones = app(PhoneNumberNormaliser::class);
        $linkedIn = app(LinkedInNormaliser::class);

        $stored = [];
        foreach (($data['phones'] ?? []) as $i => $row) {
            try {
                $n = $phones->normalise((string) ($row['number'] ?? ''));
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(["data.phones.{$i}.number" => $e->getMessage()]);
            }
            $stored[] = ['type' => $row['type'], 'number_e164' => $n['e164'], 'display' => $n['display']];
        }

        $emails = [];
        foreach (($data['emails'] ?? []) as $row) {
            $emails[] = ['type' => $row['type'], 'address' => trim((string) $row['address'])];
        }

        $addressLines = array_values(array_filter(array_map(
            fn ($row) => trim((string) ($row['line'] ?? '')),
            $data['address_lines'] ?? [],
        ), fn ($line) => $line !== ''));

        $linkedInUrl = null;
        if (filled($data['linkedin_handle'] ?? null)) {
            try {
                $linkedInUrl = $linkedIn->normalise((string) $data['linkedin_handle']);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['data.linkedin_handle' => $e->getMessage()]);
            }
        }

        $office = collect($stored)->firstWhere('type', 'office');
        $mobile = collect($stored)->firstWhere('type', 'mobile');
        $whatsapp = collect($stored)->firstWhere('type', 'whatsapp');
        $workEmail = collect($emails)->firstWhere('type', 'work');
        $personalEmail = collect($emails)->firstWhere('type', 'personal');

        return array_merge($data, [
            'phones' => $stored,
            'emails' => $emails,
            'address_lines' => array_slice($addressLines, 0, 3),
            'linkedin_handle' => $linkedInUrl !== null ? $linkedIn->handleOf($linkedInUrl) : null,
            // Legacy columns, read by the old print page and the search index.
            'full_name' => $data['person_name'],
            'job_title' => $data['person_title'] ?? null,
            'phone' => $office['display'] ?? null,
            'mobile' => $mobile['display'] ?? null,
            'whatsapp' => $whatsapp['display'] ?? null,
            'work_email' => $workEmail['address'] ?? null,
            'personal_email' => $personalEmail['address'] ?? null,
            'linkedin' => $linkedInUrl,
            'address' => implode(', ', $addressLines),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessCards::route('/'),
            'create' => Pages\CreateBusinessCard::route('/create'),
            'edit' => Pages\EditBusinessCard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** The legacy browser print page. The executive card prints from its own export. */
    public static function printAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('print')
            ->label('Legacy print')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn ($record) => route('print.business-card', $record), shouldOpenInNewTab: true);
    }

    public static function printHeaderAction(): Action
    {
        return Action::make('print')
            ->label('Legacy print')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn ($record) => route('print.business-card', $record), shouldOpenInNewTab: true);
    }
}
