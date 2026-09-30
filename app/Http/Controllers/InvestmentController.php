<?php

namespace App\Http\Controllers;

use App\Models\BranchWallet;
use App\Models\company_status;
use App\Models\frequency;
use App\Models\Investmentbooking;
use App\Models\InvestmentProduct;
use App\Models\status;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class InvestmentController extends Controller
{

    public function __construct()
    {
        $this->middleware("custom_permission:book-investment|manage-investment|root");
    }

    public function index($company_name)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html5',
            'page_menu'        => 'investment_product',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'product'          => InvestmentProduct::where('company_id', auth()->user()->company_id)->get(),
            'freq'             => frequency::where('id', '>', 1)->get(),
            'status'            => company_status::all(),
        ];

        return view('company.investment.main')->with($data);
    }

    public function booking($company_name)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'investment_booking',
            'page_menu'        => 'investment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => status::where('id', '<', 3)->get(),
        ];

        return view('company.investment.booking')->with($data);
    }

    public function viewinvestment($company_name)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'viewinvestment_booking',
            'page_menu'        => 'viewinvestment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => status::where('id', '<', 1)->get(),
        ];

        return view('company.investment.viewinvestment')->with($data);
    }

    public function ajax_terminate_investment($id)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $runninginvestment = Investmentbooking::where('id', $id)->first();

        if (! $runninginvestment) {
            return response()->json(['status' => 0, 'message' => 'Investment not found'], 404);
        }

        if ($runninginvestment->status == 1) {
            $runninginvestment->update(['status' => 0]);
            return response(json_encode(['status' => 1, 'message' => 'Investment Disabled']));
        }

        $runninginvestment->update(['status' => 1]);
        return response(json_encode(['status' => 1, 'message' => 'Investment Activated']));
    }

    public function ajax_investmentstatus($id)
    {
        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(21, auth()->user()->company_id);
        if ($Investment_Plan) {
            $ledger_cr = $Investment_Plan->ledger_cr;
            $ledger_dr = $Investment_Plan->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Credit transaction type in ledger settings !']);
        }

        $terminationoption = auth()->user()->company->setting->investment_termination_control;
        $runninginvestment = Investmentbooking::where('id', $id)->first();

        if (! $runninginvestment) {
            return response()->json(['status' => 0, 'message' => 'Investment not found'], 404);
        }

        // The original condition had two opposite checks (status != 1 and later status == 1).
        // An active investment is the one eligible for termination.
        if ($runninginvestment->status == 1) {
            $termination_charge = $runninginvestment->plan->termination_charge;
            $amount             = $runninginvestment->amount;
            $PI                 = $amount + $runninginvestment->wallet;
            $percent_charge     = 0;
            $cashleft           = 0;

            if ($terminationoption == 1) {
                if ($runninginvestment->wallet > 1) {
                    $percent_charge = ($termination_charge / 100) * $runninginvestment->wallet;
                    if ($runninginvestment->wallet > $percent_charge) {
                        $cashleft = ($runninginvestment->wallet - $percent_charge) + $amount;
                    } else {
                        return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
                    }
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later (Low interest)']));
                }
            } elseif ($terminationoption == 2) {
                $percent_charge = ($termination_charge / 100) * $amount;
                $cashleft       = ($amount - $percent_charge) + $runninginvestment->wallet;
            } elseif ($terminationoption == 3) {
                $percent_charge = ($termination_charge / 100) * $PI;
                if ($PI > $percent_charge) {
                    $cashleft = $PI - $percent_charge;
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
                }
            } elseif ($terminationoption == 4) {
                $cashleft = $amount;
            } else {
                return response(json_encode(['status' => 0, 'message' => 'Go to company settings and set Investment termination Control']));
            }

            $wb      = wb_account_details::where('id', $runninginvestment->wb_accountdetails_id)->first();
            if (! $wb || ! $wb->user) {
                return response()->json(['status' => 0, 'message' => 'Investment account not found'], 404);
            }

            $wbcheck = ($wb->user->company_id == auth()->user()->company_id);
            if ($wbcheck) {
                if ($runninginvestment->status == 1) {
                    $userwallet = user_wallet::where('wb_account_detail_id', $runninginvestment->wb_accountdetails_id)->first();

                    if (! $userwallet) {
                        return response()->json(['status' => 0, 'message' => 'User wallet not found'], 404);
                    }

                    $balanceBefore       = $userwallet->balance;
                    $balanceAfter        = $balanceBefore + $cashleft;
                    $userwallet->balance = $balanceAfter;
                    $userwallet->save();

                    $BranchWallet = BranchWallet::where('branch_id', $wb->user->branch_id)->first();
                    if (! $BranchWallet) {
                        return response()->json(['status' => 0, 'message' => 'Branch wallet not found'], 404);
                    }

                    $BranchWallet->balance += $cashleft;
                    $BranchWallet->save();
                    $desc = "Investment Termination";
                    $date = date('Y-m-d H:i:s');
                    $runninginvestment->update(['status' => 2]);
                    $data = [
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $amount,
                        "description"            => $desc,
                        "withdrawal_charge"      => $percent_charge,
                        "account_id"             => $runninginvestment->wb_accountdetails_id,
                        "branch_id"              => $wb->user->branch_id,
                        "transaction_reference"  => generate_txnID(),
                        "transaction_type_id"    => 21,
                        "credit_debit_type_id"   => 1,
                        "transaction_remarks_id" => 2,
                        "balance_after"          => $balanceAfter,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ];
                    transactions::create($data);
                    sendSMS($runninginvestment->wb_accountdetails_id, "Credit Amt:$cashleft Charges:$percent_charge Desc:$desc Avail Bal:$userwallet->balance Date:$date", false, true, 'Investment Termination');
                    return response(json_encode(['status' => 1, 'message' => "Investment Terminated!"]));
                }

                return response(json_encode(['status' => 0, 'message' => 'Investment no longer Active']));
            }

            return response(json_encode(['status' => 0, 'message' => 'User restricted from other company customers']));
        }

        return response(json_encode(['status' => 0, 'message' => 'Investment Terminated!']));
    }

    public function fetchInvestmentProduct()
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $product = InvestmentProduct::where('company_id', auth()->user()->company_id)->get();
        return response(json_encode($product));
    }

    public function calculateinvestment(Request $request)
    {
        if ($request->plan == null || $request->amount == null) {
            return response(json_encode(['status' => 0, 'message' => "Please fill all field!"]));
        }

        $InvestmentProduct = InvestmentProduct::findorfail($request->plan);
        $amount            = $request->amount;
        $plan_interest     = $InvestmentProduct->interest_rate;
        $interest          = $InvestmentProduct->interest_rate / 100 * $amount;
        $amt_interest      = $amount + $interest;
        $product_duration  = $InvestmentProduct->duration;
        $product_frequency = $InvestmentProduct->frequency_id;

        if ($product_frequency == 1) {
            $daysToAdd = $product_duration;
        } else if ($product_frequency == 2) {
            $daysToAdd = $product_duration * 7;
        } else if ($product_frequency == 3) {
            $daysToAdd = $product_duration * 30;
        } else if ($product_frequency == 4) {
            $daysToAdd = $product_duration * 365;
        } else {
            return response(json_encode(['status' => 0, 'message' => 'Investment frequency error']));
        }

        $percentinvalue = ($amount / $InvestmentProduct->maximum_investment) * 100;
        $startdate      = ($request->created_at == null) ? date("Y-m-d") : $request->created_at;
        $end_date       = add_day_to_date($daysToAdd, $startdate);

        return response(
            json_encode([
                'test'                 => $product_frequency,
                "product"              => $InvestmentProduct->product,
                "investmentproduct_id" => $InvestmentProduct->id,
                "amount"               => $amount,
                'percentvalue'         => round($percentinvalue),
                'termination'          => $InvestmentProduct->termination_charge,
                'max'                  => $InvestmentProduct->maximum_investment,
                'min'                  => $InvestmentProduct->minimum_investment,
                'start'                => $startdate,
                'end'                  => $end_date,
                'totalcash'            => $amt_interest,
                'interest'             => $interest,
                'plan_interest'        => $plan_interest,
                'plantype'             => $InvestmentProduct->investmentfrequency->frequency,
                'duration'             => $InvestmentProduct->duration,
                'account_type'         => auth()->user()->wb_account_details->first()->virtual_accnum ?? null,
            ])
        );
    }

    /**
     * Create an investment for the authenticated customer.
     *
     * The original method returned "Access denied!" before validation whenever
     * account_type did not match a wb_account_details.virtual_accnum. This
     * version resolves the authenticated customer first, then validates that
     * the selected account belongs to that customer and company.
     */
    public function makeinvestment(Request $request)
    {
        $traceId = $request->trace_id ?? (string) \Illuminate\Support\Str::uuid();
        $authUser = auth()->user();

        logInfo([
            'trace_id'       => $traceId,
            'stage'          => 'makeinvestment_start',
            'authenticated'  => (bool) $authUser,
            'auth_user_id'   => $authUser?->id,
            'auth_company_id'=> $authUser?->company_id,
            'uuid'           => $request->uuid,
            'amount'         => $request->amount,
            'account_type'   => $request->account_type,
            'investmentproduct' => $request->investmentproduct,
            'status'         => $request->status,
            'platform'       => $request->platform,
            'can_book_investment' => cando('book-investment|manage-investment|root'),
        ], 'INVESTMENT DEBUG START');

        if (! $authUser) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'auth_failed',
            ], 'INVESTMENT DEBUG ERROR');

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Validate BEFORE attempting account lookup. Previously the account lookup
        // could return "Access denied!" for a missing/incorrect account_type.
        $validation = Validator::make($request->all(), [
            'amount'            => 'required|numeric|gt:0',
            'account_type'      => 'required|string',
            'investmentproduct' => 'required|integer',
            'status'            => 'nullable|integer',
            'uuid'              => 'required|string',
            'platform'          => 'nullable|string',
            'created_at'        => 'nullable|date',
        ]);

        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'validation_failed',
                'errors' => $validation->errors()->toArray(),
            ], 'INVESTMENT DEBUG VALIDATION FAILED');

            if ($request->platform === 'mobile') {
                return response()->json(['status' => false, 'message' => trim($errors)], 422);
            }

            return response()->json(['status' => 0, 'message' => trim($errors)]);
        }

        $uuid = $request->uuid;
        $companyId = $authUser->company_id;

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'before_customer_lookup',
            'uuid' => $uuid,
            'company_id' => $companyId,
        ], 'INVESTMENT DEBUG CUSTOMER LOOKUP');

        $userdata = User::where('uuid', $uuid)
            ->where('company_id', $companyId)
            ->first();

        if (! $userdata) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'customer_not_found',
                'uuid' => $uuid,
                'company_id' => $companyId,
            ], 'INVESTMENT DEBUG CUSTOMER NOT FOUND');

            if ($request->platform === 'mobile') {
                return response()->json(['status' => false, 'message' => 'User not found for this company.'], 404);
            }

            return response()->json(['status' => 0, 'message' => 'User not found!'], 404);
        }

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'customer_found',
            'userdata_id' => $userdata->id,
            'userdata_uuid' => $userdata->uuid,
            'userdata_company_id' => $userdata->company_id,
            'branch_id' => $userdata->branch_id,
            'status_id' => $userdata->status_id,
        ], 'INVESTMENT DEBUG CUSTOMER FOUND');

        $requestedAccount = trim((string) $request->account_type);

        // IMPORTANT: the account must belong to the customer identified by uuid.
        // This is the direct replacement for the old global lookup that produced
        // "Access denied!".
        $wbAccountDetails = wb_account_details::where('virtual_accnum', $requestedAccount)
            ->where('user_id', $userdata->id)
            ->first();

        $accountMatchType = 'virtual_accnum';

        // Backwards-compatible fallback if the mobile app is sending the
        // wb_account_details row ID instead of the virtual account number.
        if (! $wbAccountDetails && ctype_digit($requestedAccount)) {
            $wbAccountDetails = wb_account_details::where('id', (int) $requestedAccount)
                ->where('user_id', $userdata->id)
                ->first();
            $accountMatchType = 'wb_account_details.id';
        }

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'account_lookup_result',
            'requested_account_type' => $requestedAccount,
            'account_match_type' => $accountMatchType,
            'account_found' => (bool) $wbAccountDetails,
            'wb_account_details_id' => $wbAccountDetails?->id,
            'wb_account_user_id' => $wbAccountDetails?->user_id,
            'wb_account_virtual_accnum' => $wbAccountDetails?->virtual_accnum,
        ], 'INVESTMENT DEBUG ACCOUNT LOOKUP');

        if (! $wbAccountDetails) {
            // Show useful diagnostics in the server log without exposing all
            // customer account records to the client.
            $customerAccounts = wb_account_details::where('user_id', $userdata->id)
                ->select('id', 'virtual_accnum', 'user_id')
                ->get();

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'account_not_found',
                'requested_account_type' => $requestedAccount,
                'customer_id' => $userdata->id,
                'customer_account_count' => $customerAccounts->count(),
                'customer_accounts' => $customerAccounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'virtual_accnum' => $account->virtual_accnum,
                        'user_id' => $account->user_id,
                    ];
                })->values()->all(),
            ], 'INVESTMENT DEBUG ACCESS DENIED ROOT CAUSE');

            if ($request->platform === 'mobile') {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied: the selected investment account was not found for this user.',
                    'trace_id' => $traceId,
                ], 403);
            }

            return response()->json([
                'status' => 0,
                'message' => 'Access denied!',
                'trace_id' => $traceId,
            ], 403);
        }

        $account_typeid = $wbAccountDetails->id;

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'account_authorized',
            'account_typeid' => $account_typeid,
            'virtual_accnum' => $wbAccountDetails->virtual_accnum,
            'user_id' => $userdata->id,
            'company_id' => $companyId,
        ], 'INVESTMENT DEBUG ACCOUNT AUTHORIZED');

        $platform = $request->platform;
        $created_at = $request->created_at ?? date('Y-m-d');
        $amount = (float) $request->amount;
        $investmentproduct = (int) $request->investmentproduct;
        $status = $request->status ?? 1;

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'before_ledger_lookup',
            'transaction_type_id' => 34,
            'company_id' => $companyId,
        ], 'INVESTMENT DEBUG LEDGER LOOKUP');

        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, $companyId);
        if (! $Investment_Plan) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'ledger_missing',
                'transaction_type_id' => 34,
                'company_id' => $companyId,
            ], 'INVESTMENT DEBUG LEDGER MISSING');

            return response()->json([
                'status' => 0,
                'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !',
                'trace_id' => $traceId,
            ], 422);
        }

        $ledger_cr = $Investment_Plan->ledger_cr;
        $ledger_dr = $Investment_Plan->ledger_dr;

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'ledger_found',
            'ledger_cr' => $ledger_cr,
            'ledger_dr' => $ledger_dr,
        ], 'INVESTMENT DEBUG LEDGER FOUND');

        $BranchWallet = BranchWallet::where('branch_id', $userdata->branch_id)->first();

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'branch_wallet_lookup',
            'branch_id' => $userdata->branch_id,
            'branch_wallet_id' => $BranchWallet?->id,
            'branch_wallet_balance' => $BranchWallet?->balance,
        ], 'INVESTMENT DEBUG BRANCH WALLET');

        if (! $BranchWallet) {
            return response()->json([
                'status' => false,
                'message' => 'Branch wallet not found.',
                'trace_id' => $traceId,
            ], 404);
        }

        if ((float) $BranchWallet->balance < $amount) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'branch_wallet_insufficient',
                'branch_wallet_balance' => $BranchWallet->balance,
                'amount' => $amount,
            ], 'INVESTMENT DEBUG INSUFFICIENT BRANCH WALLET');

            if ($platform === "mobile") {
                return response()->json(["status" => false, 'message' => "Insufficient balance in Branch Wallet", 'trace_id' => $traceId], 400);
            }

            return response(json_encode(["status" => 0, "message" => "Insufficient balance in Branch Wallet", 'trace_id' => $traceId]));
        }

        if ($userdata->status_id != 1) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'customer_disabled_or_pending',
                'status_id' => $userdata->status_id,
            ], 'INVESTMENT DEBUG CUSTOMER STATUS FAILED');

            if ($platform === "mobile") {
                return response()->json(["status" => false, 'message' => "User is Either Disabled or on Pending Entry!", 'trace_id' => $traceId], 400);
            }

            return response(json_encode(["status" => 0, "message" => "User is Either Disabled or on Pending Entry!", 'trace_id' => $traceId]));
        }

        $restricted = checkcustomerrestriction($userdata->id, 5);
        logInfo([
            'trace_id' => $traceId,
            'stage' => 'customer_restriction_check',
            'customer_id' => $userdata->id,
            'restricted' => (bool) $restricted,
            'restriction_type' => 5,
        ], 'INVESTMENT DEBUG CUSTOMER RESTRICTION');

        if ($restricted) {
            if ($platform === "mobile") {
                return response()->json(["status" => false, 'message' => "User is Restricted from performing this transaction!", 'trace_id' => $traceId], 400);
            }

            return response(json_encode(["status" => 0, "message" => "User is Restricted from performing this transaction!", 'trace_id' => $traceId]));
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $account_typeid)->first();

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'customer_wallet_lookup',
            'wb_account_details_id' => $account_typeid,
            'wallet_id' => $userWallet?->id,
            'wallet_found' => (bool) $userWallet,
            'customer_balance' => $userWallet?->balance,
        ], 'INVESTMENT DEBUG CUSTOMER WALLET');

        if (! $userWallet) {
            return response()->json([
                'status' => false,
                'message' => 'Customer wallet not found for the selected account.',
                'trace_id' => $traceId,
            ], 404);
        }

        $customerBalance = (float) $userWallet->balance;
        $walletID = $userWallet->id;

        if ($customerBalance < $amount) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'customer_wallet_insufficient',
                'customer_balance' => $customerBalance,
                'amount' => $amount,
            ], 'INVESTMENT DEBUG CUSTOMER INSUFFICIENT');

            if ($platform === "mobile") {
                return response()->json(["status" => false, 'message' => nomoney(), 'trace_id' => $traceId], 400);
            }

            return response(json_encode(['status' => 0, 'message' => nomoney(), 'trace_id' => $traceId]));
        }

        $product = InvestmentProduct::where('id', $investmentproduct)
            ->where('company_id', $companyId)
            ->first();

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'investment_product_lookup',
            'product_id' => $investmentproduct,
            'product_found' => (bool) $product,
            'company_id' => $companyId,
            'product_name' => $product?->product,
            'minimum_investment' => $product?->minimum_investment,
            'maximum_investment' => $product?->maximum_investment,
            'interest_rate' => $product?->interest_rate,
            'frequency_id' => $product?->frequency_id,
            'duration' => $product?->duration,
        ], 'INVESTMENT DEBUG PRODUCT');

        if (! $product) {
            return response()->json([
                'status' => false,
                'message' => 'Investment product not found for this company.',
                'trace_id' => $traceId,
            ], 404);
        }

        if ((float) $product->minimum_investment > $amount) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => 'Minimum amount for this investment is ' . $product->minimum_investment, 'trace_id' => $traceId], 400);
            }

            return response(json_encode(['status' => 0, 'message' => 'Minimum amount for this investment is ' . $product->minimum_investment, 'trace_id' => $traceId]));
        }

        if ($amount > (float) $product->maximum_investment) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => 'Maximun amount for this investment is ' . $product->maximum_investment, 'trace_id' => $traceId], 400);
            }

            return response(json_encode(['status' => 0, 'message' => 'Maximun amount for this investment is ' . $product->maximum_investment, 'trace_id' => $traceId]));
        }

        if (! ((float) $product->interest_rate >= 0 && (float) $product->interest_rate <= 100)) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => 'Investment interest rate error', 'trace_id' => $traceId], 400);
            }

            return response(json_encode(['status' => 0, 'message' => 'Investment interest rate error', 'trace_id' => $traceId]));
        }

        $product_duration  = (int) $product->duration;
        $product_frequency = (int) $product->frequency_id;

        if ($product_frequency == 1) {
            // Keep this consistent with calculateinvestment(): frequency 1 = days.
            $daysToAdd = $product_duration;
        } elseif ($product_frequency == 2) {
            $daysToAdd = $product_duration * 7;
        } elseif ($product_frequency == 3) {
            $daysToAdd = $product_duration * 30;
        } elseif ($product_frequency == 4) {
            $daysToAdd = $product_duration * 365;
        } else {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'invalid_frequency',
                'frequency_id' => $product_frequency,
                'product_id' => $product->id,
            ], 'INVESTMENT DEBUG INVALID FREQUENCY');

            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => 'Investment frequency error', 'trace_id' => $traceId], 400);
            }

            return response(json_encode(['status' => 0, 'message' => 'Investment frequency error', 'trace_id' => $traceId]));
        }

        $expectedcash = (($product->interest_rate / 100) * $amount) + $amount;
        $transactionId = generate_txnID();
        $balanceAfter = $customerBalance - $amount;
        $startdate = $created_at;
        $end_date = add_day_to_date($daysToAdd, $startdate);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'investment_calculation_complete',
            'customer_balance_before' => $customerBalance,
            'investment_amount' => $amount,
            'customer_balance_after' => $balanceAfter,
            'interest_rate' => $product->interest_rate,
            'expected_balance' => $expectedcash,
            'frequency_id' => $product_frequency,
            'days_to_add' => $daysToAdd,
            'start_date' => $startdate,
            'end_date' => $end_date,
            'transaction_reference' => $transactionId,
        ], 'INVESTMENT DEBUG CALCULATION');

        try {
            DB::beginTransaction();

            // Re-lock balances so concurrent investment requests cannot spend the
            // same balance twice.
            $lockedUserWallet = user_wallet::where('id', $walletID)
                ->lockForUpdate()
                ->first();

            $lockedBranchWallet = BranchWallet::where('id', $BranchWallet->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedUserWallet || ! $lockedBranchWallet) {
                throw new \RuntimeException('Unable to lock investment wallets.');
            }

            $customerBalance = (float) $lockedUserWallet->balance;
            $branchBalance = (float) $lockedBranchWallet->balance;

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'locked_balances',
                'customer_balance' => $customerBalance,
                'branch_balance' => $branchBalance,
                'amount' => $amount,
            ], 'INVESTMENT DEBUG LOCKED BALANCES');

            if ($customerBalance < $amount) {
                DB::rollBack();

                logInfo([
                    'trace_id' => $traceId,
                    'stage' => 'customer_balance_changed_before_commit',
                    'customer_balance' => $customerBalance,
                    'amount' => $amount,
                ], 'INVESTMENT DEBUG RACE CONDITION CUSTOMER BALANCE');

                return response()->json([
                    'status' => false,
                    'message' => nomoney(),
                    'trace_id' => $traceId,
                ], 400);
            }

            if ($branchBalance < $amount) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance in Branch Wallet',
                    'trace_id' => $traceId,
                ], 400);
            }

            $balanceAfter = $customerBalance - $amount;
            $branchBalanceAfter = $branchBalance - $amount;

            $transactionData = [
                "balance_before"         => $customerBalance,
                "amount"                 => $amount,
                "description"            => "$product->product Investment Plan ",
                "account_id"             => $account_typeid,
                "branch_id"              => $userdata->branch_id,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 34,
                "credit_debit_type_id"   => 2,
                "transaction_remarks_id" => 2,
                "balance_after"          => $balanceAfter,
                "created_date"           => $startdate,
                "created_at"             => "$startdate 00:00:00",
                "created_by"             => $authUser->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ];

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'before_transaction_create',
                'transaction' => $transactionData,
            ], 'INVESTMENT DEBUG TRANSACTION CREATE');

            transactions::create($transactionData);

            $lockedUserWallet->update([
                'balance' => $balanceAfter,
            ]);

            $lockedBranchWallet->update([
                'balance' => $branchBalanceAfter,
            ]);

            $insertInv = [
                'wb_accountdetails_id'  => $account_typeid,
                'investment_product_id' => $investmentproduct,
                'amount'                => $amount,
                'wallet'                => 0,
                'balance'               => 0,
                'expected_balance'      => $expectedcash,
                'duration'              => $daysToAdd,
                'start_date'            => $startdate,
                'end_date'              => $end_date,
                'status'                => $status,
                "created_at"            => $startdate,
            ];

            if (isset($request->paymentOption)) {
                $insertInv["paymentOption"] = $request->paymentOption;
            }

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'before_investment_booking_create',
                'investment_booking' => $insertInv,
            ], 'INVESTMENT DEBUG BOOKING CREATE');

            $investmentBooking = Investmentbooking::create($insertInv);

            DB::commit();

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'investment_transaction_committed',
                'investment_booking_id' => $investmentBooking->id,
                'transaction_reference' => $transactionId,
                'customer_balance_after' => $balanceAfter,
                'branch_balance_after' => $branchBalanceAfter,
            ], 'INVESTMENT DEBUG SUCCESS');

            $redirectlink = "transaction-receipt/$transactionId";

            if ($platform === 'mobile') {
                return response()->json([
                    "status" => true,
                    "message" => "Transaction Successful!",
                    "redirectlink" => $redirectlink,
                    "trace_id" => $traceId,
                ]);
            }

            return response(json_encode([
                "status" => 1,
                "message" => "Transaction Successful!",
                "redirectlink" => $redirectlink,
                "trace_id" => $traceId,
            ]));

        } catch (Throwable $e) {
            DB::rollBack();

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'investment_transaction_exception',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 'INVESTMENT DEBUG EXCEPTION');

            if ($platform === 'mobile') {
                return response()->json([
                    'status' => false,
                    'message' => 'Error processing investment transaction.',
                    'trace_id' => $traceId,
                ], 500);
            }

            return response()->json([
                'status' => 0,
                'message' => 'Error processing investment transaction.',
                'trace_id' => $traceId,
            ], 500);
        }
    }

    public function ajax_viewInvestment($page, Request $request)
    {
        $search = $request->query('search', ['value' => '', 'regex' => false]);
        $draw   = $request->query('draw', 0);
        $start  = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order  = $request->query('order', [1, 'asc']);

        $filter = $search['value'];

        $sortColumns = [
            0 => 'investmentbooking.id',
            1 => 'users.first_name',
        ];

        $query = Investmentbooking::join('investment_products', 'investment_products.id', '=', 'investmentbooking.investment_product_id')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('frequencies', 'frequencies.id', '=', 'investment_products.frequency_id')
            ->where('users.company_id', auth()->user()->company_id)
            ->select('investmentbooking.*', 'wb_account_details.virtual_accnum', 'users.last_name', 'users.first_name', 'frequencies.frequency', 'investment_products.termination_charge', 'investment_products.duration', 'investment_products.product', 'investment_products.interest_rate', DB::raw('savings_products.savings_product as accountsavingsp'));

        $is_root = cando('root');
        if (! $is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }

        if ($page == 'active') {
            $state = 1;
        } else if ($page == 'terminated') {
            $state = 2;
        } else {
            $state = 3;
        }

        $query = $query->where(['investmentbooking.status' => $state, 'users.user_type_id' => '1']);
        $query = $query->where('users.status_id', '=', 1);

        if (! empty($filter)) {
            $query = $query->where(function ($query) use ($filter) {
                $query->where('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('investment_products.product', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }

        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);

        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];

        $info = $query->get();
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $pen     = $infoData->termination_charge;

            if ($state == 1) {
                $btn = "<button class='btn btn-outline-warning btn-sm' onclick=";
                $btn .= "invest('$btnlink','$pen') >Terminate</button>";
                $btn .= "<button class='btn btn-outline-secondary btn-sm' onclick=";
                $btn .= "edit('$btnlink') >Edit</button>";
            } else if ($state == 2) {
                $btn = "<button class='btn btn-danger btn-sm' disabled>Terminated</button>";
            } else {
                $btn = "<button class='btn btn-success btn-sm' disabled>Completed</button>";
            }

            $btn .= "<button class='btn btn-danger btn-sm' title='This will delete this invest,ment record' onclick=";
            $encryptedID = Crypt::encryptString($btnlink);
            $btn .= "deleteInvestment('$encryptedID') >Delete</button>";

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->accountsavingsp,
                $infoData->product,
                $infoData->amount,
                $infoData->interest_rate . '%',
                ($infoData->duration > 1) ? $infoData->duration . ' ' . $infoData->frequency . 's' : $infoData->duration . ' ' . $infoData->frequency,
                $infoData->wallet,
                $infoData->expected_balance,
                dateDiffInDays($infoData->start_date, date('Y-m-d')),
                $infoData->start_date,
                $infoData->end_date,
                $btn,
            ];
        }

        return $json;
    }

    public function ajax_userviewInvestment($page, $id, Request $request)
    {
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'investmentbooking.id',
            1 => 'users.first_name',
        ];

        $query = Investmentbooking::join('investment_products', 'investment_products.id', '=', 'investmentbooking.investment_product_id')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->select('investmentbooking.*', 'wb_account_details.virtual_accnum', 'users.last_name', 'users.first_name', 'investment_products.product', 'investment_products.interest_rate', DB::raw('savings_products.savings_product as accountsavingsp'));

        $is_root = cando('root');
        if (! $is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }

        if ($page == 'active') {
            $state = 1;
        } else if ($page == 'terminated') {
            $state = 2;
        } else {
            $state = 3;
        }

        $query = $query->where([
            'investmentbooking.status' => $state,
            'users.user_type_id' => '1',
            'users.company_id' => auth()->user()->company_id,
            'investmentbooking.wb_accountdetails_id' => $id,
        ]);

        $query = $query->where('users.status_id', '=', 1);

        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->where('users.first_name', 'like', '%' . $filter . '%')
                    ->orWhere('users.phone', 'like', '%' . $filter . '%')
                    ->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%')
                    ->orWhere('investment_products.product', 'like', '%' . $filter . '%')
                    ->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }

        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);

        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];

        $info = $query->get();
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $pen     = $infoData->interest_rate;

            if ($state == 1) {
                $btn = "<button class='btn btn-outline-warning btn-sm' onclick=";
                $btn .= "invest('$btnlink','$pen') >Terminate</button>";
            } else if ($state == 2) {
                $btn = "<button class='btn btn-danger btn-sm' disabled>Terminated</button>";
            } else {
                $btn = "<button class='btn btn-success btn-sm' disabled>Completed</button>";
            }

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->accountsavingsp,
                $infoData->product,
                $infoData->amount,
                $infoData->wallet,
                $infoData->expected_balance,
                $infoData->duration,
                $infoData->start_date,
                $infoData->end_date,
                $btn,
            ];
        }

        return $json;
    }

    public function store(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'name'               => 'required',
            'cover_pic'          => 'required',
            'freq'               => 'required',
            'interest'           => 'required',
            'minimum_investment' => 'required',
            'maximum_investment' => 'required',
            'termination_charge' => 'required',
            'duration'           => 'required',
            'status'             => 'required',
        ]);

        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            return redirect()->back()->with('fail', $errors);
        }

        $savings_product = new InvestmentProduct;
        if (InvestmentProduct::where(['company_id' => auth()->user()->company_id, 'product' => $request->name])->count() == 0) {
            $savings_product->cover_pic = '';
            $company_product = $request->file('cover_pic');
            if ($company_product) {
                $productName = $company_product->getClientOriginalName();
                $savings_product->cover_pic = $company_product->storeAs("public/company/product/" . auth()->user()->company_id, $productName);
            }
            $savings_product->product            = $request->name;
            $savings_product->company_id         = auth()->user()->company_id;
            $savings_product->frequency_id       = $request->freq ? $request->freq : 0;
            $savings_product->interest_rate      = $request->interest;
            $savings_product->minimum_investment = $request->minimum_investment;
            $savings_product->termination_charge = $request->termination_charge;
            $savings_product->status_id          = $request->status;
            $savings_product->maximum_investment = $request->maximum_investment;
            $savings_product->duration           = $request->duration;
            $savings_product->created_by          = auth()->user()->id;
            if ($savings_product->save()) {
                return redirect()->back()->with('success', 'Product Added!');
            }
            return redirect()->back()->with('fail', 'Operation Failed!');
        }

        return redirect()->back()->with('fail', 'Duplicate entry!');
    }

    public function update(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'name'         => 'required',
            'freq'         => 'required',
            'interest'     => 'required',
            'min_withdraw' => 'required|numeric|gt:0',
            'max_deposit'  => 'required|numeric|gt:0',
            'duration'     => 'required',
            'status'       => 'required',
        ]);

        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            return redirect()->back()->with('fail', $errors);
        }

        $savings_product = InvestmentProduct::findorfail($request->id);
        if (InvestmentProduct::where(['company_id' => auth()->user()->company_id, 'product' => $request->name])->count() < 2) {
            $company_product = $request->file('cover_pic');
            if ($company_product) {
                Storage::delete($savings_product->cover_pic);
                $productName                = $company_product->getClientOriginalName();
                $savings_product->cover_pic = $company_product->storeAs("public/company/product/" . auth()->user()->company_id, $productName);
            }
            $savings_product->product            = $request->name;
            $savings_product->company_id         = auth()->user()->company_id;
            $savings_product->frequency_id       = $request->freq ? $request->freq : 0;
            $savings_product->interest_rate      = $request->interest;
            $savings_product->minimum_investment = $request->min_withdraw;
            $savings_product->status_id          = $request->status;
            $savings_product->maximum_investment = $request->max_deposit;
            $savings_product->duration           = $request->duration;
            $savings_product->modified_by        = auth()->user()->id;
            $savings_product->termination_charge = $request->termination_charge;
            if ($savings_product->save()) {
                return redirect()->back()->with('success', 'Product Updated!');
            }
            return redirect()->back()->with('fail', 'Operation Failed!');
        }

        return redirect()->back()->with('fail', 'Duplicate entry!');
    }

    public function destroy($company_name, $id)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $investment_product = InvestmentProduct::where(['id' => $id, 'company_id' => auth()->user()->company_id])->first();
        if (! $investment_product) {
            return redirect()->back()->with('fail', 'Investment product not found!');
        }

        Storage::delete($investment_product->cover_pic);
        $investment_product->delete();
        return redirect()->back()->with('success', 'Product Deleted!');
    }

    public function removeInvestment($id)
    {
        try {
            DB::beginTransaction();

            if (! cando('manage-investment|root')) {
                return abort(403);
            }

            $auth = auth()->user();
            $id = Crypt::decryptString($id);
            $investment = Investmentbooking::where(['id' => $id])->first();

            if (! $investment) {
                DB::rollBack();
                return json_encode(['status' => 0, 'message' => 'INVALID ACCOUNT ']);
            }

            if ($investment->wb_account_details->user->company_id == auth()->user()->company_id) {
                $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, auth()->user()->company_id);
                if ($Investment_Plan) {
                    $ledger_cr = $Investment_Plan->ledger_cr;
                    $ledger_dr = $Investment_Plan->ledger_dr;
                } else {
                    DB::rollBack();
                    return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !']);
                }

                $transactionId = generate_txnID();
                $user_wallet   = user_wallet::where('wb_account_detail_id', $investment->wb_accountdetails_id)->first();
                if (! $user_wallet) {
                    DB::rollBack();
                    return response()->json(['status' => 0, 'message' => 'Unknow error!' . $investment->wb_accountdetails_id]);
                }

                $balanceBefore = $user_wallet->balance;
                $extracash     = $investment->amount;
                $txnType       = getTransactionIdByName('Delete-Investment');
                if (! $txnType) {
                    DB::rollBack();
                    return response()->json(['status' => 0, 'message' => 'Invalid Transaction Type']);
                }

                $data = [
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $extracash,
                    "description"            => $investment->product . " Delete Investment",
                    "account_id"             => $investment->wb_accountdetails_id,
                    "branch_id"              => $auth->branch_id,
                    "transaction_status_id"  => 1,
                    "transaction_reference"  => $transactionId,
                    "transaction_type_id"    => $txnType->id,
                    "credit_debit_type_id"   => 1,
                    "transaction_remarks_id" => 2,
                    "balance_after"          => ($balanceBefore + $extracash),
                    "created_date"           => date("Y-m-d h:i:s"),
                    "created_by"             => $auth->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ];

                $data['userParamType'] = 2;
                $data['userId']        = $data['account_id'];
                $data['remark']        = $data['transaction_remarks_id'];
                $data['chargeamt']     = 0;
                $CreditCustomer        = DebitCreditcustomer($data);

                if (! $CreditCustomer['status']) {
                    DB::rollBack();
                    return json_encode(['status' => 0, 'message' => $CreditCustomer['message']]);
                }

                $investment->delete();
                DB::commit();

                return json_encode(['status' => 1, 'message' => 'Investment deleted!']);
            }

            DB::rollBack();
            return json_encode(['status' => 0, 'message' => 'Access denied!']);

        } catch (Throwable $e) {
            DB::rollBack();
            return json_encode(['status' => 0, 'message' => 'Error processing request!']);
        }
    }

    public function viewUserInvestment($company_name, $id)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'viewinvestment_booking',
            'page_menu'        => 'user_viewinvestment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'accountid'        => $id,
            'status'           => status::where('id', '<', 1)->get(),
        ];

        return view('company.investment.userinvestment')->with($data);
    }

    public function updateInvestment(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'amount'            => 'required',
            'investment_id'     => 'required',
            'investmentproduct' => 'required',
            'investment_date'   => 'required',
        ]);

        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            return redirect()->back()->with('fail', $errors);
        }

        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, auth()->user()->company_id);
        if ($Investment_Plan) {
            $ledger_cr = $Investment_Plan->ledger_cr;
            $ledger_dr = $Investment_Plan->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !']);
        }

        $Investment_Plan_Credit = get_CR_DR_TXN_Type_Ledger(21, auth()->user()->company_id);
        if ($Investment_Plan_Credit) {
            $credit_ledger_cr = $Investment_Plan_Credit->ledger_cr;
            $credit_ledger_dr = $Investment_Plan_Credit->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Credit transaction type in ledger settings !']);
        }

        $Investmentbooking = Investmentbooking::where('id', $request->investment_id)->first();
        if (! $Investmentbooking) {
            return redirect()->back()->with('fail', 'Fail to process transaction');
        }

        $investment_product = InvestmentProduct::where(['id' => $request->investmentproduct, 'company_id' => auth()->user()->company_id])->first();
        if (! $investment_product) {
            return redirect()->back()->with('fail', 'Fail to process transaction');
        }

        $auth = auth()->user();
        $minimum_investment = $investment_product->minimum_investment;
        $maximum_investment = $investment_product->maximum_investment;

        if ($minimum_investment > $request->amount) {
            return redirect()->back()->with('fail', "Minimum Investment is $minimum_investment");
        }

        if ($maximum_investment < $request->amount) {
            return redirect()->back()->with('fail', "Maximum Investment is $maximum_investment");
        }

        $prev_cash = $Investmentbooking->amount;
        $user_wallet = user_wallet::where('wb_account_detail_id', $Investmentbooking->wb_accountdetails_id)->first();

        if (! $user_wallet) {
            return redirect()->back()->with('fail', 'Customer wallet not found');
        }

        $balanceBefore = $user_wallet->balance;
        $transactionId = generate_txnID();

        if ($prev_cash < $request->amount) {
            $extracash = $request->amount - $prev_cash;
            if ($user_wallet->balance < $extracash) {
                return ["status" => 0, 'message' => "Insufficient Balance!"];
            }

            $data = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $extracash,
                "description"            => $investment_product->product . " Investment Plan ",
                "account_id"             => $Investmentbooking->wb_accountdetails_id,
                "branch_id"              => $auth->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 34,
                "credit_debit_type_id"   => 2,
                "transaction_remarks_id" => 2,
                "balance_after"          => ($balanceBefore - $extracash),
                "created_date"           => date("Y-m-d h:i:s"),
                "created_by"             => $auth->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ];
            $data['userParamType'] = 2;
            $data['userId']        = $data['account_id'];
            $data['remark']        = $data['transaction_remarks_id'];
            $data['chargeamt']     = 0;
            $CreditCustomer        = DebitCreditcustomer($data);
            if (! $CreditCustomer['status']) {
                return redirect()->back()->with('error', $CreditCustomer['message']);
            }
        }

        if ($prev_cash > $request->amount) {
            $overflow = $prev_cash - $request->amount;
            $data     = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $overflow,
                "description"            => "$investment_product->product Investment Plan ",
                "account_id"             => $Investmentbooking->wb_accountdetails_id,
                "branch_id"              => $auth->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 21,
                "credit_debit_type_id"   => 1,
                "transaction_remarks_id" => 2,
                "balance_after"          => ($balanceBefore + $overflow),
                "created_date"           => date("Y-m-d h:i:s"),
                "created_by"             => $auth->id,
                "ledger_cr"              => $credit_ledger_cr,
                "ledger_dr"              => $credit_ledger_dr,
            ];
            $data['userParamType'] = 2;
            $data['userId']        = $data['account_id'];
            $data['remark']        = $data['transaction_remarks_id'];
            $data['chargeamt']     = 0;
            $CreditCustomer        = DebitCreditcustomer($data);
            if (! $CreditCustomer['status']) {
                return redirect()->back()->with('error', $CreditCustomer['message']);
            }
        }

        $enddays = date("Y-m-d", strtotime($request->investment_date . "+ $investment_product->duration " . str_replace('ly', 's', $investment_product->frequency->charge_customer_status)));
        $totalDuration = dateDiffInDays($request->investment_date, $enddays);
        $interest = $investment_product->interest_rate;
        $totalInterest = ($interest / 100) * $request->amount;
        $expectedBalance = $totalInterest + $request->amount;
        $currentDuration = dateDiffInDays($request->investment_date, date('Y-m-d'));
        $dailycash = $totalDuration > 0 ? $expectedBalance / $totalDuration : 0;
        $balance = $currentDuration * $dailycash;

        $Investmentbooking->amount                = $request->amount;
        $Investmentbooking->wallet                = $balance;
        $Investmentbooking->balance               = $balance;
        $Investmentbooking->expected_balance      = $expectedBalance;
        $Investmentbooking->duration               = $totalDuration;
        $Investmentbooking->start_date            = $request->investment_date;
        $Investmentbooking->end_date              = $enddays;
        $Investmentbooking->status                = 1;
        $Investmentbooking->investment_product_id = $investment_product->id;

        if ($Investmentbooking->save()) {
            return redirect()->back()->with('success', 'Investment updated!');
        }

        return redirect()->back()->with('error', 'Failed to Update Investment!');
    }
}
