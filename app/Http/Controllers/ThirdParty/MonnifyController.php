<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Services\MonnifyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonnifyController extends WebhookController
{
    private MonnifyService $monnifyService;

    public function __construct(MonnifyService $monnifyService)
    {
        $this->monnifyService = $monnifyService;
    }

    /**
     * Get Monnify credentials for a company.
     */
    private function credentials($companyId)
    {
        return $this->monnifyService->initCredentials($companyId);
    }

    /**
     * Get Monnify base URL.
     */
    private function baseUrl()
    {
        return env('APP_MODE') === 'test'
            ? config('monnify.base_url_test')
            : config('monnify.base_url');
    }

    /**
     * Authenticate with Monnify.
     *
     * The API key and secret key are determined by company ID.
     */
    public function login($companyId)
    {
        try {

            $credentials = $this->credentials($companyId);

            $authorization = base64_encode(
                $credentials['apiKey'] . ':' . $credentials['secretKey']
            );

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $authorization,
                'Content-Type' => 'application/json',
            ])->post(
                rtrim($this->baseUrl(), '/') . '/api/v1/auth/login'
            );

            $result = $response->json();

            logInfo(
                json_encode($result),
                "Monnify Login Response"
            );

            if (
                $response->successful() &&
                ($result['requestSuccessful'] ?? false)
            ) {
                return [
                    'status' => true,
                    'message' => 'Login success',
                    'data' => [
                        'token' => $result['responseBody']['accessToken'],
                    ],
                ];
            }

            return [
                'status' => false,
                'message' => $result['responseMessage']
                    ?? 'Failed to login to Monnify',
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Monnify Login Error"
            );

            return [
                'status' => false,
                'message' => 'Server Error...',
            ];
        }
    }

    /**
     * Create a Monnify wallet for a customer.
     *
     * The wallet is owned/managed by Monnify.
     * We simply return the account details provided by Monnify.
     */
    public function createWallet(
        $companyId,
        $walletReference,
        $walletName,
        $customerName,
        $customerEmail,
        $bvn,
        $bvnDateOfBirth
    ) {
        try {

            $login = $this->login($companyId);

            if (!$login['status']) {
                return [
                    'status' => false,
                    'message' => 'Failed to authenticate with Monnify',
                ];
            }

            $body = [
                'walletReference' => $walletReference,
                'walletName' => $walletName,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'bvnDetails' => [
                    'bvn' => $bvn,
                    'bvnDateOfBirth' => $bvnDateOfBirth,
                ],
            ];

            $result = $this->monnifyPostRequest(
                $companyId,
                'api/v1/disbursements/wallet',
                $body,
                $login['data']['token']
            );

            logInfo(
                $result,
                "Monnify Create Wallet Response"
            );

            if (
                ($result['requestSuccessful'] ?? false)
            ) {
                return [
                    'status' => true,
                    'message' => 'Wallet created successfully',
                    'data' => $result['responseBody'] ?? [],
                ];
            }

            return [
                'status' => false,
                'message' => $result['responseMessage']
                    ?? 'Failed to create Monnify wallet',
                'data' => $result,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Monnify Create Wallet Error"
            );

            return [
                'status' => false,
                'message' => 'Server Error...Please contact support',
            ];
        }
    }

    /**
     * Perform a bank transfer through Monnify.
     */
    public function bankTransfer(
        $companyId,
        $amount,
        $transactionReference,
        $description,
        $bankCode,
        $accountNumber
    ) {
        $body = [
            "amount" => $amount,
            "reference" => $transactionReference,
            "narration" => $description,
            "destinationBankCode" => $bankCode,
            "destinationAccountNumber" => $accountNumber,
            "currency" => "NGN",
            "sourceAccountNumber" => "your account number",
        ];

        try {

            $loginResult = $this->login($companyId);

            if (!$loginResult['status']) {
                return [
                    'status' => false,
                    'message' => 'Failed to complete transfer',
                ];
            }

            $result = $this->monnifyPostRequest(
                $companyId,
                'api/v2/disbursements/single',
                $body,
                $loginResult['data']['token']
            );

            logInfo(
                $result,
                "Monnify Transfer Response"
            );

            if (
                ($result['requestSuccessful'] ?? false)
            ) {
                return [
                    'status' => true,
                    'message' => 'Bank Transfer Successful',
                    'data' => $result['responseBody'] ?? [],
                ];
            }

            return [
                'status' => false,
                'message' => $result['responseMessage']
                    ?? 'Failed to complete transfer',
                'data' => $result,
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Monnify Transfer Error"
            );

            return [
                'status' => false,
                'message' => 'Server Error...Please contact support to resolve',
            ];
        }
    }

    /**
     * Get Monnify transaction status.
     */
    public function getTransactionStatus($companyId, $reference)
    {
        try {

            $reference = urlencode($reference);

            $loginMonnify = $this->login($companyId);

            if (!$loginMonnify['status']) {
                return [
                    'status' => "01",
                    'message' => $loginMonnify['message']
                        ?? 'Failed to authenticate with Monnify',
                ];
            }

            $result = $this->monnifyGetRequest(
                $companyId,
                "api/v2/disbursements/single/summary?reference={$reference}",
                $loginMonnify['data']['token']
            );

            logInfo(
                $result,
                "Monnify Transaction Status Response"
            );

            if (
                ($result['requestSuccessful'] ?? false)
            ) {

                $responseBody = $result['responseBody'] ?? [];
                $paymentStatus = $responseBody['status'] ?? null;

                if ($paymentStatus === "SUCCESS") {

                    return [
                        'status' => "00",
                        'message' => 'Transaction verified successfully',
                        'data' => [
                            'payment_status' => true,
                            'amount_paid' => $responseBody['amount'] ?? 0,
                        ],
                    ];

                } elseif ($paymentStatus === "FAILED") {

                    return [
                        'status' => "02",
                        'message' => 'Transaction failed and has been reversed',
                        'data' => [
                            'payment_status' => false,
                            'amount_paid' => $responseBody['amount'] ?? 0,
                        ],
                    ];

                } else {

                    return [
                        'status' => "04",
                        'message' => $result['responseMessage']
                            ?? 'Transaction status is pending',
                    ];
                }
            }

            if (($result['responseCode'] ?? null) === "99") {
                return [
                    'status' => "03",
                    'message' => $result['responseMessage']
                        ?? 'Transaction verification failed',
                ];
            }

            return [
                'status' => "04",
                'message' => $result['responseMessage']
                    ?? 'Failed to verify transaction',
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Monnify Transaction Status Error"
            );

            return [
                'status' => "05",
                'message' => 'Server Error...',
            ];
        }
    }

    /**
     * Get exchange rate.
     *
     * NOTE:
     * This currently uses Flutterwave and is left unchanged
     * because it is not a Monnify operation.
     */
    public function getExchangeRate(
        $exchangeAmount,
        $sourceCurrency,
        $destinationCurrency
    ) {
        $body = [
            "amount" => $exchangeAmount,
            "destination_currency" => $sourceCurrency,
            "source_currency" => $destinationCurrency,
        ];

        $url = "transfers/rates?amount=$exchangeAmount"
            . "&destination_currency=$destinationCurrency"
            . "&source_currency=$sourceCurrency";

        logInfo(
            "We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency"
        );

        try {

            $exhangeRate = $this->flutterWavePostRequest($url, $body);

            logInfo(
                $exhangeRate,
                "Exchange Rates"
            );

            if ($exhangeRate['status'] == 'success') {

                $rate = $exhangeRate["data"]["rate"];
                $amount = $exhangeRate["data"]["source"]["amount"];

                $finalAmount = $amount;

                return [
                    'status' => true,
                    'message' => "Exchange rate received",
                    'data' => [
                        'rate' => $rate,
                        'amount' => $amount,
                        'final_amount' => $finalAmount,
                    ],
                ];

            }

            return [
                'status' => false,
                'message' => 'Failed to get Exchange rates',
            ];

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                "Flutterwave Error"
            );

            return [
                'status' => false,
                'message' => 'Failed to get Exchange rates',
            ];
        }
    }

    /**
     * Verify incoming Monnify webhook.
     */
    public function verifyIncomingWebhook(Request $request)
    {
        $stringToEncrypt =
            config('monnify.secret_key_live')
            . "|$request->paymentReference"
            . "|$request->amountPaid"
            . "|$request->paidOn"
            . "|$request->transactionReference";

        $hashed = hash(
            "sha512",
            $stringToEncrypt
        );

        if (
            strtolower($request->transactionHash) != strtolower($hashed)
        ) {
            return [
                'status' => false,
                'message' => 'Failed to verify Transaction Hash',
            ];
        }

        return [
            'status' => true,
            'message' => 'Webhook Verify Success',
        ];
    }

    /**
     * Make a POST request to Monnify.
     *
     * The token is supplied by login($companyId), so
     * the request is made using the correct company's credentials.
     */
    public function monnifyPostRequest(
        $companyId,
        $endpoint,
        $body,
        $token
    ) {
        $response = Http::withHeaders(
            $this->monnifyHeader($token)
        )->post(
            rtrim($this->baseUrl(), '/') . '/' . ltrim($endpoint, '/'),
            $body
        );

        return $response->json();
    }

    /**
     * Make a GET request to Monnify.
     */
    public function monnifyGetRequest(
        $companyId,
        $endpoint,
        $token
    ) {
        $response = Http::withHeaders(
            $this->monnifyHeader($token)
        )->get(
            rtrim($this->baseUrl(), '/') . '/' . ltrim($endpoint, '/')
        );

        return $response->json();
    }

    /**
     * Generate Monnify request headers.
     */
    public function monnifyHeader($token)
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
 * Get wallets created by a merchant.
 *
 * @param int|string $companyId
 * @param string|null $walletReference
 * @param int|null $pageSize
 * @param int|null $pageNo
 */
public function getWallets(
    $companyId,
    $walletReference = null,
    $pageSize = null,
    $pageNo = null
) {
    try {

        $login = $this->login($companyId);

        if (!$login['status']) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Monnify',
            ];
        }

        /*
         * Build query parameters.
         */
        $query = [];

        if (!empty($walletReference)) {
            $query['walletReference'] = $walletReference;
        }

        if (!is_null($pageSize)) {
            $query['pageSize'] = $pageSize;
        }

        if (!is_null($pageNo)) {
            $query['pageNo'] = $pageNo;
        }

        /*
         * Build endpoint.
         */
        $endpoint = 'api/v1/disbursements/wallet';

        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }

        /*
         * Make request to Monnify.
         */
        $result = $this->monnifyGetRequest(
            $companyId,
            $endpoint,
            $login['data']['token']
        );

        logInfo(
            $result,
            "Monnify Get Wallets Response"
        );

        if (
            ($result['requestSuccessful'] ?? false)
        ) {
            return [
                'status' => true,
                'message' => 'Wallets retrieved successfully',
                'data' => $result['responseBody'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['responseMessage']
                ?? 'Failed to retrieve Monnify wallets',
            'data' => $result,
        ];

    } catch (Exception $e) {

        logInfo(
            $e->getMessage(),
            "Monnify Get Wallets Error"
        );

        return [
            'status' => false,
            'message' => 'Server Error...Please contact support',
        ];
    }
}

/**
 * Initialize a Monnify transaction.
 *
 * This creates the transaction that can subsequently be
 * used for card payment, bank transfer, USSD, etc.
 */
public function initializeTransaction(
    $companyId,
    $amount,
    $paymentReference,
    $customerEmail,
    $customerName = null,
    $paymentDescription = null,
    $redirectUrl = null,
    $paymentMethods = ['CARD', 'ACCOUNT_TRANSFER', 'USSD', 'PHONE_NUMBER'],
    $metadata = [],
    $incomeSplitConfig = []
) {
    try {

        $login = $this->login($companyId);

        if (!$login['status']) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Monnify',
            ];
        }

        $credentials = $this->credentials($companyId);

        /*
         * The contract code belongs to the Monnify merchant
         * whose credentials are being used.
         *
         * If you add contractCode to MonnifyService credentials,
         * it will be retrieved here.
         */
        $body = [
            'amount' => $amount,
            'contractCode' => $credentials['contractCode'],
            'currencyCode' => 'NGN',
            'customerEmail' => $customerEmail,
            'paymentReference' => $paymentReference,
            'customerName' => $customerName,
            'paymentDescription' => $paymentDescription,
            'paymentMethods' => $paymentMethods,
            'metadata' => $metadata,
            'redirectUrl' => $redirectUrl,
            'incomeSplitConfig' => $incomeSplitConfig,
        ];

        /*
         * Remove optional values that were not supplied.
         */
        $body = array_filter(
            $body,
            fn ($value) => !is_null($value)
        );

        $result = $this->monnifyPostRequest(
            $companyId,
            'api/v1/merchant/transactions/init-transaction',
            $body,
            $login['data']['token']
        );

        logInfo(
            $result,
            "Monnify Initialize Transaction Response"
        );

        if (
            ($result['requestSuccessful'] ?? false)
        ) {
            return [
                'status' => true,
                'message' => 'Transaction initialized successfully',
                'data' => $result['responseBody'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['responseMessage']
                ?? 'Failed to initialize transaction',
            'data' => $result,
        ];

    } catch (Exception $e) {

        logInfo(
            $e->getMessage(),
            "Monnify Initialize Transaction Error"
        );

        return [
            'status' => false,
            'message' => 'Server Error...Please contact support',
        ];
    }
}


/**
 * Initialize a Monnify bank transfer payment.
 *
 * This generates a temporary/dynamic account number
 * that the customer can transfer money into.
 */
public function initializeBankTransferPayment(
    $companyId,
    $transactionReference,
    $bankCode = null
) {
    try {

        $login = $this->login($companyId);

        if (!$login['status']) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Monnify',
            ];
        }

        $body = [
            'transactionReference' => $transactionReference,
        ];

        /*
         * bankCode is optional according to Monnify.
         */
        if (!empty($bankCode)) {
            $body['bankCode'] = $bankCode;
        }

        $result = $this->monnifyPostRequest(
            $companyId,
            'api/v1/merchant/bank-transfer/init-payment',
            $body,
            $login['data']['token']
        );

        logInfo(
            $result,
            "Monnify Initialize Bank Transfer Response"
        );

        if (
            ($result['requestSuccessful'] ?? false)
        ) {
            return [
                'status' => true,
                'message' => 'Bank transfer payment initialized successfully',
                'data' => $result['responseBody'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['responseMessage']
                ?? 'Failed to initialize bank transfer payment',
            'data' => $result,
        ];

    } catch (Exception $e) {

        logInfo(
            $e->getMessage(),
            "Monnify Initialize Bank Transfer Error"
        );

        return [
            'status' => false,
            'message' => 'Server Error...Please contact support',
        ];
    }
}

}