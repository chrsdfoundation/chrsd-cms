<?php

namespace App\Filament\Portal\Resources\MyRequestResource\Pages;

use App\Filament\Portal\Resources\MyRequestResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMyRequest extends ViewRecord
{
    protected static string $resource = MyRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Your request')->columns(2)->schema([
                TextEntry::make('type.name')->label('Certificate type'),
                TextEntry::make('created_at')->label('Submitted')->since(),
                TextEntry::make('purpose'),
                TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
            ]),

            Section::make('Review')->columns(2)
                ->visible(fn ($record) => $record->status?->value !== 'pending')
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('reviewed_at')->since()->placeholder('—'),
                    TextEntry::make('reviewedBy.name')->label('Reviewer'),
                    TextEntry::make('resultingCertificate.serial_number')
                        ->label('Issued certificate')->fontFamily('mono'),
                    TextEntry::make('review_notes')->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }
}
