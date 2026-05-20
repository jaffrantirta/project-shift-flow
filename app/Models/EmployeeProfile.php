<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'employee_code', 'job_title', 'employment_type', 'pay_rate', 'pay_type', 'hire_date'])]
class EmployeeProfile extends Model
{
    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'pay_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
