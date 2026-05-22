<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate from Attendance')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Generate Timesheets')
                ->modalDescription(fn() =>
                    'This will create draft timesheets and entries for the current week ('
                    . Carbon::now()->startOfWeek()->toDateString()
                    . ' → '
                    . Carbon::now()->endOfWeek()->toDateString()
                    . ') based on all attendance records. Already submitted or approved timesheets will not be changed.'
                )
                ->modalSubmitActionLabel('Generate')
                ->action(function () {
                    Artisan::call('timesheets:generate', ['--current-week' => true]);
                    $output = Artisan::output();

                    Notification::make()
                        ->title('Timesheets Generated')
                        ->body(trim($output))
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }

    public function getTabs(): array
    {
        $companyId = auth()->user()?->company_id;

        return [
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'draft'))
                ->badge(function () use ($companyId) { return \App\Models\Timesheet::whereHas('user', fn($q) => $q->where('company_id', $companyId))->where('status', 'draft')->count(); }),
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'submitted'))
                ->badge(function () use ($companyId) { return \App\Models\Timesheet::whereHas('user', fn($q) => $q->where('company_id', $companyId))->where('status', 'submitted')->count(); }),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All'),
        ];
    }
}
