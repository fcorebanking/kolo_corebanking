<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\company_settings;
use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;
use App\Models\transactions;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                            'customer_name'   => trim($verifyCableResponse['content']['Customer_Name']),
                            'cable_tv'        => trim($verifyCableResponse['content']['Customer_Type']),
                            'current_bouquet' => trim($verifyCableResponse['content']['Current_Bouquet']) ?? "",
                            //   'current_bouquet_code' => trim($verifyCableResponse['content']['Current_Bouquet_Code']) ?? "",
                            'renewal_amount'  => trim($verifyCableResponse['content']['Renewal_Amount']) ?? "",
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
                        'purchased_units' => $buyElectricity['PurchasedUnits'] ?? $buyElectricity['Units'] ?? $buyElectricity['units'] ?? null,
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
        $payload = $request->all();
        logInfo(json_encode($payload), "VTPASSWebHook");

        try {
            $this->processTransaction($payload);
        } catch (\Exception $e) {
            logError($e->getMessage(), "VTPASSWebHookError");
        }

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
        $txnData = $response['data']['content']['transactions'] ?? null;

        if (! $txnData || $txnData['status'] !== "delivered") {
            logInfo("Webhook ignored — no delivered transaction", "VTPASSWebHook");
            return;
        }

        if (! $txnData || $txnData['status'] == "reversed") {
            $transaction =
                logInfo("TRANSACTION REVERSAL TO WALLET ", "VTPASSWebHook");
            return;
        }

        DB::beginTransaction();
        try {
            $transaction = transactions::lockForUpdate()
                ->where([
                    'transaction_reference' => $txnData['transactionId'],
                    'amount'                => $txnData['amount'],
                ])->first();

            if (! $transaction) {
                logError("Transaction not found for ref " . $txnData['transactionId']);
                DB::rollBack();
                return;
            }

            // Idempotency check
            if ($transaction->transaction_remarks_id == 2) {
                logInfo("Duplicate webhook ignored for {$transaction->transaction_reference}", "VTPASSWebHook");
                DB::rollBack();
                return;
            }

            switch ($transaction->description) {
                case "Buy Airtime Initialized":
                    $this->processAirtime($response, $transaction);
                    break;
                case "Buy Electricity Initialized":
                    $this->processElectricity($response, $transaction);
                    break;
                case "Subscribe TV Initialized":
                    $this->processCableTV($response, $transaction);
                    break;
                default:
                    $this->processDataBundle($response, $transaction);
                    break;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logInfo($e->getMessage(), "VTPASS_PROCESS_FAIL");
        }
    }

    public function processAirtime(array $response, $transaction)
    {
        // Keep original transaction reference
        $parentTx = $transaction;

        // Idempotency: skip if already marked successful
        if ($parentTx->transaction_remarks_id == 2) {
            Log::info("Webhook ignored: transaction already processed", ['ref' => $parentTx->transaction_reference]);
            return;
        }

        // Get user and ensure account id
        $accountId = $parentTx->account_id;
        $user      = $this->getUser($parentTx);
        if (! $user) {
            Log::error("processAirtime: user or account missing", ['transaction_id' => $parentTx->id]);
            return;
        }

        // Read values from response safely
        $txnData = $response['data']['content']['transactions'] ?? null;
        if (! $txnData) {
            Log::error("processAirtime: invalid response structure", ['response' => $response]);
            return;
        }

        $final_amount = $txnData['total_amount'] ?? $txnData['amount'] ?? 0;
        $phoneNumber  = $txnData['phone'] ?? null;

        // Call your debit helper (assumed to return expected structure)
        $debitCustomer = debitCustomerAndCompanyWithCompanyCashback(
            $accountId,
            $user->company_id,
            $final_amount,
            0,  // customer cashback percent
            0.01// company cashback percent (example)
        );

        // Basic validation of helper output
        if (! is_array($debitCustomer) || ! isset($debitCustomer['balance_before'])) {
            Log::error("processAirtime: debit helper failed or returned unexpected data", ['transaction_id' => $parentTx->id]);
            return;
        }

        // Perform DB atomic work
        DB::beginTransaction();
        try {
            // Update parent transaction with debit results
            $parentTx->update([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $final_amount,
                "account_id"             => $accountId,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"] ?? null,
                "company_balance_after"  => $debitCustomer["company_balance_after"] ?? null,
                "company_amount"         => $debitCustomer["company_amount"] ?? 0,
                "description"            => "Purchase of Airtime worth {$final_amount} for {$phoneNumber}",
                "transaction_remarks_id" => 1, // processing
                "transaction_type_id"    => 11,
                "credit_debit_type_id"   => 2,
            ]);

            // Create cashback transaction only if customer_cashback > 0
            $customerCashback = $debitCustomer['customer_cashback'] ?? 0;
            $cashbackTx       = null;

            if ($customerCashback > 0) {
                $cashbackTransactionId = generate_txnID();

                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(66, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                $cashbackTx = transactions::create([
                    "balance_before"         => $debitCustomer['cashback_balance_before'] ?? null,
                    "amount"                 => $customerCashback,
                    "account_id"             => $accountId,
                    "balance_after"          => $debitCustomer["cashback_balance_after"] ?? null,
                    "company_balance_before" => $debitCustomer["company_cashback_balance_before"] ?? null,
                    "company_balance_after"  => $debitCustomer["company_cashback_balance_after"] ?? null,
                    "company_amount"         => $debitCustomer["company_cashback"] ?? 0,
                    "description"            => "Cashback on Airtime Purchase",
                    "branch_id"              => $parentTx->branch_id,
                    "company_id"             => $parentTx->company_id,
                    "transaction_reference"  => $cashbackTransactionId,
                    "parent_transaction_id"  => $parentTx->id,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 66,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $parentTx->created_by,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            }

            // Mark the parent transaction as successful and set balance
            // Use $modifierId fallback if no authenticated user in webhook context
            $modifierId = auth()->id() ?? $parentTx->created_by ?? null;
            $this->updateTransactionAsSuccessful($parentTx, $debitCustomer["balance_after"] ?? 0, true, $modifierId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("processAirtime failed: " . $e->getMessage(), [
                'transaction_id' => $parentTx->id,
                'exception'      => $e,
            ]);
            // optionally rethrow or handle
        }
    }

    public function processDataBundle(array $response, $transaction)
    {
        $parentTx = $transaction;

        // Avoid reprocessing
        if ($parentTx->transaction_remarks_id == 2) {
            Log::info("Webhook ignored: transaction already processed", ['ref' => $parentTx->transaction_reference]);
            return;
        }

        // Extract data from response
        $txnData = $response['data']['content']['transactions'] ?? null;
        if (! $txnData) {
            Log::error("processDataBundle: invalid response format", ['response' => $response]);
            return;
        }

        $final_amount = $txnData['total_amount'] ?? $txnData['amount'] ?? 0;
        $phoneNumber  = $txnData['phone'] ?? null;

        // Get user and ensure account
        $user = $this->getUser($parentTx);
        if (! $user) {
            Log::error("processDataBundle: user or account missing", ['transaction_id' => $parentTx->id]);
            return;
        }
        $accountId = $parentTx->account_id;

        // Debit
        $debitCustomer = debitCustomerAndCompanyWithCompanyCashback(
            $accountId,
            $user->company_id,
            $final_amount,
            0,  // customer cashback %
            0.01// company cashback %
        );

        if (! is_array($debitCustomer) || ! isset($debitCustomer['balance_before'])) {
            Log::error("processDataBundle: debit helper returned unexpected data", ['transaction_id' => $parentTx->id]);
            return;
        }

        DB::beginTransaction();
        try {
            // Update main transaction
            $parentTx->update([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $final_amount,
                "account_id"             => $accountId,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"] ?? null,
                "company_balance_after"  => $debitCustomer["company_balance_after"] ?? null,
                "company_amount"         => $debitCustomer["company_amount"] ?? 0,
                "description"            => "Purchase of Data worth ₦{$final_amount}" . ($phoneNumber ? " for {$phoneNumber}" : ""),
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 12,
                "credit_debit_type_id"   => 2,
            ]);

            // Cashback creation (if applicable)
            $customerCashback = $debitCustomer["customer_cashback"] ?? 0;
            if ($customerCashback > 0) {
                $cashbackTransactionId = generate_txnID();
                $ledger_cr             = null;
                $ledger_dr             = null;

                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(66, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                transactions::create([
                    "balance_before"         => $debitCustomer['cashback_balance_before'] ?? null,
                    "amount"                 => $customerCashback,
                    "account_id"             => $accountId,
                    "balance_after"          => $debitCustomer["cashback_balance_after"] ?? null,
                    "company_balance_before" => $debitCustomer["company_cashback_balance_before"] ?? null,
                    "company_balance_after"  => $debitCustomer["company_cashback_balance_after"] ?? null,
                    "company_amount"         => $debitCustomer["company_cashback"] ?? 0,
                    "description"            => "Cashback on Data Purchase",
                    "company_id"             => $parentTx->company_id,
                    "branch_id"              => $parentTx->branch_id,
                    "parent_transaction_id"  => $parentTx->id,
                    "transaction_reference"  => $cashbackTransactionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 66,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $parentTx->created_by,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            }

            // Mark as successful
            $modifierId = auth()->id() ?? $parentTx->created_by ?? null;
            $this->updateTransactionAsSuccessful($parentTx, $debitCustomer["balance_after"] ?? 0, true, $modifierId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("processDataBundle failed: " . $e->getMessage(), [
                'transaction_id' => $parentTx->id,
                'exception'      => $e,
            ]);
        }
    }

    public function processElectricity(array $response, $transaction)
    {
        // Avoid double-processing
        if ($transaction->transaction_remarks_id == 2) {
            Log::info("Webhook ignored: electricity transaction already processed", ['ref' => $transaction->transaction_reference]);
            return;
        }

        // Extract total amount safely
        $txnData = $response['data']['content']['transactions'] ?? null;
        if (! $txnData || ! isset($txnData['total_amount'])) {
            Log::error("processElectricity: invalid response data", ['response' => $response]);
            return;
        }

        $final_amount     = $txnData['total_amount'];
        $meterNumber      = $txnData['meterNumber'] ?? null;
        $electricityToken = $txnData['token'] ?? null;

        // Get company settings
        $companySettings = $this->getCompanySettings($transaction);
        if (! $companySettings) {
            Log::error("processElectricity: missing company settings", ['transaction_id' => $transaction->id]);
            return;
        }

        // Calculate charges
        $wbaCharge = getWbaTransferCharges(13, $final_amount);

        // Perform debit
        $debitCustomer = $this->customDebitCustomerAndCompanyWithCharge($transaction, $final_amount, $companySettings, $wbaCharge);
        if (! is_array($debitCustomer) || ! isset($debitCustomer['balance_before'])) {
            Log::error("processElectricity: debit helper failed", ['transaction_id' => $transaction->id]);
            return;
        }

        DB::beginTransaction();
        try {
            // Update main transaction
            $transaction->update([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $final_amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Purchase of Electricity worth ₦{$final_amount}" . ($meterNumber ? " for meter {$meterNumber}" : ""),
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 14,
                "credit_debit_type_id"   => 2,
            ]);

            // Record charge transaction if charge applies
            if (! empty($companySettings->bills_payment_charge)) {
                $transactionChargeId = generate_txnID();
                $ledger_cr           = null;
                $ledger_dr           = null;

                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(51, $transaction->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                transactions::create([
                    "balance_before"         => $debitCustomer['charge_balance_before'] ?? null,
                    "amount"                 => $companySettings->bills_payment_charge,
                    "balance_after"          => $debitCustomer["charge_balance_after"] ?? null,
                    "account_id"             => $transaction->account_id ?? $debitCustomer['account_id'] ?? null,
                    "company_id"             => $transaction->company_id,
                    "branch_id"              => $transaction->branch_id,
                    "company_balance_before" => $debitCustomer["company_charge_balance_before"] ?? null,
                    "company_amount"         => $wbaCharge,
                    "company_balance_after"  => $debitCustomer["company_charge_balance_after"] ?? null,
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
            }

            // Update token info
            if ($electricityToken) {
                $transaction->update([
                    "transaction_remarks_id" => 2,
                    "description"            => "Purchased Electricity Token: {$electricityToken} Amount: ₦{$final_amount}",
                ]);
            } else {
                $transaction->update([
                    "transaction_remarks_id" => 2,
                    "description"            => "Electricity Purchase of ₦{$final_amount} completed (Token unavailable)",
                ]);
            }

            DB::commit();

            // Send SMS notification
            $availableBal = $debitCustomer["charge_balance_after"] ?? $debitCustomer["balance_after"];
            $smsMessage   = "Electricity Token: " . ($electricityToken ?? 'N/A') .
            ", Amount: ₦{$final_amount}, Avail Bal: ₦" . number_format($availableBal, 2);

            sendSMS($transaction->created_by, $smsMessage, true, true, 'Electricity Token');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("processElectricity failed: " . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'exception'      => $e,
            ]);
        }
    }

    public function processCableTV(array $response, $transaction)
    {
        // Prevent double-processing
        if ($transaction->transaction_remarks_id == 2) {
            Log::info("CableTV transaction already processed", ['ref' => $transaction->transaction_reference]);
            return;
        }

        // Validate response
        $txnData = $response['data']['content']['transactions'] ?? null;
        if (! $txnData || ! isset($txnData['total_amount'])) {
            Log::error("processCableTV: invalid response", ['response' => $response]);
            return;
        }

        $final_amount = $txnData['total_amount'];
        $smartCard    = $txnData['purchased_code'] ?? 'Unknown';

        // Charges and settings
        $companySettings = $this->getCompanySettings($transaction);
        if (! $companySettings) {
            Log::error("processCableTV: Missing company settings", ['transaction_id' => $transaction->id]);
            return;
        }

        $wbaCharge = getWbaTransferCharges(12, $final_amount); // No divide by 100

        // Perform debit
        $debitCustomer = $this->customDebitCustomerAndCompanyWithCharge($transaction, $final_amount, $companySettings, $wbaCharge);
        if (! is_array($debitCustomer) || ! isset($debitCustomer['balance_before'])) {
            Log::error("processCableTV: Debit failed", ['transaction_id' => $transaction->id]);
            return;
        }

        DB::beginTransaction();
        try {
            // Update main transaction
            $transaction->update([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $final_amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Cable TV Subscription worth ₦{$final_amount} for Smartcard {$smartCard}",
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 13,
                "credit_debit_type_id"   => 2,
            ]);

            // Record charge transaction
            if (! empty($companySettings->bills_payment_charge)) {
                $transactionChargeId = generate_txnID();
                $ledger_cr           = null;
                $ledger_dr           = null;

                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(51, $transaction->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                transactions::create([
                    "balance_before"         => $debitCustomer['charge_balance_before'] ?? null,
                    "amount"                 => $companySettings->bills_payment_charge,
                    "balance_after"          => $debitCustomer["charge_balance_after"] ?? null,
                    "account_id"             => $transaction->account_id,
                    "company_id"             => $transaction->company_id,
                    "branch_id"              => $transaction->branch_id,
                    "company_balance_before" => $debitCustomer["company_charge_balance_before"] ?? null,
                    "company_amount"         => $wbaCharge,
                    "company_balance_after"  => $debitCustomer["company_charge_balance_after"] ?? null,
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
            }

            // Mark as successful
            $transaction->update([
                "transaction_remarks_id" => 2,
                "description"            => "Cable TV Subscription of ₦{$final_amount} completed successfully for Smartcard {$smartCard}",
            ]);

            DB::commit();

            // Notify user
            $availBal = $debitCustomer["charge_balance_after"] ?? $debitCustomer["balance_after"];
            sendSMS(
                $transaction->created_by,
                "Cable TV Subscription successful for Smartcard {$smartCard}, Amount ₦{$final_amount}, Avail Bal: ₦" . number_format($availBal, 2),
                true,
                true,
                'Cable TV'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("processCableTV failed: " . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'exception'      => $e,
            ]);
        }
    }

public function checkVTPASSBalance()
{
     try {

        $endpoint = "balance";

        $dataResponse = $this->vtpassGetRequest($endpoint);

        logInfo($dataResponse, "VTPass Check Balance Response Log");

        /*
         * Make sure VTPass returned a valid response
         */
        if (!is_array($dataResponse)) {
            return [
                'status'  => false,
                'message' => 'Invalid response received from VTPass',
            ];
        }

        /*
         * VTPass successful balance response:
         *
         * {
         *     "code": 1,
         *     "contents": {
         *         "balance": "56.08"
         *     }
         * }
         */
        if (
            isset($dataResponse['code']) &&
            (string) $dataResponse['code'] === '1'
        ) {

            if (
                !isset($dataResponse['contents']) ||
                !isset($dataResponse['contents']['balance'])
            ) {
                logInfo(
                    $dataResponse,
                    "VTPass Balance Missing From Response"
                );

                return [
                    'status'  => false,
                    'message' => 'VTPass balance was not returned',
                ];
            }

            $balance = (float) $dataResponse['contents']['balance'];

            return [
                'status'  => true,
                'message' => 'VTPass balance retrieved successfully',
                'balance' => $balance,
            ];
        }

        /*
         * VTPass returned an error
         */
        logInfo(
            $dataResponse,
            "VTPass Balance API Error Response"
        );

        return [
            'status'  => false,
            'message' => $dataResponse['response_description']
                ?? $dataResponse['message']
                ?? 'Unable to retrieve VTPass balance',
        ];

    } catch (Exception $e) {

        logInfo(
            $e->getMessage(),
            "VTPASS Balance Error"
        );

        return [
            'status'  => false,
            'message' => 'Failed to retrieve VTPass balance. Please try again later',
        ];
    }
}

    public function updateTransactionAsSuccessful($transaction, $balance = 0, $updateBalance = true, $modifierId = null)
    {
        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        // prefer provided modifier id, fallback to auth user id, else null
        $transaction->modified_by = $modifierId ?? auth()->user()->id;
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
