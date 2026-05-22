<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Filament\Forms;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Components\Utilities\Get;

class ShiftResource extends Resource
{
    use ScopedToAuthCompany;

    const COMPANY_SCOPE = 'via_location';
    protected static ?string $model = Shift::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Shifts';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Shift Assignment')->schema([
                Forms\Components\Select::make('schedule_id')
                    ->label('Schedule')
                    ->relationship('schedule', 'week_start_date', fn($query) => $query->whereHas('location', fn($q) => $q->where('company_id', auth()->user()?->company_id)))
                    ->getOptionLabelFromRecordUsing(fn($record) => 'Week of ' . $record->week_start_date->format('M j, Y') . ' — ' . $record->location->name)
                    ->searchable()
                    ->preload()
                    ->placeholder('Ad-hoc (no schedule)')
                    ->columnSpanFull(),
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name', fn($query) => $query->where('company_id', auth()->user()?->company_id)->whereIn('role', ['admin', 'employee']))
                    ->searchable()
                    ->preload()
                    ->placeholder('Open shift (unassigned)'),
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name', fn($query) => $query->where('company_id', auth()->user()?->company_id))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name', fn($query) => $query->whereHas('location', fn($q) => $q->where('company_id', auth()->user()?->company_id)))
                    ->searchable()
                    ->preload()
                    ->placeholder('No department'),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder('Optional shift title'),
            ])->columns(2),

            SchemaComponents\Section::make('Shift Timing')->schema([
                Forms\Components\DateTimePicker::make('start_datetime')
                    ->label('Start')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->timezone(fn(Get $get): string => \App\Models\Location::find($get('location_id'))?->timezone ?? 'UTC'),
                Forms\Components\DateTimePicker::make('end_datetime')
                    ->label('End')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->after('start_datetime')
                    ->timezone(fn(Get $get): string => \App\Models\Location::find($get('location_id'))?->timezone ?? 'UTC'),
                Forms\Components\TextInput::make('break_duration_minutes')
                    ->label('Break (minutes)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('min'),
                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('scheduled')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Date')
                    ->formatStateUsing(fn(Shift $record): string =>
                        $record->start_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('M j, Y')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Time')
                    ->formatStateUsing(fn(Shift $record): string =>
                        $record->start_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('H:i')
                        . ' – ' .
                        $record->end_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('H:i')
                    ),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->placeholder('Open shift'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('break_duration_minutes')
                    ->label('Break')
                    ->suffix(' min')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'confirmed' => 'success',
                        'scheduled' => 'indigo',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'name', fn($query) => $query->where('company_id', auth()->user()?->company_id)),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn($query) => $query->whereDate('start_datetime', today())),
            ])
            ->actions([
                EditAction::make(),
                TableAction::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Shift $record): bool => $record->status === 'scheduled')
                    ->action(fn(Shift $record) => $record->update(['status' => 'confirmed'])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_datetime', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
