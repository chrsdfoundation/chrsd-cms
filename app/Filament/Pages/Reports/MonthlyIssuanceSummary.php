<?php

namespace App\Filament\Pages\Reports;

use App\Services\Reports\ReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyIssuanceSummary extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Monthly Issuance Summary';

    protected static string $view = 'filament.pages.reports.monthly-issuance-summary';

    public ?array $data = [];

    public array $preview = [];

    public function mount(): void
    {
        $this->form->fill(['month' => now()->format('Y-m')]);
        $this->refreshPreview();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        $options = collect(range(0, 11))->mapWithKeys(function ($i) {
            $m = now()->subMonths($i);

            return [$m->format('Y-m') => $m->format('F Y')];
        })->all();

        return $form
            ->schema([
                Forms\Components\Section::make('Period')
                    ->schema([
                        Forms\Components\Select::make('month')
                            ->options($options)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshPreview()),
                    ]),
            ])
            ->statePath('data');
    }

    protected function currentMonth(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->data['month'] ?? now()->format('Y-m'))
            ->startOfMonth();
    }

    public function refreshPreview(): void
    {
        $this->preview = app(ReportService::class)->monthlyIssuance($this->currentMonth());
    }

    public function exportPdf(): StreamedResponse
    {
        return app(ReportService::class)->exportMonthlyIssuance($this->currentMonth());
    }
}
