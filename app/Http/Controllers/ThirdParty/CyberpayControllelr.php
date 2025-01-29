<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CyberpayControllelr extends Controller
{
    private $api_key;
    private $integration_key;
    private $baseUrl;

    function __construct()
    {
        if (env('APP_MODE') == 'test') {
            $this->api_key = config('cyberpay.api_key_test');
            $this->integration_key = config('cyberpay.integration_key_test');
            $this->baseUrl = config('cyberpay.base_url_test');
        } else {
            $this->api_key = config('cyberpay.api_key_live');
            $this->integration_key = config('cyberpay.integration_key_live');
            $this->baseUrl = config('cyberpay.base_url_live');
        }
    }

    public function createAccount(
        $tagName,
        $payerName,
        $payerAddress,
        $emailAddress,
        $phoneNumber,
        $bank,
        $accountNumber,
        $accountName,
        $narration,
        $amount,
        $variableAmounts,
        $startDate,
        $endDate,
        $firstPaymentDate,
        $subscriberCode,
        $repeat,
        $frequency,
        $mandateFormExtension,
        $mandateFormBase64Image
    ) {

        $body = [
            'IntegrationKey' => $this->integration_key,
            'TagName' => $tagName,
            'MandateRequests' => [
                array(
                    "payerName" => $payerName,
                    "payerAddress" => $payerAddress,
                    "emailAddress" => $emailAddress,
                    "phoneNumber" => $phoneNumber,
                    "bankCode" => $bank,
                    "accountNumber" => $accountNumber,
                    "accountName" => $accountName,
                    "narration" => $narration,
                    "amount" => $amount,
                    "variableAmounts" => $variableAmounts,
                    "startDate" => date("Ymd", strtotime($startDate)),
                    "endDate" => date("Ymd", strtotime($endDate)),
                    "firstPaymentDate" => date("Ymd", strtotime($firstPaymentDate)),
                    "subscriberCode" => $subscriberCode,
                    "repeat" => $repeat,
                    "frequency" => $frequency,
                    "businessProductCode" => "LOAN",
                    "productId" => 746,
                    "callbackUrl" => "yourendpoint/webhook",
                    "mandateFile" => [
                        "fileExtension" => $mandateFormExtension,
                        "fileBase64EncodedString" => $mandateFormBase64Image
                    ]
                )
            ]
        ];

        $url = "api/v1/directdebit/mandate";

        // logInfo($body, "Cyberpay mandate Request");

        try {

            $createAccountResponse = $this->cyberpayPostRequest($url, $body);

            logInfo($createAccountResponse, "Cyberpay Create Mandate Response");

            if ($createAccountResponse["succeeded"]) {

                if ($createAccountResponse["data"]["isSuccessful"]) {
                    return [
                        'status' => true,
                        'data' => $createAccountResponse['data']['itemList'][0],
                        'message' => "Mandate Created successfully"
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => $createAccountResponse["message"],
                    ];
                }
            } else {
                return ['status' => false, 'message' => $createAccountResponse["message"]];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Cyberpay Error");

            return ['status' => false, 'message' => 'Mandate Creation Error. Please contact support'];
        }
    }


    public function verifyMandateStatus($transactionReference)
    {

        $url = "api/v1/directdebit/mandates/status";

        $verifyPaymentResponse = $this->cyberpayPostRequest($url, [$transactionReference]);

        try {
            logInfo($verifyPaymentResponse, "Verifying Mandate Response");

            if ($verifyPaymentResponse['succeeded']) {
                if ($verifyPaymentResponse['data'][0]['status'] == "Active") {
                    return ['status' => true, 'message' => "Mandate status verified successfully", 'data' => $verifyPaymentResponse['data'][0]];
                } else {
                    return ['status' => false, 'message' => "Invalid mandate reference"];
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


    public function cyberpayBaseUrl($endpoint)
    {
        return "$this->baseUrl$endpoint";
    }

    public function cyberpayGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->cyberpayHeader())->get($this->cyberpayBaseUrl($endpoint));
        return $response->json();
    }

    public function cyberpayPostRequest($endpoint, $body)
    {
        logInfo($this->cyberpayBaseUrl($endpoint), "Complete url");
        $response = Http::withHeaders($this->cyberpayHeader())->post($this->cyberpayBaseUrl($endpoint), $body);
        return $response->json();
    }


    public function cyberpayHeader()
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'ApiKey' => $this->api_key,

        ];
    }
}
