<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $cluster = \App\Filament\Clusters\Fundraising::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Campaign')->columns(2)->schema([
                Forms\Components\TextInput::make('code')->required()->maxLength(32)
                    ->helperText('Short unique code — e.g. FRESC-Q3-26'),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('goal_amount')->numeric()->minValue(0)->prefix('৳'),
                Forms\Components\Select::make('currency')->options(['BDT' => 'BDT', 'USD' => 'USD', 'EUR' => 'EUR'])->default('BDT'),
                Forms\Components\DatePicker::make('starts_on')->native(false),
                Forms\Components\DatePicker::make('ends_on')->native(false),
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->fontFamily('mono')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('goal_amount')->money('BDT')->alignEnd()->toggleable(),
                Tables\Columns\TextColumn::make('raised')
                    ->label('Raised')->money('BDT')->alignEnd()
                    ->state(fn (Campaign $r) => $r->raisedAmount()),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->state(fn (Campaign $r) => $r->progressPercent() !== null ? $r->progressPercent() . '%' : '—'),
                Tables\Columns\TextColumn::make('starts_on')->date()->toggleable(),
                Tables\Columns\TextColumn::make('ends_on')->date()->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit'   => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
