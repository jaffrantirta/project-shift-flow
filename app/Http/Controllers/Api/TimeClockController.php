<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeClock;
use Illuminate\Http\Request;

class TimeClockController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        $last = TimeClock::where('user_id', $user->id)
            ->whereDate('clocked_at', today())
            ->orderByDesc('clocked_at')
            ->first();

        $isClockedIn = $last && in_array($last->type, ['clock_in', 'break_end']);

        return response()->json([
            'is_clocked_in' => $isClockedIn,
            'last_event'    => $last ? [
                'type'       => $last->type,
                'clocked_at' => $last->clocked_at->toISOString(),
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'             => 'required|in:clock_in,clock_out,break_start,break_end',
            'location_id'      => 'required|integer|exists:locations,id',
            'shift_id'         => 'nullable|integer|exists:shifts,id',
            'lat'              => 'nullable|numeric|between:-90,90',
            'lng'              => 'nullable|numeric|between:-180,180',
            'accuracy_meters'  => 'nullable|numeric|min:0',
            'device_type'      => 'nullable|in:mobile,kiosk,web',
        ]);

        $event = TimeClock::create([
            'user_id'         => $request->user()->id,
            'type'            => $request->type,
            'location_id'     => $request->location_id,
            'shift_id'        => $request->shift_id,
            'clocked_at'      => now(),
            'lat'             => $request->lat,
            'lng'             => $request->lng,
            'accuracy_meters' => $request->accuracy_meters,
            'device_type'     => $request->device_type ?? 'mobile',
        ]);

        return response()->json([
            'id'         => $event->id,
            'type'       => $event->type,
            'clocked_at' => $event->clocked_at->toISOString(),
        ], 201);
    }

    public function history(Request $request)
    {
        $events = TimeClock::where('user_id', $request->user()->id)
            ->orderByDesc('clocked_at')
            ->paginate(30);

        return response()->json($events);
    }
}
