<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockIpAddressMiddleware
{
    /**
     * @var string[]
     */
    public $blacklistIps = [
        '102.67.29.183',
        '162.0.230.135',
        '105.112.226.8',
        '154.73.8.97',
        '3.84.200.91',
        '54.162.174.123',
        '77.111.246.40',
        '66.29.138.27'
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->getClientIp(), $this->blacklistIps)) {
            return response()->json(['status' => false, 'message' => 'You are restricted from Accessing this site'], 403);
        }

        return $next($request);
    }
}
