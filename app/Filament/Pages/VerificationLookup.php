<?php

namespace App\Filament\Pages;

use App\Services\QrCodeService;
use App\Services\Verification\VerificationService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class VerificationLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Verification Lookup';

    protected static string $view = 'filament.pages.verification-lookup';

    public ?array $data = [];

    public ?Model $resolved = null;

    public ?string $qrSvg = null;

    public ?string $publicUrl = null;

    public ?string $notFoundQuery = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Search')
                    ->description('Enter a serial number (EMP-2026-000123) or a 64-character verification hash.')
                    ->schema([
                        Forms\Components\TextInput::make('query')
                            ->label('Serial or Hash')
                            ->required()
                            ->autofocus()
                            ->placeholder('EMP-2026-000123 or a 64-char hex hash')
                            ->maxLength(128),
                    ]),
            ])
            ->statePath('data');
    }

    public function lookup(): void
    {
        $data = $this->form->getState();
        $query = trim($data['query']);

        $this->resolved = null;
        $this->qrSvg = null;
        $this->publicUrl = null;
        $this->notFoundQuery = null;

        $verifier = app(VerificationService::class);

        // 64-char hex → hash path
        if (strlen($query) === 64 && ctype_xdigit($query)) {
            $this->resolved = $verifier->resolve($query);
        } else {
            // Serial path — scan each registered verifiable, across all organizations
            // so lookups work even when the user is in a different tenant.
            foreach ((new VerificationService)->registry() as $prefix => $class) {
                $this->resolved = $class::query()->acrossOrganizations()
                    ->where('serial_number', $query)->first();
                if ($this->resolved) {
                    break;
                }
            }
        }

        if (! $this->resolved) {
            $this->notFoundQuery = $query;
            Notification::make()
                ->title('No matching document')
                ->body('No employee, certificate, or letter matched your query.')
                ->warning()->send();

            return;
        }

        $qr = app(QrCodeService::class);
        $this->qrSvg = $qr->svg($this->resolved, 5);
        $this->publicUrl = $qr->verificationUrl($this->resolved);

        Notification::make()
            ->title('Resolved')
            ->body(class_basename($this->resolved) . ' ' . $this->resolved->serial_number)
            ->success()->send();
    }
}
