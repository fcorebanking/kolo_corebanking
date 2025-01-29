<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permission=null)
    {
      
       if(cando($permission)){
             return $next($request);
       }
       abort(403);
    }
}
{
    
}
