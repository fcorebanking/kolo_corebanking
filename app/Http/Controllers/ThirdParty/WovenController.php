<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\WovenWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class WovenController extends WebhookController
{

    private $appSecret;
    private $appKey;
    private $baseUrl;
    private $settlementAccount;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->appSecret = config('woven.app_secret_test');
            $this->appKey = config('woven.api_key_test');
            $this->baseUrl = config('woven.base_url_test');
            $this->settlementAccount = config('woven.settlement_account_test');
        } else {
            $this->appSecret = config('woven.app_secret_live');
            $this->appKey = config('woven.api_key_live');
            $this->baseUrl = config('woven.base_url_live');
            $this->settlementAccount = config('woven.settlement_account_live');
        }
    }

    public function createCustomerAccount($accountName, $email, $phone, $bank, $customer_reference)
    {

        // $accountName = $isCorpoerate ? $request->company_name : $request->first_name . ' ' . $request->last_name;

        $body = [
            'customer_reference' => $customer_reference,
            'name' => $accountName,
            'email' => $email,
            'collection_bank' => $bank,
            'destination_nuban' => $this->settlementAccount,
            'mobile_number' => preg_replace('/[^A-Za-z0-9\-]/', '', $phone),

        ];

        try {
            $response = $this->wovenPostRequest('vnubans/create_customer', $body);

            logInfo($response, "Woven Create Account Body");

            if (isset($response['status'])) {
                if ($response['status'] == "success") {
                    return ['status' => true, 'message' => $response['message'], 'data' => $response['data']];
                } else {
                    return ['status' => false, 'message' => $response['message']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account Card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Woven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }


    public function createVirtualAccount($name, $email, $phone, $customerReference)
    {

        $body = [
            "customer_reference" => $customerReference,
            "name" => $name,
            "email" => $email,
            "collection_bank" => "060001",
            "destination_nuban" => $this->settlementAccount,
            "mobile_number" => $phone,
        ];

        $endpoint = "vnubans/create_customer";

        try {
            $createAccount = $this->wovenPostRequest($endpoint, $body);

            if (isset($createAccount['status'])) {
                if ($createAccount['status'] == "success") {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Woven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }


    public function verifyBankAccountNumber($bankCode, $accountNumber)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.woven.finance/v2/api/nuban/enquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "account_number=$accountNumber&bank_code=$bankCode",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'api_secret:  ver'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    public function verifyBankAccountNumberGuzzle($bankCode, $accountNumber)
    {
        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'api_secret' => 'ver',
        ];

        $body = [
            "account_number" => $accountNumber,
            "bank_code" => $bankCode
        ];

        $url = $this->baseUrl . "nuban/enquiry";

        try {

            $result = \postAsFormRequest($header, $body, $url);

            \logInfo($result);

            if ($result['status'] == 'success') {
                $accountName = $result["data"]["account_name"];
                $bankName = $result["data"]["bank_name"];

                return [
                    'status' => true,
                    'message' => "Account Verified Successfully",
                    'data' => [
                        'account_name' => $accountName,
                        "bank_name" => $bankName
                    ]
                ];
            } else {
                return ["status" => false, 'message' => "Failed to Verify Bank Account"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Verify Account Error Log");
            return ["status" => false, 'message' => "Failed to Verify Bank Account", "data" => $e->getMessage()];
        }
    }

    public function transferToBank($destination_account, $firstName, $bank_code, $description, $amount, $transaction_reference)
    {

        $new_description = \urlencode($description);

        $url = "payouts/request?command=initiate&";
        $url .= "source_account=8386023627&PIN=2222&beneficiary_nuban=$destination_account&";
        $url .= "beneficiary_account_name=$firstName&";
        $url .= "beneficiary_bank_code=$bank_code&";
        $url .= "currency_code=NGN&narration=$new_description&";
        $url .= "amount=$amount&";
        $url .= "reference=$transaction_reference&sender_name=WBAfrica&bank_code_scheme=NIP";

        try {
            $bankTransfer = $this->wovenPostRequest($url, []);
            \logInfo($bankTransfer, "Woven bank transfer result");
            \logInfo($transaction_reference, "woven Transaction ref result");

            if (isset($bankTransfer["status"])) {
                if (strtolower($bankTransfer["status"]) == "success" || strtolower($bankTransfer["status"]) == "pending") {
                    return ["status" => true, 'message' => "Bank Transfer Successful"];
                } else {
                    return ["status" => false, 'message' => "Bank Transfer Processing"];
                }
            } else {
                return ["status" => false, 'message' => "Bank Transfer Processing"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Woven Transfer Error log");
            return ["status" => false, 'message' => "Bank Transfer failed"];
        }
    }

    public function insertIntoWebhookTable(Request $request)
    {

        $webhook = WovenWebhook::where('unique_reference', $request->unique_reference)->first();

        if (!$webhook) {

            WovenWebhook::create([
                "nuban" => $request->nuban,
                "bank_code" => $request->bank_code,
                "account_name" => $request->account_name,
                "account_email" => $request->account_email,
                "account_mobile_number" => $request->account_mobile_number,
                "is_test" => $request->is_test,
                "account_reference" => $request->account_reference,
                "source_bank_code" => $request->source_bank_code,
                "source_nuban" => $request->source_nuban,
                "source_account_name" => $request->source_account_name,
                "source_bvn" => $request->source_bvn,
                "amount" => $request->amount,
                "fee" => $request->fee,
                "currency" => $request->currency,
                "transaction_time" => $request->transaction_time,
                "nip_session_id" => $request->nip_session_id,
                "unique_reference" => $request->unique_reference,
                "transaction_type" => $request->transaction_type,
                "status" => $request->status,
                "settled_status" => $request->settled_status,
                "merchantId" => $request->merchantId,
                "vnubanId" => $request->vnubanId,
                "requestId" => $request->requestId,
                "amount_payable" => $request->amount_payable,
                "hash" => $request->hash
            ]);
        }
    }

    public function verifyWovenTransaction($transactionReference)
    {

        $verifyPaymentResponse = $this->wovenGetRequest("transactions?unique_reference=$transactionReference");

        try {
            logInfo($verifyPaymentResponse, "Verifying Webhook Tranaction Response");

            if (isset($verifyPaymentResponse['status'])) {
                if ($verifyPaymentResponse['status'] == "success") {
                    if (count($verifyPaymentResponse['data']['transactions'])) {
                        if ($verifyPaymentResponse['data']['transactions'][0]['status'] == 'ACTIVE') {
                            return ['status' => true, 'message' => $verifyPaymentResponse['message'], 'data' => $verifyPaymentResponse['data']['transactions'][0]];
                        } else {
                            return ['status' => false, 'message' => "Failed to verify Transaction"];
                        }
                    } else {
                        return ['status' => false, 'message' => "Invalid transaction reference"];
                    }
                } else {
                    return ['status' => false, 'message' => "Failed to verify Transaction"];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account Card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Woven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }

        return $verifyPaymentResponse->json();
    }

    public function wovenPostRequest($endpoint, $body)
    {

        \logInfo("$this->baseUrl$endpoint", "Endpoint log");
        $response = Http::withHeaders($this->wovenHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function wovenGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Woven route");
        logInfo($this->wovenHeader(), "Woven header");

        $response = Http::withHeaders($this->wovenHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function wovenHeader()
    {
        $header = [];
        $header =  [
            'Content-Type' => 'application/json',
            'api_secret' => $this->appSecret
        ];

        return $header;
    }
}
