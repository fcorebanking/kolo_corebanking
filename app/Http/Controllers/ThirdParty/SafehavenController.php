<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\UserCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SafehavenController extends WebhookController
{

    private $grantType;
    private $clientAssertionType;
    private $clientId;
    private $ibsClientId;
    private $sessionId;
    private $clientAssertion;
    private $baseUrl;
    private $token;
    private $sendingAccount;
    private $debitAccount;
    private $baseSudoUrl;
    private $baseSudoKey;
    private $fundingSourceId;

    public function __construct()
    {
        $this->token               = "";
        $this->ibsClientId         = "";
        $this->sessionId           = "";
        $this->sendingAccount      = "0117187948";
        $this->grantType           = env('APP_MODE') == 'test' ? config('safehaven.grant_type_test') : config('safehaven.grant_type_live');
        $this->clientAssertionType = env('APP_MODE') == 'test' ? config('safehaven.client_assertion_type_test') : config('safehaven.client_assertion_type_live');
        $this->clientId            = env('APP_MODE') == 'test' ? config('safehaven.client_id_test') : config('safehaven.client_id_live');
        $this->clientAssertion     = env('APP_MODE') == 'test' ? config('safehaven.client_assertion_test') : config('safehaven.client_assertion_live');
        $this->baseUrl             = env('APP_MODE') == 'test' ? config('safehaven.base_url_test') : config('safehaven.base_url_live');
        $this->debitAccount        = "0117187948" ?? "0115361250";
        $this->baseSudoUrl         = env('APP_MODE') == 'test' ? config('safehaven.base_url_sudo_test') : config('safehaven.base_url_sudo_live');
        $this->baseSudoKey         = env('APP_MODE') == 'test' ? config('safehaven.base_url_sudo_test_key') : config('safehaven.base_url_sudo_live_key');
        // $this->baseSudoUrl     = config('safehaven.base_url_sudo_test');
        //  $this->baseSudoKey     = config('safehaven.base_url_sudo_test_key');

        $this->fundingSourceId = env('APP_MODE') == 'test' ? "67d40044ad1be81c57ff0684" : "69206fa316f888f19e0025ff"; //69206fa316f888f19e0025ff
        $this->debitAccountId  = env('APP_MODE') == 'test' ? "67d9ba1c39198caf19512516" : "67dac34b5eb41985c50d402c";

    }

    public function getToken()
    {

        $body = [
            'grant_type'            => $this->grantType,
            'client_assertion_type' => $this->clientAssertionType,
            'client_id'             => $this->clientId,
            'client_assertion'      => $this->clientAssertion,
        ];

        logInfo($body, "Exchange cliient assertion");

        try {
            $response = $this->safehavenPostRequest("oauth2/token", $body);

            if (isset($response["access_token"])) {
                $this->token = $response["access_token"];

                logInfo($this->token, "Safe Haven Access Token");

                $this->ibsClientId = $response["ibs_client_id"];
                return ['status' => true, 'message' => 'Token obtained successfully', 'data' => ['token' => $this->token]];
            } else {
                return ['status' => false, 'message' => 'Failed to login to safehaven'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Safehaven Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function createVirtualAccount($firstName, $lastName, $email, $phone, $customerReference, $bvn, $otp, $bvnId)
    {

        $body = [
            "autoSweepDetails"  => [
                "schedule"      => "Instant",
                "accountNumber" => $this->debitAccount,
            ],
            "autoSweep"         => "true",
            "phoneNumber"       => $phone,
            "emailAddress"      => $email,
            "externalReference" => $customerReference,
            "identityId"        => $bvnId,
            "identityNumber"    => $bvn,
            "identityType"      => "BVN",
            "otp"               => $otp,
            "callbackUrl"       => "https://core.finova.ng/api/v1/transactions/safehaven-webhook",

        ];

        logInfo($body, "Safe Have request");

        $endpoint = "accounts/v2/subaccount";

        try {

            $loginResult = $this->getToken();

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }

            $createAccount = $this->safehavenPostRequest($endpoint, $body);

            logInfo($createAccount, "Exchange cliient assertion");

            if (isset($createAccount['statusCode'])) {
                if ($createAccount['statusCode'] == "200") {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function createVirtualAccountV2($firstName, $lastName, $email, $phone, $customerReference, $otp)
    {

        $body = [
            "metadata"          => [],
            "autoSweepDetails"  => [
                "schedule"      => "Instant",
                "accountNumber" => $this->debitAccount,
            ],
            "autoSweep"         => true,
            "firstName"         => $firstName,
            "lastName"          => $lastName,
            "phoneNumber"       => $phone,
            "identityType"      => "BVN",
            "autoSweep"         => false,
            "emailAddress"      => $email,
            "otp"               => $otp,
            "externalReference" => $customerReference,
            "bvn"               => "222222222",
        ];

        $endpoint = "accounts/subaccount";

        try {

            $loginResult = $this->getToken();

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Failed to complete transfer'];
            }

            $createAccount = $this->safehavenPostRequest($endpoint, $body);

            logInfo($createAccount, "Exchange cliient assertion");

            if (isset($createAccount['statusCode'])) {
                if ($createAccount['statusCode'] == "200") {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function bankTransfer($amount, $transactionReference, $description, $bankCode, $accountNumber, $sessionId)
    {
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

        try {

            $loginResult = $this->getToken();

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

    public function getTransactionStatus($reference)
    {
        try {

            $reference = urlencode($reference);

            $loginSafehaven = $this->getToken();

            if (! $loginSafehaven['status']) {
                $loginSafehaven['status'] = "01";
                return $loginSafehaven;
            }

            $body = [
                "paymentReference" => $reference,
            ];

            $result = $this->safehavenPostRequest("transfers/tqs", $body);

            logInfo($result, "Safehaven Response");

            if (isset($result["statusCode"])) {
                if ($result["statusCode"] == 200) {
                    if ($result["responseCode"] == "00") {
                        return ['status' => "00", 'message' => 'Transaction verified successfully'];
                    } else if ($result["responseCode"] == "97" || $result["responseCode"] == "91" || $result["responseCode"] == "61") {
                        return ['status' => "02", 'message' => 'Transaction failed and has been reversed'];
                    } else {
                        return ['status' => "01", 'message' => $result['message']];
                    }
                } else {
                    return ['status' => "01", 'message' => 'Failed to Verify Transaction'];
                }
            } else {
                return ['status' => "03", 'message' => 'Failed to Verify Transaction'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Safehaven Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function verifyBVN($bvn)
    {
        $body = [
            "type"               => "BVN",
            "async"              => false,
            "number"             => $bvn,
            "debitAccountNumber" => $this->debitAccount,
        ];

        $url = "identity/v2";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to connect to Server. Please contact support'];
        }

        try {
            $response = $this->safehavenPostRequest($url, $body);

            logInfo($response, "Safehaven response");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == 200) {

                    return [
                        'status'  => true,
                        'message' => "BVN Verified Successfully",
                        'data'    => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to Verify BVN'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Verify BVN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error verifying account'];
        }
    }

    public function verifyNIN($nin)
    {
        $body = [
            "type"               => "NIN",
            "async"              => false,
            "number"             => $nin,
            "debitAccountNumber" => $this->debitAccount,
        ];

        $url = "identity/v2";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to connect to Server. Please contact support'];
        }

        try {
            $response = $this->safehavenPostRequest($url, $body);

            logInfo($response, "Safehaven response");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == 200) {

                    return [
                        'status'  => true,
                        'message' => "NIN Verified Successfully",
                        'data'    => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to Verify NIN'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Verify NIN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error verifying NIN account'];
        }
    }

    public function verifyCAC($cac)
    {
        logInfo($cac, "CAC");
        logInfo($this->debitAccount, "DEBIT Account");

        $body = [
            "type"               => "CAC-SEARCH",
            "async"              => false,
            "number"             => $cac,
            "debitAccountNumber" => $this->debitAccount,
        ];

        $url = "identity/v2";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to connect to Server. Please contact support'];
        }

        try {
            $response = $this->safehavenPostRequest($url, $body);

            logInfo($response, "Safehaven response CAC");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == 200) {

                    return [
                        'status'  => true,
                        'message' => "CAC Verified Successfully",
                        'data'    => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to Verify CAC'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Verify CAC'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error verifying CAC account'];
        }
    }

    public function creditCheck($cac)
    {
        $body = [
            "type"               => "CREDITCHECK",
            "async"              => false,
            "number"             => $cac,
            "provider"           => "firstCentral",
            "debitAccountNumber" => $this->debitAccount,
        ];

        $url = "identity/v2";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to connect to Server. Please contact support'];
        }

        try {
            $response = $this->safehavenPostRequest($url, $body);

            logInfo($response, "Safehaven response");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == 200) {

                    return [
                        'status'  => true,
                        'message' => "CAC Verified Successfully",
                        'data'    => $response['data'],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to Verify CAC'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Verify CAC'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error verifying CAC account'];
        }
    }

    public function verifyAccount($bankCode, $accountNumber)
    {
        $body = [
            "bankCode"      => $bankCode,
            "accountNumber" => $accountNumber,
        ];

        $url = "transfers/name-enquiry";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to complete transfer'];
        }

        try {
            $exhangeRate = $this->safehavenPostRequest($url, $body);

            logInfo($exhangeRate, "Safehaven response");

            if (isset($exhangeRate['responseCode'])) {
                if ($exhangeRate['responseCode'] == '00') {

                    $this->sessionId = $exhangeRate["data"]["sessionId"];

                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name' => $exhangeRate["data"]["accountName"],
                            'bank_code'    => $exhangeRate["data"]["bankCode"],
                            'session_id'   => $exhangeRate["data"]["sessionId"],
                        ],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to validate bank account'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to validate bank account'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error validating bank account'];
        }
    }

    public function verifyAccountFull($bankCode, $accountNumber)
    {
        $body = [
            "bankCode"      => $bankCode,
            "accountNumber" => $accountNumber,
        ];

        $url = "transfers/name-enquiry";

        $loginResult = $this->getToken();

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to complete transfer'];
        }

        try {
            $exhangeRate = $this->safehavenPostRequest($url, $body);

            logInfo($exhangeRate, "Safehaven response");

            if (isset($exhangeRate['responseCode'])) {
                if ($exhangeRate['responseCode'] == '00') {

                    $this->sessionId = $exhangeRate["data"]["sessionId"];

                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name' => $exhangeRate["data"]["accountName"],
                            'bank_code'    => $exhangeRate["data"]["bankCode"],
                            'bvn'          => $exhangeRate["data"]["bvn"],
                            'session_id'   => $exhangeRate["data"]["sessionId"],
                        ],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to validate bank account'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to validate bank account'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");

            return ['status' => false, 'message' => 'Error validating bank account'];
        }
    }

    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url  = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->flutterWavePostRequest($url, $body);

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
            logInfo($e->getMessage(), "Flutterwave Error");

            return ['status' => false, 'message' => 'Failed to get Exchange rates'];
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {

        $stringToEncrypt = config('safehaven.secret_key_live') . "|$request->paymentReference|$request->amountPaid|$request->paidOn|$request->transactionReference";

        $hashed = hash("sha512", $stringToEncrypt);

        if (strtolower($request->transactionHash) != $hashed) {
            return ['status' => false, 'message' => "Failed to verify Transaction Hash"];
        }

        return ['status' => true, 'message' => 'Webhook Verify Success'];
    }

    public function safehavenPostRequest($endpoint, $body)
    {
        logInfo("{$this->baseUrl}{$endpoint}", "Complete url");
        logInfo($this->safehavenHeader(), "Safe haven header");
        logInfo(json_encode($body), "Safe haven Body Request");
        $response = Http::withHeaders($this->safehavenHeader())->post("{$this->baseUrl}{$endpoint}", $body);
        return $response->json();
    }

    public function safehavenGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->safehavenHeader())->get("{$this->baseUrl}{$endpoint}");
        return $response->json();
    }

    public function safehavenHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
            'ClientID'      => $this->ibsClientId,
        ];

        return $header;
    }
    public function sudoPostRequest($endpoint, $body)
    {
        logInfo("$endpoint", "Complete Sudo url");
        logInfo($this->sudoHeader(), "Sudo header");
        $response = Http::withHeaders($this->sudoHeader())->post("$endpoint", $body);
        return $response->json();
    }

    public function sudoGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->sudoHeader())->get("$endpoint");
        return $response->json();
    }

    public function sudoPutRequest($endpoint, array $body = [])
    {
        try {
            if (! empty($body)) {
                // PUT with body
                $response = Http::withHeaders($this->sudoHeader())
                    ->put($endpoint, $body);
            } else {
                // PUT without body
                $response = Http::withHeaders($this->sudoHeader())
                    ->put($endpoint);
            }

            return $response->json();

        } catch (\Exception $e) {
            logInfo($e->getMessage(), "Sudo PUT Request Error");
            return [
                'status'  => false,
                'message' => 'PUT Request Failed',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function sudoPatchRequest($endpoint, array $body = [])
    {
        try {
            if (! empty($body)) {
                // PUT with body
                $response = Http::withHeaders($this->sudoHeader())
                    ->patch($endpoint, $body);
            } else {
                // PUT without body
                $response = Http::withHeaders($this->sudoHeader())
                    ->patch($endpoint);
            }

            return $response->json();

        } catch (\Exception $e) {
            logInfo($e->getMessage(), "Sudo PUT Request Error");
            return [
                'status'  => false,
                'message' => 'PUT Request Failed',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function sudoHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->baseSudoKey,

        ];

        return $header;
    }

    public function createPhysicalCard($request, $transactionId, $customer, $customer_sudo_account_id)
    {
        // logInfo(json_encode($customer), "Customer");
        /**
         * 1. Resolve or create Sudo Customer
         */
        $customer_sudo_account_id = $customer_sudo_account_id ?? $this->getCustomerSudoID($customer);

        logInfo($customer_sudo_account_id, "getCustomerSudoID");

        if (is_null($customer_sudo_account_id)) {
            // Create new Sudo Customer
            $sudoCustomer = $this->createSudoCustomer($request, $customer);
            logInfo(json_encode($sudoCustomer), "SudoCustomer");
            // Ensure creation returned a valid structure
            if (! isset($sudoCustomer['data']['_id'])) {
                return ['status' => false, 'message' => "Failed to create Sudo customer"];
            }

            $user_id = $customer->id ?? $customer->accounts->first()->user_id;

            logInfo(json_encode($user_id), "user_id");

            try {
                $usercardsudo = UserCard::create([
                    "user_id"          => $user_id,
                    "card_name"        => "Pending",
                    "card_type_id"     => 2,
                    "card_category_id" => 2,
                    "card_number"      => "0000000000000000",
                    "masked_pan"       => "0000********0000",
                    "card_reference"   => "TEMP",
                    "currency_id"      => 1,
                    "exp_date"         => "00/0000",
                    "cvv"              => "000",
                    "created_by"       => $user_id,
                    "sudo_customer_id" => $sudoCustomer['data']['_id'],
                ]);
            } catch (\Exception $e) {
                dd($e->getMessage());
            }

            logInfo(json_encode($usercardsudo), "usercardsudo");

            DB::commit();

            $customer_sudo_account_id = $sudoCustomer['data']['_id'];
            logInfo($customer_sudo_account_id, "createSudoCustomer");
        }

        logInfo($customer_sudo_account_id, "customer_sudo_account_id");

        /**
         * 2. Build request body
         */
        $body = [
            "customerId"      => $customer_sudo_account_id,
            "fundingSourceId" => $this->fundingSourceId,
            "type"            => "physical",
            "currency"        => "NGN",
            "status"          => "active",
            "number"          => $request->card_number,
            "debitAccountId"  => $this->debitAccountId,
            "brand"           => $request->card_type,
            "amount"          => 123,
        ];

        logInfo(json_encode($body), "Sudo Customer Register Physical Card request body");

        $endpoint = "{$this->baseSudoUrl}/cards";

        /**
         * 3. Execute request
         */
        try {
            $result = $this->sudoPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Register Physical Card Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return [
                    'status'                   => true,
                    'message'                  => $result['message'],
                    'result'                   => $result,
                    'customer_sudo_account_id' => $customer_sudo_account_id,
                ];
            }

            return ['status' => false, 'message' => $result['message'] ?? "Failed to create card"];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Register Physical Error");
            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function createVirtualCard($request, $customer, $customer_sudo_account_id)
    {
        /**
         * 1. Resolve or create Sudo Customer
         */
        $customer_sudo_account_id = $customer_sudo_account_id ?? $this->getCustomerSudoID($customer);

        if (is_null($customer_sudo_account_id)) {
            // No Sudo ID → create new Sudo customer now
            $sudoCustomer = $this->createSudoCustomer($request, $customer);

            // Ensure valid structure returned
            if (! isset($sudoCustomer['data']['_id'])) {
                return ['status' => false, 'message' => "Failed to create Sudo customer"];
            }

            $customer_sudo_account_id = $sudoCustomer['data']['_id'];
        }

        /**
         * 2. Build request body
         */
        $body = [
            "customerId"      => $customer_sudo_account_id,
            "fundingSourceId" => $this->fundingSourceId,
            "type"            => "virtual",
            "currency"        => "NGN",
            "status"          => "active",
            "debitAccountId"  => $this->debitAccountId,
            "brand"           => "Verve",
            "amount"          => 123,
        ];

        logInfo(json_encode($body), "Sudo Customer Register Virtual Card body");

        $endpoint = "{$this->baseSudoUrl}/cards";

        /**
         * 3. Execute request
         */
        try {
            $result = $this->sudoPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Register Virtual Card Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return [
                    'status'      => true,
                    'message'     => $result['message'],
                    'result'      => $result,
                    "customer_id" => $sudoCustomer['data']['_id'],
                ];
            }

            return ['status' => false, 'message' => $result['message'] ?? "Failed to create card"];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Register Virtual Card Error");
            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function createSudoCustomer($request, $customer)
    {
        $firstName = $request->first_name ?? $customer->first_name;
        $lastName  = $request->last_name ?? $customer->last_name;
        $email     = $request->email ?? $customer->email;
        $phone     = $request->phone ?? $customer->phone;
        $address   = $request->address ?? $customer->address;
        $address40 = str_pad(Str::limit($address, 40, ''), 40, ' ');

        $body = [
            "type"         => "individual",
            "emailAddress" => $email,
            "name"         => "{$firstName} {$lastName}",
            "status" => "active",
            "phoneNumber" => $phone,
            "individual" => [
                "firstName" => $firstName,
                "lastName"  => $lastName,
                "dob"       => $customer->dob,

            ],
            "billingAddress" => [
                "line1"      => $address40 ?? "",
                "line2"      => "",
                "city"       => $customer->state_of_origin ?? "LA",
                "state"      => $customer->state_of_origin ?? "Lagos",
                "postalCode" => "23401",
                "country"    => "Nigeria",

            ],

        ];

        logInfo($body, "Sudo Customer Register request body");

        $endpoint = "$this->baseSudoUrl/customers";

        try {
            $result = $this->sudoPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Register Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to create customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Register Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getSudoCustomer($customer)
    {

        $endpoint = "$this->baseSudoUrl/customers/$customer";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getCardToken($cardReference)
    {

        $endpoint = "$this->baseSudoUrl/cards/$cardReference/token";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Token");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create card ... Please contact support"];
        }
    }

    public function getPhysicalCardDetials($cardReference)
    {
        $endpoint = "$this->baseSudoUrl/cards/$cardReference?reveal=true";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Card Details with Pin");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create card ... Please contact support"];
        }
    }

    public function getCustomerSudoID($user)
    {
        $existingCard = UserCard::where('user_id', $user->id)->first();
        if ($existingCard != null) {
            return $existingCard->sudo_customer_id;

        } else {
            return null;
        }

    }

    public function getSudoCardCustomerInfo($cardReference)
    {

        $endpoint = "$this->baseSudoUrl/cards/$cardReference";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Card Customer info");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getSudoCustomerInfo($customerID)
    {

        $endpoint = "$this->baseSudoUrl/customers/$customerID";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Customer info");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function enrollcard2fa($cardReference)
    {
        $endpoint = "$this->baseSudoUrl/cards/$cardReference/enroll2fa";

        try {
            $result = $this->sudoPutRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Put Card Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function changeUserCardPin(Request $request)
    {
        $cardReference = $request->card_reference;
        /*  $endpoint      = "$this->baseSudoUrl/cards/$cardReference/pin";

        $body = [
            "oldPin" => "{$request->old_pin}",
            "newPin" => "{$request->new_pin}",

        ];*/
        $endpoint = "$this->baseSudoUrl/cards/$cardReference/reset-pin";

        $body = [

            "newPin" => "{$request->new_pin}",

        ];

        try {
            $result = $this->sudoPatchRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Get Card Change Pin Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Card pin change failed... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Card pin change failed ... Please contact support"];
        }
    }

    public function getSudoUserCardBalance($user_wallet)
    {

        logInfo($user_wallet->balance, "Sudo Virtual Account Amount");

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            "data"         => [
                "balance" => $user_wallet->balance,
            ],
            'httpCode'     => true,
        ];

    }

    public function getSudoUserTransaction($request, $user_wallet, $companyId, $branchId, $user, $transactionReference)
    {

        $amount      = $request->input('data.object.amount');
        $description = $request->input('data.object.transactionMetadata.type');
        $oldBalance  = $user_wallet->balance;
        logInfo($oldBalance, "Old Wallet Balance");

        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        $debitCustomer = $this->debitCustomerWithChargeSudo($formatedAmount, 0, $user, $user_wallet);
        logInfo(json_encode($debitCustomer), "debitCustomer Sudo");

        $debitCompany = $this->debitCompanyCardWallet($companyId, $formatedAmount);
        logInfo(json_encode($debitCompany), "debitCompany Card Wallet");

        $customerAccountId = $user->id;

        $this->debitSudoTransaction($debitCustomer, $debitCompany, $formatedAmount, $customerAccountId, $companyId, $description, $branchId, $user, $transactionReference);

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            'httpCode'     => true,
        ];

    }

    public function getSudoUserAuthorization($request, $user_wallet, $companyId, $branchId, $user)
    {

        $amount      = $request->input('data.object.amount');
        $description = $request->input('data.object.transactionMetadata.type');
        $oldBalance  = $user_wallet->balance;
        logInfo($oldBalance, "Old Wallet Balance");

        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        if ($formatedAmount < $oldBalance) {
            logInfo("amount working", "formated Amount if less than");
            logInfo("ready to send response", "response");

            $checkCompanyBalance = $this->checkCompanyBalance($user_wallet, $amount, $companyId, $user);
            logInfo(json_encode($checkCompanyBalance), "checkCompanyBalance Sudo");

            if ($checkCompanyBalance['status']) {

                return [
                    'statusCode'   => 200,
                    'responseCode' => "00",
                    'httpCode'     => true,
                ];

            } else {
                return [
                    'statusCode'   => 403,
                    'responseCode' => "58",
                    "message"      => $checkCompanyBalance['message'],
                    'httpCode'     => false,
                ];

            }

        } else {
            return [
                'statusCode'   => 403,
                'responseCode' => "51",
                "message"      => "Insufficient fund",
                'httpCode'     => false,
            ];

        }
    }

    public function getSudoUserRefund($request, $user_wallet, $companyId, $branchId)
    {
        $amount = $request->input('data.object.amount');
        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        $oldBalance = $user_wallet->balance;
        logInfo($user_wallet->balance, "Old Wallet Balance");

        $customerAccountId = $user_wallet->wb_account_detail_id;
        $creditCustomer    = $this->creditCustomerSudo($customerAccountId, $formatedAmount, $charge = 0.0, $user_wallet);
        $creditCompany     = $this->creditCompanyCardWallet($companyId, $formatedAmount);

        if ($creditCustomer['status']) {
            $newdescription = "Card Refund";
            $this->refundSudoTransaction($creditCustomer, $creditCompany, $formatedAmount, $companyId, $newdescription, $branchId, $customerAccountId, $user);
            logInfo($creditCompany['balance_before'], "New Wallet Balance");
        }

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            'httpCode'     => true,
        ];
    }

    public function getUserCard($accountReference)
    {
        return UserCard::where('card_reference', $accountReference)->first()->user_id;

    }

    private function refundSudoTransaction($creditCustomer, $creditCompany, $amount, $companyId, $newdescription, $branchId, $customerAccountId, $user)
    {
        $transaction = transactions::create([
            "balance_before"         => $creditCustomer['balance_before'],
            "amount"                 => $amount,
            "company_amount"         => $amount,
            "balance_after"          => $creditCustomer['balance_after'],
            'company_balance_before' => $creditCompany['balance_before'],
            'company_balance_after'  => $creditCompany['balance_after'],
            "description"            => $newdescription,
            "company_id"             => $companyId,
            "branch_id"              => $branchId,
            "account_id"             => $customerAccountId,
            "transaction_reference"  => $this->transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 23,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
        ]);
    }

    private function debitSudoTransaction($debitCustomer, $debitCompany, $amount, $customerAccountId, $companyId, $description, $branchId, $user, $transactionReference)
    {
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $companyId);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        logInfo("transaction created working", "debitSudoTransaction");
        // Save Charges to transaction table

        $transaction = transactions::create([

            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $customerAccountId,
            "company_id"             => $companyId,
            "branch_id"              => $branchId,
            "company_balance_before" => $debitCompany["balance_before"],
            "company_amount"         => $debitCompany["amount"],
            "company_balance_after"  => $debitCompany["balance_after"],
            "description"            => "Card {$description} {$amount}",
            "transaction_reference"  => $transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 23,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,

        ]);
    }

    private function checkCompanyBalance($user_wallet, $amount, $companyId, $user)
    {

        $response = [];

        $companyWallet = company_wallet::where('company_id', $companyId)->first();

        if ($companyWallet->card_balance < 300001) {
            $response = ["status" => false, 'message' => "Below Threshold of 300000"];
        } else {
            if ($companyWallet->card_balance >= $amount) {

                $response = [
                    "status"          => true,
                    'message'         => "Amount is Valid",
                    'account_id'      => $user->accounts->first()->id,
                    'balance'         => $user_wallet->balance,
                    'company_balance' => $user->company->balance,
                ];
            } else {
                $response = ["status" => false, 'message' => "Switcher Error... Please contact support $companyWallet->balance $amount"];
            }
        }

        return $response;
    }

    private function debitCustomerWithChargeSudo($originalAmount, $charge, $user, $userWallet)
    {
        // Validate amount
        if ($originalAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        $initialBalance = $userWallet->balance;
        $debitAmount    = $originalAmount + $charge;

        // Minimum balance allowed to remain in the wallet
        $minBalance = $user->company->setting->min_withdraw_charge;

        // Check sufficient funds
        if (($initialBalance - $minBalance) <= $debitAmount) {
            return [
                "status"  => false,
                "message" => "Not Sufficient funds",
            ];
        }

        // Debit the wallet
        $newBalance          = $initialBalance - $debitAmount;
        $userWallet->balance = $newBalance;
        $userWallet->save();

        return [
            "status"         => true,
            "amount"         => $debitAmount,
            "message"        => "Amount is valid",
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }

    private function debitCompanyCardWallet($companyId, $formattedAmount)
    {
        $response = [];

        // Ensure wallet exists
        $companyWallet = company_wallet::where('company_id', $companyId)->first();
        if (! $companyWallet) {
            return [
                "status"  => false,
                "message" => "Company wallet not found",
            ];
        }

        $initialBalance = $companyWallet->card_balance;

        logInfo("$formattedAmount", "formattedAmount debitCompanyCardWallet");

        logInfo("$initialBalance", "Initial Card wallet balance for company {$companyId}");

        // Validate transaction
        if ($formattedAmount > $initialBalance) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        // Update new balance
        $companyWallet->card_balance = $initialBalance - $formattedAmount;
        $companyWallet->save();

        logInfo("{$companyWallet->card_balance}", "New Card wallet balance for company {$companyId}");

        return [
            "status"         => true,
            "amount"         => $formattedAmount,
            "message"        => "Amount is valid",
            "balance_before" => $initialBalance,
            "balance_after"  => $companyWallet->card_balance,
        ];
    }

    private function creditCustomerSudo($customerAccountId, $originalAmount, $charge = 0.0, $userWallet)
    {
        // Validate credit amount
        if ($originalAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        // Do not allow negative credit after charge deduction
        if ($originalAmount < $charge) {
            return [
                "status"  => false,
                "message" => "Charge cannot be greater than the amount",
            ];
        }

        $initialBalance = $userWallet->balance;
        $creditAmount   = $originalAmount - $charge;

        // Apply credit
        $newBalance          = $initialBalance + $creditAmount;
        $userWallet->balance = $newBalance;
        $userWallet->save();

        return [
            "status"         => true,
            "message"        => "Amount credited successfully",
            "amount"         => $creditAmount,
            "account_id"     => $customerAccountId,
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }

    private function creditCompanyCardWallet($companyId, $formattedAmount)
    {
        // Check for valid amount
        if ($formattedAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid credit amount",
            ];
        }

        // Fetch company wallet
        $companyWallet = company_wallet::where('company_id', $companyId)->first();

        if (! $companyWallet) {
            return [
                "status"  => false,
                "message" => "Company wallet not found",
            ];
        }

        $initialBalance = $companyWallet->card_balance;

        logInfo($initialBalance, "Initial Card Wallet Balance for {$companyWallet->company_id}");

        // Perform credit
        $newBalance                  = $initialBalance + $formattedAmount;
        $companyWallet->card_balance = $newBalance;
        $companyWallet->save();

        logInfo($newBalance, "New Card Wallet Balance for {$companyWallet->company_id}");

        return [
            "status"         => true,
            "message"        => "Amount credited successfully",
            "amount"         => $formattedAmount,
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }
}
