<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Time & Attendance';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Attendance Details')->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'title')
                    ->getOptionLabelFromRecordUsing(fn($record) =>
                        ($record->title ?? 'Shift') . ' — ' . $record->start_datetime->format('M j, H:i')
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('No shift (ad-hoc)'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->native(false),
            ])->columns(2),

            SchemaComponents\Section::make('Clock Times')->schema([
                Forms\Components\DateTimePicker::make('clock_in_at')
                    ->label('Clock In')
                    ->native(false)
                    ->seconds(false)
                    ->nullable(),
                Forms\Components\DateTimePicker::make('clock_out_at')
                    ->label('Clock Out')
                    ->native(false)
                    ->seconds(false)
                    ->nullable()
                    ->after('clock_in_at'),
                Forms\Components\TextInput::make('break_minutes')
                    ->label('Break (minutes)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('min'),
                Forms\Components\TextInput::make('total_minutes')
                    ->label('Total Minutes Worked')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('min')
                    ->helperText('Leave 0 to calculate automatically from clock times.'),
                Forms\Components\TextInput::make('overtime_minutes')
                    ->label('Overtime Minutes')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('min'),
                Forms\Components\Select::make('status')
                    ->options([
                        'present'   => 'Present',
                        'late'      => 'Late',
                        'early_out' => 'Early Out',
                        'absent'    => 'Absent',
                        'no_show'   => 'No Show',
                    ])
                    ->default('present')
                    ->required(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('clock_in_at')
                    ->label('Clock In')
                    ->time('H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('clock_out_at')
                    ->label('Clock Out')
                    ->time('H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('break_minutes')
                    ->label('Break')
                    ->suffix(' min')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_minutes')
                    ->label('Total')
                    ->formatStateUsing(fn(int $state): string => sprintf('%dh %02dm', intdiv($state, 60), $state % 60))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('overtime_minutes')
                    ->label('OT')
                    ->formatStateUsing(fn(int $state): string => $state > 0 ? sprintf('%dh %02dm', intdiv($state, 60), $state % 60) : '—')
                    ->color(fn(int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'present'   => 'success',
                        'late'      => 'warning',
                        'early_out' => 'warning',
                        'absent'    => 'danger',
                        'no_show'   => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present'   => 'Present',
                        'late'      => 'Late',
                        'early_out' => 'Early Out',
                        'absent'    => 'Absent',
                        'no_show'   => 'No Show',
                    ]),
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn() => \App\Models\Location::pluck('name', 'id')->toArray()),
                Tables\Filters\Filter::make('missing_clock_out')
                    ->label('Missing Clock-Out')
                    ->query(fn($query) => $query->whereNotNull('clock_in_at')->whereNull('clock_out_at')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Attendance Record')->schema([
                Infolists\Components\TextEntry::make('user.name')
                    ->label('Employee')
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Infolists\Components\TextEntry::make('date')
                    ->date('l, M j, Y'),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'present'   => 'success',
                        'late'      => 'warning',
                        'early_out' => 'warning',
                        'absent'    => 'danger',
                        'no_show'   => 'danger',
                        default     => 'gray',
                    }),
                Infolists\Components\TextEntry::make('shift.start_datetime')
                    ->label('Scheduled Shift')
                    ->formatStateUsing(fn($state, $record) =>
                        $record->shift
                            ? $record->shift->start_datetime->format('H:i') . ' – ' . $record->shift->end_datetime->format('H:i')
                            : 'Ad-hoc'
                    )
                    ->placeholder('—'),
            ])->columns(3),

            SchemaComponents\Section::make('Clock Times')->schema([
                Infolists\Components\TextEntry::make('clock_in_at')
                    ->label('Clock In')
                    ->dateTime('H:i')
                    ->placeholder('Not recorded'),
                Infolists\Components\TextEntry::make('clock_out_at')
                    ->label('Clock Out')
                    ->dateTime('H:i')
                    ->placeholder('Not recorded'),
                Infolists\Components\TextEntry::make('break_minutes')
                    ->label('Break')
                    ->suffix(' min'),
                Infolists\Components\TextEntry::make('total_minutes')
                    ->label('Total Time Worked')
                    ->formatStateUsing(fn(int $state): string => sprintf('%dh %02dm', intdiv($state, 60), $state % 60)),
                Infolists\Components\TextEntry::make('overtime_minutes')
                    ->label('Overtime')
                    ->formatStateUsing(fn(int $state): string => $state > 0 ? sprintf('%dh %02dm', intdiv($state, 60), $state % 60) : 'None'),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view'   => Pages\ViewAttendance::route('/{record}'),
            'edit'   => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
