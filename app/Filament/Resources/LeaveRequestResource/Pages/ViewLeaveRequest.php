<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewLeaveRequest extends ViewRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->getRecord()->status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'approved', 'reviewed_by' => Auth::id()]);
                    Notification::make()->title('Leave request approved')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(): bool => $this->getRecord()->status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'rejected', 'reviewed_by' => Auth::id()]);
                    Notification::make()->title('Leave request rejected')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
