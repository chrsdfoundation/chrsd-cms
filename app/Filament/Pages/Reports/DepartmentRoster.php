<?php

namespace App\Filament\Pages\Reports;

use App\Models\Department;
use App\Services\Reports\ReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Department Roster';

    protected static string $view = 'filament.pages.reports.department-roster';

    public ?array $data = [];

    public array $preview = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->refreshPreview();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filters')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(Department::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('— All departments —')
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshPreview()),
                    ]),
            ])
            ->statePath('data');
    }

    public function refreshPreview(): void
    {
        $this->preview = app(ReportService::class)
            ->departmentRoster($this->data['department_id'] ?? null);
    }

    public function exportPdf(): StreamedResponse
    {
        return app(ReportService::class)
            ->exportDepartmentRoster($this->data['department_id'] ?? null);
    }
}
