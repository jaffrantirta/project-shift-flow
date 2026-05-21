<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimesheetResource;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $timesheets = $request->user()->timesheets()
            ->with(['location', 'entries'])
            ->orderByDesc('period_start')
            ->paginate(15);

        return TimesheetResource::collection($timesheets);
    }

    public function show(Request $request, int $id)
    {
        $timesheet = $request->user()->timesheets()
            ->with(['location', 'entries'])
            ->findOrFail($id);

        return new TimesheetResource($timesheet);
    }

    public function submit(Request $request, int $id)
    {
        $timesheet = $request->user()->timesheets()->findOrFail($id);

        if ($timesheet->status !== 'draft') {
            return response()->json(['message' => 'Only draft timesheets can be submitted.'], 422);
        }

        $timesheet->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return new TimesheetResource($timesheet->load(['location', 'entries']));
    }
}
