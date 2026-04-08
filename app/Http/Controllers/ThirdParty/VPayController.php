<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VPayController extends WebhookController
{

    private $username;
    private $password;
    private $pubilcKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->username  = config('vpay.username_test');
            $this->password  = config('vpay.password_test');
            $this->pubilcKey = config('vpay.public_key_test');
            $this->secretKey = config('vpay.secret_key_live');
            $this->baseUrl   = config('vpay.base_url_test');
        } else {
            $this->username  = config('vpay.username_live');
            $this->password  = config('vpay.password_live');
            $this->pubilcKey = config('vpay.public_key_live');
            $this->secretKey = config('vpay.secret_key_live');
            $this->baseUrl   = config('vpay.base_url_live');
        }
    }

    public function login()
    {
        $body = [
            "consumerKey"    => $this->pubilcKey,
            "consumerSecret" => $this->secretKey,
            "validityTime"   => "3600",
        ];

        try {
            $response = $this->vPayPostRequestAuth("https://api-apps.vfdbank.systems/vfd-tech/baas-portal/v1.1/baasauth/token", $body);

            logInfo($response, "Vpay Login");

            if ($response['status'] == "00") {
                $this->token = $response['data']['access_token'];
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => $response['message']];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function addCluster($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn)
    {
        $body = [
            'businessname'     => $businessname,
            'email'            => $email,
            'phone'            => $phone,
            'contactfirstname' => $contactfirstname,
            'contactlastname'  => $contactlastname,
            'bvn'              => $bvn,
        ];

        logInfo($body, "Add cluster request");

        try {
            $response = $this->vPayPostRequest("query/merchant/cluster/add", $body);

            if (isset($response['id'])) {
                return ['status' => true, 'message' => 'Cluster Added', 'data' => ['cluster_id' => $response['id']]];
            } else {
                return ['status' => false, 'message' => 'Failed to get Cluster id'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function bankTransfer($nuban, $bank_code, $amount, $remark, $transaction_ref, $clusterId)
    {
        $body = [
            'businessname'    => $nuban,
            'bank_code'       => $bank_code,
            'amount'          => $amount,
            'remark'          => $remark,
            'transaction_ref' => $transaction_ref,
            'clusterId'       => $clusterId,
        ];

        try {
            $response = $this->vPayPostRequest("query/transfer/outbound", $body);

            if (isset($response['id'])) {
                return ['status' => true, 'message' => 'Cluster Added', 'data' => ['cluster_id' => $response['id']]];
            } else {
                return ['status' => false, 'message' => 'Failed to get Cluster id'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function getTill($businessname, $email, $phone, $contactfirstname, $contactlastname, $clusterId)
    {
        $body = [
            'businessname'     => $businessname,
            'email'            => $email,
            'phone'            => $phone,
            'contactfirstname' => $contactfirstname,
            'contactlastname'  => $contactlastname,
            'cluster'          => $clusterId,
        ];

        try {
            $response = $this->vPayPostRequest("query/merchant/cashpoint/till", $body);

            if (isset($response['id'])) {
                return ['status' => true, 'message' => 'Cluster Added', 'data' => ['till_id' => $response['id']]];
            } else {
                return ['status' => false, 'message' => 'Failed to get Cluster id'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function createAccount($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn)
    {
        $body = [
            'email'     => $email,
            'phone'     => $phone,
            'firstname' => $contactfirstname,
            'lastname'  => $contactlastname,
            'bvn'       => $bvn,

        ];

        logInfo($body, "VFD Ccreate Account request");

        try {

            $loginResponse = $this->login();

            if (! $loginResponse['status']) {
                return ['status' => false, 'message' => 'Unable to Authorize Third party'];
            }

            $response = $this->vPayPostRequest("client/individual", $body);

            logInfo($response, "Vpay Create Account");

            if ($response['status'] == "00") {

                return [
                    'status'  => true,
                    'message' => 'Account Created successfully',
                    'data'    => [
                        'virtual_account_number' => $response['data']['accountNo'],

                    ],
                ];
            } else {
                return ['status' => false, 'message' => $response['message']];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {
        //Check if payload has
        if (! $request->hasHeader('x-payload-auth')) {
            return ['status' => false, 'message' => "Unauthorized: Authorization token is required"];
        }

        $signature = $request->header('x-payload-auth');

        $secret = decryptJWT($signature, 'secret');

        if (! $secret['status']) {
            return $secret;
        }

        $localSignature = env('APP_MODE') == 'live' ? config('vpay.secret_key_live') : config('vpay.secret_key_live');

        if ($secret['data']['value'] !== $localSignature) {
            return ['status' => false, 'message' => 'Authorization token does not match'];
        }
        return ['status' => true, 'message' => 'Webhook Verified Successfully'];
    }

    public function vPayPostRequest($endpoint, $body)
    {
        logInfo($this->vPayHeader(), "Header information");
        $response = Http::withHeaders($this->vPayHeader())->post("$this->baseUrl $endpoint", $body);
        return $response->json();
    }

    public function vPayPostRequestAuth($endpoint, $body)
    {
        logInfo($this->vPayHeader(), "Header information");
        $response = Http::post("$endpoint", $body);
        return $response->json();
    }

    public function vPayGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->vPayHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function vPayHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'AccessToken'  => $this->token,
        ];

        return $header;
    }
}
