<?php
namespace App\Http\Controllers\CustomClasses;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\CgateController;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Models\AgentTerminal;
use App\Models\ApiKey;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\loan;
use App\Models\TempWebhook;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
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

    public function handleWebhook()
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
                        $customerCharge = $companySettings->incoming_transfer_charge_amount;
                        $customerAmount -= $customerCharge;
                    } else {
                        $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $originalAmount;
                        $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
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
        logInfo($this->accountReference, "Card Name");
        logInfo($this->transactionReference, "Masked Pan");
        $requestResponse = $request->all();

        logInfo(json_encode($requestResponse), "request Response");

        $user_id = UserCard::where('card_name', $this->accountReference)->orWhere('masked_pan', $this->transactionReference)->orWhere('card_reference', $this->accountReference)->first()->user_id;

        logInfo($user_id, "User Card username ID");

        $user = User::where('id', $user_id)->first();

        logInfo($user->first_name, "User first name");

        $virtualAccount = $user->virtual_account;

        logInfo($user->$virtualAccount, "User Virtual Accont");

        $account     = wb_account_details::where('virtual_accnum', $virtualAccount)->first();
        $mainAccount = wb_account_details::where('virtual_accnum', $virtualAccount)->first();
        $companyId   = $account->user->company->id;
        $branchId    = $account->user->branch->id;
        $accountId   = $mainAccount->id;
        $userId      = $account->user_id;

        if ($requestResponse['type'] == "card.balance") {
            $this->getSudoUserCardBalance($account);
        }

        if ($requestResponse['type'] == "transaction.created" || $requestResponse['type'] == "authorization.request") {
            $this->getSudoUserAuthorization($request, $account);
        }

        if ($requestResponse['type'] == "transaction.refund") {
            $this->getSudoUserRefund($account);
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

    private function getSudoUserCardBalance($account)
    {
        logInfo(json_encode($account), "Virtual Accont");
        $user_wallet = user_wallet::where('wb_account_detail_id', $account->id)->first();

        logInfo($user_wallet->balance, "Virtual Accont Amount");
        $data = [
            "balance" => $user_wallet->balance,
        ];

    }

    private function getSudoUserAuthorization($request, $account)
    {
        logInfo(json_encode($account), "Virtual Accont");
        $user_wallet = user_wallet::where('wb_account_detail_id', $account->id)->first();

        logInfo($user_wallet->balance, "Virtual Accont Amount");
        $amount = $request->input('data.object.amount');

        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        if ($formatedAmount < $user_wallet->balance) {
            response()->json([
                "statusCode" => 200,
                "data"       => [
                    "responseCode" => "00",
                ],
            ], 406);
        } else {
            response()->json([
                "statusCode" => 400,
                "data"       => [
                    "responseCode" => "51",
                ],
            ], 406);
        }
    }

}
