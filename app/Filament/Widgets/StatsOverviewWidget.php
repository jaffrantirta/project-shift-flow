<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\Timesheet;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayShifts = Shift::whereDate('start_datetime', today())->count();
        $pendingTimesheets = Timesheet::where('status', 'submitted')->count();
        $pendingLeave = LeaveRequest::where('status', 'pending')->count();
        $activeEmployees = User::where('status', 'active')->count();

        return [
            Stat::make('Active Employees', $activeEmployees)
                ->description('Total active staff')
                ->icon('heroicon-o-users')
                ->color('indigo'),

            Stat::make('Shifts Today', $todayShifts)
                ->description('Scheduled for ' . today()->format('M j'))
                ->icon('heroicon-o-calendar-days')
                ->color('emerald'),

            Stat::make('Pending Timesheets', $pendingTimesheets)
                ->description('Awaiting your approval')
                ->icon('heroicon-o-document-check')
                ->color($pendingTimesheets > 0 ? 'warning' : 'gray'),

            Stat::make('Pending Leave', $pendingLeave)
                ->description('Awaiting your approval')
                ->icon('heroicon-o-no-symbol')
                ->color($pendingLeave > 0 ? 'danger' : 'gray'),
        ];
    }
}
