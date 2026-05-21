<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn(Builder $q) => $q->whereDate('date', today()))
                ->badge(fn() => \App\Models\Attendance::whereDate('date', today())->count()),
            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn(Builder $q) => $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])),
            'issues' => Tab::make('Issues')
                ->modifyQueryUsing(fn(Builder $q) => $q->whereIn('status', ['absent', 'no_show', 'late', 'early_out']))
                ->badge(fn() => \App\Models\Attendance::whereIn('status', ['absent', 'no_show'])->whereDate('date', today())->count()),
            'all' => Tab::make('All'),
        ];
    }
}
