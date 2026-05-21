<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftSwapResource;
use App\Models\ShiftSwap;
use Illuminate\Http\Request;

class ShiftSwapController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $swaps = ShiftSwap::where('requester_id', $userId)
            ->orWhere('target_id', $userId)
            ->with(['requesterShift.location', 'targetShift.location', 'requester', 'target'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return ShiftSwapResource::collection($swaps);
    }

    public function store(Request $request)
    {
        $request->validate([
            'requester_shift_id' => 'required|integer|exists:shifts,id',
            'target_shift_id'    => 'required|integer|exists:shifts,id|different:requester_shift_id',
            'target_id'          => 'required|integer|exists:users,id|different:' . $request->user()->id,
            'reason'             => 'nullable|string|max:1000',
        ]);

        $requesterShift = $request->user()->shifts()->findOrFail($request->requester_shift_id);

        $swap = ShiftSwap::create([
            'requester_shift_id' => $requesterShift->id,
            'target_shift_id'    => $request->target_shift_id,
            'requester_id'       => $request->user()->id,
            'target_id'          => $request->target_id,
            'reason'             => $request->reason,
            'status'             => 'pending',
        ]);

        return new ShiftSwapResource($swap->load(['requesterShift.location', 'targetShift.location', 'requester', 'target']));
    }

    public function show(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $swap = ShiftSwap::where(fn($q) => $q->where('requester_id', $userId)->orWhere('target_id', $userId))
            ->with(['requesterShift.location', 'targetShift.location', 'requester', 'target'])
            ->findOrFail($id);

        return new ShiftSwapResource($swap);
    }

    public function cancel(Request $request, int $id)
    {
        $swap = ShiftSwap::where('requester_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $swap->update(['status' => 'cancelled']);

        return new ShiftSwapResource($swap);
    }
}
