<?php

namespace App\Filament\Widgets;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\IdCard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpiringDocumentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $in7 = $today->copy()->addDays(7);
        $in30 = $today->copy()->addDays(30);

        $count = function (string $model, $from, $to) {
            return $model::query()
                ->where('status', VerificationStatus::Valid->value)
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '>=', $from)
                ->whereDate('valid_until', '<=', $to)
                ->count();
        };

        $urgent = $count(Certificate::class, $today, $in7) + $count(IdCard::class, $today, $in7);
        $soon = $count(Certificate::class, $in7, $in30) + $count(IdCard::class, $in7, $in30);
        $expired = Certificate::where('status', VerificationStatus::Expired->value)->count()
                 + IdCard::where('status', VerificationStatus::Expired->value)->count();

        return [
            Stat::make('Expiring within 7 days', $urgent)
                ->description('certificates + ID cards')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgent > 0 ? 'warning' : 'gray'),

            Stat::make('Expiring within 30 days', $soon)
                ->description('early-warning window')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Expired', $expired)
                ->description('past valid_until, status flipped')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($expired > 0 ? 'danger' : 'gray'),
        ];
    }
}
