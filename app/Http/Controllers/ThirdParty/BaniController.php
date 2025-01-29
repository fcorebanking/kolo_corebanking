<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BaniController extends WebhookController
{

    private $merchantKey;
    private $tribeAccountRef;
    private $publicKey;
    private $accessToken;
    private $baseUrl;
    private $token;

    function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->merchantKey = config('bani.merchant_key_test');
            $this->tribeAccountRef = config('bani.tribe_account_ref_test');
            $this->publicKey = config('bani.public_key_test');
            $this->accessToken = config('bani.access_token_test');
            $this->baseUrl = config('bani.base_url_test');
        } else {
            $this->merchantKey = config('bani.merchant_key_live');
            $this->tribeAccountRef = config('bani.tribe_account_ref_live');
            $this->publicKey = config('bani.public_key_live');
            $this->accessToken = config('bani.access_token_live');
            $this->baseUrl = config('bani.base_url_live');
        }
    }



    public function createAccount($firstName, $lastName, $phone, $address, $email, $bankName, $bvn)
    {

        $newPhone = formatPhoneNumber($phone, "+234");
        $customerName = "$firstName $lastName";

        if ($bankName != 'guaranty trust bank') {

            $body = [
                'customer_phone' => "+$newPhone",
                'customer_first_name' => $firstName,
                'customer_last_name' => $lastName,
                'customer_email' => $email,
                'customer_address' => $address,
                'alternate_name' => $customerName . "-WL",
                'bank_name' => $bankName,
            ];
        } else {
            $body = [
                'customer_phone' => "+$newPhone",
                'customer_first_name' => $firstName,
                'customer_last_name' => $lastName,
                'customer_email' => $email,
                'customer_address' => $address,
                'bank_name' => $bankName,
            ];
        }

        logInfo($body, "Bani Ccreate Account request");

        try {

            $url = "comhub/add_my_customer";

            $response = $this->baniPostRequest($url, $body);

            logInfo($response, "Bani Ccreate Account Response");

            if ($response['status'] && $response['status_code'] == 201) {

                sleep(3);

                $customerRef = $response['customer_ref'];

                $result = $this->getAccountDetails($customerRef, $bankName, $customerName, $bvn);

                if ($result['status']) {

                    return [
                        'status' => true,
                        'message' => $result['message'],
                        'data' => [
                            'virtual_account_number' => $result['data']['holder_account_number'],
                            'account_name' => $result['data']['account_name'],
                            'bank_name' => $result['data']['holder_bank_name'],
                            'account_reference' => $customerRef
                        ]
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => $result['message']
                    ];
                }
            } else {
                return ['status' => false, 'message' => $response['message']];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Bani Create account serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }


    public function generateSignature($digest)
    {
        $secret = $this->merchantKey;

        $signature = hash_hmac('sha256', $digest, $secret);
        return $signature;
    }


    public function getAccountDetails($customerRef, $bankName, $customerName, $bvn)
    {

        if ($bankName != 'guaranty trust bank') {

            $body = [
                'pay_va_step' => 'direct',
                'country_code' => 'NG',
                'pay_currency' => 'NGN',
                'holder_account_type' => 'permanent',
                'customer_ref' => $customerRef,
                'bank_name' => $bankName,
                'holder_legal_number' => $bvn,
                'alternate_name' => $customerName . "-WL"
            ];
        } else {

            $body = [
                'pay_va_step' => 'direct',
                'country_code' => 'NG',
                'pay_currency' => 'NGN',
                'holder_account_type' => 'permanent',
                'customer_ref' => $customerRef,
                'bank_name' => $bankName,
                'holder_legal_number' => $bvn
            ];
        }


        $url = "partner/collection/bank_transfer";

        try {

            logInfo($body, "Get Account request log");

            $response = $this->baniPostRequest($url, $body);

            logInfo($response, "Get Account response log");

            if ($response['status'] && $response['status_code'] == 201) {

                return ['status' => true, 'message' => 'Customer Account Fetched successfully', 'data' => $response];
            } else {
                return ['status' => false, 'message' => 'Failed to get Account Details'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "bani Get account serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {

        logInfo($request->header(), "All headers of the webhook");

        //Check if payload has
        if (!$request->hasHeader('BANI-HOOK-SIGNATURE')) {
            return ['status' => false, 'message' => "Unauthorized: Authorization token is required"];
        }

        $signature = $request->header('BANI-HOOK-SIGNATURE');
        $jsonRequest = $request->getContent();

        $secret = $this->generateSignature($jsonRequest);

        if ($secret != $signature) {
            return ['status' => false, 'message' => 'Invalid webhook signature'];
        }
        return ['status' => true, 'message' => 'Webhook signature verified successfully'];
    }


    public function baniPostRequest($endpoint, $body)
    {
        logInfo("$this->baseUrl$endpoint");
        logInfo($this->baniHeader());
        $response = Http::withHeaders($this->baniHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function baniGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint");
        $response = Http::withHeaders($this->baniHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function baniHeader()
    {
        $digest = $this->tribeAccountRef . $this->publicKey;

        $moniSignature = $this->generateSignature($digest);

        $header = [
            'Content-Type' => 'application/json',
            'moni-signature' => $moniSignature,
            'Authorization' => "Bearer $this->accessToken"
        ];

        return $header;
    }
    public function payoutBankTransferToOtherAfricaCountries($body)
    {
        $endpoint = 'partner/payout/initiate_transfer/';
        // "payout_step":"direct",
        // "receiver_currency":"KES",
        // "receiver_amount":"1000",
        // "transfer_method":"bank",
        // "transfer_receiver_type":"personal",
        // "receiver_account_num":"0142428721",
        // "receiver_country_code":"KE",
        // "receiver_sort_code":"0003",
        // "receiver_account_name":"Joseph Donga",
        // "sender_amount":"1000",
        // "sender_currency":"KES",
        // "transfer_note":"chocolate bars"
        return $this->baniPostRequest($endpoint, $body);
    }
    public function africanCollectionPaywithMobileMoney($body)
    {
        $endpoint = 'partner/collection/mobile_money/';
        // {
        //     "pay_va_step":"direct",
        //     "country_code":"ZM",
        //     "pay_amount":"105.24",
        //     "pay_currency":"ZMW",
        //     "holder_phone":"+260966750759",
        //     "holder_ip_address":"47.58.201.124",
        //     "holder_device_tag":"iphone",
        //     "customer_ref":"MC-DHEG123EHEH33H2H2",
        //     "pay_ext_ref":"12gddhetwdgedgdge",
        //     "custom_data":{"color":"blue","item_name":"radio"}
        //  }
        return $this->baniPostRequest($endpoint, $body);
    }
    public function listSupportedMomo()
    {
        $endpoint = 'partner/list_supported_momo/';
        return $this->baniGetRequest($endpoint);
    }
    public function fiatConversion($body)
    {
        $endpoint = 'partner/collection/fiat/';
        return $this->baniPostRequest($endpoint, $body);
    }
}
