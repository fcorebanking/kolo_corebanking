<?php
namespace App\Http\Controllers\CustomClasses;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\CgateController;
use App\Http\Controllers\ThirdParty\FincraController;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Models\AgentTerminal;
use App\Models\ApiKey;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\loan;
use App\Models\TempWebhook;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{

    public $transactionReference;
    public $virtualAccount;
    public $wbaCharge;
    public $amount;
    public $webhookType;
    public $accountReference;
    public $description;
    public $senderName;
    public $transactionDate;
    public $tempWebookRequest;
    public $fromTempWebhook;
    public Request $request;

    /*  public function handleWebhook()
    {
        try {
            $webhook = transactions::where('transaction_reference', $this->transactionReference)->first();

            if ($webhook) {
                if ($this->webhookType == 'providus') {
                    return ["responseCode" => "01", "requestSuccessful" => true, "sessionId" => $this->transactionReference, "responseMessage" => "duplicate transaction"];
                }
                return ['status' => false, 'message' => "Duplicate transaction", 'httpCode' => 400];
            }
            $checkOne = WebhookLog::where(['unique_reference' => $this->transactionReference])->first();

            if ($checkOne && $this->fromTempWebhook == 'false') {
                return ['status' => false, 'message' => "Duplicate transaction", 'httpCode' => 400];
            }

            if ($this->fromTempWebhook == 'false') {

                WebhookLog::create([
                    'webhook_type'     => $this->webhookType,
                    'unique_reference' => $this->transactionReference,
                    'body'             => $this->fromTempWebhook == 'true' ? json_encode($this->tempWebookRequest) : json_encode($this->request->all()),
                ]);
            }
            if ($this->webhookType == 'providus') {
                $providusController = new ProvidusController();
                // $account            = $providusController->getVirtualAccount($this->virtualAccount);
                $getVirtualAccount = $this->virtualAccount;
            }
            if ($this->webhookType == 'kuda') {
                $getVirtualAccount = $this->getVirtualAccountNumber($this->request);
            }

            logInfo($this->virtualAccount, "Webhook virtal hook");

            if (isset($getVirtualAccount)) {
                $account = wb_account_details::where('virtual_accnum', $getVirtualAccount)->first();
            } else {
                $account = wb_account_details::where('virtual_accnum', $this->virtualAccount)->first();
            }

            if ($account) {

                DB::beginTransaction();

                $checkOne = WebhookLog::where(['unique_reference' => $this->transactionReference])->count();
                if ($checkOne > 1) {
                    return ['status' => false, 'message' => "Duplicate transaction", 'httpCode' => 400];
                }

                $customer = $account->user;

                $accountTier = $customer->tier;

                logInfo($this->amount, "The Amount Log Tier");
                logInfo($this->fromTempWebhook, "The from webhook tier");
                logInfo($accountTier->max_amount_received, "The Max Amount Log Tier");

                if ($this->fromTempWebhook == 'false') {
                    if ($this->amount > $accountTier->max_amount_received || (($this->amount / 100) > $accountTier->max_amount_received && $this->webhookType == 'kuda')) {

                        $theAmount = $this->webhookType == 'kuda' ? ($this->amount / 100) : $this->amount;

                        try {

                            $tempWebookRequest = TempWebhook::create([
                                'webhook_type'     => $this->webhookType,
                                'amount'           => $theAmount,
                                'company_id'       => $customer->company_id,
                                'description'      => $this->description,
                                'charge'           => $this->wbaCharge,
                                'transaction_date' => $this->transactionDate,
                                'account_number'   => $this->virtualAccount,
                                'unique_reference' => $this->transactionReference,
                                'status_id'        => 7,
                                'body'             => json_encode($this->request->all()),
                            ]);

                            DB::commit();
                            logInfo($tempWebookRequest, "temp webhook things");

                            //      return ['status' => true, 'httpCode' => 200, 'message' => "Success"];
                        } catch (Exception $e) {
                            logInfo($e->getMessage(), "Tier Amount error message");
                        }
                    }
                }

                if ($this->webhookType == 'kuda' && $this->fromTempWebhook == 'true') {

                    $kudaAmount = $this->fromTempWebhook == 'true' ? $this->amount * 100 : $this->amount;

                    $kudaAmount = $kudaAmount >= 1000000 ? ($kudaAmount - 5000) : $kudaAmount;

                    $kudaController = new KudaController();

                    $verifyPaymentResponse = $kudaController->webhookWithdraw($this->transactionReference, $kudaAmount, $this->description, $account->acc_reference);

                    logInfo($verifyPaymentResponse, "Transaction Kuda Response");

                    \logInfo($kudaAmount, "Kuda Amount");

                    $verifyPaymentResponse['httpCode'] = 400;

                    if (! $verifyPaymentResponse['status']) {
                        return $verifyPaymentResponse;
                    }
                }

                //  $mainAccount = $this->webhookType == 'monnify' ? wb_account_details::where('acc_reference', $this->accountReference)->first() : wb_account_details::where('virtual_accnum', $this->virtualAccount)->orWhere('virtual_accnum', $getVirtualAccount)->first();
                if ($this->webhookType == 'monnify') {
                    $mainAccount = wb_account_details::where('acc_reference', $this->accountReference)->first();
                } else {
                    if (isset($getVirtualAccount)) {
                        $mainAccount = wb_account_details::where('virtual_accnum', $getVirtualAccount)->first();
                    } else {
                        {
                            $mainAccount = wb_account_details::where('virtual_accnum', $this->virtualAccount)->first();
                        }
                    }
                }

                $companyId = $account->user->company->id;
                $branchId  = $account->user->branch->id;
                $accountId = $mainAccount->id;
                $userId    = $account->user_id;

                if ($this->fromTempWebhook == 'false') {
                    $customerAmount = in_array($this->webhookType, ['kuda', 'paystack'])
                        ? ($this->amount / 100)
                        : $this->amount;

                    $originalAmount = in_array($this->webhookType, ['kuda', 'paystack'])
                        ? ($this->amount / 100)
                        : $this->amount;
                } else {
                    $customerAmount = $this->amount;

                    $originalAmount = $this->amount;
                }

                $customerCharge = 0;

                $companySettings = company_settings::where('company_id', $companyId)->first();

                if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                    if ($companySettings->incoming_transfer_charge_state == 0) {
                        $customerCharge  = $companySettings->incoming_transfer_charge_amount;
                        $customerAmount -= $customerCharge;
                    } else {
                        $customerCharge  = $companySettings->incoming_transfer_charge_percentage * 0.01 * $originalAmount;
                        $customerCharge  = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                        $customerAmount -= $customerCharge;
                    }
                }

                // logInfo($customerCharge, "Webhook Customer charge");
                // logInfo($this->wbaCharge, "Webhook WBA Company charge");

                $newdescription = str_replace("| [$customer->account_reference] TO WOBILO AFRICA LIMITED", "", $this->description);

                if ($customer->user_type_id == '4') {

                    $checkSendWebhookNotification = $this->webhookType == "kuda" ? $this->forwardToWebhookUrlAndSweepAccount($this->request->all(), $companyId) : $this->forwardToWebhookUrlAndSweepAccountSafeHaven($companyId);

                    if ($checkSendWebhookNotification) {
                        \logInfo("Sweeping customer account but Crediting company with Charge", "Crediting Status");
                        $creditCustomer = sweepCustomerAccountAndCreditCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
                    } else {
                        \logInfo("Crediting Both customer and Company with charget", "Crediting Status");
                        $creditCustomer = \creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
                    }
                } else {
                    $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
                }

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "company_amount"         => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => $newdescription,
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "transaction_reference"  => $this->transactionReference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => $this->transactionDate,
                    "created_by"             => 1,
                ]);

                $transactionId = generateTransactionReference();

                if ($customerCharge > 0) {

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "company_amount"         => $this->wbaCharge,
                        "balance_after"          => $creditCustomer['charge_balance_after'],
                        'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                        'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                        "description"            => $this->webhookType == "cgate" ? "USSD funding charges" : "Charge from payment received",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 125,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => $this->transactionDate,
                        "created_by"             => 1,
                    ]);
                } else {

                    if ($this->wbaCharge > 0) {

                        $transactionId = generateTransactionReference();

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['company_charge_balance_before'],
                            "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                            "amount"                 => $this->wbaCharge,
                            "company_amount"         => $this->wbaCharge,
                            "balance_after"          => $creditCustomer['company_charge_balance_after'],
                            "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => $this->transactionDate,
                            "created_by"             => 1,
                        ]);
                    }
                }

                $balanceAfter = $creditCustomer['charge_balance_after'];
                $desc         = $newdescription;
                $currentDate  = Carbon::now();

                DB::commit();

                // if ($this->webhookType == "amucha") {
                //     $forwardWebhookResponse = forwardRequestToWirelesspay('user/card/webhook', $this->request->all());

                //     logInfo($forwardWebhookResponse, "Response of forwarded Amucha Webhook");
                //     return $forwardWebhookResponse;
                // }

                $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date: $this->transactionDate Ref:$this->transactionReference";

                $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                logInfo(json_encode($smsstate));

                // $this->sendNotificationToErrandPay($userId);

                if ($this->webhookType == 'providus') {
                    return ["responseCode" => "00", "requestSuccessful" => true, "sessionId" => $this->transactionReference, "responseMessage" => "success"];
                }
                return ['status' => true, 'httpCode' => 200, 'message' => "Success"];
            } else {

                DB::beginTransaction();

                $transactionId = generateTransactionReference();

                if ($this->webhookType == "cgate") {
                    if (strlen($this->virtualAccount) == 5) {
                        $accountCode   = (int) $this->virtualAccount;
                        $companyWallet = company_wallet::where('company_id', $accountCode)->first();
                    } else {
                        $loanRecord    = loan::where('ref', $this->virtualAccount)->first();
                        $companyWallet = $loanRecord->user->company->wallet;
                    }
                } else {
                    if ($this->webhookType == 'kuda') {
                        $getVirtualAccount = $this->getVirtualAccountNumber($this->request);
                    } else if ($this->webhookType == 'providus') {
                        $providusController = new ProvidusController();
                        //  $account            = $providusController->getVirtualAccount($this->virtualAccount);
                        //  $getVirtualAccount  = $account['wallet']['accountNumber'];
                        $getVirtualAccount = $this->virtualAccount;
                    } else {
                        $getVirtualAccount = null;
                    }

                    $companyWallet = company_wallet::where('vaccount', $this->virtualAccount)->orWhere('vaccount', $getVirtualAccount)->first();
                }

                // if (!$companyWallet) {
                //     $forwardWebhookResponse = forwardRequestToWirelesspay('user/ussd/webhook', $this->request->all());

                //     return $forwardWebhookResponse;
                // }

                logInfo("check company id");

                $companyId = $companyWallet->company_id;

                logInfo($companyId, "company id");

                if ($this->webhookType == 'kuda') {
                    $originalAmount = $this->amount / 100;
                } else {
                    $originalAmount = $this->amount;
                }

                \logInfo($this->wbaCharge, "Company Charge");

                $newdescription = str_replace("TO WOBILO AFRICA LIMITED", "", $this->description);

                $creditCompany = creditCompanyWithCharge($companyId, $originalAmount, $this->wbaCharge);

                $transaction = transactions::create([
                    "balance_before"         => $creditCompany['balance_before'],
                    "company_balance_before" => $creditCompany['balance_before'],
                    "amount"                 => $originalAmount,
                    "company_amount"         => $originalAmount,
                    "balance_after"          => $creditCompany['balance_after'],
                    "company_balance_after"  => $creditCompany['balance_after'],
                    "description"            => $newdescription,
                    "company_id"             => $companyId,
                    "transaction_reference"  => $this->transactionReference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => $this->transactionDate,
                    "created_by"             => 1,
                ]);

                if ($this->wbaCharge > 0) {
                    $transactionId = generateTransactionReference();

                    $transaction = transactions::create([
                        "company_balance_before" => $creditCompany['charge_balance_before'],
                        "balance_before"         => $creditCompany['charge_balance_before'],
                        "amount"                 => $this->wbaCharge,
                        "company_amount"         => $this->wbaCharge,
                        "company_balance_after"  => $creditCompany['charge_balance_after'],
                        "balance_after"          => $creditCompany['charge_balance_after'],
                        "description"            => $this->webhookType == "cgate" ? "Charge for Funding Wallet via USSD" : "Charge from payment received",
                        "company_id"             => $companyId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => $this->webhookType == "cgate" ? 99 : 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);
                }

                $balanceAfter = $creditCompany['charge_balance_after'];
                $desc         = $newdescription;
                $currentDate  = Carbon::now();

                $adminUser = User::where('company_id', $companyId)->first();

                $company = Company::find($companyId);

                if ($company->status_id == 10) {

                    $flutterwaveController = new FlutterwaveController();

                    $getExchangeRate = $flutterwaveController->getExchangeRater(15, "NGN", "USD");

                    if (! $getExchangeRate['status']) {
                        return \response()->json($getExchangeRate);
                    }

                    $finalAmount = $getExchangeRate['data']['amount'];

                    if ($balanceAfter > $finalAmount) {

                        $debitCustomerAndCompany = debitCompanyWithCharge($companyId, $finalAmount, 0);

                        $setupTransactionId = generateTransactionReference();

                        $transaction = transactions::create([
                            "balance_before"         => 0,
                            "amount"                 => 0,
                            "balance_after"          => 0,
                            "company_balance_before" => $debitCustomerAndCompany['balance_before'],
                            "company_amount"         => $finalAmount,
                            "company_balance_after"  => $debitCustomerAndCompany['balance_after'],
                            "description"            => "Setup fee payment", //airtime and data
                            "transaction_reference"  => $setupTransactionId,
                            "transaction_remarks_id" => 2,
                            "company_id"             => $companyId,
                            "transaction_type_id"    => 57,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => $adminUser->id,
                        ]);

                        $company->status_id = 7;
                        $company->save();
                    }
                }

                $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$this->transactionDate Ref:$this->transactionReference";

                DB::commit();
                // $smsstate = sendSMS($adminUser->id, $message, true, true, 'Credit Transaction', '', 0, false);

                if ($this->webhookType == 'providus') {
                    return ["responseCode" => "00", "requestSuccessful" => true, "sessionId" => $this->transactionReference, "responseMessage" => "success"];
                }

                return ['status' => true, 'httpCode' => 200, 'message' => "Success"];
            }
        } catch (Exception $e) {

            DB::rollBack();
            logInfo($e->getMessage() . $e->getLine(), "$this->webhookType WebHook laravel error");

            // return ['status' => false,  'message' => "Error Crediting account"];
            return ['responseCode' => "02", "requestSuccessful" => true, 'sessionId' => $this->request->sessionId, 'responseMessage' => "rejected transaction"];

        }
    }*/

    public function handleWebhook()
    {
        try {

            $this->validateDuplicate();

            $this->logWebhook();

            $account = $this->resolveVirtualAccount();

            return $account
                ? $this->processCustomerWebhook($account)
                : $this->processCompanyWebhook();

        } catch (Exception $e) {
            DB::rollBack();
            logInfo($e->getMessage() . $e->getLine(), "$this->webhookType WebHook Error");

            return $this->respondFailure();
        }
    }

    /* ============================================================
       DUPLICATE VALIDATION
    ============================================================ */

    private function validateDuplicate()
    {
        if (transactions::where('transaction_reference', $this->transactionReference)->exists()) {
            if ($this->webhookType == 'providus') {
                return ["responseCode" => "01", "requestSuccessful" => true, "sessionId" => $this->transactionReference, "responseMessage" => "duplicate transaction"];
            }
            return ['status' => false, 'message' => "Duplicate transaction", 'httpCode' => 400];
        }

        if (
            WebhookLog::where('unique_reference', $this->transactionReference)->exists()
            && $this->fromTempWebhook == 'false'
        ) {
            return ['status' => false, 'message' => "Duplicate transaction", 'httpCode' => 400];
        }
    }

    /* ============================================================
       LOG WEBHOOK
    ============================================================ */

    private function logWebhook()
    {

        if ($this->webhookType == 'kuda' && $this->fromTempWebhook == 'true') {

            $kudaAmount = $this->fromTempWebhook == 'true' ? $this->amount * 100 : $this->amount;

            $kudaAmount = $kudaAmount >= 1000000 ? ($kudaAmount - 5000) : $kudaAmount;

            $kudaController = new KudaController();

            $verifyPaymentResponse = $kudaController->webhookWithdraw($this->transactionReference, $kudaAmount, $this->description, $account->acc_reference);

            logInfo($verifyPaymentResponse, "Transaction Kuda Response");

            \logInfo($kudaAmount, "Kuda Amount");

            $verifyPaymentResponse['httpCode'] = 400;

            if (! $verifyPaymentResponse['status']) {
                return $verifyPaymentResponse;
            }
        }

        if ($this->fromTempWebhook == 'false') {
            WebhookLog::create([
                'webhook_type'     => $this->webhookType,
                'unique_reference' => $this->transactionReference,
                'body'             => $this->fromTempWebhook == 'true' ? json_encode($this->tempWebookRequest) : json_encode($this->request->all()),
            ]);
        }

    }

    /* ============================================================
       RESOLVE ACCOUNT
    ============================================================ */

    private function resolveVirtualAccount()
    {
        $accountNumber = $this->virtualAccount;

        if ($this->webhookType === 'kuda') {
            $accountNumber = $this->getVirtualAccountNumber($this->request);
        }

        if ($this->webhookType === 'providus') {
            $accountNumber = $this->virtualAccount;
        }

        if ($this->webhookType == 'monnify') {
            $accountNumber = wb_account_details::where('acc_reference', $this->accountReference)->first();
        }

        logInfo($accountNumber, "Webhook virtal hook");

        return wb_account_details::where('virtual_accnum', $accountNumber)->orWhere('virtual_accnum', ($accountNumber . "_"))->first();

    }

    /* ============================================================
       CUSTOMER FLOW
    ============================================================ */

    private function processCustomerWebhook($account)
    {
        DB::beginTransaction();

        $customer  = $account->user;
        $companyId = $customer->company_id;
        $branchId  = $customer->branch->id;
        $accountId = $account->id;

        $originalAmount = $this->normalizeAmount();

        $this->validateTierLimit($customer, $originalAmount);

        [$customerCharge, $customerAmount] =
        $this->calculateCustomerCharge($companyId, $originalAmount);

        $credit = $this->applyCreditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $customer);

        $mainTransaction = $this->createMainTransaction(
            $credit,
            $originalAmount,
            $companyId,
            $branchId,
            $accountId,
            $customer
        );

        $this->createChargeTransaction(
            $credit,
            $customerCharge,
            $mainTransaction,
            $companyId,
            $branchId,
            $accountId
        );

        DB::commit();

        $this->sendCreditSMS($customer, $originalAmount, $credit['charge_balance_after']);

        return $this->respondSuccess();
    }

    /* ============================================================
       COMPANY FLOW
    ============================================================ */

    private function processCompanyWebhook()
    {
        DB::beginTransaction();

        if ($this->webhookType == "cgate") {
            if (strlen($this->virtualAccount) == 5) {
                $accountCode   = (int) $this->virtualAccount;
                $companyWallet = company_wallet::where('company_id', $accountCode)->first();
            } else {
                $loanRecord    = loan::where('ref', $this->virtualAccount)->first();
                $companyWallet = $loanRecord->user->company->wallet;
            }
        } else {
            if ($this->webhookType == 'kuda') {
                $getVirtualAccount = $this->getVirtualAccountNumber($this->request);
            } else if ($this->webhookType == 'providus') {
                $providusController = new ProvidusController();
                //  $account            = $providusController->getVirtualAccount($this->virtualAccount);
                //  $getVirtualAccount  = $account['wallet']['accountNumber'];
                $getVirtualAccount = $this->virtualAccount;
            } else {
                $getVirtualAccount = null;
            }
            $companyWallet = company_wallet::where('vaccount', $this->virtualAccount)->orWhere('vaccount', $getVirtualAccount)->orWhere('vaccount', ($getVirtualAccount . "_"))->first();
        }
        logInfo("check company id");

        $companyId = $companyWallet->company_id;

        logInfo($companyId, "company id");

        $originalAmount = $this->normalizeAmount();

        $newdescription = str_replace("TO WOBILO AFRICA LIMITED", "", $this->description);

        $credit = creditCompanyWithCharge(
            $companyId,
            $originalAmount,
            $this->wbaCharge
        );

        $transaction = transactions::create([
            "balance_before"         => $credit['balance_before'],
            "company_balance_before" => $credit['balance_before'],
            "amount"                 => $originalAmount,
            "company_amount"         => $originalAmount,
            "balance_after"          => $credit['balance_after'],
            "company_balance_after"  => $credit['balance_after'],
            "description"            => $newdescription,
            "company_id"             => $companyId,
            "transaction_reference"  => $this->transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
            "credit_debit_type_id"   => 1,
            "created_date"           => $this->transactionDate,
            "created_by"             => 1,
        ]);

        if ($this->wbaCharge > 0) {
            $transactionId = generateTransactionReference();

            $transaction = transactions::create([
                "company_balance_before" => $credit['charge_balance_before'],
                "balance_before"         => $credit['charge_balance_before'],
                "amount"                 => $this->wbaCharge,
                "company_amount"         => $this->wbaCharge,
                "company_balance_after"  => $credit['charge_balance_after'],
                "balance_after"          => $credit['charge_balance_after'],
                "description"            => $this->webhookType == "cgate" ? "Charge for Funding Wallet via USSD" : "Charge from payment received",
                "company_id"             => $companyId,
                "parent_transaction_id"  => $transaction->id,
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $this->webhookType == "cgate" ? 99 : 27,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => 1,
            ]);
        }

        $balanceAfter = $credit['charge_balance_after'];
        $desc         = $newdescription;
        $currentDate  = Carbon::now();
        $this->processCompanyInternational($companyId, $balanceAfter);
        $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$this->transactionDate Ref:$this->transactionReference";

        DB::commit();

        return $this->respondSuccess();
    }

    private function normalizeAmount()
    {
        return in_array($this->webhookType, ['kuda', 'paystack'])
            ? $this->amount / 100
            : $this->amount;
    }

    private function validateTierLimit($customer, $amount)
    {
        logInfo($amount, "The Amount Log Tier");
        logInfo($this->fromTempWebhook, "The from webhook tier");
        $accountTier = $customer->tier;
        logInfo($accountTier->max_amount_received, "The Max Amount Log Tier");

        if ($amount > $accountTier->max_amount_received || (($amount) > $accountTier->max_amount_received && $this->webhookType == 'kuda')) {

            try {
                $tempWebookRequest = TempWebhook::create([
                    'webhook_type'     => $this->webhookType,
                    'amount'           => $amount,
                    'company_id'       => $customer->company_id,
                    'description'      => $this->description,
                    'charge'           => $this->wbaCharge,
                    'transaction_date' => $this->transactionDate,
                    'account_number'   => $this->virtualAccount,
                    'unique_reference' => $this->transactionReference,
                    'status_id'        => 7,
                    'body'             => json_encode($this->request->all()),
                ]);

                logInfo($tempWebookRequest, "temp webhook things");

                //      return ['status' => true, 'httpCode' => 200, 'message' => "Success"];
            } catch (Exception $e) {
                logInfo($e->getMessage(), "Tier Amount error message");
            }

        }
    }

    private function calculateCustomerCharge($companyId, $amount)
    {
        $settings = company_settings::where('company_id', $companyId)->first();

        $charge = 0;

        if ($settings->who_to_pay_incoming_transfer_charge == 1) {
            if ($settings->incoming_transfer_charge_state == 0) {
                $charge = $settings->incoming_transfer_charge_amount;
            } else {
                $charge = ($settings->incoming_transfer_charge_percentage / 100) * $amount;
                $charge = min($charge, $settings->incoming_transfer_charge_capped_at);
            }
        }

        return [$charge, $amount - $charge];
    }

    private function applyCreditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $customer)
    {

        if ($customer->user_type_id == '4') {

            $checkSendWebhookNotification = $this->webhookType == "kuda" ? $this->forwardToWebhookUrlAndSweepAccount($this->request->all(), $companyId) : $this->forwardToWebhookUrlAndSweepAccountSafeHaven($companyId);

            if ($checkSendWebhookNotification) {
                \logInfo("Sweeping customer account but Crediting company with Charge", "Crediting Status");
                $creditCustomer = sweepCustomerAccountAndCreditCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
            } else {
                \logInfo("Crediting Both customer and Company with charget", "Crediting Status");
                $creditCustomer = \creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
            }
        } else {
            $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $this->wbaCharge);
        }
        return $creditCustomer;
    }

    private function createMainTransaction($credit, $amount, $companyId, $branchId, $accountId, $customer)
    {
        $newdescription = str_replace("| [$customer->account_reference] TO WOBILO AFRICA LIMITED", "", $this->description);
        return transactions::create([

            "balance_before"         => $credit['balance_before'],
            "amount"                 => $amount,
            "company_amount"         => $amount,
            "balance_after"          => $credit['balance_after'],
            'company_balance_before' => $credit['company_balance_before'],
            'company_balance_after'  => $credit['company_balance_after'],
            "description"            => $newdescription,
            "company_id"             => $companyId,
            "branch_id"              => $branchId,
            "account_id"             => $accountId,
            "transaction_reference"  => $this->transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
            "credit_debit_type_id"   => 1,
            "created_date"           => $this->transactionDate,
            "created_by"             => 1,
        ]);

    }

    private function createChargeTransaction(
        $credit,
        $charge,
        $parentTransaction,
        $companyId,
        $branchId,
        $accountId
    ) {
        if ($charge <= 0) {
            return;
        }

        if ($charge > 0) {

            return transactions::create([
                "balance_before"         => $credit['charge_balance_before'],
                "amount"                 => $charge,
                "company_amount"         => $this->wbaCharge,
                "balance_after"          => $credit['charge_balance_after'],
                'company_balance_before' => $credit['company_charge_balance_before'],
                'company_balance_after'  => $credit['company_charge_balance_after'],
                "description"            => $this->webhookType == "cgate" ? "USSD funding charges" : "Charge from payment received",
                "company_id"             => $companyId,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "parent_transaction_id"  => $parentTransaction->id,
                "transaction_reference"  => generateTransactionReference(),
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 125,
                "credit_debit_type_id"   => 2,
                "created_date"           => $this->transactionDate,
                "created_by"             => 1,
            ]);
        } else {

            if ($this->wbaCharge > 0) {

                $transactionId = generateTransactionReference();

                $transaction = transactions::create([
                    "balance_before"         => $credit['company_charge_balance_before'],
                    "company_balance_before" => $credit['company_charge_balance_before'],
                    "amount"                 => $this->wbaCharge,
                    "company_amount"         => $this->wbaCharge,
                    "balance_after"          => $credit['company_charge_balance_after'],
                    "company_balance_after"  => $credit['company_charge_balance_after'],
                    "description"            => "Charge from payment received",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "parent_transaction_id"  => $parentTransaction->id,
                    "transaction_reference"  => generateTransactionReference(),
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 27,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => $this->transactionDate,
                    "created_by"             => 1,
                ]);
            }
        }

        $balanceAfter = $creditCustomer['charge_balance_after'];
        $desc         = $newdescription;
        $currentDate  = Carbon::now();
    }

    private function sendCreditSMS($customer, $amount, $balanceAfter)
    {
        $message = "Credit Amt:$amount Avail Bal:$balanceAfter Ref:$this->transactionReference";
        sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);
    }

    private function respondSuccess()
    {
        if ($this->webhookType === 'providus') {
            return [
                "responseCode"      => "00",
                "requestSuccessful" => true,
                "sessionId"         => $this->transactionReference,
                "responseMessage"   => "success",
            ];
        }

        return ['status' => true, 'httpCode' => 200, 'message' => "Success"];
    }

    private function respondFailure()
    {
        return [
            "responseCode"      => "02",
            "requestSuccessful" => true,
            "sessionId"         => $this->transactionReference,
            "responseMessage"   => "rejected transaction",
        ];
    }

    private function processCompanyInternational($companyId, $balanceAfter)
    {
        $adminUser = User::where('company_id', $companyId)->first();

        $company = Company::find($companyId);

        if ($company->status_id == 10) {

            $flutterwaveController = new FlutterwaveController();

            $getExchangeRate = $flutterwaveController->getExchangeRater(15, "NGN", "USD");

            if (! $getExchangeRate['status']) {
                return \response()->json($getExchangeRate);
            }

            $finalAmount = $getExchangeRate['data']['amount'];

            if ($balanceAfter > $finalAmount) {

                $debitCustomerAndCompany = debitCompanyWithCharge($companyId, $finalAmount, 0);

                $setupTransactionId = generateTransactionReference();

                $transaction = transactions::create([
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_balance_before" => $debitCustomerAndCompany['balance_before'],
                    "company_amount"         => $finalAmount,
                    "company_balance_after"  => $debitCustomerAndCompany['balance_after'],
                    "description"            => "Setup fee payment", //airtime and data
                    "transaction_reference"  => $setupTransactionId,
                    "transaction_remarks_id" => 2,
                    "company_id"             => $companyId,
                    "transaction_type_id"    => 57,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $adminUser->id,
                ]);

                $company->status_id = 7;
                $company->save();
            }
        }
    }

    public function sendNotificationToErrandPay($userId)
    {
        $terminal = AgentTerminal::where('user_id', $userId)->first();

        if ($terminal) {

            $body = [
                "TransactionReference"   => $this->transactionReference,
                "SenderAccountNumber"    => $this->virtualAccount,
                "SenderAccountName"      => $this->virtualAccount,
                "ResponseCode"           => "00",
                "DateOfTransaction"      => $this->transactionDate,
                "OriginatorBank"         => "Big Bank",
                "RecipientAccountNumber" => $this->virtualAccount,
                "RecipientAccountName"   => $this->virtualAccount,
                "Amount"                 => $this->webhookType == "kuda" ? $this->amount / 100 : $this->amount,
            ];

            $cgateController = new CgateController();

            $notificationResult = $cgateController->sendTransactionNotification($body);
        }
    }

    public function verifyIncomingWebhook(Request $request)
    {
        return [];
    }

    public function forwardToWebhookUrlAndSweepAccount($incomingRequest, $companyId)
    {

        $sweepAccount = false;

        try {

            $checkWebhook = ApiKey::where('company_id', $companyId)->first();

            if ($checkWebhook) {
                if ($checkWebhook->send_notification == 1) {
                    $sweepAccount = true;

                    $body = [
                        "account_number"        => $incomingRequest['nuban'],
                        "amount"                => $incomingRequest['amount'],
                        "transaction_reference" => $incomingRequest['account_reference'],
                        "narration"             => "Funds received from " . $incomingRequest['source_account_name'],
                        "status"                => $incomingRequest['status'],
                        "transaction_date"      => $incomingRequest['transaction_time'],
                    ];

                    $header = [
                        'Content-Type' => 'application/json',
                        'ApiKey'       => $checkWebhook->api_key,
                    ];
                    $response = Http::withHeaders($header)->post($checkWebhook->webhook_url, $body);

                    \logInfo($response->json(), "Forwarded webhook Response");
                } else {
                    \logInfo("Not sending wehook from settings");
                }
            } else {
                \logInfo("Not sending wehook");
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Forward Webhook Error");
        }
        return $sweepAccount;
    }

    public function forwardToWebhookUrlAndSweepAccountSafeHaven($companyId)
    {

        $sweepAccount = false;

        try {

            $checkWebhook = ApiKey::where('company_id', $companyId)->first();

            if ($checkWebhook) {
                if ($checkWebhook->send_notification == 1) {
                    $sweepAccount = true;

                    $body = [
                        "account_number"        => $this->virtualAccount,
                        "amount"                => $this->amount,
                        "transaction_reference" => $this->transactionReference,
                        "narration"             => $this->description,
                        "status"                => "Success",
                        "transaction_date"      => $this->transactionDate,
                    ];

                    $header = [
                        'Content-Type' => 'application/json',
                        'ApiKey'       => $checkWebhook->api_key,
                    ];
                    $response = Http::withHeaders($header)->post($checkWebhook->webhook_url, $body);

                    \logInfo($response->json(), "Forwarded webhook Response");
                } else {
                    \logInfo("Not sending wehook from settings");
                }
            } else {
                \logInfo("Not sending wehook");
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Forward Webhook Error");
        }
        return $sweepAccount;
    }

    public function handleSudoWebhook(Request $request)
    {
        $safehaven = new SafehavenController();

        $this->transactionReference = $request->input('data.object.transactionMetadata.reference');
        logInfo(json_encode($this->transactionReference), "Sudo Transaction Reference");

        $requestResponse = $request->all();
        logInfo(json_encode($requestResponse), "request Response");

        if ($requestResponse['type'] == "card.balance") {
            $this->accountReference = $request->input('data.object._id');
        }

        if ($requestResponse['type'] == "authorization.request") {
            $this->accountReference = $request->input('data.object.card._id');
        }

        if ($requestResponse['type'] == "transaction.created") {
            $this->accountReference = $request->input('data.object.card');
        }

        if ($requestResponse['type'] == "transaction.refund") {
            $this->accountReference = $request->input('data.object.card');
        }

        logInfo($this->accountReference, "Card Reference");

        $user_id = $safehaven->getUserCard($this->accountReference);
        logInfo($user_id, "User Card username ID");

        $user = $this->getUser($user_id);
        logInfo($user->first_name, "User first name");

        $virtualAccount = $user->virtual_account;
        logInfo($virtualAccount, "User Virtual Accont");

        $mainAccount = $this->getMainAccount($virtualAccount);
        logInfo(json_encode($mainAccount), "Virtual Accont Object");
        $companyId = $mainAccount->user->company->id;
        $branchId  = $mainAccount->user->branch->id;
        $accountId = $mainAccount->id;
        $userId    = $mainAccount->user_id;

        $user_wallet = $this->getUserWallet($mainAccount);

        if ($requestResponse['type'] == "card.balance") {
            return $safehaven->getSudoUserCardBalance($user_wallet);
        }

        if ($requestResponse['type'] == "authorization.request") {
            return $safehaven->getSudoUserAuthorization($request, $user_wallet, $companyId, $branchId, $user);
        }

        if ($requestResponse['type'] == "transaction.created") {
            return $safehaven->getSudoUserTransaction($request, $user_wallet, $companyId, $branchId, $user, $this->transactionReference);
        }

        if ($requestResponse['type'] == "transaction.refund") {
            return $safehaven->getSudoUserRefund($request, $user_wallet, $companyId, $branchId, $this->transactionReference, $user);
        }

    }

    public function handleMapleradWebhook()
    {
        $requestResponse = $request->all();
        logInfo(json_encode($requestResponse), "request Response");
    }

    public function handleNinePSBWebhook(Request $request)
    {

        // Get credentials from header
        $username = $request->getUser();
        $password = $request->getPassword();

        logInfo(json_encode($username), "request username");
        logInfo(json_encode($password), "request password");

        // Your expected credentials (store in .env)
        $validUser = config('ninePSB.username');
        $validPass = config('ninePSB.password');

        if ($username !== $validUser || $password !== $validPass) {
            return [
                'success' => false,
                'status'  => 'error',
                'code'    => '401',
                'message' => 'Unauthorized',

            ];
        }

        $requestResponse = $request->all();
        logInfo(json_encode($requestResponse), "request Response");

        return ["success" => true, 'status' => "success", 'code' => '00', 'message' => 'Acknowledged', 'httpCode' => true];
    }

    public function handleFincraWebhook($request)
    {
        $requestResponse = $request->all();
        logInfo(json_encode($requestResponse), "request Response");

        $fincraController = new FincraController();

        if ($requestResponse['event'] == "virtualaccount.approved") {
            return $fincraController->updateForeignVirtualAccount($requestResponset);
        }

    }

    public function handleVPayWebhook()
    {

        try {

            $this->validateDuplicate();

            $this->logWebhook();

            DB::beginTransaction();

            $getVirtualAccount = $this->virtualAccount;

            $companyWallet = company_wallet::where('subscription_vaccount', $this->virtualAccount)->first();

            logInfo("check company id");

            $companyId = $companyWallet->company_id;

            logInfo($companyId, "company id");

            $originalAmount = $this->normalizeAmount();

            $newdescription = str_replace("TO WOBILO AFRICA LIMITED", "", $this->description);
            $service        = "subscription_maintenance";

            $credit = creditCompanyWithService(
                $companyId,
                $originalAmount,
                $service
            );

            $transaction = transactions::create([
                "balance_before"         => $credit['balance_before'],
                "company_balance_before" => $credit['balance_before'],
                "amount"                 => $originalAmount,
                "company_amount"         => $originalAmount,
                "balance_after"          => $credit['balance_after'],
                "company_balance_after"  => $credit['balance_after'],
                "description"            => $newdescription,
                "company_id"             => $companyId,
                "transaction_reference"  => $this->transactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
                "credit_debit_type_id"   => 1,
                "created_date"           => $this->transactionDate,
                "created_by"             => 1,
            ]);

            //    $customer  = $account->user;
            $balanceAfter = $credit['charge_balance_after'];
            $desc         = $newdescription;
            $currentDate  = Carbon::now();
            $message      = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$this->transactionDate Ref:$this->transactionReference";

            DB::commit();
//$this->sendCreditSMS($customer, $amount, $balanceAfter);
            return $this->respondSuccess();

        } catch (Exception $e) {

            logInfo($e->getMessage() . $e->getLine(), "$this->webhookType WebHook Error");

            return $this->respondFailure();
        }
    }

    public function handleSafeHavenWebhook()
    {

        try {

            $this->validateDuplicate();

            $this->logWebhook();

            DB::beginTransaction();

            $getVirtualAccount = $this->virtualAccount;

            $companyWallet = company_wallet::where('card_vaccount', $this->virtualAccount)->first();

            logInfo("check company id");

            $companyId = $companyWallet->company_id;

            logInfo($companyId, "company id");

            $originalAmount = $this->normalizeAmount();

            $newdescription = str_replace("TO WOBILO AFRICA LIMITED", "", $this->description);
            $service        = "atm_naira_card";

            $credit = creditCompanyWithService(
                $companyId,
                $originalAmount,
                $service
            );

            $transaction = transactions::create([
                "balance_before"         => $credit['balance_before'],
                "company_balance_before" => $credit['balance_before'],
                "amount"                 => $originalAmount,
                "company_amount"         => $originalAmount,
                "balance_after"          => $credit['balance_after'],
                "company_balance_after"  => $credit['balance_after'],
                "description"            => $newdescription,
                "company_id"             => $companyId,
                "transaction_reference"  => $this->transactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $this->webhookType == "cgate" ? 97 : 6,
                "credit_debit_type_id"   => 1,
                "created_date"           => $this->transactionDate,
                "created_by"             => 1,
            ]);

            $balanceAfter = $credit['charge_balance_after'];
            $desc         = $newdescription;
            $currentDate  = Carbon::now();
            $message      = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$this->transactionDate Ref:$this->transactionReference";

            DB::commit();

            return $this->respondSuccess();

        } catch (Exception $e) {

            logInfo($e->getMessage() . $e->getLine(), "$this->webhookType WebHook Error");

            return $this->respondFailure();
        }
    }

    private function getVirtualAccountNumber($kudaVirtualNumber)
    {
        if (isset($kudaVirtualNumber->payingBank) && $kudaVirtualNumber->payingBank == "Paycom(OPay)") {
            $virtualAccount = $kudaVirtualNumber->recipientName;
        }
        if (isset($kudaVirtualNumber->recipientName) && (is_numeric($kudaVirtualNumber->recipientName) && (int) $kudaVirtualNumber->recipientName == $kudaVirtualNumber->recipientName)) {
            $virtualAccount = $kudaVirtualNumber->recipientName;
        } else {
            $virtualAccount = $kudaVirtualNumber->senderAccountNumber;
        }

        return $virtualAccount;
    }

    private function getUser($user_id)
    {
        return User::where('id', $user_id)->first();
    }

    private function getMainAccount($virtualAccount)
    {
        return wb_account_details::where('virtual_accnum', $virtualAccount)->first();
    }

    private function getUserWallet($account)
    {
        return user_wallet::where('wb_account_detail_id', $account->id)->lockForUpdate()->first();

    }

}
