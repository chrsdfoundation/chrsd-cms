<?php

namespace App\Filament\Portal\Resources\MyCertificateResource\Pages;

use App\Filament\Portal\Resources\MyCertificateResource;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMyCertificate extends ViewRecord
{
    protected static string $resource = MyCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')->icon('heroicon-o-arrow-down-tray')->color('primary')
                ->url(fn () => $this->getRecord()->getFirstMediaUrl('rendered'))
                ->openUrlInNewTab()
                ->visible(fn () => $this->getRecord()->hasMedia('rendered')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Certificate')->columns(2)->schema([
                TextEntry::make('serial_number')->label('Serial')->fontFamily('mono'),
                TextEntry::make('type.name')->label('Type'),
                TextEntry::make('purpose'),
                TextEntry::make('issuance_status')->badge(),
                TextEntry::make('issued_on')->date(),
                TextEntry::make('valid_until')->date()->placeholder('No expiry'),
                TextEntry::make('signedBy.full_name')->label('Signatory'),
            ]),

            Section::make('Verification')->columns(2)->schema([
                TextEntry::make('status')->badge(),
                TextEntry::make('verification_hash')->label('Hash')
                    ->fontFamily('mono')->size('xs')->columnSpanFull(),
                TextEntry::make('verify_url')
                    ->label('Public verify URL')
                    ->getStateUsing(fn ($record) => app(QrCodeService::class)->verificationUrl($record))
                    ->url(fn ($record) => app(QrCodeService::class)->verificationUrl($record), true)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
