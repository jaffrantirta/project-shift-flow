<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftResource;
use App\Models\NewsFeed;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->user();
        $location = $user->locations()->wherePivot('is_primary', true)->first()
                 ?? $user->locations()->first();
        $tz       = $location?->timezone ?? 'UTC';

        // Build today's date range in the user's local timezone, converted to UTC for the query
        $localToday    = Carbon::now($tz)->toDateString();
        $todayStartUtc = Carbon::parse($localToday, $tz)->startOfDay()->utc();
        $todayEndUtc   = Carbon::parse($localToday, $tz)->endOfDay()->utc();

        $todayShifts = $user->shifts()
            ->with(['location', 'department'])
            ->whereBetween('start_datetime', [$todayStartUtc, $todayEndUtc])
            ->orderBy('start_datetime')
            ->get();

        $upcomingShifts = $user->shifts()
            ->with(['location', 'department'])
            ->where('start_datetime', '>', $todayEndUtc)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_datetime')
            ->limit(10)
            ->get();

        $announcements = NewsFeed::where('company_id', $user->company_id)
            ->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'is_pinned', 'created_at'])
            ->map(fn($item) => [
                'id'         => $item->id,
                'title'      => $item->title,
                'body'       => $item->body,
                'is_pinned'  => $item->is_pinned,
                'created_at' => $item->created_at->setTimezone($tz)->toISOString(),
            ]);

        $pendingLeave = $user->leaveRequests()
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'timezone'        => $tz,
            'today_shifts'    => ShiftResource::collection($todayShifts),
            'upcoming_shifts' => ShiftResource::collection($upcomingShifts),
            'announcements'   => $announcements,
            'pending_leave'   => $pendingLeave,
        ]);
    }
}
