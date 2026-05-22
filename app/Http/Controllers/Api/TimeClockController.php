<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\TimeClock;
use Illuminate\Http\Request;

class TimeClockController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        // Prefer assigned locations, fall back to all company locations
        $locations = $user->locations()->orderByPivot('is_primary', 'desc')->get();
        if ($locations->isEmpty()) {
            $locations = Location::where('company_id', $user->company_id)->orderBy('name')->get();
        }

        $primaryLocation = $locations->first();
        $tz = $primaryLocation?->timezone ?? 'UTC';
        $localToday = now()->setTimezone($tz)->toDateString();

        $last = TimeClock::where('user_id', $user->id)
            ->whereDate('clocked_at', $localToday)
            ->orderByDesc('clocked_at')
            ->first();

        $isClockedIn = $last && in_array($last->type, ['clock_in', 'break_end']);

        return response()->json([
            'is_clocked_in'    => $isClockedIn,
            'timezone'         => $tz,
            'default_location' => $primaryLocation ? [
                'id'       => $primaryLocation->id,
                'name'     => $primaryLocation->name,
                'timezone' => $primaryLocation->timezone,
            ] : null,
            'locations'        => $locations->map(fn($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'timezone' => $l->timezone,
            ]),
            'last_event' => $last ? [
                'type'       => $last->type,
                'clocked_at' => $last->clocked_at->setTimezone($tz)->toIso8601String(),
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

        $user = $request->user();
        $type = $request->type;
        $nowTs = now(); // stored as UTC

        // Ensure the location belongs to the employee's company
        $location = Location::where('id', $request->location_id)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $location) {
            return response()->json(['message' => 'Location not found or does not belong to your company.'], 422);
        }

        $tz = $location->timezone ?? 'UTC';
        $localNow = $nowTs->copy()->setTimezone($tz);
        $localDate = $localNow->toDateString(); // today in location's timezone

        $event = TimeClock::create([
            'user_id'         => $user->id,
            'type'            => $type,
            'location_id'     => $request->location_id,
            'shift_id'        => $request->shift_id,
            'clocked_at'      => $nowTs,
            'lat'             => $request->lat,
            'lng'             => $request->lng,
            'accuracy_meters' => $request->accuracy_meters,
            'device_type'     => $request->device_type ?? 'mobile',
        ]);

        // Use whereDate so SQLite's date function handles any stored time component
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $localDate)
            ->first();

        $attendanceExists = $attendance !== null;

        if ($type === 'clock_in') {
            if (! $attendance) {
                // First clock-in of the day — create the row and set the start time
                $attendance = new Attendance([
                    'user_id'          => $user->id,
                    'date'             => $localDate,
                    'break_minutes'    => 0,
                    'total_minutes'    => 0,
                    'overtime_minutes' => 0,
                    'clock_in_at'      => $nowTs,
                ]);
            }
            // Subsequent clock-ins (returning from break / multi-session):
            // keep the original clock_in_at, just clear clock_out so it shows as active
            $attendance->clock_out_at = null;
            $attendance->location_id  = $request->location_id;
            $attendance->shift_id     = $request->shift_id;
            $attendance->status       = 'present';
            $attendance->save();
        } elseif ($type === 'clock_out' && $attendanceExists) {
            $attendance->clock_out_at = $nowTs;

            if ($attendance->clock_in_at) {
                $grossMinutes = (int) $attendance->clock_in_at->diffInMinutes($nowTs);
                $attendance->total_minutes = max(0, $grossMinutes - ($attendance->break_minutes ?? 0));
            }

            $attendance->save();
        } elseif ($type === 'break_end' && $attendanceExists) {
            $breakStart = TimeClock::where('user_id', $user->id)
                ->whereDate('clocked_at', $localDate)
                ->where('type', 'break_start')
                ->orderByDesc('clocked_at')
                ->first();

            if ($breakStart) {
                $breakDuration = (int) $breakStart->clocked_at->diffInMinutes($nowTs);
                $attendance->break_minutes = ($attendance->break_minutes ?? 0) + $breakDuration;

                if ($attendance->clock_in_at) {
                    $grossMinutes = (int) $attendance->clock_in_at->diffInMinutes($nowTs);
                    $attendance->total_minutes = max(0, $grossMinutes - $attendance->break_minutes);
                }

                $attendance->save();
            }
        }

        return response()->json([
            'id'         => $event->id,
            'type'       => $event->type,
            'clocked_at' => $event->clocked_at->setTimezone($tz)->toIso8601String(),
            'timezone'   => $tz,
        ], 201);
    }

    public function history(Request $request)
    {
        $user     = $request->user();
        $location = $user->locations()->wherePivot('is_primary', true)->first()
            ?? $user->locations()->first()
            ?? Location::where('company_id', $user->company_id)->first();
        $tz = $location?->timezone ?? 'UTC';

        $events = TimeClock::where('user_id', $user->id)
            ->with('location')
            ->orderByDesc('clocked_at')
            ->paginate(30);

        $events->getCollection()->transform(function ($event) use ($tz) {
            $eventTz = $event->location?->timezone ?? $tz;
            return [
                'id'         => $event->id,
                'type'       => $event->type,
                'clocked_at' => $event->clocked_at->setTimezone($eventTz)->toIso8601String(),
                'timezone'   => $eventTz,
                'location'   => $event->location?->name,
            ];
        });

        return response()->json($events);
    }
}
