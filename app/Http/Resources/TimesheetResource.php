<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->location?->timezone ?? $this->userTz($request);

        return [
            'id'           => $this->id,
            'period_start' => $this->period_start->toDateString(),
            'period_end'   => $this->period_end->toDateString(),
            'status'       => $this->status,
            'total_hours'  => $this->getTotalHoursAttribute(),
            'submitted_at' => $this->submitted_at?->setTimezone($tz)->toIso8601String(),
            'approved_at'  => $this->approved_at?->setTimezone($tz)->toIso8601String(),
            'timezone'     => $tz,
            'location'     => $this->whenLoaded('location', fn() => [
                'id'       => $this->location->id,
                'name'     => $this->location->name,
                'timezone' => $this->location->timezone,
            ]),
            'entries' => TimesheetEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
