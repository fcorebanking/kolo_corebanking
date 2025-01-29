<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DojahController extends Controller
{
    private $authorization;
    private $appId;
    private $baseUrl;


    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->authorization = config('dojah.authorization_test');
            $this->appId = config('dojah.app_id_test');
            $this->baseUrl = config('dojah.base_url_test');
        } else {

            $this->authorization = config('dojah.authorization_live');
            $this->appId = config('dojah.app_id_live');
            $this->baseUrl = config('dojah.base_url_live');
        }

        \logInfo($this->baseUrl, "Dojah Base Url");
    }


    public function verifyBVN($bvn)
    {
        $url = "kyc/bvn/advance?bvn=$bvn";

        logInfo("getting bvn details for $bvn");

        try {
            $exhangeRate = $this->dojahGetRequest($url);
            if (isset($exhangeRate['entity'])) {
                return [
                    'status' => true,
                    'message' => 'BVN Verified Successfuly', 'data' => $exhangeRate['entity']
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify BVN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Verify BVN'];
        }
    }

    public function verifyNIN($nin)
    {
        $url = "kyc/vnin?vnin=$nin";

        logInfo("getting nin details for $nin");

        try {
            $verifyNIN = $this->dojahGetRequest($url);

            logInfo($verifyNIN, "Verify NIN Rates");

            if (isset($verifyNIN['entity'])) {

                return [
                    'status' => true,
                    'message' => 'NIN Verified Successfuly', 'data' => $verifyNIN['entity']
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify NIN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Verify NIN'];
        }
    }

    public function verifyDriverLicense($licenseNo)
    {
        $url = "kyc/dl?license_number=$licenseNo";

        logInfo("getting nin details for $licenseNo");

        try {
            $driverLicense = $this->dojahGetRequest($url);

            logInfo($driverLicense, "Verify Driver License");

            if (isset($driverLicense['entity'])) {

                return [
                    'status' => true,
                    'message' => 'Driver License Verified Successfuly', 'data' => $driverLicense['entity']
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to Drivers License'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Drivers License'];
        }
    }

    public function dojahPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint");
        $response = Http::withHeaders($this->dojahHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function dojahGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        logInfo("$this->authorization", "Authorization key");
        $response = Http::withHeaders($this->dojahHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }


    public function dojahHeader()
    {

        $header = [
            "Content-Type" => "application/json",
            "Authorization" => $this->authorization,
            "AppId" => $this->appId,
        ];

        return $header;
    }
}
