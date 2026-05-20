<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingShiftsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Upcoming Shifts (Next 48 Hours)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shift::query()
                    ->whereBetween('start_datetime', [now(), now()->addHours(48)])
                    ->with(['user', 'location', 'department'])
                    ->orderBy('start_datetime')
            )
            ->columns([
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Start')
                    ->dateTime('M j, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('End')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('—'),
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
            ->paginated(false);
    }
}
