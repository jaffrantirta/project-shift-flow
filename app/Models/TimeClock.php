<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shift_id', 'location_id', 'type', 'clocked_at', 'lat', 'lng', 'accuracy_meters', 'device_type', 'photo_url', 'is_verified'])]
class TimeClock extends Model
{
    protected function casts(): array
    {
        return [
            'clocked_at'  => 'datetime',
            'is_verified' => 'boolean',
            'lat'         => 'decimal:7',
            'lng'         => 'decimal:7',
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
