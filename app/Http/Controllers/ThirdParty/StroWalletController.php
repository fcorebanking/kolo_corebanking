<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Services\SafeHavenService;
use Exception;
use Illuminate\Support\Facades\Http;

class StroWalletController extends WebhookController
{

    private $publicKey;
    private $baseUrl;
    private $owner;

    public function __construct()
    {

        $this->publicKey = "pub_MqhuKiKnU6nwCZkWoqtJtLeGud3jXmVHqRK6yMv0";

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
            $response = $this->strowalletPostRequest("oauth2/token", $body);

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

    public function createVirtualAccount($firstName, $lastName, $email, $phone, $customerReference, $companyID, $type)
    {

        $endpoint = " https://strowallet.com/api/virtual-bank/fidelitybank/virtual-bank/new-customer/";

        try {

            $body = [

                "public_key"   => $this->publicKey,
                "email"        => $email,
                "account_name" => "{$firstName} {$lastName}",
                "phone"       => $phone,
                "webhook_url" => "https://core.finova.ng/api/v1/transactions/safehaven-webhook",

            ];

            logInfo($body, "Strowallet request");

            $createAccount = $this->strowalletPostRequest($endpoint, $body);

            logInfo($createAccount, "strowallet Response Log");

            if (isset($createAccount['statusCode'])) {
                if ($createAccount['statusCode'] == "200") {
                    return ['status' => true, 'message' => $createAccount['message'], 'data' => $createAccount['accountNumber']];
                } else {
                    return ['status' => false, 'message' => $createAccount['message'], 'data' => $createAccount];
                }
            } else {
                return ['status' => false, 'message' => "Failed to Create Account ...Please contact support", 'data' => $createAccount];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Strowallet Error");
            return ['status' => false, 'message' => "Failed to Create Account ...Please contact support"];
        }
    }

    public function bankTransfer($amount, $transactionReference, $description, $bankCode, $accountNumber, $sessionId, $companyID)
    {

        try {

            //   $loginResult = $this->getToken($companyID);

            $queryParams = [
                "public_key"             => $this->publicKey,
                "amount"                 => floatval($amount),
                "name_enquiry_reference" => $sessionId,
                "narration"              => $description,
                "bank_code"              => $bankCode,
                "account_number"         => $accountNumber,

            ];

            $url = "https://strowallet.com/api/banks/request" . '?' . http_build_query($queryParams);

            $body = [];

            logInfo($body, "Strowallet transfer request body");

            $response = $this->strowalletPostRequest($url, $body);

            $result = $response;

            logInfo($result, "Strowallet  transfer response");

            if (isset($createAccount['statusCode'])) {
                if ($createAccount['statusCode'] == "200") {
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

    public function verifyAccount($bankCode, $accountNumber, $company_id = null)
    {
        $queryParams = [
            "public_key"    => $this->publicKey,
            "bankCode"      => $bankCode,
            "accountNumber" => $accountNumber,
        ];

        $url = "https://strowallet.com/api/banks/get-customer-name" . '?' . http_build_query($queryParams);

        try {
            $response = $this->strowalletGetRequest($url);

            logInfo($response, "Strowallet response");

            if (isset($response['statusCode'])) {
                if ($response['statusCode'] == "200") {

                    $this->sessionId = $response["data"]["sessionId"];

                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name' => $response["data"]["accountName"],
                            'bank_code'    => $response["data"]["bankCode"],
                            'session_id'   => $response["data"]["sessionId"],
                        ],
                    ];
                } else {
                    return ['status' => false, 'message' => 'Failed to validate bank account'];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to validate bank account'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Strowallet Error");

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

    public function strowalletPostRequest($endpoint, $body)
    {
        logInfo("$endpoint", "Complete url");

        $response = Http::post("{$endpoint}", $body);
        return $response->json();
    }

    public function strowalletGetRequest($endpoint)
    {
        $response = Http::get("{$endpoint}");
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
