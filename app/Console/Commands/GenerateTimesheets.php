<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTimesheets extends Command
{
    protected $signature = 'timesheets:generate
                            {--period-start= : Start date (Y-m-d). Defaults to start of last week.}
                            {--period-end=   : End date (Y-m-d). Defaults to end of last week.}
                            {--current-week  : Generate for the current week so far.}';

    protected $description = 'Generate timesheets and entries from attendance records for a pay period.';

    public function handle(): int
    {
        [$periodStart, $periodEnd] = $this->resolvePeriod();

        $this->info("Generating timesheets for {$periodStart->toDateString()} → {$periodEnd->toDateString()}");

        $attendances = Attendance::with(['user', 'location'])
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereNotNull('clock_in_at')
            ->get()
            ->groupBy(fn($a) => $a->user_id . '_' . $a->location_id);

        if ($attendances->isEmpty()) {
            $this->warn('No attendance records found for this period.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $entries = 0;

        foreach ($attendances as $records) {
            $first      = $records->first();
            $userId     = $first->user_id;
            $locationId = $first->location_id;

            $timesheet = Timesheet::where('user_id', $userId)
                ->where('location_id', $locationId)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->first();

            if ($timesheet) {
                $skipped++;
            } else {
                $timesheet = Timesheet::create([
                    'user_id'      => $userId,
                    'location_id'  => $locationId,
                    'period_start' => $periodStart->toDateString(),
                    'period_end'   => $periodEnd->toDateString(),
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                ]);
                $created++;
            }

            if (in_array($timesheet->status, ['submitted', 'approved'])) {
                $this->line("  Skipping user {$userId} — timesheet already {$timesheet->status}");
                continue;
            }

            foreach ($records as $attendance) {
                if (TimesheetEntry::where('timesheet_id', $timesheet->id)
                    ->where('attendance_id', $attendance->id)
                    ->exists()) {
                    continue;
                }

                $tz        = $attendance->location?->timezone ?? 'UTC';
                $startTime = $attendance->clock_in_at?->setTimezone($tz)->format('H:i:s');
                $endTime   = $attendance->clock_out_at?->setTimezone($tz)->format('H:i:s');

                TimesheetEntry::create([
                    'timesheet_id'   => $timesheet->id,
                    'attendance_id'  => $attendance->id,
                    'date'           => $attendance->date->toDateString(),
                    'start_time'     => $startTime,
                    'end_time'       => $endTime,
                    'break_minutes'  => $attendance->break_minutes ?? 0,
                    'total_hours'    => round(($attendance->total_minutes ?? 0) / 60, 2),
                    'overtime_hours' => round(($attendance->overtime_minutes ?? 0) / 60, 2),
                    'is_manual'      => false,
                ]);

                $entries++;
            }
        }

        $this->info("Done. Created: {$created} timesheets, skipped: {$skipped} existing, added: {$entries} entries.");

        return self::SUCCESS;
    }

    private function resolvePeriod(): array
    {
        if ($this->option('current-week')) {
            return [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ];
        }

        if ($this->option('period-start') && $this->option('period-end')) {
            return [
                Carbon::parse($this->option('period-start'))->startOfDay(),
                Carbon::parse($this->option('period-end'))->endOfDay(),
            ];
        }

        return [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek(),
        ];
    }
}
