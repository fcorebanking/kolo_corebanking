<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProvidusController extends WebhookController
{

    private $appId;
    private $appKey;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $xAuthSignature;

    public function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->baseUrl            = config('providus.base_url_test');
            $this->xAuthSignature     = config('providus.auth_signature_test');
            $this->appId              = config('providus.app_id_test');
            $this->appKey             = config('providus.app_key_test');
            $this->clientId           = config('providus.client_id_test');
            $this->clientSecret       = config('providus.client_secret_test');
            $this->clientSecretXpress = config('providus.base_xpress_secret_test');
        } else {
            $this->baseUrl            = config('providus.base_url_live');
            $this->xAuthSignature     = config('providus.auth_signature_live');
            $this->appId              = config('providus.app_id_live');
            $this->appKey             = config('providus.app_key_live');
            $this->clientId           = config('providus.client_id_live');
            $this->clientSecret       = config('providus.client_secret_live');
            $this->clientSecretXpress = config('providus.base_xpress_secret_live');
        }
    }

    public function createPhysicalCard($request, $transactionId)
    {
        $body = [
            "appId"           => $this->appId,
            "appKey"          => $this->appKey,
            "currencyCode"    => "566",
            "MeansOfIdNumber" => "National ID",
            "emailAddress"    => $request->email,
            "firstName"       => $request->first_name,
            "lastName"        => $request->last_name,
            "mobileNr"        => $request->phone,
            "streetAddress"   => $request->address,
            "bvn"             => $request->bvn,
            "PaymentRef"      => $transactionId,
        ];

        logInfo($body, "Providus request body");

        try {
            $cardResult = $this->providusPostRequest('PrepaidCardNewCardRequest', $body);

            logInfo($cardResult, "Providus Result");

            if (isset($cardResult['StatusCode'])) {
                if ($cardResult['StatusCode'] == "00") {
                    return ['status' => true, 'message' => $cardResult['JsonData']];
                } else {
                    return ['status' => false, 'message' => $cardResult['JsonData']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Providus Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function verifyProvidusTransaction($transactionReference)
    {

        $verifyPaymentResponse = $this->providusGetRequest("PiPverifyTransaction_sessionid?session_id=$transactionReference");

        try {
            logInfo($verifyPaymentResponse, "Verifying Webhook Tranaction Response");

            if (isset($verifyPaymentResponse['sessionId'])) {
                return [
                    'status'  => true,
                    'message' => "Transaction verified successfully",
                    'data'    => [
                        'amount' => $verifyPaymentResponse['transactionAmount'],
                    ],
                ];
            } else {
                return ['status' => false, 'message' => "Failed to verify Transaction"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Providus Error");
            return ['status' => false, 'message' => "Failed to Verify Transaction... Please contact support"];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {

        $signature = $request->header('X-Auth-Signature');

        $localSignature = env('APP_MODE') == 'live' ? config('providus.auth_signature_live') : config('providus.auth_signature_live');

        if ($signature != $localSignature) {
            // return ['responseCode' => "02", 'status' => false, "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction " . env('APP_MODE')];
            return ['responseCode' => "02", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"];

        }

        return ['status' => true, 'message' => 'Webhook Verified Successfully'];
    }

    public function getProvidusHeader()
    {

        $stringTxt = "$this->clientId:$this->clientSecret";

        return hash('sha512', $stringTxt);
    }

    public function createAccount($accountName)
    {

        // // $accountName = $isCorpoerate ? $request->company_name : $request->first_name . ' ' . $request->last_name;

        // $unusedAccount = UnusedProvidusAccounts::where('assigned_status', '0')->first();

        // if (!$unusedAccount) {
        //     \logInfo("No Accoumnt found", "Newly selected Account Number");
        //     return ['status' => false, 'message' => "Sorry Providus bank cannot generate account for now. Account re-assigning will resume soon!!"];
        // }

        // $body = [
        //     "account_name" => $accountName,
        //     "bvn" => "",
        //     "account_number" => $unusedAccount->account_number
        // ];

        $body = [
            "account_name" => $accountName,
            "bvn"          => "",
        ];

        logInfo($body, "Providus request body");

        try {
            // $createAccount = $this->providusPostRequest("PiPUpdateAccountName", $body);
            $createAccount = $this->providusPostRequest("PiPCreateReservedAccountNumber", $body);

            logInfo($createAccount, "Providus Result");

            if (isset($createAccount['requestSuccessful'])) {
                if ($createAccount['requestSuccessful']) {

                    return [
                        'status'  => true,
                        'message' => $createAccount['responseMessage'],
                        'data'    => [
                            'account_number' => $createAccount['account_number'],
                        ],
                    ];
                } else {
                    return ['status' => false, 'message' => $createAccount['responseMessage']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create account... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Providus Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    // public function createAccount(Request $request, $isCorpoerate)
    // {

    //     $accountName = $isCorpoerate ? $request->company_name : $request->first_name . ' ' . $request->last_name;

    //     $body = [
    //         "account_name" => $accountName,
    //         "bvn" => $request->bvn
    //     ];

    //     logInfo($body, "Providus request body");

    //     try {
    //         $cardResult = $this->providusPostRequest('PiPCreateReservedAccountNumber', $body);

    //         logInfo($cardResult, "Providus Result");

    //         if (isset($cardResult['requestSuccessful'])) {
    //             if ($cardResult['requestSuccessful']) {
    //                 return [
    //                     'status' => true,
    //                     'message' => $cardResult['responseMessage'],
    //                     'data' => [
    //                         'account_number' => $cardResult['account_number']
    //                     ]
    //                 ];
    //             } else {
    //                 return ['status' => false, 'message' => $cardResult['responseMessage']];
    //             }
    //         } else {
    //             return ['status' => false, 'message' => "Failed to create account... Please contact support"];
    //         }
    //     } catch (Exception $e) {
    //         logInfo($e->getMessage(), "Providus Error");

    //         return ['status' => false, 'message' => "Failed to create card... Please contact support"];
    //     }
    // }

    public function fundPhysicalCard($email, $phone, $amount, $pan, $firstName, $lastName, $transactionId)
    {
        $body = [
            "appId"        => $this->appId,
            "appKey"       => $this->appKey,
            "currencyCode" => "566",
            "emailAddress" => $email,
            "firstName"    => $firstName,
            "lastName"     => $lastName,
            "mobileNr"     => $phone,
            "amount"       => $amount,
            "pan"          => $pan,
            "PaymentRef"   => $transactionId,
        ];

        logInfo($body, "Providus request body");

        try {
            $cardResult = $this->providusPostRequest('PrepaidCardFundCard', $body);

            logInfo($cardResult, "Providus Fund card result body");

            if (isset($cardResult['responseCode'])) {
                if ($cardResult['responseCode'] == "90000") {
                    return ['status' => "00", 'message' => "Card Funded Successfully"];
                } else if ($cardResult['responseCode'] == "99" || $cardResult['responseCode'] == "") {
                    return ['status' => "01", 'message' => "Card funding failed and funds have already been reversed"];
                } else {
                    return ['status' => "02", 'message' => "Card funding is processing.. Kindly check your card balance before you contact support"];
                }
            } else {
                return ['status' => "03", 'message' => "Card funding is processing.. Kindly check your card balance before you contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Providus Error");
            return ['status' => "04", 'message' => "Failed to Fund Card... Please contact support"];
        }
    }

    public function getPhysicalCardBalance($cardNumber)
    {

        logInfo($cardNumber, "getPhysicalCardBalance request body");

        $url = "PrepaidCardGetCardBalance?CardNo=$cardNumber&appId=wired_banking&appKey=w!r39_6A4k1n9.P0";

        try {
            $cardResult = $this->providusGetRequest($url);

            logInfo($cardResult, "Providus Fund card result body");

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
            logInfo($e->getMessage(), "Providus Error");
            return ['status' => false, 'message' => "Failed to Get Card Balance... Please contact support"];
        }
    }

    public function createVirtualAccount(VirtualAccountType $virtualAccount)
    {
        $data = [
            "bvn"         => $virtualAccount->bvn,
            "firstName"   => $virtualAccount->firstName,
            "lastName"    => $virtualAccount->lastName,
            "dateOfBirth" => $virtualAccount->dob,
            "phoneNumber" => $virtualAccount->phone,
            "address"     => $virtualAccount->address,
        ];

        $data_string = json_encode($data);

        \logInfo($data_string, "Providus Request body");

        $curl = curl_init();
        // $bvn = $bvn == '' ? '' : $bvn;

        $ch = curl_init('https://payment.xpress-wallet.com/api/v1/wallet');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json', "Authorization: Bearer $token",
            ]
        );

        $response      = curl_exec($ch);
        $createAccount = \json_decode($response, true);

        logInfo(json_encode($createAccountResponse), "Providus Create Account Response");

        if (isset($createAccount['status'])) {
            if ($createAccount['status']) {
                return [
                    'status'  => true,
                    'message' => $createAccount['wallet']['accountName'],
                    'data'    => [
                        'account_number' => $createAccount['wallet']['accountNumber'],
                    ],
                ];
            } else {
                return ['status' => false, 'message' => $createAccount['responseMessage']];
            }
        } else {
            return ['status' => false, 'message' => "Failed to create account... Please contact support"];
        }
    }

    public function vtpassGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->providusHeaderV2())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function providusPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->providusHeader())->post("$this->baseUrl $endpoint", $body);
        return $response->json();
    }

    public function providusPutRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->providusHeader())->put("$this->baseUrl $endpoint", $body);
        return $response->json();
    }

    public function providusGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->providusHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function providusHeader()
    {
        $header = [];
        $header = [
            'Content-Type'     => 'application/json',
            'Client-Id'        => $this->clientId,
            'X-Auth-Signature' => $this->xAuthSignature,
        ];

        return $header;
    }

    public function providusHeaderV2()
    {
        $header = [];
        $header = ['appId' => $this->appId, 'appKey' => $this->appKey];

        return $header;
    }

}
