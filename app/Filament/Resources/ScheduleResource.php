<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class ScheduleResource extends Resource
{
    use ScopedToAuthCompany;

    const COMPANY_SCOPE = 'via_location';
    protected static ?string $model = Schedule::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Schedules';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make()->schema([
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name', fn($query) => $query->where('company_id', auth()->user()?->company_id))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                        'archived'  => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\DatePicker::make('week_start_date')
                    ->label('Week Starting')
                    ->native(false)
                    ->required()
                    ->default(fn() => Carbon::now()->startOfWeek()->toDateString())
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $set('week_end_date', Carbon::parse($state)->endOfWeek()->toDateString());
                        }
                    }),
                Forms\Components\DatePicker::make('week_end_date')
                    ->label('Week Ending')
                    ->native(false)
                    ->required()
                    ->default(fn() => Carbon::now()->endOfWeek()->toDateString()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week_start_date')
                    ->label('Week')
                    ->formatStateUsing(fn($record) => $record->week_start_date->format('M j') . ' – ' . $record->week_end_date->format('M j, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('shifts_count')
                    ->counts('shifts')
                    ->label('Shifts')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'published' => 'success',
                        'draft'     => 'gray',
                        'archived'  => 'warning',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                        'archived'  => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'name', fn($query) => $query->where('company_id', auth()->user()?->company_id)),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('week_start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit'   => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
