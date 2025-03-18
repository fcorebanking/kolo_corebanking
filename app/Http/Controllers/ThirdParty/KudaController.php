<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Support\Facades\Http;

class KudaController extends WebhookController
{

    private $username;
    private $apiKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->username = config('kuda.email_test');
            $this->apiKey   = config('kuda.api_key_test');
            $this->baseUrl  = config('kuda.base_url_test');
        } else {
            $this->username = config('kuda.email_live');
            $this->apiKey   = config('kuda.api_key_live');
            $this->baseUrl  = config('kuda.base_url_live');
        }
    }

    public function login()
    {

        $body = [
            "email"  => $this->username,
            "apiKey" => $this->apiKey,
        ];

        // logInfo($body, "Kuda Bank Code in kuda controller body");

        $header = ['Content-Type' => 'application/json'];
        try {
            $response = Http::post($this->baseUrl . "v2.1/Account/GetToken", $body);

            logInfo($response, "Kuda Bank Code in kuda controller body");

            if ($response->successful()) {
                $this->token = $response;
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => 'Failed to login to Monnify'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Kuda Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url  = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->kudaPostRequest($url, $body);

            logInfo($exhangeRate, "Exchange Rates");

            if ($exhangeRate['status'] == 'success') {
                $rate   = $exhangeRate["data"]["rate"];
                $amount = $exhangeRate["data"]["source"]["amount"];

                $finalAmount = $amount;

                return [
                    'status'  => true,
                    'message' => "Exchange rate received",
                    'data'    => [
                        'rate'         => $rate,
                        'amount'       => $amount,
                        'final_amount' => $finalAmount,
                    ],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to get Exchange rates'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }

    public function createAccount($requestReference, $email, $phoneNumber, $firstName, $lastName, $trackingReference)
    {
        /*  $body = [
            "servicetype" => "ADMIN_CREATE_DYNAMIC_COLLECTION_ACCOUNT",
            "requestref"  => $requestReference,
            "data"        => [
                "email"             => $email,
                "phoneNumber"       => $phoneNumber,
                "lastName"          => $lastName,
                "firstName"         => $firstName,
                "trackingReference" => $trackingReference,
            ],
        ];*/
        $body = [
            "serviceType" => "ADMIN_CREATE_DYNAMIC_COLLECTION_ACCOUNT",
            "requestRef"  => $requestReference,
            "data"        => [
                "Amount"        => 0,
                "AccountName"   => "$firstName $lastName",
                "IsPassThrough" => true,

            ],
        ];

        $jsonBody = json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        //  $url = "v2.1/";
        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Create Account Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Created Successfully",
                        'data'    => [
                            'account_number'         => $createAccountResponse['data']['accountNumber'],
                            'virtual_account_number' => $createAccountResponse['data']['accountNumber'],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Account Creation Failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Kuda Error");

            return ['status' => false, 'message' => 'Error Creating Account. Kindly contact support for assistance'];
        }
    }

    public function webhookWithdraw($requestReference, $amount, $narration, $trackingReference)
    {

        $body = [
            "servicetype" => "WITHDRAW_VIRTUAL_ACCOUNT",
            "requestref"  => $requestReference,
            "data"        => [
                "amount"            => $amount,
                "narration"         => $narration,
                "trackingReference" => $trackingReference,
            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Create Account Response");

            if ($createAccountResponse->successful()) {

                //  $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Webhook Withdrawal Successful",
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Webhook withdrawal failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => false, 'message' => 'Error completing withdrawal. Kindly contact support for assistance'];
        }
    }

    public function verifyBankAccount($bankCode, $accountNumber)
    {

        $requestReference = \generate_txnID();

        $body = [
            "servicetype" => "NAME_ENQUIRY",
            "requestRef"  => $requestReference,
            "Data"        => [
                "BeneficiaryAccountNumber" => $accountNumber,
                "BeneficiaryBankCode"      => $bankCode,

            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Verify Account Response");

            if ($createAccountResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name'      => $createAccountResponse["data"]["beneficiaryName"],
                            'bank_code'         => $createAccountResponse["data"]["beneficiaryBankCode"],
                            'request_reference' => $requestReference,
                            //  'sessionID'         => $createAccountResponse["data"]["SessionID"],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Webhook withdrawal failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => false, 'message' => 'Error completing withdrawal. Kindly contact support for assistance'];
        }
    }

    public function verifyTransaction($transactionReference)
    {

        $requestReference = \generate_txnID();

        $body = [
            "servicetype" => "TRANSACTION_STATUS_QUERY",
            "requestref"  => $requestReference,
            "data"        => [
                "isThirdPartyBankTransfer"    => false,
                "transactionRequestReference" => $transactionReference,
            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => "03", 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Verify Transaction Response");

            if ($createAccountResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["status"]) {
                    if ($createAccountResponse["responseCode"] == "-4" || $createAccountResponse["responseCode"] == "-2" || $createAccountResponse["responseCode"] == "-1") {
                        return [
                            'status'  => "02",
                            'message' => "Your transaction has been found and as well reversed successfully",
                        ];
                    } else if ($createAccountResponse["responseCode"] == "k25") {
                        return [
                            'status'  => "03",
                            'message' => $createAccountResponse["message"],
                        ];
                    } else {
                        return [
                            'status'  => "04",
                            'message' => $createAccountResponse["message"],
                        ];
                    }
                } else {
                    return [
                        'status'  => "00",
                        'message' => "Transaction Verified Successfully (Session ID: " . $createAccountResponse["sessionID"] . ")",
                        'data'    => $createAccountResponse["data"],
                    ];
                }
            } else {
                return ['status' => "05", 'message' => 'Verify transaction failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => "06", 'message' => 'Error verifying transaction. Kindly contact support for assistance'];
        }
    }

    public function bankTransfer($requestReference, $accountNumber, $bankCode, $sessionId, $amount, $accountName, $description, $type)
    {

        //************** Old one *************************
        // $body = [
        //     "servicetype" => "VIRTUAL_ACCOUNT_FUND_TRANSFER",
        //     "requestref" => $requestReference,
        //     "data" => [
        //         "beneficiaryAccount" => $accountNumber,
        //         "beneficiarybankCode" => $bankCode,
        //         "nameEnquirySessionID" => $sessionId,
        //         "amount" => $amount * 100,
        //         "ClientAccountNumber" => "payout account",
        //         "beneficiaryName" => $accountName,
        //         "trackingReference" => "payout account",
        //         "senderName" => "Wired Banking Lite",
        //         "narration" => $description
        //     ]
        // ];

        //************** tracking Ref changed one *************************

        if ($type == 1) {
            $body = [
                "servicetype" => "SINGLE_FUND_TRANSFER",
                "requestRef"  => $requestReference,
                "Data"        => [
                    "BeneficiaryAccount"  => $accountNumber,
                    "beneficiaryBankCode" => $bankCode,
                    "amount"              => $amount * 100,
                    "ClientAccountNumber" => "3002284739",
                    "beneficiaryName"     => $accountName,
                    "SenderName"          => $description,
                    "narration"           => $description,
                ],
            ];
        } else {
            //************** New one *************************
            $body = [
                "servicetype" => "SINGLE_FUND_TRANSFER",
                "requestRef"  => $requestReference,
                "Data"        => [
                    "BeneficiaryAccount"  => $accountNumber,
                    "beneficiaryBankCode" => $bankCode,
                    "amount"              => $amount * 100,
                    "ClientAccountNumber" => "3002284739",
                    "beneficiaryName"     => $accountName,
                    "SenderName"          => $description,
                    "narration"           => $description,
                ],
            ];
        }

        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "https://kuda-openapi.kuda.com/v2.1";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $body);

            logInfo($createAccountResponse, "Kuda Bank Transfer Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($createAccountResponse['responseCode'] == "-2") {

                    $newType = $type == 1 ? 15 : 1;
                    $this->bankTransfer($requestReference, $accountNumber, $bankCode, $sessionId, $amount, $accountName, $description, $newType);
                }

                if (! $createAccountResponse["status"]) {
                    return [
                        'status'  => true,
                        'message' => "Bank Transfer Processing",
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Bank Transfer Successful",
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Bank Transfer failed. If you have been debited kindly contact support for reversal'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Kuda Error");

            return ['status' => false, 'message' => 'Error with Bank Transfer. If you have been debited kindly contact support for reversal'];
        }
    }

    public function buyAirtime($transactionId, $network, $amount, $phoneNumber, $account)
    {
        $requestReference = \generate_txnID();
        $type             = "airtime";
        $biller_id        = $this->getBiller($type, $network);
        if (isset($biller_id['status'])) {
            return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
        }

        $login = $this->login();
        try {

            $body = [
                "servicetype" => "ADMIN_PURCHASE_BILL",
                "requestRef"  => $requestReference,
                "Data"        => [
                    "CustomerFirstName"  => $account,
                    "CustomerIdentifier" => $bankCode,
                    "PhoneNumber"        => $phoneNumber,
                    "BillItemIdentifier" => $biller_id,
                    "Amount"             => $amount,

                ]];
            $url = "https://kuda-openapi.kuda.com/v2.1";

            $buyAirtime = $this->kudaPostRequestUpdate($url, $body);

            logInfo($buyAirtime, 'Kuda Airtime Response log');

            if (isset($buyAirtime['status'])) {
                if ($buyAirtime["status"] == "true") {
                    return ["status" => true, 'message' => "Airtime Purhcase Successful"];
                } else {
                    return ["status" => false, 'message' => $buyAirtime['response_description'] . ". Kindly contact admin for support", 'data' => $buyAirtime];
                }
            } else {
                return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "VTPass log error");
            return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
        }
    }

    public function getBiller($type, $network = null)
    {
        $requestReference = \generate_txnID();
        $login            = $this->login();

        $body = [
            "servicetype" => "GET_BILLERS_BY_TYPE",
            "requestRef"  => $requestReference,
            "Data"        => [
                "BillTypeName" => $type,

            ]];
        $url = "https://kuda-openapi.kuda.com/v2.1";

        $billers = $this->kudaPostRequestUpdate($url, $body);

        if (isset($billers['status']) == "true") {
            if (isset($billers['data']['billers'][0]['description']) && $billers['data']['billers'][0]['description'] == "Airtime") {
                return $this->getBillAirtimeID($billers, $network);
            }
            return ['status' => true, 'message' => "{$type} fetched successfully", 'data' => $billers['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => $billers];
        }
    }

    public function getBillAirtimeID($data, $network)
    {
        $airtime_id_name = "";
        $airtime_id      = "";
        if ($network == "mtn") {
            $airtime_id_name = "MTN VTU";

        }
        if ($network == "glo") {
            $airtime_id_name = "GLO VTU";

        }
        if ($network == "airtel") {
            $airtime_id_name = "AIRTEL VTU";

        }
        if ($network == "9mobile") {
            $airtime_id_name = "9MOBILE VTU";

        }

        foreach ($data['data']['billers'] as $airtime) {
            if ($airtime['billItems'][0]['name'] == $airtime_id_name) {
                $airtime_id = $airtime['billItems'][0]['kudaIdentifier'];
            }
        }
        logInfo($airtime_id, "$airtime_id_name");
        return $airtime_id;

    }

    public function kudaPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->kudaHeader())->post("$this->baseUrl $endpoint", $body);
        return $response;
    }
    public function kudaPostRequestUpdate($endpoint, $body)
    {
        logInfo(json_encode($body), "Kuda Process Endpoint Body");
        $response = Http::withHeaders($this->kudaHeader())->post($endpoint, $body);
        return $response;
    }

    public function monnifyGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->kudaHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function sanitizeResponse($stringValue)
    {
        return \json_decode(str_replace('\\', '', $stringValue), true);
    }

    public function kudaHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ];

        return $header;
    }
}
