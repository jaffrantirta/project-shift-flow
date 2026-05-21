<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'start_datetime'   => $this->start_datetime?->toISOString(),
            'end_datetime'     => $this->end_datetime?->toISOString(),
            'break_duration_minutes' => $this->break_duration_minutes,
            'duration_minutes' => $this->getDurationMinutes(),
            'status'           => $this->status,
            'location'         => $this->whenLoaded('location', fn() => [
                'id'   => $this->location->id,
                'name' => $this->location->name,
            ]),
            'department'       => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
        ];
    }
}
