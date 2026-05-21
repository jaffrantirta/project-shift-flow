<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'date'           => $this->date->toDateString(),
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'break_minutes'  => $this->break_minutes,
            'total_hours'    => $this->total_hours,
            'overtime_hours' => $this->overtime_hours,
            'is_manual'      => $this->is_manual,
        ];
    }
}
