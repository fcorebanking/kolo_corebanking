<?php
namespace App\Services;

use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\PreferredEbankingPartner;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
            return $checkAllowedTransaction;
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
            return response()->json($checkAllowedTransaction);
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

}
