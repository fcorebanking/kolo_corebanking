<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NinePSBController extends WebhookController
{

    private $username;
    private $apiKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        logInfo(env('APP_MODE'), "9PSB mode");

        $this->token = '';

        if (env('APP_MODE') == 'test') {
            $this->publicKey  = env('PSB9_TEST_PUBLIC_KEY');
            $this->privateKey = env('PSB9_TEST_PRIVATE_KEY');
            $this->baseUrl    = 'https://baastest.9psb.com.ng/ipaymw-api/v1/merchant';
        } else {
            $this->publicKey  = env('PSB9_LIVE_PUBLIC_KEY');
            $this->privateKey = env('PSB9_LIVE_PRIVATE_KEY');
            $this->baseUrl    = 'https://baas.9psb.com.ng/ipaymw-api/v1/merchant'; // ✅ Add correct live endpoint
        }
    }

    public function login($action)
    {

        $body = [
            "publickey"  => $this->publicKey,
            "privatekey" => $this->privateKey,
        ];

        logInfo($body, "9PSB Request in controller body");

        $header = ['Content-Type' => 'application/json'];
        if ($action == "virtualaccont"){
             $url    = $this->baseUrl . "/virtualaccount/authenticate";
        }

        if ($action == "fundtransfer"){
             $url    = $this->baseUrl . "/authenticate";
        }
        $url    = $this->baseUrl . "/authenticate";
        try {
            $response = Http::post($url, $body);

            logInfo($response->body(), "9PSB response kuda controller ");

            if ($response->successful() && $response->json('code') == "00") {
                $this->token = $response->json('access_token');
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => 'Failed to login to 9PSB'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "9PSB Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function createAccount($requestReference, $email, $phoneNumber, $firstName, $lastName, $trackingReference)
    {

        $body = [
            "transaction" => [
                "reference" => $requestReference,
            ],

            "order"       => [
                "amount"      => 0,
                "currency"    => "NGN",
                "description" => "Create Account",
                "country"     => "NGA",
                "amounttype"  => "ANY",

            ],
            "customer"    => [
                "account" => [
                    "name" => "$firstName $lastName",
                    "type" => "STATIC",

                ],

            ],
        ];

        $jsonBody = json_encode($body);

        //  $url = "v2.1/";
        $url = $this->baseUrl . "/virtualaccount/create";

        try {
$action = "virtualaccount"
            $login = $this->login($action );

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Failed to login to 9PSB'];
            }

            \logInfo($jsonBody, "9PSB Request body");

            $createAccountResponse = $this->kudaPostRequestUpdate($url, $jsonBody);

            logInfo($createAccountResponse, "9PSB Create Account Response");

            if ($createAccountResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $createAccountResponse["code"] != "00") {
                    return [
                        'status'  => false,
                        'message' => $createAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Created Successfully",
                        'data'    => [
                            'account_number'         => $createAccountResponse['customer']['number'],
                            'virtual_account_number' => $createAccountResponse['customer']['number'],
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

    public function verifyBankAccount($bankCode, $accountNumber)
    {

        $body = [

            "customer" => [
                "account" => [
                    "number" => $accountNumber,
                    "bank"   => $bankCode,
                ],

            ],
        ];
        $jsonBody    = \json_encode($body);
        $requestBody = $jsonBody;

        $url = $this->baseUrl . "/account/enquiry";

        try {
$action = "fundtransfer"
            $login = $this->login($action);

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "9PSB Request body");

            $verifyAccountResponse = $this->ninePSBPostRequest($url, $body);

            logInfo($verifyAccountResponse, "9PSB Verify Account Response");

            if ($verifyAccountResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($verifyAccountResponse["code"] != "00") {
                    return [
                        'status'  => false,
                        'message' => $verifyAccountResponse["message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name' => $verifyAccountResponse["customer"]["account"]["name"],
                            'bank_code'    => $verifyAccountResponse["customer"]["account"]["bank"],
                            //'request_reference' => $requestReference,
                            //  'sessionID'         => $createAccountResponse["data"]["SessionID"],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'verify bank failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "9PSB Error");

            return ['status' => false, 'message' => 'Error verify bank failed. Kindly contact support for assistance'];
        }
    }

    public function verifyTransaction($transactionReference, $amount)
    {

        $body = [
            "",
        ];
        $jsonBody = \json_encode($body);

        $url = $this->baseUrl . "/account/transfer/status?reference=$transactionReference";

        try {
$action = "fundtransfer"
            $login = $this->login($action);

            if (! $login['status']) {
                return ['status' => "03", 'message' => 'Bank Unavailable. Try again later'];
            }

            $verifyTransactionResponse = $this->ninePSBPostRequest($url, $body);

            logInfo($verifyTransactionResponse, "9PSB Verify Transaction Response");

            if ($verifyTransactionResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($verifyTransactionResponse["code"] != "00") {
                    if ($verifyTransactionResponse["responseCode"] == "-4" || $createAccountResponse["responseCode"] == "-2" || $createAccountResponse["responseCode"] == "-1") {
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

    public function verifyTransactionReversal($transactionReference, $amount)
    {

        $body = [
            "transaction" => [
                "reference" => $transactionReference,
                "amount"    => $amount,
            ],
        ];
        $jsonBody = \json_encode($body);

        $url = $this->baseUrl . "/account/transfer/reversalstatus";

        try {
$action = "fundtransfer"
            $login = $this->login($action);

            if (! $login['status']) {
                return ['status' => "03", 'message' => 'Bank Unavailable. Try again later'];
            }

            $verifyTransactionResponse = $this->ninePSBPostRequest($url, $body);

            logInfo($verifyTransactionResponse, "9PSB Verify Transaction Response Reversal");

            if ($verifyTransactionResponse->successful()) {

                //$response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($verifyTransactionResponse["code"] == "46") {
                    return [
                        'status'  => "02",
                        'message' => "Your transaction has been found and as well reversed successfully",
                    ];
                }

                if ($verifyTransactionResponse["code"] == "47") {
                    return [
                        'status'  => "04",
                        'message' => $verifyTransactionResponse["message"],
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

        $privateKey         = $this->privateKey;
        $senderAccount      = "1100076684";
        $beneficiaryAccount = $accountNumber;
        $bankCode           = $bankCode;
        $amount             = number_format($amount, 2, '.', ''); // ensure two decimal places
        $reference          = $requestReference;

        $hashed = $this->hash($privateKey, $senderAccount, $beneficiaryAccount, $bankCode, $amount, $reference);

        $body = [
            "transaction" => [
                "reference" => $requestReference,
            ],
            "order"       => [
                "amount"      => $amount,
                "description" => $description,
                "currency"    => "NGN",
                "country"     => "NGA",
            ],
            "customer"    => [
                "account" => [
                    "number"              => $accountNumber,
                    "bank"                => $bankCode,
                    "name"                => $accountName,
                    "senderaccountnumber" => "1100015371",
                    "sendername"          => $description,
                ],
            ],
            "hash"        => $hashed,
        ];

        $jsonBody = \json_encode($body);

        $url = $this->baseUrl . "/account/transfers";

        try {
$action = "fundtransfer"
            $login = $this->login($action );

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Bank Unavailable. Try again later'];
            }

            \logInfo($requestBody, "9PSB Transfer Bank Request body");

            $bankTransferResponse = $this->ninePSBPostRequest($url, $body);

            logInfo($bankTransferResponse, "9PSB Bank Transfer Response");

            if ($bankTransferResponse->successful()) {

                //    $response = $this->sanitizeResponse($createAccountResponse['data']);

                if ($bankTransferResponse["code"] == "07") {
                    return [
                        'status'  => true,
                        'message' => "Invalid Account",
                    ];
                }

                if ($bankTransferResponse["code"] == "15") {
                    return [
                        'status'  => true,
                        'message' => "Invalid Session Or Record I Dt",
                    ];
                }

                if ($bankTransferResponse["code"] == "72") {
                    return [
                        'status'  => true,
                        'message' => "Customer debit failed",
                    ];
                }

                if ($bankTransferResponse["code"] == "00") {
                    return [
                        'status'  => true,
                        'message' => "Bank Transfer Processing",
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Bank Transfer failed. If you have been debited kindly contact support for reversal'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "9PSB Error");

            return ['status' => false, 'message' => 'Error with Bank Transfer. If you have been debited kindly contact support for reversal'];
        }
    }

    public function getHashed(Request $request)
    {

        try {
            $privateKey         = $request->privateKey;
            $senderAccount      = $request->input('customer.account.senderaccountnumber');
            $beneficiaryAccount = $request->input('customer.account.number');
            $bankCode           = "120001";
            $amount             = $request->amount; // ensure two decimal places
            $reference          = generateTransactionReference();

            $hashed = $this->hash($privateKey, $senderAccount, $beneficiaryAccount, $bankCode, $amount, $reference);
            return [
                'status'  => true,
                'message' => $hashed,
            ];

        } catch (Exception $e) {
            logInfo($e->getMessage(), "9PSB Error");

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

    public function ninePSBPostRequest($endpoint, $body)
    {
        logInfo(json_encode($body), "ninePSB Process Endpoint Body");
        $response = Http::withHeaders($this->ninePSBHeader())->post($endpoint, $body);
        return $response;
    }

    public function ninePSBGetRequest($endpoint)
    {

        $response = Http::withHeaders($this->ninePSBHeader())->get($endpoint);
        return $response;
    }

    public function ninePSBHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ];

        return $header;
    }

    private function hash($privateKey, $senderAccount, $beneficiaryAccount, $bankCode, $amount, $reference)
    {

// Step 1: concatenate all parts
        $rawData = $privateKey . $senderAccount . $beneficiaryAccount . $bankCode . $amount . $reference;

// Step 2: hash using SHA512
        $hash = hash('sha512', $rawData);

// Step 3: output results
        logInfo($rawData, "Raw Data:");
        logInfo(strtoupper($hash), "Hash:");

        return strtoupper($hash);
    }
}
