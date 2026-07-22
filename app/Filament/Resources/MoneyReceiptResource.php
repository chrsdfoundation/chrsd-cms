<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\VerificationStatus;
use App\Filament\Resources\MoneyReceiptResource\Pages;
use App\Models\MoneyReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Milon\Barcode\DNS2D;

class MoneyReceiptResource extends Resource
{
    protected static ?string $model = MoneyReceipt::class;

    protected static ?string $cluster = \App\Filament\Clusters\Fundraising::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->serial_number . ' — ' . $record->payer_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_number', 'payer_name', 'purpose', 'reference_no'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Receipt Details')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('receipt_date')
                        ->required()->default(now())->native(false),

                    Forms\Components\Select::make('payment_method')
                        ->required()
                        ->options(PaymentMethod::class)
                        ->default(PaymentMethod::Cash->value),

                    Forms\Components\TextInput::make('payer_name')
                        ->label('Received from')
                        ->required()->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()->minValue(0.01)->step(0.01)
                        ->prefix('৳')
                        ->helperText('Amount in Bangladeshi Taka.'),

                    Forms\Components\TextInput::make('reference_no')
                        ->label('Reference / Txn ID')
                        ->maxLength(100)
                        ->helperText('Cheque no. / bKash TrxID / bank ref. — optional.'),

                    Forms\Components\Textarea::make('purpose')
                        ->label('On account of')
                        ->required()->rows(3)->maxLength(2000)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('received_by')
                        ->label('Received by (name & designation)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Verification')
                ->columns(2)
                ->visible(fn (?MoneyReceipt $record) => $record !== null)
                ->schema([
                    Forms\Components\TextInput::make('serial_number')
                        ->disabled()->dehydrated(false),
                    Forms\Components\Select::make('status')
                        ->options(VerificationStatus::class)
                        ->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('qr_code_uri')
                        ->label('QR verification URL')
                        ->disabled()->dehydrated(false)->columnSpanFull(),
                    Forms\Components\TextInput::make('verification_hash')
                        ->disabled()->dehydrated(false)->columnSpanFull(),
                    Forms\Components\Textarea::make('revocation_reason')
                        ->disabled()->dehydrated(false)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Receipt No.')
                    ->searchable()->sortable()->fontFamily('mono')->size('xs')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Date')->date('d M Y')->sortable(),

                Tables\Columns\TextColumn::make('payer_name')
                    ->label('Received From')->searchable()->limit(30),

                Tables\Columns\TextColumn::make('amount')
                    ->money('BDT')->sortable()->alignEnd(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')->badge(),

                Tables\Columns\TextColumn::make('purpose')
                    ->limit(40)->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Verify')->badge(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged')->since()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),
                Tables\Filters\SelectFilter::make('status')
                    ->options(VerificationStatus::class)
                    ->label('Verification'),
                Tables\Filters\Filter::make('receipt_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false),
                        Forms\Components\DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from']  ?? null, fn ($qq, $v) => $qq->whereDate('receipt_date', '>=', $v))
                        ->when($data['until'] ?? null, fn ($qq, $v) => $qq->whereDate('receipt_date', '<=', $v))
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                self::printAction(),
                self::qrPreviewAction(),
                self::revokeAction(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->label('More'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMoneyReceipts::route('/'),
            'create' => Pages\CreateMoneyReceipt::route('/create'),
            'view'   => Pages\ViewMoneyReceipt::route('/{record}'),
            'edit'   => Pages\EditMoneyReceipt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('creator');
    }

    // ---- Row actions ----------------------------------------------------

    public static function printAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('print')
            ->label('Print')->icon('heroicon-o-printer')->color('primary')
            ->url(fn (MoneyReceipt $record) => route('money-receipts.print', $record))
            ->openUrlInNewTab();
    }

    public static function qrPreviewAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('qr_preview')
            ->label('QR')->icon('heroicon-o-qr-code')->color('info')
            ->modalHeading('Verification QR')
            ->modalContent(fn (MoneyReceipt $record) => new HtmlString(
                '<div class="flex justify-center p-6">'
                . (new DNS2D())->getBarcodeSVG($record->qr_code_uri, 'QRCODE', 6, 6)
                . '</div>'
                . '<p class="text-center text-sm text-gray-600 break-all px-4 pb-4">'
                . e($record->qr_code_uri)
                . '</p>'
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    public static function revokeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('revoke')
            ->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
            ->form([
                Forms\Components\Textarea::make('reason')->required()
                    ->label('Reason for revocation')->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (MoneyReceipt $record, array $data) {
                $record->revoke($data['reason']);
                Notification::make()->danger()->title('Receipt revoked')->send();
            })
            ->visible(fn (MoneyReceipt $record) => $record->status === VerificationStatus::Valid);
    }
}
