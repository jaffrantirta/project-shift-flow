<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('users.id', $userId))
            ->with(['participants', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->get()
            ->map(function ($conv) use ($userId) {
                $participant = $conv->participants->firstWhere('id', $userId);
                $lastReadAt  = $participant?->pivot->last_read_at;

                $conv->unread_count = $conv->messages()
                    ->where('sender_id', '!=', $userId)
                    ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
                    ->count();

                return $conv;
            });

        return ConversationResource::collection($conversations);
    }

    public function messages(Request $request, int $conversationId)
    {
        $userId = $request->user()->id;

        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('users.id', $userId))
            ->findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderByDesc('created_at')
            ->paginate(30);

        // Mark as read
        $conversation->participants()->updateExistingPivot($userId, ['last_read_at' => now()]);

        return MessageResource::collection($messages);
    }

    public function send(Request $request, int $conversationId)
    {
        $userId = $request->user()->id;

        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('users.id', $userId))
            ->findOrFail($conversationId);

        $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'sometimes|in:text,image,file',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'body'      => $request->body,
            'type'      => $request->type ?? 'text',
        ]);

        return new MessageResource($message->load('sender'));
    }

    public function startDirect(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id|different:' . $request->user()->id,
        ]);

        $user      = $request->user();
        $targetId  = $request->user_id;

        // Find existing direct conversation between these two users
        $conversation = Conversation::where('type', 'direct')
            ->where('company_id', $user->company_id)
            ->whereHas('participants', fn($q) => $q->where('users.id', $user->id))
            ->whereHas('participants', fn($q) => $q->where('users.id', $targetId))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'company_id' => $user->company_id,
                'type'       => 'direct',
            ]);
            $conversation->participants()->attach([$user->id, $targetId], ['joined_at' => now()]);
        }

        return new ConversationResource($conversation->load(['participants', 'messages']));
    }
}
