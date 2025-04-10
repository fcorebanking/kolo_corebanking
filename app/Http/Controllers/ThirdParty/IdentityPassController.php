<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Http;

class IdentityPassController extends Controller
{
    private $authorization;
    private $appId;
    private $baseUrl;
    private $appKey;

    public function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->authorization = config('identitypass.authorization_test');
            $this->appId         = config('identitypass.app_id_test');
            $this->baseUrl       = config('identitypass.base_url_test');
            $this->appKey        = config('identitypass.app_key_test');

        } else {

            $this->authorization = config('identitypass.authorization_live');
            $this->appId         = config('identitypass.app_id_live');
            $this->baseUrl       = config('identitypass.base_url_live');
            $this->appKey        = config('identitypass.app_key_live');
        }

        \logInfo($this->baseUrl, "Dojah Base Url");
    }

    public function verifyBVN($bvn)
    {
        $url = "kyc/bvn/advance?bvn=$bvn";

        logInfo("getting bvn details for $bvn from identitypass");

        try {
            $exhangeRate = $this->identitypassGetRequest($url);
            if (isset($exhangeRate['entity'])) {
                return [
                    'status'  => true,
                    'message' => 'BVN Verified Successfuly', 'data' => $exhangeRate['entity'],
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
            $verifyNIN = $this->identitypassGetRequest($url);

            logInfo($verifyNIN, "Verify NIN Rates");

            if (isset($verifyNIN['entity'])) {

                return [
                    'status'  => true,
                    'message' => 'NIN Verified Successfuly', 'data' => $verifyNIN['entity'],
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
            $driverLicense = $this->identitypassGetRequest($url);

            logInfo($driverLicense, "Verify Driver License");

            if (isset($driverLicense['entity'])) {

                return [
                    'status'  => true,
                    'message' => 'Driver License Verified Successfuly', 'data' => $driverLicense['entity'],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to Drivers License'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Drivers License'];
        }
    }

    public function verifyCAC($company_type, $company_no)
    {
        $url = "{$this->baseUrl}/verification/cac";

        logInfo($company_no, "RC Number");
        logInfo($company_type, "Company Type");

        $body = [
            'rc_number'    => $company_no,
            'company_type' => $company_type,
        ];

        try {
            $cac = $this->identitypassPostRequest($url, $body);

            logInfo($cac, "Verify CAC");

            if (isset($driverLicense['status'])) {

                return [
                    'status'  => true,
                    'message' => 'Driver License Verified Successfuly', 'data' => $driverLicense['entity'],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to Drivers License'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Drivers License'];
        }
    }

    public function identitypassPostRequest($endpoint, $body)
    {
        \logInfo("$endpoint");
        $response = Http::withHeaders($this->identitypassHeader())->post("$endpoint", $body);
        return $response->json();
    }

    public function identitypassGetRequest($endpoint)
    {
        logInfo("$this->baseUrl $endpoint", "Convertion url");
        logInfo("$this->$appKey", "Authorization key");
        $response = Http::withHeaders($this->identitypassHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function identitypassHeader()
    {

        $header = [
            "Content-Type" => "application/json",
            "app_id"       => $this->appId,
            "x-api-key"    => $this->$appKey,
        ];

        return $header;

    }
}
