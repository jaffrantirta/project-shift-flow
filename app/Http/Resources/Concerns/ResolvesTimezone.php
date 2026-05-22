<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

trait ResolvesTimezone
{
    // Cached per user ID for the lifetime of the process (one request in PHP-FPM)
    private static array $tzCache = [];

    protected function userTz(Request $request): string
    {
        $userId = $request->user()?->id;

        if ($userId && array_key_exists($userId, self::$tzCache)) {
            return self::$tzCache[$userId];
        }

        $location = $request->user()
            ?->locations()
            ->wherePivot('is_primary', true)
            ->first()
            ?? $request->user()?->locations()->first();

        $tz = $location?->timezone ?? 'UTC';

        if ($userId) {
            self::$tzCache[$userId] = $tz;
        }

        return $tz;
    }
}
