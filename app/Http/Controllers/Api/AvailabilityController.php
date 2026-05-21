<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $availabilities = Availability::where('user_id', $request->user()->id)
            ->orderBy('recurrence')
            ->orderBy('day_of_week')
            ->orderBy('date')
            ->get();

        return AvailabilityResource::collection($availabilities);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:available,unavailable,preferred',
            'recurrence'  => 'required|in:once,weekly',
            'date'        => 'required_if:recurrence,once|nullable|date',
            'day_of_week' => 'required_if:recurrence,weekly|nullable|integer|between:0,6',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i|after:start_time',
            'reason'      => 'nullable|string|max:500',
        ]);

        $availability = Availability::create([
            'user_id'     => $request->user()->id,
            'type'        => $request->type,
            'recurrence'  => $request->recurrence,
            'date'        => $request->date,
            'day_of_week' => $request->day_of_week,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'reason'      => $request->reason,
        ]);

        return new AvailabilityResource($availability);
    }

    public function update(Request $request, int $id)
    {
        $availability = Availability::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'type'        => 'sometimes|in:available,unavailable,preferred',
            'recurrence'  => 'sometimes|in:once,weekly',
            'date'        => 'nullable|date',
            'day_of_week' => 'nullable|integer|between:0,6',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'reason'      => 'nullable|string|max:500',
        ]);

        $availability->update($request->only(['type', 'recurrence', 'date', 'day_of_week', 'start_time', 'end_time', 'reason']));

        return new AvailabilityResource($availability);
    }

    public function destroy(Request $request, int $id)
    {
        $availability = Availability::where('user_id', $request->user()->id)->findOrFail($id);
        $availability->delete();

        return response()->json(['message' => 'Availability deleted.']);
    }
}
