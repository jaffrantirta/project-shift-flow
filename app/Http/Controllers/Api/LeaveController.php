<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function balances(Request $request)
    {
        $balances = LeaveBalance::where('user_id', $request->user()->id)
            ->where('year', now()->year)
            ->with('leaveType')
            ->get();

        return LeaveBalanceResource::collection($balances);
    }

    public function types(Request $request)
    {
        $types = LeaveType::where('company_id', $request->user()->company_id)
            ->get(['id', 'name', 'is_paid', 'requires_approval']);

        return response()->json($types);
    }

    public function index(Request $request)
    {
        $requests = $request->user()->leaveRequests()
            ->with(['leaveType', 'reviewedBy'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return LeaveRequestResource::collection($requests);
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'start_date'    => 'required|date|after_or_equal:today',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:1000',
        ]);

        $start = \Carbon\Carbon::parse($request->start_date);
        $end   = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $start->diffInDaysFiltered(
            fn(\Carbon\Carbon $date) => ! $date->isWeekend(),
            $end
        ) + 1;

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $request->user()->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => $totalDays,
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        return new LeaveRequestResource($leaveRequest->load(['leaveType']));
    }

    public function show(Request $request, int $id)
    {
        $leaveRequest = $request->user()->leaveRequests()
            ->with(['leaveType', 'reviewedBy'])
            ->findOrFail($id);

        return new LeaveRequestResource($leaveRequest);
    }

    public function cancel(Request $request, int $id)
    {
        $leaveRequest = $request->user()->leaveRequests()->findOrFail($id);

        if (! in_array($leaveRequest->status, ['pending'])) {
            return response()->json(['message' => 'Only pending leave requests can be cancelled.'], 422);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return new LeaveRequestResource($leaveRequest->load(['leaveType']));
    }
}
