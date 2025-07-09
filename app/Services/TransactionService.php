<?php
namespace App\Services;

use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\PreferredEbankingPartner;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
    public function __construct()
    {

        $this->baseUrl = url('/');
    }

    public function initiateTransfer(Request $request, $user)
    {
        $transactionId = generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "transaction_type" => "required|numeric|gt:0",
            "description"      => "string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = \checkCompanyAllowedTransactionStatus($user->company_id);

        if ($checkAllowedTransaction['status'] != 1) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;

        }

        $description = $request->description;

        if ($request->final_amount > 0) {

            if ($request->transaction_type == 3) {
                $companyName     = $user->company->company_name;
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $user->company->wallet->balance,
                    "amount"                 => $request->amount,
                    "description"            => "Funding Admin Wallet",
                    "transaction_reference"  => $transactionId,
                    "branch_id"              => $user->branch_id,
                    "transaction_remarks_id" => 6,
                    "company_id"             => $user->company_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $user->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
                $body   = [
                    "tx_ref"          => $transactionId,
                    "amount"          => $request->amount,
                    "currency"        => $user->company->currency->code,
                    "redirect_url"    => $this->baseUrl . "/" . "company/" . $user->company->short_name . "/transaction/verify-fund-wallet",
                    "payment_options" => "card",
                    "customer"        => [
                        "email"       => $user->email,
                        "phonenumber" => $user->phone,
                        "name"        => $user->first_name . ' ' . $user->last_name,
                    ],
                    "customizations"  => [
                        "title"       => "Funding WBA Wallet",
                        "description" => "Fund Wired Banking Wallet",
                        "logo"        => "https://wiredbanking.ng/img/logo/logo-dark.png",
                    ],
                ];
                $url = 'https://api.flutterwave.com/v3/payments';

                $jsonData = \postRequest($header, $body, $url);

                // {"status":"success","message":"Hosted Link","data":{"link":"https:\/\/ravemodal-dev.herokuapp.com\/v3\/hosted\/pay\/79d6b15bf0bcb701e77d"}}
                return response()->json(["status" => true, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => " " . generateTransactionReference()]);
            } else if ($request->transaction_type == 5) {

                $description = $description ?? "Company Wallet Transfer";

                if ($request->final_amount > 0) {

                    if ($user->company->wallet->balance >= $request->final_amount) {

                        $charge          = 0;
                        $ledger_cr       = null;
                        $ledger_dr       = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, $user->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $transaction = transactions::create([
                            "balance_before"         => $user->company->wallet->balance,
                            "amount"                 => $request->final_amount,
                            "description"            => $description,
                            "transaction_reference"  => $transactionId,
                            "branch_id"              => $user->branch_id,
                            "company_id"             => $user->company_id,
                            "transaction_remarks_id" => 6,
                            "transaction_type_id"    => $request->transaction_type,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => $user->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);

                        $result = [
                            "status"  => true,
                            'message' => "Transaction initialized",
                            'data'    => [
                                'charge'            => $charge ?? 0.00,
                                'transactionId'     => $transaction->id,
                                'transactionTypeId' => $request->transaction_type,
                                'exchangeAmount'    => $request->final_amount,
                                'total'             => $request->finalAmount,
                            ],
                        ];

                        return $result;
                    } else {
                        return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid Transfer amount"], 400);
                }
            } else if ($request->transaction_type == 8 || $request->transaction_type == 19) {

                $amount      = 0;
                $totalAmount = 0;
                $charge      = 834;

                if ($request->transaction_type == 19) {
                    $description = $description ?? "Mobile Money Transfer";
                    $totalAmount = $request->finalAmount;
                    $amount      = $request->finalAmount;
                } else if ($request->transaction_type == 8) {

                    $validateSavingsProduct = $this->accountProductCharges($amount);

                    if (! $validateSavingsProduct['status']) {
                        return $validateSavingsProduct;
                    }

                    $charge = $validateSavingsProduct['data']['charge'];
                    logInfo($charge, "Charge for Transaction type 8");

                    $amount      = $request->final_amount;
                    $totalAmount = $request->final_amount + $charge;
                    $description = $description ?? "You sent money to $request->receipient_name via Bank Transfer";
                }

                $userWallet = user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount, $user);

                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction($user->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                $validateTransferAmount = $this->validateTransfer($request->final_amount, $user);

                if (! $validateTransferAmount['status']) {
                    return $validateTransferAmount;
                }
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $amount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "branch_id"              => $user->branch_id,
                    "company_balance_before" => $customerBalance['company_balance'],
                    "company_amount"         => $amount,
                    "system_description"     => $user->company->company_name . " - " . $user->first_name . " sent money to " . $request->receipient_name,
                    "company_id"             => $user->company_id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 6,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $user->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                $result = [
                    "status"  => true,
                    'message' => "Transaction initialized",
                    'data'    => [
                        'charge'                => $charge ?? 0.00,
                        'transaction_reference' => $transactionId,
                        'transactionTypeId'     => $request->transaction_type,
                        'transfer_amount'       => $amount,
                        'total'                 => $totalAmount,
                    ],
                ];

                return $result;

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else if ($request->transaction_type == 4) {

                $amount      = 0;
                $totalAmount = 0;

                $amount      = $request->final_amount;
                $totalAmount = $request->final_amount;
                $name        = isset($request->receipient_name) ? $request->receipient_name : "N/A";
                $description = "You sent payment to $name $request->description / $request->destination_account ";

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount, $user);

                if (! $customerBalance['status']) {
                    return $customerBalance;
                }

                if (\checkcustomerrestriction($user->id, '2')) {
                    return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                }

                if ($request->destination_account == $user->virtual_account) {
                    return ["status" => false, 'message' => "Cannot transfer to self"];
                }

                $charge = 0;

                $validateTransferAmount = $this->validateTransfer($request->final_amount, $user);

                if (! $validateTransferAmount['status']) {
                    return $validateTransferAmount;
                }
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, $user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $totalAmount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "transaction_reference"  => $transactionId,
                    "company_balance_before" => $customerBalance["company_balance"],
                    "company_amount"         => 0,
                    "company_id"             => $user->company_id,
                    "transaction_remarks_id" => 6,
                    "branch_id"              => $user->branch_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $user->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                $result =
                    [
                    "status"  => true,
                    'message' => "Transaction initialized",
                    'data'    => [
                        'charge'                => $charge ?? 0.00,
                        'transaction_reference' => $transactionId,
                        'transactionTypeId'     => $request->transaction_type,
                        'transfer_amount'       => $amount,
                        'total'                 => $totalAmount,
                    ],
                ];

                return $result;

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else {
                return ["status" => false, 'message' => "Invalid Transaction Type"];
            }
        } else {
            return ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
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

    public function validateTransfer($amount, $user)
    {
        $companySettings = company_settings::where('company_id', $user->company_id)->first();
        $accountTier     = $user->tier;
        logInfo($accountTier, "accountTier Twilio");
        logInfo($user->accounts->first(), "accounts Twilio");
        $wbaAccount = $user->accounts->first();

        if (! $companySettings) {
            return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        if ($amount < $accountTier->minimum_transfer) {
            return ["status" => false, 'message' => "Minimum amount allowed is NGN$accountTier->minimum_transfer"];
        }

        // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $user->accounts->first()->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
        $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->sum('amount');

        $lastTransaction = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->whereDate('created_at', Carbon::today())->orderBy('id', 'DESC')->first();

        if ($lastTransaction) {
            if ($lastTransaction->created_at > Carbon::now()->subSeconds(60)) {
                return ["status" => false, 'message' => "Cannot make multiple transactions within 1 minute"];
            }

            if ($dailySum + $amount > $accountTier->daily_transaction_limit) {
                return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
            }
            if ($sum >= $accountTier->daily_transaction_count) {
                return ["status" => false, 'message' => "Daily Transaction Count Exceeded"];
            }
        }

        return ["status" => \true, 'message' => "Transaction Validated"];
    }

    public function transferToBankAccount($description, $amount, $transaction_reference, $destination_account, $bank_code, $transaction_pin, $user, $receipient_name)
    {

        $charge = 0;

        $currentUser = $user;

        $validateSavingsProduct = $this->accountProductCharges($amount, $user);

        if (! $validateSavingsProduct['status']) {
            return $validateSavingsProduct;
        }

        $charge = isset($validateSavingsProduct['data']['charge']) ? $validateSavingsProduct['data']['charge'] : 0;

        $totalAmount = $amount + $charge;

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($currentUser->company_id);

        if ($checkAllowedTransaction['status'] != 1) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;

        }

        if ($amount > 3000000) {
            return ['status' => false, 'message' => 'Maximum transfer amount is 3,000,000'];
        }
        DB::beginTransaction();
        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePinCheckCustomerBalance($transaction_pin, $totalAmount, $selectedwallet, $user);
        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transaction_reference, 'amount' => $amount])->first();

        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else if ($transaction->transaction_remarks_id == 3) {
                return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
            } else if ($transaction->transaction_remarks_id == 5) {
                return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
            } else if ($transaction->transaction_remarks_id == 1) {
                return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
            } else {

                $transaction->transaction_remarks_id = 1;
                $transaction->save();

                $userWallet = user_wallet::where('wb_account_detail_id', $currentUser->accounts->first()->id)->first();

                if ($userWallet->balance >= $totalAmount) {

                    if (\checkcustomerrestriction($currentUser->id, '2')) {
                        return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                    }

                    $validateTransferAmount = $this->validateTransfer($amount, $user);

                    if (! $validateTransferAmount['status']) {
                        return $validateTransferAmount;
                    }

                    $wbaCharge = getWbaTransferCharges(1, $amount);

                    $wbaCharge = $amount > 1000000 ? 100 : $wbaCharge;

                    // $charge =  $charge < $wbaCharge && $charge > 0 ?  ($wbaCharge + ($wbaCharge * 0.01)) : $charge;

                    $debitCustomer = debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $amount, $charge, $wbaCharge, $user);

                    if (! $debitCustomer["status"]) {
                        return $debitCustomer;
                    }

                    $accountId                   = $debitCustomer['account_id'];
                    $customerBalanceBefore       = $debitCustomer['balance_before'];
                    $customerBalanceAfter        = $debitCustomer['balance_after'];
                    $customerChargeBalanceBefore = $debitCustomer['charge_balance_before'];
                    $customerChargeBalanceAfter  = $debitCustomer['charge_balance_after'];

                    $companyBalanceBefore       = $debitCustomer['company_balance_before'];
                    $companyAmount              = $debitCustomer['company_amount'];
                    $companyBalanceAfter        = $debitCustomer['company_balance_after'];
                    $companyChargeBalanceBefore = $debitCustomer['company_charge_balance_before'];
                    $companyChargeBalanceAfter  = $debitCustomer['company_charge_balance_after'];

                    $preferredEbankingPartner = PreferredEbankingPartner::all();
                    $ebankingService          = new EbankingService($preferredEbankingPartner);

                    $bankAccount = $ebankingService->verifyAccountNumberFull($bank_code, $destination_account);

                    if (! $bankAccount['status']) {
                        $bankAccount['status'] = false;
                        return $bankAccount;
                    }

                    sendSMS($currentUser->id, "Debit Amt:$amount, Desc: App/" . $bankAccount['data']['account_name'] . "$description" . " Avail Bal:" . $debitCustomer['charge_balance_after'] . "Date:" . date('Y-m-d') . " Ref: " . $transaction_reference, true, true, 'Debit Transaction');
                    // $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                    $newdescription = $description == "" ? $currentUser->first_name : $description;

                    $bank = banks::where('bank_code', $bank_code)->orwhere('short_code', $bank_code)->first();

                    $newdescription = $newdescription . "/" . $receipient_name . "/" . $destination_account . " - " . $bank->bank_name;

                    $gatewayDescription = $currentUser->first_name . " " . $currentUser->last_name . " / " . $currentUser->company->company_name;
                    $myDescription      = "You transferred funds to " . $bankAccount['data']['account_name'] . "/" . $destination_account . " / " . $bank->bank_name . " / " . $currentUser->company->company_name;

                    $transaction->description = $myDescription;

                    $transaction->balance_before = $customerBalanceBefore;
                    $transaction->amount         = $amount + $charge;
                    $transaction->balance_after  = $customerChargeBalanceAfter;

                    $transaction->company_balance_before = $companyBalanceBefore;
                    $transaction->company_amount         = $amount + $wbaCharge;
                    $transaction->company_balance_after  = $companyChargeBalanceAfter;

                    $transaction->save();

                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $transaction->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    } else {
                        return ['status' => false, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
                    }

                    $myDescription = $user->first_name . " " . $user->last_name . " / " . $user->company->company_name;
                    // $myDescription .= $request->description == "" ? "" : $request->description;
                    DB::commit();

                    $bankTransferType                       = new BankTransferType();
                    $bankTransferType->bankCode             = $bank_code;
                    $bankTransferType->shortBankCode        = $bank->short_code;
                    $bankTransferType->accountNumber        = $destination_account;
                    $bankTransferType->description          = $gatewayDescription;
                    $bankTransferType->amount               = $amount;
                    $bankTransferType->transactionReference = $transaction->transaction_reference;
                    $bankTransferType->sessionId            = $currentUser->virtual_account;

                    $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

                    // if ($bankTransfer['status'] == "01") {
                    //     $reversalResult = debitReversal($transaction->id, $transaction->account_id, $transaction->company_id, $transaction->amount, $bankTransferInfo->charge + $bankTransferInfo->amount, $bankTransferInfo->transfer_entity_type);
                    //     $bankAccount['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
                    //     $transaction->transaction_remarks_id = 3;
                    //     $transaction->save();

                    //     logInfo($reversalResult, "Reversal of Transaction Result");

                    //     return $bankTransfer;
                    // }

                    if ($bankTransfer['status'] != "00") {
                        $transaction->transaction_remarks_id = 3;
                        $transaction->save();
                        $bankTransfer['status'] = 0;

                        return $bankTransfer;
                    }

                    $transaction->transaction_remarks_id = 2;
                    $transaction->save();

                    $check_transfer_log = Beneficiary::where('accountNo', $destination_account)
                        ->whereNull('transaction_id')
                        ->first();

                    if ($check_transfer_log) {

                    } else {
                        $transfer_log            = new Beneficiary;
                        $transfer_log->name      = $receipient_name;
                        $transfer_log->bank_id   = $bank->id;
                        $transfer_log->accountNo = $destination_account;
                        $transfer_log->user_id   = $user->id;
                        $transfer_log->save();
                    }

                    return [
                        "status"  => true,
                        'message' => "Wallet to Bank Transfer Successful",
                        'data'    => [
                            'balance' => $debitCustomer['charge_balance_after'],
                        ],
                    ];
                } else {
                    return ["status" => false, 'message' => "Insufficient Balance"];
                }
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
        }
    }

    public function accountProductCharges($amount, $user)
    {
        $wbaAccount = $user->accounts->first();

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $wbaAccount->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        logInfo($amountLeft, "Transaction Service Amount left after Deduction");
        logInfo($accoountProduct->minimum_balance, "Transaction Service Amount left after Deduction");

        if ($amount < $accoountProduct->minimum_withdraw) {
            return ["status" => false, 'message' => "Transaction Service Transfer Amount cannot be less than $accoountProduct->minimum_withdraw"];
        }

        if ($amount > $accoountProduct->max_withdraw) {
            return ["status" => false, 'message' => "Transaction Service Transfer Amount cannot exceed $accoountProduct->max_withdraw"];
        }

        if ($accoountProduct->bank_transfer_charge_type == 1) {
            $charge = $amount * ($accoountProduct->percentage_transfer_charge / 100);

            $charge = $charge > $accoountProduct->max_bank_transfer_charge ? $accoountProduct->max_bank_transfer_charge : $charge;
        } else {
            $charge = $accoountProduct->max_bank_transfer_charge;
        }

        return ["status" => true, 'message' => "Transaction Service Savings products validated", 'data' => ['charge' => $charge]];
    }

    public function verifyBankAccount($bank_code, $account_number)
    {

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($bank_code, $account_number);

        logInfo(json_encode($bankAccount), "Transaction Service bank account");

        if (! $bankAccount['status']) {
            return $bankAccount;
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank      = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bank_name = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $accountName = $bankAccount["data"]["account_name"];

        $myAccountName = [];

        $myAccountName = explode(" ", $accountName);

        $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];
        $lastName  = count($myAccountName) > 2 ? (strlen($myAccountName[2]) < 3 ? $myAccountName[1] : $myAccountName[2]) : (count($myAccountName) > 1 ? $myAccountName[1] : "");

        return
            [
            'status'  => true,
            'message' => "Account Verified Successfully",
            'data'    => [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'bank_name'  => $bank_name,

            ],
        ];
    }

    public function getBankCode($account_name)
    {
        logInfo(json_encode($account_name), "Transaction Service account_name");

        $bank_code = banks::whereRaw('LOWER(bank_name) LIKE ?', ["%" . strtolower($account_name) . "%"])->first()->bank_code;

        return $bank_code;
    }

    function validatePinCheckCustomerBalance($transactionPin, $amount, $selectedwallet = null, $user)
    {
        $response = [];

        try {

            if ($amount > 0) {
                $validatePin = User::where(['id' => $user->id, 'pin' => $transactionPin])->first();

                if ($validatePin) {
                    $sourceWbAccount = ($selectedwallet != null) ? $selectedwallet : $user->accounts->first()->id;
                    $userWallet      = user_wallet::where('wb_account_detail_id', $sourceWbAccount)->lockForUpdate()->first();

                    if ($userWallet->balance >= $amount) {
                        if ($user->company->wallet->balance >= $amount) {
                            $user->balance_fails         = 0;
                            $user->transaction_pin_fails = 0;
                            $user->save();
                            $response = ["status" => true, 'message' => "Amount and pin are Valid", 'account_id' => $user->accounts->first()->id, 'balance' => $userWallet->balance];
                        } else {
                            $response = ["status" => false, 'message' => "Switcher Error... Please contact support for assistance"];
                        }
                    } else {
                        if ($user->balance_fails < 4) {
                            $user->balance_fails += 1;
                            $user->save();
                            $response = ["status" => false, 'message' => "Insufficient Funds"];
                        } else {
                            // $user->status_id = 5;
                            restrictUserAccount(2, "Multiple Insuficient balance trials");

                            $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                        }
                    }
                } else {
                    if ($user->transaction_pin_fails < 4) {
                        $user->transaction_pin_fails += 1;
                        $user->save();
                        $response = ["status" => false, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - $user->transaction_pin_fails) . " wrong attempts"];
                    } else {
                        restrictUserAccount(2, "Multiple Invalid transaction pin");
                        $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                    }
                }
            } else {
                $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "customer Debit error");
            $response = ["status" => false, 'message' => "Server Error"];
        }

        return $response;
    }

    public function getDataBundlesList($networkProvider)
    {
        $vtPassController = new VTPassController();
        $dataBundles      = $vtPassController->getDataBundles($networkProvider);

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function buyDataBundle($transaction_pin, $amount, $user, $network_provider, $data_plan, $phone_number)
    {

        $transactionId = $this->vtPassRequestId();

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($user->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] = 1;

        }
        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePinCheckCustomerBalance($transaction_pin, 1, $selectedwallet, $user);
        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalance = $this->checkCompanyBalance($amount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $amount);

        if (! $checkCustomerBalance['status']) {
            return $checkCustomerBalance;
        }

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $network_provider,
            "variation_code" => $data_plan['plan']['variation_code'],
            "amount"         => $data_plan['plan']['variation_amount'],
            "phone"          => $phone_number,
        ];

        $endpoint = "pay";

        $variation_amount = $data_plan['plan']['variation_amount'];
        $variation_code   = $data_plan['plan']['variation_code'];

        $vtPassController = new VTPassController();

        $buyAirtime = $vtPassController->buyData($transactionId, $network_provider, $variation_code, $variation_amount, $phone_number);

        if ($buyAirtime["status"]) {

            $debitCustomer = $this->debitCustomerAndCompanyWithCompanyCashback($user->accounts->first()->id, $user->company_id, $variation_amount, 0, 0.01, $user);

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
                "amount"                 => $variation_amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "account_id"             => $transactionPinValidation['account_id'],
                "description"            => "Purchase of Data worth " . $variation_amount . " for $phone_number",
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 12,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $cashbackTransactionId = \generate_txnID();
            $ledger_cr             = null;
            $ledger_dr             = null;
            $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, $user->company_id);
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
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "parent_transaction_id"  => $transaction->id,
                "transaction_reference"  => $cashbackTransactionId,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 66,
                "credit_debit_type_id"   => 1,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
            $updateBalance = true;
            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after'], $updateBalance, $user);

            return
                [
                "status"  => true,
                'message' => "Data Plan Purchase Successful",
                'data'    => [
                    'balance' => $debitCustomer['balance_after'],
                ],
            ];
        } else {
            return ["status" => false, 'message' => $buyAirtime['message']];
        }
    }

    public function verifyMeterNumber($meter_number, $electricity_disco, $type)
    {

        $vtPassController = new VTPassController();

        $verifyMeterResult = $vtPassController->verifyMeterNumber($meter_number, $electricity_disco, $type);

        if (! $verifyMeterResult['status']) {
            return $verifyMeterResult;
        }
        return $verifyMeterResult;
    }

    public function verifySmartCard(Request $request)
    {

        $vtPassController = new VTPassController();

        $verifyCableTV = $vtPassController->verifyCableTV($smart_card_number, $cable_tv_type);

        if (! $verifyCableTV["status"]) {
            return $verifyCableTV;
        }
        return $verifyCableTV;
    }

    public function buyElectricity($user, $amount, $transaction_pin, $electricity_disco, $meter_type, $meter_number, $phone_number)
    {

        // $transactionId = $this->randomNumber(12);
        $transactionId = $this->vtPassRequestId();

        \logInfo(json_encode($user), "checkAllowedTransaction");

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($user->company_id);

        \logInfo(json_encode($checkAllowedTransaction), "checkAllowedTransaction");

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return $checkAllowedTransaction;

        }

        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePinCheckCustomerBalance($transaction_pin, $amount, $selectedwallet, $user);
        \logInfo(json_encode($transactionPinValidation), "transactionPinValidation");

        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(13, $amount);

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $amount;

        $checkCompanyBalance = $this->checkCompanyBalance($checkCompanyBalanceAmount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $amount);

        if (! $checkCustomerBalance['status']) {
            return $checkCustomerBalancee;
        }

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $electricity_disco,
            "variation_code" => $meter_type,
            "billersCode"    => $meter_number,
            "amount"         => $amount,
            "phone"          => $phone_number,
        ];

        $endpoint = "pay";

        $vtPassController = new VTPassController();

        $buyElectricity = $vtPassController->buyElectricity(
            $transactionId,
            $electricity_disco,
            $meter_type,
            $meter_number,
            $amount,
            $phone_number
        );

        logInfo($buyElectricity, "VTPass Electricity Response");

        if (! $buyElectricity['status']) {
            return $buyElectricity;
        } else {
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
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 51,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $electricityToken = $buyElectricity['data']['token'];

            $transaction->transaction_remarks_id = 2;
            $transaction->description            = "Purchased Electricity Token: $electricityToken Amount: $amount";
            $transaction->save();

            $buyElectricity['data']['balance'] = $debitCustomer["charge_balance_after"];

            sendSMS($user->id, "Purchased Electricity Token $electricityToken, Amount: $amount, Avail Bal: " . $debitCustomer["charge_balance_after"], true, true, 'Electricity Token');

            return $buyElectricity;
        }

    }

    public function subscribeCableTV($user, $amount, $phone_number, $smart_card_number, $subscription_plan, $cable_tv_type, $transaction_pin)
    {

        $transactionId = $this->vtPassRequestId();

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($user->company_id);

        \logInfo(json_encode($checkAllowedTransaction), "checkAllowedTransaction");

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return $checkAllowedTransaction;

        }

        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePinCheckCustomerBalance($transaction_pin, $amount, $selectedwallet, $user);
        \logInfo(json_encode($transactionPinValidation), "transactionPinValidation");

        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        //Get WBA Charges
        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(12, ($amount / 100));

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $amount;

        $checkCompanyBalance = $this->checkCompanyBalance($checkCompanyBalanceAmount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $amount);

        if (! $checkCustomerBalance['status']) {
            return $checkCustomerBalancee;
        }

        $vtPassController = new VTPassController();

        $subscribeCableTV = $vtPassController->subscribeCableTV($transactionId, $cable_tv_type, $subscription_plan, $smart_card_number, $amount, $phone_number);

        logInfo($subscribeCableTV, "VTPass Subscribe Cable TV Log");

        if ($subscribeCableTV['status']) {

            $debitCustomer   = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);
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
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ]);

            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_before'], false);

            $subscribeCableTV['data']['balance'] = $debitCustomer['charge_balance_after'];

            return $subscribeCableTV;
        } else {
            return $subscribeCableTV;
        }
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
    function debitCustomerAndCompanyWithCompanyCashback($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
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

    public function register($bvn, $company_id, $email, $phone, $username, $customerInfo, $referral, $password, $pin)
    {
        $rules = [
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'required|string',
            'email'      => 'required|email|string',
            'dob'        => 'required|date',
            'username'   => 'required|string',
            'company'    => 'required|string',
            'pin'        => 'required|numeric|gt:0',
            'password'   => 'required|string',
        ];

        if ($bvn) {
            $userNIN = User::where('bvn', $bvn)->get();
            if (count($userNIN) > 0) {
                return response()->json(["status" => false, "message" => "BVN already exist!"], 409);
            }
        }

        $companyRecord = Company::where(['company_reference' => $company_id])->orWhere(['id' => $company_id])->first();
        if (! $companyRecord || $companyRecord->id == 27) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return $ra;
        }

        \logInfo($companyRecord->id, "Current company ID");

        $savings_product = $companyRecord->savingsProducts[0];
        $selectedProduct = $savings_product;

        if (! ($selectedProduct)) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return $ra;
        }

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            // return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return $ra;
        }
        $userPhone = $phone;

        $user = User::where([['email', $email], ['company_id', $companyRecord->id]])->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Email already exists"];
            return $ra;
        }

        $user = User::where([['phone', $userPhone], ['company_id', $companyRecord->id]])->get();
        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Phone number already exists"];
            return $ra;
        }

        $user = User::where('username', $username)->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Username already exiss"];
            return $ra;
        }

        \logInfo($user, "Current user");

        $companyIDs = [];

        $companyIDsStatus = false;

        if (in_array($companyRecord->id, $companyIDs)) {
            $companyIDsStatus = true;
        }

        $bank_id      = $companyRecord->setting->preferred_bank_id ?? 126;
        $selectedBank = banks::where('id', $bank_id)->first();
        if (! isset($request->account_type) || $request->account_type != 'regular') {
            $virtualAccount = new VirtualAccountType();

            \logInfo($bank_id, "Register Bank Id ");
            \logInfo($selectedBank->bank_code, "Register Bank code ");

            if ($companyIDsStatus) {
                $customSelectedBank = "090645";
            } else {
                $customSelectedBank = $selectedBank->bank_code;
            }

            $customSelectedBank = $selectedBank->bank_code;

            $virtualAccount->bankCode                     = $customSelectedBank;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return $response;
            }
        }
        $branch = $request->branch ?? $companyRecord->branch[0]->id;
        // if($request->branch){
        $branchData = (branch::where('id', $request->branch)->first());
        // }
        // logInfo($response, "Create Account service response");
        $customerReference = $response['data']['account_reference'] ?? rand(1000000000, 9999999999);

        $virtual_acccount_number = (! isset($request->account_type) || $request->account_type != 'regular') ? $response['data']['virtual_account'] : rand(1000000000, 9999999999);
        if ($request->selected_product) {
            $savings_product = savings_product::Where('id', $request->selected_product)->first();
            $selectedProduct = $savings_product;
            if (! $savings_product) {
                $ra = ["status" => false, "message" => "Product doesn't exist!"];
                return response()->json($ra, 400);
            }
            if (isset($request->account_type) && $request->account_type == 'regular') {

                if (isset($savings_product->code)) {
                    if ($savings_product->code != null) {
                        $length                  = strlen($savings_product->code);
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $savings_product->code, 0, $length);
                    }
                }
            }
        }
        if (isset($request->account_type) && $request->account_type == 'regular') {

            if (isset($branchData->code)) {
                if ($branchData->code != null) {
                    $branchCodeLength = strlen($branchData->code);
                    $branchCodeLength -= 1;
                    $virtual_acccount_number = substr_replace($virtual_acccount_number, $branchData->code, -$branchCodeLength);
                }
            }
        }

        $accountOfficer = User::where('company_id', $companyRecord->id)->first();
        //
        //  GENERATE AND SEND OTP TO USER HERE
        //
        $otp = generateOtp();
        // $otp = '7777';
        DB::beginTransaction();

        try {
            // referral_id
            $dataraw = [
                'first_name'                   => $customerInfo['first_name'],
                'last_name'                    => $customerInfo['last_name'],
                'company_id'                   => $companyRecord->id,
                'email'                        => $email,
                'bvn'                          => $bvn,
                'phone'                        => $userPhone,
                'otp'                          => $otp,
                'nin'                          => $request->nin ?? null,
                'bank_id'                      => $bank_id ?? 126,
                'pin'                          => $pin,
                'address'                      => $customerInfo['address'],
                'dob'                          => $customerInfo['dob'],
                'sms'                          => 1,
                'kyc_level_id'                 => 1,
                'status_id'                    => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                'branch_id'                    => $branch,
                'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                'user_type_id'                 => 1,
                'phone_verification_status_id' => 1,
                'virtual_account'              => $virtual_acccount_number,
                'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                'account_reference'            => $customerReference,
                'username'                     => $username,
                'account_officer_id'           => $accountOfficer->id,
                'uuid'                         => $customerReference,
                'password'                     => Hash::make($password),
                'phone_verification_status_id' => (isset($branchData->currency_id)) ? (($branchData->currency_id == 1) ? null : 1) : null,
                //
            ];

            if ($referral) {
                $refeUser = User::where(['company_id' => $companyRecord->id, 'username' => $request->referral])->first();
                if (! $refeUser) {
                    return response()->json(['status' => false, 'message' => 'Invalid referral username']);
                }
                $dataraw['referral_id'] = $refeUser->id;
            }

            $user = User::create($dataraw);
            if (! $user) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::09'];
            }

            $wb_account_details = wb_account_details::create([
                'user_id'            => $user->id,
                'account_product_id' => $selectedProduct->id,
                'bankid'             => $bank_id ?? 126,
                'virtual_accnum'     => $virtual_acccount_number,
                'acc_reference'      => $customerReference,
                'has_atm_card'       => 0,
                'currency_id'        => $branchData->currency_id ?? 1,
            ]);
            if (! $wb_account_details) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::10'];
            }

            $user_wallet = user_wallet::create([
                'wb_account_detail_id' => $wb_account_details->id,
                'balance'              => 0,
            ]);
            if (! $user_wallet) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::11'];
            }

            // \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

            $bank = banks::where('id', $bank_id)->first();

            if (! $bank) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Invalid bank selected'];
            }

            // if ($user && $user_wallet) {
            $user->assignRole([4]);

            $accountNumber = $virtual_acccount_number;

            $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";

            // $welcomeMessage = "Welcome FirstName! Your account number is 5080135881. Bank Name: Asset Matrix MFB.  upload your Valid ID and link BVN. Have Access via USSD. Simply dial *7006*10#. T&Cs applyEnjoy 24/7.";
            //             sendSMS($userid, $msg, $type = true, $charge_customer = true, $title = 'Create Account', $accno = '', $usesms = 0, $isApi = false, $all_sms_email = 1, $sms_logdata = [])
            $smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00%23 To verify your phone from the registered phone number", true, false, 'Verify OTP', '');
            $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);
            // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);

            \logInfo($smsstate, "SMS response");

            DB::commit();

            $companyIDs = [30];

            $companyIDsStatus = false;
            if (in_array($user->company_id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            return [
                'status'  => true,
                'message' => 'Registration Successful',
                'data'    => [
                    'user_reference' => $customerReference,
                    'account_number' => $accountNumber,
                    'bank'           => $bank->bank_name,
                    'otp_code'       => $user->otp,

                ],
            ];

        } catch (\Exception $e) {

            \logInfo($e->getTraceAsString(), "Registration failure");
            DB::rollback();
            // something went wrong
            return ['status' => false, 'message' => 'Failed to complete Registration', 'data' => $e->getMessage() . ' ' . $e->getLine()];
        }

    }

    public function verifyBVN($bvn, $company_id)
    {

        $charge = 50;

        logInfo( $company_id, "Twilio Debit company id");

        $debitCompany = debitCompanyWithCharge($company_id, $charge, 0, );

        if (! $debitCompany['status']) {
            return ['status' => false, 'message' => 'Switcher Error. Kindly contact support'];
        }

        logInfo($debitCompany, "Twilio Debit company log");

        $this->debitBVNFromCompany($debitCompany, $company_id, $charge);

        $identityPassController = new IdentityPassController();

        $bvnResponse = $identityPassController->verifyBVN($bvn);

        if (isset($bvnResponse['data']['verification']['status']) && $bvnResponse['data']['verification']['status'] == "VERIFIED") {

            return $bvnResponse;
        } else {
            return false;
        }
    }

    public function debitBVNFromCompany($debitCompanyWithCharge, $companyId, $charge)
    {
        $companyBalanceBefore       = $debitCompanyWithCharge['balance_before'];
        $companyBalanceAfter        = $debitCompanyWithCharge['balance_after'];
        $companyChargeBalanceBefore = $debitCompanyWithCharge['charge_balance_before'];
        $companyChargeBalanceAfter  = $debitCompanyWithCharge['charge_balance_after'];

        $transactionRef2 = generateTransactionReference();

        transactions::create([
            "company_balance_before" => $companyChargeBalanceBefore,
            "company_amount"         => 0,
            "account_id"             => null,
            "company_balance_after"  => $companyChargeBalanceAfter,
            "description"            => "Charge for BVN Check",
            "transaction_reference"  => $transactionRef2,
            "balance_before"         => $companyBalanceBefore,
            "amount"                 => $charge,
            "balance_after"          => $companyBalanceAfter,
            "company_id"             => $companyId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 43,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $companyId,

        ]);

    }

    public function verifyOTP($user_reference, $otp_code)
    {
        \logInfo($otp_code, "verifyOTP Twilio request log");

        $userAccunt = User::where(['uuid' => $user_reference, 'otp' => $otp_code])->first();

        if ($userAccunt) {

            $newdate = Carbon::now();

            if (Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date)) > Carbon::now()) {

                $userAccunt->phone_verification_status_id = 2;
                $userAccunt->last_login                   = Carbon::now();
                $userAccunt->save();

                return ['status' => true, 'message' => 'OTP Verification Successful'];
            } else {
                return ['status' => false, 'message' => 'You OTP has Expired', 'data' => "Expiration: " . Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date))->format('d-m-Y / h:i:s A') . " ***** CurrentDate: " . Carbon::now()->format('d-m-Y / h:i:s A')];
            }
        } else {
            return response(['status' => false, 'message' => "Invalid user reference or OTP supplied"]);
        }
    }

}
