<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use App\Models\Timesheet;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Pending Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Timesheet::query()
                    ->where('status', 'submitted')
                    ->with('user', 'location')
                    ->orderBy('submitted_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn($record) => $record->period_start->format('M j') . ' – ' . $record->period_end->format('M j, Y')),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([
                TableAction::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Timesheet $record): string => \App\Filament\Resources\TimesheetResource::getUrl('view', ['record' => $record]))
                    ->color('indigo'),
            ])
            ->paginated(false);
    }
}
