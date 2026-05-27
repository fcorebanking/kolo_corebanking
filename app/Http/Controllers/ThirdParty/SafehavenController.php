<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\UserCard;
use App\Services\SafeHavenService;
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
    private $owner;

    public function __construct()
    {

        $this->baseSudoUrl     = env('APP_MODE') == 'test' ? config('safehaven.base_url_sudo_test') : config('safehaven.base_url_sudo_live');
        $this->fundingSourceId = env('APP_MODE') == 'test' ? "67d40044ad1be81c57ff0684" : "69206fa316f888f19e0025ff"; //69206fa316f888f19e0025ff
        $this->debitAccountId  = env('APP_MODE') == 'test' ? "67d9ba1c39198caf19512516" : "67dac34b5eb41985c50d402c";
        $this->baseSudoKey     = env('APP_MODE') == 'test' ? config('safehaven.base_url_sudo_test_key') : config('safehaven.base_url_sudo_live_key');

    }

    public function getToken($company_id = null)
    {

        $safeHavenService = new SafeHavenService();

        $credentials = $safeHavenService->initCredentials($company_id);

        logInfo(json_encode($credentials), "Safe Haven credentials");

        $this->token               = "";
        $this->ibsClientId         = "";
        $this->sessionId           = "";
        $this->sendingAccount      = $credentials['sendingAccount'];
        $this->grantType           = $credentials['grant_type_live'];
        $this->clientAssertionType = $credentials['client_assertion_type_live'];
        $this->clientId            = $credentials['client_id_live'];
        $this->clientAssertion     = $credentials['client_assertion_live'];
        $this->baseUrl             = $credentials['base_url_live'];
        $this->debitAccount        = $credentials['debitAccount'];
        $this->owner               = $credentials['owner'];

        $body = [
            'grant_type'            => $this->grantType,
            'client_assertion_type' => $this->clientAssertionType,
            'client_id'             => $this->clientId,
            'client_assertion'      => $this->clientAssertion,
        ];

        logInfo($body, "Exchange cliient assertion");

        logInfo($body, "Exchange cliient assertion");

        try {
            $response = $this->safehavenPostRequest("oauth2/token", $body);

            logInfo(json_encode($response), "Safe Haven Access Token Response");

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

    public function createVirtualAccount($firstName, $lastName, $email, $phone, $customerReference, $bvn, $otp, $bvnId, $companyID, $dob, $type)
    {

        $endpoint = "accounts/v2/subaccount";

        try {

            $loginResult = $this->getToken($companyID);

            $companyID = (int) $companyID;

            $bodyOTP = [
                "autoSweepDetails"  => [
                    "schedule"      => "Instant",
                    "accountNumber" => $this->debitAccount,
                ],
                "autoSweep"         => true,
                "phoneNumber"       => $phone,
                "emailAddress"      => $email,
                "externalReference" => $customerReference,
                "identityId"        => $bvnId,
                "identityNumber"    => $bvn,
                "identityType"      => $type ?? "BVN",
                "otp"               => $otp,
                "callbackUrl"       => "https://core.finova.ng/api/v1/transactions/safehaven-webhook",

            ];

            $bodyNoOtp = [
                "autoSweepDetails"  => [
                    "schedule"      => "Instant",
                    "accountNumber" => $this->debitAccount,
                ],
                "autoSweep"         => true,
                "phoneNumber"       => $this->format_number($phone),
                "emailAddress"      => $email,
                "externalReference" => $customerReference,
                "identityId"        => $bvnId,
                "identityNumber"    => $bvn,
                "identityType"      => $type ?? "BVN",
                "dateOfBirth"       => $dob,
                "booleanMatch"      => true,
                "callbackUrl"       => "https://core.finova.ng/api/v1/transactions/safehaven-webhook",

            ];

            $body = match ($companyID) {

                92      => $bodyNoOtp,
                default => $bodyOTP,
            };

            logInfo($body, "Safe Have request");

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Safe Haven Access createVirtualAccount Response'];
            }

            $createAccount = $this->safehavenPostRequest($endpoint, $body);

            logInfo($createAccount, "safehaven Response Log");

            if (isset($createAccount['statusCode'])) {
                if ($createAccount['statusCode'] == "200") {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['data']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed toCreateAccount ...Please contactsupport", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");
            return ['status' => false, 'message' => "Failed toCreateAccount ...Please contactsupport"];
        }
    }

    public function createVirtualAccountMaintenance($firstName, $lastName, $email, $phone, $customerReference, $bvn, $otp, $bvnId, $companyID)
    {

        $endpoint = "accounts / v2 / subaccount";

        try {

            $loginResult = $this->getToken($companyID);

            $body = [
                "autoSweepDetails"  => [
                    "schedule"      => "Instant",
                    "bankCode"      => "090286",
                    "accountNumber" => "0116906698",
                ],
                "autoSweep"         => true,
                "phoneNumber"       => $phone,
                "emailAddress"      => $email,
                "externalReference" => $customerReference,
                "identityId"        => $bvnId,
                "identityNumber"    => $bvn,
                "identityType"      => "BVN",
                "otp"               => $otp,
                "callbackUrl"       => "https: //core.finova.ng/api/v1/transactions/safehaven-webhook",

            ];

            logInfo($body, "Safe Have request");

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Safe Haven Access createVirtualAccount Response'];
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
                "bankCode"      => "090286",
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

    public function updateVirtualAccount($id, $email, $phone, $customerReference, $bvn, $companyID)
    {

        $endpoint = "accounts/{$id}/subaccount";

        try {

            $loginResult = $this->getToken($companyID);

            $body = [
                "autoSweepDetails" => [
                    "schedule"      => "Instant",
                    "bankCode"      => "090286",
                    "accountNumber" => $this->debitAccount,
                ],
                "autoSweep"        => true,
                //    "phoneNumber"       => $phone,
                //  "emailAddress"      => $email,
                // "externalReference" => $customerReference,
                //  "identityNumber"    => $bvn,
                //  "identityType"      => "BVN",

            ];

            logInfo($body, "Safe Have request");

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Safe Haven Access createVirtualAccount Response'];
            }

            $updateAccount = Http::withHeaders($this->safehavenHeader())
                ->withOptions([
                    'force_ip_resolve' => 'v4',
                ])
                ->put("{$this->baseUrl}{$endpoint}", $body);

            logInfo($updateAccount, "Safe Haven Access update account");

            if (isset($updateAccount['statusCode'])) {
                if ($updateAccount['statusCode'] == "200") {
                    return ['status' => true, 'message' => $updateAccount['message'], 'data' => $updateAccount['data']];
                } else {
                    return ['status' => false, 'message' => $updateAccount['message'], 'data' => $updateAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account... Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Safehaven Error");
            return ['status' => false, 'message' => "Failed to Create Account... Please contact support"];
        }
    }

    public function bankTransfer($amount, $transactionReference, $description, $bankCode, $accountNumber, $sessionId, $companyID, $senderAccountNumber)
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

    public function getServices($companyID)
    {
        try {

            $loginSafehaven = $this->getToken($companyID);

            if (! $loginSafehaven['status']) {
                $loginSafehaven['status'] = "01";
                return $loginSafehaven;
            }

            $result = $this->safehavenGetRequest("vas/services");

            logInfo($result, "Safehaven Get Services Response");

            if (isset($result["statusCode"])) {
                if ($result["statusCode"] == 200) {
                    return ['status' => true, 'message' => 'Get Services successfully', 'data' => $result['data']];
                } else {
                    return ['status' => false, 'message' => 'Failed to Verify Transaction'];
                }
            } else {
                return ['status' => "03", 'message' => 'Failed to Verify Transaction'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Safehaven Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function getAccounts($companyID)
    {
        try {

            $loginSafehaven = $this->getToken($companyID);

            if (! $loginSafehaven['status']) {
                $loginSafehaven['status'] = "01";
                return $loginSafehaven;
            }

            $result = $this->safehavenGetRequest("accounts");

            logInfo($result, "Safehaven Get Account Response");

            if (isset($result["statusCode"])) {
                if ($result["statusCode"] == 200) {
                    return ['status' => true, 'message' => 'Get Account successfully', 'data' => $result['data']];
                } else {
                    return ['status' => false, 'message' => 'Failed to Account'];
                }
            } else {
                return ['status' => "03", 'message' => 'Failed to Account'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Safehaven Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function getService(string $action, $companyID)
    {

        $results = $this->getServices($companyID);

        logInfo($results, "Safehaven Get Services results Response");

        logInfo($action, "Safehaven Get Services map action Response");

        if ($results['status']) {
            $map = [
                'buyAirTime'        => [
                    'name'       => 'Mobile Recharge',
                    'identifier' => 'AIRTIME',
                    'log'        => 'Safehaven Get Airtime Service Response Log',
                ],
                'buyData'           => [
                    'name'       => 'DATA PURCHASE',
                    'identifier' => 'DATA',
                    'log'        => 'Safehaven Get Data Service Response Log',
                ],
                'buyElectricity'    => [
                    'name'       => 'UTILITY BILLS',
                    'identifier' => 'UTILITY',
                    'log'        => 'Safehaven Get UTILITY Service Response Log',
                ],
                'subscribeCableTV'  => [
                    'name'       => 'CABLE TV',
                    'identifier' => 'CABLETV',
                    'log'        => 'Safehaven Get CABLE TV Service Response Log',
                ],
                'verifyMeterNumber' => [
                    'name'       => 'UTILITY BILLS',
                    'identifier' => 'UTILITY',
                    'log'        => 'Safehaven Get UTILITY Service Response Log',
                ],
                'verifyCableTV'     => [
                    'name'       => 'CABLE TV',
                    'identifier' => 'CABLETV',
                    'log'        => 'Safehaven Get CABLE TV Service Response Log',
                ],
                // future actions can go here
            ];
            logInfo($map[$action], "Safehaven Get Services map action Response 2");

            if (! isset($map[$action])) {
                return null;
            }

            foreach ($results['data'] as $result) {
                logInfo($result['name'], "Safehaven Get Services map action name result");
                logInfo($map[$action]['name'], "Safehaven Get Services map action name");
                if (
                    ($result['name'] ?? null) == $map[$action]['name'] ||
                    ($result['identifier'] ?? null) == $map[$action]['identifier']
                ) {
                    logInfo($result['name'], $map[$action]['log']);
                    return $result;
                }
            }

            return null;
        }

    }

    public function getServiceCategory($result, $companyID, $provider)
    {
        logInfo($result, "Safehaven getServiceCategory");
        logInfo($provider, "Safehaven getServiceCategory Provider");

        try {

            $loginSafehaven = $this->getToken($companyID);

            if (! $loginSafehaven['status']) {
                $loginSafehaven['status'] = "01";
                return $loginSafehaven;
            }

            $serviceID = $result['_id'];

            $serviceResults = $this->safehavenGetRequest("vas/service/{$serviceID}/service-categories");

            logInfo($serviceResults, "Safehaven Get Services Category Response");

            if (isset($serviceResults["statusCode"])) {
                if ($serviceResults["statusCode"] == 200) {
                    foreach ($serviceResults['data'] as $serviceResult) {
                        if (
                            ($serviceResult['name']) == $provider ||
                            ($serviceResult['identifier']) == $provider
                        ) {
                            logInfo($serviceResult['name'], "Safehaven Reslt Provider response");

                            return ['status' => true, 'message' => 'Get Services successfully', 'data' => $serviceResult];
                        }
                    }

                } else {
                    return ['status' => false, 'message' => 'Failed to  Get Services Category'];
                }
            } else {
                return ['status' => "03", 'message' => 'Failed to  Get Services Category'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Safehaven Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function getServiceCategoryProduct($providerID, $companyID, $amount)
    {
        logInfo($providerID, "Safehaven getServiceCategory providerID");

        try {

            $loginSafehaven = $this->getToken($companyID);

            if (! $loginSafehaven['status']) {
                $loginSafehaven['status'] = "01";
                return $loginSafehaven;
            }

            $serviceResults = $this->safehavenGetRequest("vas/service-category/{$providerID}/products");

            logInfo($serviceResults, "Safehaven getServiceCategoryProduct Response");

            if (isset($serviceResults["statusCode"])) {
                if ($serviceResults["statusCode"] == 200) {
                    foreach ($serviceResults['data'] as $serviceResult) {
                        if (
                            ($serviceResult['amount']) == (int) $amount
                        ) {
                            logInfo($serviceResult['bundleCode'], "Safehaven Reslt Provider Service Category Product bundleCode response");
                            return ['status' => true, 'message' => 'Get bundleCode successfully', 'data' => $serviceResult];
                        }
                    }

                } else {
                    return ['status' => false, 'message' => 'Failed to  Get Services Category'];
                }
            } else {
                return ['status' => "03", 'message' => 'Failed to  Get Services Category'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Safehaven Login serror log");
            return ['status' => "05", 'message' => 'Server Error...'];
        }
    }

    public function buyAirtime($transactionId, $network, $amount, $phoneNumber, $companyID)
    {

        try {
            $loginResult = $this->getToken($companyID);

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Safe Haven Access buyAirtime Response'];
            }

            $body = [
                "externalReference"  => $transactionId,
                "serviceCategoryId"  => $network,
                "amount"             => floatval($amount),
                "phoneNumber"        => $phoneNumber,
                "channel"            => "WEB",
                "debitAccountNumber" => $this->debitAccount,

            ];

            logInfo($body, "Safe Haven Airtime request");

            $buyAirtime = $this->safehavenPostRequest("vas/pay/airtime", $body);

            logInfo($buyAirtime, "SafeHaven Airtime Response log");

            if ($buyAirtime["statusCode"] == 200) {
                return ["status" => true, 'message' => "Airtime Purhcase Successful"];
            } else {
                return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "SafeHaven log error");
            return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
        }
    }

    public function buyData($transactionId, $network, $dataPlain, $amount, $phoneNumber, $companyID)
    {
        try {
            $loginResult = $this->getToken($companyID);

            $serviceCartegoryID = $this->getServiceCategoryProduct($network, $companyID, $amount);
            $bundleCode         = $serviceCartegoryID['data']['bundleCode'];

            if (! $loginResult['status']) {
                return ['status' => false, 'message' => 'Safe Haven Access buyData Response'];
            }

            $body = [
                "serviceCategoryId"  => $network,
                "bundleCode"         => $bundleCode,
                "amount"             => floatval($amount),
                "phoneNumber"        => $phoneNumber,
                "externalReference"  => $transactionId,
                "channel"            => "WEB",
                "debitAccountNumber" => $this->debitAccount,
            ];

            logInfo($body, "Safe Haven Data request");

            $buyDataResponse = $this->safehavenPostRequest("vas/pay/data", $body);

            logInfo($buyDataResponse, "Safe Haven Data Response Log");

            if ($buyDataResponse["statusCode"] == 200) {
                return ["status" => true, 'message' => "Data Purchcase Successful"];
            } else {
                return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "SafeHaven Data Error");
            return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
        }
    }

    public function verifyMeterNumber($meterNumber, $electrictyDisco, $companyID)
    {
        $loginResult = $this->getToken($companyID);

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Safe Haven Access buyData Response'];
        }

        $body = [
            "serviceCategoryId" => $electrictyDisco,
            "entityNumber"      => $meterNumber,

        ];

        logInfo($body, "Safe Haven verify meter request");

        $verifyMeterResult = $this->safehavenPostRequest("vas/verify", $body);

        logInfo($verifyMeterResult, "Safe Haven Verify Meter Number Response Log");

        if (isset($verifyMeterResult["statusCode"])) {
            if ($verifyMeterResult["statusCode"] == 200) {

                return [
                    'status'  => true,
                    'message' => "Meter Verified Successfully",
                    'data'    => [
                        'meter_name'   => trim($verifyMeterResult['data']['name']),
                        'address'      => trim($verifyMeterResult['data']['address']),
                        'meter_type'   => trim($verifyMeterResult['data']['vendType']),
                        //    'meter_number' => $electrictyDisco == "abuja-electric" ? trim($verifyMeterResult['content']['Meter_Number']) : (trim($verifyMeterResult['content']['Meter_Number']) ?? trim($verifyMeterResult['content']['MeterNumber'])),

                        'meter_number' => trim($verifyMeterResult['data']['meterNo']
                        ),
                    ],
                ];
            } else {

                return [
                    'status'  => false,
                    'message' => "Failed to verify meter number",
                ];
            }
        } else {

            return [
                'status'  => false,
                'message' => "Failed to verify meter number. Please contact support",
            ];
        }
    }

    public function buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber, $companyID)
    {

        $loginResult = $this->getToken($companyID);

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Safe Haven Access buyElectricity Response'];
        }

        $body = [
            "externalReference"  => $transactionId,
            "serviceCategoryId"  => $electrictyDisco,
            "vendType"           => $meterType,
            "meterNumber"        => $meterNumber,
            "amount"             => floatval($amount),
            "channel"            => "WEB",
            "debitAccountNumber" => $this->debitAccount,
        ];

        $buyElectricity = $this->safehavenPostRequest("vas/pay/utility", $body);

        logInfo($buyElectricity, "Safe Haven Buy Electricity Response Log");

        try {

            if (isset($buyElectricity)) {
                if ($buyElectricity["statusCode"] == 200) {

                    $electricityToken = isset($buyElectricity['data']['utilityToken']);

                    $returnTypeResponse = [
                        'balance'         => "0.0",
                        'token'           => $electricityToken,
                        'purchased_units' => null,
                    ];

                    return [
                        "status"  => true,
                        'message' => "Electricity Unit Purchase Successful",
                        'data'    => $returnTypeResponse,
                    ];
                } else {
                    return ["status" => false, 'message' => $buyElectricity['response_description']];
                }
            } else {
                return ["status" => false, 'message' => "Failed to Purchase Electricity"];
            }
        } catch (Exception $e) {
            \logInfo(json_encode($e->getMessage()), "Safe Haven Buy Electricity Error Response Log");
            return ["status" => false, 'message' => "Sorry, Unable to get token. Kindly contact support"];
        }
    }

    public function verifyCableTV($smart_card_number, $cable_tv_type, $companyID)
    {
        $loginResult = $this->getToken($companyID);

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Safe Haven Access buyData Response'];
        }

        try {

            $body = [
                "serviceCategoryId" => $cable_tv_type,
                "entityNumber"      => $smart_card_number,

            ];

            $verifyCableResponse = $this->safehavenPostRequest("vas/verify", $body);

            logInfo($verifyCableResponse, "Safe Haven Verify Cable Response Log");

            if (isset($verifyCableResponse["statusCode"])) {
                if ($verifyCableResponse["statusCode"] == 200) {

                    return [
                        'status'  => true,
                        'message' => "Cable TV Verified Successfully",
                        'data'    => [
                            'customer_name'   => trim($verifyCableResponse['data']['name']),
                            'cable_tv'        => "",
                            'current_bouquet' => "",
                            //   'current_bouquet_code' => trim($verifyCableResponse['content']['Current_Bouquet_Code']) ?? "",
                            'renewal_amount'  => "",
                        ],
                    ];
                } else {

                    return [
                        'status'  => false,
                        'message' => "Failed to verify meter number",
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to verify smart card. Please contact support'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VTPASS Data Error");
            return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
        }
    }

    public function subscribeCableTV($transactionId, $cableTVType, $subscriptionPlan, $smartCardNumber, $amount, $phone, $companyID)
    {
        try {

            $body = [
                "request_id"        => $transactionId,
                "serviceID"         => $cableTVType,
                "variation_code"    => $subscriptionPlan,
                "billersCode"       => $smartCardNumber,
                "subscription_type" => "renew",
                "amount"            => $amount,
                "phone"             => $phone,
            ];

            logInfo(json_encode($body), "VTPass Subscribe CableTV Request");

            $endpoint = "pay";

            $meterResponse = $this->vtpassPostRequest($endpoint, $body);

            logInfo($meterResponse, "VTPass Subscribe CableTV Response Log");

            if (isset($meterResponse["code"])) {
                if ($meterResponse["code"] == "000") {

                    return
                        [
                        "status"  => true,
                        'message' => "Cable TV Purchase Successful",
                    ];
                } else {
                    return ["status" => false, 'message' => $meterResponse['response_description']];
                }
            } else {
                return ["status" => false, 'message' => "Failed to subscribe cable TV"];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VTPASS Data Error");
            return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
        }
    }

    public function verifyBVN($bvn, $company_id = null)
    {

        $url = "identity/v2";

        $loginResult = $this->getToken($company_id);

        $body = [
            "type"               => "BVN",
            "async"              => false,
            "number"             => $bvn,
            "debitAccountNumber" => $this->debitAccount,
        ];

        if (! $loginResult['status']) {
            return ['status' => false, 'message' => 'Failed to connect to Server. Please contact support'];
        }

        try {
            $response = $this->safehavenPostRequest($url, $body);

            logInfo($response, "Safehaven verify bvn response");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == 200) {

                    return [
                        'status'  => true,
                        'message' => $response['message'],
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

    public function verifyNIN($nin, $company_id = null)
    {

        $url = "identity/v2";

        $loginResult = $this->getToken($company_id);

        $body = [
            "type"               => "NIN",
            "async"              => false,
            "number"             => $nin,
            "debitAccountNumber" => $this->debitAccount,
        ];

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

    public function verifyCAC($cac, $company_id = null)
    {
        logInfo($cac, "CAC");
        logInfo($this->debitAccount, "DEBIT Account");

        $url = "identity/v2";

        $loginResult = $this->getToken($company_id);

        $body = [
            "type"               => "CAC-SEARCH",
            "async"              => false,
            "number"             => $cac,
            "debitAccountNumber" => $this->debitAccount,
        ];

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

    public function creditCheck($cac, $company_id = null)
    {

        $url = "identity/v2";

        $loginResult = $this->getToken();

        $body = [
            "type"               => "CREDITCHECK",
            "async"              => false,
            "number"             => $cac,
            "provider"           => "firstCentral",
            "debitAccountNumber" => $this->debitAccount,
        ];

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

    public function verifyAccount($bankCode, $accountNumber, $company_id = null)
    {
        $body = [
            "bankCode"      => $bankCode,
            "accountNumber" => $accountNumber,
        ];

        $url = "transfers/name-enquiry";

        $loginResult = $this->getToken($company_id);

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
        $response = Http::withHeaders($this->safehavenHeader())
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->post("{$this->baseUrl}{$endpoint}", $body);
        return $response->json();
    }

    public function safehavenGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->safehavenHeader())
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->get("{$this->baseUrl}{$endpoint}");
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

        $card_withdrawal_charge = $this->getCompanySettingsCardWithrawalFee($companyId);

        $chargeAmount = $card_withdrawal_charge + $formatedAmount;

        logInfo($chargeAmount, "full charge Amount");

        $debitCustomer = $this->debitCustomerWithChargeSudo($chargeAmount, 0, $user, $user_wallet);
        logInfo(json_encode($debitCustomer), "debitCustomer Sudo");

        $debitCompany = $this->debitCompanyCardWallet($companyId, $chargeAmount);
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

        $card_withdrawal_charge = $this->getCompanySettingsCardWithrawalFee($companyId);

        $chargeAmount = $card_withdrawal_charge + $formatedAmount;

        if ($chargeAmount < $oldBalance) {
            logInfo("amount working", "formated Amount if less than");
            logInfo("ready to send response", "response");

            $checkCompanyBalance = $this->checkCompanyBalance($user_wallet, $chargeAmount, $companyId, $user);
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

    public function getSudoUserRefund($request, $user_wallet, $companyId, $branchId, $transactionReference, $user)
    {
        $transaction = transactions::where('transaction_reference', $transactionReference)->where('description', 'Card Refund')->first();
        if ($transaction == null) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        } else {
            $amount = $request->input('data.object.amount');
            logInfo($amount, "Amount");
            $formatedAmount = abs($amount);

            logInfo($formatedAmount, "formated Amount");

            $oldBalance = $user_wallet->balance;
            logInfo($user_wallet->balance, "Old Wallet Balance");

            $customerAccountId = $user_wallet->wb_account_detail_id;
            $charge            = 0.0;
            $creditCustomer    = $this->creditCustomerSudo($customerAccountId, $formatedAmount, $charge, $user_wallet);
            $creditCompany     = $this->creditCompanyCardWallet($companyId, $formatedAmount);

            if ($creditCustomer['status']) {
                $newdescription = "Card Refund";
                $this->refundSudoTransaction($creditCustomer, $creditCompany, $formatedAmount, $companyId, $newdescription, $branchId, $customerAccountId, $user, $transactionReference);
                logInfo($creditCompany['balance_before'], "New Wallet Balance");
            }

            return [
                'statusCode'   => 200,
                'responseCode' => "00",
                'httpCode'     => true,
            ];
        }

    }

    public function getUserCard($accountReference)
    {
        return UserCard::where('card_reference', $accountReference)->first()->user_id;

    }

    private function refundSudoTransaction($creditCustomer, $creditCompany, $amount, $companyId, $newdescription, $branchId, $customerAccountId, $user, $transactionReference)
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
            "transaction_reference"  => $transactionReference,
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
            "transaction_type_id"    => 10,
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

    private function creditCustomerSudo($customerAccountId, $originalAmount, $charge, $userWallet)
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
        //  $userWallet->save();

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
        //    $companyWallet->save();

        logInfo($newBalance, "New Card Wallet Balance for {$companyWallet->company_id}");

        return [
            "status"         => true,
            "message"        => "Amount credited successfully",
            "amount"         => $formattedAmount,
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }

    private function getCompanySettingsCardWithrawalFee($companyId)
    {
        $company_settings_data = company_settings::where('company_id', '=', $companyId)->get();
        foreach ($company_settings_data as $company_settings_dataid);
        $company_settings_id = $company_settings_dataid->id;
        $company_settings    = company_settings::findorfail($company_settings_id);

        return $company_settings->physical_card_withdrawal_charge ?? 0;
    }

    private function initializeKeys()
    {
        if (! auth()->check()) {
            return;
        }

        if (auth()->user()->company_id == 14) {
            $this->getKeysFinova();
        }
    }

    public function format_number($tell)
    {

        $tell = str_replace('+', '', $tell);
        $len  = strlen($tell);
        if ($len == 11) {
            $tell = "234" . substr($tell, 1);
        } elseif ($len == 13) {
            $tell = $tell;
        }

        return $tell;
    }
}
