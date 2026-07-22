<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Enums\EmploymentEventType;
use App\Models\EmploymentEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $recordTitleAttribute = 'event_type';

    protected static ?string $title = 'Employment History';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('event_type')
                ->options([EmploymentEventType::Note->value => EmploymentEventType::Note->getLabel()])
                ->default(EmploymentEventType::Note->value)
                ->required()
                ->helperText('Only manual "Note" entries via this form. Promotions, transfers, and contract changes are captured automatically when you edit the employee.'),
            Forms\Components\DatePicker::make('occurred_on')
                ->required()->native(false)->default(now()),
            Forms\Components\Textarea::make('notes')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                Tables\Columns\TextColumn::make('occurred_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('event_type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('summary')
                    ->wrap()
                    ->getStateUsing(fn (EmploymentEvent $r) => $r->summary),
                Tables\Columns\TextColumn::make('notes')->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('recordedBy.name')->label('Recorded by')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add note')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['recorded_by_user_id'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->form([
                    Forms\Components\TextInput::make('event_type')->disabled(),
                    Forms\Components\DatePicker::make('occurred_on')->disabled()->native(false),
                    Forms\Components\Textarea::make('notes')->disabled(),
                    Forms\Components\KeyValue::make('previous_state')->disabled(),
                    Forms\Components\KeyValue::make('new_state')->disabled(),
                ]),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmploymentEvent $r) => $r->event_type === EmploymentEventType::Note),
            ])
            ->defaultSort('occurred_on', 'desc');
    }
}
