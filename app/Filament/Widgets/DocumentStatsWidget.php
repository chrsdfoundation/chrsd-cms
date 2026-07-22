<?php

namespace App\Filament\Widgets;

use App\Enums\OfficialLetterStatus;
use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\OfficialLetter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $certsThisMonth = Certificate::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lettersReleased = OfficialLetter::where('letter_status', OfficialLetterStatus::Released->value)->count();

        $revoked = Certificate::where('status', VerificationStatus::Revoked->value)->count()
            + OfficialLetter::where('status', VerificationStatus::Revoked->value)->count()
            + Employee::where('status', VerificationStatus::Revoked->value)->count();

        return [
            Stat::make('Certificates (MTD)', $certsThisMonth)
                ->description('drafted or issued this month')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Letters Released', $lettersReleased)
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),

            Stat::make('Revocations', $revoked)
                ->description('across all verifiable documents')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($revoked > 0 ? 'danger' : 'gray'),
        ];
    }
}
