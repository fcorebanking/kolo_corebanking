<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class VTPassController extends Controller
{
    public function __construct()
    {
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
                        'meter_number' => $electrictyDisco == "abuja-electric" ? trim($verifyMeterResult['content']['Meter_Number']) : (trim($verifyMeterResult['content']['Meter_Number']) ?? trim($verifyMeterResult['content']['MeterNumber'])),
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
            return ["status" => false, 'message' => "Sorry, Unable to get token. Kindly contact support"];
        }
    }

    public function restrictUserAccount($restrictionId, $remark)
    {
        if (count(auth()->user()->customerPermissions) > 0) {
            foreach (auth()->user()->customerPermissions as $customerPermission) {
                if ($customerPermission->id == $restrictionId) {
                    return;
                } else {
                    // auth()->user()->customerPermissions()->attach($restrictionId);

                    MainCustomerRestriction::create([
                        'user_id'                => auth()->user()->id,
                        'company_id'             => \auth()->user()->company_id,
                        'customer_permission_id' => $restrictionId,
                        "remark"                 => $remark,
                        "created_by"             => auth()->user()->id,
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id"                 => auth()->user()->id,
                        "customer_permission_id"  => $restrictionId,
                        "remark"                  => $remark,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 1,
                    ]);
                }
            }
        }
    }

    public function vtPassRequestId()
    {
        $result     = '';
        $dateString = \str_replace("-", "", Carbon::now()->format('Y-m-d H:i:s'));
        $dateString = \str_replace(" ", "", $dateString);
        $dateString = \str_replace(":", "", $dateString);
        // return $dateString . $this->randomNumber(8);
        return $dateString;
    }

    public function vtPassBaseUrl($endpoint)
    {
        return env('APP_MODE') == 'test' ? config('vtpass.base_url_test') . $endpoint : config('vtpass.base_url_live') . $endpoint;
    }

    public function vtpassGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->vtpassHeader())->get($this->vtPassBaseUrl($endpoint));
        return $response->json();
    }

    public function vtpassPostRequest($endpoint, $body)
    {
        logInfo($this->vtPassBaseUrl($endpoint));
        $response = Http::withHeaders($this->vtpassHeader())->post($this->vtPassBaseUrl($endpoint), $body);
        return $response->json();
    }

    public function vtpassHeader()
    {
        $header         = [];
        $basicAuthToken = '';

        if (env('APP_MODE') == 'test') {
            $basicAuthToken = base64_encode(config('vtpass.username_test') . ":" . config('vtpass.password_test'));
        } else {
            $basicAuthToken = base64_encode(config('vtpass.username_live') . ":" . config('vtpass.password_live'));
        }
        $header = ['Authorization' => 'Basic ' . $basicAuthToken];

        return $header;
    }
}
