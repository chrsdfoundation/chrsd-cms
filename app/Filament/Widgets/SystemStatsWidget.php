<?php

namespace App\Filament\Widgets;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\OfficialLetter;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $users = User::query()->count();

        $employees = Employee::query()->acrossOrganizations()->count();

        $certsIssued = Certificate::query()->acrossOrganizations()
            ->where('status', VerificationStatus::Valid->value)
            ->count();

        $idsIssued = IdCard::query()->acrossOrganizations()
            ->where('status', VerificationStatus::Valid->value)
            ->count();

        $revocations = Certificate::query()->acrossOrganizations()
                           ->where('status', VerificationStatus::Revoked->value)->count()
                     + OfficialLetter::query()->acrossOrganizations()
                           ->where('status', VerificationStatus::Revoked->value)->count()
                     + Employee::query()->acrossOrganizations()
                           ->where('status', VerificationStatus::Revoked->value)->count();

        return [
            Stat::make('Total users', $users)
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Employees', $employees)
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Valid certificates', $certsIssued)
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Valid ID cards', $idsIssued)
                ->descriptionIcon('heroicon-m-identification')
                ->color('success'),

            Stat::make('Total revocations', $revocations)
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($revocations > 0 ? 'danger' : 'gray'),
        ];
    }
}
