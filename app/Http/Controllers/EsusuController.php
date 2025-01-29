<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\esusu_types;
use App\Models\Esusulog;
use App\Models\Esusu;
use App\Models\EsusuTXN;
use App\Models\BranchWallet;

use App\Models\wb_account_details;
use Illuminate\Support\Facades\Validator;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\PendingEsusu;
use App\Models\branch;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class EsusuController extends Controller
{
    private $is_root;
    function __construct()
    {
        $this->middleware("custom_permission:manage-esusu|esusu-operation|root");
        // $this->is_root = cando('root');
    }
    public function index($company_name)
    {
        if (!cando('manage-esusu|root')) return abort(403);

        $data = [
            'category_name' => 'esusu',
            'page_name' => 'manage_esusu_product',
            'page_menu' => 'manage_esusu_product',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,

        ];
        return view('company.esusu.esusu_product')->with($data);
    }
    public function circle_out_history()
    {
        if (!cando('manage-esusu|root')) return  abort(403);

        $data = [
            'category_name' => 'esusu',
            'page_name' => 'manage_esusu_circleout',
            'page_menu' => 'manage_esusu_circleout',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,

        ];
        return view('company.esusu.circleout')->with($data);
    }
    public function esusu_statement($company, $id)
    {
        $id = Crypt::decryptString($id);
        if (!cando('manage-esusu|root')) return  abort(403);
        $data = [
            'category_name' => 'esusu',
            'page_name' => 'manage_esusu_statement',
            'page_menu' => 'manage_esusu_statement',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'esusu' => Esusu::where('id',$id)->first(),
        ];
        return view('company.esusu.statement')->with($data);
    }
    public function store(Request $request)
    {
        if (!cando('manage-esusu|root')) {
            abort(403);
            return 0;
        }
        $validator = Validator::make($request->all(), [
            'esusu_type' => 'required|string|max:50|min:2',
            'min_rate' => 'required',
            'max_rate' => 'required',
            'circle_charge' => 'required',
            'duration' => 'required',
            'overdraft_charge' => 'required',
            'productid' => 'required',
            'status' => 'required',
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
        if($request->overdraft_charge > 100){
            return response()->json(['status' => 0, 'message' => 'Overdraft charge should not be above 100%!']);
        }
        if($request->overdraft_charge > 100){
            return response()->json(['status' => 0, 'message' => 'Circleout charge should not be above 100%!']);
        }
        if ($request->productid == 0) {
            if (esusu_types::where([
                'company_id' => auth()->user()->company_id,
                'esusu_type' => $request->esusu_type
            ])->count() > 0) {
                return response()->json(['status' => 0, 'message' => 'Name Already In use!']);
            }
            $new_esusu = [
                'company_id' => auth()->user()->company_id,
                'esusu_type' => $request->esusu_type,
                'min_rate' => $request->min_rate,
                'max_rate' => $request->max_rate,
                'circle_charge' => $request->circle_charge,
                'duration' => $request->duration,
                'overdraft_charge' => $request->overdraft_charge,
                'status' => $request->status,
            ];
            if (esusu_types::create($new_esusu)) {
                return response()->json(['status' => 1, 'message' => 'Esusu Product Added Successfully!']);
            } else {
                return response()->json(['status' => 0, 'message' => 'Operation Failed!']);
            }
        } else if ($request->productid > 0) {

            $new_esusu = [

                'esusu_type' => $request->esusu_type,
                'min_rate' => $request->min_rate,
                'max_rate' => $request->max_rate,
                'circle_charge' => $request->circle_charge,
                'duration' => $request->duration,
                'overdraft_charge' => $request->overdraft_charge,
                'status' => $request->status,

            ];
            if (esusu_types::where(['id' => $request->productid, 'company_id' => auth()->user()->company_id])->update($new_esusu)) {
                return response()->json(['status' => 1, 'message' => 'Esusu Product Updated Successfully!']);
            } else {
                return response()->json(['status' => 0, 'message' => 'Operation Failed!']);
            }
        }
    }
    public function edit_product($id)
    {
        if (!cando('manage-esusu|root')) {
            abort(403);
            return 0;
        }
        return response()->json(esusu_types::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->first());
    }

    public function display_product(Request $request)
    {
        if (!cando('manage-esusu|root')) return abort(403);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        // response()->json($request->all());
        $sortColumns = array(
            0 => 'esusu_type',
            1 => 'min_rate',
            2 => 'max_rate',
            3 => 'circle_charge',
            4 => 'duration',
            5 => 'status',
            6 => 'overdraft_charge',
            7 => 'created_at',
            8 => 'created_at',
        );
        $query = esusu_types::where(['company_id' => auth()->user()->company_id]);
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('esusu_type', 'like', '%' . $filter . '%');
                $query->orWhere('min_rate', 'like', '%' . $filter . '%');
                $query->orWhere('max_rate', 'like', '%' . $filter . '%');
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
        $loop = 0;
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $btn = "<button data-toggle='modal'
                    data-target='#exampleModal' class='btn btn-outline-primary btn-sm' onclick=";
            $btn .= "edit_product('$btnlink') ><i class='fa fa-edit'></i></button>";
            $btn .= "<button class='btn btn-outline-danger btn-sm' onclick=";
            $btn .= "delete_product('$btnlink') ><i class='fa fa-trash'></i></button>";
            $json['data'][] = [
                $infoData->esusu_type,
                number_format($infoData->min_rate, 2),
                number_format($infoData->max_rate, 2),
                number_format($infoData->circle_charge, 2),
                $infoData->duration,
                $infoData->status,
                $infoData->overdraft_charge,
                $infoData->created_at,

                $btn,
            ];
        }

        return $json;
    }
    public function delete_product($id)
    {
        if (!cando('manage-esusu|root')) {
            abort(403);
            return 0;
        }
        $esusu = Esusu::where(['esusu_type_id' => $id])->count();
        if ($esusu > 0) {
            return response()->json(['status' => 0, 'message' => 'Product is being use by an account!']);
        }
        $esu =   esusu_types::where(['company_id' => auth()->user()->company_id, 'id' => $id])->delete();
        if ($esu)
            return response()->json(['status' => 1, 'message' => 'Esusu Product DeletedSuccessfully!']);
        else
            return response()->json(['status' => 0, 'message' => 'Operation Failed!']);
    }
    public function esusu($company_name)
    {


        $data = [
            'category_name' => 'datatable',
            'page_name' => 'esusu',
            'page_menu' => 'esusu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,

        ];
        return view('company.esusu.esusu_operation')->with($data);
    }
    public function Esusu_Productlist(Request $request)
    {

        $search = $request->searchTerm;
        if ($search != null) {
            $user = esusu_types::where(['company_id' => auth()->user()->company_id])
                ->where('esusu_type', 'like', '%' . $search . '%')

                ->paginate(10);
        } else {
            $user = esusu_types::where(['company_id' => auth()->user()->company_id])->paginate(10);
        }

        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->id, 'text' => $userdata->esusu_type . ' (' . $userdata->min_rate . ' - ' . $userdata->max_rate . ') ' . $userdata->duration . 'days'];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function create_esusu_wallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'savetype' => 'required',
            'rate' => 'required',
            'start_date' => 'required',
            'esusu_product' => 'required',
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
        // esusu_type_id //wb_account_detail_id // rate //commence_date //prev_card // today_card
        //today_cash // circle_out //withdrawable // wallet //overdraft //missed_day // status_id
        $esusu_type_id  = $request->esusu_product;
        $wb_account_detail_id  = $request->savetype;
        $rate  = $request->rate;
        $start_date  = $request->start_date;
        $useraccount = wb_account_details::where('id',$wb_account_detail_id)->first();
        if (!$useraccount) return response()->json(['status' => 0, 'message' => 'Block User Error!']);

        //restricted
        if (!restrict_to_just_my_company(['user_id' => $useraccount->user_id])) return json_encode(['status' => 0, 'message' => 'staff restriction']);

        if ($useraccount->user->company_id != auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'User Not Found!']);
        }
        $esusu_type_data =$datacount = esusu_types::where(['id' => $esusu_type_id, 'company_id' => auth()->user()->company_id]);
        if ($esusu_type_data->count() == 0) {
            return response()->json(['status' => 0, 'message' => 'Esusu Type no Found!']);
        }
        if ($datacount->count() == 0) {
            return ['status' => 0, 'message' => 'Esusu Type no Found! '];
        }
        $esusu_type_data = $esusu_type_data->first();
        if($esusu_type_data->min_rate > $rate ){
            return ['status' => 0, 'message' => 'The minimum rate for this plan is '.$esusu_type_data->min_rate];
        }
        if($esusu_type_data->max_rate < $rate ){
            return ['status' => 0, 'message' => 'The maximum rate for this plan is '.$esusu_type_data->max_rate];
        }
        $esusu_already_exist = Esusu::where(['wb_account_detail_id' => $wb_account_detail_id, 'esusu_type_id' => $esusu_type_id])->count();
        if ($esusu_already_exist) return response()->json(['status' => 0, 'message' => 'Esusu Already Exist for this account!']);
        $esusu_type = $esusu_type_data->first();
        // $minrate = $esusu_type->min_rate;
        // $maxrate = $esusu_type->max_rate;
        // if ($rate < $minrate || $rate > $maxrate) {
        //     return response()->json(['status' => 0, 'message' => "The Min and Max Rate for this Plan is $minrate  to $maxrate!"]);
        // }
        if (Esusu::create(
            [
                'wb_account_detail_id' => $wb_account_detail_id,
                'rate' => $rate,
                'commence_date' => $start_date,
                'prev_card' => 0,
                'esusu_type_id' => $esusu_type_id,
                'start_date' => $start_date, // esusu txn
                'withdrawable' => 0,
                'wallet' => 0,
                'overdraft' => 0,
                'missed_day' => 0,
                'status_id' => 1,
            ]
        )) {
            return response()->json(['status' => 1, 'message' => 'Esusu Account Created!']);
        } else {
            return response()->json(['status' => 0, 'message' => 'Failed to Register Esusu Account!']);
        }
    }
    // EsusuTXN
    public function display_esusu(Request $request)
    {
        if (!cando('esusu-operation|manage-esusu|root|allow-staff-view-all-customers')) return   ['status'=>false,'message'=>'Permission denied'];
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'esusu_types.esusu_type',
            1 => 'users.first_name',
            2 => 'wb_account_details.virtual_accnum',
            3 => 'rate',
            4 => 'start_date',
            5 => 'commence_date',
            6 => 'created_at',
            7 => 'created_at',
            8 => 'created_at',
            9 => 'created_at',
            10 => 'created_at',
            11 => 'created_at',
            12 => 'created_at',
            13 => 'created_at',
            14 => 'created_at',
            15 => 'created_at',
        );
        if ($request->log == null) {
            $query = Esusu::join('wb_account_details', 'wb_account_details.id', '=', 'esusus.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('esusu_types', 'esusu_types.id', '=', 'esusus.esusu_type_id')
                ->where(['users.company_id' => auth()->user()->company_id, 'esusus.status_id' => 1])
                ->select('esusus.*', 'users.first_name', 'users.last_name', 'esusu_types.esusu_type', 'wb_account_details.virtual_accnum');
            if($request->hasAgent != null){
                $query = $query->where('esusus.hasAgent',1);
            }

        } else {
            $query =   Esusulog::join('esusus', 'esusus.id', '=', 'esusulogs.esusu_id')
                ->join('wb_account_details', 'wb_account_details.id', '=', 'esusus.wb_account_detail_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('esusu_types', 'esusu_types.id', '=', 'esusus.esusu_type_id')
                ->where(['users.company_id' => auth()->user()->company_id])
                ->select('esusulogs.*', 'esusus.esusu_type_id', 'users.first_name', 'users.last_name', 'esusu_types.esusu_type', 'wb_account_details.virtual_accnum');
        }
        $is_root = (cando('esusu-operation|root|allow-staff-view-all-customers'));
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('esusu_types.esusu_type', 'like', '%' . $filter . '%');
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

        $loop = 0;

        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $esusu_circleout =  Esusulog::where(['esusu_id' => $btnlink])->count();
            $today_card = EsusuTXN::where(['esusu_id' => $btnlink])->whereDay('created_at', '=', date('d'))
                ->sum('no_card');
            $prev_card = EsusuTXN::where(['esusu_id' => $btnlink])->whereDay('created_at', '<', date('d'))
                ->sum('no_card');
            $start_date = $infoData->commence_date;
            $allcard =  EsusuTXN::where(['esusu_id' => $btnlink])->whereDate('created_date', '=', $start_date)->sum('no_card');

            $days_so_far = diff_btw_date($start_date, date('Y-m-d'));
            if (date('Y-m-d') >= $start_date) {
                $day_missed = ($days_so_far > $allcard) ? $days_so_far - $allcard : 0;
            } else {
                $day_missed = 0;
            }
            $today_cash = $today_card * $infoData->rate;
            if ($request->log == null) {
                $id_decrypt = Crypt::encryptString($btnlink);
                $wb_account_detail_id = Crypt::encryptString($infoData->wb_account_detail_id);
                $statement = url('/company/' . auth()->user()->company->short_name . '/esusu/statement/' . $id_decrypt . '?uid=' . $wb_account_detail_id);
                $btn = "<button data-toggle='modal'
                        data-target='#exampleModal' class='btn btn-outline-primary btn-sm' onclick=";
                $btn .= "edit_product('$btnlink') ><i class='fa fa-edit'></i></button>";
                $btn .= "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "delete_product('$btnlink') ><i class='fa fa-trash'></i></button>";

                $btn = '<div class="dropdown">
                            <button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Actions
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',1)">Topup</a>
                                <a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',2)">Withdraw</a>
                                <a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',3)">Apply Overdraft</a>
                                <a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',4)">Reset</a>
                                <a class="dropdown-item" href="' . $statement . '" >View History</a>
                            </div>
                            </div>';
            } else {
                $btn = '';
            }
            $json['data'][] = [
                $infoData->esusu_type,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                number_format($infoData->rate, 2),
                $infoData->start_date,
                $infoData->commence_date,
                $prev_card,
                $today_card,
                number_format($today_cash, 2),
                $esusu_circleout, //Circle

                number_format($infoData->withdrawable, 2),
                number_format($infoData->wallet, 2),
                number_format($infoData->overdraft, 2),
                number_format($infoData->accum_charge, 2),
                $day_missed,

                $btn,
            ];
        }

        return $json;
    }

    public function topup_script($request)
    {
        \logInfo('Esusu deposit ', "Esusu deposit");

        //&& !cando('root')
        if ($request->number < 1) {
            return response()->json(['status' => 0, 'message' => 'Number should be greater than zero!']);
        }
        // $esusu = Esusu::find($request->esusuid);
        $esusu = Esusu::where('id',$request->esusuid)->first();
        if(!$esusu){
            return response()->json(['status' => 0, 'message' => 'Error Processing Request!']);
        }
        $wb_id = $esusu->wb_account_detail_id;
        $number = $request->number;
        $esusuid = $request->esusuid;

        $rate = $esusu->rate;
        $cash = $rate * $number;
        // $walletfinder = wb_account_details::find($wb_id);
        $walletfinder = wb_account_details::where(['id'=>$wb_id])->first();

        $balanceBefore = $walletfinder->user_wallet->balance;
        // if($balanceBefore < $cash){ // we no longer check this
        //     return response()->json(['status' => 0, 'message' => 'You dont have enough cash in your wallet to perform this transaction!']);
        // }
        // return response()->json(['status' => 0, 'message' => "$balanceBefore $cash You dont have enough cash in your wallet to perform this transaction!"]);
        $get_ledger_setting = get_CR_DR_TXN_Type_Ledger(35,auth()->user()->company_id);
        if($get_ledger_setting){
            $request->ledger_cr = $get_ledger_setting->ledger_cr;
            $request->ledger_dr = $get_ledger_setting->ledger_dr;
        }else{
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Esusu Deposit transaction type in ledger settings !']);
        }
        $transactions = transactions::where('account_id', $wb_id)->orderBy('id', 'desc')->first();

        if ($transactions) {
            $dbtimestamp = strtotime($transactions->created_at);
            if (time() - $dbtimestamp < 1 * 60) {
                //return response()->json(["status" => 0, "message" => env('txn_restrict_msg')]);
            }
        }
        if ($request->pending == null && !cando('root|direct-debit-credit')) {
            $companyEsusu_type = auth()->user()->company->setting->esusu_type_id;
            if ($companyEsusu_type == 1) {
                if (PendingEsusu::where(['esusuid' => $request->esusuid, 'pending' => 'topup'])->count() > 0) {
                    return response()->json(['status' => 0, 'message' => 'Request Already Sent!']);
                }
                if (PendingEsusu::create([
                    'esusuid' => $request->esusuid,
                    'number' => $request->number,
                    'pending' => 'topup',
                    'created_by' => auth()->user()->id,
                    'ledger_cr'=>$request->ledger_cr,
                    'ledger_dr'=>$request->ledger_dr,
                ])) {
                    return response()->json(['status' => 1, 'message' => 'Topup Sent to Pending Transaction!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Topup Failed!']);
                }
            }
        }
        if ($request->created_by != null) {
            $created_by = User::where('id',$request->created_by)->first();
        } else {
            $created_by =User::where('id',auth()->user()->id)->first(); // User::find(auth()->user()->id);
        }
        $balanceAfter = $balanceBefore + $cash;
        $backdate = date('Y-m-d h:i:s');
        $transactionId = generate_txnID();


        $data_credit = [
            "balance_before" => $balanceBefore,
            "amount" => $cash,
            "description" => "Esusu Wallet Deposit",
            "account_id" => $wb_id,
            "branch_id" => $walletfinder->user->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => 35, //debit 7
            "credit_debit_type_id" => 1, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "balance_after" => $balanceAfter,
            "created_date"  => $backdate,
            "created_by" => $created_by->id,
            'ledger_cr'=>$request->ledger_cr,
            'ledger_dr'=>$request->ledger_dr,
        ];
        $transaction_credit = transactions::create($data_credit);

        if (checkcustomerrestriction($walletfinder->user->id, 3)) {
            return ["status" => 0, "message" => "User is Restricted from performing this transaction!"];
        }

        $esusu->withdrawable += $cash;
        $start_date  =  $esusu->commence_date;
        $duration =  $esusu->esusu_type->duration;
        $this_date_deposit = EsusuTXN::where(['esusu_id' => $esusuid])->wheredate('created_date', '=', $start_date)->sum('no_card');
        $count_num_sofar = $this_date_deposit + $number;
        if ($count_num_sofar > $duration) {
            $supposenum = $count_num_sofar - $duration;
            return response()->json(['status' => 0, 'message' => "Number of input exceed maximum required to complete this period. Put a number to complete the first period! $count_num_sofar $duration extra ($supposenum) "]);
        }
        // $suppose_day = add_day_to_date($duration, $start_date);
        // $days_so_far = diff_btw_date($start_date, $backdate);
        //  $transaction_credit = transactions::create($data_credit);
        // $transaction_debit = transactions::create($data_debit);

        // die(var_dump($data));
        if($request->method ==='wallet'){

            $transaction_type = getTransactionIdByName("Esusu topup wallet debit");
            //
            $data_debit = [
            "balance_before" => $balanceBefore,
            "amount" => $cash,
            "description" => "Esusu Wallet Deposit",
            "account_id" => $wb_id,
            "branch_id" => $walletfinder->user->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => 35, //debit 7
            "credit_debit_type_id" => 2, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "balance_after" => $balanceAfter,
            "created_date"  => $backdate,
            "created_by" => $created_by->id,
            'ledger_cr'=>$request->ledger_cr,
            'ledger_dr'=>$request->ledger_dr,
            ];
            $data_debit['transaction_type_id'] = $transaction_type->id;
            $data_debit['userParamType'] = 2;
            $data_debit['userId'] = $data_credit['account_id'];
            $data_debit['remark'] = 2;
            $data_debit['chargeamt'] = 0;
            $data_debit['companyVault'] = null;

            $CreditCustomer = DebitCreditcustomer($data_debit);
            if (!$CreditCustomer['status']) {
                // DB::rollBack();
                return $CreditCustomer;
            }
            // $transaction_credit2 = $CreditCustomer['data'];
        }
        EsusuTXN::create(['transaction_id'=>$transaction_credit->id,'esusu_id' => $esusuid, 'no_card' => $number, 'created_date' => $esusu->commence_date]);
        if ($esusu->save()) {
            Esusulog::where(['esusu_id' => $esusuid])->count();
            if (($count_num_sofar >= $duration)) {
                // circle_out_charge
                $circle_charge_percentage =  $esusu->esusu_type->circle_charge;
                $circle_charge = ($circle_charge_percentage / 100) * $rate;
                $esusu_circleout =  Esusulog::where(['esusu_id' => $esusuid])->count();
                $today_card = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '=', date('d'))
                    ->sum('no_card');
                $prev_card = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '<', date('d'))
                    ->sum('no_card');
                $today_cash = $today_card * $rate;
                Esusulog::create([
                    'circle_out_charge' => $circle_charge,
                    'esusu_id' => $esusuid,
                    'rate' => $rate,
                    'commence_date' => $esusu->commence_date,
                    'prev_card' => $prev_card,
                    'today_card' => $today_card,
                    'today_cash' => $today_cash,
                    'circle_out' => $esusu_circleout,
                    'withdrawable' => $esusu->withdrawable,
                    'wallet' => $esusu->wallet,
                    'overdraft' => $esusu->overdraft,
                    'missed_day' => $esusu->missed_day,
                    'status_id' => 1,
                ]);
                $esusu->wallet = $wallet =  ($esusu->wallet + ($esusu->withdrawable));
                $esusu->accum_charge += $circle_charge;
                $esusu->withdrawable = 0;
                $esusu->commence_date = add_day_to_date(1, $start_date);
                $esusu->save();
            }
            $smsstate = sendSMS(['txnid' => null, 'userid' => $wb_id], "Credit Amt:$cash Desc: Credit from Esusu Wallet Avail Bal:$balanceAfter Date:$backdate Ref:$transactionId", false, true, 'Esusu  Credit');
            return response()->json(["status" => 1, "message" => "Transaction Successful!"]);
            // $redirectlink = "transaction-receipt/$transactionId";
        } else {
            return response()->json(["status" => 0, "message" => "Transaction Failed!"]);
        }
    }
    public function withdraw_script($request)
    {
        \logInfo('Esusu Withdraw ', "Esusu withdraw");
        $wallet_to_main = $request->wallet_to_main;
        $amount = $request->amount;
        $esusuid = $request->esusuid;
        if (($request->amount < 1) && $wallet_to_main > 1) {
            return response()->json(['status' => 0, 'message' => 'Amount should be greater than zero!']);
        }
        $esusu = Esusu::find($request->esusuid);
        $wb_id = $esusu->wb_account_detail_id;
        $userid = $esusu->wb_account_details->user_id;
        if (checkcustomerrestriction($userid, 3)) {
            return ["status" => 0, "message" => "User is Restricted from performing this transaction!"];
        }
        $created_by =auth()->user();
        $esusu_withdrawal_ledger = get_CR_DR_TXN_Type_Ledger(37,$created_by->company_id);
        if (!$esusu_withdrawal_ledger) {
            return ["status" => 0, "message" => "Set ledger for Esusu Withdrawal transaction type!"];
        }
        $Esusu_Wallet_Debit = get_CR_DR_TXN_Type_Ledger(73,$created_by->company_id);
        if (!$Esusu_Wallet_Debit) {
            return ["status" => 0, "message" => "Set ledger for Esusu Wallet Debit transaction type!"];
        }
        $Esusu_Overdraft_Debit = get_CR_DR_TXN_Type_Ledger(40,$created_by->company_id);
        if (!$Esusu_Overdraft_Debit) {
            return ["status" => 0, "message" => "Set ledger for Esusu Overdraft Debit transaction type!"];
        }
        $Esusu_Accum_Charge = get_CR_DR_TXN_Type_Ledger(39,$created_by->company_id);
        if (!$Esusu_Accum_Charge) {
            return ["status" => 0, "message" => "Set ledger for Esusu Accum Charge transaction type!"];
        }

        $transactions = transactions::where('account_id', $wb_id)->orderBy('id', 'desc')->first();
        if ($transactions) {
            $dbtimestamp = strtotime($transactions->created_at);
            if (time() - $dbtimestamp < 1 * 60) {
                return response()->json(["status" => 0, "message" => env('txn_restrict_msg')]);
            }
        }
        if ($request->created_by != null) {
            $created_by = User::where('id',$request->created_by)->first();
        } else {
            $created_by =User::where('id',auth()->user()->id)->first(); // User::find(auth()->user()->id);
        }
        if ($request->pending == null  && !cando('root|direct-debit-credit')) {
            $companyEsusu_type = auth()->user()->company->setting->esusu_type_id;
            if ($companyEsusu_type == 1) {
                if (PendingEsusu::where(['esusuid' => $request->esusuid, 'pending' => 'withdraw'])->count() > 0) {
                    return response()->json(['status' => 0, 'message' => 'Request Already Sent !']);
                }

                // $esusu_withdrawal_ledger $Esusu_Wallet_Debit  $Esusu_Overdraft_Debit $Esusu_Accum_Charge
                if (PendingEsusu::create([
                    'esusuid' => $request->esusuid,
                    'amount' => $request->amount,
                    'wallet_to_main' => $request->wallet_to_main,
                    'withdraw_to_wallet' => $request->withdraw_to_wallet,
                    'pending' => 'withdraw',
                    'created_by' => auth()->user()->id,
                    'ledger_cr'=>($wallet_to_main == 1)?$esusu_withdrawal_ledger->ledger_cr:$Esusu_Wallet_Debit->ledger_cr,//$request->ledger_cr,
                    'ledger_dr'=>($wallet_to_main == 1)?$esusu_withdrawal_ledger->ledger_dr:$Esusu_Wallet_Debit->ledger_dr,//$request->ledger_dr,
                ])) {
                    return response()->json(['status' => 1, 'message' => 'Withdrawal Request Pending Transaction!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Rquest Failed!']);
                }
            }
        }
        $rate = $esusu->rate;
        if ($wallet_to_main) {
            if($wallet_to_main > 1){
                if ($esusu->wallet < $amount) {
                    if ($request->withdraw_to_wallet) {
                        if (($esusu->wallet + $esusu->withdrawable) < $amount) {
                            return response()->json(['status' => 0, 'message' => 'You dont have enough cash to perform this transaction!']);
                            exit();
                        }
                    } else {
                        return response()->json(['status' => 0, 'message' => 'Wallet balance is too low for this transaction, you can tick the withdraw from withdrawable to wallet!']);
                        exit();
                    }
                }
            }
        }

        $start_date  =  $esusu->commence_date;
        $wb_id = $esusu->wb_account_detail_id;
        $walletfinder = wb_account_details::find($wb_id);
        $balanceBefore = $walletfinder->user_wallet->balance;
        $circle_charge_percentage =  $esusu->esusu_type->circle_charge;
        $circle_charge = ($circle_charge_percentage / 100) * $rate;
        $backdate = date('Y-m-d h:i:s');
        //$balanceAfter = $balanceBefore + $cash;
        $logtxn = null;
        if ($request->withdraw_to_wallet == 1 &&  $esusu->withdrawable > 0 ) {
            if ($esusu->withdrawable >= $circle_charge) {
                $esusu->wallet += ($esusu->withdrawable);
                $esusu->accum_charge += $circle_charge;
                $esusu->withdrawable = 0;
                $esusu->commence_date = add_day_to_date(1, $start_date);
                $esusu->save();
                //vault transaction
                $esusu_circleout =  Esusulog::where(['esusu_id' => $esusuid])->count();
                $today_card = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '=', date('d'))
                    ->sum('no_card');
                $prev_card = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '<', date('d'))
                    ->sum('no_card');
                $today_cash = $today_card * $rate;
                $logtxn = Esusulog::create([
                    'circle_out_charge' => $circle_charge,
                    'esusu_id' => $esusuid,
                    'rate' => $rate,
                    'commence_date' => $esusu->commence_date,
                    'prev_card' => $prev_card,
                    'today_card' => $today_card,
                    'today_cash' => $today_cash,
                    'circle_out' => $esusu_circleout,
                    'withdrawable' => $esusu->withdrawable,
                    'wallet' => $esusu->wallet,
                    'overdraft' => $esusu->overdraft,
                    'missed_day' => $esusu->missed_day,
                    'status_id' => 1,
                ]);
                if ($logtxn) {
                    if ($wallet_to_main == 1) {
                        return response(json_encode(["status" => 1, "message" => "All withdrawable to Wallet Transaction Successful!"]));
                    }
                }
            } else {
                return response()->json(['status' => 0, 'message' => 'Insuficient balance, withdrawable doesnt cover circle-out charges!']);
            }
        }
        if($wallet_to_main  > 1){
            if ($esusu->overdraft > 0) {
                $debit_overdraft = [
                    "balance_before" => $balanceBefore,
                    "amount" => $esusu->overdraft,
                    "description" => "Debit from Esusu Overdraft",
                    "account_id" => $wb_id,
                    "branch_id" => $walletfinder->user->branch_id,
                    "transaction_status_id" => 1,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 40, //debit 7
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 1, //success 2
                    "balance_after" => $balanceBefore,
                    "created_date"  => $backdate,
                    "created_by" => $created_by->id,
                    'ledger_cr'=>$Esusu_Overdraft_Debit->ledger_cr,
                    'ledger_dr'=>$Esusu_Overdraft_Debit->ledger_dr
                ];
                if ($esusu->wallet >= $esusu->overdraft) {
                    $esusu->wallet -= $esusu->overdraft;
                    $esusu->overdraft = 0;
                } else {
                    $leftoverdraft = $esusu->overdraft - $esusu->wallet;
                    $esusu->overdraft  = $leftoverdraft;
                    $debit_overdraft['amount'] = $esusu->wallet;
                    $esusu->wallet = 0;
                }
                transactions::create($debit_overdraft);

                if ($esusu->wallet < $amount) {
                    $esusu->save();
                    return response()->json(['status' => 0, 'message' => 'Overdraft due was debitted and the balance remaining is not enough to proceed with this transaction!']);
                }
            }
            $accum_charge = $esusu->accum_charge;
            if ($accum_charge > 0) {
                $data_debit_charges = [
                    "balance_before" => $balanceBefore,
                    "amount" => $accum_charge,
                    "description" => "Debit from Esusu Accumulated Charges",
                    "account_id" => $wb_id,
                    "branch_id" => $walletfinder->user->branch_id,
                    "transaction_status_id" => 1,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 39, //debit 7
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $balanceBefore,
                    "created_date"  => $backdate,
                    "created_by" => $created_by->id,
                    'ledger_cr'=>$Esusu_Accum_Charge->ledger_cr,
                    'ledger_dr'=>$Esusu_Accum_Charge->ledger_dr
                ];
                if ($esusu->wallet >= $accum_charge) {
                    $esusu->wallet -= $accum_charge;
                    $esusu->accum_charge = 0;
                } else {
                    $leftoverdraft = $esusu->accum_charge - $esusu->wallet;
                    $esusu->accum_charge  = $leftoverdraft;
                    $data_debit_charges['amount'] = $esusu->wallet;
                    $esusu->wallet = 0;
                }
                $accum_txt = transactions::create($data_debit_charges);
                if ($esusu->wallet < $amount) {
                    if ($accum_charge > 0) {
                        fundVault(['type' => 'esusu_circle_out_charge', 'value' => $accum_charge, 'company_id' => auth()->user()->company_id, 'created_by' => auth()->user()->id, 'branch_id' => auth()->user()->branch_id, 'transaction_id' => $accum_txt->id]);
                    }
                    $esusu->save();
                    return response()->json(['status' => 0, 'message' => 'Esusu Outstanding Charge was debitted and the balance remaining is not enough to proceed with this transaction!']);
                }
            }
            $esusu->wallet  -= $amount;
            $wallet = $esusu->wallet;
            $transactionId = generate_txnID();
            $balanceAfterx  = ($balanceBefore + $amount);
            if($wallet_to_main == 2){
                $credit_debit_type_id = 2;
            }else if($wallet_to_main == 3){
                $credit_debit_type_id = 1;
            }

            $data_credit_circle_out = [
                "balance_before" => $balanceBefore,
                "amount" => $amount,
                "description" => "Withdraw from Esusu Wallet to Savings Wallet",
                "account_id" => $wb_id,
                "branch_id" => $walletfinder->user->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" =>  ($credit_debit_type_id == 1)?37:73,
                "credit_debit_type_id" => $credit_debit_type_id,
                "transaction_remarks_id" => 1,
                "balance_after" => ($credit_debit_type_id == 1)?$balanceAfterx:$balanceBefore,
                "created_date"  => $backdate,
                "created_by" => $created_by->id,
                'ledger_cr'=>($credit_debit_type_id == 1)?$esusu_withdrawal_ledger->ledger_cr:$Esusu_Wallet_Debit->ledger_cr,
                'ledger_dr'=>($credit_debit_type_id == 1)?$esusu_withdrawal_ledger->ledger_dr:$Esusu_Wallet_Debit->ledger_dr
            ];
            $balanceAfter_x = $balanceAfterx;
            $transaction_credit_balance = transactions::create($data_credit_circle_out);
            $esusu->save();
            if ($logtxn != null) {
                $logtxn->txnid = $transaction_credit_balance->id;
                $logtxn->save();
            }
            $fbalance = ($balanceAfterx - $esusu->overdraft);
            if($wallet_to_main == 3){
                $chargwallet = user_wallet::find($walletfinder->user_wallet->id)->update(['balance' => $fbalance]);
                $BranchWallet =BranchWallet::where('branch_id',$walletfinder->user->branch_id)->first();
                $BranchWallet->balance += $amount;
                $BranchWallet->save();
            }else if($wallet_to_main == 2){
                $chargwallet =1;
            }
            if ($chargwallet) {
                $transaction_credit_balance->transaction_remarks_id = 2;
                $transaction_credit_balance->save();
                //$smsstate = sendSMS(['txnid' => $transaction_credit_balance->id, 'userid' => $wb_id], "Credit Amt:$amount Desc: Credit from Esusu Wallet Avail Bal:$fbalance Date:$backdate Ref:$transaction_credit_balance->transaction_reference ", false, true, 'Esusu  Credit');
                return response()->json(["status" => 1, "message" => "Esusu Wallet to Savings Wallet Transaction Successful!"]);
            } else {
                return response()->json(["status" => 0, "message" => "Fatal Error!"]);
            }
        }else{
            return response()->json(["status" => 0, "message" => "Withdrawable is zero or withdrawal option error!"]);
        }
    }
    public function topup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esusuid' => 'required',
            'number' => 'required',
            'method' => 'required',
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

        return $this->topup_script($request);
    }
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esusuid' => 'required',
            'wallet_to_main' => 'required',
            'amount' => 'required',

            'withdraw_to_wallet' => 'required',
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
        return $this->withdraw_script($request);
    }
    public function overdraft_script($request)
    {
        \logInfo('Esusu Overdraft ', "Esusu Overdraft");
        try{
            $amount = $request->amount;
            $esusuid = $request->esusuid;
            if ($amount < 1) {

                return response()->json(['status' => 0, 'message' => "Value must be greater than 0!"]);
            }
            $Esusu_Overdraft_Ledger = get_CR_DR_TXN_Type_Ledger(36, auth()->user()->company_id);
            if($Esusu_Overdraft_Ledger){
                $request->ledger_cr = $Esusu_Overdraft_Ledger->ledger_cr;
                $request->ledger_dr = $Esusu_Overdraft_Ledger->ledger_dr;
            }else{
                return response()->json(['status' => 0, 'message' => 'Transaction failed, set Esusu Overdraft transaction type in ledger settings !']);
            }
            if ($request->pending == null  && !cando('root')) {
                $companyEsusu_type = auth()->user()->company->setting->esusu_type_id;
                if ($companyEsusu_type == 1) {
                    if (PendingEsusu::where(['esusuid' => $request->esusuid, 'pending' => 'overdraft'])->count() > 0) {
                        return response()->json(['status' => 0, 'message' => 'Request Already Sent !']);
                    }
                    if (PendingEsusu::create([
                        'esusuid' => $request->esusuid,
                        'amount' => $request->amount,
                        'pending' => 'overdraft',
                        'created_by' => auth()->user()->id,
                        'ledger_cr'=>$request->ledger_cr,
                        'ledger_dr'=>$request->ledger_dr,
                    ])) {
                        return response()->json(['status' => 1, 'message' => 'Overdraft Sent to Pending Transaction!']);
                    } else {
                        return response()->json(['status' => 0, 'message' => 'Request Failed!']);
                    }
                }
            }

            $backdate = date('Y-m-d h:i:s');
            $esusu = Esusu::find($esusuid);
            if ($amount < $esusu->wallet) {
                return response()->json(['status' => 0, 'message' => "Request amount is contain on Esusu Wallet, Withdraw from wallet!"]);
            }
            if (0 != $esusu->overdraft) {
                return response()->json(['status' => 0, 'message' => "Overdraft already Applied, Clear previous overdraft!"]);
            }
            $overdraft_charge = ($esusu->esusu_type->overdraft_charge / 100) * $amount;
            //return response()->json(['status' => 0, 'message' => $esusu->esusu_type->overdraft_charge." / 100) * $amount = $overdraft_charge"]);
            $esusu->overdraft += $amount;
            $esusu->accum_charge =  $overdraft_charge;
            $wb_id = $esusu->wb_account_detail_id;
            $walletfinder = wb_account_details::find($wb_id);
            $balanceBefore = $walletfinder->user_wallet->balance;
            $balanceAfter = $balanceBefore + ($amount);
            $data_out = [
                "balance_before" => $balanceBefore,
                "amount" => $amount,
                "description" => "Credit from Esusu Overdraft",
                "account_id" => $wb_id,
                "branch_id" => $walletfinder->user->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" => 36, //debit 7
                "credit_debit_type_id" => 1, //debit 2
                "transaction_remarks_id" => 1, //success 2
                "balance_after" => $balanceAfter,
                "created_date"  => $backdate,
                "created_by" => auth()->user()->id,
                'ledger_cr'=>$request->ledger_cr,
                'ledger_dr'=>$request->ledger_dr,
            ];
            $balanceAfterx = ($balanceAfter );
            $transaction_credit_balance = transactions::create($data_out);
            if ($esusu->save()) {
                if (user_wallet::find($walletfinder->user_wallet->id)->update(['balance' => $balanceAfter])) {
                    $esusu = Esusu::find($esusuid);
                    $transaction_credit_balance->transaction_remarks_id = 2;
                    $transaction_credit_balance->save();
                    $BranchWallet =BranchWallet::where('branch_id',$walletfinder->user->branch_id)->first();
                    $BranchWallet->balance += $amount;
                    $BranchWallet->save();
                    $smsstate = sendSMS(['txnid' => $transaction_credit_balance->id, 'userid' => $wb_id], "Credit Amt:$amount Desc: Esusu Overdraft Avail Bal:$balanceAfterx Date:$backdate Ref:$transaction_credit_balance->transaction_reference", false, true, 'Esusu Overdraft Credit');
                    return response()->json(["status" => 1, "message" => "Esusu Overdraft Successful!"]);
                }
            }
        }catch(Exception $e) {
            $message = 'Message: ' .$e->getMessage().$e->getLine();
            return response()->json(["status" => 0, "message" => "$message!"]);
        }

    }
    public function overdraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esusuid' => 'required',
            'amount' => 'required',
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
        return $this->overdraft_script($request);
    }
    public function reset_script($request)
    {
        \logInfo('Esusu Reset ', "Esusu Reset");

        //&& !cando('root')
        if ($request->pending == null  && !cando('root')) {
            $companyEsusu_type = auth()->user()->company->setting->esusu_type_id;

            if ($companyEsusu_type == 1) {
                if (PendingEsusu::where(['esusuid' => $request->esusuid, 'pending' => 'reset'])->count() > 0) {
                    return response()->json(['status' => 0, 'message' => 'Request Already Sent !']);
                }
                if (PendingEsusu::create([
                    'esusuid' => $request->esusuid,
                    'reset_rate' => $request->reset_rate,
                    'reset_date' => $request->reset_date,
                    'pending' => 'reset',
                    'created_by' => auth()->user()->id
                ])) {
                    return response()->json(['status' => 1, 'message' => 'Esusu Reset Request Pending!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Request Failed!']);
                }
            }
        }
        $esusuid = $request->esusuid;
        $esusu = Esusu::find($esusuid);
        $esusu->commence_date = $request->reset_date;
        $esusu->rate = $request->reset_rate;
        if ($esusu->save()) {
            return response()->json(['status' => 1, 'message' => 'Esusu Account Reset Successful!']);
        } else {
            return response()->json(['status' => 0, 'message' => 'Untraced error, try  again!']);
        }
    }
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esusuid' => 'required',
            'reset_rate' => 'required',
            'reset_date' => 'required',
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
        return $this->reset_script($request);
    }
    public function pending($company_name)
    {
        //
        if (!cando('manage-esusu|root')) return  abort(403);

        $data = [
            'category_name' => 'datatable',
            'page_name' => 'esusu',
            'page_menu' => 'pending_esusu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'branch' => branch::has('author')->where('company_id', auth()->user()->company_id)->get(),

            'alt_menu' => 0,

        ];
        return view('company.esusu.esusu_pending')->with($data);
    }
    //EsusuTXN

    public function display_pending(Request $request)
    {
        if (!cando('manage-esusu|root')) return abort(403);
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'pending_esusus.pending',
            1 => 'pending_esusus.pending',
            2 => 'pending_esusus.pending',
            3 => 'pending_esusus.pending',
            4 => 'pending_esusus.pending',
            5 => 'pending_esusus.pending',
            6 => 'pending_esusus.pending',
            7 => 'pending_esusus.pending',
            8 => 'pending_esusus.pending',
            9 => 'pending_esusus.created_at',
        );
        //
        $query = PendingEsusu::join('esusus', 'esusus.id', '=', 'pending_esusus.esusuid')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'esusus.wb_account_detail_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            //  ->join('users', 'users.id', '=', 'pending_esusus.created_by')
            ->join('esusu_types', 'esusu_types.id', '=', 'esusus.esusu_type_id')
            ->where(['users.company_id' => auth()->user()->company_id, 'esusus.status_id' => 1])
            ->select('pending_esusus.*', 'esusus.rate', 'users.first_name', 'users.last_name', 'esusu_types.esusu_type', 'wb_account_details.virtual_accnum');
        $is_root = cando('root');
        if (!$is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        // echo now();
        if (!empty($filter)) {
            $query->where('pending_esusus.pending', 'like', '%' . $filter . '%');
        }
        if ($request->staff != null && $request->staff != 'null') {
            $staffid = User::where(['uuid' => $request->staff, 'company_id' => auth()->user()->company_id])->first();
            $query->where('pending_esusus.created_by', $staffid->id);
            if ($staffid) {
                $query->where('pending_esusus.created_by', $staffid->id);
            } else {
                $query->where('pending_esusus.created_by', 0);
            }
        }
        $query->where('pending_esusus.status_id', 1);
        $recordsTotal = $query->count();
        // $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy('id', $order[0]['dir'])
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
            $btn = '<input type="checkbox" name="pendingid[]" class="checkSingle" value="' . $infoData->id . '">';
            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                number_format($infoData->rate, 2),
                $infoData->number,
                number_format($infoData->amount, 2),
                $infoData->wallet_to_main,
                $infoData->withdraw_to_wallet,
                number_format($infoData->reset_rate, 2),
                $infoData->reset_date,
                $infoData->pending,
                $created_by->first_name . ' ' . $created_by->last_name,
                $btn,
            ];
        }
        return $json;
    }
    public function post_pending_esusu(Request $request)
    {
        if (!cando('manage-esusu|root')) return response()->json(['status' => $status, 'message' => "Total of $num_success Approved and $num_fail failed! $failmsg"]);

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
        $penidarray = $request->pendingid;
        $status = 0;
        $num_success = 0;
        $failmsg = '';
        $num_fail = 0;
        for ($i = 0; $i < count($penidarray); $i++) {
            $penentry = PendingEsusu::findorfail($penidarray[$i]);
            $created_by = User::where(['id' => $penentry->created_by, 'company_id' => auth()->user()->company_id])->first();
            if ($created_by) {
                if ($request->action == 1) {
                    if ($penentry->pending == 'overdraft') {
                        $res = $this->overdraft_script($penentry);
                    } else if ($penentry->pending == 'reset') {
                        $res =  $this->reset_script($penentry);
                    } else if ($penentry->pending == 'topup') {
                        $res = $this->topup_script($penentry);
                    } else if ($penentry->pending == 'withdraw') {
                        $res = $this->withdraw_script($penentry);
                    }
                    $responseData = json_decode($res->getContent());

                    if($responseData->status != null){
                        $response = $responseData->status;
                        if ($response == 1) {
                            $num_success++;
                            $status = 1;
                            $penentry->delete();
                        } else {
                            $failmsg .= "<br> Error:" . json_encode($responseData->message);
                            $num_fail++;
                        }
                    }else{
                        return $res;
                    }
                }
                if ($request->action == 2) {
                    $penentry->delete();
                }
            }
        }
        return response()->json(['status' => $status, 'message' => "Total of $num_success Approved and $num_fail failed! $failmsg"]);
    }

}
