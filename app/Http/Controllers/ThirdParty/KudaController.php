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
                "Amount"        => 50000000,
                "AccountName"   => "$firstName $lastName",
                "IsPassThrough" => true,

            ],
        ];

        $jsonBody = json_encode($body);

        /* $requestBody = [
            'data' => $jsonBody,
        ];*/

        $requestBody = $jsonBody;

        $url = "v2.1/";
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

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $response["Status"]) {
                    return [
                        'status'  => false,
                        'message' => $response["Message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Created Successfully",
                        'data'    => [
                            'account_number'         => $response['data']['AccountNumber'],
                            'virtual_account_number' => $response['data']['AccountNumber'],
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

        $url = "v2/";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequest($url, $requestBody);

            logInfo($createAccountResponse, "Kuda Create Account Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $response["Status"]) {
                    return [
                        'status'  => false,
                        'message' => $response["Message"],
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
            "requestref"  => $requestReference,
            "data"        => [
                "beneficiaryAccountNumber"    => $accountNumber,
                "beneficiaryBankCode"         => $bankCode,
                "SenderTrackingReference"     => "payout account",
                "isRequestFromVirtualAccount" => "true",
            ],
        ];
        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "v2/";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequest($url, $requestBody);

            logInfo($createAccountResponse, "Kuda Verify Account Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $response["Status"]) {
                    return [
                        'status'  => false,
                        'message' => $response["Message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name'      => $response["Data"]["BeneficiaryName"],
                            'bank_code'         => $response["Data"]["BeneficiaryBankCode"],
                            'request_reference' => $requestReference,
                            'session_id'        => $response["Data"]["SessionID"],
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

        $url = "v2.1/";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => "03", 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequest($url, $requestBody);

            logInfo($createAccountResponse, "Kuda Verify Transaction Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $response["Status"]) {
                    if ($response["ResponseCode"] == "-4" || $response["ResponseCode"] == "-2" || $response["ResponseCode"] == "-1") {
                        return [
                            'status'  => "02",
                            'message' => "Your transaction has been found and as well reversed successfully",
                        ];
                    } else if ($response["ResponseCode"] == "k25") {
                        return [
                            'status'  => "03",
                            'message' => $response["Message"],
                        ];
                    } else {
                        return [
                            'status'  => "04",
                            'message' => $response["Message"],
                        ];
                    }
                } else {
                    return [
                        'status'  => "00",
                        'message' => "Transaction Verified Successfully (Session ID: " . $response["SessionID"] . ")",
                        'data'    => $response["Data"],
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
                "servicetype" => "VIRTUAL_ACCOUNT_FUND_TRANSFER",
                "requestref"  => $requestReference,
                "data"        => [
                    "beneficiaryAccount"   => $accountNumber,
                    "beneficiarybankCode"  => $bankCode,
                    "nameEnquirySessionID" => $sessionId,
                    "amount"               => $amount * 100,
                    "ClientAccountNumber"  => "payout account",
                    "beneficiaryName"      => $accountName,
                    "trackingReference"    => "payout account",
                    "senderName"           => "WBALite-" . $description,
                    "narration"            => $description,
                ],
            ];
        } else {
            //************** New one *************************
            $body = [
                "servicetype" => "VIRTUAL_ACCOUNT_FUND_TRANSFER",
                "requestref"  => $requestReference,
                "data"        => [
                    "beneficiaryAccount"   => $accountNumber,
                    "beneficiarybankCode"  => $bankCode,
                    "nameEnquirySessionID" => $sessionId,
                    "amount"               => $amount * 100,
                    "ClientAccountNumber"  => "payout account",
                    "beneficiaryName"      => $accountName,
                    "trackingReference"    => "payout account",
                    "senderName"           => "WBALite-" . $description,
                    "narration"            => $description,
                ],
            ];
        }

        $jsonBody = \json_encode($body);

        $requestBody = [
            'data' => $jsonBody,
        ];

        $url = "v2.1/";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "Kuda Request body");

            $createAccountResponse = $this->kudaPostRequest($url, $requestBody);

            logInfo($createAccountResponse, "Kuda Bank Transfer Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($response['ResponseCode'] == "-2") {

                    $newType = $type == 1 ? 15 : 1;
                    $this->bankTransfer($requestReference, $accountNumber, $bankCode, $sessionId, $amount, $accountName, $description, $newType);
                }

                if (! $response["Status"]) {
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

    public function kudaPostRequest($endpoint, $body)
    {
        $response = Http::withHeaders($this->kudaHeader())->post("$this->baseUrl $endpoint", $body);
        return $response;
    }
    public function kudaPostRequestUpdate($endpoint, $body)
    {
        logInfo($endpoint, "Kuda Endpoint");
        logInfo($this->kudaHeader(), "Kuda Header");
        logInfo($body, "Kuda Process Endpoint Body");
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
