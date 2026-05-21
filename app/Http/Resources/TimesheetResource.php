<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'period_start' => $this->period_start->toDateString(),
            'period_end'   => $this->period_end->toDateString(),
            'status'       => $this->status,
            'total_hours'  => $this->getTotalHoursAttribute(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at'  => $this->approved_at?->toISOString(),
            'location'     => $this->whenLoaded('location', fn() => [
                'id'   => $this->location->id,
                'name' => $this->location->name,
            ]),
            'entries'      => TimesheetEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
