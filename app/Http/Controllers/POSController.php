<?php

namespace App\Http\Controllers;

use App\Models\AgentTerminal;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerRestriction;
use App\Models\MainCustomerRestriction;
use App\Models\CustomerPermission;
use App\Models\transactions;
use Illuminate\Support\Facades\Validator;

class POSController extends Controller
{
    //

    function __construct()
    {
        //$this->middleware("custom_permission:customer-restriction|allow-staff-view-all-customers|root");
    }
    public function index()
    {
        if (!cando('view-pos-terminals|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'pos_management',
            'page_menu' => 'pos_management',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'permission' => CustomerPermission::all(),

        ];
        // $pageName = 'html5';

        return view('company.pos.index')->with($data);
    }

    public function creditPOS(Request $request)
    {
        if (!cando('add-pos|root')) return abort(403);
        $validation = Validator::make($request->all(), [
            "customer_reference" => 'required|string',
            "terminal_serial" => 'required|numeric|gt:0',
            "transaction_amount" => 'required|numeric|gt:0'
        ]);


        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }
        $POS_Admin_Topup = get_CR_DR_TXN_Type_Ledger(68, auth()->user()->company_id);
        $ledger_cr=null;
        $ledger_dr=null;
        if($POS_Admin_Topup){
            $ledger_cr = $POS_Admin_Topup->ledger_cr;
            $ledger_dr = $POS_Admin_Topup->ledger_dr;
        }else{
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set POS Admin Topup transaction type in ledger settings !']);
        }
        $POS_Agent_Company_Debit = get_CR_DR_TXN_Type_Ledger(75, auth()->user()->company_id);
        $companyledger_cr=null;
        $companyledger_dr=null;
        if($POS_Agent_Company_Debit){
            $companyledger_cr = $POS_Agent_Company_Debit->ledger_cr;
            $companyledger_dr = $POS_Agent_Company_Debit->ledger_dr;
        }else{
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set POS Agent Company Debit transaction type in ledger settings !']);
        }
        $user = User::where('uuid', $request->customer_reference)->first();

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Reference']);
        }

        if ($user->company_id != auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'Cannot credit this customer']);
        }

        $userPos = AgentTerminal::find($request->terminal_serial);

        if (!$userPos) {
            return response()->json(['status' => 0, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => 0, 'message' => 'POS Serial number does to belong to the user']);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($user->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        $transactionRef = generate_txnID();

        if (auth()->user()->company->wallet->balance < $request->transaction_amount) {
            return response()->json(['status' => 0, 'message' => 'Insufficient Company Wallet balance']);
        }

        $transaction = transactions::create([
            "balance_before" => $userPos->balance,
            "amount" => $request->transaction_amount,
            "balance_after" => $userPos->balance + $request->transaction_amount,
            "description" => "POS Wallet Credit - $userPos->terminal_code",
            "transaction_reference" => $transactionRef,
            "account_id" => $user->accounts[0]->id,
            "company_balance_before" => 0,
            "company_amount" => 0,
            "company_balance_after" => 0,
            "transaction_remarks_id" => 2,
            "transaction_type_id" => 68,
            "credit_debit_type_id" => 1,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id,
            "ledger_cr"=>$ledger_cr,
            "ledger_dr"=>$ledger_dr
        ]);

        $companyTransactionRef = generate_txnID();

        $transaction = transactions::create([
            "balance_before" => 0,
            "amount" => 0,
            "balance_after" => 0,
            "description" => "Funded Terminal - $userPos->terminal_code the sum of $request->transaction_amount",
            "transaction_reference" => $companyTransactionRef,
            "company_id" => auth()->user()->company_id,
            "company_balance_before" => auth()->user()->company->wallet->balance,
            "company_amount" => $request->transaction_amount,
            "company_balance_after" => auth()->user()->company->wallet->balance - $request->transaction_amount,
            "transaction_remarks_id" => 2,
            "transaction_type_id" => 75,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id,
            "ledger_cr"=>$companyledger_cr,
            "ledger_dr"=>$companyledger_dr
            //
        ]);

        $userPos->balance += $request->transaction_amount;
        $userPos->save();

        $company_wallet = auth()->user()->company->wallet;

        $company_wallet->balance -= $request->transaction_amount;
        $company_wallet->save();

        sendSMS($user->accounts[0]->id, "POS Credit Amt:$request->transaction_amount TXNcharge:0 Desc: POS Wallet Credit - $userPos->terminal_code Avail Bal:$userPos->balance Date:" . date('Y-m-d') . " Ref:$transactionRef", false, true, 'Credit Transaction');

        return response()->json(['status' => 1, 'message' => 'POS Terminal Credited Successfully']);
    }

    public function debitPOS(Request $request)
    {
        if (!cando('debit-pos|root')) return abort(403);
        $validation = Validator::make($request->all(), [
            "customer_reference" => 'required|string',
            "terminal_serial" => 'required|numeric|gt:0',
            "transaction_amount" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }
        $POS_Admin_Debit = get_CR_DR_TXN_Type_Ledger(69, auth()->user()->company_id);
        if($POS_Admin_Debit){
            $POS_Admin_ledger_cr = $POS_Admin_Debit->ledger_cr;
            $POS_Admin_ledger_dr = $POS_Admin_Debit->ledger_dr;
        }else{
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set POS Admin Debit transaction type in ledger settings !']);
        }
        $POS_Agent_Company_Credit = get_CR_DR_TXN_Type_Ledger(74, auth()->user()->company_id);
        if($POS_Agent_Company_Credit){
            $POS_Agent_ledger_cr = $POS_Agent_Company_Credit->ledger_cr;
            $POS_Agent_ledger_dr = $POS_Agent_Company_Credit->ledger_dr;
        }else{
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set POS Agent Company Debit transaction type in ledger settings !']);
        }
        $user = User::where('uuid', $request->customer_reference)->first();

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Reference']);
        }

        if ($user->company_id != auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'Cannot credit this customer']);
        }

        $userPos = AgentTerminal::find($request->terminal_serial);

        if (!$userPos) {
            return response()->json(['status' => 0, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => 0, 'message' => 'POS Serial number does to belong to the user']);
        }

        $transactionRef = generate_txnID();

        if ($userPos->balance < $request->transaction_amount) {
            return response()->json(['status' => 0, 'message' => 'Insufficient POS balance']);
        }

        $creditCustomerAndCompany = creditCustomerAndCompanyPOSAmountWithCharge($user->accounts[0]->id, $userPos->terminal_code, auth()->user()->company_id, $request->transaction_amount, 0, 0, $user);

        logInfo($creditCustomerAndCompany, "Credit Customer and Company POS");

        $transaction = transactions::create([
            "balance_before" => $creditCustomerAndCompany['pos_balance_before'],
            "amount" => $request->transaction_amount,
            "balance_after" => $creditCustomerAndCompany['pos_balance_after'],
            "description" => "POS Wallet Debit - $userPos->terminal_code to credit Savings wallet",
            "transaction_reference" => $transactionRef,
            "account_id" => $user->accounts[0]->id,
            "company_balance_before" => 0,
            "company_amount" => 0,
            "company_balance_after" => 0,
            "transaction_remarks_id" => 2,
            "transaction_type_id" => 69,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id,
            "ledger_cr"=>$POS_Admin_ledger_cr,
            "ledger_dr"=>$POS_Admin_ledger_dr
        ]);

        $companyTransactionRef = generate_txnID();

        $transaction = transactions::create([
            "balance_before" => $creditCustomerAndCompany['balance_before'],
            "amount" => $request->transaction_amount,
            "balance_after" => $creditCustomerAndCompany['balance_after'],
            "description" => "Received payment from POS Terminal - $userPos->terminal_code. the sum of $request->transaction_amount",
            "transaction_reference" => $companyTransactionRef,
            "account_id" => $user->accounts[0]->id,
            "company_id" => auth()->user()->company_id,
            "company_balance_before" => $creditCustomerAndCompany['company_balance_before'],
            "company_amount" => $request->transaction_amount,
            "company_balance_after" => $creditCustomerAndCompany['company_balance_after'],
            "transaction_remarks_id" => 2,
            "transaction_type_id" => 74,
            "credit_debit_type_id" => 1,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id,
            "ledger_cr"=>$POS_Agent_ledger_cr,
            "ledger_dr"=>$POS_Agent_ledger_dr

        ]);

        sendSMS($user->accounts[0]->id, "Credit Amt:$request->transaction_amount TXNcharge:0 Desc: POS Wallet Credit - $userPos->terminal_code Avail Bal:$userPos->balance Date:" . date('Y-m-d') . " Ref:$transactionRef", false, true, 'Credit Transaction');


        return response()->json(['status' => 1, 'message' => 'POS Terminal Debited Successfully']);
    }

    public function reassignPOS(Request $request)
    {
        if (!cando('debit-pos|root')) return abort(403);

        logInfo($request, "Reassign request");

        $validation = Validator::make($request->all(), [
            "customer_reference" => 'required|string',
            "terminal_serial" => 'required|numeric|gt:0',
            "account_number" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $user = User::where('uuid', $request->customer_reference)->first();

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Reference']);
        }

        $newUser = User::where('virtual_account', $request->account_number)->first();

        if (!$newUser) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Account']);
        }

        if ($user->company_id != auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'Cannot Reassign this customer']);
        }

        $userPos = AgentTerminal::find($request->terminal_serial);

        if (!$userPos) {
            return response()->json(['status' => 0, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => 0, 'message' => 'POS Serial number does to belong to the user']);
        }

        $userPos->user_id = $newUser->id;
        $userPos->save();

        return response()->json(['status' => 1, 'message' => 'POS Terminal has been sucessfully reasign to a new customer']);
    }

    public function deactivateActivate(Request $request)
    {
        if (!cando('debit-pos|root')) return abort(403);

        $validation = Validator::make($request->all(), [
            "customer_reference" => 'required|string',
            "terminal_serial" => 'required|numeric|gt:0',
            "pos_status" => 'required|numeric|in:1,0',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $user = User::where('uuid', $request->customer_reference)->first();

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Reference']);
        }

        $newUser = User::where('uuid', $request->customer_reference)->first();

        if (!$newUser) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Account']);
        }

        if ($user->company_id != auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'Cannot Disable/Enable this customer']);
        }

        $userPos = AgentTerminal::find($request->terminal_serial);

        if (!$userPos) {
            return response()->json(['status' => 0, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => 0, 'message' => 'POS Serial number does to belong to the user']);
        }

        $userPos->status = $request->pos_status == 1 ? 0 : 1;
        $userPos->save();

        $terminalMessage = $request->pos_status == 1 ?  "POS Terminal has been Disabled Successfully" :  "POS Terminal has been Enabled Successfully";

        return response()->json(['status' => 1, 'message' => $terminalMessage]);
    }
    public function addPOS(Request $request)
    {
        if (!cando('add-pos|root')) return abort(403);
        $validation = Validator::make($request->all(), [
            "customer" => 'required|string',
            "pos_serial_number" => 'required|string'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $user = User::where('uuid', $request->customer)->first();

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Invalid User Reference']);
        }

        $userPos = AgentTerminal::where('terminal_code', $request->pos_serial_number)->first();

        if ($userPos) {
            return response()->json(['status' => 0, 'message' => 'POS Already exists']);
        }

        $agentSQL = AgentTerminal::create([
            'user_id' => $user->id,
            'status' => 1,
            'terminal_code' => $request->pos_serial_number,
        ]);

        logInfo($agentSQL);

        return response()->json(['status' => 1, 'message' => 'POS Terminal Added Successfully']);
    }

    public function ajax_view(Request $request)
    {
        if (!cando('view-pos-terminals|root')) return abort(403);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'users.first_name',
            1 => 'users.last_name',
            2 => 'users.phone',
            3 => 'agent_terminals.balance',
        );
        $query = AgentTerminal::join('users', 'users.id', '=', 'agent_terminals.user_id')
            ->select('agent_terminals.*', 'users.phone', 'users.virtual_account', 'users.last_name', 'users.first_name', 'users.uuid', 'users.created_by');

        $is_root = cando('root');
        if (!$is_root) {
            if (!cando('allow-staff-view-all-customers'))
                $query = $query->where('users.account_officer_id', auth()->user()->id);
        } else {
            $query = $query->where('users.company_id', auth()->user()->company_id);
        }
        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->where('users.last_name', 'like', '%' . $filter . '%');

            $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
            $query->orWhere('agent_terminals.balance', 'like', '%' . $filter . '%');
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
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $bydata = User::find($infoData->created_by);
            $byname = '';
            if ($bydata) {
                $byname = $bydata->first_name . ' ' . $bydata->last_name;
            }

            $userReference = $infoData->uuid;

            if ($is_root || cando('allow-staff-remove-any-customer-restriction')) {

                // $btn = '
                // <div class="btn-group" role="group">
                //     <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                //         Dropdown
                //         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                //     </button>
                //     <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                //         <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                //         <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                //     </ul>
                // </div>';

                $statusBtnText =  $infoData->status == 1 ? "Deactivate" : "Activate";

                $status = $infoData->status;

                $btn = "<button class='btn btn-outline-success btn-sm' onclick=";
                $btn .= "creditPOS('$btnlink','$userReference') >Credit</button>";
                $btn .= "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "debitPOS('$btnlink','$userReference') >Debit</button>";
                $btn .= "<button class='btn btn-outline-info btn-sm' onclick=";
                $btn .= "reassign('$btnlink','$userReference') >Re-assign</button>";
                $btn .= "<button class='btn btn-outline-info btn-sm' onclick=";
                $btn .= "deactivateActivate('$btnlink','$userReference','$status') >$statusBtnText</button>";
                // $btn .= "<button class='btn btn-outline-info btn-sm' onclick=";
                // $btn .= "deactivateActivate('$btnlink','$userReference', $status) >Descalkjs</button>";
            } else {
                $btn = "";
            }

            $posStatus = $infoData->status == 1 ? "<div class='badge badge-success me-4 btn-xs'>Active</div>" : "<div class='badge badge-danger me-4 btn-xs'>Disabled</div>";

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_account,
                $infoData->terminal_code,
                $infoData->balance,
                $posStatus,
                $byname,
                $btn
            ];
        }

        return $json;
    }
}
