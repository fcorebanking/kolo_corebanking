<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonnifyController extends WebhookController
{

    private $secretKey;
    private $apiKey;
    private $baseUrl;
    private $token;

    function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->secretKey = config('monnify.secret_key_test');
            $this->apiKey = config('monnify.api_key_test');
            $this->baseUrl = config('monnify.base_url_test');
        } else {
            $this->secretKey = config('monnify.secret_key_live');
            $this->apiKey = config('monnify.api_key_live');
            $this->baseUrl = config('monnify.base_url_live');
        }
    }

    public function login()
    {

        $str = $this->apiKey . ':' . $this->secretKey;
        $base64String = base64_encode($str);

        $header = ["Authorization" => "Basic $base64String"];
        try {
            $response = Http::withHeaders($header)->post($this->baseUrl . "api/v1/auth/login", []);

            $result = $response->json();

            if (isset($result["requestSuccessful"])) {
                if ($result["requestSuccessful"]) {
                    $this->token = $result["responseBody"]["accessToken"];
                    return ['status' => true, 'message' => 'Login success', 'data' => ['token' => $this->token]];
                } else {
                    return ['status' => false, 'message' => 'Failed to login to Monnify'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to login to Monnify'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Monnify Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function bankTransfer($amount, $transactionReference, $description, $bankCode, $accountNumber)
    {
        $body = [
            "amount" => $amount,
            "reference" => $transactionReference,
            "narration" => $description,
            "destinationBankCode" => $bankCode,
            "destinationAccountNumber" => $accountNumber,
            "currency" => "NGN",
            "sourceAccountNumber" => "your account number"
        ];

        try {

            $loginResult = $this->login();

            if (!$loginResult['status']) {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }

            $response = $this->monnifyPostRequest('api/v2/disbursements/single', $body);

            $result = $response;

            \logInfo($result, "Monnify transfer response");

            if ($result["requestSuccessful"]) {
                return ['status' => true, 'message' => 'Bank Transfer Successful', 'data' => $result['responseBody']];
            } else {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Monnify Transfer serror log");

            return ['status' => false, 'message' => 'Server Error...Please contact support to resolve'];
        }
    }

    public function getTransactionStatus($reference)
    {
        try {

            $reference = urlencode($reference);

            $loginMonnify = $this->login();
            if (!$loginMonnify['status']) {
                $loginMonnify['status'] = "01";
                return $loginMonnify;
            }

            $result = $this->monnifyGetRequest("api/v2/disbursements/single/summary?reference=$reference");

            logInfo($result, "Monnify Response");

            if ($result["requestSuccessful"]) {
                $paymentStatus = $result["responseBody"]["status"] == "SUCCESS";
                if ($result["responseBody"]["status"] == "SUCCESS") {
                    return ['status' => "00", 'message' => 'Transaction verified successfully', 'data' => ['payment_status' => $paymentStatus, 'amount_paid' => $result["responseBody"]["amount"]]];
                } else if ($result["responseBody"]["status"] == "FAILED") {
                    return ['status' => "02", 'message' => 'Transaction failed and has been reversed', 'data' => ['payment_status' => $paymentStatus, 'amount_paid' => $result["responseBody"]["amount"]]];
                }else{
                    return ['status' => "04", 'message' => $result["responseMessage"]];
                }
            } else {
                if ($result["responseCode"] == "99") {
                    return ['status' => "03", 'message' => $result["responseMessage"]];
                }
                return ['status' => "04", 'message' => 'Failed to verify transaction'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Monnify Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->flutterWavePostRequest($url, $body);

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

    public function verifyIncomingWebhook(Request $request)
    {

        $stringToEncrypt = config('monnify.secret_key_live') . "|$request->paymentReference|$request->amountPaid|$request->paidOn|$request->transactionReference";

        $hashed = hash("sha512", $stringToEncrypt);

        if (strtolower($request->transactionHash) != $hashed) {
            return ['status' => false, 'message' => "Failed to verify Transaction Hash"];
        }

        return ['status' => true, 'message' => 'Webhook Verify Success'];
    }

    public function monnifyPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->monnifyHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function monnifyGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->monnifyHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function monnifyHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ];

        return $header;
    }
}
