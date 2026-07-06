<?php
namespace App\Http\Middleware;

use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CheckAccountStatus
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
        loginfo("working on bypass", 'checkaccount status');
        loginfo(json_encode($request->user()), 'checkaccount status');
        if ($request->user()->company_id == 14) {
            return $next($request);
        } else {
            if ($request->user()->last_ip_address == $request->getClientIp() && $request->user()->updated_at > Carbon::now()->subSeconds(3)) {

                $restriction = MainCustomerRestriction::where(['user_id' => $request->user()->id, 'customer_permission_id' => 2])->get()->exists();

                if (! $restriction) {
                    // auth()->user()->customerPermissions()->attach(2);
                    MainCustomerRestriction::create([
                        'user_id'                => auth()->user()->id,
                        'company_id'             => \auth()->user()->company_id,
                        'customer_permission_id' => 2,
                        "remark"                 => "Likely Duplicate transaction",
                        "created_by"             => auth()->user()->id,
                    ]);
                    CustomerRestrictionLog::create([
                        "user_id"                 => auth()->user()->id,
                        "customer_permission_id"  => 2,
                        "remark"                  => "Likely Duplicate transaction",
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 1,
                    ]);
                }

                return response()->json(['status' => false, 'message' => 'Likely Duplicate transaction'], 403);
            } else if ($request->user()->last_ip_address != $request->getClientIp() && $request->user()->updated_at > Carbon::now()->subSeconds(10)) {
                $request->user()->last_ip_address = $request->getClientIp();

                // auth()->user()->customerPermissions()->attach(2);

                MainCustomerRestriction::create([
                    'user_id'                => auth()->user()->id,
                    'company_id'             => \auth()->user()->company_id,
                    'customer_permission_id' => 2,
                    "remark"                 => "Likely Duplicate transaction",
                    "created_by"             => auth()->user()->id,
                ]);

                CustomerRestrictionLog::create([
                    "user_id"                 => auth()->user()->id,
                    "customer_permission_id"  => 2,
                    "remark"                  => "Likely Duplicate transaction",
                    "restricted_by_system"    => 1,
                    "restriction_log_type_id" => 1,

                ]);

                return response()->json(['status' => false, 'message' => 'Likely Duplicate transaction'], 403);
            } else {
                if ($request->user()->status_id == 2 || $request->user()->status_id == 6 || $request->user()->status_id == 5) {
                    return response()->json(['status' => false, 'message' => 'Your account has been Blocked... Please contact support'], 403);
                } else if (count($request->user()->customerPermissions) > 0) {

                    foreach ($request->user()->customerPermissions as $customerPermission) {
                        if ($customerPermission->id == 2) {
                            if (startsWith(request()->path(), 'api')) {
                                return response()->json(['status' => false, 'message' => 'Your account has been Restricted... Please contact support'], 403);
                            } else {
                                return response()->json(['status' => 0, 'message' => 'Your account has been Restricted... Please contact support']);
                            }
                        }
                    }
                    return $next($request);
                } else {

                    return $next($request);
                }
            }
        }

    }
}
