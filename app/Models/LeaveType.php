<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'color', 'is_paid', 'max_days_per_year', 'carry_over', 'requires_approval'])]
class LeaveType extends Model
{
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'carry_over' => 'boolean',
            'requires_approval' => 'boolean',
            'max_days_per_year' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
