<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\frequency;
use App\Models\savings_product;
use App\Models\User;
use App\Models\wb_account_details;
use App\Models\user_wallet;
use App\Models\CompanyWalletTXN;
use App\Models\transactions;
use App\Models\StaffWallet;
use App\Models\CustomerRestriction;
use App\Models\BranchWallet;
use App\Models\branch;
use App\Models\banks;
use App\Models\CompanyValut;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Models\PendingSaving;
use App\Models\Beneficiary;
use App\Models\LedgerAccountModel;
use App\Models\currencies as Currency;
use App\Models\ClosingBalance;

class SavingsController extends Controller
{
    // private $is_root;
    function __construct()
    {
        $this->middleware("custom_permission:manage-product|root|manage-saving|pending-credit-debit");
        // $this->is_root = cando('root');
    }
    public function index($company_name)
    {
        if (!cando('manage-product|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'savings_product',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'status' => frequency::where('id', 1)->get(),
        ];
        // $pageName = 'html5';
        return view('company.products.savings.account')->with($data);
    }

    public function DepositWithdraw($company_name)
    {
        if (!cando('manage-saving|credit-customer|debit-customer|root')) {
            abort(403);
            return 0;
        }
        $auth = auth()->user();
        $ledger = LedgerAccountModel::where('company_id', $auth->company_id)->get();
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'savings_withdraw',
            'page_menu' => 'savings_withdraw',
            'bank' => banks::all(),
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'auth' => $auth,
            'ledger' => $ledger,
            'CompanyValut' => CompanyValut::where('company_id', $auth->company_id)->get(),
            'status' => frequency::all(),
        ];
        return view('company.savings.saving')->with($data);
    }

    public function ajax_add_product($company_name, Request $request)
    {
        $token = csrf_token();
        if (!cando('manage-product|root')) {
            abort(403);
            return 0;
        }
        if ($request->name == null) {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Product Name required!"]);
        }
        if ($request->maintenace_duration < 30 && $request->maintenace_duration != null) {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Maintenance charge should be 30day above!"]);
        }
        $savings_product = new savings_product;
        if (savings_product::where(['company_id' => auth()->user()->company_id, 'savings_product' => $request->code])->count() == 0) {
            $savings_product->savings_product = $request->name;
            $savings_product->code = $request->code;
            $savings_product->company_id = auth()->user()->company_id;
            $savings_product->frequency_id = $request->freq ? $request->freq : 0;
            $savings_product->interest_rate = $request->interest;
            $savings_product->daily_withdraw_limit_count = $request->max_daily_transfer;
            $savings_product->max_withdraw = $request->max_withdraw;
            $savings_product->duration = $request->duration;
            $savings_product->min_amt_for_interest = $request->interestamt;
            $savings_product->minimum_withdraw = $request->min_withdraw;
            $savings_product->maximum_deposit = $request->max_deposit;
            $savings_product->maintenace_duration = $request->maintenace_duration ?? 0;

            $savings_product->maintenance_charges = $request->main_charges;
            $savings_product->minimum_balance = $request->minimum_balance;
            $savings_product->registration_fee = $request->registration_fee;
            $savings_product->created_by = auth()->user()->id;

            $savings_product->max_bank_transfer_charge = $request->max_bank_transfer_charge_or_flat_fee;
            $savings_product->min_bank_transfer_charge = $request->min_bank_transfer_charge_or_percentage;
            $savings_product->bank_transfer_charge_type = $request->bank_transfer_charge_type;
            $savings_product->percentage_transfer_charge = $request->min_bank_transfer_charge_or_percentage;

            if ($savings_product->save()) {
                return response()->json(["status" => 1, "token" => $token, 'message' => "Product Added!"]);
            } else {
                return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed!"]);
            }
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Duplicate entry!"]);
        }
    }
    public function fetch_ajax_product($company_name)
    {
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'savings_product_fetch',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'product' => savings_product::where('company_id', auth()->user()->company_id)->get(),
            'alt_menu' => 0,

        ];

        return view('company.products.savings.product_table')->with($data);
    }
    public function ajax_update_product($company_name, Request $request)
    {
        if (!cando('manage-product|root')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();
        if ($request->name == null) {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Product Name required!"]);
        }
        if ($request->maintenace_duration < 30 && $request->maintenace_duration != null) {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Maintenance charge should be 30day above!"]);
        }


        logInfo($request->all(), "Update charge request");

        $savings_product = savings_product::find($request->id);

        $savings_product->savings_product = $request->name;

        $savings_product->code = $request->code;

        $savings_product->frequency_id = $request->freq ? $request->freq : 0;
        $savings_product->interest_rate = $request->interest;
        $savings_product->min_amt_for_interest = $request->interestamt;
        $savings_product->duration = $request->duration;
        $savings_product->minimum_withdraw = $request->min_withdraw;
        $savings_product->maximum_deposit = $request->max_deposit;
        $savings_product->maintenance_charges = $request->main_charges;
        $savings_product->modified_by = auth()->user()->id;
        $savings_product->maintenace_duration = $request->maintenace_duration;
        $savings_product->daily_withdraw_limit_count = $request->max_daily_transfer;
        $savings_product->max_withdraw = $request->max_withdraw;
        $savings_product->minimum_balance = $request->minimum_balance;
        $savings_product->registration_fee = $request->registration_fee;
        $savings_product->max_bank_transfer_charge = $request->max_bank_transfer_charge_or_flat_fee;
        $savings_product->min_bank_transfer_charge = $request->min_bank_transfer_charge_or_percentage;
        $savings_product->bank_transfer_charge_type = $request->bank_transfer_charge_type;
        $savings_product->percentage_transfer_charge = $request->min_bank_transfer_charge_or_percentage;


        if ($savings_product->save()) {
            return response()->json(["status" => 1, "token" => $token, 'message' => "Product Updated!"]);
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed!"]);
        }




        if ($savings_product->save()) {
            return response()->json(["status" => 1, "token" => $token, 'message' => "Product Updated!"]);
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed!"]);
        }
    }
    public function destroy_product($company_name, $id)
    {
        if (!cando('manage-product|root')) {
            abort(403);
            return 0;
        }
        $token = csrf_token();
        if (User::where(['company_id' => auth()->user()->company_id, 'account_type_id' => $id])->count() == 0) {

            if (savings_product::where('id', $id)->delete()) {
                return response()->json(["status" => 1, "token" => $token, 'message' => "Product Deleted!"]);
            } else {
                return response()->json(["status" => 0, "token" => $token, 'message' => "Operation Failed!"]);
            }
        } else {
            return response()->json(["status" => 0, "token" => $token, 'message' => "One or more users still link to this product!"]);
        }
    }

    public function CreditCustomer_script($request)
    {
        if (!cando('manage-saving|credit-customer|root')) {
            return ["status" => 0, "message" => "Access denied!"];
            return 0;
        }
        try {
            // DB::beginTransaction();
            $userid = $request->uuid;
            $auth = auth()->user();
            $companydata = $auth->company;
            $amount = $request->amount;
            $desc = $request->desc;
            $account_type = $request->account_type;

            $get_ledger_setting = get_CR_DR_TXN_Type_Ledger($request->transaction_type_id, $auth->company_id);
            if (!$get_ledger_setting) {
                if (($request->ledger == null && $request->ledger_cr)) {
                    return ["status" => 0, "message" => "Transaction failed, Customer deposit ledger transaction type ledger on ledger settings!"];
                }
                $SystemLegder = systemLedgerAccount('Customer Saving Deposit', $auth->company_id)->id;
                $ledger = ($request->ledger != null) ? $request->ledger : $request->ledger_cr;
            } else {
                if ($request->ledger == null) {
                    $SystemLegder = $get_ledger_setting->ledger_cr;
                    $ledger = $get_ledger_setting->ledger_dr;
                } else {
                    $SystemLegder = $get_ledger_setting->ledger_cr;
                    $ledger = ($request->ledger) ? $request->ledger : $get_ledger_setting->ledger_dr;
                }
            }
            $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger(20, $auth->company_id);
            if (!$get_ledger_setting_Commission) {
                return ["status" => 0, "message" => "Transaction failed, Set teller commission transaction type ledger on ledger settings!"];
            }
            $get_ledger_setting_staffWallet = get_CR_DR_TXN_Type_Ledger(44, $auth->company_id);
            if (!$get_ledger_setting_staffWallet) {
                return ["status" => 0, "message" => "Transaction failed, Set Teller Credit transaction type ledger on ledger settings!"];
            }
            if ($request->instrumentCode) {
                if (transactions::where(['instrumentCode' => $request->instrumentCode])->count() > 0) {
                    return ["status" => 0, "message" => "Transaction Instrument Code Already Exist!"];
                }
            }
            $backdate = date("Y-m-d H:i:s");
            if ($request->created_byx != null && cando('can-set-txn-created-by|root') && $auth->company->setting->enable_txn_created_by == 1) {
                $request->created_by = User::where('uuid', $request->created_byx)->first()->id;
            }
            if ($request->pending == null  && !cando('root|direct-debit-credit')) {
                $companyTellerOption = $auth->company->setting->teller_option_id;
                if ($companyTellerOption == 3) {
                    if (PendingSaving::where(['uuid' => $request->uuid, 'pending' => 'credit', 'status_id' => 1])->count() > 0) {
                        return ['status' => 0, 'message' => 'Request Already Sent !'];
                    }
                    if (PendingSaving::create([
                        'uuid' => $request->uuid,
                        'amount' => $request->amount,
                        'company_id' => $auth->company_id,
                        'desc' => $request->desc,
                        'account_type' => $request->account_type,
                        'status_id' => 1,
                        'pending' => 'credit',
                        'created_by' => ($request->created_byx != null && cando('can-set-txn-created-by|root')) ? $request->created_by : $auth->id,
                        'transaction_type_id' => $request->transaction_type_id,
                        'companyVault' => $request->companyVault,
                        'instrumentCode' => $request->instrumentCode,
                        "ledger_dr" => $ledger,
                        "ledger_cr" => $SystemLegder,
                        "initiator" => $request->initiator,

                    ])) {
                        // DB::commit();

                        return ['status' => 1, 'message' => 'Deposit Request Pending!'];
                    } else {
                        return (['status' => 0, 'message' => 'Rquest Failed!']);
                    }
                }
            }

            if ($request->created_by != null) {
                $created_by = User::where('id', $request->created_by)->first();
            } else {
                $created_by = User::where('id', $auth->id)->first(); // User::find($auth->id);
            }
            $user = User::where(['uuid' => $userid, 'company_id' => $auth->company_id])->first();
            $useruniqueID = $user->id;

            if (checkcustomerrestriction($user->id, 1)) {
                $restrictionMSG = '';
                foreach (checkcustomerrestrictionData($user->id, 1) as $rdata) {
                    $restrictionMSG .= $rdata->remark;
                }
                return ["status" => 0, "message" => "User is Restricted from performing this transaction, Reason: $restrictionMSG! "];
            }
            if ($user->status_id != 1) {
                return ["status" => 0, "message" => "User is Either Disabled or on Pending Entry!"];
            }
            if ($user->branch_id == null) {
                return ["status" => 0, "message" => "No branch Assign to this customer"];
            }
            if ($companydata->setting->backdate->id == 2) {
                if ($request->backdate != null) {
                    $date_back = date_create($request->backdate);
                    $backdate = date_format($date_back, "Y-m-d H:i:s"); //date_format($request->backdate,"Y-m-d H:i:s");
                    //  return response(json_encode(array("status"=>0,"message"=>"$backdate")));
                }
            }

            if ($amount > 1) {
                if ($user) {
                    $wb_account_details = wb_account_details::where(['user_id' => $user->id, 'virtual_accnum' => $account_type])->first();
                    if ($wb_account_details) {

                        if ($wb_account_details->user_wallet) {
                            $account_typeid = $wb_account_details->id;
                            $transactions = transactions::where(['account_id' => $account_typeid, 'amount' => $amount])->orderBy('id', 'desc')->first();
                            $time_is_ok = true;
                            if ($transactions) {
                                $dbtimestamp = strtotime($transactions->created_at);
                                if (time() - $dbtimestamp < 1 * 60) {
                                    $time_is_ok = false;
                                }
                            }
                            if ($time_is_ok) {
                                if ($companydata->setting->teller_option_id == 2) {
                                    //credit teller
                                    if ($created_by->wallet->balance < $amount) {
                                        return ["status" => 0, "message" => "Insuficient Teller Wallet balance!"];
                                    }
                                }
                                $balanceBefore = $wb_account_details->user_wallet->balance;
                                $maxdep = $wb_account_details->accountType->maximum_deposit;
                                if ($maxdep < $amount) {
                                    return ["status" => 0, "message" => "Maximum Deposit for this plan is $maxdep"];
                                }
                                $walletID =  $wb_account_details->user_wallet->id;
                                $transactionId = generate_txnID();
                                // $staffwallet = $created_by->wallet->commission;
                                $staffcanDeposit = 1;
                                $remark = 1;
                                $finalRemark = 2;
                                $balanceAfter = $balanceBefore; //initialize balance after TXN
                                $balanceAfter = $balanceBefore + $amount;
                                // if($telleroption){
                                $teller_commission = $companydata->setting->teller_commission;
                                if ($companydata->setting->teller_commission > 0 && $companydata->setting->teller_commission <= 100) {
                                    $teller_commission = ($teller_commission / 100) * $amount;
                                } else {
                                    $teller_commission = 0;
                                }
                                $staffTXN = null;

                                // return response(json_encode(array("status"=>0,"message"=>"$teller_commission")));
                                if (isset($created_by->wallet->commission)) {
                                    $tellerbalance = $created_by->wallet->commission + $teller_commission;
                                    if ($teller_commission > 0) {
                                        $staffTXN = transactions::create(
                                            [
                                                "balance_before" => $created_by->wallet->commission,
                                                "amount" => $teller_commission,
                                                "description" => "Customer Deposit Commission",
                                                "branch_id" => $created_by->branch_id,
                                                "transaction_reference" => generate_txnID(),
                                                "transaction_type_id" => 20, //teller_commission
                                                "transaction_status_id" => 1,
                                                "credit_debit_type_id" => 2, //debit 2
                                                "transaction_remarks_id" => 2, //success 2
                                                "balance_after" => $tellerbalance,
                                                "created_date"  => date('Y-m-d'),
                                                "created_by" => $created_by->id,
                                                "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                                                "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                                            ]
                                        );
                                        StaffWallet::where(['id' => $created_by->wallet->id])->update(['commission' => $tellerbalance]);
                                    }
                                }

                                $data = [
                                    "balance_before" => $balanceBefore,
                                    "amount" =>  $amount,
                                    "description" => $desc,
                                    "account_id" => $account_typeid,
                                    "branch_id" => $created_by->branch_id,
                                    "transaction_status_id" => 1,
                                    "transaction_reference" => $transactionId,
                                    "transaction_type_id" => $request->transaction_type_id, //debit 7
                                    "credit_debit_type_id" => 1, //debit 2
                                    "transaction_remarks_id" => $remark, //success 2
                                    "balance_after" => $balanceAfter,
                                    "created_date"  => $backdate,
                                    "created_by" => $created_by->id,
                                    "instrumentCode" => $request->instrumentCode,
                                    "ledger_cr" => $SystemLegder,
                                    "ledger_dr" => $ledger,
                                    "initiator" => $request->initiator,

                                ];
                                // die(var_dump($data));
                                if ($staffcanDeposit) {
                                    $data['userParamType'] = 2;
                                    $data['userId'] = $account_typeid;
                                    $data['remark'] = 2;
                                    $data['chargeamt'] = 0;
                                    $data['companyVault'] = $request->companyVault;
                                    $data['request'] = $request;

                                    $CreditCustomer = DebitCreditcustomer($data);
                                    if (!$CreditCustomer['status']) {
                                        // DB::rollBack();
                                        return $CreditCustomer;
                                    }
                                    // start commission check

                                    if (isset(auth()->user()->company->CustomerReferalSetting)) {
                                        if (isset(auth()->user()->company->CustomerReferalSetting->indirect_comission)) {
                                            $referalsetting = auth()->user()->company->CustomerReferalSetting;

                                            $credit_method = $referalsetting->credit_method;
                                            $indirect_comission = $referalsetting->indirect_comission;
                                            if ($indirect_comission > 0) {

                                                $data_User = $user;
                                                if ($data_User->referral_id) {
                                                    if (isset($data_User->ReferralUpline)) {
                                                        if ($data_User->ReferralUpline->company_id != auth()->user()->company_id) {
                                                            $authuser = auth()->user(); // Fetch the user model
                                                            $authuser->status_id = 5; // Update the status
                                                            $authuser->save(); // Save the changes
                                                            return json_encode(['status' => 0, 'message' => 'possible fraud transaction']);
                                                        }

                                                        $wb_account_details = wb_account_details::where('user_id', $data_User->referral_id)->first();
                                                        $referalPaymentTransactionType = 111; //refPayment
                                                        $transactions = transactions::where(['account_id' => $wb_account_details->id, 'transaction_type_id' => $referalPaymentTransactionType])->count();
                                                        if (($transactions == 0 && $credit_method == 1) || ($transactions  >= 0 && $credit_method == 2)) {
                                                            $ledgerCr = null;
                                                            $ledgerDr = null;
                                                            $get_ledger_setting_commission = get_CR_DR_TXN_Type_Ledger($referalPaymentTransactionType, $data_User->company_id);
                                                            if ($get_ledger_setting_commission) {
                                                                $ledgerCr = $get_ledger_setting->ledger_cr;
                                                                $ledgerDr = $get_ledger_setting->ledger_dr;
                                                            }
                                                            $userWallet = $wb_account_details->user_wallet;
                                                            $balanceBefore = $userWallet->balance;
                                                            $balanceAfter = $balanceBefore + $indirect_comission;

                                                            $data = [
                                                                "balance_before" => $balanceBefore,
                                                                "amount" =>  $indirect_comission,
                                                                "description" => "Referal Comission",
                                                                "account_id" => $wb_account_details->id,
                                                                "branch_id" => $data_User->branch_id,
                                                                "transaction_status_id" => 1,
                                                                "transaction_reference" => generate_txnID(),
                                                                "transaction_type_id" => $referalPaymentTransactionType, //debit 7
                                                                "credit_debit_type_id" => 1,
                                                                "transaction_remarks_id" => 2,
                                                                "balance_after" => $balanceAfter,
                                                                "created_date"  => date('Y-m-d'),
                                                                "created_by" => $data_User->id,
                                                                "ledger_cr" => $ledgerCr,
                                                                "ledger_dr" => $ledgerDr,
                                                                "initiator" => $data_User->id,
                                                            ];
                                                            $data['userParamType'] = 2;
                                                            $data['userId'] = $wb_account_details->id;
                                                            $data['remark'] = 2;
                                                            $data['chargeamt'] = 0;
                                                            $CreditCustomer = DebitCreditcustomer($data);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    //end start commissin

                                    $transaction = $CreditCustomer['data'];
                                    $statesms = $CreditCustomer['statesms'];
                                    $transactionId = $transaction->transaction_reference;
                                    if ($staffTXN != null) {
                                        $staffTXN->update(["parent_transaction_id" => $transaction->id]);
                                    }
                                    if ($transaction) {
                                        //$user_wallet = user_wallet::findorfail($walletID)->update(['balance' => $balanceAfter]);
                                        if ($CreditCustomer) {
                                            if ($transaction->update([
                                                "transaction_remarks_id" => $finalRemark,
                                                "modified_by" => $created_by->id
                                            ])) {
                                                if (!cando('ignore-teller-option')) { // ignore-teller-option
                                                    if ($companydata->setting->teller_option_id == 1 || $companydata->setting->teller_option_id == 4) {
                                                        //debit teller approval
                                                        //$balanceAfter = $balanceBefore + $amount;
                                                        $staff_bal = $created_by->wallet->balance + $amount;
                                                       transactions::create(
                                                            [
                                                                "balance_before" => $created_by->wallet->balance,
                                                                "amount" => $amount,
                                                                "description" => "Staff credit customer Wallet",
                                                                "branch_id" => $created_by->branch_id,
                                                                "transaction_reference" => generate_txnID(),
                                                                "transaction_type_id" => 44, //teller_deposit
                                                                "transaction_status_id" => 1,
                                                                "credit_debit_type_id" => 1, //debit 2
                                                                "transaction_remarks_id" => 2, //success 2
                                                                "balance_after" => $staff_bal,
                                                                'parent_transaction_id' => $transaction->id,
                                                                "created_date"  => date('Y-m-d'),
                                                                "created_by" => $created_by->id,
                                                                "ledger_cr" => $get_ledger_setting_staffWallet->ledger_cr, //
                                                                "ledger_dr" => $get_ledger_setting_staffWallet->ledger_dr,

                                                            ]
                                                        );
                                                        StaffWallet::where(['id' => $created_by->wallet->id])->update(['balance' => $staff_bal]);

                                                    } else if ($companydata->setting->teller_option_id == 2) {
                                                        //credit teller

                                                        $staff_bal = $created_by->wallet->balance - $amount;
                                                         transactions::create(
                                                            [
                                                                "balance_before" => $created_by->wallet->balance,
                                                                "amount" => $amount,
                                                                "description" => "Staff Wallet",
                                                                "branch_id" => $created_by->branch_id,
                                                                "transaction_reference" => generate_txnID(),
                                                                "transaction_type_id" => 44, //teller_debit
                                                                "transaction_status_id" => 1,
                                                                "credit_debit_type_id" => 2, //debit 2
                                                                "transaction_remarks_id" => 2, //success 2
                                                                "balance_after" => $staff_bal,
                                                                "created_date"  => date('Y-m-d'),
                                                                'parent_transaction_id' => $transaction->id,
                                                                "created_by" => $created_by->id,
                                                                "ledger_cr" => $get_ledger_setting_staffWallet->ledger_cr, //
                                                                "ledger_dr" => $get_ledger_setting_staffWallet->ledger_dr,
                                                            ]
                                                        );
                                                         StaffWallet::where(['id' => $created_by->wallet->id])->update(['balance' => $staff_bal]);

                                                    }
                                                }
                                                // DB::commit();

                                                $redirectlink = "transaction-receipt/$transactionId";
                                                return ["status" => 1, "message" => "Transaction Successful! $finalRemark", "redirectlink" => $redirectlink, "smsstate" => $statesms];
                                            } else {
                                                // DB::rollBack();
                                                return ["status" => 0, "message" => "Error!"];
                                            }
                                        } else {
                                            // DB::rollBack();
                                            return ["status" => 0, "message" => "Error!"];
                                        }
                                    } else {

                                        return ["status" => 0, "message" => "Error!"];
                                    }
                                } else {

                                    return ["status" => 0, "message" => "Staff Wallet is too low for this transaction!"];
                                }
                            } else {
                                return ["status" => 0, "message" => env('txn_restrict_msg')];
                            }
                        } else {
                            return ["status" => 0, "message" => "User Wallet not found for ($wb_account_details->virtual_accnum)!"];
                        }
                    } else {
                        return ["status" => 0, "message" => "User account Type not found for ($account_type)!"];
                    }
                } else {
                    return ["status" => 0, "message" => "User Not Found!"];
                }
            } else {
                return ["status" => 0, "message" => "Amount should be above 10! "];
            }
        } catch (\Exception $e) {
            // DB::rollBack();
            return ["status" => 0, "message" => $e->getMessage() . $e->getLine()];
        }
    }
    public function CreditCustomer(Request $request)
    {
        if (!cando('manage-saving|credit-customer|root')) {
            abort(403);
            return 0;
        }
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'desc' => 'required',
            'uuid' => 'required',
            'account_type' => 'required',
        ]);
        // if(auth()->user()->username == 'james'){
        //     $request->amount = str_replace(',','',$request->amount);
        //     $poster = json_encode($_POST);
        //     return response()->json(['status'=>0,'message'=>$poster]);
        // }
        if ($validator->fails()) {

            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }


        return response()->json($this->CreditCustomer_script($request));
    }
    public function DebitCustomer_script($request)
    {
        // DB::beginTransaction();
        if (!cando('manage-saving|debit-customer|root')) {
            return ["status" => 0, "message" => 'Access Denied'];

            return 0;
        }
        try {
            $auth = auth()->user();
            $userid = $request->uuid;
            $actualAMT = $amount = $request->amount;
            $companydata = $auth->company;
            $desc = $request->desc;
            $account_type = $request->account_type;
            $desc = $request->desc;
            $wcharge = $request->w_charge;
            $companyTellerOption = $auth->company->setting->teller_option_id;
            $get_ledger_setting = get_CR_DR_TXN_Type_Ledger($request->transaction_type_id, $auth->company_id);
            if (!$get_ledger_setting) {
                if (($request->ledger == null && $request->ledger_cr)) {
                    return ["status" => 0, "message" => "Transaction failed, Customer deposit ledger transaction type ledger on ledger settings!"];
                }
                $SystemLegder = systemLedgerAccount('Customer Saving Deposit', $auth->company_id)->id;
                $ledger = ($request->ledger != null) ? $request->ledger : $request->ledger_cr;
            } else {
                if ($request->ledger == null) {
                    $SystemLegder = $get_ledger_setting->ledger_cr;
                    $ledger = $get_ledger_setting->ledger_dr;
                } else {
                    $SystemLegder = $get_ledger_setting->ledger_cr;
                    $ledger = ($request->ledger) ? $request->ledger : $get_ledger_setting->ledger_dr;
                }
            }
            if ($request->companyVault) {
                $CompanyValut = CompanyValut::where(['id' => $request->companyVault, 'company_id' => $auth->company_id])->first();
                if ($CompanyValut->value < $actualAMT) {
                    return ["status" => 0, "message" => "Insufficient vualt balance!"];
                }
            }
            if ($request->instrumentCode) {
                if (transactions::where(['instrumentCode' => $request->instrumentCode])->count() > 0) {
                    return ["status" => 0, "message" => "Transaction Instrument Code Already Exist!"];
                }
            }
            $backdate = date("Y-m-d H:i:s");
            $user = User::where(['uuid' => $userid, 'company_id' => $auth->company_id])->first();
            if (!$user) {
                return ["status" => 0, "message" => "Invalid User ID! $user->company_id != $auth->company_id"];
            }

            if (checkcustomerrestriction($user->id, 2)) {
                $restrictionMSG = '';
                foreach (checkcustomerrestrictionData($user->id, 2) as $rdata) {
                    $restrictionMSG .= $rdata->remark;
                }
                return ["status" => 0, "message" => "User is Restricted from performing this transaction, Reason: $restrictionMSG! "];
            }
            if ($user->status_id != 1) {
                return ["status" => 0, "message" => "User is Either Disabled or on Pending Entry!"];
            }
            if ($user->branch_id == null) {
                return (["status" => 0, "message" => "No branch Assign to this customer"]);
            }
            $BranchWallet = BranchWallet::where('branch_id', $user->branch_id)->first();
            if ($BranchWallet->balance < $actualAMT) {
                return ["status" => 0, "message" => "Insufficient balance in Branch Wallet"];
            }
            //transaction charges

            $chargeamt = 0;
            $minimum_balance = $companydata->setting->minimum_balance;
            if ($wcharge > 0) {
                if ($companydata->setting->min_withdraw_charge < $companydata->setting->max_withdraw_charge && $companydata->setting->max_withdraw_charge > 0) {
                    $minwc = $companydata->setting->min_withdraw_charge;
                    $maxwc = $companydata->setting->max_withdraw_charge;
                    $wchargeValue = $companydata->setting->withdraw_charge_state ? ($wcharge / 100) * $amount : $wcharge;
                    $wcper = $companydata->setting->withdraw_charge_state ? '%' : $companydata->currency->symbol;
                    $minCharge = $companydata->setting->withdraw_charge_state ? ($companydata->setting->min_withdraw_charge / 100) * $amount : $companydata->setting->min_withdraw_charge;
                    if ($wchargeValue >= $minCharge  && $wchargeValue <= $companydata->setting->max_withdraw_charge) {
                        $chargeamt =  $companydata->setting->withdraw_charge_state ? (($wcharge / 100) * $amount) : $wcharge;
                        $actualAMT += $chargeamt;
                    } else {
                        $chargeamt =  $companydata->setting->max_withdraw_charge;
                        $actualAMT += $chargeamt;
                        // return ["status" => 0, "message" => "Withdrawal charge ($wchargeValue) is between " . $wcper . $minwc . " and " . $wcper . $maxwc];
                    }
                }
            }
            if ($companydata->setting->backdate->id == 2) {
                if ($request->backdate != null) {
                    $date_back = date_create($request->backdate);
                    $backdate = date_format($date_back, "Y-m-d H:i:s");
                }
            }

            if ($amount > 10) {
                if ($user) {
                    $wb_account_details = wb_account_details::where(['user_id' => $user->id, 'virtual_accnum' => $account_type])->first();
                    $account_typeid = $wb_account_details->id;
                    if (!isset($wb_account_details->user_wallet->balance)) {
                        user_wallet::create([
                            'wb_account_detail_id' => $wb_account_details->id,
                            'balance' => 0
                        ]);
                    }
                    $customerCurrency = Currency::where('id',$wb_account_details->currency_id)->first();
                    if($customerCurrency){
                        $closing = ClosingBalance::where(['company_id'=>$auth->company_id,'staff_id'=>$auth->id,'branch_id'=>$auth->branch_id,'currency'=>$customerCurrency->code])->first();
                        if($closing ){
                            $closing->ten_thousand_quantity -= $request->ten_thousand_quantity ;
                            $closing->ten_thousand_amount -= ($request->ten_thousand_quantity??0) * 10000;
                            $closing->five_thousand_quantity -= $request->five_thousand_quantity;
                            $closing->five_thousand_amount -=  ($request->five_thousand_quantity??0) * 5000;
                            $closing->two_thousand_quantity -= $request->two_thousand_quantity;
                            $closing->two_thousand_amount -= ($request->two_thousand_quantity??0) * 2000;
                            $closing->one_thousand_quantity -= $request->one_thousand_quantity;
                            $closing->one_thousand_amount -= ($request->one_thousand_quantity??0) * 1000;
                            $closing->five_hundred_note_quantity -= $request->five_hundred_note_quantity;
                            $closing->five_hundred_note_amount -=($request->five_hundred_note_quantity??0) * 500;
                            $closing->five_hundred_coin_quantity -= $request->five_hundred_coin_quantity;
                            $closing->five_hundred_coin_amount -= $request->five_hundred_coin_quantity?($request->five_hundred_coin_quantity / 100):0;
                            $closing->one_hundred_quantity -= $request->one_hundred_quantity;
                            $closing->one_hundred_amount -= ($request->one_hundred_quantity??0) * 100;
                            $closing->fifthy_quantity -= $request->fifthy_quantity;
                            $closing->fifthy_amount -= ($request->fifthy_quantity??0) * 50;
                            $closing->twenty_five_amount -= ($request->twenty_five_quantity??0) * 25;
                            $closing->twenty_five_quantity -= $request->twenty_five_quantity;
                            $closing->ten_quantity -= $request->ten_quantity;
                            $closing->ten_amount -= ($request->ten_quantity??0) * 10;
                            $closing->five_quantity -= $request->five_quantity;
                            $closing->five_amount -= ($request->five_quantity??0) * 5;
                            $closing->two_quantity -= $request->two_quantity;
                            $closing->two_amount -= ($request->two_quantity??0) * 2;
                            $closing->one_quantity -= $request->one_quantity;
                            $closing->one_amount -= ($request->one_amount??0) * 1;
                            $closing->total = ($closing->one_amount + $closing->two_amount + $closing->five_amount + $closing->ten_amount + $closing->twenty_five_amount + $closing->fifthy_amount + $closing->one_hundred_amount + $closing->five_hundred_coin_amount +  $closing->five_hundred_note_amount + $closing->one_thousand_amount + $closing->two_thousand_amount + $closing->five_thousand_amount +$closing->ten_thousand_amount);

                            if($closing->status == 'closed'){
                                return ["status" => 0, "message" => "Open Till Balance before performing transaction, goto accounting report and then click on Close/Open Account and use the open button"];
                            }
                            if($closing->total < $amount ){
                                return ["status" => 0, "message" => "The amount you want to withdraw is above open balance"];
                            }
                        }
                    }
                    $transactions = transactions::where(['account_id' => $account_typeid, 'amount' => $request->amount])->orderBy('id', 'desc')->first();
                    $time_is_ok = true;
                    if ($transactions) {
                        $dbtimestamp = strtotime($transactions->created_at);
                        if (time() - $dbtimestamp < 1 * 60) {
                            $time_is_ok = false;
                        }
                    }
                    if ($time_is_ok) {
                        $balanceBefore = $wb_account_details->user_wallet->balance;
                        $minwith = $wb_account_details->accountType->minimum_withdraw;
                        $maxwith = $wb_account_details->accountType->max_withdraw;
                        if ($amount < $minwith) {
                            return ["status" => 0, "message" => "Minimum Withdrawal for this plan is $minwith"];
                        }
                        if ($amount > $maxwith) {
                            return ["status" => 0, "message" => "Maximum Withdrawal for this plan is $maxwith"];
                        }
                        $walletID =  $wb_account_details->user_wallet->id;
                        $transactionId = generate_txnID();
                        if ($request->created_byx != null && cando('can-set-txn-created-by|root') && $auth->company->setting->enable_txn_created_by == 1) {
                            $request->created_by = User::where('uuid', $request->created_byx)->first()->id;
                        }
                        if ($request->created_by != null) {
                            $created_by = User::where(['id' => $request->created_by, 'company_id' => $auth->company_id])->first();
                        } else {
                            $created_by = User::where('id', $auth->id)->first();
                        }
                        // $staffwallet = $created_by->wallet->commission;
                        $staffcanDeposit = 1;
                        $remark = 1;
                        $finalRemark = 2;
                        $telleroption = 0;
                        if ($request->teller_option_id != null) {
                            if ($request->teller_option_id == 4) {
                                $tellerData = ['recordData' => $created_by, 'amount' => $amount, 'transaction_id' => $request->transaction_id];
                                // DB::commit();

                                return $this->debitStaffTXN($tellerData);
                            }
                        }
                        if ($balanceBefore >= $actualAMT) {
                            $balanceAfter = $balanceBefore; //initialize balance after TXN
                            $balanceAfter = $balanceBefore - $actualAMT;
                            $telleroption = 1;
                            if (($balanceAfter < $minimum_balance) && ($minimum_balance != 0 || $minimum_balance != null)) {

                                return ["status" => 0, "message" => "Minimum Account balance must be $minimum_balance"];
                            }
                            $savingTypeMinimumBalance = $wb_account_details->accountType->minimum_balance;
                            if ($savingTypeMinimumBalance != null && $savingTypeMinimumBalance > 0) {
                                if ($balanceAfter < $savingTypeMinimumBalance) {

                                    return ["status" => 0, "message" => "Minimum balance for this product is $savingTypeMinimumBalance"];
                                }
                            }
                            if ($request->pending == null  && !cando('root|direct-debit-credit')) {
                                //  if($companyTellerOption == 3){
                                if (PendingSaving::where(['uuid' => $request->uuid, 'pending' => 'debit', 'status_id' => 1])->count() > 0) {
                                    return ['status' => 0, 'message' => 'Request Already Sent !'];
                                }
                                $pandingsaving = PendingSaving::create([
                                    'uuid' => $request->uuid,
                                    'amount' => $request->amount,
                                    'company_id' => $auth->company_id,
                                    'desc' => $request->desc,
                                    'account_type' => $request->account_type,
                                    'status_id' => 1,
                                    'pending' => 'debit',
                                    'w_charge' => $request->w_charge,
                                    'created_by' => ($request->created_byx != null && cando('can-set-txn-created-by|root')) ? $request->created_by : $auth->id,
                                    'transaction_type_id' => $request->transaction_type_id,
                                    'teller_option_id' => $companyTellerOption,
                                    'companyVault' => $request->companyVault,
                                    'instrumentCode' => $request->instrumentCode,
                                    "ledger_cr" => $SystemLegder,
                                    "ledger_dr" => $ledger,
                                    "initiator" => $request->initiator,

                                ]);
                                if ($pandingsaving) {
                                    if ($request->transaction_type_id == 93) {
                                        $transfer_log = new Beneficiary;
                                        $transfer_log->name  = ($request->transaction_type_id == 8) ? $request->transferAccountName : $user->first_name . ' ' . $user->last_name;
                                        $transfer_log->bank_id  = $request->bank_id;
                                        $transfer_log->accountNo  = $request->accountNo;
                                        $transfer_log->pending_saving_id  = $pandingsaving->id;
                                        $transfer_log->save();
                                    }
                                    if ($companyTellerOption == 4) { //Using Leap Method Debit Customer and then Debit teller when the Txn is approved
                                        $data_TXN = [
                                            "balance_before" => $balanceBefore,
                                            "amount" => $amount,
                                            "withdrawal_charge" => $chargeamt,
                                            "description" => $desc,
                                            "account_id" => $account_typeid,
                                            "branch_id" => $created_by->branch_id,
                                            "transaction_status_id" => 1,
                                            "transaction_reference" => $transactionId,
                                            "transaction_type_id" => $request->transaction_type_id, //debit 7
                                            "credit_debit_type_id" => 2, //debit 2
                                            "transaction_remarks_id" => $remark, //success 2
                                            "balance_after" => $balanceAfter,
                                            "created_date"  => $backdate,
                                            "created_by" => $created_by->id,
                                            "walletID" => $walletID,
                                            "chargeamt" => $chargeamt,
                                            "user" => $user,
                                            "created_byData" => $created_by,
                                            "ledger_cr" => $SystemLegder,
                                            "ledger_dr" => $ledger,
                                            "initiator" => $request->initiator,
                                        ];
                                        $resp_from_customer = $this->debitTypeLeapMFIMethod($data_TXN, $pandingsaving->id);
                                        // DB::commit();/
                                        return $resp_from_customer;
                                    }
                                    return ['status' => 1, 'message' => 'Debit Request Pending!'];
                                } else {
                                    return ['status' => 0, 'message' => 'Debit Request Failed!'];
                                }
                                //  }
                            }
                            $data = [
                                "balance_before" => $balanceBefore,
                                "amount" => $amount,
                                "withdrawal_charge" => $chargeamt,
                                "description" => $desc,
                                "account_id" => $account_typeid,
                                "branch_id" => $created_by->branch_id,
                                "transaction_status_id" => 1,
                                "transaction_reference" => $transactionId,
                                "transaction_type_id" => $request->transaction_type_id, //debit 7
                                "credit_debit_type_id" => 2, //debit 2
                                "transaction_remarks_id" => $remark, //success 2
                                "balance_after" => $balanceAfter,
                                "created_date"  => $backdate,
                                "created_by" => $created_by->id,
                                'instrumentCode' => $request->instrumentCode,
                                "ledger_cr" => $SystemLegder,
                                "ledger_dr" => $ledger,
                                "initiator" => $request->initiator,

                            ];
                            if ($staffcanDeposit) {
                                $transaction = transactions::create($data);
                                if ($transaction) {

                                    $user_wallet = user_wallet::where('id', $walletID)->update(['balance' => $balanceAfter]);
                                    if ($user_wallet) {
                                        if ($transaction->update([
                                            "transaction_remarks_id" => $finalRemark,
                                            "modified_by" => $created_by->id
                                        ])) {
                                            if($customerCurrency){
                                                if($closing){
                                                    $closing->save();
                                                }
                                            }


                                            if ($request->companyVault) {
                                                if (isset($auth->company->Transactionsettings)) {
                                                    if ($auth->company->Transactionsettings->view_vault_on_txn == 1) {
                                                        if (!fundVaultByID(['vaultID' => $request->companyVault, 'value' => 0 - $amount, 'company_id' => $auth->company_id, 'created_by' => $created_by->id, 'branch_id' => $created_by->branch_id, 'transaction_id' => $transaction->id])) {
                                                            // DB::rollBack();
                                                            return ["status" => 0, "message" => "I've try reversing this transaction due to some issues!"];
                                                        }
                                                    }
                                                }
                                            }
                                            if ($chargeamt > 0) {
                                                fundVault(['type' => 'withdrawal_charge', 'value' => $chargeamt, 'company_id' => $auth->company_id, 'created_by' => $created_by->id, 'branch_id' => $created_by->branch_id, 'transaction_id' => $transaction->id]);
                                            }
                                            $BranchWallet = BranchWallet::where('branch_id', $user->branch_id)->first();
                                            $BranchWallet->balance -= $amount;
                                            $BranchWallet->save();
                                            sendSMS(['txnid' => $transaction->id, 'userid' => $account_typeid], "Debit Amt:$amount Desc: $desc Avail Bal:$balanceAfter Date:$backdate Ref:$transactionId", false, true, 'Debit Transaction');
                                            $redirectlink = "transaction-receipt/$transactionId";
                                            // DB::commit();
                                            return ["status" => 1, "message" => "Transaction Successful!", "redirectlink" => $redirectlink];
                                        } else {
                                            // DB::rollBack();
                                            return ["status" => 0, "message" => "Error!"];
                                        }
                                    } else {
                                        // DB::rollBack();
                                        return ["status" => 0, "message" => "Error!"];
                                    }
                                } else {
                                    return ["status" => 0, "message" => "Error!"];
                                }
                            } else {
                                return ["status" => 0, "message" => "Staff Wallet is too low for this transaction!"];
                            }
                        } else {
                            return ["status" => 0, "message" => "Insufficient Balance $balanceBefore >= $actualAMT"];
                        }
                    } else {
                        return ["status" => 0, "message" => env('txn_restrict_msg')];
                    }
                } else {
                    return ["status" => 0, "message" => "User Not Found!"];
                }
            } else {
                return ["status" => 0, "message" => "Amount should be above 10! "];
            }

            //  return redirect()->route('demo.index');
        } catch (\Exception $e) {
            //if there is an error/exception in the above code before commit, it'll rollback
            // DB::rollBack();
            return ["status" => 0, "message" => $e->getMessage() . $e->getLine()];
        }
    }
    public function debitTypeLeapMFIMethod($data, $tracePendingID)
    {
        try {
                // DB::beginTransaction();

            $walletID = $data['walletID'];
            $balanceAfter = $data['balance_after'];
            $created_by = $data['created_byData'];
            $chargeamt = $data['chargeamt'];
            $user = $data['user'];
            $account_typeid = $data['account_id'];
            $amount = $data['amount'];
            $desc = $data['description'];
            $backdate = $data['created_date'];
            $transactionId = $data['transaction_reference'];
            $transaction = transactions::create($data);
            $pending = PendingSaving::where('id', $tracePendingID)->first();
            $pending->transaction_id = $transaction->id;
            $pending->save();
            $Beneficiary = Beneficiary::where('pending_saving_id', $tracePendingID)->first();
            if ($Beneficiary) {
                $Beneficiary->transaction_id = $transaction->id;
                $Beneficiary->save();
            }
            if ($transaction) {
                $user_wallet = user_wallet::where('id', $walletID)->update(['balance' => $balanceAfter]);
                if ($user_wallet) {
                    if ($transaction->update([
                        "transaction_remarks_id" => 2,
                        "modified_by" => $created_by->id
                    ])) {
                        if ($chargeamt > 0) {
                            fundVault(['type' => 'withdrawal_charge', 'value' => $chargeamt, 'company_id' => auth()->user()->company_id, 'created_by' => $created_by->id, 'branch_id' => $created_by->branch_id, 'transaction_id' => $transaction->id]);
                        }
                        $BranchWallet = BranchWallet::where('branch_id', $user->branch_id)->first();
                        $BranchWallet->balance -= $amount;
                        $BranchWallet->save();
                        sendSMS(['txnid' => $transaction->id, 'userid' => $account_typeid], "Debit Amt:$amount Desc: $desc Avail Bal:$balanceAfter Date:$backdate Ref:$transactionId", false, true, 'Debit Transaction');
                        $redirectlink = "transaction-receipt/$transactionId";
                        // DB::commit();

                        return ["status" => 1, "message" => "Transaction Successful!", "redirectlinkx" => $redirectlink];
                    }
                } else {
                    // DB::rollback();

                    return ["status" => 0, "message" => "Wallet Error 406!"];
                }
            } else {
                // DB::rollback();

                return ["status" => 0, "message" => "Error Initiating Transaction 407!"];
            }
        } catch (\Exception $e) {
            // DB::rollback();

            return ["status" => 0, "message" => $e->getMessage() . $e->getLine()];
        }
    }
    public function debitStaffTXN($recordData)
    {
        try {
            $auth = auth()->user();
            $created_by = $recordData['recordData'];
            $amount = $recordData['amount'];
            $transactionid = $recordData['transaction_id'];
            $staffInitials = StaffWallet::where(['user_id' => $created_by->id])->first();

            $tellerbalance = $staffInitials->balance - $amount;
            transactions::create(
                [
                    "balance_before" => $staffInitials->balance,
                    "amount" => $amount,
                    "description" => "Debit from customer Debit Transaction",
                    "branch_id" => $created_by->branch_id,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 45, //teller balance
                    "transaction_status_id" => 1,
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $tellerbalance,
                    "created_date"  => date('Y-m-d'),
                    "created_by" => $created_by->id,
                    "modified_by" => $auth->id,
                    'parent_transaction_id' => $transactionid,
                    "ledger_cr" => (systemLedgerAccount('Customer Saving Deposit', $auth->company_id))->id, //
                    "ledger_dr" => (systemLedgerAccount('Staff Wallet', $auth->company_id))->id,

                ]
            );
            $staffInitials->balance = $tellerbalance;
            $staffInitials->save();
            // StaffWallet::where(['id' => $created_by->wallet->id])->update(['balance' => $tellerbalance]);
            CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'staff_id'=>$created_by->id,'type'=>'debit', 'amount'=>$amount]);

            return ["status" => 1, "message" => "Teller Debited Successfully!"];
        } catch (\Exception $e) {
            return ["status" => 0, "message" => $e->getMessage() . $e->getLine()];
        }
    }
    public function DebitCustomer(Request $request)
    {
        if (!cando('manage-saving|debit-customer|root')) return abort(403);
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'desc' => 'required',
            'uuid' => 'required',
            'account_type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            return response()->json($datax);
        }
        return response()->json($this->DebitCustomer_script($request));
    }
    public function txnReceipt($id)
    {
        if (!cando('manage-saving|credit-customer|debit-customer|root')) return abort(403);
        $is_root = cando('manage-saving|credit-customer|debit-customer|root');
        $auth = auth()->user();
        if (!$is_root) {
            $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
                ->where(['users.company_id' => $auth->company_id, 'transaction_reference' => $id, 'transactions.created_by' => $auth->id]);
        } else {
            $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
                ->where(['users.company_id' => $auth->company_id, 'transaction_reference' => $id]);
        }
        $query = $query->first();
        $data = [
            'category_name' => 'apps',
            'page_name' => 'form',
            'page_menu' => 'sub_invoice',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 1,
            'txnid' => $id,
            'txn' => $query,
            'success' => "Transaction Successful!"
        ];
        return view('company.invoice.transaction_invoice')->with($data);
    }
    public function pending($company_name)
    {
        if (!cando('pending-credit-debit|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'saving',
            'page_menu' => 'pending_saving',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'branch' => branch::has('author')->where('company_id', auth()->user()->company_id)->get(),

            'alt_menu' => 0,

        ];
        return view('company.savings.saving_pending')->with($data);
    }
    public function display_pending(Request $request)
    {
        if (!cando('pending-credit-debit|root')) {
            abort(403);
            return 0;
        }
        $auth = auth()->user();
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];
        $sortColumns = array(
            0 => 'users.first_name',
            1 => 'wb_account_details.virtual_accnum',
            2 => 'wb_account_details.virtual_accnum',
            3 => 'wb_account_details.virtual_accnum',
            4 => 'pending_savings.status_id',
            5 => 'pending_savings.desc',
            6 => 'pending_savings.created_by',
            7 => 'pending_savings.pending',

        );
        $query = PendingSaving::join('wb_account_details', 'wb_account_details.virtual_accnum', '=', 'pending_savings.account_type')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->where(['pending_savings.status_id' => 1, 'users.company_id' => $auth->company_id])
            ->select('users.company_id', 'pending_savings.*', 'users.first_name', 'users.last_name', 'wb_account_details.virtual_accnum');
        $is_root = cando('pending-credit-debit|root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', $auth->id);
        }
        if (!empty($filter)) {
            $query->where('pending_savings.pending', 'like', '%' . $filter . '%');
        }
        if ($request->staff != null) {
            $staffid = User::where(['uuid' => $request->staff, 'company_id' => $auth->company_id])->first();
            if ($staffid) {
                $query->where('pending_savings.created_by', $staffid->id);
            } else {
                $query->where('pending_savings.created_by', 0);
            }
        }

        //   $query = $query->where('users.company_id', $auth->company_id);
        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);

        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {

            $btnlink = $infoData->id;
            $created_by = User::find($infoData->created_by);
            $cryptedID = "'" . Crypt::encryptString($infoData->id) . "'";
            $btn = '<input type="checkbox" name="pendingid[]" value="' . $infoData->id . '">';
            $btn2 = '<button type="button" class="btn btn-info" onclick="viewPendingDetails(' . $cryptedID . ')">View</button>';
            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->account_type,
                number_format($infoData->amount, 2),
                $infoData->w_charge,
                $infoData->pending,
                $infoData->desc,
                $created_by->first_name . ' ' . $created_by->last_name,
                $btn2,
                $btn,
            ];
        }

        return $json;
    }
    public function post_pending_saving(Request $request)
    {
        if (!cando('pending-credit-debit|root')) return abort(403);

        $validator = Validator::make($request->all(), [
            'pendingid' => 'required',
            'action' => 'required',
        ]);

        if ($validator->fails()) {

            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }

        $penidarray = $request->pendingid;
        $status = 0;
        $num_success = 0;
        $failmsg = '';
        $num_fail = 0;
        for ($i = 0; $i < count($penidarray); $i++) {
            $penentry = PendingSaving::findorfail($penidarray[$i]);
            $created_by = User::where(['id' => $penentry->created_by, 'company_id' => auth()->user()->company_id])->first();
            if ($created_by) {
                if ($request->action == 1) {
                    $is_root = cando('pending-credit-debit|root');
                    if (!$is_root) {
                        return response()->json(['status' => 0, 'message' => 'Restricted']);
                    }
                    if ($penentry->pending == 'credit') {
                        $res = $this->CreditCustomer_script($penentry);
                    } else if ($penentry->pending == 'debit') {
                        $res = $this->DebitCustomer_script($penentry);
                    }
                    $response = json_encode($res['status']);
                    if ($response == 1) {
                        $num_success++;
                        $status = 1;
                        // $Beneficiary = Beneficiary::where('pending_saving_id',$penentry->id)->delete();

                        $penentry->status_id = 2;
                        $penentry->save();
                    } else {
                        $failmsg .= "<br> Error:" . json_encode($res['message']);
                        $num_fail++;
                    }
                }
                if ($request->action == 2) {
                    // $Beneficiary = Beneficiary::where('pending_saving_id',$penentry->id)->delete();
                    $penentry->status_id = 2;
                    $penentry->save();
                }
            }
        }
        return response()->json(['status' => $status, 'message' => "Total of $num_success Approved and $num_fail failed! $failmsg"]);
    }
    public function singleApproval(Request $request)
    {
        if (!cando('pending-credit-debit|root')) return abort(403);
        $validator = Validator::make($request->all(), [
            'pendingid' => 'required',
            'action' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            return response()->json($datax);
        }
        $penidarray  = Crypt::decryptString($request->pendingid);
        $status = 0;
        $num_success = 0;
        $failmsg = '';
        $num_fail = 0;
        $penentry = PendingSaving::where(['id'=>$penidarray,'status_id'=>1])->first();
        if(!$penentry){
            return response()->json(['status' => 0, 'message' => 'Pending entry already treated!']);
        }

        $created_by = User::where(['id' => $penentry->created_by, 'company_id' => auth()->user()->company_id])->first();
        if ($created_by) {
            if ($request->action == 1) {
                $is_root = cando('pending-credit-debit|root');
                if (!$is_root) {
                    return response()->json(['status' => 0, 'message' => 'Restricted']);
                }
                if ($penentry->pending == 'credit') {
                    $res = $this->CreditCustomer_script($penentry);
                } else if ($penentry->pending == 'debit') {
                    $res = $this->DebitCustomer_script($penentry);
                }
                $response = json_encode($res['status']);
                if ($response == 1) {
                    $num_success++;
                    $status = 1;
                    // $Beneficiary = Beneficiary::where('pending_saving_id',$penentry->id)->delete();
                    $penentry->status_id = 2;
                    $penentry->save();
                }
                $msg =  json_encode($res['message']);
                return response()->json(['status' => $status, 'message' => "$msg!"]);
            }
            if ($request->action == 2) {
                //penentry  Reversing account_type
                $transactionId = generate_txnID();
                if ($penentry->teller_option_id == 4 && $penentry->transaction_id != null) { //if user was debitted
                    $txn = transactions::where('id', $penentry->transaction_id)->first();
                    if ($txn) {
                        $user_wallet = user_wallet::where('wb_account_detail_id', $txn->account_id)->first();
                        if ($user_wallet) {
                            $txn->transaction_status_id = 0;
                            $txn->save();
                            $balanceAfter = ($user_wallet->balance + $txn->amount) + $txn->withdrawal_charge;
                            transactions::where([
                                "balance_before" => $user_wallet->balance,
                                "amount" => $txn->amount,
                                "withdrawal_charge" => 0,
                                "description" => $txn->description,
                                "account_id" => $txn->account_id,
                                "branch_id" => $txn->branch_id,
                                "transaction_status_id" => 1,
                                "transaction_reference" => $transactionId,
                                "transaction_type_id" => 26, //debit 7
                                "credit_debit_type_id" => 1, //debit 2
                                "transaction_remarks_id" => 2, //success 2
                                "balance_after" => $balanceAfter,
                                "created_date"  => date('Y-m-d'),
                                "parent_transaction_id" => $txn->id,
                                "created_by" => auth()->user()->id
                            ]);
                            $user_wallet->balance = $balanceAfter;
                            $user_wallet->save();
                        }
                    }
                }
                // $Beneficiary = Beneficiary::where('pending_saving_id',$penentry->id)->delete();
                $penentry->status_id = 2;
                $penentry->save();
                return response()->json(['status' => 1, 'message' => "Transaction Rejected!"]);
            }
        }
    }

    public function GeneralCreditCustomer_script($request)
    {
        try {
            $userid = $request->uuid;
            $companydata = auth()->user()->company;
            $amount = $request->amount;
            $desc = $request->desc;
            $account_type = $request->account_type;
            $backdate = date("Y-m-d H:i:s");
            $transaction_status_id = $request->transaction_status_id;
            $transaction_type_id = $request->transaction_type_id;
            $credit_debit_type_id = $request->credit_debit_type_id;
            if ($request->created_by != null) {
                $created_by = User::find($request->created_by);
            } else {
                $created_by = User::find(auth()->user()->id);
            }
            $user = User::where(['uuid' => $userid, 'company_id' => auth()->user()->company_id])->first();
            $useruniqueID = $user->id;
            if ($user->status_id != 1) {
                return ["status" => 0, "message" => "User is Either Disabled or on Pending Entry!"];
            }
            if ($user->branch_id == null) {
                return ["status" => 0, "message" => "No branch Assign to this customer"];
            }
            if ($user) {
                $wb_account_details = wb_account_details::where(['user_id' => $useruniqueID, 'id' => $account_type])->first();
                $account_typeid = $wb_account_details->id;
                if (!isset($wb_account_details->user_wallet->balance)) {
                    user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance' => 0
                    ]);
                }
                $balanceBefore = $wb_account_details->user_wallet->balance;
                $walletID =  $wb_account_details->user_wallet->id;
                $transactionId = generate_txnID();
                // $staffwallet = $created_by->wallet->commission;
                $remark = 1;
                $finalRemark = 2;
                $balanceAfter = $balanceBefore + $amount;
                $staffTXN = null;
                $data = [
                    "balance_before" => $balanceBefore,
                    "amount" =>  $amount,
                    "description" => $desc,
                    "account_id" => $account_typeid,
                    "branch_id" => $created_by->branch_id,
                    "transaction_status_id" => $transaction_status_id,
                    "transaction_reference" => $transactionId,
                    "transaction_type_id" => $transaction_type_id, //debit 7
                    "credit_debit_type_id" => $credit_debit_type_id, //debit 2
                    "transaction_remarks_id" => $remark, //success 2
                    "balance_after" => $balanceAfter,
                    "created_date"  => $backdate,
                    "created_by" => $created_by->id,
                    "ledger_cr" => $request->ledger_cr,
                    "ledger_dr" => $request->ledger_dr,

                ];
                $transaction = transactions::create($data);
                $BranchWallet = BranchWallet::where('branch_id', $user->branch_id)->first();
                $BranchWallet->balance += $amount;
                $BranchWallet->save();
                //created_date
                if ($transaction) {
                    $user_wallet = user_wallet::where('id', $walletID)->update(['balance' => $balanceAfter]);
                    if ($user_wallet) {
                        if ($transaction->update([
                            "transaction_remarks_id" => $finalRemark,
                            "modified_by" => $created_by->id
                        ])) {
                            $userinfo = auth()->user();

                            $smsstate = sendSMS(['txnid' => $transaction->id, 'userid' => $account_typeid], "Credit Amt:$amount Desc: $desc Avail Bal:$balanceAfter Date:$backdate Ref:$transactionId", false, true, 'Credit Transaction');
                            $redirectlink = "transaction-receipt/$transactionId";
                            return ["status" => 1, "message" => "Transaction Successful! $finalRemark", "redirectlink" => $redirectlink, "smsstate" => $smsstate];
                        }
                    } else {
                        return ["status" => 0, "message" => "Error!"];
                    }
                } else {
                    return ["status" => 0, "message" => "Error!"];
                }
            } else {
                return ["status" => 0, "message" => "User Not Found!"];
            }
            // DB::commit();
            //  return redirect()->route('demo.index');
        } catch (\Exception $e) {
            //if there is an error/exception in the above code before commit, it'll rollback
            //DB::rollBack();
            return ["status" => 0, "message" => $e->getMessage() . $e->getLine()];
        }
    }
    public function viewTXNDetails($pendingID)
    {   //use for viewing pending users before approving
        $ids = Crypt::decryptString($pendingID);
        $pendingdata = PendingSaving::with('user')->with('teller')->where('id', $ids)->first();
        if ($pendingdata) {
            $wbdetails = wb_account_details::with('user_wallet')->with('accountType')->where('user_id', $pendingdata->user->id)->get();
            $teller = User::with('staff_wallet')->where(['id' => $pendingdata->created_by, 'company_id' => auth()->user()->company_id])->first();
            if ($teller) {
                $Beneficiary = Beneficiary::with('bank')->where('pending_saving_id', $pendingdata->id)->first();
                if (!$Beneficiary) {
                    $Beneficiary = false;
                }
                $logo = ($pendingdata->user->profile_pic != null) ? url(str_replace('public', 'storage', $pendingdata->user->profile_pic)) : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                $tellerlogo = ($teller->profile_pic != null) ? url(str_replace('public', 'storage', $teller->profile_pic)) : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                return json_encode(['account' => $wbdetails, 'data' => $pendingdata, 'teller' => $teller, 'customerlogo' => $logo, 'tellerLogo' => $tellerlogo, 'beneficiary' => $Beneficiary]);
            }
        }
        return [];
    }
}
