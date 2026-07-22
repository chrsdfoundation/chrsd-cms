<?php

namespace App\Filament\Portal\Resources;

use App\Enums\CertificateRequestStatus;
use App\Filament\Portal\Resources\MyRequestResource\Pages;
use App\Models\CertificateRequest;
use App\Models\CertificateType;
use App\Services\Documents\CertificateRequestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyRequestResource extends Resource
{
    protected static ?string $model = CertificateRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Request a Certificate';

    protected static ?string $modelLabel = 'request';

    protected static ?string $pluralModelLabel = 'my requests';

    protected static ?string $slug = 'my-requests';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', Auth::user()?->employee_id ?? 0);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request details')
                ->schema([
                    Forms\Components\Select::make('certificate_type_id')
                        ->label('Certificate type')
                        ->options(CertificateType::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()->required(),
                    Forms\Components\TextInput::make('purpose')->required()->maxLength(255)
                        ->helperText('What is this certificate for? (e.g. Bank loan, Visa application, Personal record)'),
                    Forms\Components\Textarea::make('notes')->rows(3)
                        ->helperText('Any additional context HR should know about.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->since()->sortable(),
                Tables\Columns\TextColumn::make('type.code')->label('Type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('purpose')->limit(40),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('reviewed_at')->label('Reviewed')->since()->placeholder('—'),
                Tables\Columns\TextColumn::make('resultingCertificate.serial_number')
                    ->label('Issued as')->fontFamily('mono')->size('xs')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CertificateRequestStatus::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Request')
                    ->using(function (array $data): CertificateRequest {
                        $data['employee_id'] = Auth::user()->employee_id;
                        $req = app(CertificateRequestService::class)->submit($data);
                        Notification::make()
                            ->success()
                            ->title('Request submitted')
                            ->body('HR has been notified and will review your request.')
                            ->send();

                        return $req;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (CertificateRequest $r) => $r->isPending()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyRequests::route('/'),
            'view' => Pages\ViewMyRequest::route('/{record}'),
        ];
    }
}
