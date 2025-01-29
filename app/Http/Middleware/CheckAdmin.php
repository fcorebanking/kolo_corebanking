<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $header = $request->header('Authorization');

        if(!$key = $request->get('apikey') or $key !== config('app.api_key')){
            throw new AuthenticationException('Wrong api key');
          }
        return $next($request);
    }
}
