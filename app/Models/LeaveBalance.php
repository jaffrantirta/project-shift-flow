<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'leave_type_id', 'year', 'total_days', 'used_days', 'pending_days', 'remaining_days'])]
class LeaveBalance extends Model
{
    protected function casts(): array
    {
        return [
            'total_days'     => 'decimal:2',
            'used_days'      => 'decimal:2',
            'pending_days'   => 'decimal:2',
            'remaining_days' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
