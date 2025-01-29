<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\AssetMatrixWebhook;
use App\Models\User;
use App\Models\wb_account_details;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Http;


class AssetMatrixController extends WebhookController
{

    private $publicKey;
    private $secretKey;
    private $baseUrl;
    private $token;

    function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->publicKey = config('assetmatrix.public_key_test');
            $this->secretKey = config('assetmatrix.secret_key_test');
            $this->baseUrl = config('assetmatrix.base_url_test');
        } else {
            $this->publicKey = config('assetmatrix.public_key_live');
            $this->secretKey = config('assetmatrix.secret_key_live');
            $this->baseUrl = config('assetmatrix.base_url_live');
        }
    }

    public function login()
    {

        $body = [
            "publicKey" => $this->publicKey,
            "secretKey" => $this->secretKey
        ];

        $header = ['Content-Type' => 'application/json'];
        try {
            $myResponse = Http::withHeaders($header)->post($this->baseUrl . "auth/login", $body);

            \logInfo($body, "assetmatrix Login BODY");
            \logInfo($myResponse, "assetmatrix Login Response");

            $response = $myResponse->json();

            if ($response["status"] == "00") {
                $this->token = $response["data"]["access_token"];
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => 'Failed to login to Monnify'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Asset Matrix Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->assetMatrixPostRequest($url, $body);

            logInfo($exhangeRate, "Exchange Rates");

            if ($exhangeRate['status'] == 'success') {
                $rate = $exhangeRate["data"]["rate"];
                $amount = $exhangeRate["data"]["source"]["amount"];

                $finalAmount = $amount;

                return [
                    'status' => true,
                    'message' => "Exchange rate received",
                    'data' => [
                        'rate' => $rate,
                        'amount' => $amount,
                        'final_amount' => $finalAmount
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to get Exchange rates'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Flutterwave Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }

    public function createAccount($requestReference, $email, $phoneNumber, $firstName, $lastName, $bvn)
    {

        $body = [
            "firstName" => $firstName,
            "lastName" => $lastName,
            "phone" => $phoneNumber,
            "phoneCountryCode" => "234",
            "bvn" => $bvn,
            "accountType" => "SAVINGS",
            "requestId" => $requestReference,
            "email" => $email

        ];

        $url = "/open";

        try {

            $login = $this->login();

            if (!$login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            \logInfo($body, "Asset Matrix Request body");

            $response = $this->assetMatrixPostRequest($url, $body);

            logInfo($response, "Asset matrix Create Account Response");

            if ($response["status"] == "00") {

                return [
                    'status' => true,
                    'message' => "Account Created Successfully",
                    'data' => [
                        'account_number' => $response['data']['accountNumber']
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Account Creation Failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset matrix Error");

            return ['status' => false, 'message' => 'Error Creating Account. Kindly contact support for assistance'];
        }
    }

    public function createAccountInternal()
    {

        try {
            return [
                'status' => true,
                'message' => "Account Created Successfully",
                'data' => [
                    'account_number' => generateAccountNumber()
                ]
            ];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset matrix Error");

            return ['status' => false, 'message' => 'Error Creating Account. Kindly contact support for assistance'];
        }
    }


    public function generateAndVerifyAssetMatrixAccount()
    {

        $createAccountResponse = $this->createAccountInternal();

        if ($createAccountResponse['status']) {

            $virtualAccountNumber = $createAccountResponse['data']['account_number'];
            // $virtualAccountNumber = $this->sanitizeAccountNumber("NGN", $createAccountResponse['data']['account_number']);
            $accountReference = $virtualAccountNumber;

            $checkVirtualAccount = wb_account_details::where('virtual_accnum', $virtualAccountNumber)->first();

            if ($checkVirtualAccount) {
                $this->generateAndVerifyAssetMatrixAccount();
            } else {

                return [
                    'status' => true,
                    'message' => 'Account Created Successfully',
                    'data' => [
                        'virtual_account' => $virtualAccountNumber,
                        'account_reference' => $accountReference
                    ]
                ];
            }
        }
    }

    public function verifyBankAccount($bankCode, $accountNumber)
    {
        $url = "payout?accountNumber=$accountNumber&bankCode=$bankCode";

        try {

            $login = $this->login();

            if (!$login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            $response = $this->assetMatrixGetRequest($url);

            logInfo($response, "Asset matrix Verify Account Response");

            if ($response['status'] == "00") {

                return [
                    'status' => true,
                    'message' => "Account Verified Successfully",
                    'data' => [
                        'account_name' => $response["data"]["accountName"],
                        'bank_code' => $response["data"]["bankCode"],
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify Account name'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error verifying Bank Account. Kindly contact support for assistance'];
        }
    }

    public function verifyTransaction($transactionReference)
    {
        $url = "https://partner.com/api/v1/accounts/$transactionReference";

        try {

            $response = Http::withHeaders($this->assetMatrixHeader())->get($url);
            return $response->json();
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error verifying Bank Account. Kindly contact support for assistance'];
        }
    }

    public function verifyBVN($bvn)
    {

        $url = "bvn/validate?bvn=$bvn";

        try {

            $login = $this->login();

            if (!$login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            $createAccountResponse = $this->assetMatrixGetRequest($url);

            logInfo($createAccountResponse, "Asset Matrix Verify Account Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (!$response["Status"]) {
                    return [
                        'status' => false,
                        'message' => $response["Message"]
                    ];
                } else {
                    return [
                        'status' => true,
                        'message' => "Account Verified Successfully",
                        'data' => [
                            'account_name' => $response["Data"]["BeneficiaryName"],
                            'bank_code' => $response["Data"]["BeneficiaryBankCode"],
                            'session_id' => $response["Data"]["SessionID"]
                        ]
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Webhook withdrawal failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error completing withdrawal. Kindly contact support for assistance'];
        }
    }

    public function bankTransfer($requestReference, $accountNumber, $bankCode, $amount, $description)
    {

        $body = [
            "beneficiaryAccount" => $accountNumber,
            "beneficiaryBankCode" => $bankCode,
            "amount" => $amount,
            "referenceNumber" => $requestReference,
            "narration" => $description,
        ];

        $url = "core/payout";

        try {

            $login = $this->login();

            if (!$login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            \logInfo($login, "Asset Matrix Request body");

            $createAccountResponse = $this->assetMatrixPostRequest($url, $body);

            logInfo($createAccountResponse, "Asset Matrix Bank Transfer Response");

            if ($createAccountResponse["status"] == "00") {
                return [
                    'status' => true,
                    'message' => "Bank Transfer Successful",
                ];
            } else {
                return ['status' => false, 'message' => 'Bank Transfer failed. If you have been debited kindly contact support for reversal'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error with Bank Transfer. If you have been debited kindly contact support for reversal'];
        }
    }


    public function insertIntoWebhookTable(Request $request)
    {

        $webhook = AssetMatrixWebhook::where('session_id', $request->sessionId)->first();

        if (!$webhook) {
            AssetMatrixWebhook::create([
                "amount" => $request->amount,
                "account_name" => $request->accountName,
                "narration" => $request->narration,
                "session_id" => $request->sessionId,
                "account_number" => $request->accountNumber,
                "source_account_number" => $request->sourceAccountNumber,
                "source_account_name" => $request->sourceAccountName,
            ]);
        }
    }



    public function assetMatrixPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint");
        $response = Http::withHeaders($this->assetMatrixHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function assetMatrixGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        $response = Http::withHeaders($this->assetMatrixHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function assetMatrixHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ];

        return $header;
    }
}
