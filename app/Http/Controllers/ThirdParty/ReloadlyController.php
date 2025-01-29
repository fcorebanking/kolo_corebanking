<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReloadlyController extends Controller
{
    //

    public function buyAirtime($transactionId, $networkProviderId, $amount, $mobileNumber)
    {

        $loginResult = $this->loginReloadly();

        logInfo("Our token is: $loginResult");

        if ($loginResult != null) {

            $header = [
                'Authorization' => 'Bearer ' . $loginResult,
                'Accept' => 'application/com.reloadly.topups-v1+json',
                "Content-Type" => "application/json"
            ];
            $body = [
                "operatorId" => $networkProviderId,
                "amount" => $amount,
                "useLocalAmount" => true,
                "customIdentifier" => $transactionId,
                "recipientPhone" => [
                    "countryCode" => \auth()->user()->company->country->code,
                    "number" => "00000"
                ],
                "senderPhone" => [
                    "countryCode" => "NG",
                    "number" => "08187798227"
                ]
            ];

            logInfo($body, "Reloadly airtime request data");

            $url = 'https://topups.reloadly.com/topups';

            $jsonData = \postRequest($header, $body, $url);

            logInfo($jsonData);


            if (isset($jsonData["status"])) {
                if ($jsonData["status"] == "SUCCESSFUL") {
                    return ["status" => true, "message" => "Airtime Purchase Successful"];
                } else {
                    return ["status" => false, "message" => "Failed to Purchase Airtime"];
                }
            } else if (isset($jsonData["errorCode"])) {
                return ["status" => false, "message" => $jsonData["message"]];
            }else{
                return ["status" => false, "message" => "Failed to Purchase Airtime"];
            }
        }
    }


    function loginReloadly()
    {

        $clientId = '';
        $clientSecret = '';

        if (env('APP_MODE') == 'test') {
            $clientId = env('RELOADLY_CLIENT_ID_TEST');
            $clientSecret = env('RELOADLY_CLIENT_SECRET_TEST');
        } else {
            $clientId = env('RELOADLY_CLIENT_ID_LIVE');
            $clientSecret = env('RELOADLY_CLIENT_SECRET_LIVE');
        }
        $clientId = env('RELOADLY_CLIENT_ID_LIVE');
        $clientSecret = env('RELOADLY_CLIENT_SECRET_LIVE');

        $body = [

            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "grant_type" => "client_credentials",
            "audience" => "https://topups.reloadly.com"

        ];

        $url = "https://auth.reloadly.com/oauth/token";

        $loginResult = postRequest(["Content-Type" => "application/json"], $body, $url);

        return isset($loginResult['access_token']) ? $loginResult['access_token'] : null;
    }

    public function reloadlyLogin()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://auth.reloadly.com/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "client_id":"000000",
                "client_secret":"00000",
                "grant_type":"client_credentials",
                "audience":"https://topups.reloadly.com"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }


    public function getNetworkOperators()
    {
        $reloadlyLogin = $this->reloadlyLogin();

        if (isset($reloadlyLogin['access_token'])) {

            $token = $reloadlyLogin['access_token'];

            $header = ["Authorization" => "Bearer $token"];

            $countryCode = \auth()->user()->company->country->code;

            $newCode = \strtolower($countryCode);

            $networkProviders = [];

            if ($countryCode == "NG") {

                $networkProviders = [
                    ["id" => "mtn", "name" => "MTN Nigeria"],
                    ["id" => "airtel", "name" => "Airtel Nigeria"],
                    ["id" => "glo", "name" => "Glo Nigeria"],
                    ["id" => "etisalat", "name" => "9Mobile (Etisalat) Nigeria"]
                ];
            } else {
                $url = "https://topups.reloadly.com/operators/countries/$newCode?includeBundles=false&includeData=false&includePin=false&suggestedAmounts=false&suggestedAmountsMap=false";

                $newtorkOperators = \getRequest($header, [], $url);


                foreach ($newtorkOperators as $operator) {
                    if ($operator['data'] == false & $operator['bundle'] == false) {
                        $networkProviders[] = $operator;
                    }
                }
            }

            return \response()->json(['status' => 1, 'message' => 'Operators fetched successfully' . $newCode, 'data' => $networkProviders]);
        } else {
            return \response()->json(['status' => 0, 'message' => 'Failed to get operators', 'data' => $reloadlyLogin]);
        }
    }
}
