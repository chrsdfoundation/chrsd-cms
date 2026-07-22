<?php

namespace App\Filament\Resources;

use App\Enums\DocumentTemplateType;
use App\Filament\Clusters\Documents;
use App\Filament\Resources\DocumentTemplateResource\Pages;
use App\Models\CertificateType;
use App\Models\DocumentTemplate;
use App\Models\IdCardType;
use App\Models\LetterCategory;
use App\Services\Documents\BrowsershotPdfService;
use App\Services\Documents\TemplateRenderer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTemplateResource extends Resource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static ?string $cluster = Documents::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Template')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(160),

                    Forms\Components\Select::make('document_type')
                        ->label('Document type')
                        ->options(DocumentTemplateType::class)
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('certificate_type_id')
                        ->label('Applies to certificate type')
                        ->options(fn () => CertificateType::query()->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('document_type') === DocumentTemplateType::Certificate->value)
                        ->helperText('Leave blank to make this template available for any certificate type.'),

                    Forms\Components\Select::make('letter_category_id')
                        ->label('Applies to letter category')
                        ->options(fn () => LetterCategory::query()->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('document_type') === DocumentTemplateType::Letter->value)
                        ->helperText('Leave blank to make this template available for any letter category.'),

                    Forms\Components\Select::make('id_card_type_id')
                        ->label('Applies to ID card type')
                        ->options(fn () => IdCardType::query()->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('document_type') === DocumentTemplateType::IdCard->value)
                        ->helperText('Employee / Volunteer / Visitor. Leave blank for a generic ID template.'),

                    Forms\Components\Placeholder::make('id_card_notice')
                        ->label('')
                        ->content('ID Card templates render the FRONT side only via this Markdown template. The back side stays code-driven (instructions + return address + signatory).')
                        ->visible(fn (Forms\Get $get) => $get('document_type') === DocumentTemplateType::IdCard->value),

                    Forms\Components\Select::make('orientation')
                        ->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])
                        ->default('portrait')
                        ->required(),

                    Forms\Components\Select::make('shell_variant')
                        ->label('Shell variant')
                        ->options([
                            'course-completion' => 'Course Completion (green wave)',
                            'course-completion-blue' => 'Course Completion (blue wave)',
                            'course-completion-usaid' => 'Course Completion (USAID / GlobalHealth)',
                        ])
                        ->placeholder('Default (branded frame + gold seal)')
                        ->visible(fn (Forms\Get $get) => $get('document_type') === DocumentTemplateType::Certificate->value)
                        ->helperText('Course-completion variants use a distinct layout. USAID variant matches the cream/gold + double-border certificate shell.'),

                    Forms\Components\Toggle::make('is_default')
                        ->label('Default template for this type')
                        ->helperText('One default per organization + document type is enforced by the picker.')
                        ->inline(false),
                ]),

            Forms\Components\Section::make('Body (Markdown)')
                ->description('Use double-braces for placeholders — e.g. {{name}}. Triple-braces render raw HTML: {{{qr_code}}}.')
                ->schema([
                    Forms\Components\Textarea::make('body_markdown')
                        ->label('Body')
                        ->required()
                        ->rows(18)
                        ->autosize()
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('placeholders_legend')
                        ->label('Available placeholders')
                        ->content(function () {
                            $rows = collect(TemplateRenderer::knownPlaceholders())
                                ->map(fn ($desc, $key) => "**{$key}** — {$desc}")
                                ->join("  \n");

                            return new HtmlString(Str::markdown($rows));
                        }),
                ]),

            Forms\Components\Section::make('Preview sample data (optional)')
                ->description('JSON object of sample values used when previewing this template. If empty, sensible defaults are used.')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('sample_context')
                        ->label('Sample context (JSON)')
                        ->rows(6)
                        ->helperText('Example: {"name": "Jane Doe", "event_name": "Volunteer Orientation 2026", "duration": "3 weeks"}')
                        ->columnSpanFull()
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                        ->dehydrateStateUsing(fn ($state) => static::decodeJson($state)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DocumentTemplateType ? $state->getLabel() : ucfirst((string) $state))
                    ->color(fn ($state) => $state instanceof DocumentTemplateType ? $state->getColor() : 'gray'),
                Tables\Columns\TextColumn::make('certificateType.name')->label('Cert type')->toggleable(),
                Tables\Columns\TextColumn::make('letterCategory.name')->label('Letter category')->toggleable(),
                Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')->options(DocumentTemplateType::class),
                Tables\Filters\TernaryFilter::make('is_default'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-eye')
                    ->action(fn (DocumentTemplate $record) => static::streamPreview($record)),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function streamPreview(DocumentTemplate $template): StreamedResponse
    {
        $context = static::previewContext($template);
        $renderer = app(TemplateRenderer::class);
        $html = $renderer->render($template, $context);

        // Puppeteer (Browsershot) — sidesteps DomPDF's GD requirement so this
        // works even where php.ini has GD disabled (which this deployment
        // does). Sizing mirrors the real generators:
        //   • ID cards → CR80 landscape, 85.6 × 54 mm
        //   • Letters  → A4, honouring template orientation, tight margins
        //     because letterhead is only stitched on real generation
        //   • Certificates / others → A4 with the template's orientation
        $orientation = $template->orientation === 'landscape' ? 'landscape' : 'portrait';
        $opts = [];
        if ($template->document_type === DocumentTemplateType::IdCard) {
            $opts['pageSize'] = ['width' => '85.6mm', 'height' => '54mm'];
            $opts['margin'] = ['top' => '2mm', 'right' => '2mm', 'bottom' => '2mm', 'left' => '2mm'];
        } else {
            // A4 in Puppeteer via width/height so we control orientation
            // explicitly (Chromium's format:'A4' with `landscape:true` is
            // available too, but pageSize keeps this consistent with the ID
            // card branch).
            $a4 = ['210mm', '297mm'];
            $opts['pageSize'] = $orientation === 'landscape'
                ? ['width' => $a4[1], 'height' => $a4[0]]
                : ['width' => $a4[0], 'height' => $a4[1]];
            $opts['margin'] = ['top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm'];
        }

        $bytes = app(BrowsershotPdfService::class)->render($html, $opts);
        $filename = sprintf('preview-%s.pdf', Str::slug($template->name));

        return response()->streamDownload(fn () => print ($bytes), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** Build a preview context: user-supplied sample_context merged over sensible defaults. */
    public static function previewContext(DocumentTemplate $template): array
    {
        $defaults = [
            'name' => 'Jane Doe',
            'designation' => 'Volunteer Officer',
            'organization' => config('app.name'),
            'certificate_number' => 'PREVIEW-2026-000001',
            'letter_reference' => 'PREVIEW-2026-000001',
            'date' => now()->toFormattedDateString(),
            'issue_date' => now()->toFormattedDateString(),
            'event_name' => 'Volunteer Orientation 2026',
            'position' => 'Field Officer',
            'duration' => '3 weeks',
            'verification_url' => url('/verify/PREVIEW'),
            'qr_code' => '<div style="width:60px;height:60px;background:#ddd;text-align:center;line-height:60px;">QR</div>',
            'subject' => 'Preview subject',
            'body' => 'This is the letter body.',
            'recipient_name' => 'Jane Doe',
            'recipient_title' => 'Volunteer',
            'recipient_address' => '123 Sample Road, Dhaka',
            'blood_group' => 'O+',
            'nationality' => 'Bangladeshi',
            'program_name' => 'Community Outreach',
            'valid_from' => now()->toFormattedDateString(),
            'valid_until' => now()->addYears(2)->toFormattedDateString(),
            'signatory_name' => 'A. Rauf',
            'signatory_title' => 'Executive Director',
            'signatory_left' => '',
            'signatory_right' => '',
            'department' => 'Programmes',
            'employee_id' => 'CHRSD-EMP-2026-0007',
            'course_name' => 'M&E Fundamentals',
            'verify_code' => 'PREVIEW-CODE',
            'issuer_name' => 'CHRSD LEARNING',
            'issuer_tagline' => 'Centre for Humanitarian Research',
        ];

        $sample = $template->sample_context ?? [];
        if (! is_array($sample)) {
            $sample = [];
        }

        return array_replace($defaults, $sample);
    }

    protected static function decodeJson(mixed $state): ?array
    {
        if (is_array($state)) {
            return $state;
        }
        if (! is_string($state) || trim($state) === '') {
            return null;
        }
        $decoded = json_decode($state, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'hr_manager']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentTemplates::route('/'),
            'create' => Pages\CreateDocumentTemplate::route('/create'),
            'view' => Pages\ViewDocumentTemplate::route('/{record}'),
            'edit' => Pages\EditDocumentTemplate::route('/{record}/edit'),
        ];
    }
}
