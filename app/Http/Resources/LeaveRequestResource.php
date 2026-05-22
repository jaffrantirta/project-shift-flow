<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->userTz($request);

        return [
            'id'         => $this->id,
            'leave_type' => $this->whenLoaded('leaveType', fn() => [
                'id'   => $this->leaveType->id,
                'name' => $this->leaveType->name,
            ]),
            'start_date'  => $this->start_date->toDateString(),
            'end_date'    => $this->end_date->toDateString(),
            'total_days'  => $this->total_days,
            'reason'      => $this->reason,
            'status'      => $this->status,
            'reviewed_by' => $this->whenLoaded('reviewedBy', fn() => $this->reviewedBy?->name),
            'created_at'  => $this->created_at->setTimezone($tz)->toIso8601String(),
        ];
    }
}
