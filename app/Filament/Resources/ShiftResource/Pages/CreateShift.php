<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pre-fill from schedule builder query params
        if (request()->has('user_id')) {
            $data['user_id'] = request()->integer('user_id');
        }
        if (request()->has('date')) {
            $date = Carbon::parse(request()->string('date'));
            $data['start_datetime'] ??= $date->setHour(9)->toDateTimeString();
            $data['end_datetime'] ??= $date->setHour(17)->toDateTimeString();
        }
        return $data;
    }
}
