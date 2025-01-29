<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class CheckAPIKey
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

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        // logInfo($checkApiKey, "API key checking");

        if ($checkApiKey) {
            return $next($request);
        } else {
            return response()->json(['status' => false, 'message' => "Invalid API Key"]);
        }

        return $next($request);
    }
}
