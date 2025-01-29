<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
       
    }

    // Add new method 
    // protected function unauthenticated($request, array $guards)
    // {
   
    //     if (!$request->expectsJson()) {
    //        // abort(403);
    //        abort(route('login'), 401);
    //     //    return redirect()->route('login');
    //     }else{
    //         abort(response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorize Request',
    //         ], 401));
    //     }
        
    // }
}
