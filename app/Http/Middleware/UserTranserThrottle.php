<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserTranserThrottle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->user()->id;

        $key = "user:transfer_attempts:{$userId}";

        $attempts = Cache::get($key, 0);

        if ($attempts >= 4) {
            return response()->json([
                'status' => false,
                'message' => 'Too many attempts. Please try again in a minute.',
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinute());

        return $next($request);
    }
}