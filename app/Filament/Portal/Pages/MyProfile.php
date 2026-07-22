<?php

namespace App\Filament\Portal\Pages;

use App\Models\Employee;
use App\Services\Reports\ReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My Profile';

    protected static ?string $slug = 'me';

    protected static string $view = 'filament.portal.pages.my-profile';

    public ?Employee $employee = null;

    public function mount(): void
    {
        $this->employee = Auth::user()?->employee?->loadMissing([
            'department', 'position', 'supervisor',
            'history' => fn ($q) => $q->orderBy('occurred_on'),
        ]);

        abort_unless($this->employee, 403, 'No employee record linked to this account.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('service_record')
                ->label('Download Service Record')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(fn (): StreamedResponse =>
                    app(ReportService::class)->exportServiceRecord($this->employee)
                ),
        ];
    }
}
