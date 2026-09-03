<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Services\GTBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class GTBController extends WebhookController
{
    private $secretKey;
    private $publicKey;
    private $beneficiaryAccount;
    private $baseUrl;

    private $secretKeySquado;
    private $secretKeySquadoTest;

    private $publicKeySquado;
    private $publicKeySquadoTest;

    private $beneficiaryAccountSquado;
    private $beneficiaryAccountSquadoTest;

    private $baseUrlSquado;
    private $baseUrlSquadoTest;

    // public function __construct()
    // {
    //     if (env('APP_MODE') === 'test') {

    //         $this->secretKey          = config('gtb.secret_key_test');
    //         $this->publicKey          = config('gtb.public_key_test');
    //         $this->beneficiaryAccount = config('gtb.beneficiary_account_test');
    //         $this->baseUrl            = config('gtb.base_url_test');

    //         $this->baseUrlSquado     = config('gtb.base_url_squado');
    //         $this->baseUrlSquadoTest = config('gtb.base_url_squado_test');

    //     } else {
 
    //         $this->secretKey          = config('gtb.secret_key_live');
    //         $this->publicKey          = config('gtb.public_key_live');
    //         $this->beneficiaryAccount = config('gtb.beneficiary_account_live');
    //         $this->baseUrl            = config('gtb.base_url_live');

    //         $this->baseUrlSquado     = config('gtb.base_url_squado');
    //         $this->baseUrlSquadoTest = config('gtb.base_url_squado_test');
    //     }

    //     logInfo([
    //         'app_mode'              => env('APP_MODE'),
    //         'base_url_squado'       => $this->baseUrlSquado,
    //         'base_url_squado_test'  => $this->baseUrlSquadoTest,
    //     ], 'GTB Controller Configuration');
    // }

public function getToken($company_id = null)
{
     $gtbService = new GTBService();

     $credentials = $gtbService->initCredentials($company_id);

    if (env('APP_MODE') === 'test') {

        $this->secretKeySquado =
            $credentials['secretKeySquadoTest'];

        $this->publicKeySquado =
            $credentials['publicKeySquadoTest'];

        $this->beneficiaryAccountSquado =
            $credentials['beneficiaryAccountTest'];

        $this->baseUrlSquado =
            $credentials['baseUrlSquadoTest'];

    } else {

        $this->secretKeySquado =
            $credentials['secretKeySquado'];

        $this->publicKeySquado =
            $credentials['publicKeySquado'];

        $this->beneficiaryAccountSquado =
            $credentials['beneficiaryAccount'];

        $this->baseUrlSquado =
            $credentials['baseUrlSquado'];
    }

    logInfo([
        'company_id' => $company_id,
        'app_mode' => env('APP_MODE'),
        'beneficiary_account_present' => !empty($this->beneficiaryAccountSquado),
        'secret_key_present' => !empty($this->secretKeySquado),
        'base_url' => $this->baseUrlSquado,
      ], 'GTB credentials loaded');

    return [
        'status' => true,
        'message' => 'GTB credentials loaded successfully',
    ];
}

public function createVirtualAccount(
    $firstName,
    $lastName,
    $dob,
    $gender,
    $bvn,
    $email,
    $phone,
    $address,
    $customerReference,
    $companyID
) {
    if (empty($firstName) || empty($lastName)) {
        logInfo([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company_id' => $companyID,
        ], "GTB Invalid Customer Name");

        return [
            'status' => false,
            'message' => "Invalid Customer Name"
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Load credentials for this company
    |--------------------------------------------------------------------------
    */
    $credentials = $this->getToken($companyID);

    if (! $credentials['status']) {
        return $credentials;
    }

    /*
    |--------------------------------------------------------------------------
    | Make sure beneficiary account exists
    |--------------------------------------------------------------------------
    */
    if (empty($this->beneficiaryAccountSquado)) {
        logInfo([
            'company_id' => $companyID,
        ], "GTB Beneficiary Account Missing");

        return [
            'status' => false,
            'message' => 'GTB beneficiary account has not been configured for this company.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build request
    |--------------------------------------------------------------------------
    */
    $body = [
        "first_name"          => $firstName,
        "last_name"           => $lastName,
        "middle_name"         => $firstName,
        "mobile_num"          => $phone,
        "dob"                 => date("m/d/Y", strtotime($dob)),
        "email"               => $email,
        "bvn"                 => $bvn,
        "gender"              => $gender == "Male" ? '1' : '2',
        "address"             => $address,
        "customer_identifier" => $customerReference,
        "beneficiary_account" => $this->beneficiaryAccountSquado,
    ];

    $endpoint = "virtual-account";

    logInfo([
        'company_id' => $companyID,
        'beneficiary_account_present' => !empty($this->beneficiaryAccountSquado),
    ], "GTB Request Configuration");

    logInfo($body, "GT Request Create Virtual account body");

    try {
        $createAccount = $this->gtbPostRequest($endpoint, $body);

        logInfo($createAccount, "GTB Response");

        if (isset($createAccount['success'])) {

            if ($createAccount['success']) {
                return [
                    'status' => true,
                    'message' => $createAccount['message'],
                    'data' => $createAccount['data']
                ];
            }

            return [
                'status' => false,
                'message' => $createAccount['message'],
                'data' => $createAccount
            ];
        }

        return [
            'status' => false,
            'message' => "Failed to Create Account... Please contact support",
            'data' => $createAccount
        ];

    } catch (\Exception $e) {

        logInfo($e->getMessage(), "GTB Error");

        return [
            'status' => false,
            'message' => "Failed to Create Account... Please contact support"
        ];
    }
}

public function testCreateVirtualAccount(Request $request)
{
    $validator = Validator::make($request->all(), [
        'first_name'         => 'required|string|max:50',
        'last_name'          => 'required|string|max:50',
        'dob'                => 'required|date',
        'gender'             => 'required|string',
        'bvn'                => 'required|numeric|digits:11',
        'email'              => 'nullable|email',
        'phone'              => 'required|string',
        'address'            => 'required|string',
        'customer_reference' => 'required|string',
        'company_id'         => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    try {

        $result = $this->createVirtualAccount(
            $request->first_name,
            $request->last_name,
            $request->dob,
            $request->gender,
            $request->bvn,
            $request->email,
            $request->phone,
            $request->address,
            $request->customer_reference,
            $request->company_id
        );

        logInfo($result, 'GTB Sandbox API Test');

        return response()->json($result);

    } catch (\Throwable $e) {

        logInfo([
            'message'   => $e->getMessage(),
            'company_id' => $request->company_id,
        ], 'GTB Sandbox API Test Error');

        return response()->json([
            'status'  => false,
            'message' => 'An error occurred while creating the virtual account.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    public function createCompanyVirtualAccount($businessName, $bvn, $phone, $customerReference, $companyID)
    {

        $body = [
            "business_name"       => $businessName,
            "mobile_num"          => $phone,
            "bvn"                 => $bvn,
            "customer_identifier" => $customerReference,
        ];

        $this->getToken($companyID);

        $endpoint = "virtual-account/business";

        logInfo($body, "GT Request body");
        try {
            $createAccount = $this->gtbPostRequest($endpoint, $body);

            logInfo($createAccount, "GTB Response");

            if (isset($createAccount['status'])) {
                if ($createAccount['status'] == 200) {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (\Exception $e) {
            logInfo($e->getMessage(), "GTB Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {
        logInfo($request, "GTB Webhook Incoming");

        $signature = $request->header('x - squad - signature');

        $localSignature = env('APP_MODE') == 'live' ? config('gtb . secret_key_live') : config('gtb . secret_key_live');

        if ($signature !== hash_hmac('sha512', json_encode($request->all(), JSON_UNESCAPED_SLASHES), $localSignature)) {
            return ['status' => false, 'message' => 'he WebhookrequestisnotfromSQAD', 'response_code' => 400, 'transaction_reference' => $request->transaction_reference, 'response_description' => "The Webhook request is not from SQAD"];
        }

        return ['status' => true, 'message' => 'Webhook VerifiedSuccessfully'];
    }

    public function insertIntoWebhookTable(Request $request)
    {

        $webhook = GtbWebhook::where('transaction_reference', $request->transaction_reference)->first();

        if (! $webhook) {

            GtbWebhook::create([
                'transaction_reference'  => $request->transaction_reference,
                'virtual_account_number' => $request->virtual_account_number,
                'principal_amount'       => $request->principal_amount,
                'settled_amount'         => $request->settled_amount,
                'fee_charged'            => $request->fee_charged,
                'transaction_date'       => $request->transaction_date,
                'customer_identifier'    => $request->customer_identifier,
                'transaction_indicator'  => $request->transaction_indicator,
                'remarks'                => $request->remarks,
                'currency'               => $request->currency,
                'channel'                => $request->channel,
                'encrypted_body'         => $request->encrypted_body,
            ]);
        }
    }

    public function verifyBankAccount($bankCode, $accountNumber)
    {

        $requestReference = \generate_txnID();

        $body = [
            "bank_code"      => $bankCode,
            "account_number" => $accountNumber,

        ];

        $companyID = auth('api')->user()->company_id ?? auth()->user()->company_id;

        $this->getToken($companyID);
        $endpoint = "payout/account/lookup";

        logInfo($body, "GT Request Squado verify bank body");
        try {
            $verifyAccount = $this->gtbPostRequest($endpoint, $body);

            logInfo($verifyAccount, "GTB Response verifyAccount");

            if (isset($verifyAccount['status'])) {
                if ($verifyAccount['status'] == 200) {
                    return ['status' => true, 'message' => $verifyAccount['message'], 'data' => $verifyAccount['data']];
                } else {
                    return ['status' => false, 'message' => $verifyAccount['message'], 'data' => $verifyAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $verifyAccount];
            }
        } catch (\Exception $e) {
            logInfo($e->getMessage(), "GTB Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function bankTransfer($amount, $transactionReference, $description, $bankCode, $accountNumber, $companyID)
    {

        try {

            $loginResult = $this->getToken($companyID);

            $body = [
                "amount"                   => floatval($amount),
                "nameEnquiryReference"     => $sessionId,
                "debitAccountNumber"       => $this->sendingAccount,
                "narration"                => $description,
                "beneficiaryBankCode"      => $bankCode,
                "beneficiaryAccountNumber" => $accountNumber,
                "saveBeneficiary"          => false,
                "paymentReference"         => $transactionReference,
            ];

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }

            logInfo($body, "Safe haven transfer request body");

            $response = $this->safehavenPostRequest('transfers', $body);

            $result = $response;

            logInfo($result, "Safehaven transfer response");

            if (isset($result["responseCode"])) {
                if ($result["responseCode"] == "00") {
                    return [
                        "status"  => true,
                        'message' => "Bank Transfer Successful",
                        'data'    => [
                            'session_id' => $sessionId,
                        ],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to complete transfer'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }
        } catch (Exception $e) {

            logInfo($e->getMessage(), "Safehaven Transfer serror log");

            return ['status' => false, 'message' => 'Server Error...Please contact support to resolve'];
        }
    }

    public function getSenderName($senderString)
    {
        $firstPart = explode("|", $senderString)[0];
        return substr($firstPart, 14);
    }

    public function gtbPostRequest($endpoint, $body)
    {

        \logInfo("$this->baseUrlSquadoTest/$endpoint", "Endpoint log");
        logInfo($this->gtbHeader(), "gtb header");
        logInfo($body, "gtb body");

        $response = Http::withHeaders($this->gtbHeader())->post("$this->baseUrlSquadoTest/$endpoint", $body);
        return $response->json();
    }

    public function gtbGetRequest($endpoint)
    {
        logInfo("$this->baseUrlSquado/$endpoint", "gtb route");
        logInfo($this->gtbHeader(), "gtb header");

        $response = Http::withHeaders($this->gtbHeader())->get("$this->baseUrlSquado/$endpoint");
        return $response->json();
    }

    public function gtbHeader()
    {

        logInfo(env('APP_MODE'), "API key");
        $header = [];
        $header = [
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer $this->secretKeySquado",
        ];

        return $header;
    }
}
