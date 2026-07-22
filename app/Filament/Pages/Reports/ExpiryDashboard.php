<?php

namespace App\Filament\Pages\Reports;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\IdCard;
use App\Services\Documents\ExpiryScannerService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExpiryDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Document Expiry';

    protected static string $view = 'filament.pages.reports.expiry-dashboard';

    public ?array $data = [];

    /** @var array<string, mixed> */
    public array $preview = [];

    public function mount(): void
    {
        $this->form->fill(['window' => 30]);
        $this->refreshPreview();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filter')
                    ->schema([
                        Forms\Components\Select::make('window')
                            ->label('Show items expiring within')
                            ->options([
                                7  => 'Next 7 days',
                                30 => 'Next 30 days',
                                60 => 'Next 60 days',
                                90 => 'Next 90 days',
                            ])
                            ->default(30)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshPreview()),
                    ]),
            ])
            ->statePath('data');
    }

    public function refreshPreview(): void
    {
        $days = (int) ($this->data['window'] ?? 30);
        $today = Carbon::today();
        $end = $today->copy()->addDays($days);

        $expiring = collect()
            ->merge($this->collect(Certificate::query(), $today, $end, 'Certificate'))
            ->merge($this->collect(IdCard::query(), $today, $end, 'ID Card'))
            ->sortBy('valid_until')
            ->values();

        $expired = collect()
            ->merge($this->collectExpired(Certificate::query(), 'Certificate'))
            ->merge($this->collectExpired(IdCard::query(), 'ID Card'))
            ->sortByDesc('valid_until')
            ->values();

        $this->preview = [
            'expiring' => $expiring,
            'expired'  => $expired,
            'window'   => $days,
        ];
    }

    protected function collect($query, Carbon $today, Carbon $end, string $kindLabel): Collection
    {
        return $query
            ->acrossOrganizations()
            ->with('employee')
            ->where('status', VerificationStatus::Valid->value)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $end)
            ->orderBy('valid_until')
            ->get()
            ->map(fn ($doc) => $this->mapRow($doc, $kindLabel, $today));
    }

    protected function collectExpired($query, string $kindLabel): Collection
    {
        return $query
            ->acrossOrganizations()
            ->with('employee')
            ->where('status', VerificationStatus::Expired->value)
            ->orderByDesc('valid_until')
            ->limit(50)
            ->get()
            ->map(fn ($doc) => $this->mapRow($doc, $kindLabel, Carbon::today()));
    }

    protected function mapRow($doc, string $kindLabel, Carbon $today): array
    {
        $days = $doc->valid_until ? (int) $today->diffInDays($doc->valid_until, false) : null;

        return [
            'kind'                => $kindLabel,
            'serial'              => $doc->serial_number,
            'subject'             => optional($doc->employee)->full_name ?? '—',
            'email'               => optional($doc->employee)->email,
            'valid_until'         => $doc->valid_until,
            'days_left'           => $days,
            'expiry_notified_at'  => $doc->expiry_notified_at,
            'status'              => $doc->status,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('runScan')
                ->label('Run scan now')
                ->icon('heroicon-o-bell-alert')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Notifies employees and HR about expiring / expired documents and flips past-expiry rows to Expired. Safe to run any time — anti-spam guard prevents re-notification within 21 days.')
                ->action(function () {
                    $stats = app(ExpiryScannerService::class)->run();
                    Notification::make()
                        ->title('Scan complete')
                        ->body("Expiring: {$stats['expiring']} • Expired: {$stats['expired']}")
                        ->success()
                        ->send();
                    $this->refreshPreview();
                }),
        ];
    }
}
