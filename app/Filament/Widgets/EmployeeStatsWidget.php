<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = Employee::count();
        $active = Employee::where('employee_status', EmployeeStatus::Active->value)->count();
        $onLeave = Employee::where('employee_status', EmployeeStatus::OnLeave->value)->count();
        $inactive = Employee::whereIn('employee_status', [
            EmployeeStatus::Terminated->value,
            EmployeeStatus::Resigned->value,
            EmployeeStatus::Retired->value,
        ])->count();

        return [
            Stat::make('Employees', $total)
                ->description('total headcount')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Active', $active)
                ->description($total > 0 ? round(($active / $total) * 100) . '% of workforce' : '—')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('On Leave', $onLeave)
                ->descriptionIcon('heroicon-m-clock')
                ->color($onLeave > 0 ? 'warning' : 'gray'),

            Stat::make('Off-boarded', $inactive)
                ->description('terminated / resigned / retired')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('gray'),
        ];
    }
}
