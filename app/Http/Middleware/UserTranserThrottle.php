<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserTranserThrottle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->user_id ?? optional(auth()->user())->id;

        if (! $userId) {
            return $next($request); // or handle differently
        }

        $key = "user:last_request:{$userId}";
        $now = now()->timestamp;

        // If there is a previous timestamp
        if (Cache::has($key)) {
            $last = Cache::get($key);

            if (($now - $last) < 2) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Please wait 10 seconds before trying again.',
                ], 429);
            }
        }

        // store timestamp for 20 seconds (just needs to live longer than 10s)
        Cache::put($key, $now, 20);
        return $next($request);
    }
}
