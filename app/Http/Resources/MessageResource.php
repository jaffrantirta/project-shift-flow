<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesTimezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    use ResolvesTimezone;

    public function toArray(Request $request): array
    {
        $tz = $this->userTz($request);

        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'body'            => $this->body,
            'type'            => $this->type,
            'read_at'         => $this->read_at?->setTimezone($tz)->toISOString(),
            'sender'          => $this->whenLoaded('sender', fn() => [
                'id'     => $this->sender->id,
                'name'   => $this->sender->name,
                'avatar' => $this->sender->avatar ? asset('storage/' . $this->sender->avatar) : null,
            ]),
            'created_at' => $this->created_at->setTimezone($tz)->toISOString(),
        ];
    }
}
