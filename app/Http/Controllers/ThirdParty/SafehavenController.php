<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\UserCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $this->fundingSourceId = env('APP_MODE') == 'test' ? "67d40044ad1be81c57ff0684" : "6920ef3416f888f19e03e7f6";
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

    public function sudoPutRequest($endpoint)
    {
        $response = Http::withHeaders($this->sudoHeader())->put("$endpoint");
        return $response->json();
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

        if ($customer_sudo_account_id == null) {
            // check if user has a customer base with Sudo
            $customer_sudo_account_id = $this->getCustomerSudoID($customer);

            if ($customer_sudo_account_id == null) {
                $customer = $this->createSudoCustomer($request, $customer);
            } else {
                $customer = $this->getSudoCardCustomerInfo($customer_sudo_account_id);
            }

        } else {
            $customer = $this->getSudoCardCustomerInfo($customer_sudo_account_id);
        }

        logInfo("$customer_sudo_account_id", "customer_sudo_account_id");
        logInfo($customer['data']['_id'], "customer");

        $body = [

            "customerId"      => $customer_sudo_account_id ?? $customer['data']['_id'],
            "fundingSourceId" => $this->fundingSourceId,
            "type"            => "physical",
            "currency"        => "NGN",
            "status"          => "active",
            "number"          => $request->card_number,
            "status"          => "active",
            "debitAccountId"  => $this->debitAccountId,
            "brand"           => $request->card_type,
            "amount"          => 123,

        ];

        logInfo(json_encode($body), "Sudo Customer Register Physical Card request body");

        $endpoint = "$this->baseSudoUrl/cards";

        try {
            $result = $this->sudoPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Register Physical Card Result");

            if (isset($result['statusCode'])) {
                if ($result['statusCode'] == 200) {
                    return ['status' => true, 'message' => $result['message'], 'result' => $result];
                } else {
                    return ['status' => false, 'message' => $result['message']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Register Physical Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function createVirtualCard($request, $customer, $customer_sudo_account_id)
    {

        if ($customer_sudo_account_id == null) {
            // check if user has a customer base with Sudo
            $customer_sudo_account_id = $this->getCustomerSudoID($customer);

            if ($customer_sudo_account_id == null) {
                $customer = $this->createSudoCustomer($request, $customer);
            } else {
                $customer = $this->getSudoCardCustomerInfo($customer_sudo_account_id);
            }

        } else {
            $customer = $this->getSudoCardCustomerInfo($customer_sudo_account_id);
        }

        $body = [
            "customerId"      => $customer_sudo_account_id ?? $customer['data']['_id'],
            "fundingSourceId" => $this->fundingSourceId,
            "type"            => "virtual",
            "currency"        => "NGN",
            "status"          => "active",
            "debitAccountId"  => $this->debitAccountId,
            "brand"           => "Verve",
            "amount"          => 123,

        ];

        logInfo($body, "Sudo Customer Register Virtual Card body");

        $endpoint = "$this->baseSudoUrl/cards";

        try {
            $result = $this->sudoPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Register Virtual Card Result");

            if (isset($result['statusCode'])) {
                if ($result['statusCode'] == 200) {
                    return ['status' => true, 'message' => $result['message'], 'result' => $result];
                } else {
                    return ['status' => false, 'message' => $result['message']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create card... Please contact support"];
            }
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
                "line1"      => $address ?? "",
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

    public function getPhysicalCardDetials($request, $transactionId)
    {
        $body = [
            "appId"           => $this->appId,
            "appKey"          => $this->appKey,
            "currencyCode"    => "566",
            "MeansOfIdNumber" => "National ID",
            "emailAddress"    => $request->email,
            "firstName"       => $request->first_name,
            "lastName"        => $request->last_name,
            "mobileNr"        => $request->phone,
            "streetAddress"   => $request->address,
            "bvn"             => $request->bvn,
            "PaymentRef"      => $transactionId,
        ];

        logInfo($body, "Providus request body");

        try {
            $cardResult = $this->providusPostRequest('PrepaidCardNewCardRequest', $body);

            logInfo($cardResult, "Providus Result");

            if (isset($cardResult['StatusCode'])) {
                if ($cardResult['StatusCode'] == "00") {
                    return ['status' => true, 'message' => $cardResult['JsonData']];
                } else {
                    return ['status' => false, 'message' => $cardResult['JsonData']];
                }
            } else {
                return ['status' => false, 'message' => "Failed to create card... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Providus Error");

            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function getCustomerSudoID($user)
    {
        $existingCard = UserCard::where('user_id', $user->id)->first();
        if ($existingCard != null) {
            $cardReference = $existingCard->card_reference;
            $customer      = $this->getSudoCardCustomerInfo($cardReference);
            if (isset($customer['data']['customer']['_id'])) {
                return $customer['data']['customer']['_id'];
            } else {
                return null;
            }
        } else {
            return null;
        }

    }

    public function getSudoCardCustomerInfo($cardReference)
    {

        $endpoint = "$this->baseSudoUrl/cards/$cardReference";

        try {
            $result = $this->sudoGetRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Get Card Result");

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

            logInfo(json_encode($result), "Sudo Customer Get Card Result");

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
}
