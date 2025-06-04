<?php
namespace App\Services;

use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
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
            return response()->json($checkAllowedTransaction, 400);
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

                        return response()->json(
                            [
                                "status"  => true,
                                'message' => "Transaction initialized",
                                'data'    => [
                                    'charge'            => $charge ?? 0.00,
                                    'transactionId'     => $transaction->id,
                                    'transactionTypeId' => $request->transaction_type,
                                    'exchangeAmount'    => $request->final_amount,
                                    'total'             => $request->finalAmount,
                                ],
                            ]
                        );
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

                    $validateSavingsProduct = $this->accountProductCharges($request->final_amount);

                    if (! $validateSavingsProduct['status']) {
                        return \response()->json($validateSavingsProduct, 400);
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

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
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

                return response()->json(
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
                    ]
                );

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
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction($user->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                if ($request->destination_account == $user->virtual_account) {
                    return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                }

                $charge = 0;

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
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

                return response()->json(
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
                    ]
                );

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else {
                return response()->json(["status" => false, 'message' => "Invalid Transaction Type"], 400);
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Amount"], 400);
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

    public function validateTransfer($amount)
    {
        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();
        $accountTier     = auth()->user()->tier;
        $wbaAccount      = auth()->user()->accounts->first();

        if (! $companySettings) {
            return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();
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
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => auth()->user()->accounts->first()->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
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
}
