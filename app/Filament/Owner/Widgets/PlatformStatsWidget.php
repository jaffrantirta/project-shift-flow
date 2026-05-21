<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants = Company::count();
        $newThisMonth = Company::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $totalUsers = User::whereNotNull('company_id')->count();
        $activeUsers = User::whereNotNull('company_id')->where('status', 'active')->count();

        return [
            Stat::make('Total Tenants', $totalTenants)
                ->description($newThisMonth . ' new this month')
                ->icon('heroicon-o-building-office-2')
                ->color('indigo'),

            Stat::make('Total Users', $totalUsers)
                ->description($activeUsers . ' active')
                ->icon('heroicon-o-users')
                ->color('emerald'),

            Stat::make('Active Users', $activeUsers)
                ->description('Across all tenants')
                ->icon('heroicon-o-user-circle')
                ->color('success'),

            Stat::make('New Tenants (Month)', $newThisMonth)
                ->description('Registered in ' . now()->format('F Y'))
                ->icon('heroicon-o-plus-circle')
                ->color($newThisMonth > 0 ? 'warning' : 'gray'),
        ];
    }
}
