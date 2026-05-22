<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Location;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $assignments = TaskAssignment::where('user_id', $userId)
            ->with(['task.createdBy', 'task.assignments' => fn($q) => $q->where('user_id', $userId)])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END")
            ->paginate(20);

        $tasks = $assignments->through(fn($a) => tap($a->task, fn($t) => $t->setRelation('assignments', collect([$a]))));

        return TaskResource::collection($tasks);
    }

    public function show(Request $request, int $taskId)
    {
        $userId = $request->user()->id;

        $assignment = TaskAssignment::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->with(['task.createdBy', 'task.assignments' => fn($q) => $q->where('user_id', $userId)])
            ->firstOrFail();

        $task = tap($assignment->task, fn($t) => $t->setRelation('assignments', collect([$assignment])));

        return new TaskResource($task);
    }

    public function updateStatus(Request $request, int $taskId)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $assignment = TaskAssignment::where('user_id', $request->user()->id)
            ->where('task_id', $taskId)
            ->firstOrFail();

        $assignment->update([
            'status'       => $request->status,
            'notes'        => $request->notes ?? $assignment->notes,
            'completed_at' => $request->status === 'completed' ? now() : null,
        ]);

        $user     = $request->user();
        $location = $user->locations()->wherePivot('is_primary', true)->first()
            ?? $user->locations()->first()
            ?? Location::where('company_id', $user->company_id)->first();
        $tz = $location?->timezone ?? 'UTC';

        return response()->json([
            'task_id'      => $taskId,
            'status'       => $assignment->status,
            'completed_at' => $assignment->completed_at?->setTimezone($tz)->toIso8601String(),
            'notes'        => $assignment->notes,
            'timezone'     => $tz,
        ]);
    }
}
