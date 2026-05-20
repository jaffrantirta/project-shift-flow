<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['schedule_id', 'location_id', 'department_id', 'user_id', 'shift_template_id', 'title', 'start_datetime', 'end_datetime', 'break_duration_minutes', 'status'])]
class Shift extends Model
{
    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getDurationMinutes(): int
    {
        return (int) $this->start_datetime->diffInMinutes($this->end_datetime) - $this->break_duration_minutes;
    }
}
