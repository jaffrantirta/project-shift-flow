<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'leave_type'   => $this->whenLoaded('leaveType', fn() => [
                'id'   => $this->leaveType->id,
                'name' => $this->leaveType->name,
            ]),
            'start_date'   => $this->start_date->toDateString(),
            'end_date'     => $this->end_date->toDateString(),
            'total_days'   => $this->total_days,
            'reason'       => $this->reason,
            'status'       => $this->status,
            'reviewed_by'  => $this->whenLoaded('reviewedBy', fn() => $this->reviewedBy?->name),
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
