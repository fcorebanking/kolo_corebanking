<?php
namespace App\Http\Controllers;

use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\DomAccount;
use App\Models\Esusu;
use App\Models\Expenses;
use App\Models\Investmentbooking;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\savings_product;
use App\Models\StaffWallet;
use App\Models\Temp_Charges;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *Route::get('/dashboard', function () {
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $companyRef  = auth()->user()->company->wallet->refaccount;
        $customerUrl = url('/company/customer-registration/' . $companyRef);
        $data        = [
            'registrationLink' => $customerUrl,
            'category_name'    => 'dashboard',
            'page_name'        => 'sales',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'loanID'           => false,
            'alt_menu'         => 0,
        ];
        // $pageName = 'sales';
        return view('company.dashboard')->with($data);
    }
    public function dashboard()
    {
        $companyRef     = auth()->user()->company->wallet->refaccount;
        $wallet         = auth()->user()->company->allWallet;
        $closingBalance = auth()->user()->company->companyClosingBalance;
        $customerUrl    = url('/company/customer-registration/' . $companyRef);
        $data           = [
            'registrationLink' => $customerUrl,
            'category_name'    => 'dashboard',
            'page_name'        => 'company_dashboard',
            'page_menu'        => 'company_dashboard',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'loanID'           => false,
            'alt_menu'         => 0,
            'wallet'           => $wallet,
            'closingBalance'   => $closingBalance,

        ];

        // if(cando('advance-dashboard|root')){
        return view('company.dashboard')->with($data);
        // }else{

        // }
    }
    public function dashboard_json(Request $request)
    {
        $viewtype            = $is_staff            = cando('root');
        $auth                = auth()->user();
        $created_by          = $auth->id;
        $currency            = $auth->company->currency->symbol;
        $staff_wallet        = $auth->wallet->balance;
        $company_wallet      = $auth->company->wallet;
        $vaultBalance        = 0;
        $transaction_type    = $total_entry_charge    = $total_deposit_withdraw    = $latest_txt    = $lagest_txt    = $transaction_type_day    = $brancharray    = [];
        $users_staff         = $users_customer         = $pending_customer         = $total_daily         = 0;
        $monthly_dep_txn     = $dep_data     = [];
        $card_display        = $dep_data_label        = [];
        $company_wallet_data = $day_txn = [];
        $user                = $unpaid_sms_Charges                = 0;
        $user_card_pending   = $user_card_has   = 0;
        $total_branch        = 0;
        ##Transaction Records
        if ($request->page == "latestTXN") {
            $latest_txt = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->where(['users.company_id' => $auth->company_id])
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->orderBy('id', 'desc')
                ->select('transactions.*', 'wb_account_details.virtual_accnum', 'credit_debit_types.type', 'transaction_types.transaction_type', 'users.first_name', 'users.last_name');

            if (! $is_staff) {
                $latest_txt = $latest_txt->where(['transactions.created_by' => $created_by]);
            }
            $latest_txt = $latest_txt->take(5)->get();
            $lagest_txt = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->where(['users.company_id' => $auth->company_id])
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->orderBy('amount', 'desc')
                ->select('transactions.*', 'wb_account_details.virtual_accnum', 'credit_debit_types.type', 'transaction_types.transaction_type', 'users.first_name', 'users.last_name');
            if (! $is_staff) {
                $lagest_txt = $lagest_txt->where(['transactions.created_by' => $created_by]);
            }
            $lagest_txt = $lagest_txt->take(5)->get();

        }
                                                        ##Branch records
                                                        ##Customer Record
        if ($request->page == "branch_customer_Stat") { //Customer Record
            $branch = Branch::leftjoin('users', 'users.branch_id', '=', 'branch.id')
                ->leftjoin('wb_account_details', 'users.id', '=', 'wb_account_details.user_id')
                ->leftjoin('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                ->where(['branch.company_id' => $auth->company_id, 'users.user_type_id' => 1])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->groupBy('branch.id')
                ->select('branch.id', DB::raw('SUM(user_wallets.balance) AS total_balance'), 'branch.branch_name')
                ->get();
            if ($branch) {
                foreach ($branch as $branchdata) {
                    $total_branch  += 1;
                    $brancharray[]  = ['name' => $branchdata->branch_name, 'balance' => ($branchdata->total_balance)];
                }
            }
            $users_customer   = User::where(['user_type_id' => '1', 'status_id' => '1', 'company_id' => $auth->company_id]);
            $is_root          = cando('root');
            $users_staff      = User::where(['user_type_id' => '2', 'company_id' => $auth->company_id])->count();
            $pending_customer = User::where(['user_type_id' => '1', 'status_id' => '7', 'company_id' => $auth->company_id]);
            if (! $is_root) {
                $users_customer   = $users_customer->where(['account_officer_id' => $auth->id]);
                $pending_customer = $pending_customer->where(['account_officer_id' => $auth->id]);
                $users_staff      = 0;
                $total_branch     = 0;
            }
            $users_customer   = $users_customer->count();
            $pending_customer = $pending_customer->count();
            $user             = ['label_user' => ['Customer', 'Staff', 'branch'], 'data' => [$users_customer, $users_staff, $total_branch]];
        }
                                            // var_dump($user);
                                            ##TXN Type
        if ($request->page == "txn_type") { //TXN Type
            $transaction_type_day = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_status_id' => 1])
                ->select('transactions.amount')
                ->whereDate('transactions.created_date', '=', date('Y-m-d'));
            if (! $is_staff) {
                $transaction_type_day = $transaction_type_day->where(['transactions.created_by' => $created_by]);
            }
            $transaction_type_day = $transaction_type_day->take(5)->get();
            if ($transaction_type_day) {
                foreach ($transaction_type_day as $transaction_type_day_data) {
                    $day_txn[]    = $transaction_type_day_data->amount;
                    $total_daily += $transaction_type_day_data->amount;
                }
            }
            $transaction_type = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_status_id' => 1])
                ->groupBy('transactions.transaction_type_id')
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->select('transactions.transaction_type_id', 'transactions.credit_debit_type_id', 'credit_debit_types.type', 'transaction_types.transaction_type', DB::raw('sum(transactions.amount) as total'))
                ->whereDate('transactions.created_date', '=', date('Y-m-d'));
            if (! $is_staff) {
                $transaction_type = $transaction_type->where(['transactions.created_by' => $created_by]);
            }
            $transaction_type = $transaction_type->get();
        }
        //end
        $sum_monthdata = 0;
                                               ##Monthly TXN
        if ($request->page == "monthly_txn") { //Monthly TXN Type
            $monthly_dep_txn = DB::table('transactions')
                ->join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->select(DB::raw('sum(transactions.amount) as total'), DB::raw('date(transactions.created_date) as dates'))
                ->groupBy('transactions.created_date')
                ->orderBy('transactions.created_date', 'desc')
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where('users.company_id', $auth->company_id)
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->whereYear('transactions.created_date', '=', date('Y'));
            if (! $is_staff) {
                $monthly_dep_txn = $monthly_dep_txn->where(['transactions.created_by' => $created_by]);
            }
            $monthly_dep_txn = $monthly_dep_txn->get();
            if ($monthly_dep_txn) {
                foreach ($monthly_dep_txn as $monthly_dep_txn_data) {
                    $sum_monthdata += $monthly_dep_txn_data->total;
                }
            }
            if ($monthly_dep_txn) {
                foreach ($monthly_dep_txn as $dep) {
                    $dep_data[]       = $dep->total;
                    $dep_data_label[] = $dep->dates;
                }
            }
        }
        //end
        ##Temp Charges TXN
        if ($request->page == "temp_charges") {
            $unpaid_sms_Charges = Temp_Charges::join('wb_account_details', 'wb_account_details.id', '=', 'temp__charges.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->where(['users.company_id' => $auth->company_id, 'temp__charges.status' => 0])
                ->sum('temp__charges.amount');
            if (! $is_staff) {
                $unpaid_sms_Charges = 0;
            }
        }
        ##end
        $products = [];
        ##Savings Product stat
        if ($request->page == "savings_stat") {
            $products = savings_product::join('wb_account_details', 'wb_account_details.account_product_id', '=', 'savings_products.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', 'wb_account_details.id')
                ->join('users', 'wb_account_details.user_id', '=', 'users.id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->groupBy('wb_account_details.account_product_id')
                ->select(DB::raw('sum(user_wallets.balance) as balance'), 'savings_products.savings_product')
                ->where(['users.company_id' => $auth->company_id]);
            if (! $is_staff) {
                $products = $products->where('users.account_officer_id', $created_by);
            }
            $products = $products->get();
            if (! cando('advance-dashboard|root')) {
                $products = [];
            }
        }
        ##end
        $smsCountAmount = 0;
        ##SMS amount stat
        if ($request->page == "smsAmount_stat") {
            $smsCountAmount = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_type_id' => 33])->sum('amount');
        }
        ##end
        $card_display = [
            'deposit'         => ['total' => 0],
            'withdraw'        => ['total' => 0],
            'today_deposit'   => ['total' => 0],
            'today_withdraw'  => ['total' => 0],
            'sms'             => $smsCountAmount,
            'unpaidsms'       => $unpaid_sms_Charges,
            'total_new_entry' => User::where(['company_id' => $auth->company_id, 'status_id' => 7, 'user_type_id' => 1])->count(),
            'product'         => $products,
            'systemBalance'   => 0,
        ];
        ##Transaction history for deposit and withdraw
        if ($request->page == "transaction_hist_txn") {
            $total_deposit_withdraw = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->where(['users.company_id' => $auth->company_id])
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_type_id', '!=', 35)
                ->where('transactions.transaction_type_id', '!=', 39)
                ->where('transactions.transaction_type_id', '!=', 73)
                ->where('transactions.transaction_type_id', '!=', 98)
                ->where('transactions.transaction_type_id', '!=', 68)
                ->where('transactions.transaction_status_id', 1)
                ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 1 THEN amount ELSE 0 END) AS totalDeposit')
                ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 2 THEN amount ELSE 0 END) AS totalWithdraw');
            if (! $is_staff) {
                $total_deposit_withdraw = $total_deposit_withdraw->where(['transactions.created_by' => $auth->id]);
            }
            $total_deposit_withdraw = $total_deposit_withdraw->first();
            if ($total_deposit_withdraw) {
                $card_display['deposit'] = [
                    'total' => $total_deposit_withdraw->totalDeposit,
                ];
                $card_display['withdraw'] = [
                    'total' => $total_deposit_withdraw->totalWithdraw,
                ];
            }
            $currentDate                  = Carbon::now();
            $total_today_deposit_withdraw = DB::table('transactions')
                ->join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->where(['users.company_id' => $auth->company_id]);
            if (! $is_staff) {
                $total_today_deposit_withdraw = $total_today_deposit_withdraw->where('transactions.created_by', $auth->id);
            }
            $total_today_deposit_withdraw = $total_today_deposit_withdraw->whereNull('transactions.parent_transaction_id')
                ->whereNotIn('transactions.transaction_type_id', [35, 39, 73, 98, 68])
                ->where('transactions.transaction_status_id', 1)
                ->whereDate('transactions.created_date', '=', $currentDate->toDateString()) // Filter by current day
                ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 1 THEN amount ELSE 0 END) AS totalDeposit')
                ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 2 THEN amount ELSE 0 END) AS totalWithdraw')
                ->first();
            $card_display['today_deposit'] = [
                'total' => $total_today_deposit_withdraw->totalDeposit,
            ];
            $card_display['today_withdraw'] = [
                'total' => $total_today_deposit_withdraw->totalWithdraw,
            ];

            $vaultBalance = $auth->wallet->balance;
            //visit later
            $systembalance = user_wallet::join('wb_account_details', 'wb_account_details.id', '=', 'user_wallets.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })
                ->where(['users.company_id' => $auth->company_id])->sum('user_wallets.balance');
            $card_display['systemBalance'] = $systembalance; //$card_display['deposit']['total'] - $card_display['withdraw']['total'];
        }
        //birthday notification
        if ($request->page == "company_n_data") {
            $user_card_pending   = User::where(['company_id' => $auth->company_id, 'user_card_status_id' => 2, 'status_id' => 1])->count();
            $user_card_has       = User::where(['company_id' => $auth->company_id, 'user_card_status_id' => 3, 'status_id' => 1])->count();
            $company_wallet_data = [
                'sms'                  => $company_wallet->sms,
                'whatsapp'             => $company_wallet->whatsapp,
                'balance'              => $company_wallet->balance,
                'ussd'                 => $company_wallet->ussd,
                'card_balance'         => $company_wallet->card_balance,
                'subscription_balance' => $company_wallet->subscription_balance,

            ];
        }

        if ($viewtype) {
            $total_record = [
                'latest_txn'           => $latest_txt,
                'largest_txn'          => $lagest_txt,
                'monthly_data'         => ['txn' => ['monthly_TXN' => $dep_data, 'monthly_label' => $dep_data_label]],
                'transaction_type_day' => $day_txn,
                'total_daily'          => $total_daily,
                'transaction_type'     => $transaction_type,
                'total_month'          => $sum_monthdata,
                'display_card'         => $card_display,
                'currency'             => $currency,
                'permission_to_see'    => $viewtype,
                'auth_wallet'          => $staff_wallet,
                'pending_entry'        => $pending_customer,
                'user_has_card'        => $user_card_has,
                'user_penidng_card'    => $user_card_pending,
                'vaultBalance'         => $vaultBalance,
            ];
            if ($viewtype) {
                $total_record['wallet_data'] = $company_wallet_data;
                $total_record['branch']      = $brancharray;
                $total_record['user']        = $user;
            } else {
                $total_record['user'] = ['label_user' => ['Customer', 'Staff', 'branch'], 'data' => [$user['data'][0], 0, 0]];
            }
        } else {
            $total_record = [
                'latest_txn'           => $latest_txt,
                'largest_txn'          => $lagest_txt,
                'transaction_type_day' => $day_txn,
                'transaction_type'     => $transaction_type,
                'total_daily'          => $total_daily,
                'total_month'          => $sum_monthdata,
                'display_card'         => $card_display,
                'currency'             => $currency,
                'permission_to_see'    => $viewtype,
                'auth_wallet'          => $staff_wallet,
                'pending_entry'        => $pending_customer,
                'monthly_data'         => ['txn' => ['monthly_TXN' => $dep_data, 'monthly_label' => $dep_data_label]],
                'vaultBalance'         => $vaultBalance,
            ];
            $total_record['user'] = [
                'label_user' => ['Customer', 'Staff', 'branch'],
                'data'       => [($user) ? $user['data'][0] : 0, 0, 0]];
        }
        return json_encode($total_record);
    }
    public function dashboard_json_full(Request $request)
    {
        $viewtype           = $is_staff           = cando('root');
        $auth               = auth()->user();
        $created_by         = $auth->id;
        $total_entry_charge = $total_deposit_withdraw = $latest_txt = $lagest_txt = $transaction_type_day = $brancharray = [];
        $users_staff        = $users_customer        = $pending_customer        = $total_daily        = 0;
        $monthly_dep_txn    = $dep_data    = [];
        $card_display       = $dep_data_label       = [];
        $day_txn            = [];
        $is_root            = cando('root');
        $unpaid_sms_Charges = 0;
        $company_wallet     = $auth->company->wallet;
        $currency           = $auth->company->currency->symbol;
        $user_card_pending  = $user_card_has  = 0;
        if ($request->page == 'loan') {
            $loanComplete = loan::join('users', 'users.id', '=', 'loans.user_id')
                ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 10])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })->count();
            $loanPending = loan::join('users', 'users.id', '=', 'loans.user_id')
                ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 3])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })->count();
            $loanDesbusment = loan::join('users', 'users.id', '=', 'loans.user_id')
                ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 2])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })->count();
            $activeloandetails = loan::join('users', 'users.id', '=', 'loans.user_id')
                ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 1])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                });
            $loanActiveData = $activeloandetails->select(DB::raw('SUM(loans.principal) AS InitPamount'), DB::raw('SUM(loans.loanBalance) AS TloanBalance'), DB::raw('SUM(loans.principalLeft) AS TprincipalLeft'), DB::raw('SUM(loans.InterestLeft) AS TInterestLeft'))->get();
            $loanActive     = $activeloandetails->count();
            $loanWriteOff   = loan::join('users', 'users.id', '=', 'loans.user_id')
                ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 7])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })->count();
            $paidrec = LoanRepayment::join('users', 'users.id', '=', 'loan_repayments.created_by')->select(DB::raw('SUM(loan_repayments.amount) AS paidamount'), DB::raw('SUM(loan_repayments.P) AS Ppaidamount'), DB::raw('SUM(loan_repayments.I) AS Ipaidamount'), DB::raw('SUM(loan_repayments.loanFee) AS loanFeePaid'), DB::raw('SUM(loan_repayments.mgrtFee) AS mgrtFeePaid'))->where(['users.company_id' => $auth->company_id, 'loan_repayments.status_id' => 1])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })->get();
            $loanPackage = ['currency' => $currency, 'completeCount' => $loanComplete, 'pendingCount' => $loanPending, 'disbursmentCount' => $loanDesbusment, 'activeData' => $loanActiveData, 'loanActiveCount' => $loanActive, 'writeOff' => $loanWriteOff, 'paymentData' => $paidrec];
            return json_encode($loanPackage);
        }
        if ($request->page == 'esusu') {
            $esusu = $esusu_count = Esusu::join('wb_account_details', 'wb_account_details.id', '=', 'esusus.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(['users.company_id' => $auth->company_id, 'esusus.status_id' => 1])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                });
            if (! $is_root) {
                $esusu_count = $esusu_count->where(['account_officer_id' => $auth->id]);
                $esusu       = $esusu->where(['account_officer_id' => $auth->id]);
            }
            $esusu_count  = $esusu_count->count();
            $esusu        = $esusu->select(DB::raw('Sum(accum_charge) as accum_charge'), DB::raw('Sum(wallet) as wallet'), DB::raw('Sum(withdrawable) as withdrawable'))->get();
            $total_record = [
                'esusu' => (cando('advance-dashboard|root')) ? ['currency' => $currency, 'count' => $esusu_count, 'record' => $esusu] : ['count' => 0, 'record' => [['accum_charge' => 0, 'wallet' => 0, 'withdrawable' => 0]]],
            ];
            return json_encode($total_record);
        }
        if ($request->page == 'pending') {
            $pending_customer = User::where(['user_type_id' => '1', 'status_id' => '7', 'company_id' => $auth->company_id]);
            if (! $is_root) {
                $pending_customer = $pending_customer->where(['account_officer_id' => $auth->id]);
            }
            $pending_customer   = $pending_customer->count();
            $total_entry_charge = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->where('transactions.parent_transaction_id', null)
                ->where('transactions.transaction_status_id', 1)
                ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_type_id' => 30])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                });
            if (! $is_staff) {
                $total_entry_charge = $total_entry_charge->where(['transactions.created_by' => $created_by]);
            }
            $total_entry_charge = $total_entry_charge->sum('amount');
            $card_display       = [
                'currency'        => $currency,
                'total_new_entry' => $total_entry_charge,
            ];
            return json_encode($card_display);
        }
        if ($request->page == "txncharge") {
            $Depositcharge = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $smstxnCharge = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $withdrawCharge = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $withdrawCharge2 = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $smstxnCharge  = $smstxnCharge->where(['transactions.transaction_type_id' => 33])->sum('transactions.amount');
            $Depositcharge = $Depositcharge->where(['transactions.transaction_type_id' => 27, 'credit_debit_type_id' => 1])->sum('transactions.amount');

            $withdrawCharge2 = $withdrawCharge2->where(['transactions.transaction_type_id' => 27, 'credit_debit_type_id' => 2])->sum('transactions.amount');
            $withdrawCharge  = $withdrawCharge->where('transactions.withdrawal_charge', '>', 0)->sum('transactions.withdrawal_charge');
            //backherer
            return json_encode(['currency' => $currency, 'smscharge' => $smstxnCharge, 'Depositcharge' => $Depositcharge, 'withdrawCharge2' => $withdrawCharge2, 'withdrawCharge' => $withdrawCharge]);
        }
        if ($request->page == 'unpaid_sms_Charges') {
            $unpaid_sms_Charges = Temp_Charges::join('wb_account_details', 'wb_account_details.id', '=', 'temp__charges.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->where(['users.company_id' => $auth->company_id, 'temp__charges.status' => 0])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
                ->sum('temp__charges.amount');
            if (! $is_staff) {
                $unpaid_sms_Charges = 0;
            }
            $card_display = [
                'currency'  => $currency,
                'unpaidsms' => $unpaid_sms_Charges,
            ];
            return json_encode($card_display);
        }
        if ($request->page == 'investment') {
            $investment_complete = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $investment_total = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            $investment_active = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
            if (! $is_staff) {
                $investment_total    = $investment_total->where('users.account_officer_id', $created_by);
                $investment_active   = $investment_active->where('users.account_officer_id', $created_by);
                $investment_complete = $investment_complete->where('users.account_officer_id', $created_by);
            }
            $investment_complete = $investment_complete->where('status', 3)->sum('amount');
            $investment_active   = $investment_active->where('status', 1)->sum('amount');
            $investment_total    = $investment_total->sum('amount');
            return json_encode((cando('advance-dashboard|root')) ? ['active' => $investment_active, 'complete' => $investment_complete, 'total' => $investment_total, 'currency' => $currency] : ['active' => 0, 'complete' => 0, 'total' => 0, 'currency' => 'NGN']);
        }
        if ($request->page == 'product') {
            $products = savings_product::join('wb_account_details', 'wb_account_details.account_product_id', '=', 'savings_products.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', 'wb_account_details.id')
                ->join('users', 'wb_account_details.user_id', '=', 'users.id')
                ->groupBy('wb_account_details.account_product_id')
                ->select(DB::raw('sum(user_wallets.balance) as balance'), 'savings_products.savings_product')
                ->where(['users.company_id' => $auth->company_id])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                });
            if (! $is_staff) {
                $products = $products->where('users.account_officer_id', $created_by);
            }
            $products = $products->get();
            if (! cando('advance-dashboard|root')) {
                $products = [];
            }
            $card_display = [
                'product'  => $products,
                'currency' => $currency,
            ];
            return json_encode($card_display);
        }
        //expenses
        if ($request->page == "expenses") {
            $Expenses = Expenses::join('users', 'users.id', '=', 'expenses.created_by')
                ->where('users.company_id', $auth->company_id)->sum('expenses.amount');
            return ['expenses' => $Expenses, 'currency' => $currency];
        }
        //staff wallet DB::raw('sum(transactions.sms_charge) as sms'),
        if ($request->page == "staffwallet") {
            $staffwallet_query = StaffWallet::join('users', 'users.id', '=', 'staff_wallet.user_id')
                ->where('users.company_id', $auth->company_id)->select(DB::raw('sum(staff_wallet.commission) as comission'), DB::raw('sum(staff_wallet.balance) as balance'))->first();
            return ['staff' => $staffwallet_query, 'currency' => $currency];
        }
        if ($request->page == 'dom') {
            $domaccount = DomAccount::join('wb_account_details', 'wb_account_details.id', '=', 'dom_accounts.wb_account_detail_id')
                ->join('users', 'wb_account_details.user_id', '=', 'users.id')
                ->where(['users.user_type_id' => '1', 'users.company_id' => $auth->company_id, 'dom_accounts.status' => 0, 'users.status_id' => 1]);
            if (! $is_staff) {
                $domaccount = $domaccount->where('users.account_officer_id', $created_by);
            }
            $domaccount   = $domaccount->count();
            $card_display = [
                'dom'      => $domaccount,
                'currency' => $currency,
            ];
            return json_encode($card_display);
        }

        // $card_display = [
        //   'unpaidsms' => $unpaid_sms_Charges,
        //   'total_new_entry' => $total_entry_charge,

        //   'product' => $products,
        //   'dom' => $domaccount
        // ];

        //birthday notification
        //  $today = User::where(['company_id'=>$auth->company_id])->whereMonth('dob', '=', Carbon::now()->format('m'))->whereDay('dob', '=', Carbon::now()->format('d'))->get();
        //  $tomorrow = User::where(['company_id'=>$auth->company_id])->whereMonth('dob', '=', Carbon::now()->format('m'))->whereDay('dob', '=', Carbon::now()->addDay()->format('d'))->get();

        // $total_record = [
        //   'transaction_type_day' => $day_txn,
        //   'transaction_type' => $transaction_type,
        //   'total_daily' => $total_daily,
        //   'display_card' => $card_display,
        //   'currency' => $currency,
        //   'permission_to_see' => $viewtype,
        //   'auth_wallet' => $staff_wallet,
        //   'pending_entry' => $pending_customer,
        //   'user_has_card' => $user_card_has,
        //   'user_penidng_card' => $user_card_pending,
        //   'esusu' => (cando('advance-dashboard|root')) ? ['count' => $esusu_count, 'record' => $esusu] : ['count' => 0, 'record' => [['accum_charge' => 0, 'wallet' => 0, 'withdrawable' => 0]]],
        //   'loanRec' => $loanPackage
        // ];
        // if ($viewtype) {
        //   $total_record['wallet_data'] = $company_wallet_data;
        //   $total_record['branch'] = $brancharray;
        //   $total_record['user'] = $user;
        // //   loanPackage
        // } else {
        //   $total_record['user'] = ['label_user' => ['Customer', 'Staff', 'branch'], 'data' => [$user['data'][0], 0, 0]];
        // }
        return json_encode(['message' => 'No Record']);
    }

    public function dashboard_json_full2()
    {
        $viewtype   = $is_staff   = cando('root');
        $auth       = auth()->user();
        $created_by = $auth->id;
        $latest_txt = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->where(['users.company_id' => $auth->company_id, 'users.status_id' => 1])
            ->where('transactions.parent_transaction_id', null)
            ->where('transactions.transaction_status_id', 1)
            ->orderBy('id', 'desc')
            ->select('transactions.*', 'wb_account_details.virtual_accnum', 'credit_debit_types.type', 'transaction_types.transaction_type', 'users.first_name', 'users.last_name');
        if (! $is_staff) {
            $latest_txt = $latest_txt->where(['transactions.created_by' => $created_by]);
        }
        $latest_txt = $latest_txt->take(5)->get();

        $lagest_txt = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->where(['users.company_id' => $auth->company_id])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
            ->orderBy('amount', 'desc')
            ->where('transactions.parent_transaction_id', null)
            ->where('transactions.transaction_status_id', 1)
            ->select('transactions.*', 'wb_account_details.virtual_accnum', 'credit_debit_types.type', 'transaction_types.transaction_type', 'users.first_name', 'users.last_name');
        if (! $is_staff) {
            $lagest_txt = $lagest_txt->where(['transactions.created_by' => $created_by]);
        }
        $lagest_txt = $lagest_txt->take(5)->get();

        $transaction_type_day = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_status_id' => 1])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
            ->select('transactions.amount')
            ->where('transactions.parent_transaction_id', null)
            ->where('transactions.transaction_status_id', 1)
            ->whereDate('transactions.created_date', '=', date('Y-m-d'));
        if (! $is_staff) {
            $transaction_type_day = $transaction_type_day->where(['transactions.created_by' => $created_by]);
        }
        $transaction_type_day = $transaction_type_day->take(5)->get();
        $total_branch         = 0;
        $branch               = branch::where('company_id', $auth->company_id)->get();
        $brancharray          = [];
        if ($branch) {
            foreach ($branch as $branchdata) {
                $total_branch  += 1;
                $brancharray[]  = ['name' => $branchdata->branch_name, 'balance' => $branchdata->wallet->balance];
            }
        }
        $esusu = $esusu_count = Esusu::join('wb_account_details', 'wb_account_details.id', '=', 'esusus.wb_account_detail_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->where(['users.company_id' => $auth->company_id, 'esusus.status_id' => 1])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });
        $users_customer = User::where(['user_type_id' => '1', 'company_id' => $auth->company_id])
            ->where(function ($query) {
                $query->where('status_id', 1)
                    ->orWhere('status_id', 2)
                    ->orWhere('status_id', 8);
            });
        $is_root     = cando('root');
        $users_staff = User::where(['user_type_id' => '2', 'company_id' => $auth->company_id])
            ->where(function ($query) {
                $query->where('status_id', 1)
                    ->orWhere('status_id', 2)
                    ->orWhere('status_id', 8);
            })->count();
        $pending_customer = User::where(['user_type_id' => '1', 'status_id' => '7', 'company_id' => $auth->company_id]);
        if (! $is_root) {
            $users_customer   = $users_customer->where(['account_officer_id' => $auth->id]);
            $pending_customer = $pending_customer->where(['account_officer_id' => $auth->id]);
            $users_staff      = 0;
            $total_branch     = 0;
            $esusu_count      = $esusu_count->where(['account_officer_id' => $auth->id]);
            $esusu            = $esusu->where(['account_officer_id' => $auth->id]);
        }
        $esusu_count = $esusu_count->count();
        $esusu       = $esusu->select(DB::raw('Sum(accum_charge) as accum_charge'), DB::raw('Sum(wallet) as wallet'), DB::raw('Sum(withdrawable) as withdrawable'))->get();

        $users_customer   = $users_customer->count();
        $pending_customer = $pending_customer->count();

        $user = ['label_user' => ['Customer', 'Staff', 'branch'], 'data' => [$users_customer, $users_staff, $total_branch]];
        // var_dump($user);
        $day_txn     = [];
        $total_daily = 0;
        if ($transaction_type_day) {
            foreach ($transaction_type_day as $transaction_type_day_data) {
                $day_txn[]    = $transaction_type_day_data->amount;
                $total_daily += $transaction_type_day_data->amount;
            }
        }
        $transaction_type = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_status_id' => 1])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })
            ->groupBy('transactions.transaction_type_id')
            ->where('transactions.parent_transaction_id', null)
            ->where('transactions.transaction_status_id', 1)
            ->select('transactions.transaction_type_id', 'transactions.credit_debit_type_id', 'credit_debit_types.type', 'transaction_types.transaction_type', DB::raw('sum(transactions.amount) as total'))
            ->whereDate('transactions.created_date', '=', date('Y-m-d'));
        if (! $is_staff) {
            $transaction_type = $transaction_type->where(['transactions.created_by' => $created_by]);
        }
        $transaction_type = $transaction_type->get();

        $company_wallet  = $auth->company->wallet;
        $monthly_dep_txn = DB::table('transactions')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->select(DB::raw('sum(transactions.amount) as total'), DB::raw('date(transactions.created_date) as dates'))
            ->groupBy('transactions.created_date')
            ->where('transactions.parent_transaction_id', null)
            ->where(['transactions.transaction_status_id' => 1])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
            ->orderBy('transactions.created_date', 'desc')
            ->where('users.company_id', $auth->company_id)
            ->whereYear('transactions.created_date', '=', date('Y'));
        if (! $is_staff) {
            $monthly_dep_txn = $monthly_dep_txn->where(['transactions.created_by' => $created_by]);
        }
        $monthly_dep_txn = $monthly_dep_txn->get();
        $sum_monthdata   = 0;
        if ($monthly_dep_txn) {
            foreach ($monthly_dep_txn as $monthly_dep_txn_data) {
                $sum_monthdata += $monthly_dep_txn_data->total;
            }
        }
        $company_wallet_data = ['sms' => $company_wallet->sms, 'whatsapp' => $company_wallet->whatsapp, 'balance' => $company_wallet->balance, 'ussd' => $company_wallet->ussd];
        $dep_data            = [];
        $dep_data_label      = [];
        if ($monthly_dep_txn) {
            foreach ($monthly_dep_txn as $dep) {
                $dep_data[]       = $dep->total;
                $dep_data_label[] = $dep->dates;
            }
        }
        $total_deposit_withdraw = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->where(['users.company_id' => $auth->company_id])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
        //   ->groupBy('transactions.credit_debit_type_id')
            ->where('transactions.parent_transaction_id', null)
            ->where('transactions.transaction_status_id', 1)
            ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 1 THEN amount ELSE 0 END) AS totalDeposit')
            ->selectRaw('SUM(CASE WHEN credit_debit_type_id = 2 THEN amount ELSE 0 END) AS totalWithdraw');
        if (! $is_staff) {
            $total_deposit_withdraw = $total_deposit_withdraw->where(['transactions.created_by' => Carbon::today()]);
        }
        $total_deposit_withdraw = $total_deposit_withdraw->first();
        if ($total_deposit_withdraw) {
            $card_display['deposit']  = ['total' => cando('advance-dashboard|root') ? $total_deposit_withdraw->totalDeposit : 0];
            $card_display['withdraw'] = ['total' => cando('advance-dashboard|root') ? $total_deposit_withdraw->totalWithdraw : 0];
        }

        $total_entry_charge = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->where('transactions.parent_transaction_id', null)
            ->where(['transactions.transaction_status_id' => 1])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
            ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_type_id' => 30]);
        if (! $is_staff) {
            $total_entry_charge = $total_entry_charge->where(['transactions.created_by' => $created_by]);
        }
        $total_entry_charge = $total_entry_charge->sum('amount');

        $unpaid_sms_Charges = Temp_Charges::join('wb_account_details', 'wb_account_details.id', '=', 'temp__charges.wb_account_detail_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->where(['users.company_id' => $auth->company_id, 'temp__charges.status' => 0])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            })
            ->sum('temp__charges.amount');

        if (! $is_staff) {
            //$unpaid_sms_Charges = $unpaid_sms_Charges->where(['temp_charges.created_by'=>$created_by]);
            $unpaid_sms_Charges = 0;
        }
        $products = savings_product::join('wb_account_details', 'wb_account_details.account_product_id', '=', 'savings_products.id')
            ->join('user_wallets', 'user_wallets.wb_account_detail_id', 'wb_account_details.id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->groupBy('wb_account_details.account_product_id')
            ->select(DB::raw('sum(user_wallets.balance) as balance'), 'savings_products.savings_product')
            ->where(['users.company_id' => $auth->company_id])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });
        if (! $is_staff) {
            $products = $products->where('users.account_officer_id', $created_by);
        }
        $products   = $products->get();
        $domaccount = DomAccount::join('wb_account_details', 'wb_account_details.id', '=', 'dom_accounts.wb_account_detail_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => $auth->company_id, 'dom_accounts.status' => 0])
            ->where(function ($query) {
                $query->where('users.status_id', 1)
                    ->orWhere('users.status_id', 2)
                    ->orWhere('users.status_id', 8);
            });
        if (! $is_staff) {
            $domaccount = $domaccount->where('users.account_officer_id', $created_by);
        }
        $domaccount = $domaccount->count();
        if (! cando('advance-dashboard|root')) {
            $products = [];
        }
        $card_display = [
            'deposit'         => [],
            'withdraw'        => [],
            'sms'             => transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->where(['users.company_id' => $auth->company_id, 'transactions.transaction_type_id' => 33])
                ->where(function ($query) {
                    $query->where('users.status_id', 1)
                        ->orWhere('users.status_id', 2)
                        ->orWhere('users.status_id', 8);
                })->sum('transactions.amount'),
            'unpaidsms'       => $unpaid_sms_Charges,
            'total_new_entry' => $total_entry_charge,
            'investment'      => (cando('advance-dashboard|root')) ? ['active' => $investment_active, 'complete' => $investment_complete, 'total' => $investment_total] : ['active' => 0, 'complete' => 0, 'total' => 0],
            'product'         => $products,
            'dom'             => $domaccount,
        ];

        //birthday notification
        //  $today = User::where(['company_id'=>$auth->company_id])->whereMonth('dob', '=', Carbon::now()->format('m'))->whereDay('dob', '=', Carbon::now()->format('d'))->get();
        //  $tomorrow = User::where(['company_id'=>$auth->company_id])->whereMonth('dob', '=', Carbon::now()->format('m'))->whereDay('dob', '=', Carbon::now()->addDay()->format('d'))->get();

        $currency          = $auth->company->currency->symbol;
        $staff_wallet      = $auth->wallet->balance;
        $user_card_pending = User::where(['company_id' => $auth->company_id, 'user_card_status_id' => 2, 'status_id' => 1])->count();
        $user_card_has     = User::where(['company_id' => $auth->company_id, 'user_card_status_id' => 3, 'status_id' => 1])->count();
        $total_record      = [
            'latest_txn'           => $latest_txt,
            'largest_txn'          => $lagest_txt,
            'monthly_data'         => ['txn' => ['monthly_TXN' => $dep_data, 'monthly_label' => $dep_data_label]],
            'transaction_type_day' => $day_txn,
            'transaction_type'     => $transaction_type,
            'total_daily'          => $total_daily,
            'total_month'          => $sum_monthdata,
            'display_card'         => $card_display,
            'currency'             => $currency,
            'permission_to_see'    => $viewtype,
            'auth_wallet'          => $staff_wallet,
            'pending_entry'        => $pending_customer,
            'user_has_card'        => $user_card_has,
            'user_penidng_card'    => $user_card_pending,
            'esusu'                => (cando('advance-dashboard|root')) ? ['count' => $esusu_count, 'record' => $esusu] : ['count' => 0, 'record' => [['accum_charge' => 0, 'wallet' => 0, 'withdrawable' => 0]]],
            'loanRec'              => [],
        ];
        if ($viewtype) {
            $total_record['wallet_data'] = $company_wallet_data;
            $total_record['branch']      = $brancharray;
            $total_record['user']        = $user;
        } else {
            $total_record['user'] = ['label_user' => ['Customer', 'Staff', 'branch'], 'data' => [$user['data'][0], 0, 0]];
        }
        return json_encode($total_record);
    }
    public function loanstat()
    {
        $viewtype     = $is_staff     = cando('root');
        $auth         = auth()->user();
        $created_by   = $auth->id;
        $loanComplete = loan::join('users', 'users.id', '=', 'loans.user_id')
            ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 10])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })->count();
        $loanPending = loan::join('users', 'users.id', '=', 'loans.user_id')
            ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 3])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })->count();
        $loanDesbusment = loan::join('users', 'users.id', '=', 'loans.user_id')
            ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 2])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })->count();
        $activeloandetails = loan::join('users', 'users.id', '=', 'loans.user_id')
            ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 1])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });
        $loanActiveData = $activeloandetails->select(DB::raw('SUM(loans.principal) AS InitPamount'), DB::raw('SUM(loans.loanBalance) AS TloanBalance'), DB::raw('SUM(loans.principalLeft) AS TprincipalLeft'), DB::raw('SUM(loans.InterestLeft) AS TInterestLeft'))->get();
        $loanActive     = $activeloandetails->count();
        $loanWriteOff   = loan::join('users', 'users.id', '=', 'loans.user_id')
            ->where(['users.company_id' => $auth->company_id, 'loans.loan_status_id' => 7])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })->count();
        $paidrec = LoanRepayment::join('users', 'users.id', '=', 'loan_repayments.created_by')->select(DB::raw('SUM(loan_repayments.amount) AS paidamount'), DB::raw('SUM(loan_repayments.P) AS Ppaidamount'), DB::raw('SUM(loan_repayments.I) AS Ipaidamount'), DB::raw('SUM(loan_repayments.loanFee) AS loanFeePaid'), DB::raw('SUM(loan_repayments.mgrtFee) AS mgrtFeePaid'))->where(['users.company_id' => $auth->company_id, 'loan_repayments.status_id' => 1])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        })->get();
        $loanPackage = ['completeCount' => $loanComplete, 'pendingCount' => $loanPending, 'disbursmentCount' => $loanDesbusment, 'activeData' => $loanActiveData, 'loanActiveCount' => $loanActive, 'writeOff' => $loanWriteOff, 'paymentData' => $paidrec];
        return json_encode($loanPackage);

    }
    public function investmentStat()
    {
        $viewtype            = $is_staff            = cando('root');
        $auth                = auth()->user();
        $created_by          = $auth->id;
        $investment_complete = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });
        $investment_total = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });

        $investment_active = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')->where(['users.company_id' => $auth->company_id])->where(function ($query) {
            $query->where('users.status_id', 1)
                ->orWhere('users.status_id', 2)
                ->orWhere('users.status_id', 8);
        });
        if (! $is_staff) {
            $investment_total    = $investment_total->where('users.account_officer_id', $created_by);
            $investment_active   = $investment_active->where('users.account_officer_id', $created_by);
            $investment_complete = $investment_complete->where('users.account_officer_id', $created_by);
        }
        $investment_complete = $investment_complete->where('status', 3)->sum('amount');
        $investment_active   = $investment_active->where('status', 1)->sum('amount');
        $investment_total    = $investment_total->sum('amount');
        return json_encode([]);

    }
    /*public function fundwallet(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'amount' => 'required|numeric|gt:0',
            'wallet' => 'required',
        ]);

        if ($validator->fails()) {

            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                $loop = 0;
                foreach ($messages as $message) {
                    if ($loop > 0) {
                        $errors .= ',';
                    }
                    $errors .= "$message ";
                }
            }

            return response()->json(['status' => 0, 'message' => $errors]);
        }

        $amount         = $request->amount;
        $wallet         = $request->wallet;
        $auth           = auth()->user();
        $company_wallet = company_wallet::where('company_id', $auth->company_id)->first();

        if ($amount < 1000) {
            return response()->json(['status' => 0, 'message' => 'Minimum wallet fund is 1000 !']);
        }
        if ($amount < 100000 && $wallet == "card_balance") {
            return response()->json(['status' => 0, 'message' => 'Minimum wallet fund is 100000 !']);
        }
        if ($company_wallet->subscription_balance > $amount) {
            //  $balanceBefore = $company_wallet->balance;

            //  $company_wallet->balance -= $amount;
            $txnType = 7;
            if ($wallet == 'sms') {
                $balanceBefore                         = $company_wallet->subscription_balance;
                $company_wallet->subscription_balance -= $amount;
                $company_wallet->sms                  += $amount;
                $txnType                               = 16;
            }

            if ($wallet == 'ussd') {
                $balanceBefore                         = $company_wallet->subscription_balance;
                $company_wallet->subscription_balance -= $amount;
                $company_wallet->ussd                 += $amount;
                $txnType                               = 15;
            }

            if ($wallet == 'whatsapp') {
                $balanceBefore                         = $company_wallet->subscription_balance;
                $company_wallet->subscription_balance -= $amount;
                $company_wallet->whatsapp             += $amount;
                $txnType                               = 29;
            }
            $this->responseMessageTopup($wallet, $balanceBefore, $amount, $auth, $txnType, $company_wallet);
        } else {
            return response()->json(['status' => 0, 'message' => "Insuficient Balance!"]);
        }

        if ($company_wallet->balance > $amount) {
            if ($wallet == 'card_balance') {
                $balanceBefore                 = $company_wallet->balance;
                $company_wallet->balance      -= $amount;
                $company_wallet->card_balance += $amount;
                $txnType                       = 9;
            }
            $this->responseMessageTopup($wallet, $balanceBefore, $amount, $auth, $txnType, $company_wallet);
        } else {
            return response()->json(['status' => 0, 'message' => "Insuficient Balance!"]);
        }
    }*/

    public function fundwallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'wallet' => 'required|in:sms,ussd,whatsapp,card_balance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => implode(', ', $validator->errors()->all()),
            ]);
        }

        $amount = $request->amount;
        $wallet = $request->wallet;
        $auth   = auth()->user();

        $companyWallet = company_wallet::where('company_id', $auth->company_id)->first();

        if (! $companyWallet) {
            return response()->json(['status' => 0, 'message' => 'Wallet not found']);
        }

        // Minimum checks
        if ($amount < 1000) {
            return response()->json(['status' => 0, 'message' => 'Minimum wallet fund is 1000!']);
        }

        if ($wallet == 'card_balance' && $amount < 100000) {
            return response()->json(['status' => 0, 'message' => 'Minimum card funding is 100000!']);
        }

        DB::beginTransaction();

        try {

            $txnType       = null;
            $balanceBefore = null;

            // 🔥 Wallet mapping (clean approach)
            $map = [
                'sms'      => ['field' => 'sms', 'type' => 16],
                'ussd'     => ['field' => 'ussd', 'type' => 15],
                'whatsapp' => ['field' => 'whatsapp', 'type' => 29],
            ];

            if (isset($map[$wallet])) {

                if ($companyWallet->subscription_balance < $amount) {
                    return response()->json(['status' => 0, 'message' => 'Insufficient Subscription Balance!']);
                }

                $balanceBefore = $companyWallet->subscription_balance;

                $companyWallet->subscription_balance     -= $amount;
                $companyWallet->{$map[$wallet]['field']} += $amount;

                $txnType = $map[$wallet]['type'];
            } elseif ($wallet === 'card_balance') {

                if ($companyWallet->balance < $amount) {
                    return response()->json(['status' => 0, 'message' => 'Insufficient Main Balance!']);
                }

                $balanceBefore = $companyWallet->balance;

                $companyWallet->balance      -= $amount;
                $companyWallet->card_balance += $amount;

                $txnType = 9;
            }

            $companyWallet->save();

            DB::commit();

            return $this->responseMessageTopup(
                $wallet,
                $balanceBefore,
                $amount,
                $auth,
                $txnType,
                $companyWallet
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            \logInfo([
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ], 'wallet funding error');

            return response()->json([
                'status'  => 0,
                'message' => 'Something went wrong',
            ]);
        }
    }

    public function createTransactionforTopup($wallet, $balanceBefore, $amount, $auth, $txnType, $company_wallet)
    {
        $desc          = strtoupper($wallet) . " Wallet Topup!";
        $transactionId = generate_txnID();
        $data          = [
            "balance_before"         => $balanceBefore,
            "amount"                 => $amount,
            "withdrawal_charge"      => 0,
            "description"            => $desc,
            "company_id"             => $auth->company_id,
            "branch_id"              => $auth->branch_id,
            "transaction_reference"  => $transactionId,
            "transaction_type_id"    => $txnType, //debit 7
            "credit_debit_type_id"   => 2,        //debit 2
            "transaction_remarks_id" => 2,        //success 2
            "balance_after"          => $company_wallet->balance,
            "created_date"           => date('Y-m-d H:i:s'),
            "created_by"             => $auth->id,
        ];
        transactions::create($data);
    }

    public function responseMessageTopup($wallet, $balanceBefore, $amount, $auth, $txnType, $company_wallet)
    {
        $this->createTransactionforTopup($wallet, $balanceBefore, $amount, $auth, $txnType, $company_wallet);

        if ($company_wallet->save()) {
            return response()->json(['status' => 1, 'message' => strtoupper($wallet) . " Wallet Topup Successful!"]);
        } else {
            return response()->json(['status' => 0, 'message' => "Operation Failed Try Again!"]);
        }
    }

    public function generate_ussd_string()
    {
        $auth           = auth()->user();
        $company_wallet = company_wallet::where('company_id', $auth->company_id)->first();
        $company        = Company::where('id', $auth->company_id)->first();

        $generate_cost = config('app.ussd_generate_charge');
        if ($company->ussd_string > 0) {
            return response()->json(['status' => 0, 'message' => "USSD String Already Generated refresh page!", "refresh" => 1]);
        }
        if ($company_wallet->balance < $generate_cost) {
            return response()->json(['status' => 0, 'message' => "Insuficient Balance!", "refresh" => 0]);
        }
        $string = 10;
        while (Company::where('ussd_string', $string)->count() > 0) {
            $string += 1;
        }
        $company->ussd_string     = $string;
        $company_wallet->balance -= $generate_cost;
        if ($company_wallet->save()) {
            $company->save();
            return response()->json(['status' => 1, 'message' => "USSD String Generated Successfully!", "refresh" => 1]);
        } else {
            return response()->json(['status' => 0, 'message' => "Operation Failed!", "refresh" => 0]);
        }
    }
    public function phoneVerification()
    {
        $data = [
            'category_name'    => 'forms',
            'page_name'        => 'input_mask',
            'page_menu'        => 'verification',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        $auth = auth()->user();
        if ($auth->company->status_id == 1 && $auth->phone_verification_status_id == 1) {
            return \redirect("dashboard");
        }

        return view('company.settings.phone_verification')->with($data);
    }
    public function phoneVerificationPage()
    {
        $auth = auth()->user();
        $data = [
            'category_name'    => 'phone_verification_page',
            'page_name'        => 'phone_verification_page',
            'page_menu'        => 'phone_verification_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'phone_number'     => $auth->phone,
            'status'           => $auth->company->status_id,
        ];

        return view('company.settings.phone_verification_page')->with($data);
    }
    public function termsOfServiceAgree()
    {
        $auth               = auth()->user();
        $company            = $auth->company;
        $company->status_id = 1;
        $company->save();

        return \redirect("dashboard");
    }

    public function termsOfServiceDisagree()
    {
        $auth               = auth()->user();
        $company            = $auth->company;
        $company->status_id = 2;
        $company->save();

        return \redirect("dashboard");
    }
    public function resendOtp(Request $request)
    {
        $auth       = auth()->user();
        $userAccunt = $auth;

        if ($userAccunt->user_type_id == 2) {

            //Third party SMS (Send OTP Code goes here)

            $otpCode = \generateOtp();

            $userAccunt->otp = $otpCode;
            $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

            $userAccunt->otp_expiration_date = $date;
            $userAccunt->save();

            $sms = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";

            sendSMSWithHollatag($userAccunt->phone, "WBLite", $sms);
            // sendVansoSMS($userAccunt->phone, $sms);
            $phone          = $userAccunt->phone;
            $firstCharacter = substr($phone, 0, 1);
            if ($firstCharacter == 0) {
                $str   = ltrim($phone, '0');
                $phone = '234' . $str;
            } //($phone, $msg, $type = 'transaction', $param = [])
            if ($userAccunt->whatsapp) {whatsapp($phone, $sms, 'myotp');}
            return response(['status' => 1, 'message' => 'OTP has been successfully sent please check your phone. If you did not recieve the code after two attempt please check your Whatsapp or contact support']);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid user reference or phone number'], 400);
        }
    }

    public function verifyPhoneNumber(Request $request)
    {
        $auth       = auth()->user();
        $userAccunt = $auth;
        if ($userAccunt->otp == $request->otp_code) {

            if (Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date)) > Carbon::now()) {

                $userAccunt->phone_verification_status_id = 1;
                $userAccunt->updated_at                   = Carbon::now();
                $userAccunt->save();

                return response(['status' => 1, 'message' => 'Phone Verification Successfuly']);
            } else {
                return response(['status' => 0, 'message' => 'OTP Expired. Kindly resend otp and verify phone']);
            }
        } else {
            return response()->json(['status' => 0, 'message' => 'Invalid OTP']);
        }
    }
    public function updateToken(Request $request)
    {
        try {
            $request->user()->update(['fcm_token' => $request->token]);
            return response(['status' => true, 'message' => 'Success', 'user' => $request->user()->id, 'token' => $request->token]);

        } catch (\Exception $e) {
            report($e);
            return response(['status' => false, 'message' => 'fail']);

        }
    }
    public function notification(Request $request)
    {
        $request->validate([
            'title'   => 'required',
            'message' => 'required',
        ]);

        try {
            $fcmTokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

            //Notification::send(null,new SendPushNotification($request->title,$request->message,$fcmTokens));

            /* or */

            //$auth->notify(new SendPushNotification($title,$message,$fcmTokens));

            /* or */
            // die(var_dump($fcmTokens));

            $lara = Larafirebase::withTitle($request->title)
                ->withBody($request->message)
                ->sendMessage($fcmTokens);
            return redirect()->back()->with('success', 'Notification Sent Successfully!!');

        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Something goes wrong while sending notification.');
        }
    }
    public function messanger()
    {
        if (! auth()->user()->company->setting->access_fcm_messanger) {
            return abort(403);
        }
        $data = [
            'category_name'    => 'dashboard',
            'page_name'        => 'fcm_message',
            'page_menue'       => 'fcm_message',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'loanID'           => false,
            'alt_menu'         => 0,
        ];
        // $pageName = 'sales';
        return view('company.fcm.notification')->with($data);
    }
    public function resetbalance()
    {
        $transactions = transactions::groupBy('branch_id')->get();
        if ($transactions) {
            foreach ($transactions as $transactionsData) {
                $BranchWallet = BranchWallet::where('branch_id', $transactionsData->branch_id)->first();
                if ($BranchWallet) {echo "$transactionsData->id uploading <br>";
                    $BranchWallet->balance += $transactionsData->amount;
                    $BranchWallet->save();}
            }
        }
// try{
//   if($id == 1){
//     $transactions =transactions::where('transaction_type_id',36)->get();
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance += $transactionsData->amount;
//                 $BranchWallet->save();
//             }
//         }
//     }
//   }
//   if($id ==2){

//     $transactions =transactions::where('transaction_type_id',37)->get();
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance += $transactionsData->amount;
//             $BranchWallet->save();
//         }
//         }
//     }
//   }
//   if($id ==3){//fail

//     $transactions =transactions::where('transaction_type_id',56)->where('id','>',311561)->get();//loan repayment
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){
//                 echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance -= $transactionsData->amount;
//             $BranchWallet->save();
//         }
//         }
//     }
//   }
//   if($id ==4){

//     $transactions =transactions::where('transaction_type_id',62)->get();//loan repayment
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance -= $transactionsData->amount;
//             $BranchWallet->save();}
//         }
//     }
//   }
//   if($id ==5){//fail

//     $transactions =transactions::where('transaction_type_id',58)->get();//maintenance
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){
//                 echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance -= $transactionsData->amount;
//             $BranchWallet->save();
//         }
//         }
//     }
//   }
//   if($id ==6){

//     $transactions =transactions::where('transaction_type_id',59)->get();//saving interest
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";
//                 $BranchWallet->balance += $transactionsData->amount;
//             $BranchWallet->save();}
//         }
//     }
//   }
//   if($id ==7){

//     $transactions =transactions::where('transaction_type_id',34)->get();//investment debit
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";$BranchWallet->balance -= ($transactionsData->amount*2);
//             $BranchWallet->save();}
//         }
//     }
//   }
//   if($id ==8){

//     $transactions =transactions::where('transaction_type_id',21)->get();//investment tarminator
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";$BranchWallet->balance += $transactionsData->amount;
//             $BranchWallet->save();}
//         }
//     }
//   }
//   if($id ==9){

//     $transactions =transactions::where('transaction_type_id',27)->get();//investment tarminator
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";$BranchWallet->balance -= $transactionsData->amount;
//             $BranchWallet->save();}
//         }
//     }
//   }
//   if($id == 10){
//     $transactions =transactions::where('transaction_type_id',30)->get();
//     if($transactions){
//         foreach($transactions as $transactionsData){
//             $BranchWallet =BranchWallet::where('branch_id',$transactionsData->branch_id)->first();
//             if($BranchWallet){ echo "$transactionsData->id uploading <br>";$BranchWallet->balance -= $transactionsData->amount;
//             $BranchWallet->save();}
//         }
//     }
//   }
// }catch(Exception $e){
//     return response()->json(['error'=>$e]);
// }
    }
}
