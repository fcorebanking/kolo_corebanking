<?php

namespace App\Http\Controllers;

use App\Models\banks;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Investmentbooking;
use App\Models\savings_product;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\CompanyWalletTXN;
use Exception;
use App\Models\currencies;
use App\Models\BranchValut;
use App\Models\CompanyValut;
use App\Models\BranchWallet;


use App\Models\transaction_types;
use App\Models\StaffWallet;
use DB;
use Illuminate\Support\Facades\Crypt;

class UserReportController extends Controller
{

    function __construct()
    {
        // $this->middleware("custom_permission:view-callover|view-end-of-day|root");
    }
    public function main($company_name)
    {
        if (!cando('view-customer-statement|root')) return abort(403);

        $data = [
            'category_name' => 'datatable',
            'page_name' => 'customer_statement',
            'page_menu' => 'customer_statement',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        // $pageName = 'html5';
        // 'permission' => transactions::,

        return view('company.statement.main')->with($data);
    }

    public function statement($company_name, $id)
    {
        if (!cando('view-customer-statement|root')) return abort(403);
        if (!restrict_to_just_my_company(['user_uuid' => $id])) return json_encode(array('status' => 0, 'message' => "Access Denied!"));
        $user = User::where(['user_type_id' => '1', 'uuid' => $id, 'company_id' => auth()->user()->company_id])->first();

        $selectedBanks = banks::where('in_use', 1)->get();

        $data = [
            'category_name' => 'datatable',
            'page_name' => 'account_statement',
            'page_menu' => 'account_statement',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'banks' => $selectedBanks,
            'alt_menu' => 0,
            'uuid' => $id,
            'user' => $user,
            'currencies'=>currencies::all()
        ];
        // 'status_id'=>1,
        if ($user->status_id == 1) {
            return view('company.statement.statement')->with($data);
        }
        $data['tag'] = '#ffbf00';
        $data['status'] = 'Unavailable';
        $data['message'] = 'This user is no longer available; <br> Contact Admin';
        return view('company.report.simple_notificatio_page')->with($data);
    }
    public function reversal_statement($company_name)
    {
        if (!cando('reverse-customer-statement|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'basic',
            'page_menu' => 'reverse_statement',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch' => auth()->user()->company->branch,
        ];

        return view('company.statement.reverse_statement')->with($data);
    }
    public function callover_report($company_name)
    {
        if (!cando('view-callover|root')) return  abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'callover_report',
            'page_menu' => 'callover_report',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'branch' =>auth()->user()->company->branch,
            'alt_menu' => 0,
        ];

        return view('company.report.callover')->with($data);
    }

    public function txn_type_Datalist(Request $request)
    {
        $search = $request->searchTerm;
        if ($search != null) {
            $type = transaction_types::where('transaction_type', 'like', '%' . $search . '%')
                ->paginate(10);
        } else {
            $type  = transaction_types::paginate(10);
        }
        $typeinfo = [];
        $typeinfo[] = ['id' => 0, 'text' => 'All Transactions'];
        if ($type) {
            foreach ($type as $data) {
                $typeinfo[] = ['id' => $data->id, 'text' => $data->transaction_type];
            }
            return (json_encode($typeinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function all_balance_info($company_name, $id)
    {
        //transaction_type_id credt=1,debit =7  investment= 21 credit_debit_type_id 1 2
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'users.uuid' => $id, 'users.status_id' => 1]);
        $query2 =   User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'users.uuid' => $id, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
            $query2 = $query2->where('users.account_officer_id', auth()->user()->id);
        }
        $credit = $query->where('transactions.credit_debit_type_id', 1)->sum('transactions.amount');

        $debit = $query2->where('transactions.credit_debit_type_id', 2)->sum('transactions.amount');

        $investment = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'users.uuid' => $id, 'investmentbooking.status' => 1, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $investment = $investment->where('users.account_officer_id', auth()->user()->id);
        }
        $investment = $investment->sum('investmentbooking.expected_balance');

        $aval = user_wallet::join('wb_account_details', 'wb_account_details.id', '=', 'user_wallets.wb_account_detail_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'users.uuid' => $id, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $aval = $aval->where('users.account_officer_id', auth()->user()->id);
        }
        $aval = $aval->sum('user_wallets.balance');
        return response()->json(['credit' => $credit, 'debit' => $debit, 'investment' => $investment, 'balance' => $aval]);
    }
    public function account_balance_info($company_name, $id)
    {
        //transaction_type_id credt=1,debit =7  investment= 21 credit_debit_type_id 1 2
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'wb_account_details.id' => $id, 'users.status_id' => 1]);
        $query2 =   User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'wb_account_details.id' => $id, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
            $query2 = $query2->where('users.account_officer_id', auth()->user()->id);
        }
        $credit = $query->where('transactions.credit_debit_type_id', 1)->sum('transactions.amount');

        $debit = $query2->where('transactions.credit_debit_type_id', 2)->sum('transactions.amount');

        $investment = Investmentbooking::join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'wb_account_details.id' => $id, 'investmentbooking.status' => 1, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $investment = $investment->where('users.account_officer_id', auth()->user()->id);
        }
        $investment = $investment->sum('investmentbooking.expected_balance');
        $aval = user_wallet::join('wb_account_details', 'wb_account_details.id', '=', 'user_wallets.wb_account_detail_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'wb_account_details.id' => $id, 'users.status_id' => 1]);
        $is_root = cando('root');
        if (!$is_root) {
            $aval = $aval->where('users.account_officer_id', auth()->user()->id);
        }
        $aval = $aval->select('wb_account_details.virtual_accnum', \DB::raw('SUM(user_wallets.balance) as total_balance'))
    ->first();
    $virtualAccount = $aval->virtual_accnum;
    $totalBalance = $aval->total_balance;

        $aval = $aval->sum('user_wallets.balance');


        return response()->json(['credit' => $credit, 'debit' => $debit, 'investment' => $investment, 'balance' => $totalBalance,'virtual_account'=>$virtualAccount]);
    }
    public function ajax_account_statement($id, Request $request)
    {

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'transactions.id',
            1 => 'transactions.transaction_reference',
            2 => 'savings_products.savings_product',
            // 3 => 'wb_account_details.virtual_accnum',
            3 => 'transactions.balance_before',
            4 => 'transactions.amount',
            5 => 'transactions.balance_after',
            6 => 'transactions.withdrawal_charge',
            7 => 'transactions.credit_debit_type_id',
            // 9 => 'transaction_types.transaction_type',
            // 10 => 'transaction_remarks.transaction_remark',
            8 => 'transactions.description',
            // 12 => 'transactions.id',
            9 => 'transactions.id',
        );
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->select('transactions.*', 'credit_debit_types.type', 'transaction_types.transaction_type', 'transaction_remarks.transaction_remark', 'wb_account_details.virtual_accnum', DB::raw('savings_products.savings_product as accountsavingsp'));
        $query =  $query->where(['user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'users.uuid' => $id]);
        $query = $query->where('users.status_id', '=', 1);
        $query = $query->where('transactions.transaction_type_id', '!=', 73);
        $query = $query->where('transactions.transaction_type_id', '!=', 40);
        $query = $query->where('transactions.transaction_type_id', '!=', 39);
        $query = $query->where('transactions.transaction_type_id', '!=', 38);
        $query = $query->where('transactions.transaction_type_id', '!=', 35);
        $query = $query->where('transactions.transaction_type_id', '!=', 36);
        if ($request->status == 'off') {
            $query = $query->where('users.status_id', '=', 2);
        }
        //$is_root = cando('root');
        // if(!$is_root){
        //     $query->where(function($query) use ($filter){
        //         $query =$query->where('transactions.created_by',auth()->user()->id);
        //         $query =$query->orwhere('users.account_officer_id',auth()->user()->id);

        //     });
        // }
        if ($request->from != null && $request->to != null) {

            $query =  $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $request->to))
                ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $request->from));
        }
        if (!empty($filter)) {
            $query =  $query->where(function ($query) use ($filter) {
                $query->where('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('transactions.transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir']);
        // ->take($length)
        // ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );

        $info = $query->get();
        $sn = 0;
        $balanceAfter =0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $created = User::where('id', $infoData->created_by)->first();
            $modified = $infoData->modified_by ? User::where('id', $infoData->modified_by)->first() : null;
            $balanceAfter = number_format($infoData->balance_after, 2) . auth()->user()->company->currency->symbol;
            $json['data'][] = [
                $sn,
                "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> <i class='fa fa-eye'></i> </a>",
                $infoData->accountsavingsp,

                // $infoData->virtual_accnum,

                number_format($infoData->balance_before, 2) . auth()->user()->company->currency->symbol,
                number_format($infoData->amount, 2) . auth()->user()->company->currency->symbol,


                number_format($infoData->balance_after, 2) . auth()->user()->company->currency->symbol,

                number_format($infoData->withdrawal_charge, 2),
                $infoData->type,
                // $infoData->transaction_type,
                // $infoData->transaction_remark,
                $infoData->description,

                // $created->first_name.' '.$created->last_name,
                // $modified?$modified->first_name.' '.$modified->last_name:'None',

                $infoData->created_date,


            ];
        }
        $json['data'][] = [
            '#',
            "Total",
            '-',
            '-',
            '-',
            $balanceAfter,
            '-',
            '-',
            '-',
            '-',
        ];

        return $json;
    }
    public function ajax_general_statement(Request $request)
    {
        if (!cando('reverse-customer-statement|reverse-transactions|root')) return abort(403);
        $auth = auth()->user();
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'transactions.id',
            1 => 'users.first_name',
            1 => 'transactions.transaction_reference',
            2 => 'savings_products.savings_product',
            3 => 'wb_account_details.virtual_accnum',
            4 => 'transactions.balance_before',
            5 => 'transactions.amount',
            6 => 'transactions.balance_after',
            7 => 'transactions.withdrawal_charge',
            8 => 'transactions.credit_debit_type_id',
            9 => 'transactions.transaction_type_id',
            10 => 'transaction_types.transaction_type',
            11 => 'transaction_remarks.transaction_remark',
            12 => 'transactions.description',
            13 => 'transactions.id',
            14 => 'transactions.id',
            15 => 'transactions.id',
            16 => 'transactions.id',

        );
        $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->select('transactions.*', 'users.first_name', 'users.last_name', 'credit_debit_types.type', 'transaction_types.transaction_type', 'transaction_remarks.transaction_remark', 'wb_account_details.virtual_accnum', DB::raw('savings_products.savings_product as accountsavingsp'));


        $query =  $query->where(['users.user_type_id' => '1', 'users.company_id' => $auth->company_id, 'transactions.transaction_status_id' => 1]);
        $query =  $query->where(function ($query) use ($filter) {
            $query->orwhere(['transactions.transaction_type_id' => '1']);
            $query->orwhere(['transactions.transaction_type_id' => '7']);
        });
        // $query =  $query->where('transactions.parent_transaction_id', null); //
        $query = $query->where('users.status_id', '=', 1);
        $is_root = cando('root|reverse-transactions');
        if (!$is_root) {
            $query = $query->where(function ($query) use ($filter, $auth) {
                $query->orWhere(['users.account_officer_id' => $auth->id]);
                $query->orWhere(['transactions.created_by' => $auth->id]);
            });

        }
        if ($request->branch != null && $request->branch != 'null') {
            // echo "branch";
            $request->branch = Crypt::decryptString($request->branch);
            $query = $query->where(['transactions.branch_id' => $request->branch]);
        }
        if ($request->staff != null && $request->staff != 'null') {
            // echo "staff";
            $user = User::where(['uuid' => $request->staff, 'status_id' => 1,])->first();
            if ($user) $query = $query->where(['transactions.created_by' => $user->id]);

            // echo $user->id;
        }
        if ($request->from != null && $request->to != null) {
            $query =  $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $request->to))
                ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $request->from));
        }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('transactions.transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();
        // $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy("id", 'desc');
        // ->take($length)
        // ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );

        $info = $query->get();

        $sn = 0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $created = User::where('id', $infoData->created_by)->first();
            $modified = $infoData->modified_by ? User::where('id', $infoData->modified_by)->first() : null;
            $btn_id = $infoData->transaction_reference;
            if ($infoData->credit_debit_type_id == 2) {
                $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26) ? "<button class='btn btn-warning'  onclick=reverse('$btn_id')> Reverse Debit</button>" : '<span class="btn btn-danger">Reversed</span>';
            } else {
                $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26) ? "<button class='btn btn-secondary'  onclick=reverse('$btn_id')> Reverse Credit</button>" : '<span class="btn btn-danger">Reversed</span>';
            }
            $json['data'][] = [
                $sn,
                $infoData->first_name . ' ' . $infoData->last_name,
                "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> $infoData->transaction_reference</a>",
                $infoData->accountsavingsp,

                $infoData->virtual_accnum,
                number_format($infoData->balance_before, 2) . $auth->company->currency->symbol,
                number_format($infoData->amount, 2) . $auth->company->currency->symbol,


                number_format($infoData->balance_after, 2) . $auth->company->currency->symbol,

                number_format($infoData->withdrawal_charge, 2),
                $infoData->type,
                $infoData->transaction_type,
                $infoData->transaction_remark,
                $infoData->description,

                $created->first_name . ' ' . $created->last_name,
                $modified ? $modified->first_name . ' ' . $modified->last_name : 'None',

                $infoData->created_date,
                $btnreverse


            ];
        }

        return $json;
    }
    public function reverseTXN($id)
    {
        try{
        if (!cando('reverse-customer-statement|root')) return abort(403);
        $transaction = transactions::where(['transaction_reference' => $id, 'transaction_status_id' => 1])
            ->where(function ($query) {
                $query->orwhere('transactions.transaction_type_id', '1');
                $query->orwhere('transactions.transaction_type_id', '7');
            })
            ->first();
        if ($transaction) {
            // "parent_transaction_id"
            $parent_transaction_id = $transaction->id;
            $backdate = date("Y-m-d H:i:s");
            $transactionId = generate_txnID();
            $customerwallet = user_wallet::where('wb_account_detail_id', $transaction->account_id)->first();
            if (!$customerwallet) return json_encode(['status' => 0, 'message' => 'Invalid User!']);
            if (!restrict_to_just_my_company(['user_uuid' => $customerwallet->wb_account_details->user->uuid])) return json_encode(array('status' => 0, 'message' => "Access Denied!"));
            $balanceBefore = $customerwallet->balance;
            if ($transaction->credit_debit_type_id == 1) {
                //credit
                $ledger_cr = null;
                $ledger_dr = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(25, auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $customerwallet->balance = $customerwallet->balance - $transaction->amount;
                $data = [
                    "balance_before" => $balanceBefore,
                    "amount" =>  $transaction->amount,
                    "description" => "Credit Reversal",
                    "account_id" => $transaction->account_id,
                    "branch_id" => auth()->user()->branch_id,
                    "transaction_reference" => $transactionId,
                    "transaction_status_id" => 0,
                    "transaction_type_id" => 25, //debit 7
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $customerwallet->balance,
                    "parent_transaction_id" => $parent_transaction_id,
                    "created_date"  => $backdate,
                    "created_by" => auth()->user()->id,
                    "ledger_cr" => $ledger_cr,
                    "ledger_dr" => $ledger_dr
                ];
            } else if ($transaction->credit_debit_type_id == 2) {
                //debit
                $ledger_cr = null;
                $ledger_dr = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(26, auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $customerwallet->balance = $customerwallet->balance + $transaction->amount;
                $data = [
                    "balance_before" => $balanceBefore,
                    "amount" =>  $transaction->amount,
                    "description" => "Debit Reversal",
                    "account_id" => $transaction->account_id,
                    "branch_id" => auth()->user()->branch_id,
                    "transaction_reference" => $transactionId,
                    "transaction_type_id" => 26, //debit 7
                    "transaction_status_id" => 0,
                    "credit_debit_type_id" => 1, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $customerwallet->balance,
                    "created_date"  => $backdate,
                    "parent_transaction_id" => $parent_transaction_id,
                    "created_by" => auth()->user()->id,
                    "ledger_cr" => $ledger_cr,
                    "ledger_dr" => $ledger_dr,
                ];
            }
            $transactionx = transactions::create($data);

            if ($customerwallet->save()) {
                $BranchWallet = BranchWallet::where('branch_id', $transaction->branch_id)->first();
                $BranchWallet->balance = ($transaction->credit_debit_type_id == 1) ? ($BranchWallet->balance - $transaction->amount) : ($BranchWallet->balance + $transaction->amount);
                $BranchWallet->save();
                $selectbranchvalut = BranchValut::where('transaction_id', $transaction->id)->first();
                if ($selectbranchvalut) {
                    //BranchValut::create(['branch_id' => $vault_branch_id, 'company_valut_id' => $check_tocreate->id, 'transaction_id' => $vault_transaction_id, 'value' => $vault_value, 'created_by' => $creatorId]);
                    $valutData = CompanyValut::where(['id' => $selectbranchvalut->company_valut_id, 'company_id' => auth()->user()->company_id])->first();
                    if ($valutData) {
                        $valut_value = $selectbranchvalut->value;
                        $valutData->value = $valutData->value - $valut_value;
                        if ($valutData->save()) {
                            BranchValut::where('transaction_id', $transaction->id)->delete();
                        }
                    }
                }

                $transaction->transaction_status_id = 0; //mark transaction as reversed
                $transaction->modified_by = auth()->user()->id;
                $transaction->save();
                $staff_commission = transactions::where(['parent_transaction_id' => $parent_transaction_id, 'transaction_status_id' => 1, 'transaction_type_id' => 20])->first();
                $staffwallet = User::find($staff_commission->created_by);
                if(isset($staffwallet->wallet)){
                    if ($staff_commission) {
                        $datax = [];
                        if ($staff_commission->transaction_type_id == 20) { //if it is teller commission
                            $teller_wallet = $staffwallet->wallet->commission;
                            $balance_after = $teller_wallet - $staff_commission->amount;
                            $ledger_cr = null;
                            $ledger_dr = null;
                            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(25, auth()->user()->company_id);
                            if ($ledger_Settings) {
                                $ledger_cr = $ledger_Settings->ledger_cr;
                                $ledger_dr = $ledger_Settings->ledger_dr;
                            }
                            $datax = [
                                "balance_before" => $teller_wallet,
                                "amount" =>  $staff_commission->amount,
                                "description" => "Commission Credit Reversal",
                                "branch_id" => auth()->user()->branch_id,
                                "transaction_reference" => generate_txnID(),
                                "transaction_type_id" => 25, //
                                "transaction_status_id" => 0,
                                "credit_debit_type_id" => 2, //debit 2
                                "transaction_remarks_id" => 2, //success 2
                                "parent_transaction_id" => $transactionx->id, //success 2
                                "balance_after" => $balance_after,
                                "created_date"  => $backdate,
                                "created_by" => auth()->user()->id,
                                "ledger_cr" => $ledger_cr,
                                "ledger_dr" => $ledger_dr,
                            ];
                            StaffWallet::where(['id' => $staffwallet->wallet->id])->update(['commission' => $balance_after]);
                            if (count($datax) > 0) {
                                transactions::create($datax);
                                $staff_commission->transaction_status_id = 0; //mark commission transaction as reversed
                                $staff_commission->modified_by =  auth()->user()->id;
                                $staff_commission->save();
                            }
                        }
                    }
                }
                $staff_commission = transactions::where(['parent_transaction_id' => $parent_transaction_id, 'transaction_status_id' => 1, 'transaction_type_id' => 4])->first();
                if ($staff_commission) {
                    //is a wallet to wallet transfer credit
                    $ben_wallet = user_wallet::where('wb_account_detail_id', $staff_commission->account_id)->first();
                    $ben_balanceBefore = $ben_wallet->balance;
                    $ben_balanceafter = $ben_balanceBefore - $staff_commission->amount;
                    $ben_wallet->balance = $ben_balanceafter;
                    $ledger_cr = null;
                    $ledger_dr = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(28, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $datax = [
                        "balance_before" => $ben_balanceBefore,
                        "amount" =>  $staff_commission->amount,
                        "description" => "Credit Transfer Reversal",
                        "account_id" => $staff_commission->account_id,
                        "branch_id" => auth()->user()->branch_id,
                        "transaction_reference" => generate_txnID(),
                        "transaction_type_id" => 28, //debit 7
                        "credit_debit_type_id" => 2, //debit 2
                        "transaction_status_id" => 0, //
                        "transaction_remarks_id" => 2, //success 2
                        "parent_transaction_id" => $transactionx->id,
                        "balance_after" => $customerwallet->balance,
                        "created_date"  => $backdate,
                        "created_by" => auth()->user()->id,
                        "ledger_cr" => $ledger_cr,
                        "ledger_dr" => $ledger_dr,
                    ];
                    transactions::create($datax);
                    $ben_wallet->save();
                }
                $staff_commission = transactions::where(['parent_transaction_id' => $parent_transaction_id, 'transaction_status_id' => 1, 'transaction_type_id' => 44])->first();
                if ($staff_commission) {
                    if ($staff_commission->transaction_type_id == 44) {
                        $staffwallet = User::find($staff_commission->created_by);
                        $teller_wallet = $staffwallet->wallet->balance;
                        $balance_after = $teller_wallet - $staff_commission->amount;
                        $ledger_cr = null;
                        $ledger_dr = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(48, auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $datax = [
                            "balance_before" => $teller_wallet,
                            "amount" =>  $staff_commission->amount,
                            "description" => "Teller Credit Reversal",
                            "branch_id" => auth()->user()->branch_id,
                            "transaction_reference" => generate_txnID(),
                            "transaction_type_id" => 48, //
                            "transaction_status_id" => 0,
                            "credit_debit_type_id" => 2, //debit 2
                            "transaction_remarks_id" => 2, //success 2
                            "parent_transaction_id" => $transactionx->id, //success 2
                            "balance_after" => $balance_after,
                            "created_date"  => $backdate,
                            "created_by" => auth()->user()->id,
                            "ledger_cr" => $ledger_cr,
                            "ledger_dr" => $ledger_dr,
                        ];
                        StaffWallet::where(['id' => $staffwallet->wallet->id])->update(['balance' => $balance_after]);
                        if (count($datax) > 0) {
                            transactions::create($datax);
                            $staff_commission->transaction_status_id = 0; //mark commission transaction as reversed
                            $staff_commission->modified_by =  auth()->user()->id;
                            $staff_commission->save();
                        }
                    } else if ($staff_commission->transaction_type_id == 45) {
                        $staffwallet = User::find($staff_commission->created_by);
                        $teller_wallet = $staffwallet->wallet->balance;
                        $balance_after = $teller_wallet + $staff_commission->amount;
                        $ledger_cr = null;
                        $ledger_dr = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(48, auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $datax = [
                            "balance_before" => $teller_wallet,
                            "amount" =>  $staff_commission->amount,
                            "description" => "Teller Credit Reversal",
                            "branch_id" => auth()->user()->branch_id,
                            "transaction_reference" => generate_txnID(),
                            "transaction_type_id" => 48, //
                            "transaction_status_id" => 0,

                            "credit_debit_type_id" => 1, //debit 2
                            "transaction_remarks_id" => 2, //success 2
                            "parent_transaction_id" => $transactionx->id, //success 2
                            "balance_after" => $balance_after,
                            "created_date"  => $backdate,
                            "created_by" => auth()->user()->id,
                            "ledger_cr" => $ledger_cr,
                            "ledger_dr" => $ledger_dr,
                        ];
                        StaffWallet::where(['id' => $staffwallet->wallet->id])->update(['balance' => $balance_after]);
                        if (count($datax) > 0) {
                            transactions::create($datax);
                            $staff_commission->transaction_status_id = 0; //mark commission transaction as reversed
                            $staff_commission->modified_by =  auth()->user()->id;
                            $staff_commission->save();
                        }
                    }
                }
                $staff_commission = transactions::where(['parent_transaction_id' => $parent_transaction_id, 'transaction_status_id' => 1, 'transaction_type_id' => 45])->first();
                if ($staff_commission) {
                    if ($staff_commission->transaction_type_id == 45) {
                        $staffwallet = User::find($staff_commission->created_by);
                        $teller_wallet = $staffwallet->wallet->balance;
                        $balance_after = $teller_wallet + $staff_commission->amount;
                        $ledger_cr = null;
                        $ledger_dr = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(48, auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $datax = [
                            "balance_before" => $teller_wallet,
                            "amount" =>  $staff_commission->amount,
                            "description" => "Teller Credit Reversal",
                            "branch_id" => auth()->user()->branch_id,
                            "transaction_reference" => generate_txnID(),
                            "transaction_type_id" => 48, //
                            "transaction_status_id" => 0,
                            "credit_debit_type_id" => 1, //debit 2
                            "transaction_remarks_id" => 2, //success 2
                            "parent_transaction_id" => $transactionx->id, //success 2
                            "balance_after" => $balance_after,
                            "created_date"  => $backdate,
                            "created_by" => auth()->user()->id,
                            "ledger_cr" => $ledger_cr,
                            "ledger_dr" => $ledger_dr,
                        ];
                        StaffWallet::where(['id' => $staffwallet->wallet->id])->update(['balance' => $balance_after]);
                        if (count($datax) > 0) {
                            transactions::create($datax);
                            $staff_commission->transaction_status_id = 0; //mark commission transaction as reversed
                            $staff_commission->modified_by =  auth()->user()->id;
                            $staff_commission->save();
                        }
                    }
                }
                return response(json_encode(["status" => 1, "message" => "Transaction Reversed Successful!"]));
            } else {
                return response(json_encode(["status" => 0, "message" => "Transaction Reverser Failed!"]));
            }
        } else {
            return response(json_encode(["status" => 0, "message" => "Transaction Already Reversed!"]));
        }
    }catch(Exception $error){
        return response(json_encode(["status" => 0, "message" => "Failed!",'error'=>$error->getMessage().$error->getLine()]));

    }

    }
    public function dom()
    {
        if (!cando('manage-dom-account|root')) return  abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'dom_account',
            'page_menu' => 'dom_account',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.savings.dom')->with($data);
    }
    public function get_company_savings_products()
    {
        $product =  savings_product::where(['company_id' => auth()->user()->company_id])->get();
        return response()->json($product);
    }
    public function get_my_savings_products($id)
    {

        $product = wb_account_details::leftJoin('banks', 'banks.id', '=', 'wb_account_details.bankid')->whereHas('user', function ($q) use (&$id) {
            $q->where(['uuid' => $id]);
        })->select('wb_account_details.*', 'wb_account_details.id as accountId', 'banks.*', 'banks.id as bankId')->get();
        $ray = [];
        if ($product) {
            foreach ($product as $accountdata) {

                $ray[] = ['savings_product' => $accountdata->accountType->savings_product,'bank_name' => $accountdata->bank_name, 'id' => $accountdata->accountId, 'accno' => $accountdata->virtual_accnum];
            }
        }
        return response()->json($ray);
    }
    public function ajax_account_single_statement($id, Request $request)
    {

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'transactions.id',
            1 => 'transactions.transaction_reference',
            2 => 'savings_products.savings_product',
            // 3 => 'wb_account_details.virtual_accnum',
            3 => 'transactions.balance_before',
            4 => 'transactions.amount',
            5 => 'transactions.balance_after',
            6 => 'transactions.withdrawal_charge',
            7 => 'credit_debit_types.type',
            8 => 'transaction_types.transaction_type',
            // 10 => 'transaction_remarks.transaction_remark',
            9 => 'transactions.description',
            // 12 => 'transactions.created_at',
            // 13 => 'transactions.created_at',
            10 => 'transactions.created_at',
        );
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
            ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->select('transactions.*', 'credit_debit_types.type', 'transaction_types.transaction_type', 'transaction_remarks.transaction_remark', 'wb_account_details.virtual_accnum', DB::raw('savings_products.savings_product as accountsavingsp'));
        $query =  $query->where(['users.user_type_id' => '1', 'users.status_id' => 1, 'users.company_id' => auth()->user()->company_id, 'transactions.account_id' => $id]);
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        if ($request->from != null && $request->to != null) {

            $query =  $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $request->to))
                ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $request->from));
        }

        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('transactions.transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }
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
        $sn = 0;
        $balanceAfter =0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $created = User::where('id', $infoData->created_by)->first();
            $modified = $infoData->modified_by ? User::where('id', $infoData->modified_by)->first() : null;
            $balanceAfter = number_format($infoData->balance_after, 2) . auth()->user()->company->currency->symbol;
            $json['data'][] = [
                $sn,
                "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> <i class='fa fa-eye'></i></a>",
                $infoData->accountsavingsp,
                number_format($infoData->balance_before, 2) . auth()->user()->company->currency->symbol,
                number_format($infoData->amount, 2) . auth()->user()->company->currency->symbol,
                number_format($infoData->balance_after, 2) . auth()->user()->company->currency->symbol,
                $infoData->withdrawal_charge,
                $infoData->type,
                $infoData->description,
                $infoData->created_date,
            ];
        }
        $json['data'][] = [
            "#",
            "",
            "",
            "",
            "",
            $balanceAfter,
            "",
            "",
            "",
            "",

        ];
        return $json;
    }
    public function ajax_generate_callover(Request $request)
    {
        if (!cando('view-callover|view-all-callover|esusu-operation|root')) return [];
        if ($request->from != null && $request->to != null) {
            $search = $request->query('search', array('value' => '', 'regex' => false));
            $draw = $request->query('draw', 0);
            $start = $request->query('start', 0);
            $length = 1000000; //$request->query('length',1000000);
            $order = $request->query('order', array(1, 'desc'));
            $filter = $search['value'];
            if ($request->esusu != null) {

                $sortColumns = array(
                    0 => 'transactions.id',
                    1 => 'transactions.created_date',
                    // 2 => 'users.first_name',
                    // 3 => 'wb_account_details.virtual_accnum',
                    2 => 'transactions.description',
                    3 => 'transactions.transaction_type_id',
                    4 => 'transactions.transaction_reference',
                    5 => 'savings_products.savings_product',
                    6 => 'users.first_name',
                    7 => 'transactions.amount',
                    8 => 'transactions.amount',
                    // 12 => 'transactions.balance_after',
                    // 13 => 'transactions.withdrawal_charge',
                    9 => 'transactions.created_at',
                );
            } else {
                $sortColumns = array(
                    0 => 'transactions.id',
                    1 => 'transactions.created_date',
                    2 => 'users.first_name',
                    3 => 'wb_account_details.virtual_accnum',
                    4 => 'transactions.description',
                    5 => 'transactions.transaction_type_id',
                    6 => 'transactions.transaction_reference',
                    7 => 'savings_products.savings_product',
                    8 => 'users.first_name',
                    9 => 'transactions.amount',
                    10 => 'transactions.amount',
                    11 => 'transactions.balance_before',
                    12 => 'transactions.balance_after',
                    13 => 'transactions.withdrawal_charge',
                    14 => 'transactions.created_at',
                    15 => 'transactions.initiator',
                );
            }
            $query = transactions::join('wb_account_details', 'transactions.account_id', '=', 'wb_account_details.id')
                ->join('users', 'wb_account_details.user_id', '=', 'users.id')
                ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
                ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
                ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
                ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
                ->select('transactions.*', 'users.first_name', 'users.last_name', 'users.account_officer_id', 'credit_debit_types.type', 'transaction_types.transaction_type', 'transaction_remarks.transaction_remark', 'wb_account_details.virtual_accnum', DB::raw('savings_products.savings_product as accountsavingsp'));
            $query =  $query->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'transactions.transaction_status_id' => 1]);
            $query = $query->where('users.status_id', '=', 1);
            // $query =  $query->where('transactions.parent_transaction_id', null); //
            $is_root = cando('root|view-all-callover');

            if ($request->esusu != null) {
                $decrypt_id = Crypt::decryptString($request->esusu);
                $query =  $query->where('account_id', $decrypt_id);
                $query->where(function ($query) {
                    $query->where('transactions.transaction_type_id', '>=', 35)->where('transactions.transaction_type_id', '<=', 40);
                    $query->orwhere('transactions.transaction_type_id', 35);
                });
            }
            if ($request->branch != 0) {
                $request->branch = Crypt::decryptString($request->branch);
                $query =  $query->where('transactions.branch_id', $request->branch);
            }
            if ($request->type != 'null' && $request->type != 0) {
                $query =  $query->where('transactions.transaction_type_id', $request->type);
            }
            if ($is_root) {
                if ($request->officer != 'null' && $request->officer != 0 && $request->officer != null) {
                    $officerID = User::where(['uuid' => $request->officer])->first();
                    if ($request->initiator == 'createdby') {
                        $query =  $query->where('transactions.created_by', $officerID->id);
                    } else {
                        $query =  $query->where('users.account_officer_id', $officerID->id);
                    }
                }
            }
            if ($request->from != null && $request->to != null) {
                $query =  $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $request->to))
                    ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $request->from));
            }
            if (!$is_root) {
                $query = $query->where(function ($query) {
                    //$query->where(['users.account_officer_id'=>auth()->user()->id]);
                    $query->where(['transactions.created_by' => auth()->user()->id]);
                });
            }
            if (!empty($filter)) {
                $query->where(function ($query) use ($filter) {
                    $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                    $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                    $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                    $query->orWhere('transactions.transaction_reference', 'like', '%' . $filter . '%');
                    $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
                    $query->orWhere('transactions.initiator', 'like', '%' . $filter . '%');

                });
            }

            $recordsTotal = $query->count();
            // $sortColumnName = $sortColumns[$order[0]['column']];
            $query = $query->orderBy('id', 'desc');
            //  ->take($length)
            // ->skip($start);
            $json = array(
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
                'data' => [],
            );
            $info = $query->get();
            $sn = 0;

            $deposit = 0;
            $withdraw = 0;
            $difference = 0;
            foreach ($info as $infoData) {
                $sn++;
                $btnlink = $infoData->id;
                $created = User::where('id', $infoData->created_by)->first();
                $officer = $infoData->account_officer_id ? User::where('id', $infoData->account_officer_id)->first() : null;

                if ($infoData->credit_debit_type_id == 2) {
                    $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26) ? "<button class='btn btn-warning'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Debit</button>" : '<span class="btn btn-danger">Reversed</span>';
                } else {
                    $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26) ? "<button class='btn btn-secondary'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Credit</button>" : '<span class="btn btn-danger">Reversed</span>';
                }
                $badge = ($infoData->credit_debit_type_id == 1) ? "success" : "danger";
                $deposit += ($infoData->credit_debit_type_id == 1) ? $infoData->amount : 0;
                $withdraw += ($infoData->credit_debit_type_id == 2) ? $infoData->amount : 0;
                if ($request->esusu != null) {
                    $json['data'][] = [
                        $sn,
                        $infoData->created_date,
                        $infoData->description,
                        "<span class='badge badge-" . $badge . "'>" . $infoData->type . "</span>",
                        "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> $infoData->transaction_reference</a>",
                        $infoData->accountsavingsp,
                        @$officer->first_name . ' ' . @$officer->last_name,
                        ($infoData->credit_debit_type_id == 2) ? number_format($infoData->amount, 2) . auth()->user()->company->currency->symbol : '-',
                        ($infoData->credit_debit_type_id == 1) ? number_format($infoData->amount, 2) . auth()->user()->company->currency->symbol : '-',
                        $created->first_name . ' ' . $created->last_name,

                    ];
                } else {
                    $json['data'][] = [
                        $sn,
                        $infoData->created_date,
                        $infoData->first_name . ' ' . $infoData->last_name,
                        $infoData->virtual_accnum,
                        $infoData->description,
                        "<span class='badge badge-" . $badge . "'>" . $infoData->type . "</span>",
                        "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> $infoData->transaction_reference</a>",
                        $infoData->accountsavingsp,
                        @$officer->first_name . ' ' . @$officer->last_name,
                        ($infoData->credit_debit_type_id == 2) ? number_format($infoData->amount, 2)  : '0',
                        ($infoData->credit_debit_type_id == 1) ? number_format($infoData->amount, 2) : '0',
                        number_format($infoData->balance_before, 2),
                        number_format($infoData->balance_after, 2),
                        number_format($infoData->withdrawal_charge, 2) . auth()->user()->company->currency->symbol,
                        $created->first_name . ' ' . $created->last_name,
                        $infoData->initiator,
                    ];
                }
            }

            $difference = $deposit - $withdraw;



            $json['data'][] = [
                '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw </h5></span>',
                '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw =' . $withdraw . auth()->user()->company->currency->symbol . '</h5></span>',

                '',
                '',
                '',
                '',
                '',
                '',
                '',

                '', '',
                '',
                '',
                '',
                '',
                '',

            ];
            $json['data'][] = [
                '<span class="badge badge-warning "><h5 class="text-light">Total Deposit </h5></span>',
                '<span class="badge badge-info"><h5 class="text-light">Total Deposit=' . $deposit . auth()->user()->company->currency->symbol . '</h5></span></h4>',

                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',

            ];
            $json['data'][] = [
                '<span class="badge badge-success "><h5 class="text-light">Total Deposit </h5></span>',
                '<span class="badge badge-success"><h5 class="text-light">Difference =' . $difference . auth()->user()->company->currency->symbol . '</h5></span></h4>',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',

            ];
        } else {
            $json['data'] = [];
        }
        return $json;
    }

    public function customerOverdraftList()
    {
        if (!cando('view-customer-overdraft|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'customer_overdraft_list',
            'page_menu' => 'customer_overdraft_list',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,

        ];
        // $pageName = 'html5';

        return view('company.report.customer_overdrafts')->with($data);
    }

    public function end_of_day($company_name)
    {
        if (!cando('view-end-of-day|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html',
            'page_menu' => 'end_of_day_report',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.report.end-of-day')->with($data);
    }
    public function ajax_generate_end_of_day(Request $request)
    {
        if (!cando('view-end-of-day|root')) return abort(403);
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 2000);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'transaction_types.transaction_type',
            1 => 'transactions.amount',
        );
        $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
            ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
            ->groupBy('transactions.transaction_type_id')
            ->select('transactions.transaction_type_id', 'transactions.credit_debit_type_id', 'credit_debit_types.type', 'transaction_types.transaction_type', DB::raw('sum(transactions.amount) as total'))
            ->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'transactions.created_by' => auth()->user()->id, 'transactions.transaction_status_id' => 1, 'users.status_id' => 1]);
        $query = $query->where('users.status_id', '=', 1);
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        if ($request->from != null && $request->to != null) {
            $query =  $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $request->to))
                ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $request->from));
        } else {
            $query =  $query->whereDate('transactions.created_date', '=', date('Y-m-d'));
        }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->Where('transactions.transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('transaction_types.transaction_type', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = 1;
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        //         ->take($length)
        //         ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );

        $info = $query->get();
        $sum = 0;
        $credit = 0;
        $debit = 0;
        $badge =  '';
        if (count($info) > 0) {
            foreach ($info as $infoData) {
                $sum += $infoData->total;

                if ($infoData->credit_debit_type_id == 1) {
                    $credit += $infoData->total;
                    $badge = 'badge-success';
                } else if ($infoData->credit_debit_type_id == 2) {
                    $debit += $infoData->total;
                    $badge = 'badge-warning';
                }
                $json['data'][] = ['<span class="badge ' . $badge . '">' . $infoData->transaction_type . '</span>', number_format($infoData->total, 2)];
            }
            $json['data'][] = ['<span class="badge ' . $badge . '">' . $infoData->transaction_type . '</span>', number_format($infoData->total, 2)];
        }
        $json['data'][] = ['<span class="badge "><h5>Total Deposit</h5></span>', '<h5>' . number_format($credit, 2) . '</h5>'];
        $json['data'][] = ['<span class="badge "><h5>Total Withdraw</h5></span>', '<h5>' . number_format($debit, 2) . '</h5>'];
        $json['data'][] = ['<h5>Sum</h5>',  '<h5>' . number_format($sum, 2) . auth()->user()->company->currency->symbol . '</h5>',];
        return $json;
    }
    // public function ajax_esusu_callover(Request $request){
    //     if (!cando('view-callover|esusu-operation|root')) return abort(403);
    //     if($request->from != null && $request->to != null){

    //     $search = $request->query('search', array('value' => '', 'regex' => false));
    //     $draw = $request->query('draw', 0);
    //     $start = $request->query('start', 0);
    //     $length = 1000000; //$request->query('length',1000000);
    //     $order = $request->query('order', array(1, 'desc'));
    //     $filter = $search['value'];
    //     $sortColumns = array(
    //         0 => 'transactions.id',
    //         1 => 'transactions.created_date',
    //         2 => 'users.first_name',
    //         3 => 'wb_account_details.virtual_accnum',
    //         4 => 'transactions.description',
    //         5 => 'transactions.transaction_type_id',
    //         6 => 'transactions.transaction_reference',
    //         7 => 'savings_products.savings_product',
    //         8 => 'users.first_name',
    //         9 => 'transactions.amount',
    //         10 => 'transactions.amount',
    //         11 => 'transactions.balance_before',
    //         12 => 'transactions.balance_after',
    //         13 => 'transactions.withdrawal_charge',
    //         14 => 'transactions.created_at',
    //     );
    //     $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
    //     ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
    //     ->join('transactions', 'transactions.account_id', '=', 'wb_account_details.id')
    //     ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
    //     ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
    //     ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
    //     ->select('transactions.*','users.first_name','users.last_name','users.account_officer_id','credit_debit_types.type','transaction_types.transaction_type','transaction_remarks.transaction_remark','wb_account_details.virtual_accnum',DB::raw('savings_products.savings_product as accountsavingsp'));
    //     $query =  $query->where(['users.user_type_id'=>'1','users.company_id'=>auth()->user()->company_id,'transactions.transaction_status_id'=>1]);
    //     $query = $query->where('users.status_id','=',1);
    //     $query =  $query->where('transactions.parent_transaction_id',null);//
    //     $is_root = cando('root');
    //     if(!$is_root){
    //         $query =$query->where(function($query){
    //             //$query->where(['users.account_officer_id'=>auth()->user()->id]);
    //             $query->where(['transactions.created_by'=>auth()->user()->id]);
    //         });
    //     }

    //     if($request->esusu != null ){
    //         $decrypt_id =Crypt::decryptString($request->esusu);
    //         $query =  $query->where('transactions.transaction_type_id','>=',35)->where('transactions.transaction_type_id','<=',40)->where('account_id',$decrypt_id);
    //     }
    //     if($request->branch != 0 ){

    //         $query =  $query->where('transactions.branch_id',$request->branch);
    //     }
    //     if($request->type != 'null' && $request->type != 0 ){
    //         $query =  $query->where('transactions.transaction_type_id',$request->type);
    //     }
    //     if($is_root){
    //         if($request->username != 'null' && $request->username != 0){

    //             $query =  $query->where('transactions.created_by',$request->username);
    //         }
    //     }
    //     if($request->from != null && $request->to != null){
    //         $query =  $query->whereDate('transactions.created_date', '<=',add_day_to_date(0,$request->to) )
    //         ->whereDate('transactions.created_date', '>=', add_day_to_date(0,$request->from));
    //     }
    //     if (!empty($filter)) {
    //         $query->where(function($query) use ($filter){
    //             $query->orwhere('users.first_name', 'like', '%'.$filter.'%');
    //             $query->orWhere('users.phone', 'like', '%' .$filter. '%');
    //             $query->orWhere('savings_products.savings_product', 'like', '%' .$filter. '%');
    //             $query->orWhere('transactions.transaction_reference', 'like', '%' .$filter. '%');
    //             $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' .$filter. '%');
    //         });
    //     }

    //     $recordsTotal = $query->count();
    //     $sortColumnName = $sortColumns[$order[0]['column']];
    //     // $query->orderBy($sortColumnName, $order[0]['dir'])
    //     //          ->take($length)
    //     //         ->skip($start);
    //     $json = array(
    //         'draw' => $draw,
    //         'recordsTotal' => $recordsTotal,
    //         'recordsFiltered' => $recordsTotal,
    //         'data' => [],
    //     );
    //     $info = $query->get();
    //     $sn =0;

    //     $deposit = 0;
    //     $withdraw = 0;
    //     $difference = 0;
    //     foreach ($info as $infoData) {
    //         $sn++;
    //         $btnlink = $infoData->id;
    //     $created= User::where('id', $infoData->created_by)->first();
    //     $officer= $infoData->account_officer_id?User::where('id', $infoData->account_officer_id )->first():null;

    //     if($infoData->credit_debit_type_id == 2){
    //     $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26)? "<button class='btn btn-warning'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Debit</button>":'<span class="btn btn-danger">Reversed</span>';
    //     }else{
    //     $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26)? "<button class='btn btn-secondary'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Credit</button>":'<span class="btn btn-danger">Reversed</span>';

    //     }
    //     $badge = ($infoData->credit_debit_type_id == 1)?"success":"danger";
    //     $deposit +=($infoData->credit_debit_type_id == 1)? $infoData->amount :0;
    //     $withdraw +=($infoData->credit_debit_type_id == 2)? $infoData->amount :0;
    //     $json['data'][] = [
    //                 $sn,
    //                 $infoData->created_date,
    //                 $infoData->first_name.' '.$infoData->last_name,
    //                 $infoData->virtual_accnum,
    //                 $infoData->description,
    //                 "<span class='badge badge-".$badge."'>".$infoData->type."</span>",
    //                 "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> $infoData->transaction_reference</a>",
    //                 $infoData->accountsavingsp,

    //                 $officer->first_name.' '.$officer->last_name,

    //                 ($infoData->credit_debit_type_id == 2)? number_format($infoData->amount,2).auth()->user()->company->currency->symbol:'-',
    //                 ($infoData->credit_debit_type_id == 1)? number_format($infoData->amount,2).auth()->user()->company->currency->symbol:'-',
    //                 number_format($infoData->balance_before,2).auth()->user()->company->currency->symbol,
    //                 number_format($infoData->balance_after,2).auth()->user()->company->currency->symbol,
    //                 number_format($infoData->withdrawal_charge,2).auth()->user()->company->currency->symbol,
    //                 $created->first_name.' '.$created->last_name,

    //             ];

    //     }

    //     $difference = $deposit -$withdraw;



    //     $json['data'][] = [
    //         '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw </h5></span>',
    //         '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw ='.$withdraw.auth()->user()->company->currency->symbol.'</h5></span>',

    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',

    //         '','',
    //         '',
    //         '',
    //         '',
    //         '',

    //     ];
    //     $json['data'][] = [
    //         '<span class="badge badge-warning "><h5 class="text-light">Total Deposit </h5></span>',
    //         '<span class="badge badge-info"><h5 class="text-light">Total Deposit='.$deposit.auth()->user()->company->currency->symbol.'</h5></span></h4>',

    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',

    //     ];
    //     $json['data'][] = [
    //         '<span class="badge badge-success "><h5 class="text-light">Total Deposit </h5></span>',
    //         '<span class="badge badge-success"><h5 class="text-light">Difference ='.$difference.auth()->user()->company->currency->symbol.'</h5></span></h4>',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '',
    //         '','',
    //         '',
    //         '',

    //     ];
    //     }else{
    //         $json['data'] =[];
    //     }
    //         return $json;
    // }
    public function resetStatement()
    {
        $transactions = transactions::where('description', 'Withdraw from Esusu Wallet to Savings Wallet')->get();
        if ($transactions) {
            foreach ($transactions as $transactionsData) {
                $txn  = transactions::where('id', $transactionsData->id)->first();
                $txn->transaction_type_id = 37;
                $txn->save();
                echo  "<br> TXNID: $transactionsData->id Updated";
            }
        }
    }
    public function staff_txn_table(Request $request)
    {
        if (!cando('view-teller-transaction|view-all-teller-transaction|root')) return  abort(403);

        $query =transactions::join('users', 'users.id', '=', 'transactions.created_by')
        ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
        ->select('transactions.*','transaction_types.transaction_type', 'users.first_name', 'users.last_name', 'users.username')
        ->where([
            'users.user_type_id' => '2',
            'users.company_id' => auth()->user()->company_id,
        ])->where(['users.user_type_id' => '2', 'users.company_id' => auth()->user()->company_id] )
        ->whereIn('transactions.transaction_type_id', [45, 44]);
            $is_root = cando('root|view-all-teller-transaction');
            if (!$is_root) {
                $query = $query->where('transactions.created_by', auth()->user()->id);
            }
            if($request->staff){
                $query = $query->where('users.uuid',$request->staff);
            }
            if($request->from && $request->to){
                $query = $query->whereBetween('transactions.created_at', [$request->from, $request->to]);
            }
            $query = $query->orderBy('transactions.created_at','desc')->limit(100); ;
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'staff_txn_table',
            'page_menu' => 'staff_txn_table',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'request'=>$request,
            'query'=>$query->get(),
            'staff'=>User::where(['user_type_id'=>2,'company_id'=>auth()->user()->company_id])->get()
        ];

        return view('company.report.staff_txn_table')->with($data);
    }
    public function resetTellerwallet(Request $request){
        $userID = $request->id??auth()->user()->id;
        $date = $request->date??date('Y-m-d');
        $tellerbalance =null;
        echo "********BB****AMT****BA*****<br>";
        $tellerTXN =transactions::where('created_by',$userID)->where('created_at','>',$date)->whereIn('transactions.transaction_type_id', [45, 44])->orderby('id','asc')->get();
        if($tellerTXN){
            $i=0;
            $balanceAfter = 0;

            foreach($tellerTXN as $tellerData){

                $action = $tellerData->credit_debit_type_id;
                $balanceBefore = $tellerData->balance_before;
                // $balanceAfter = $tellerData->balance_after||0;

                if($i !=0){
                    $balanceBefore = $balanceAfter;
                    $balanceAfter =$action===1? $balanceBefore + $tellerData->amount :$balanceBefore - $tellerData->amount;
                }else{
                    $balanceAfter = $action===1? $balanceBefore + $tellerData->amount :$balanceBefore - $tellerData->amount;
                }


                if(transactions::where('id',$tellerData->id)->update(['balance_after'=>$balanceAfter , 'balance_before'=>$balanceBefore  ])){
                    $tellerbalance =$balanceAfter;
                }

                echo "******** $balanceBefore  $tellerData->amount $balanceAfter ".($action==1?'Credit':'Debit')."***********</br>";
            $i++;

            }
            if($tellerbalance){
                StaffWallet::where(['user_id' => $userID])->update(['balance' => $tellerbalance]);
                echo "Balance is $tellerbalance";
            }

        }
    }
}
