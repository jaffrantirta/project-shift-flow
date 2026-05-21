<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'name'         => $this->name,
            'participants' => $this->whenLoaded('participants', fn() =>
                $this->participants->map(fn($u) => [
                    'id'     => $u->id,
                    'name'   => $u->name,
                    'avatar' => $u->avatar ? asset('storage/' . $u->avatar) : null,
                ])
            ),
            'last_message' => $this->whenLoaded('messages', fn() =>
                $this->messages->sortByDesc('created_at')->first()
                    ? new MessageResource($this->messages->sortByDesc('created_at')->first())
                    : null
            ),
            'unread_count' => $this->when(
                isset($this->unread_count),
                fn() => $this->unread_count
            ),
        ];
    }
}
