<?php

namespace App\Http\Controllers\ThirdParty;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NinePSBSandboxController
{
    private $baseUrl;
    private $token;
    private $publicKey;
    private $privateKey;

    public function __construct()
    {
        if (env('APP_MODE') !== 'test') {
            abort(403, '9PSB sandbox testing is only available when APP_MODE=test.');
        }

        $this->publicKey  = env('PSB9_TEST_PUBLIC_KEY');
        $this->privateKey = env('PSB9_TEST_PRIVATE_KEY');
        $this->baseUrl    = 'https://baastest.9psb.com.ng';

        $this->token = '';
    }

    /**
     * Authenticate with 9PSB.
     */
    private function login($action)
    {
        $body = [
            'publickey'  => $this->publicKey,
            'privatekey' => $this->privateKey,
        ];

        if ($action === 'virtualaccount') {
            $url = $this->baseUrl . '/ipaymw-api/v1/virtualaccount/authenticate';
        } else {
            $url = $this->baseUrl . '/ipaymw-api/v1/merchant/authenticate';
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $body);

            logInfo($response->body(), '9PSB Sandbox Login Response');

            if ($response->successful() && $response->json('code') === '00') {
                $this->token = $response->json('access_token');

                return [
                    'status' => true,
                    'message' => 'Login success',
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to login to 9PSB',
                'response' => $response->json(),
            ];

        } catch (Exception $e) {

            logInfo($e->getMessage(), '9PSB Sandbox Login Error');

            return [
                'status' => false,
                'message' => 'Server Error...',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Common 9PSB headers.
     */
    private function headers()
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    /**
     * Create Static Virtual Account.
     *
     * POST /9psb/sandbox/virtual-account
     */
    public function createVirtualAccount(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $login = $this->login('virtualaccount');

        if (!$login['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to login to 9PSB',
                'data' => $login,
            ], 500);
        }

        $body = [
            'transaction' => [
                'reference' => $request->reference,
            ],
            'order' => [
                'amount' => 0,
                'currency' => 'NGN',
                'description' => 'Create Account',
                'country' => 'NGA',
                'amounttype' => 'ANY',
            ],
            'customer' => [
                'account' => [
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'type' => 'STATIC',
                ],
            ],
        ];

        $url = $this->baseUrl . '/virtualaccount/create';

        try {

            logInfo(json_encode($body), '9PSB Sandbox Create VA Request');

            $response = Http::withHeaders($this->headers())
                ->post($url, $body);

            logInfo($response->body(), '9PSB Sandbox Create VA Response');

            return response()->json([
                'status' => $response->successful(),
                'message' => $response->successful()
                    ? '9PSB Create Virtual Account Response'
                    : '9PSB Create Virtual Account Failed',
                'data' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {

            logInfo($e->getMessage(), '9PSB Sandbox Create VA Error');

            return response()->json([
                'status' => false,
                'message' => 'Error creating virtual account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Bank Account.
     *
     * POST /9psb/sandbox/account/verify
     */
    public function verifyBankAccount(Request $request)
    {
        $request->validate([
            'bank_code' => 'required|string',
            'account_number' => 'required|string',
        ]);

        $login = $this->login('fundtransfer');

        if (!$login['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Bank Unavailable. Try again later',
                'data' => $login,
            ], 500);
        }

        $body = [
            'customer' => [
                'account' => [
                    'number' => $request->account_number,
                    'bank' => $request->bank_code,
                ],
            ],
        ];

        $url = $this->baseUrl . '/account/enquiry';

        try {

            logInfo(json_encode($body), '9PSB Sandbox Account Verification Request');

            $response = Http::withHeaders($this->headers())
                ->post($url, $body);

            logInfo($response->body(), '9PSB Sandbox Account Verification Response');

            return response()->json([
                'status' => $response->successful(),
                'message' => $response->successful()
                    ? '9PSB Account Verification Response'
                    : '9PSB Account Verification Failed',
                'data' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {

            logInfo($e->getMessage(), '9PSB Sandbox Account Verification Error');

            return response()->json([
                'status' => false,
                'message' => 'Error verifying bank account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bank Transfer.
     *
     * POST /9psb/sandbox/transfer
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'account_name' => 'required|string',
            'description' => 'required|string',
        ]);

        $login = $this->login('fundtransfer');

        if (!$login['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Bank Unavailable. Try again later',
                'data' => $login,
            ], 500);
        }

        $amount = number_format(
            (float) $request->amount,
            2,
            '.',
            ''
        );

        /*
         * These are the sender accounts currently hard-coded
         * in your existing NinePSBController.
         *
         * They are used here ONLY for sandbox testing.
         */
        $senderAccount = '1100076684';
        $senderAccountNumber = '1100015371';

        $hash = $this->generateHash(
            $this->privateKey,
            $senderAccount,
            $request->account_number,
            $request->bank_code,
            $amount,
            $request->reference
        );

        $body = [
            'transaction' => [
                'reference' => $request->reference,
            ],
            'order' => [
                'amount' => $amount,
                'description' => $request->description,
                'currency' => 'NGN',
                'country' => 'NGA',
            ],
            'customer' => [
                'account' => [
                    'number' => $request->account_number,
                    'bank' => $request->bank_code,
                    'name' => $request->account_name,
                    'senderaccountnumber' => $senderAccountNumber,
                    'sendername' => $request->description,
                ],
            ],
            'hash' => $hash,
        ];

        $url = $this->baseUrl . '/account/transfers';

        try {

            logInfo(json_encode($body), '9PSB Sandbox Transfer Request');

            $response = Http::withHeaders($this->headers())
                ->post($url, $body);

            logInfo($response->body(), '9PSB Sandbox Transfer Response');

            return response()->json([
                'status' => $response->successful(),
                'message' => $response->successful()
                    ? '9PSB Bank Transfer Response'
                    : '9PSB Bank Transfer Failed',
                'data' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {

            logInfo($e->getMessage(), '9PSB Sandbox Transfer Error');

            return response()->json([
                'status' => false,
                'message' => 'Error processing bank transfer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Transaction.
     *
     * GET/POST 9PSB status endpoint through our test route.
     *
     * POST /9psb/sandbox/transaction/verify
     */
    public function verifyTransaction(Request $request)
    {
        $request->validate([
            'transaction_reference' => 'required|string',
        ]);

        $login = $this->login('fundtransfer');

        if (!$login['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Bank Unavailable. Try again later',
                'data' => $login,
            ], 500);
        }

        $reference = $request->transaction_reference;

        $url = $this->baseUrl .
            '/account/transfer/status?reference=' .
            urlencode($reference);

        try {

            logInfo($url, '9PSB Sandbox Verify Transaction URL');

            /*
             * Your existing controller uses POST against this endpoint,
             * so we preserve that behaviour here.
             */
            $response = Http::withHeaders($this->headers())
                ->post($url, []);

            logInfo($response->body(), '9PSB Sandbox Verify Transaction Response');

            return response()->json([
                'status' => $response->successful(),
                'message' => $response->successful()
                    ? '9PSB Transaction Verification Response'
                    : '9PSB Transaction Verification Failed',
                'data' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {

            logInfo($e->getMessage(), '9PSB Sandbox Verify Transaction Error');

            return response()->json([
                'status' => false,
                'message' => 'Error verifying transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Transaction Reversal.
     *
     * POST /9psb/sandbox/transaction/reversal
     */
    public function verifyTransactionReversal(Request $request)
    {
        $request->validate([
            'transaction_reference' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $login = $this->login('fundtransfer');

        if (!$login['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Bank Unavailable. Try again later',
                'data' => $login,
            ], 500);
        }

        $body = [
            'transaction' => [
                'reference' => $request->transaction_reference,
                'amount' => $request->amount,
            ],
        ];

        $url = $this->baseUrl . '/account/transfer/reversalstatus';

        try {

            logInfo(
                json_encode($body),
                '9PSB Sandbox Reversal Request'
            );

            $response = Http::withHeaders($this->headers())
                ->post($url, $body);

            logInfo(
                $response->body(),
                '9PSB Sandbox Reversal Response'
            );

            return response()->json([
                'status' => $response->successful(),
                'message' => $response->successful()
                    ? '9PSB Transaction Reversal Response'
                    : '9PSB Transaction Reversal Failed',
                'data' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {

            logInfo(
                $e->getMessage(),
                '9PSB Sandbox Reversal Error'
            );

            return response()->json([
                'status' => false,
                'message' => 'Error checking transaction reversal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate 9PSB SHA512 hash.
     */
    private function generateHash(
        $privateKey,
        $senderAccount,
        $beneficiaryAccount,
        $bankCode,
        $amount,
        $reference
    ) {
        $rawData =
            $privateKey .
            $senderAccount .
            $beneficiaryAccount .
            $bankCode .
            $amount .
            $reference;

        return strtoupper(hash('sha512', $rawData));
    }
}