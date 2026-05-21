<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shift_id', 'location_id', 'date', 'clock_in_at', 'clock_out_at', 'break_minutes', 'total_minutes', 'overtime_minutes', 'status'])]
class Attendance extends Model
{
    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'clock_in_at'  => 'datetime',
            'clock_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
