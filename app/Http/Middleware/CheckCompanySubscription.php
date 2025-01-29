<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CheckCompanySubscription
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
        $company_name  = auth()->user()->company->short_name;

        if (auth()->user()->phone_verification_status_id == 1 && auth()->user()->company->status_id == 1) {
            // var_dump(auth()->user()->company->sub->end_date);
            // if(!isset(auth()->user()->company->sub->end_date)){

            //     return redirect()->route('company.subplan', $company_name)->with('fail', 'Subscribe to Countinue with our Service!!!');
            // }
            if (auth()->user()->company->sub->end_date >= date("Y-m-d")) {
                return $next($request);
            } else {
                if ('company.subplan' == Route::currentRouteName() || 'company.subscribe' == Route::currentRouteName()) {
                    return $next($request);
                }

                return redirect()->route('company.subplan', $company_name)->with('fail', 'Subscribe to Countinue with our Service!!!');
            }
        } else {
            return redirect('/phone-verification');
        }
    }
}
