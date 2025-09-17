<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\AssetMatrixWebhook;
use App\Models\wb_account_details;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AssetMatrixController extends WebhookController
{

    private $publicKey;
    private $secretKey;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->token = "";
        if (env('APP_MODE') == 'test') {

            $this->publicKey = config('assetmatrix.public_key_test');
            $this->secretKey = config('assetmatrix.secret_key_test');
            $this->baseUrl   = config('assetmatrix.base_url_test');
        } else {
            $this->publicKey = config('assetmatrix.public_key_live');
            $this->secretKey = config('assetmatrix.secret_key_live');
            $this->baseUrl   = config('assetmatrix.base_url_live');
        }
    }

    public function login()
    {

        $body = [
            "publicKey" => $this->publicKey,
            "secretKey" => $this->secretKey,
        ];

        $header = ['Content-Type' => 'application/json'];
        try {
            $myResponse = Http::withHeaders($header)->post($this->baseUrl . "auth/login", $body);

            \logInfo($body, "assetmatrix Login BODY");
            \logInfo($myResponse, "assetmatrix Login Response");

            $response = $myResponse->json();

            if ($response["status"] == "00") {
                $this->token = $response["data"]["access_token"];
                return ['status' => true, 'message' => 'Login success'];
            } else {
                return ['status' => false, 'message' => 'Failed to login to Monnify'];
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Asset Matrix Login serror log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    public function getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency)
    {
        $body = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url  = "transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        logInfo("We are converting $exchangeAmount from $sourceCurrency to $destinationCurrency");
        try {
            $exhangeRate = $this->assetMatrixPostRequest($url, $body);

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

    public function createAccount($requestReference, $email, $phoneNumber, $firstName, $lastName, $bvn)
    {

        $body = [
            "accountName"   => "$firstName $lastName",
            "accountNumber" => "1017822346",

        ];

        $url = "$this->baseUrl/open";

        try {

            /*    $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }*/

            \logInfo($body, "Asset Matrix Request body");

            $response = $this->assetMatrixPostRequest($url, $body);

            logInfo($response, "Asset matrix Create Account Response");

            if ($response["status"] == "00") {

                return [
                    'status'  => true,
                    'message' => "Account Created Successfully",
                    'data'    => [
                        'account_number' => $response['data']['accountNumber'],
                    ],
                ];
            } else {
                return ['status' => false, 'message' => 'Account Creation Failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset matrix Error");

            return ['status' => false, 'message' => 'Error Creating Account. Kindly contact support for assistance'];
        }
    }

    public function createAccountInternal()
    {

        try {
            return [
                'status'  => true,
                'message' => "Account Created Successfully",
                'data'    => [
                    'account_number' => generateAccountNumber(),
                ],
            ];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset matrix Error");

            return ['status' => false, 'message' => 'Error Creating Account. Kindly contact support for assistance'];
        }
    }

    public function generateAndVerifyAssetMatrixAccount()
    {

        $createAccountResponse = $this->createAccountInternal();

        if ($createAccountResponse['status']) {

            $virtualAccountNumber = $createAccountResponse['data']['account_number'];
            // $virtualAccountNumber = $this->sanitizeAccountNumber("NGN", $createAccountResponse['data']['account_number']);
            $accountReference = $virtualAccountNumber;

            $checkVirtualAccount = wb_account_details::where('virtual_accnum', $virtualAccountNumber)->first();

            if ($checkVirtualAccount) {
                $this->generateAndVerifyAssetMatrixAccount();
            } else {

                return [
                    'status'  => true,
                    'message' => 'Account Created Successfully',
                    'data'    => [
                        'virtual_account'   => $virtualAccountNumber,
                        'account_reference' => $accountReference,
                    ],
                ];
            }
        }
    }

    public function verifyBankAccount($bankCode, $accountNumber)
    {
        $url = "payout?accountNumber=$accountNumber&bankCode=$bankCode";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            $response = $this->assetMatrixGetRequest($url);

            logInfo($response, "Asset matrix Verify Account Response");

            if ($response['status'] == "00") {

                return [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'account_name' => $response["data"]["accountName"],
                        'bank_code'    => $response["data"]["bankCode"],
                    ],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify Account name'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error verifying Bank Account. Kindly contact support for assistance'];
        }
    }

    public function verifyTransaction($transactionReference)
    {
        $url = "https://partner.com/api/v1/accounts/$transactionReference";

        try {

            $response = Http::withHeaders($this->assetMatrixHeader())->get($url);
            return $response->json();
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error verifying Bank Account. Kindly contact support for assistance'];
        }
    }

    public function verifyBVN($bvn)
    {

        $url = "bvn/validate?bvn=$bvn";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            $createAccountResponse = $this->assetMatrixGetRequest($url);

            logInfo($createAccountResponse, "Asset Matrix Verify Account Response");

            if ($createAccountResponse->successful()) {

                $response = $this->sanitizeResponse($createAccountResponse['data']);

                if (! $response["Status"]) {
                    return [
                        'status'  => false,
                        'message' => $response["Message"],
                    ];
                } else {
                    return [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'account_name' => $response["Data"]["BeneficiaryName"],
                            'bank_code'    => $response["Data"]["BeneficiaryBankCode"],
                            'session_id'   => $response["Data"]["SessionID"],
                        ],
                    ];
                }
            } else {
                return ['status' => false, 'message' => 'Webhook withdrawal failed. Kindly contact support for assistance'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error completing withdrawal. Kindly contact support for assistance'];
        }
    }

    public function bankTransfer($requestReference, $accountNumber, $bankCode, $amount, $description)
    {

        $body = [
            "beneficiaryAccount"  => $accountNumber,
            "beneficiaryBankCode" => $bankCode,
            "amount"              => $amount,
            "referenceNumber"     => $requestReference,
            "narration"           => $description,
        ];

        $url = "core/payout";

        try {

            $login = $this->login();

            if (! $login['status']) {
                return ['status' => false, 'message' => 'Failed to Process Asset Matrix Login'];
            }

            \logInfo($login, "Asset Matrix Request body");

            $createAccountResponse = $this->assetMatrixPostRequest($url, $body);

            logInfo($createAccountResponse, "Asset Matrix Bank Transfer Response");

            if ($createAccountResponse["status"] == "00") {
                return [
                    'status'  => true,
                    'message' => "Bank Transfer Successful",
                ];
            } else {
                return ['status' => false, 'message' => 'Bank Transfer failed. If you have been debited kindly contact support for reversal'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Asset Matrix Error");

            return ['status' => false, 'message' => 'Error with Bank Transfer. If you have been debited kindly contact support for reversal'];
        }
    }

    public function insertIntoWebhookTable(Request $request)
    {

        $webhook = AssetMatrixWebhook::where('session_id', $request->sessionId)->first();

        if (! $webhook) {
            AssetMatrixWebhook::create([
                "amount"                => $request->amount,
                "account_name"          => $request->accountName,
                "narration"             => $request->narration,
                "session_id"            => $request->sessionId,
                "account_number"        => $request->accountNumber,
                "source_account_number" => $request->sourceAccountNumber,
                "source_account_name"   => $request->sourceAccountName,
            ]);
        }
    }

    public function buyAirtime($transactionId, $network, $amount, $phoneNumber)
    {

        try {
            $body = [
                "request_id" => $transactionId,
                "serviceID"  => $network,
                "amount"     => $amount,
                "phone"      => $phoneNumber,
            ];

            $buyAirtime = $this->vtpassPostRequest('pay', $body);

            logInfo($buyAirtime, 'Vtpass Airtime Response log');

            if (isset($buyAirtime['code'])) {
                if ($buyAirtime["code"] == "000") {
                    return ["status" => true, 'message' => "Airtime Purhcase Successful"];
                } else {
                    return ["status" => false, 'message' => $buyAirtime['response_description'] . ". Kindly contact admin for support", 'data' => $buyAirtime];
                }
            } else {
                return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "VTPass log error");
            return ["status" => false, 'message' => 'Failed to purchase airtime, Please contact support'];
        }
    }

    public function buyData($transactionId, $network, $dataPlain, $amount, $phoneNumber)
    {
        try {
            $body = [
                "request_id"     => $transactionId,
                "serviceID"      => $network,
                "variation_code" => $dataPlain,
                "amount"         => $amount,
                "phone"          => $phoneNumber,
            ];

            $endpoint = "pay";

            $buyDataResponse = $this->vtpassPostRequest($endpoint, $body);

            logInfo($buyDataResponse, "VTPass Data Response Log");

            if (isset($buyDataResponse['code'])) {
                if ($buyDataResponse["code"] == "000") {
                    return ["status" => true, 'message' => "Data Purhcase Successful"];
                } else {
                    return ["status" => false, 'message' => $buyDataResponse['response_description'] . ". Kindly contact admin for support", 'data' => $buyDataResponse];
                }
            } else {
                return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "VTPASS Data Error");
            return ["status" => false, 'message' => 'Failed to purchase data, Please contact support'];
        }
    }

    public function verifyCableTV($smart_card_number, $cable_tv_type)
    {
        try {

            $body = [
                "billersCode" => $smart_card_number,
                "serviceID"   => $cable_tv_type,
            ];

            $endpoint = "merchant-verify";

            $verifyCableResponse = $this->vtpassPostRequest($endpoint, $body);

            logInfo($verifyCableResponse, "VTPass Verify Cable Response Log");

            if (isset($verifyCableResponse["code"])) {
                if ($verifyCableResponse["code"] == "000") {

                    if (isset($verifyCableResponse['content']['error'])) {
                        return [
                            'status'  => false,
                            'message' => $verifyCableResponse['content']['error'],
                        ];
                    }

                    return [
                        'status'  => true,
                        'message' => "Cable TV Verified Successfully",
                        'data'    => [
                            'customer_name'        => trim($verifyCableResponse['content']['Customer_Name']),
                            'cable_tv'             => trim($verifyCableResponse['content']['Customer_Type']),
                            'current_bouquet'      => trim($verifyCableResponse['content']['Current_Bouquet']),
                            'current_bouquet_code' => trim($verifyCableResponse['content']['Current_Bouquet_Code']),
                            'renewal_amount'       => trim($verifyCableResponse['content']['Renewal_Amount']),
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

    public function subscribeCableTV($transactionId, $cableTVType, $subscriptionPlan, $smartCardNumber, $amount, $phone)
    {
        try {

            $body = [
                "request_id"        => $transactionId,
                "serviceID"         => $cableTVType,
                "variation_code"    => $subscriptionPlan,
                "billersCode"       => $smartCardNumber,
                "subscription_type" => "change",
                "amount"            => $amount,
                "phone"             => $phone,
            ];

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

    public function getDataBundles($networkProvider)
    {
        $endpoint = "service-variations?serviceID=$networkProvider";

        $dataBundles = $this->vtpassGetRequest($endpoint);

        if (isset($dataBundles['response_description']) == "000") {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['content']['varations']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => $dataBundles];
        }
    }

    public function verifyMeterNumber($meterNumber, $electrictyDisco, $type)
    {
        $endpoint = "merchant-verify";

        $body = [
            "billersCode" => $meterNumber,
            "serviceID"   => $electrictyDisco,
            "type"        => $type,
        ];

        $verifyMeterResult = $this->vtpassPostRequest($endpoint, $body);

        logInfo($verifyMeterResult, "VTPass Verify Meter Number Response Log");

        if (isset($verifyMeterResult["code"])) {
            if ($verifyMeterResult["code"] == "000") {

                if (isset($verifyMeterResult['content']['error'])) {

                    return [
                        'status'  => false,
                        'message' => $verifyMeterResult['content']['error'],
                    ];
                }

                return [
                    'status'  => true,
                    'message' => "Meter Verified Successfully",
                    'data'    => [
                        'meter_name'   => trim($verifyMeterResult['content']['Customer_Name']),
                        'address'      => trim($verifyMeterResult['content']['Address']),
                        'meter_type'   => trim($type),
                        //    'meter_number' => $electrictyDisco == "abuja-electric" ? trim($verifyMeterResult['content']['Meter_Number']) : (trim($verifyMeterResult['content']['Meter_Number']) ?? trim($verifyMeterResult['content']['MeterNumber'])),

                        'meter_number' => trim($verifyMeterResult['content']['Meter_Number'] ?? $verifyMeterResult['content']['MeterNumber'] ?? ''
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

    public function getSubscriptions($cableTV)
    {
        $endpoint = "service-variations?serviceID=$cableTV";

        $dataBundles = $this->vtpassGetRequest($endpoint);

        if (isset($dataBundles['response_description']) == "000") {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['content']['varations']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => $dataBundles];
        }
    }

    public function buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber)
    {

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $electrictyDisco,
            "variation_code" => $meterType,
            "billersCode"    => $meterNumber,
            "amount"         => $amount,
            "phone"          => $phoneNumber,
        ];

        $endpoint = "pay";

        $buyElectricity = $this->vtpassPostRequest($endpoint, $body);

        logInfo($buyElectricity, "VTPass Buy Electricity Response Log");

        try {

            if (isset($buyElectricity)) {
                if ($buyElectricity["code"] == "000") {

                    if ($buyElectricity['content']['transactions']['status'] == 'pending') {
                        return [
                            "status"  => true,
                            'message' => "Transaction is Processing. you will receive a token shortly",
                        ];
                    }

                    $electricityToken = isset($buyElectricity['Token']) ? $buyElectricity['Token'] : $buyElectricity['token'];

                    $returnTypeResponse = $meterType == 'prepaid' ? [
                        'balance'         => "0.0",
                        'token'           => $electricityToken,
                        'purchased_units' => isset($buyElectricity['PurchasedUnits']) ? $buyElectricity['PurchasedUnits'] : $buyElectricity['Units'],
                    ] : [
                        'balance' => "0.0",
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
            \logInfo(json_encode($e->getMessage()), "VTPass Buy Electricity Error Response Log");
            return ["status" => false, 'message' => "Sorry, Unable to get token. Kindly contact support"];
        }
    }

    public function handleWebhook(Request $request)
    {
        $response_status = $request->all();

        // file_put_contents(public_path('PaystackWebHook.txt'), PHP_EOL . json_encode($request->all()), FILE_APPEND);

        \logInfo(json_encode($request->all()), "VTPASSWebHook");

        $this->processTransaction($response_status);

        return response()->json(["response" => "success"], 200);
    }

    public function assetMatrixPostRequest($endpoint, $body)
    {
        \logInfo("$endpoint");
        $response = Http::withHeaders($this->assetMatrixHeader())->post("$this->baseUrl $endpoint", $body);
        return $response->json();
    }

    public function assetMatrixGetRequest($endpoint)
    {
        logInfo("$endpoint", "Convertion url");
        $response = Http::withHeaders($this->assetMatrixHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function assetMatrixHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
            "publicKey"     => $this->publicKey,
            "secretKey"     => $this->secretKey,
        ];

        return $header;
    }
}
