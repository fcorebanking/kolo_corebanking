<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use Exception;
use Illuminate\Support\Facades\Http;

class KudaController extends WebhookController
{
    private $username;
    private $apiKey;
    private $apiMaintenanceKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = '';

        if (env('APP_MODE') === 'test') {
            $this->username = config('kuda.email_test');
            $this->apiKey = config('kuda.api_key_test');
            $this->baseUrl = rtrim(config('kuda.base_url_test'), '/');

            $this->apiMaintenanceKey = config('kuda.api_maintenace_key_live');
        } else {
            $apiUser = auth('api')->user();
            $webUser = auth()->user();

            $companyId = $apiUser?->company_id ?? $webUser?->company_id;

            $isSpecial = $companyId == 14;

            $this->username = $isSpecial
                ? config('kuda.email_live_linkforte')
                : config('kuda.email_live');

            $this->apiKey = $isSpecial
                ? config('kuda.api_key_live_linkforte')
                : config('kuda.api_key_live');

            $this->baseUrl = rtrim(config('kuda.base_url_live'), '/');

            $this->apiMaintenanceKey = config('kuda.api_maintenace_key_live');
        }
    }

    /**
     * Get a Kuda access token using the normal API credentials.
     *
     * Kuda returns the access token as raw text/JWT.
     */
public function login()
{
     $body = [
        'email'  => $this->username,
        'apiKey' => $this->apiKey,
    ];

    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])
            ->timeout(30)
            ->post(
                $this->baseUrl . '/Account/GetToken',
                $body
            );

        if (! $response->successful()) {
            logInfo([
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ], 'Kuda Login Failed');

            return [
                'status'  => false,
                'message' => 'Failed to login to Kuda',
            ];
        }

        $token = $this->extractKudaAccessToken($response);

        if (! $token) {
            logInfo([
                'http_status' => $response->status(),
                'body_length' => strlen($response->body()),
            ], 'Kuda Login Invalid Token');

            return [
                'status'  => false,
                'message' => 'Kuda returned an invalid access token',
            ];
        }

        $this->token = $token;

        logInfo([
            'token_received' => true,
            'token_length'   => strlen($token),
        ], 'Kuda Login Successful');

        return [
            'status'  => true,
            'message' => 'Login success',
        ];

    } catch (Exception $e) {
        logInfo([
            'error' => $e->getMessage(),
        ], 'Kuda Login Error');

        return [
            'status'  => false,
            'message' => 'Server Error connecting to Kuda',
        ];
    }
}

    /**
     * Get a Kuda access token using the maintenance API credentials.
     *
     * IMPORTANT:
     * Do not log the API key or returned JWT.
     */
public function loginForMaintenance()
{
    $maintenanceEmail = config(
        'kuda.email_maintenance', 
    );

    if (empty($maintenanceEmail)) {
        return [
            'status'  => false,
            'message' => 'Kuda maintenance email is not configured',
        ];
    }

    // if (empty($this->apiMaintenanceKey)) {
    //     return [
    //         'status'  => false,
    //         'message' => 'Kuda maintenance API key is not configured',
    //     ];
    // }

    $body = [
        'email'  => $maintenanceEmail,
        'apiKey' => $this->apiKey,
    ];

    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])
            ->timeout(30)
            ->post(
                $this->baseUrl . '/Account/GetToken',
                $body
            );

        /*
         * Do NOT log the API key or token.
         */
        logInfo([
            'http_status' => $response->status(),
            'email'       => $maintenanceEmail,
        ], 'Kuda Maintenance Login Response');

        if (! $response->successful()) {
            logInfo([
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ], 'Kuda Maintenance Login Failed');

            return [
                'status'  => false,
                'message' => 'Kuda maintenance authentication failed',
            ];
        }

        /*
         * Kuda documents this response as a raw JWT string.
         */
        $token = trim($response->body());

        /*
         * Handle the case where the provider returns a quoted
         * JSON string instead of plain text.
         */
        if (
            strlen($token) >= 2 &&
            $token[0] === '"' &&
            substr($token, -1) === '"'
        ) {
            $decodedToken = json_decode($token, true);

            if (is_string($decodedToken)) {
                $token = trim($decodedToken);
            }
        }

        /*
         * Handle "Bearer eyJ..." just in case.
         */
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        /*
         * A JWT normally has 3 dot-separated sections.
         * Don't send an invalid token to the API.
         */
        if (
            empty($token) ||
            substr_count($token, '.') !== 2
        ) {
            logInfo([
                'http_status' => $response->status(),
                'token_length' => strlen($token),
                'token_parts'  => substr_count($token, '.') + 1,
            ], 'Kuda Maintenance Invalid Token');

            return [
                'status'  => false,
                'message' => 'Kuda returned an invalid access token',
            ];
        }

        $this->token = $token;

        logInfo([
            'token_received' => true,
            'token_length'   => strlen($token),
        ], 'Kuda Maintenance Login Successful');

        return [
            'status'  => true,
            'message' => 'Login success',
        ];

    } catch (Exception $e) {
        logInfo([
            'error' => $e->getMessage(),
        ], 'Kuda Maintenance Login Error');

        return [
            'status'  => false,
            'message' => 'Server Error connecting to Kuda',
        ];
    }
}

    /**
     * Get exchange rate.
     */
    public function getExchangeRate(
        $exchangeAmount,
        $sourceCurrency,
        $destinationCurrency
    ) {
        $body = [
            'amount'             => $exchangeAmount,
            'destination_currency' => $destinationCurrency,
            'source_currency'      => $sourceCurrency,
        ];

        $url = 'transfers/rates'
            . '?amount=' . urlencode($exchangeAmount)
            . '&destination_currency=' . urlencode($destinationCurrency)
            . '&source_currency=' . urlencode($sourceCurrency);

        logInfo(
            "We are converting {$exchangeAmount} from {$sourceCurrency} to {$destinationCurrency}",
            'Kuda Exchange Rate'
        );

        try {
            $exchangeRate = $this->kudaPostRequest($url, $body);

            logInfo(
                [
                    'http_status' => $exchangeRate->status(),
                    'response'    => $exchangeRate->json(),
                ],
                'Kuda Exchange Rates'
            );

            $data = $exchangeRate->json();

            if (($data['status'] ?? null) === 'success') {
                $rate = $data['data']['rate'] ?? null;
                $amount = $data['data']['source']['amount'] ?? null;

                return [
                    'status'  => true,
                    'message' => 'Exchange rate received',
                    'data'    => [
                        'rate'         => $rate,
                        'amount'       => $amount,
                        'final_amount' => $amount,
                    ],
                ];
            }

            return [
                'status'  => false,
                'message' => $data['message'] ?? 'Failed to get Exchange rates',
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Exchange Rate Error'
            );

            return [
                'status'  => false,
                'message' => 'Failed to get Exchange rates',
            ];
        }
    }

    /**
     * Create a normal Kuda dynamic collection account.
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
         * Kuda dynamic pass-through account.
         *
         * The email/phone/trackingReference arguments are retained
         * because existing parts of the application call this method
         * with them.
         */
        $body = [
            'serviceType' => 'ADMIN_CREATE_DYNAMIC_COLLECTION_ACCOUNT',
            'requestRef'  => $requestReference,
            'data'        => [
                'amount'       => 0,
                'accountName'  => trim("$firstName $lastName"),
                'isPassThrough' => true,
            ],
        ];

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            logInfo(
                [
                    'requestRef' => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Request Create Account'
            );

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Create Account Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Account Creation Failed',
                    'data'    => $data,
                ];
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Kuda rejected the account creation request',
                    'data'    => $data,
                ];
            }

            $accountNumber = $data['data']['accountNumber'] ?? null;

            if (! $accountNumber) {
                return [
                    'status'  => false,
                    'message' => 'Kuda did not return an account number',
                    'data'    => $data,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Account Created Successfully',
                'data'    => [
                    'account_number'         => $accountNumber,
                    'virtual_account_number' => $accountNumber,
                ],
            ];

        } catch (Exception $e) {
            logInfo([
                'error'       => $e->getMessage(),
                'request_ref' => $requestReference,
            ], 'Laravel Kuda Create Account Error');

            return [
                'status'  => false,
                'message' => 'Error Creating Account. Kindly contact support for assistance',
            ];
        }
    }

    /**
     * Create the company subscription maintenance virtual account.
     */
    public function createMaintenanceAccount(
    $requestReference,
    $email,
    $phoneNumber,
    $firstName,
    $lastName,
    $trackingReference
) {
    $body = [
        'serviceType' => 'ADMIN_CREATE_DYNAMIC_COLLECTION_ACCOUNT',
        'requestRef'  => $requestReference,
        'data'        => [
            'amount'        => 0,
            'isPassThrough' => true,
        ],
    ];

    try {
        /*
         * Get a fresh maintenance token.
         */
        $login = $this->loginForMaintenance();

        if (! ($login['status'] ?? false)) {
            logInfo([
                'requestRef' => $requestReference,
                'message'    => $login['message'] ?? 'Authentication failed',
            ], 'Kuda Maintenance Authentication Error');

            return [
                'status'  => false,
                'message' => $login['message'] ?? 'Kuda authentication failed',
            ];
        }

        logInfo([
            'requestRef'  => $requestReference,
            'serviceType' => $body['serviceType'],
            'data'        => $body['data'],
        ], 'Kuda Request Create Maintenance Account');

        /*
         * Kuda's production API uses:
         *
         * https://kuda-openapi.kuda.com/v2.1
         */
        $response = Http::withHeaders($this->kudaHeader())
            ->acceptJson()
            ->timeout(60)
            ->post(
                $this->baseUrl,
                $body
            );

        $data = null;

        /*
         * Try JSON first.
         */
        if ($response->header('Content-Type')) {
            $data = $response->json();
        }

        logInfo([
            'http_status' => $response->status(),
            'response'    => $data,
            'requestRef'  => $requestReference,
        ], 'Kuda Create Maintenance Account Response');

        /*
         * 401 means the access token/API credentials are being
         * rejected by Kuda. Return that explicitly.
         */
        if ($response->status() === 401) {
            return [
                'status'  => false,
                'message' => 'Kuda authentication rejected the maintenance access token',
                'data'    => $data,
            ];
        }

        if (! $response->successful()) {
            return [
                'status'  => false,
                'message' => $data['message']
                    ?? 'Kuda account creation request failed',
                'data'    => $data,
            ];
        }

        if (! ($data['status'] ?? false)) {
            return [
                'status'  => false,
                'message' => $data['message']
                    ?? 'Kuda rejected the maintenance account request',
                'data'    => $data,
            ];
        }

        $accountNumber = $data['data']['accountNumber'] ?? null;

        if (! $accountNumber) {
            return [
                'status'  => false,
                'message' => 'Kuda did not return a maintenance account number',
                'data'    => $data,
            ];
        }

        return [
            'status'  => true,
            'message' => 'Account Created Successfully',
            'data'    => [
                'account_number'         => $accountNumber,
                'virtual_account_number' => $accountNumber,
            ],
        ];

    } catch (Exception $e) {
        logInfo([
            'requestRef' => $requestReference,
            'error'      => $e->getMessage(),
        ], 'Laravel Kuda Create Maintenance Account Error');

        return [
            'status'  => false,
            'message' => 'Error Creating Maintenance Account',
        ];
    }
}

    /**
     * Get betting billers.
     */
    public function getBet()
    {
        $requestReference = generateTransactionReference();

        $body = [
            'serviceType' => 'GET_BILLERS_BY_TYPE',
            'requestRef'  => $requestReference,
            'data'        => [
                'BillTypeName' => 'betting',
            ],
        ];

        logInfo($body, 'Kuda Get Bets Request');

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Unavailable',
                ];
            }

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Betting Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message'] ?? 'Bet response failed',
                    'data'    => $data,
                ];
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $data['message'] ?? 'Bet response failed',
                    'data'    => $data,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Bet Response Successfully',
                'data'    => $data['data']['billers'] ?? [],
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Betting Error'
            );

            return [
                'status'  => false,
                'message' => 'Error fetching betting information',
            ];
        }
    }

    /**
     * Withdraw from a Kuda virtual account.
     */
    public function webhookWithdraw(
        $requestReference,
        $amount,
        $narration,
        $trackingReference
    ) {
        $body = [
            'serviceType' => 'WITHDRAW_VIRTUAL_ACCOUNT',
            'requestRef'  => $requestReference,
            'data'        => [
                'amount'            => $amount,
                'narration'         => $narration,
                'trackingReference' => $trackingReference,
            ],
        ];

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            logInfo(
                [
                    'requestRef'  => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Webhook Withdrawal Request'
            );

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Create Withdrawal Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Webhook withdrawal failed',
                ];
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Webhook withdrawal failed',
                ];
            }

            return [
                'status'  => true,
                'message' => 'Webhook Withdrawal Successful',
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Withdrawal Error'
            );

            return [
                'status'  => false,
                'message' => 'Error completing withdrawal',
            ];
        }
    }

    /**
     * Verify bank account.
     */
    public function verifyBankAccount($bankCode, $accountNumber)
    {
        $requestReference = generate_txnID();

        $body = [
            'serviceType' => 'NAME_ENQUIRY',
            'requestRef'  => $requestReference,
            'data'        => [
                'BeneficiaryAccountNumber' => $accountNumber,
                'BeneficiaryBankCode'      => $bankCode,
            ],
        ];

        try {
            $login = $this->login();

            logInfo(
                $login,
                'KUDA LOGIN RESPONSE'
            );

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            logInfo(
                [
                    'requestRef'  => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Verify Account Request'
            );

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Verify Account Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Account verification failed',
                ];
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Account verification failed',
                ];
            }

            return [
                'status'  => true,
                'message' => 'Account Verified Successfully',
                'data'    => [
                    'account_name' => $data['data']['beneficiaryName'] ?? null,
                    'bank_code'    => $data['data']['beneficiaryBankCode'] ?? null,
                    'request_reference' => $requestReference,
                ],
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Verify Account Error'
            );

            return [
                'status'  => false,
                'message' => 'Error completing account verification',
            ];
        }
    }

    /**
     * Verify transaction.
     */
    public function verifyTransaction($transactionReference)
    {
        $requestReference = generate_txnID();

        $body = [
            'serviceType' => 'TRANSACTION_STATUS_QUERY',
            'requestRef'  => $requestReference,
            'data'        => [
                'isThirdPartyBankTransfer'    => false,
                'transactionRequestReference' => $transactionReference,
            ],
        ];

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => '03',
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            logInfo(
                [
                    'requestRef'  => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Verify Transaction Request'
            );

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Verify Transaction Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => '05',
                    'message' => $data['message']
                        ?? 'Verify transaction failed',
                ];
            }

            if (! ($data['status'] ?? false)) {
                $responseCode = $data['responseCode'] ?? null;

                if (in_array($responseCode, ['-4', '-2', '-1'])) {
                    return [
                        'status'  => '02',
                        'message' => 'Your transaction has been found and as well reversed successfully',
                    ];
                }

                if ($responseCode === 'k25') {
                    return [
                        'status'  => '03',
                        'message' => $data['message']
                            ?? 'Transaction still pending',
                    ];
                }

                return [
                    'status'  => '04',
                    'message' => $data['message']
                        ?? 'Transaction verification failed',
                ];
            }

            $sessionId = $data['sessionID'] ?? null;

            return [
                'status'  => '00',
                'message' => $sessionId
                    ? "Transaction Verified Successfully (Session ID: {$sessionId})"
                    : 'Transaction Verified Successfully',
                'data'    => $data['data'] ?? [],
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Verify Transaction Error'
            );

            return [
                'status'  => '06',
                'message' => 'Error verifying transaction',
            ];
        }
    }

    /**
     * Transfer funds through Kuda.
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
        $body = [
            'serviceType' => 'SINGLE_FUND_TRANSFER',
            'requestRef'  => $requestReference,
            'data'        => [
                'BeneficiaryAccount'  => $accountNumber,
                'beneficiaryBankCode' => $bankCode,
                'amount'              => $amount * 100,
                'ClientAccountNumber' => '3002284739',
                'beneficiaryName'     => $accountName,
                'SenderName'          => $description,
                'narration'           => $description,
            ],
        ];

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            logInfo(
                [
                    'requestRef'  => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Bank Transfer Request'
            );

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Bank Transfer Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Bank Transfer failed',
                ];
            }

            /*
             * Preserve your previous response-code fallback behaviour.
             */
            if (($data['responseCode'] ?? null) === '-2') {
                $newType = $type == 1 ? 15 : 1;

                return $this->bankTransfer(
                    $requestReference,
                    $accountNumber,
                    $bankCode,
                    $sessionId,
                    $amount,
                    $accountName,
                    $description,
                    $newType
                );
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => true,
                    'message' => 'Bank Transfer Processing',
                    'data'    => $data,
                ];
            }

            logInfo(
                [
                    'requestRef'  => $requestReference,
                    'serviceType' => $body['serviceType'],
                    'data'        => $body['data'],
                ],
                'Kuda Bank Transfer Successful Response'
            );

            return [
                'status'  => true,
                'message' => 'Bank Transfer Successful',
                'data'    => $data,
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Bank Transfer Error'
            );

            return [
                'status'  => false,
                'message' => 'Error with Bank Transfer. If you have been debited kindly contact support for reversal',
            ];
        }
    }

    /**
     * Purchase airtime.
     */
    public function buyAirtime(
        $transactionId,
        $network,
        $amount,
        $phoneNumber,
        $account
    ) {
        $requestReference = generate_txnID();
        $type = 'airtime';

        $billerId = $this->getBiller($type, $network);

        if (! ($billerId['status'] ?? false)) {
            return [
                'status'  => false,
                'message' => $billerId['message']
                    ?? 'Failed to purchase airtime',
            ];
        }

        $billerIdentifier = $billerId['data'] ?? null;

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            $body = [
                'serviceType' => 'ADMIN_PURCHASE_BILL',
                'requestRef'  => $requestReference,
                'data'        => [
                    'CustomerFirstName'  => $account,
                    'CustomerIdentifier' => $account,
                    'PhoneNumber'        => $phoneNumber,
                    'BillItemIdentifier' => $billerIdentifier,
                    'Amount'             => $amount,
                ],
            ];

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Airtime Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Failed to purchase airtime',
                    'data'    => $data,
                ];
            }

            if (($data['status'] ?? null) === true || ($data['status'] ?? null) === 'true') {
                return [
                    'status'  => true,
                    'message' => 'Airtime Purchase Successful',
                    'data'    => $data,
                ];
            }

            return [
                'status'  => false,
                'message' => $data['response_description']
                    ?? $data['message']
                    ?? 'Failed to purchase airtime',
                'data'    => $data,
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'VTPass / Kuda Airtime Error'
            );

            return [
                'status'  => false,
                'message' => 'Failed to purchase airtime, Please contact support',
            ];
        }
    }

    /**
     * Get billers.
     */
    public function getBiller($type, $network = null)
    {
        $requestReference = generate_txnID();

        try {
            $login = $this->login();

            if (! ($login['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $login['message'] ?? 'Bank Unavailable',
                ];
            }

            $body = [
                'serviceType' => 'GET_BILLERS_BY_TYPE',
                'requestRef'  => $requestReference,
                'data'        => [
                    'BillTypeName' => $type,
                ],
            ];

            $response = $this->kudaPostRequestUpdate(
                $this->baseUrl,
                $body
            );

            $data = $response->json();

            logInfo(
                [
                    'http_status' => $response->status(),
                    'response'    => $data,
                ],
                'Kuda Biller Response'
            );

            if (! $response->successful()) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Failed to fetch billers',
                    'data'    => $data,
                ];
            }

            if (! ($data['status'] ?? false)) {
                return [
                    'status'  => false,
                    'message' => $data['message']
                        ?? 'Failed to fetch billers',
                    'data'    => $data,
                ];
            }

            /*
             * Airtime uses the bill-item identifier for the
             * selected network.
             */
            if (
                isset($data['data']['billers'][0]['description']) &&
                strtolower($data['data']['billers'][0]['description']) === 'airtime'
            ) {
                $airtimeId = $this->getBillAirtimeID(
                    $data,
                    $network
                );

                if (! $airtimeId) {
                    return [
                        'status'  => false,
                        'message' => 'Airtime biller not found for selected network',
                    ];
                }

                return [
                    'status'  => true,
                    'message' => 'Airtime biller fetched successfully',
                    'data'    => $airtimeId,
                ];
            }

            return [
                'status'  => true,
                'message' => "{$type} fetched successfully",
                'data'    => $data['data'] ?? [],
            ];

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda Biller Error'
            );

            return [
                'status'  => false,
                'message' => "Failed to fetch {$type}",
            ];
        }
    }

    /**
     * Find airtime bill item ID for a network.
     */
    public function getBillAirtimeID($data, $network)
    {
        $airtimeIdName = '';

        switch (strtolower($network)) {
            case 'mtn':
                $airtimeIdName = 'MTN VTU';
                break;

            case 'glo':
                $airtimeIdName = 'GLO VTU';
                break;

            case 'airtel':
                $airtimeIdName = 'AIRTEL VTU';
                break;

            case '9mobile':
                $airtimeIdName = '9MOBILE VTU';
                break;
        }

        if ($airtimeIdName === '') {
            return null;
        }

        $billers = $data['data']['billers'] ?? [];

        foreach ($billers as $airtime) {
            $billItems = $airtime['billItems'] ?? [];

            foreach ($billItems as $billItem) {
                if (
                    isset($billItem['name']) &&
                    strtoupper($billItem['name']) === strtoupper($airtimeIdName)
                ) {
                    return $billItem['kudaIdentifier'] ?? null;
                }
            }
        }

        logInfo(
            $airtimeIdName,
            "Kuda Airtime Biller ID Not Found"
        );

        return null;
    }

    /**
     * Send a POST request using the normal Kuda request format.
     *
     * Supports:
     * - relative endpoints
     * - the main https://.../v2.1 endpoint
     */
    public function kudaPostRequest($endpoint, $body)
    {
        return $this->postToKuda($endpoint, $body);
    }

    /**
     * Send a POST request used by the existing controller methods.
     */
    public function kudaPostRequestUpdate($endpoint, $body)
    {
        return $this->postToKuda($endpoint, $body);
    }

    /**
     * Shared Kuda POST implementation.
     */
    private function postToKuda($endpoint, $body)
    {
        $url = $this->resolveKudaEndpoint($endpoint);

        logInfo(
            [
                'url' => $url,
                'body' => $body,
            ],
            'Kuda Process Endpoint Body'
        );

        return Http::withHeaders($this->kudaHeader())
            ->acceptJson()
            ->timeout(60)
            ->post($url, $body);
    }

    /**
     * Build the correct Kuda endpoint URL.
     */
    private function resolveKudaEndpoint($endpoint)
    {
        /*
         * If a full URL is provided, use it exactly.
         */
        if (preg_match('/^https?:\/\//i', $endpoint)) {
            return $endpoint;
        }

        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * GET request helper retained for compatibility.
     */
    public function monnifyGetRequest($endpoint)
    {
        $url = $this->resolveKudaEndpoint($endpoint);

        try {
            $response = Http::withHeaders($this->kudaHeader())
                ->acceptJson()
                ->timeout(60)
                ->get($url);

            return $response->json();

        } catch (Exception $e) {
            logInfo(
                $e->getMessage(),
                'Laravel Kuda GET Error'
            );

            return [
                'status'  => false,
                'message' => 'Kuda request failed',
            ];
        }
    }

    /**
     * Sanitize a JSON string where required by legacy code.
     */
    public function sanitizeResponse($stringValue)
    {
        return json_decode(
            str_replace('\\\\', '', $stringValue),
            true
        );
    }

    /**
     * Kuda authorization headers.
     */
    public function kudaHeader()
{
    return [
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer ' . trim($this->token),
    ];
}

private function extractKudaAccessToken($response)
{
    $token = trim($response->body());

    if ($token === '') {
        return null;
    }

    /*
     * Normally Kuda returns a plain-text JWT.
     *
     * But support these formats as well:
     * 1. "eyJ..."
     * 2. {"token":"eyJ..."}
     * 3. {"accessToken":"eyJ..."}
     * 4. {"access_token":"eyJ..."}
     */
    $decoded = json_decode($token, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if (is_string($decoded)) {
            $token = trim($decoded);
        } elseif (is_array($decoded)) {
            $token =
                $decoded['accessToken']
                ?? $decoded['access_token']
                ?? $decoded['token']
                ?? null;

            if ($token !== null) {
                $token = trim($token);
            }
        }
    }

    if (! is_string($token) || $token === '') {
        return null;
    }

    /*
     * Handle "Bearer eyJ..."
     */
    if (stripos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    /*
     * Remove accidental surrounding quotes.
     */
    $token = trim($token, "\"'");

    /*
     * Kuda access tokens are JWTs.
     * A JWT has 3 dot-separated sections.
     */
    if (substr_count($token, '.') !== 2) {
        return null;
    }

    return $token;
}

}