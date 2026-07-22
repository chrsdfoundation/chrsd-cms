<?php

namespace App\Filament\Widgets;

use App\Enums\CertificateIssuance;
use App\Enums\OfficialLetterStatus;
use App\Models\Certificate;
use App\Models\OfficialLetter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class IssuanceTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Document Issuance — last 12 months';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn ($n) => Carbon::now()->subMonths($n)->startOfMonth());

        $certLabels = $months->map(fn ($m) => $m->format('M Y'))->all();

        $certs = $months->map(function (Carbon $m) {
            return Certificate::query()
                ->whereBetween('issued_on', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                ->whereIn('issuance_status', [
                    CertificateIssuance::Issued->value,
                    CertificateIssuance::Delivered->value,
                    CertificateIssuance::Generated->value,
                ])
                ->count();
        })->all();

        $letters = $months->map(function (Carbon $m) {
            return OfficialLetter::query()
                ->whereBetween('released_on', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                ->where('letter_status', OfficialLetterStatus::Released->value)
                ->count();
        })->all();

        return [
            'datasets' => [
                [
                    'label'           => 'Certificates',
                    'data'            => $certs,
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
                [
                    'label'           => 'Official Letters',
                    'data'            => $letters,
                    'borderColor'     => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $certLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
