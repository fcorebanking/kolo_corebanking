<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\GtbWebhook;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class GTBController extends WebhookController
{
    private $secretKey;
    private $publicKey;
    private $beneficiaryAccount;
    private $baseUrl;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {
            $this->secretKey = config('gtb.secret_key_live');
            $this->publicKey = config('gtb.public_key_live');
            $this->beneficiaryAccount = config('gtb.beneficiary_account_live');
            $this->baseUrl = config('gtb.base_url_live');
        } else {
            $this->secretKey = config('gtb.secret_key_live');
            $this->publicKey = config('gtb.public_key_live');
            $this->beneficiaryAccount = config('gtb.beneficiary_account_live');
            $this->baseUrl = config('gtb.base_url_live');
        }
    }

    public function createVirtualAccount($firstName, $lastName, $dob, $gender, $bvn, $email, $phone, $address, $customerReference)
    {

        $body = [
            "first_name" => $firstName,
            "last_name" => $lastName,
            "middle_name" => $firstName,
            "mobile_num" => $phone,
            "dob" => date("m/d/Y", strtotime($dob)),
            "email" => $email,
            "bvn" => $bvn,
            "gender" => $gender == "Male" ? '1' : '2',
            "address" => $address,
            "customer_identifier" => $customerReference
        ];

        $endpoint = "virtual-account";

        logInfo($body, "GT Request body");
        try {
            $createAccount = $this->gtbPostRequest($endpoint, $body);

            logInfo($createAccount, "GTB Response");

            if (isset($createAccount['status'])) {
                if ($createAccount['status'] == 200) {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "GTB Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function createCompanyVirtualAccount($businessName, $bvn, $phone, $customerReference)
    {

        $body = [
            "business_name" => $businessName,
            "mobile_num" => $phone,
            "bvn" => $bvn,
            "customer_identifier" => $customerReference
        ];

        $endpoint = "virtual-account/business";

        logInfo($body, "GT Request body");
        try {
            $createAccount = $this->gtbPostRequest($endpoint, $body);

            logInfo($createAccount, "GTB Response");

            if (isset($createAccount['status'])) {
                if ($createAccount['status'] == 200) {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "GTB Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {
        logInfo($request, "GTB Webhook Incoming");

        $signature = $request->header('x-squad-signature');

        $localSignature = env('APP_MODE') == 'live' ? config('gtb.secret_key_live') : config('gtb.secret_key_live');

        if ($signature !== hash_hmac('sha512',  json_encode($request->all(), JSON_UNESCAPED_SLASHES), $localSignature)) {
            return ['status' => false, 'message' => 'he Webhook request is not from SQAD', 'response_code' => 400, 'transaction_reference' => $request->transaction_reference, 'response_description' => "The Webhook request is not from SQAD"];
        }

        return ['status' => true, 'message' => 'Webhook Verified Successfully'];
    }

    public function insertIntoWebhookTable(Request $request)
    {

        $webhook = GtbWebhook::where('transaction_reference', $request->transaction_reference)->first();

        if (!$webhook) {

            GtbWebhook::create([
                'transaction_reference' => $request->transaction_reference,
                'virtual_account_number' => $request->virtual_account_number,
                'principal_amount' => $request->principal_amount,
                'settled_amount' => $request->settled_amount,
                'fee_charged' => $request->fee_charged,
                'transaction_date' => $request->transaction_date,
                'customer_identifier' => $request->customer_identifier,
                'transaction_indicator' => $request->transaction_indicator,
                'remarks' => $request->remarks,
                'currency' => $request->currency,
                'channel' => $request->channel,
                'encrypted_body' => $request->encrypted_body
            ]);
        }
    }

    public function getSenderName($senderString)
    {
        $firstPart = explode("|", $senderString)[0];
        return substr($firstPart, 14);
    }


    public function gtbPostRequest($endpoint, $body)
    {

        \logInfo("$this->baseUrl$endpoint", "Endpoint log");
        logInfo($this->gtbHeader(), "gtb header");
        logInfo($body, "gtb body");

        $response = Http::withHeaders($this->gtbHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function gtbGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "gtb route");
        logInfo($this->gtbHeader(), "gtb header");

        $response = Http::withHeaders($this->gtbHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function gtbHeader()
    {

        logInfo(env('APP_MODE'), "API key");
        $header = [];
        $header =  [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->secretKey"
        ];

        return $header;
    }
}
