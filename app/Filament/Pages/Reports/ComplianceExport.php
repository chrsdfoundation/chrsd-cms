<?php

namespace App\Filament\Pages\Reports;

use App\Services\Reports\ReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceExport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Compliance Bundle';

    protected static string $view = 'filament.pages.reports.compliance-export';

    public ?array $data = [];

    public array $preview = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->subDays(90)->toDateString(),
            'to'   => now()->toDateString(),
        ]);
        $this->refreshPreview();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Period')
                    ->description('Chain-of-custody bundle: activity log, revocations, and every verifiable document\'s current status + hash. HMAC-SHA256 manifest is appended.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('from')
                            ->required()->native(false)
                            ->live()->afterStateUpdated(fn () => $this->refreshPreview()),
                        Forms\Components\DatePicker::make('to')
                            ->required()->native(false)
                            ->live()->afterStateUpdated(fn () => $this->refreshPreview()),
                    ]),
            ])
            ->statePath('data');
    }

    protected function range(): array
    {
        return [
            Carbon::parse($this->data['from'] ?? now()->subDays(90)),
            Carbon::parse($this->data['to']   ?? now()),
        ];
    }

    public function refreshPreview(): void
    {
        [$from, $to] = $this->range();
        $this->preview = app(ReportService::class)->complianceBundle($from, $to);
    }

    public function exportPdf(): StreamedResponse
    {
        [$from, $to] = $this->range();
        return app(ReportService::class)->exportComplianceBundle($from, $to);
    }
}
