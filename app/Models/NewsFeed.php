<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'author_id', 'title', 'body', 'type', 'target_type', 'target_id', 'pinned', 'requires_confirmation'])]
class NewsFeed extends Model
{
    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'requires_confirmation' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NewsFeedRead::class);
    }
}
