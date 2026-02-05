<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Support\Facades\Http;

class FincraController extends WebhookController
{

     private $username;
    private $apiKey;
    private $baseUrl;
    private $token;

    public function __construct()
   
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->username = config('fincra.email_test');
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

        $response = Http::withHeaders([
            'Origin' => "finova.ng",
        ])->get(
            'https://ep-green-mountain-ad9fuebm.neonauth.c-2.us-east-1.aws.neon.tech/api/auth/get-session'
        );
        return $response->json();
    }

    public function addCluster($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn)
    {
        $body = [
            'businessname'     => $businessname,
            'email'            => $email,
            'phone'            => $phone,
            'contactfirstname' => $contactfirstname,
            'contactlastname'  => $contactlastname,
            'bvn'              => $bvn,
        ];

        logInfo($body, "Add cluster request");

        try {
            $response = $this->vPayPostRequest("query/merchant/cluster/add", $body);

            if (isset($response['id'])) {
                return ['status' => true, 'message' => 'Cluster Added', 'data' => ['cluster_id' => $response['id']]];
            } else {
                return ['status' => false, 'message' => 'Failed to get Cluster id'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VPay Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
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

}
