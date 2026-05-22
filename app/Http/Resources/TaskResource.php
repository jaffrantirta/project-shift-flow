<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->userTz($request);

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'priority'    => $this->priority,
            'status'      => $this->status,
            'due_date'    => $this->due_date?->toDateString(),
            'created_by'  => $this->whenLoaded('createdBy', fn() => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'assignment' => $this->whenLoaded('assignments', fn() =>
                $this->assignments->first()
                    ? [
                        'status'       => $this->assignments->first()->status,
                        'completed_at' => $this->assignments->first()->completed_at?->setTimezone($tz)->toISOString(),
                        'notes'        => $this->assignments->first()->notes,
                    ]
                    : null
            ),
            'created_at' => $this->created_at->setTimezone($tz)->toISOString(),
            'timezone'   => $tz,
        ];
    }
}
