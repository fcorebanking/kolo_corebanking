<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




class authfactorOTP
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
        $currenturi = $currentUrl = $_SERVER['REQUEST_URI'];// Route::current()->ur;
        try{
        $auth = $request->user();
            if (isset($auth->company->setting)) {
                if (($auth->company->setting)) {
                if ($auth->company->setting->authfactorsetup == 0) {
                    return $next($request);
                } else if ($auth->company->setting->authfactorsetup == 1) {
                    if ($auth->login_sms_otp_status != 1 && ($currenturi != "/security/2factor")) {
                        $otpgen = generateOtp();
                        $current_time = date("Y-m-d H:i:s"); // get current time
                        $incremented_time = date("Y-m-d H:i:s", strtotime($current_time . "+15 minutes"));
                        $auth->update(['otp' => $otpgen, 'otp_expiration_date' => $incremented_time]);
                        (sendSMS($auth->id, "Your 2Factor OTP is $otpgen at " . now(), true, false, 'WBA Login Security'));
                        return redirect("security/2factor");
                    }
                }
            }
        }

            return $next($request);

        }catch(Exception $e){
            return $next($request);

        }
    }
}
