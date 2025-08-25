<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\company_settings;
use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;
use App\Models\transactions;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
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

    public function processTransaction($response)
    {
        if (isset($response['data']['content']['transactions']['status']) && $response['data']['content']['transactions']['status'] == "delivered") {
            $transactionId = $response['data']['content']['transactions']['transactionId'];
            $transaction   = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction->transaction_remarks_id == 6) {
                if ($transaction->description == "Buy Airtime Initialized") {

                    $this->processAirtime($response, $transaction);

                }

                if ($transaction->description == "Buy Electricity Initialized") {

                    $this->processElectricity($response, $transaction);

                }

                if ($transaction->description == "Subscribe TV Initialized") {

                    $this->processAirtime($response, $transaction);

                } else {
                    $this->processDataBundle($response, $transaction);
                }
            }
        }

    }

    public function processAirtime($response, $transaction)
    {
        $user          = $this->getUser($transaction);
        $final_amount  = $response['data']['content']['transactions']['total_amount'];
        $phoneNumber   = $response['data']['content']['transactions']['phone'];
        $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback($user->accounts->first()->id, $user->company_id, $final_amount, 0, 0.01);
        $transaction->update([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $final_amount,
            "account_id"             => $accountId,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "description"            => "Purchase of Airtime worth  $final_amount for  $phoneNumber",
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 11,
            "credit_debit_type_id"   => 2,

        ]);

        $cashbackTransactionId = \generate_txnID();
        $ledger_cr             = null;
        $ledger_dr             = null;
        $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['cashback_balance_before'],
            "amount"                 => $debitCustomer["customer_cashback"],
            "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $accountId : null,
            "balance_after"          => $debitCustomer["cashback_balance_after"],
            "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
            "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
            "company_amount"         => $debitCustomer["company_cashback"],
            "description"            => "Cashback on Airtime Purcahse",
            "branch_id"              => $transaction->branch_id,
            "company_id"             => $transaction->company_id,
            "transaction_reference"  => $cashbackTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 66,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $transaction->created_by,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $this->updateTransactionAsSuccessful($transaction, $debitCustomer["balance_after"]);
    }

    public function processDataBundle($response, $transaction)
    {
        $stringArray                       = explode('-', $transaction->description);
        $selectedDataBundleVariationAmount = ltrim($stringArray[1]);
        $user                              = $this->getUser($transaction);
        $debitCustomer                     = \debitCustomerAndCompanyWithCompanyCashback($user->accounts->first()->id, $user->company_id, $selectedDataBundleVariationAmount, 0, 0.01);
        $transaction->update([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $selectedDataBundle['variation_amount'],
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "description"            => "Purchase of Data worth " . $selectedDataBundle['variation_amount'] . " for $request->phone_number",
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 12,
            "credit_debit_type_id"   => 2,

        ]);

        $cashbackTransactionId = \generate_txnID();
        $ledger_cr             = null;
        $ledger_dr             = null;
        $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, $transaction->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $cashbackTransaction = transactions::create([
            "balance_before"         => $debitCustomer['cashback_balance_before'],
            "amount"                 => $debitCustomer["customer_cashback"],
            "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $transactionPinValidation['account_id'] : null,
            "balance_after"          => $debitCustomer["cashback_balance_after"],
            "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
            "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
            "company_amount"         => $debitCustomer["company_cashback"],
            "description"            => "Cashback on Data Purchase",
            "company_id"             => $transaction->company_id,
            "branch_id"              => $transaction->branch_id,
            "parent_transaction_id"  => $transaction->id,
            "transaction_reference"  => $cashbackTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 66,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $transaction->created_by,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $this->updateTransactionAsSuccessful($transaction, $debitCustomer['balance_after']);

    }

    public function processElectricity($response, $transaction)
    {
        $final_amount = $response['data']['content']['transactions']['total_amount'];

        $wbaCharge = getWbaTransferCharges(13, $final_amount);

        $companySettings = $this->getCompanySettings($transaction);

        $debitCustomer = $this->customDebitCustomerAndCompanyWithCharge($transaction, $final_amount, $companySettings, $wbaCharge);

        $transaction->update([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $final_amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Purchase of Electricity worth $final_amount",
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 14,
            "credit_debit_type_id"   => 2,

        ]);
        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $transaction->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        // Save Charges to transaction table
        $chargetransaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => $transaction->company_id,
            "branch_id"              => $transaction->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Electricity Purchase",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transaction->id,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $transaction->created_by,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $electricityToken = $buyElectricity['data']['token'];

        $transaction->transaction_remarks_id = 2;
        $transaction->description            = "Purchased Electricity Token: $electricityToken Amount: $final_amount";
        $transaction->save();

        $buyElectricity['data']['balance'] = $debitCustomer["charge_balance_after"];

        sendSMS($transaction->created_by, "Purchased Electricity Token $electricityToken, Amount: $final_amount, Avail Bal: " . $debitCustomer["charge_balance_after"], true, true, 'Electricity Token');
    }

    public function processCableTV($response, $transaction)
    {
        $final_amount = $response['data']['content']['transactions']['total_amount'];
        $wbaCharge    = getWbaTransferCharges(12, ($final_amount / 100));

        $companySettings = $this->getCompanySettings($transaction);

        $debitCustomer = $this->customDebitCustomerAndCompanyWithCharge($transaction, $final_amount, $companySettings, $wbaCharge);

        $transaction->update([

            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $final_amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Cable TV Subscription worth  $final_amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 13,
            "credit_debit_type_id"   => 2,

        ]);
        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $transaction->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transaction->account_id,
            "company_id"             => $transaction->company_id,
            "branch_id"              => $transaction->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Cable TV Subscription",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transaction->id,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $transaction->created_by,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $this->updateTransactionAsSuccessful($transaction, $debitCustomer['balance_before'], false);
    }

    public function updateTransactionAsSuccessful($transaction, $balance = 0, $updateBalance = true)
    {
        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function getCompanySettings($transaction)
    {
        return company_settings::where('company_id', $transaction->company_id)->first();
    }

    public function customDebitCustomerAndCompanyWithCharge($transaction, $final_amount, $companySettings, $wbaCharge)
    {
        return \debitCustomerAndCompanyWithCharge($transaction->account_id, $transaction->company_id, $final_amount, $companySettings->bills_payment_charge, $wbaCharge);
    }

    public function getUser($transaction)
    {
        // get user details
        return User::where('id', $transaction->created_by)->first();
    }
}
