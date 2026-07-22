<?php

namespace App\Filament\Resources;

use App\Enums\EmploymentEventType;
use App\Filament\Resources\EmploymentEventResource\Pages;
use App\Models\Employee;
use App\Models\EmploymentEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmploymentEventResource extends Resource
{
    protected static ?string $model = EmploymentEvent::class;

    protected static ?string $cluster = \App\Filament\Clusters\OrgUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Employment Events';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'last_name', fn (Builder $query) => $query->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Employee $r) => $r->full_name . ' — ' . $r->serial_number)
                    ->searchable(['first_name', 'last_name', 'email', 'serial_number'])
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\Select::make('event_type')
                    ->options(EmploymentEventType::class)
                    ->required(),
                Forms\Components\DatePicker::make('occurred_on')
                    ->required()->native(false),
                Forms\Components\Textarea::make('notes')
                    ->rows(3)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('State snapshot')
                ->description('Field values before and after this event. Populated automatically for observer-generated events; editable for manual "Note" entries.')
                ->collapsed()
                ->schema([
                    Forms\Components\KeyValue::make('previous_state')->columnSpanFull(),
                    Forms\Components\KeyValue::make('new_state')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')
                    ->searchable(['employee.first_name', 'employee.last_name'])->sortable(),
                Tables\Columns\TextColumn::make('employee.serial_number')->label('Serial')
                    ->fontFamily('mono')->size('xs')->toggleable(),
                Tables\Columns\TextColumn::make('event_type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('summary')
                    ->wrap()
                    ->getStateUsing(fn (EmploymentEvent $r) => $r->summary)
                    ->searchable(false),
                Tables\Columns\TextColumn::make('recordedBy.name')->label('Recorded by')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')->options(EmploymentEventType::class),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'last_name')
                    ->searchable()->preload()->label('Employee'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (EmploymentEvent $r) => $r->event_type === EmploymentEventType::Note),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmploymentEvent $r) => $r->event_type === EmploymentEventType::Note),
            ])
            ->defaultSort('occurred_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmploymentEvents::route('/'),
            'create' => Pages\CreateEmploymentEvent::route('/create'),
            'view'   => Pages\ViewEmploymentEvent::route('/{record}'),
            'edit'   => Pages\EditEmploymentEvent::route('/{record}/edit'),
        ];
    }
}
