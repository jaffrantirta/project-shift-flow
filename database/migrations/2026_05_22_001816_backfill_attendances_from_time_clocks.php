<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $events = DB::table('time_clocks')
            ->orderBy('clocked_at')
            ->get();

        // Group events by user_id + date
        $byUserDate = [];
        foreach ($events as $event) {
            $date = substr($event->clocked_at, 0, 10); // YYYY-MM-DD
            $byUserDate[$event->user_id][$date][] = $event;
        }

        foreach ($byUserDate as $userId => $dates) {
            foreach ($dates as $date => $dayEvents) {
                $clockIn        = null;
                $clockOut       = null;
                $locationId     = null;
                $shiftId        = null;
                $breakMinutes   = 0;
                $lastBreakStart = null;

                foreach ($dayEvents as $e) {
                    if ($e->type === 'clock_in' && $clockIn === null) {
                        $clockIn    = $e->clocked_at;
                        $locationId = $e->location_id;
                        $shiftId    = $e->shift_id;
                    } elseif ($e->type === 'clock_out') {
                        $clockOut = $e->clocked_at;
                    } elseif ($e->type === 'break_start') {
                        $lastBreakStart = $e->clocked_at;
                    } elseif ($e->type === 'break_end' && $lastBreakStart) {
                        $breakMinutes  += (int) round(
                            (strtotime($e->clocked_at) - strtotime($lastBreakStart)) / 60
                        );
                        $lastBreakStart = null;
                    }
                }

                if ($clockIn === null || $locationId === null) {
                    continue; // skip incomplete records
                }

                $totalMinutes = 0;
                if ($clockOut) {
                    $gross        = (int) round((strtotime($clockOut) - strtotime($clockIn)) / 60);
                    $totalMinutes = max(0, $gross - $breakMinutes);
                }

                // Skip if an attendance row already exists — don't overwrite admin edits
                $exists = DB::table('attendances')
                    ->where('user_id', $userId)
                    ->whereDate('date', $date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('attendances')->insert([
                    'user_id'          => $userId,
                    'location_id'      => $locationId,
                    'shift_id'         => $shiftId,
                    'date'             => $date,
                    'clock_in_at'      => $clockIn,
                    'clock_out_at'     => $clockOut,
                    'break_minutes'    => $breakMinutes,
                    'total_minutes'    => $totalMinutes,
                    'overtime_minutes' => 0,
                    'status'           => 'present',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // intentionally empty — backfills are not reversible
    }
};
