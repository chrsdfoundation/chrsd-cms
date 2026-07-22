<?php

namespace App\Filament\Pages\Reports;

use App\Services\Reports\ReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevocationRegister extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Revocation Register';

    protected static string $view = 'filament.pages.reports.revocation-register';

    public ?array $data = [];

    public array $preview = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
        ]);
        $this->refreshPreview();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Date range')
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
            Carbon::parse($this->data['from'] ?? now()->subDays(30)),
            Carbon::parse($this->data['to'] ?? now()),
        ];
    }

    public function refreshPreview(): void
    {
        [$from, $to] = $this->range();
        $this->preview = app(ReportService::class)->revocationRegister($from, $to);
    }

    public function exportPdf(): StreamedResponse
    {
        [$from, $to] = $this->range();

        return app(ReportService::class)->exportRevocationRegister($from, $to);
    }
}
