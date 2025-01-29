<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\companyOpeningclosingtime;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckClosingTime
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
        if(auth()->user()->company->status_id == 2){
            Auth::logout();
            return redirect('/login')->with('fail', 'Company is not active, Contact admin!');
        }
        if(cando('root')){
            return $next($request);
        }else{
            if(auth()->user()->status_id != 1) { 
                Auth::logout();
                return redirect('/login')->with('fail', 'Your Account is not active, Contact admin!');
            }
            $companyOpeningclosingtime = companyOpeningclosingtime::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->where('nday','=',date('N'))->first();
            if($companyOpeningclosingtime){
                //
                $current_time = date('H:i:s');
            $starttime =$companyOpeningclosingtime->starttime;
            $endtime = $companyOpeningclosingtime->endtime;
            $canlogin = Carbon::now()->between($starttime, $endtime);
           
            if($current_time >= $starttime && $current_time <= $endtime){
                return $next($request);
            }else{
                Auth::logout();
                return redirect('/login')->with('fail', 'System is close, try again during business hours!');
            }
            }else{
                $companyOpeningclosingtime = companyOpeningclosingtime::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->where('holiday','=',date('Y-m-d'))->first();
                if($companyOpeningclosingtime){
                    Auth::logout();
                    return redirect('/login')->with('fail', 'System is close, try again during business hours!');
                }
                return $next($request);
            }
        }
    }
}
