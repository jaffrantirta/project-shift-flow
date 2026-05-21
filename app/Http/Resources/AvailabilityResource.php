<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'recurrence'  => $this->recurrence,
            'date'        => $this->date?->toDateString(),
            'day_of_week' => $this->day_of_week,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'reason'      => $this->reason,
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
