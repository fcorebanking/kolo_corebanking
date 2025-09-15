<?php
namespace App\Services;

use App\Models\BranchWallet;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

class fortifyTransactionService
{
    public function __construct()
    {

        $this->baseUrl = url('/');
    }

    public function checkCustomerAndCompanyBalance($amount, $user)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                $companyWallet = company_wallet::where('company_id', $user->company_id)->first();

                if ($companyWallet->balance >= $amount) {

                    $response = [
                        "status"          => true,
                        'message'         => "Amount is Valid",
                        'account_id'      => $user->accounts->first()->id,
                        'balance'         => $userWallet->balance,
                        'company_balance' => $companyWallet->balance,
                    ];
                } else {
                    $response = ["status" => false, 'message' => "Switcher Error... Please contact support $companyWallet->balance $amount"];
                }
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function debitCustomerAndCompanyWithCompany4TSCashback($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
    {

        $response = [];

        $currentUser = $user;

        if ($originalAmount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

            if (! $userWallet) {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
            }

            $customerInitialBalance = $userWallet->balance;

            $customerCashbackAmount = $customerCashback * $originalAmount;
            $companyCashbackAmount  = $companyCashback * $originalAmount;

            if ($customerInitialBalance > ($originalAmount - $customerCashbackAmount)) {

                $company = Company::find($companyId);
                if ($company->wallet->balance > ($originalAmount - $companyCashbackAmount)) {

                    $customerBalanceAfter = $customerInitialBalance - $originalAmount;

                    $customerFinalBalance = $userWallet->balance -= ($originalAmount - $customerCashbackAmount);
                    $userWallet->save();
                    if (isset($userWallet->wb_account_details->user->branch_id)) {
                        $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                        $BranchWallet->balance -= $originalAmount;
                        $BranchWallet->save();
                    }
                    $companyInitialBalance = $company->wallet->balance;
                    $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                    $companyFinalBalance = $company->wallet->balance -= ($originalAmount - $companyCashbackAmount);
                    $company->wallet->save();

                    $currentUser->balance_fails = 0;
                    $currentUser->save();

                    $response = [
                        "status"                          => true,
                        'message'                         => "Amount is Valid",
                        'account_id'                      => $customerAccountId,
                        'balance_before'                  => $customerInitialBalance,
                        'balance_after'                   => $customerBalanceAfter,
                        'cashback_balance_before'         => $customerBalanceAfter,
                        'cashback_balance_after'          => $customerFinalBalance,
                        'company_balance_before'          => $companyInitialBalance,
                        'company_amount'                  => $originalAmount,
                        'customer_cashback'               => $customerCashbackAmount,
                        'company_cashback'                => $companyCashbackAmount,
                        'company_balance_after'           => $companyBalanceAfter,
                        'company_cashback_balance_before' => $companyBalanceAfter,
                        'company_cashback_balance_after'  => $companyFinalBalance,
                    ];
                } else {
                    $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
                }
            } else {
                if ($currentUser->balance_fails < 4) {
                    $currentUser->balance_fails += 1;
                    $currentUser->save();
                    $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
                } else {
                    // auth()->user()->status_id = 5;
                    restrictUserAccount(2, "Multiple Insuficient balance trials");

                    $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function directDebitMandate($amount, $user)
    {

        $customerBalance = $this->checkCustomerAndCompanyBalance($amount, $user);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        $transactionId = $this->vtPassRequestId();

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $debitCustomer = $this->debitCustomerAndCompanyWithCompany4TSCashback($wb_account_details->id, $user->company_id, $amount, 0, 0, $user);

        /*   if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }*/

        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(51, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        if (! $debitCustomer['status']) {
            response()->json($debitCustomer, 400);
        }

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "account_id"             => $wb_account_details->id,
            "description"            => "Bill Payment Charge ",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 12,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        return
            [
            "status"  => true,
            'message' => "Data Plan Purchase Successful",
            'data'    => [
                'balance' => $debitCustomer['balance_after'],
            ],
        ];

    }

    public function buyDataBundle($amount, $user)
    {

        $customerBalance = $this->checkCustomerAndCompanyBalance($amount, $user);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        $transactionId = $this->vtPassRequestId();

        $debitCustomer = $this->debitCustomerAndCompanyWithCompany4TSCashback($user->accounts->first()->id, $user->company_id, $amount, 0, 0, $user);

        /*   if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }*/

        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(12, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        if (! $debitCustomer['status']) {
            response()->json($debitCustomer, 400);
        }

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "account_id"             => $transactionPinValidation['account_id'],
            "description"            => "Purchase of Data worth $amount ",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 12,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        return
            [
            "status"  => true,
            'message' => "Data Plan Purchase Successful",
            'data'    => [
                'balance' => $debitCustomer['balance_after'],
            ],
        ];

    }

    public function buyElectricity($user, $amount)
    {

        // $transactionId = $this->randomNumber(12);
        $transactionId = $this->vtPassRequestId();

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(13, $amount);

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $amount;

        $customerBalance = $this->checkCustomerAndCompanyBalance($amount, $user);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $user->company_id, $amount, $companySettings->bills_payment_charge, $wbaCharge);

        /*   if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }*/

        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(14, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Purchase of Electricity worth $amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 14,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $user->company_id);
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
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Electricity Purchase",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        return true;

    }

    public function subscribeCableTV($user, $amount)
    {

        $transactionId = $this->vtPassRequestId();

        $customerBalance = $this->checkCustomerAndCompanyBalance($amount, $user);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }
        //Get WBA Charges
        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(12, ($amount / 100));

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $debitCustomer   = \debitCustomerAndCompanyWithCharge($wb_account_details->id, $user->company_id, $amount, $companySettings->bills_payment_charge, $wbaCharge);
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(13, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([

            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Cable TV Subscription worth $amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 13,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => $user->company_id,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Cable TV Subscription",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "modified_by"            => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);
        return true;
    }

    public function updateTransactionAsSuccessful($transactionId, $balance = 0, $updateBalance = true, $user)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = $user->id;
        $transaction->save();
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

    function checkCompanyBalance($amount, $user)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = company_wallet::where('company_id', $user->company_id)->first();

            if ($userWallet->balance >= $amount) {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => $user->company_id, 'balance' => $userWallet->balance];
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;

    }

}
