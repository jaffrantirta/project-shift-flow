<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'leave_type'    => $this->whenLoaded('leaveType', fn() => [
                'id'   => $this->leaveType->id,
                'name' => $this->leaveType->name,
            ]),
            'year'          => $this->year,
            'total_days'    => $this->total_days,
            'used_days'     => $this->used_days,
            'pending_days'  => $this->pending_days,
            'remaining_days'=> $this->remaining_days,
        ];
    }
}
