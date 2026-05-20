<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Models\Timesheet;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTimesheet extends ViewRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve Timesheet')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->getRecord()->status === 'submitted')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                    Notification::make()->title('Timesheet approved successfully')->success()->send();
                    $this->refreshFormData(['status', 'approved_by', 'approved_at']);
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(): bool => $this->getRecord()->status === 'submitted')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'rejected']);
                    Notification::make()->title('Timesheet rejected')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
