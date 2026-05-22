<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['employee', 'admin'])) {
            return response()->json(['message' => 'Access denied. Employee portal is for employees only.'], 403);
        }

        return $next($request);
    }
}
