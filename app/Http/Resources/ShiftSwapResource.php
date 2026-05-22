<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftSwapResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->requesterShift?->location?->timezone ?? $this->userTz($request);

        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'reason'          => $this->reason,
            'requester_shift' => $this->whenLoaded('requesterShift', fn() => new ShiftResource($this->requesterShift)),
            'target_shift'    => $this->whenLoaded('targetShift', fn() => new ShiftResource($this->targetShift)),
            'requester'       => $this->whenLoaded('requester', fn() => [
                'id'   => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'target' => $this->whenLoaded('target', fn() => [
                'id'   => $this->target->id,
                'name' => $this->target->name,
            ]),
            'created_at' => $this->created_at->setTimezone($tz)->toISOString(),
            'timezone'   => $tz,
        ];
    }
}
