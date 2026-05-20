<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['name', 'slug', 'logo', 'timezone', 'address', 'phone', 'email'])]
class Company extends Model
{
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function departments(): HasManyThrough
    {
        return $this->hasManyThrough(Department::class, Location::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }
}
