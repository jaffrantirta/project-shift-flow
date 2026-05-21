<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'status'     => $this->status,
            'company_id' => $this->company_id,
            'profile'    => $this->whenLoaded('employeeProfile', fn() => [
                'employee_code'    => $this->employeeProfile->employee_code,
                'job_title'        => $this->employeeProfile->job_title,
                'employment_type'  => $this->employeeProfile->employment_type,
                'pay_type'         => $this->employeeProfile->pay_type,
                'hire_date'        => $this->employeeProfile->hire_date?->toDateString(),
            ]),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
