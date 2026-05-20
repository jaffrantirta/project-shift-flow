<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_feed_id', 'user_id', 'read_at', 'confirmed_at'])]
class NewsFeedRead extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function newsFeed(): BelongsTo
    {
        return $this->belongsTo(NewsFeed::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
