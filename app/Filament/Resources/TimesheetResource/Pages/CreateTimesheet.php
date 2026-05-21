<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Models\Timesheet;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $exists = Timesheet::where('user_id', $data['user_id'])
            ->where('period_start', $data['period_start'])
            ->where('period_end', $data['period_end'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Duplicate Timesheet')
                ->body('A timesheet for this employee already exists for the selected period.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
