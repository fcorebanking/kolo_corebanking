<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ErrandPayController extends Controller
{

    private $password;
    private $loginToken;
    private $baseUrl;
    private $token;

    function __construct()
    {
        if (env('APP_MODE') == 'test') {
            $this->password = config('errandpay.password_test');
            $this->loginToken = config('errandpay.login_token_test');
            $this->baseUrl = config('errandpay.base_url_test');
        } else {
            $this->password = config('errandpay.password_live');
            $this->loginToken = config('errandpay.login_token_live');
            $this->baseUrl = config('errandpay.base_url_live');
        }
    }


    public function verifyErrandPayTransaction($transactionReference)
    {

        $loginErrandPay = $this->login();

        if ($loginErrandPay["status"] != "00") {
            return $loginErrandPay;
        }

        $verifyPaymentResponse = $this->errandPayGetRequest("epagentservice/api/v1/GetStatus?reference=$transactionReference&businessCode=A00033");

        try {
            logInfo($verifyPaymentResponse, "Verifying Webhook Tranaction Response");

            if ($verifyPaymentResponse['apiVersion'] == "v1") {

                if ($verifyPaymentResponse['code'] == "200") {
                    if ($verifyPaymentResponse['data']['statusCode'] == "00" || $verifyPaymentResponse['data']['statusCode'] == "200") {
                        return ['status' => "00", 'message' => "Transaction verified successfully"];
                    } else {
                        return ['status' => "02", 'message' => "Invalid transaction reference"];
                    }
                } else {
                    return ['status' => "03", 'message' => "Failed to verify Transaction"];
                }
            } else {
                return ['status' => "04", 'message' => "Invalid API Version number"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Errand Error");
            return ['status' => "05", 'message' => "Failed to Create Account... Please contact support"];
        }

        return $verifyPaymentResponse->json();
    }


    public function login()
    {
        $body = [
            "loginToken" => $this->loginToken,
            "password" => $this->password
        ];

        $header = ['Content-Type' => 'application/json'];
        try {
            $myResponse = Http::withHeaders($header)->post($this->baseUrl . "epagentauth/api/v1/login", $body);

            \logInfo($body, "Errandpay Login BODY");
            \logInfo($this->baseUrl . "epagentauth/api/v1/login", "Errandpay Login BODY");

            $response = $myResponse->json();
            \logInfo($response, "Errandpay Login Response");

            if ($response["code"] == "200") {
                $this->token = $response["data"]["accessToken"];
                return ['status' => "00", 'message' => 'Login success'];
            } else {
                return ['status' => "01", 'message' => 'Failed to login to Monnify'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Errandpay Login serror log");

            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function errandPayPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint", "Endpoint log");
        $response = Http::withHeaders($this->errandPayHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function errandPayGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Errandpay route");
        logInfo($this->errandPayHeader(), "Errandpay header");

        $response = Http::withHeaders($this->errandPayHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function errandPayHeader()
    {
        $header = [];
        $header =  [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->token"
        ];

        return $header;
    }
}
