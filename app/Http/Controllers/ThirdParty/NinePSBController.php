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
    private $publicKey;
    private $privateKey;

    public function __construct()
    {
        logInfo(
            env('APP_MODE'),
            "9PSB mode"
        );

        $this->token = '';

        if (env('APP_MODE') == 'test') {

            $this->publicKey =
                env('PSB9_TEST_PUBLIC_KEY');

            $this->privateKey =
                env('PSB9_TEST_PRIVATE_KEY');

            $this->baseUrl =
                'https://baastest.9psb.com.ng';

        } else {

            $this->publicKey =
                env('PSB9_LIVE_PUBLIC_KEY');

            $this->privateKey =
                env('PSB9_LIVE_PRIVATE_KEY');

            $this->baseUrl =
                'https://baas.9psb.com.ng';
        }

        if (empty($this->publicKey) || empty($this->privateKey)) {

            logInfo([
                'mode' => env('APP_MODE'),
                'public_key_configured' =>
                    ! empty($this->publicKey),
                'private_key_configured' =>
                    ! empty($this->privateKey),
                'base_url' =>
                    $this->baseUrl,
            ], "9PSB Credentials Configuration Warning");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN / AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    public function login($action)
    {
        $body = [
            "publickey"  => $this->publicKey,
            "privatekey" => $this->privateKey,
        ];

        /*
        |--------------------------------------------------------------------------
        | DO NOT LOG THE PRIVATE KEY
        |--------------------------------------------------------------------------
        */

        logInfo([
            'action'              => $action,
            'publickey_configured' =>
                ! empty($this->publicKey),
            'privatekey_configured' =>
                ! empty($this->privateKey),
            'url' =>
                $action === "virtualaccount"
                    ? $this->baseUrl . "/ipaymw-api/v1/virtualaccount/authenticate"
                    : $this->baseUrl . "/ipaymw-api/v1/merchant/authenticate",
        ], "9PSB Login Request");

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        if ($action === "virtualaccount") {

    $url = $this->baseUrl .
        "/iva-api/v1/merchant/virtualaccount/authenticate";

} elseif ($action === "fundtransfer") {

    // Preserve your existing fund-transfer authentication route.
    $url = $this->baseUrl .
        "/ipaymw-api/v1/merchant/authenticate";

} else {

    return [
        'status'  => false,
        'message' => 'Invalid 9PSB authentication action.'
    ];
}

        try {

            $response =
                Http::withHeaders($headers)
                    ->timeout(60)
                    ->post($url, $body);

            $responseData = $response->json() ?? [];

        logInfo([
          'http_status' => $response->status(),
          'code' => $responseData['code'] ?? null,
          'message' => $responseData['message'] ?? null,
          'token_received' => !empty($responseData['access_token']),
          'expires_in' => $responseData['expires_in'] ?? null,
                ], "9PSB Login Response");

            if (
                $response->successful() &&
                isset($responseData['code']) &&
                $responseData['code'] === "00" &&
                ! empty($responseData['access_token'])
            ) {

                $this->token =
                    $responseData['access_token'];

                return [
                    'status'  => true,
                    'message' => 'Login success',
                    'data'    => [
                        'token' => $this->token,
                    ],
                ];
            }

            return [
                'status'  => false,
                'message' =>
                    $responseData['message']
                    ?? 'Failed to login to 9PSB',
                'data'    => $responseData,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Login Error"
            );

            return [
                'status'  => false,
                'message' =>
                    'Server Error while authenticating with 9PSB.'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE VIRTUAL ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function createAccount(
        $requestReference,
        $email,
        $phoneNumber,
        $firstName,
        $lastName,
        $trackingReference
    ) {
        /*
        |--------------------------------------------------------------------------
        | Build customer name
        |--------------------------------------------------------------------------
        */

        $accountName =
            trim(
                $firstName . ' ' . $lastName
            );

        if (empty($accountName)) {
            return [
                'status'  => false,
                'message' => 'Customer account name is required.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 9PSB Create Virtual Account Request
        |--------------------------------------------------------------------------
        */

        $body = [
            "transaction" => [
                "reference" =>
                    $requestReference,
            ],

            "order" => [
                "amount"      => 0,
                "currency"    => "NGN",
                "description" => "Create Account",
                "country"     => "NGA",
                "amounttype"  => "ANY",
            ],

            "customer" => [
                "account" => [
                    "name" =>
                        $accountName,
                    "type" =>
                        "STATIC",
                ],
            ],
        ];

        $url =
            $this->baseUrl .
            "/iva-api/v1/merchant/virtualaccount/create";

        try {

            $login =
                $this->login(
                    "virtualaccount"
                );

            if (! $login['status']) {

                return [
                    'status'  => false,
                    'message' =>
                        $login['message']
                        ?? 'Failed to login to 9PSB',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Log safe request information.
            |--------------------------------------------------------------------------
            */

            logInfo([
                'url'              => $url,
                'request_reference' =>
                    $requestReference,
                'tracking_reference' =>
                    $trackingReference,
                'email'            =>
                    $email,
                'phone'            =>
                    $phoneNumber,
                'first_name'       =>
                    $firstName,
                'last_name'        =>
                    $lastName,
                'body'             =>
                    $body,
            ], "9PSB Create Account Request");

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            |
            | Use the actual 9PSB HTTP helper.
            | Do NOT use kudaPostRequestUpdate().
            |--------------------------------------------------------------------------
            */

            $createAccountResponse =
                $this->ninePSBPostRequest(
                    $url,
                    $body
                );

            logInfo([
                'http_status' =>
                    $createAccountResponse->status(),
                'body' =>
                    $createAccountResponse->json(),
            ], "9PSB Create Account Response");

            $responseData =
                $createAccountResponse->json();

            if (! $createAccountResponse->successful()) {

                return [
                    'status'  => false,
                    'message' =>
                        $responseData['message']
                        ?? 'Account Creation Failed. Kindly contact support for assistance',
                    'data'    =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 9PSB API-Level Failure
            |--------------------------------------------------------------------------
            */

            if (
                isset($responseData['code']) &&
                $responseData['code'] != "00"
            ) {
                return [
                    'status'  => false,
                    'message' =>
                        $responseData['message']
                        ?? '9PSB failed to create the account.',
                    'data'    =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Extract Virtual Account Number
            |--------------------------------------------------------------------------
            */

            $accountNumber =
                $responseData['customer']['account']['number']
                ?? null;

            if (empty($accountNumber)) {

                return [
                    'status'  => false,
                    'message' =>
                        '9PSB returned a successful response but no virtual account number was found.',
                    'data' =>
                        $responseData,
                ];
            }

            return [
                'status'  => true,
                'message' =>
                    "Account Created Successfully",
                'data'    => [
                    'account_number' =>
                        $accountNumber,

                    'virtual_account_number' =>
                        $accountNumber,

                    'request_reference' =>
                        $requestReference,

                    'tracking_reference' =>
                        $trackingReference,
                ],
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Create Account Error"
            );

            return [
                'status'  => false,
                'message' =>
                    'Error Creating Account. Kindly contact support for assistance'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY BANK ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function verifyBankAccount(
        $bankCode,
        $accountNumber
    ) {
        $body = [
            "customer" => [
                "account" => [
                    "number" =>
                        $accountNumber,
                    "bank" =>
                        $bankCode,
                ],
            ],
        ];

        $url =
            $this->baseUrl .
            "/ipaymw-api/v1/merchant/account/enquiry";

        try {

            $login =
                $this->login(
                    "fundtransfer"
                );

            if (! $login['status']) {

                return [
                    'status'  => false,
                    'message' =>
                        'Bank Unavailable. Try again later'
                ];
            }

            logInfo([
                'url'           => $url,
                'bank_code'     => $bankCode,
                'account_number' =>
                    $accountNumber,
                'body'          => $body,
            ], "9PSB Verify Account Request");

            $verifyAccountResponse =
                $this->ninePSBPostRequest(
                    $url,
                    $body
                );

            logInfo([
                'http_status' =>
                    $verifyAccountResponse->status(),
                'body' =>
                    $verifyAccountResponse->json(),
            ], "9PSB Verify Account Response");

            $responseData =
                $verifyAccountResponse->json();

            if (! $verifyAccountResponse->successful()) {

                return [
                    'status'  => false,
                    'message' =>
                        $responseData['message']
                        ?? 'Verify bank failed. Kindly contact support for assistance',
                    'data' =>
                        $responseData,
                ];
            }

            if (
                isset($responseData['code']) &&
                $responseData['code'] != "00"
            ) {

                return [
                    'status'  => false,
                    'message' =>
                        $responseData['message']
                        ?? 'Bank account verification failed.',
                    'data' =>
                        $responseData,
                ];
            }

            $accountName =
                $responseData['customer']['account']['name']
                ?? null;

            $returnedBankCode =
                $responseData['customer']['account']['bank']
                ?? $bankCode;

            if (empty($accountName)) {

                return [
                    'status'  => false,
                    'message' =>
                        '9PSB did not return an account name.',
                    'data' =>
                        $responseData,
                ];
            }

            return [
                'status'  => true,
                'message' =>
                    'Account Verified Successfully',
                'data'    => [
                    'account_name' =>
                        $accountName,

                    'bank_code' =>
                        $returnedBankCode,
                ],
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Verify Bank Account Error"
            );

            return [
                'status'  => false,
                'message' =>
                    'Error verifying bank account. Kindly contact support for assistance'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function verifyTransaction(
        $transactionReference,
        $amount
    ) {
        $url =
            $this->baseUrl .
            "/ipaymw-api/v1/merchant/account/transfer/status?reference=" .
            urlencode($transactionReference);

        /*
        |--------------------------------------------------------------------------
        | The old code had:
        |
        | $body = [""];
        |
        | There is no reason to send an empty string as the JSON body.
        | Send an empty object instead.
        |--------------------------------------------------------------------------
        */

        $body = [];

        try {

            $login =
                $this->login(
                    "fundtransfer"
                );

            if (! $login['status']) {

                return [
                    'status'  => "03",
                    'message' =>
                        'Bank Unavailable. Try again later'
                ];
            }

            $verifyTransactionResponse =
                $this->ninePSBPostRequest(
                    $url,
                    $body
                );

            logInfo([
                'reference' =>
                    $transactionReference,
                'amount' =>
                    $amount,
                'http_status' =>
                    $verifyTransactionResponse->status(),
                'body' =>
                    $verifyTransactionResponse->json(),
            ], "9PSB Verify Transaction Response");

            $responseData =
                $verifyTransactionResponse->json();

            if (! $verifyTransactionResponse->successful()) {

                return [
                    'status'  => "05",
                    'message' =>
                        $responseData['message']
                        ?? 'Verify transaction failed. Kindly contact support for assistance',
                    'data' =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            |
            | The old implementation incorrectly referenced
            | $createAccountResponse here.
            |
            | Everything now uses $responseData.
            |--------------------------------------------------------------------------
            */

            $responseCode =
                $responseData['responseCode']
                ?? $responseData['code']
                ?? null;

            $message =
                $responseData['message']
                ?? 'Transaction verification response received.';

            if (
                $responseCode !== null &&
                in_array(
                    (string) $responseCode,
                    ['-4', '-2', '-1'],
                    true
                )
            ) {
                return [
                    'status'  => "02",
                    'message' =>
                        "Your transaction has been found and as well reversed successfully",
                    'data' =>
                        $responseData,
                ];
            }

            if (
                strtolower(
                    (string) $responseCode
                ) === 'k25'
            ) {
                return [
                    'status'  => "03",
                    'message' =>
                        $message,
                    'data' =>
                        $responseData,
                ];
            }

            if (
                isset($responseData['code']) &&
                $responseData['code'] != "00"
            ) {
                return [
                    'status'  => "04",
                    'message' =>
                        $message,
                    'data' =>
                        $responseData,
                ];
            }

            return [
                'status'  => "00",
                'message' =>
                    "Transaction Verified Successfully",
                'data' =>
                    $responseData,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Verify Transaction Error"
            );

            return [
                'status'  => "06",
                'message' =>
                    'Error verifying transaction. Kindly contact support for assistance'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY TRANSACTION REVERSAL
    |--------------------------------------------------------------------------
    */

    public function verifyTransactionReversal(
        $transactionReference,
        $amount
    ) {
        $body = [
            "transaction" => [
                "reference" =>
                    $transactionReference,
                "amount" =>
                    $amount,
            ],
        ];

        $url =
            $this->baseUrl .
            "/ipaymw-api/v1/merchant/account/transfer/reversalstatus";

        try {

            $login =
                $this->login(
                    "fundtransfer"
                );

            if (! $login['status']) {

                return [
                    'status'  => "03",
                    'message' =>
                        'Bank Unavailable. Try again later'
                ];
            }

            $verifyTransactionResponse =
                $this->ninePSBPostRequest(
                    $url,
                    $body
                );

            logInfo([
                'reference' =>
                    $transactionReference,
                'amount' =>
                    $amount,
                'http_status' =>
                    $verifyTransactionResponse->status(),
                'body' =>
                    $verifyTransactionResponse->json(),
            ], "9PSB Verify Transaction Response Reversal");

            $responseData =
                $verifyTransactionResponse->json();

            if (! $verifyTransactionResponse->successful()) {

                return [
                    'status'  => "05",
                    'message' =>
                        $responseData['message']
                        ?? 'Verify transaction reversal failed. Kindly contact support for assistance',
                    'data' =>
                        $responseData,
                ];
            }

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "46"
            ) {
                return [
                    'status'  => "02",
                    'message' =>
                        "Your transaction has been found and as well reversed successfully",
                    'data' =>
                        $responseData,
                ];
            }

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "47"
            ) {
                return [
                    'status'  => "04",
                    'message' =>
                        $responseData['message']
                        ?? 'Transaction reversal failed.',
                    'data' =>
                        $responseData,
                ];
            }

            return [
                'status'  => "04",
                'message' =>
                    $responseData['message']
                    ?? 'Transaction reversal status could not be determined.',
                'data' =>
                    $responseData,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Verify Transaction Reversal Error"
            );

            return [
                'status'  => "06",
                'message' =>
                    'Error verifying transaction reversal. Kindly contact support for assistance'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BANK TRANSFER
    |--------------------------------------------------------------------------
    */

    public function bankTransfer(
        $requestReference,
        $accountNumber,
        $bankCode,
        $sessionId,
        $amount,
        $accountName,
        $description,
        $type
    ) {
        $privateKey =
            $this->privateKey;

        /*
        |--------------------------------------------------------------------------
        | These are the sender accounts currently hard-coded in the
        | original implementation.
        |
        | They have NOT been changed because we don't have your 9PSB
        | configured sender-account mapping yet.
        |--------------------------------------------------------------------------
        */

        $senderAccount =
            "1100076684";

        $beneficiaryAccount =
            $accountNumber;

        $formattedAmount =
            number_format(
                $amount,
                2,
                '.',
                ''
            );

        $reference =
            $requestReference;

        $hashed =
            $this->hash(
                $privateKey,
                $senderAccount,
                $beneficiaryAccount,
                $bankCode,
                $formattedAmount,
                $reference
            );

        $body = [
            "transaction" => [
                "reference" =>
                    $requestReference,
            ],

            "order" => [
                "amount" =>
                    $formattedAmount,

                "description" =>
                    $description,

                "currency" =>
                    "NGN",

                "country" =>
                    "NGA",
            ],

            "customer" => [
                "account" => [
                    "number" =>
                        $accountNumber,

                    "bank" =>
                        $bankCode,

                    "name" =>
                        $accountName,

                    "senderaccountnumber" =>
                        $senderAccount,

                    "sendername" =>
                        $description,
                ],
            ],

            "hash" =>
                $hashed,
        ];

        $url =
            $this->baseUrl .
            "/ipaymw-api/v1/merchant/account/transfer";

        try {

            $login =
                $this->login(
                    "fundtransfer"
                );

            if (! $login['status']) {

                return [
                    'status'  => false,
                    'message' =>
                        'Bank Unavailable. Try again later'
                ];
            }

            logInfo([
                'url' =>
                    $url,

                'reference' =>
                    $requestReference,

                'beneficiary_account' =>
                    $beneficiaryAccount,

                'bank_code' =>
                    $bankCode,

                'amount' =>
                    $formattedAmount,

                'body' =>
                    $body,
            ], "9PSB Transfer Bank Request");

            $bankTransferResponse =
                $this->ninePSBPostRequest(
                    $url,
                    $body
                );

            logInfo([
                'http_status' =>
                    $bankTransferResponse->status(),
                'body' =>
                    $bankTransferResponse->json(),
            ], "9PSB Bank Transfer Response");

            $responseData =
                $bankTransferResponse->json();

            if (! $bankTransferResponse->successful()) {

                return [
                    'status'  => false,
                    'message' =>
                        $responseData['message']
                        ?? 'Bank Transfer failed. If you have been debited kindly contact support for reversal',
                    'data' =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Invalid Account
            |--------------------------------------------------------------------------
            */

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "07"
            ) {
                return [
                    'status'  => false,
                    'message' =>
                        "Invalid Account",
                    'data' =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Invalid Session / Record
            |--------------------------------------------------------------------------
            */

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "15"
            ) {
                return [
                    'status'  => false,
                    'message' =>
                        "Invalid Session Or Record ID",
                    'data' =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Customer Debit Failed
            |--------------------------------------------------------------------------
            */

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "72"
            ) {
                return [
                    'status'  => false,
                    'message' =>
                        "Customer debit failed",
                    'data' =>
                        $responseData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Successful / Processing
            |--------------------------------------------------------------------------
            */

            if (
                isset($responseData['code']) &&
                $responseData['code'] == "00"
            ) {
                return [
                    'status'  => true,
                    'message' =>
                        "Bank Transfer Processing",
                    'data' =>
                        $responseData,
                ];
            }

            return [
                'status'  => false,
                'message' =>
                    $responseData['message']
                    ?? 'Bank Transfer failed. If you have been debited kindly contact support for reversal',
                'data' =>
                    $responseData,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Bank Transfer Error"
            );

            return [
                'status'  => false,
                'message' =>
                    'Error with Bank Transfer. If you have been debited kindly contact support for reversal'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HASH GENERATOR
    |--------------------------------------------------------------------------
    */

    public function getHashed(Request $request)
    {
        try {

            $privateKey =
                $request->privatekey;

            $senderAccount =
                $request->input(
                    'customer.account.senderaccountnumber'
                );

            $beneficiaryAccount =
                $request->input(
                    'customer.account.number'
                );

            $bankCode =
                $request->input(
                    'customer.account.bank',
                    '120001'
                );

            $amount =
                $request->input(
                    'order.amount'
                );

            $reference =
                $request->input(
                    'transaction.reference'
                );

            /*
            |--------------------------------------------------------------------------
            | NEVER LOG THE PRIVATE KEY.
            |--------------------------------------------------------------------------
            */

            logInfo([
                'senderAccount' =>
                    $senderAccount,

                'beneficiaryAccount' =>
                    $beneficiaryAccount,

                'bankCode' =>
                    $bankCode,

                'amount' =>
                    $amount,

                'reference' =>
                    $reference,
            ], "9PSB Hash Request");

            if (
                empty($privateKey) ||
                empty($senderAccount) ||
                empty($beneficiaryAccount) ||
                empty($amount) ||
                empty($reference)
            ) {
                return [
                    'status'  => false,
                    'message' =>
                        'Missing required hash parameters.'
                ];
            }

            $hashed =
                $this->hash(
                    $privateKey,
                    $senderAccount,
                    $beneficiaryAccount,
                    $bankCode,
                    $amount,
                    $reference
                );

            return [
                'status'  => true,
                'message' =>
                    $hashed,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "9PSB Hash Error"
            );

            return [
                'status'  => false,
                'message' =>
                    'Error generating transaction hash.'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUY AIRTIME
    |--------------------------------------------------------------------------
    |
    | Legacy implementation retained exactly as a compatibility method.
    | It is NOT part of the new 9PSB virtual-account flow.
    |
    */

    public function buyAirtime(
        $transactionId,
        $network,
        $amount,
        $phoneNumber,
        $account
    ) {
        $requestReference =
            \generate_txnID();

        $type =
            "airtime";

        $biller_id =
            $this->getBiller(
                $type,
                $network
            );

        if (
            isset($biller_id['status']) &&
            $biller_id['status'] === false
        ) {
            return [
                "status"  => false,
                'message' =>
                    'Failed to purchase airtime, Please contact support'
            ];
        }

        try {

            $body = [
                "servicetype" =>
                    "ADMIN_PURCHASE_BILL",

                "requestRef" =>
                    $requestReference,

                "Data" => [
                    "CustomerFirstName" =>
                        $account,

                    "CustomerIdentifier" =>
                        $phoneNumber,

                    "PhoneNumber" =>
                        $phoneNumber,

                    "BillItemIdentifier" =>
                        $biller_id,

                    "Amount" =>
                        $amount,
                ]
            ];

            $url =
                "https://kuda-openapi.kuda.com/v2.1";

            $buyAirtime =
                $this->kudaPostRequestUpdate(
                    $url,
                    $body
                );

            logInfo(
                $buyAirtime,
                'Kuda Airtime Response log'
            );

            if (isset($buyAirtime['status'])) {

                if (
                    $buyAirtime["status"] == "true"
                ) {
                    return [
                        "status"  => true,
                        'message' =>
                            "Airtime Purchase Successful"
                    ];
                }

                return [
                    "status"  => false,
                    'message' =>
                        ($buyAirtime['response_description'] ?? 'Airtime purchase failed')
                        . ". Kindly contact admin for support",
                    'data' =>
                        $buyAirtime
                ];
            }

            return [
                "status"  => false,
                'message' =>
                    'Failed to purchase airtime, Please contact support'
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Airtime Purchase Error"
            );

            return [
                "status"  => false,
                'message' =>
                    'Failed to purchase airtime, Please contact support'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET BILLER
    |--------------------------------------------------------------------------
    |
    | Legacy Kuda implementation retained.
    |
    */

    public function getBiller(
        $type,
        $network = null
    ) {
        $requestReference =
            \generate_txnID();

        $body = [
            "servicetype" =>
                "GET_BILLERS_BY_TYPE",

            "requestRef" =>
                $requestReference,

            "Data" => [
                "BillTypeName" =>
                    $type,
            ]
        ];

        $url =
            "https://kuda-openapi.kuda.com/v2.1";

        $billers =
            $this->kudaPostRequestUpdate(
                $url,
                $body
            );

        if (
            isset($billers['status']) &&
            $billers['status'] == "true"
        ) {

            if (
                isset($billers['data']['billers'][0]['description']) &&
                $billers['data']['billers'][0]['description'] == "Airtime"
            ) {
                return $this->getBillAirtimeID(
                    $billers,
                    $network
                );
            }

            return [
                'status'  => true,
                'message' =>
                    "{$type} fetched successfully",
                'data' =>
                    $billers['data']
            ];
        }

        return [
            'status'  => false,
            'message' =>
                'Failed to fetch Data bundles',
            'data' =>
                $billers
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET AIRTIME BILL ID
    |--------------------------------------------------------------------------
    */

    public function getBillAirtimeID(
        $data,
        $network
    ) {
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

        if (
            ! isset($data['data']['billers']) ||
            ! is_array($data['data']['billers'])
        ) {
            return "";
        }

        foreach (
            $data['data']['billers']
            as $airtime
        ) {

            if (
                isset($airtime['billItems'][0]['name']) &&
                $airtime['billItems'][0]['name'] ==
                    $airtime_id_name
            ) {

                $airtime_id =
                    $airtime['billItems'][0]['kudaIdentifier'];

                break;
            }
        }

        logInfo(
            $airtime_id,
            "$airtime_id_name"
        );

        return $airtime_id;
    }

    /*
    |--------------------------------------------------------------------------
    | 9PSB POST REQUEST
    |--------------------------------------------------------------------------
    */

    public function ninePSBPostRequest(
        $endpoint,
        $body
    ) {
        logInfo([
            'endpoint' =>
                $endpoint,
            'body' =>
                $body,
        ], "9PSB Process Endpoint Body");

        return Http::withHeaders(
            $this->ninePSBHeader()
        )
            ->timeout(60)
            ->post(
                $endpoint,
                $body
            );
    }

    /*
    |--------------------------------------------------------------------------
    | 9PSB GET REQUEST
    |--------------------------------------------------------------------------
    */

    public function ninePSBGetRequest(
        $endpoint
    ) {
        return Http::withHeaders(
            $this->ninePSBHeader()
        )
            ->timeout(60)
            ->get(
                $endpoint
            );
    }

    /*
    |--------------------------------------------------------------------------
    | 9PSB HEADERS
    |--------------------------------------------------------------------------
    */

    public function ninePSBHeader()
    {
        return [
            'Content-Type' =>
                'application/json',

            'Accept' =>
                'application/json',

            'Authorization' =>
                'Bearer ' . $this->token,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HASH
    |--------------------------------------------------------------------------
    */

    private function hash(
        $privateKey,
        $senderAccount,
        $beneficiaryAccount,
        $bankCode,
        $amount,
        $reference
    ) {
        /*
        |--------------------------------------------------------------------------
        | 9PSB hash format from the existing implementation:
        |
        | privateKey + senderAccount + beneficiaryAccount +
        | bankCode + amount + reference
        |--------------------------------------------------------------------------
        */

        $rawData =
            $privateKey .
            $senderAccount .
            $beneficiaryAccount .
            $bankCode .
            $amount .
            $reference;

        $hash =
            hash(
                'sha512',
                $rawData
            );

        /*
        |--------------------------------------------------------------------------
        | DO NOT LOG rawData because it contains the private key.
        |--------------------------------------------------------------------------
        */

        logInfo(
            strtoupper($hash),
            "9PSB Hash Generated"
        );

        return strtoupper($hash);
    }
}