<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->userTz($request);

        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'status'     => $this->status,
            'company_id' => $this->company_id,
            'timezone'   => $tz,
            'profile'    => $this->whenLoaded('employeeProfile', fn() => [
                'employee_code'    => $this->employeeProfile->employee_code,
                'job_title'        => $this->employeeProfile->job_title,
                'employment_type'  => $this->employeeProfile->employment_type,
                'pay_type'         => $this->employeeProfile->pay_type,
                'hire_date'        => $this->employeeProfile->hire_date?->toDateString(),
            ]),
            'created_at' => $this->created_at->setTimezone($tz)->toIso8601String(),
        ];
    }
}
