<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->location?->timezone ?? $this->userTz($request);

        return [
            'id'                     => $this->id,
            'title'                  => $this->title,
            'start_datetime'         => $this->start_datetime?->setTimezone($tz)->toISOString(),
            'end_datetime'           => $this->end_datetime?->setTimezone($tz)->toISOString(),
            'break_duration_minutes' => $this->break_duration_minutes,
            'duration_minutes'       => $this->getDurationMinutes(),
            'status'                 => $this->status,
            'timezone'               => $tz,
            'location'               => $this->whenLoaded('location', fn() => [
                'id'       => $this->location->id,
                'name'     => $this->location->name,
                'timezone' => $this->location->timezone,
            ]),
            'department' => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
        ];
    }
}
