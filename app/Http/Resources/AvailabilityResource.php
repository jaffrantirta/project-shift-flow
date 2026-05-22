<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->userTz($request);

        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'recurrence'  => $this->recurrence,
            'date'        => $this->date?->toDateString(),
            'day_of_week' => $this->day_of_week,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'reason'      => $this->reason,
            'timezone'    => $tz,
            'created_at'  => $this->created_at->setTimezone($tz)->toIso8601String(),
        ];
    }
}
