<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftResource;
use App\Models\NewsFeed;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $upcomingShifts = $user->shifts()
            ->with(['location', 'department'])
            ->where('start_datetime', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_datetime')
            ->limit(10)
            ->get();

        $todayShifts = $user->shifts()
            ->with(['location', 'department'])
            ->whereDate('start_datetime', today())
            ->orderBy('start_datetime')
            ->get();

        $announcements = NewsFeed::where('company_id', $user->company_id)
            ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'is_pinned', 'created_at']);

        $pendingLeave = $user->leaveRequests()
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'today_shifts'    => ShiftResource::collection($todayShifts),
            'upcoming_shifts' => ShiftResource::collection($upcomingShifts),
            'announcements'   => $announcements,
            'pending_leave'   => $pendingLeave,
        ]);
    }
}
