<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\CorperateUserApiKey;
use Closure;
use Illuminate\Http\Request;

class VerifyCompanyCustomerApiKey
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

        $clientId = $request->header('clientId');
        $apiKey = $request->header('ApiKey');

        if (!isset($apiKey) || $apiKey == "") {
            return response()->json(["status" => false, "message" => "Please provide Api Key"], 401);
        }

        if (!isset($clientId) || $clientId == "") {
            return response()->json(["status" => false, "message" => "Please provide client Id to continue"], 401);
        }

        $checkApiKey = CorperateUserApiKey::where('api_key', $clientId)->first();
        $companyApiKey = ApiKey::where('api_key', $apiKey)->first();

        if ($checkApiKey && $companyApiKey) {
            if ($checkApiKey->user->company->id == $companyApiKey->company->id) {
                return $next($request);
            } else {
                return response()->json(["status" => false, "message" => "User does not belong to the Provided company"], 401);
            }
        } else {
            return response()->json(["status" => false, "message" => "Invalid Api Key or Client Id"], 401);
        }
    }
}
