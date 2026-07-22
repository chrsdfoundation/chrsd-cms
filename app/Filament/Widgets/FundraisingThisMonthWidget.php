<?php

namespace App\Filament\Widgets;

use App\Enums\PledgeStatus;
use App\Models\Donation;
use App\Models\Pledge;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FundraisingThisMonthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $raisedThisMonth = (float) Donation::query()
            ->acrossOrganizations()
            ->whereMonth('receipt_date', now()->month)
            ->whereYear('receipt_date', now()->year)
            ->where('is_in_kind', false)
            ->sum('amount');

        $inKindThisMonth = (float) Donation::query()
            ->acrossOrganizations()
            ->whereMonth('receipt_date', now()->month)
            ->whereYear('receipt_date', now()->year)
            ->where('is_in_kind', true)
            ->sum('amount');

        $overduePledges = Pledge::query()
            ->acrossOrganizations()
            ->where(function ($q) {
                $q->where('status', PledgeStatus::Overdue->value)
                  ->orWhere(function ($qq) {
                      $qq->where('status', PledgeStatus::Open->value)
                         ->whereDate('due_date', '<', now()->toDateString());
                  });
            })
            ->count();

        $overdueValue = (float) Pledge::query()
            ->acrossOrganizations()
            ->where(function ($q) {
                $q->where('status', PledgeStatus::Overdue->value)
                  ->orWhere(function ($qq) {
                      $qq->where('status', PledgeStatus::Open->value)
                         ->whereDate('due_date', '<', now()->toDateString());
                  });
            })
            ->sum('promised_amount');

        return [
            Stat::make('Raised (MTD)', '৳ ' . number_format($raisedThisMonth, 2))
                ->description('Cash donations this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('In-kind (MTD)', '৳ ' . number_format($inKindThisMonth, 2))
                ->description('Fair-market value')
                ->descriptionIcon('heroicon-m-gift')
                ->color('info'),

            Stat::make('Overdue pledges', $overduePledges)
                ->description('৳ ' . number_format($overdueValue, 2) . ' outstanding')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overduePledges > 0 ? 'danger' : 'gray')
                ->url($overduePledges > 0 ? \App\Filament\Resources\PledgeResource::getUrl('index', ['tableFilters' => ['overdue' => ['isActive' => true]]]) : null),
        ];
    }
}
