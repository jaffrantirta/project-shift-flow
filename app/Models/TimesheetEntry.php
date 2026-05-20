<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['timesheet_id', 'attendance_id', 'date', 'start_time', 'end_time', 'break_minutes', 'total_hours', 'overtime_hours', 'is_manual'])]
class TimesheetEntry extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'is_manual' => 'boolean',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }
}
