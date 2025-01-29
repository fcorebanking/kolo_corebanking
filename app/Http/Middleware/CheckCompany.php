<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\branch;
use App\Models\User;
use App\Models\company_settings;
use Illuminate\Support\Facades\Auth;
class CheckCompany
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

            if(company_settings::where('company_id','=',auth()->user()->company_id)->count() > 0){
               if(branch::where('company_id','=',auth()->user()->company_id)->count() > 0){
                    if (auth()->user()->branch_id != null) {
                       return $next($request);
                    }else{
                        return redirect()->route('company.staff-profile', [$company_name])->with('fail', 'Setup branch for your account!!!');
                    }
                }else{
                    return redirect()->route('company.branch', [$company_name])->with('fail', 'Setup Branch!!!');
                }
            }else{
                return redirect()->route('company.company_setup', [$company_name])->with('fail', 'Setup Company Settings!!!');
            }
        }
        abort(403);
    }
}
