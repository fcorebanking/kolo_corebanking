<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\branch;
use App\Models\User;
use App\Models\company_settings;
use Illuminate\Support\Facades\Auth;
class CheckPlan
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
        $company_name = auth()->user()->company->short_name;
        $url =  "{$_SERVER['REQUEST_URI']}";
        if (Auth::check()) {
            if(isset(auth()->user()->company->setting->plan->max_branch)){
                return $next($request);
            }else{
                return redirect()->route('company.company_settings', [$company_name])->with('fail', 'Setup your company settings!!!');                  
            }
        }
        abort(403);
    }
}
