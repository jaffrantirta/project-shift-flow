<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'submitted'))
                ->badge(fn() => \App\Models\Timesheet::where('status', 'submitted')->count()),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All'),
        ];
    }
}
