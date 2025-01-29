<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TryluxController extends WebhookController
{
    private $baseUrl;
    private $authorization;
    private $terminalId;
    private $token;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {
            $this->baseUrl = config('trylux.base_url_test');
            $this->terminalId = config('trylux.terminal_id_test');
            $this->authorization = config('trylux.authorization_test');
        } else {
            $this->baseUrl = config('trylux.base_url_live');
            $this->terminalId = config('trylux.terminal_id_live');
            $this->authorization = config('trylux.authorization_live');
        }
    }

    public function resdTransaction($tryluxPayload)
    {
        $body = [
            "pan" => $tryluxPayload->pan,
            "stan" => $tryluxPayload->stan,
            "rrn" => $tryluxPayload->rrn,
            "amount" => $tryluxPayload->amount,
            "iccData" => $tryluxPayload->iccData,
            "track2Data" => $tryluxPayload->trackData,
            "postDataCode" => $tryluxPayload->postDataCode,
            "cardExpiryDate" => $tryluxPayload->cardExpDate,
            "acquiringInstitutionalCode" => $tryluxPayload->acquiringCode,
            "sequenceNumber" => $tryluxPayload->sequenceNumber,
            "pin" => $tryluxPayload->pin,
            "accountType" => "SAVINGS",
            "type" => "CARD"
        ];

        $this->terminalId = $tryluxPayload->terminalId;

        logInfo($body, "Trylux request body");

        try {
            $cardResult = $this->tryluxPostRequest('resd/transaction', $body);

            logInfo($cardResult, "Trylux Fund card result body");

            if ($cardResult['status']) {
                return ['status' => $cardResult['status'], 'message' => "Transaction completed successfully", 'data' => $cardResult['data']];
            } else {
                return ['status' => $cardResult['status'], 'message' => $cardResult['message']];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Trylux Error");
            return ['status' => false, 'message' => "Failed to Fund Card... Please contact support"];
        }
    }

    public function cardBalance($tryluxPayload)
    {
        $body = [
            "pan" => $tryluxPayload->pan,
            "stan" => $tryluxPayload->stan,
            "rrn" => $tryluxPayload->rrn,
            "amount" => $tryluxPayload->amount,
            "iccData" => $tryluxPayload->iccData,
            "track2Data" => $tryluxPayload->trackData,
            "postDataCode" => $tryluxPayload->postDataCode,
            "cardExpiryDate" => $tryluxPayload->cardExpDate,
            "acquiringInstitutionalCode" => $tryluxPayload->acquiringCode,
            "sequenceNumber" => $tryluxPayload->sequenceNumber,
            "pin" => $tryluxPayload->pin,
            "accountType" => "SAVINGS",
            "type" => "CARD"
        ];

        $this->terminalId = $tryluxPayload->terminalId;

        logInfo($body, "Trylux request body");

        try {
            $cardResult = $this->tryluxPostRequest('resd/card-balance', $body);

            logInfo($cardResult, "Trylux Fund card result body");

            if ($cardResult['status']) {
                return ['status' => $cardResult['status'], 'message' => "Transaction completed successfully", 'data' => $cardResult['data']];
            } else {
                return ['status' => $cardResult['status'], 'message' => $cardResult['message']];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Trylux Error");
            return ['status' => false, 'message' => "Failed to Fund Card... Please contact support"];
        }
    }

    public function retryTransaction($tryluxPayload)
    {
        $body = [
            "pan" => $tryluxPayload->pan,
            "stan" => $tryluxPayload->stan,
            "rrn" => $tryluxPayload->rrn,
            "amount" => $tryluxPayload->amount,
            "iccData" => $tryluxPayload->iccData,
            "track2Data" => $tryluxPayload->trackData,
            "postDataCode" => $tryluxPayload->postDataCode,
            "cardExpiryDate" => $tryluxPayload->cardExpDate,
            "acquiringInstitutionalCode" => $tryluxPayload->acquiringCode,
            "sequenceNumber" => $tryluxPayload->sequenceNumber,
            "pin" => $tryluxPayload->pin,
            "accountType" => $tryluxPayload->accountType,
            "type" => "CARD",
            "transactionCurrency" => $tryluxPayload->transactionCurrency
        ];

        $this->terminalId = $tryluxPayload->terminalId;

        logInfo($body, "Trylux request body");

        try {
            $cardResult = $this->tryluxPostRequest('resd/transaction/retry', $body);

            logInfo($cardResult, "Trylux Fund card result body");

            if ($cardResult['status']) {
                return ['status' => $cardResult['status'], 'message' => "Transaction completed successfully", 'data' => $cardResult['data']];
            } else {
                return ['status' => $cardResult['status'], 'message' => $cardResult['message']];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Trylux Error");
            return ['status' => false, 'message' => "Failed to Fund Card... Please contact support"];
        }
    }

    public function resdNetworkManagement($tryluxPayload)
    {
        $body = [
            "stan" => $tryluxPayload->stan,
            "onlyAccountInfo" => $tryluxPayload->onlyAccountInfo,
            "serialNumber" => $tryluxPayload->serialNumber
        ];

        logInfo($body, "Trylux request body");

        try {
            $cardResult = $this->tryluxPostRequest('resd/network-mgt', $body);

            logInfo($cardResult, "Trylux Fund card result body");

            if ($cardResult['status']) {
                return ['status' => $cardResult['status'], 'message' => "Network Management Fetched successfully", 'data' => $cardResult['data']];
            } else {
                return ['status' => $cardResult['status'], 'message' => $cardResult['message']];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Trylux Error");
            return ['status' => false, 'message' => "Server error... Please contact support"];
        }
    }

    public function getPhysicalCardBalance($cardNumber)
    {

        logInfo($cardNumber, "getPhysicalCardBalance request body");

        $url = "PrepaidCardGetCardBalance?CardNo=$cardNumber&clientSecret=wired_banking&clientId=w!r39_6A4k1n9.P0";

        try {
            $cardResult = $this->tryluxGetRequest($url);

            logInfo($cardResult, "Trylux Fund card result body");

            if (isset($cardResult['StatusCode'])) {
                if ($cardResult['StatusCode'] == "200") {
                    $decodedResult = json_decode($cardResult["JsonData"], true);
                    return ['status' => true, 'message' => "Card Balance gotten Successfully", "data" => ['available_balance' => \moneyFormat($decodedResult['availableBalance'] / 100)]];
                } else {
                    return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Trylux Error");
            return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
        }
    }


    public function tryluxPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->tryluxHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function tryluxGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->tryluxHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function tryluxHeader()
    {
        $header = [
            'Content-Type' => 'application/json',
            'terminalId' => $this->terminalId,
            'Authorization' => "Basic $this->authorization",
        ];

        return $header;
    }

    public function login()
    {
        $body = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "grant_type" => "client_credentials"
        ];

        $header = [
            'Content-Type' => 'application/json',
            'accountId' => $this->accountId,
        ];

        try {

            $response = Http::withHeaders($header)->post($this->baseUrl . "auth/token/issue", $body);

            $response = $response->json();

            logInfo($response, "Trylux Login");

            if ($response['code'] == "00") {
                $this->token = $response["data"]['access_token'];
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => $response['description']];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Trylux Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }
}
