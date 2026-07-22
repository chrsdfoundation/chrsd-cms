<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\MyCertificateResource\Pages;
use App\Models\Certificate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyCertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'My Certificates';

    protected static ?string $modelLabel = 'certificate';

    protected static ?string $pluralModelLabel = 'certificates';

    protected static ?string $slug = 'my-certificates';

    protected static ?int $navigationSort = 2;

    /**
     * Scope the entire resource to the logged-in employee. Everything downstream
     * — table, view page, action visibility — inherits this filter, so there's
     * no path by which an employee sees a sibling's certificate.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', Auth::user()?->employee_id ?? 0);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('Serial')
                    ->fontFamily('mono')->size('xs')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('type.name')->label('Type')->searchable(),
                Tables\Columns\TextColumn::make('purpose')->limit(40),
                Tables\Columns\TextColumn::make('issuance_status')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Verify')->badge(),
                Tables\Columns\TextColumn::make('issued_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('valid_until')->date()->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')->icon('heroicon-o-arrow-down-tray')->color('primary')
                    ->url(fn (Certificate $r) => $r->getFirstMediaUrl('rendered'))
                    ->openUrlInNewTab()
                    ->visible(fn (Certificate $r) => $r->hasMedia('rendered')),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyCertificates::route('/'),
            'view' => Pages\ViewMyCertificate::route('/{record}'),
        ];
    }
}
