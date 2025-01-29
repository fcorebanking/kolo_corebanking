<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentRequest;
use App\Models\User;
use App\Models\transactions;
use DB;

class EsusuMobileAppController extends Controller
{
    public function viewAgent(){
        if (!cando('root')) return abort(403);
        $data = [
            'category_name' => 'mobile_esusu',
            'page_name' => 'manage_mobile_esusu_product',
            'page_menu' => 'manage_mobile_esusu_product',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.esusu.agent.index')->with($data);
    }
    public function esusu(){
        if (!cando('root')) return abort(403);
        $data = [
            'category_name' => 'mobile_esusu',
            'page_name' => 'manage_mobile_esusu',
            'page_menu' => 'manage_mobile_esusu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.esusu.agent.esusu')->with($data);
    }
    public function withdraw(){
        if (!cando('root')) return abort(403);
        $data = [
            'category_name' => 'mobile_esusu',
            'page_name' => 'manage_mobile_esusu_withdraw',
            'page_menu'=>'manage_mobile_esusu_withdraw',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.esusu.agent.view_withdrawal_request')->with($data);
    }
    public function approveAgent($id){
        if (!cando('root')) return abort(403);

        if(User::where('id',$id)->update(['is_agent'=>1])){
            return response()->json(['status'=>1,'message'=>'Agent Approved!']);
        }else{
            return response()->json(['status'=>0,'message'=>'Failed to approved agent!']);
        }
    }
    public function disableAgent($id){
        if (!cando('root')) return abort(403);

        if(User::where('id',$id)->update(['is_agent'=>3])){
            return response()->json(['status'=>1,'message'=>'Agent Disabled!']);
        }else{
            return response()->json(['status'=>0,'message'=>'Failed to disabled agent!']);
        }
    }
    public function display_esusu(Request $request)
    {
        if (!cando('root')) return abort(403);
        if($request->type == null ){
            $request->type  = 'pending-withdraw';
        }
        // die(var_dump($_GET));
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = 1000000; //$request->query('length',1000000);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'transactions.id',
            1 => 'transactions.created_date',
            2 => 'users.first_name',
            3 => 'wb_account_details.virtual_accnum',
            4 => 'transactions.description',
            5 => 'transactions.transaction_type_id',
            6 => 'transactions.transaction_reference',
            7 => 'transactions.amount',
            8 => 'transactions.amount',
            9 => 'transactions.balance_before',
            10 => 'transactions.balance_after',
            11 => 'transactions.created_at',
        );
        $query = transactions::join('wb_account_details', 'transactions.account_id', '=', 'wb_account_details.id')
        ->join('users','users.id', '=','wb_account_details.user_id'  )
        ->join('transaction_remarks', 'transactions.transaction_remarks_id', '=', 'transaction_remarks.id')
        ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
        ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
        ->where(['users.company_id'=>auth()->user()->company_id,'transactions.transaction_status_id'=>1])
        ->select('transactions.*','users.agent_id','users.first_name','users.last_name','users.account_officer_id','credit_debit_types.type','transaction_types.transaction_type','transaction_remarks.transaction_remark','wb_account_details.virtual_accnum');
        // $query =  $query->where(['users.user_type_id'=>'1','users.company_id'=>auth()->user()->company_id,'transactions.transaction_status_id'=>1]);
        // $query = $query->where('users.status_id','=',1);
        // $query = $query->where('users.agent_id','!=',null);

        $query =  $query->where('transactions.parent_transaction_id',null);//
        $is_root = cando('root');
        // if(!$is_root){
        //     $query =$query->where(function($query){
        //         $query->where(['transactions.created_by'=>auth()->user()->id]);
        //     });
        // }
        if($request->type == 'agent-txn' ){
            $query->where(function($query) {
                $query->orwhere('transactions.transaction_type_id',86);
                $query->orwhere('transactions.transaction_type_id',87);
            });
        }else if($request->type == 'approved-withdraw' ){
            $query =$query->where(function($query) {
                $query->where(['transactions.transaction_type_id'=>88,'transactions.transaction_remarks_id'=>2]);
            });
        }else if($request->type == 'pending-withdraw' ){
            $query  = $query->where(['transactions.transaction_type_id'=>88,'transactions.transaction_remarks_id'=>1]);
        }

        if($request->from != null && $request->to != null){
            $query =  $query->whereDate('transactions.created_date', '<=',add_day_to_date(0,$request->to) )
            ->whereDate('transactions.created_date', '>=', add_day_to_date(0,$request->from));
        }
        if (!empty($filter)) {
            $query->where(function($query) use ($filter){
                $query->orwhere('users.first_name', 'like', '%'.$filter.'%');
                $query->orWhere('users.phone', 'like', '%' .$filter. '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' .$filter. '%');
                $query->orWhere('transactions.transaction_reference', 'like', '%' .$filter. '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' .$filter. '%');
            });
        }

        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        //          ->take($length)
        //         ->skip($start);
        // echo $recordsTotal;

        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();
        $sn =0;
        $deposit = 0;
        $withdraw = 0;
        $difference = 0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
        $created=User::where('id', $infoData->created_by)->first();
        $officer= ($infoData->agent_id != null )? User::where('id', $infoData->agent_id)->first():($infoData->account_officer_id?User::where('id', $infoData->account_officer_id )->first():'---');
        if($infoData->credit_debit_type_id == 2){
        $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26)? "<button class='btn btn-warning'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Debit</button>":'<span class="btn btn-danger">Reversed</span>';
        }else{
        $btnreverse = ($infoData->transaction_type_id != 25 && $infoData->transaction_type_id != 26)? "<button class='btn btn-secondary'  onclick=reverse('/company/reverse-statement/$infoData->transaction_reference')> Reverse Credit</button>":'<span class="btn btn-danger">Reversed</span>';
        }
        $badge = ($infoData->credit_debit_type_id == 1)?"success":"danger";
        $deposit +=($infoData->credit_debit_type_id == 1)? $infoData->amount :0;
        $withdraw +=($infoData->credit_debit_type_id == 2)? $infoData->amount :0;
        $btn ='';


        $json['data'][] = [
            $sn,
            $infoData->first_name.' '.$infoData->last_name,
            $infoData->virtual_accnum,
            $infoData->description,
            "<span class='badge badge-".$badge."'>".$infoData->type."</span>",
            "<a class='btn btn-info' target='_blank' href='/transaction-receipt/$infoData->transaction_reference'> $infoData->transaction_reference</a>",
            @$officer->first_name.' '.@$officer->last_name,//
            number_format($infoData->amount,2),
            number_format($infoData->balance_before,2).auth()->user()->company->currency->symbol,
            number_format($infoData->balance_after,2).auth()->user()->company->currency->symbol,
            $created->first_name.' '.$created->last_name,
            $infoData->created_at,

        ];
        }
        $difference = $deposit -$withdraw;
        $json['data'][] = [
            '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw </h5></span>',
            '<span class="badge badge-warning "><h5 class="text-light">Total Withdraw ='.$withdraw.auth()->user()->company->currency->symbol.'</h5></span>',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '','',
            '',
            '',
        ];
        $json['data'][] = [
            '<span class="badge badge-warning "><h5 class="text-light">Total Deposit </h5></span>',
            '<span class="badge badge-info"><h5 class="text-light">Total Deposit='.$deposit.auth()->user()->company->currency->symbol.'</h5></span></h4>',
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
            '<span class="badge badge-success"><h5 class="text-light">Difference ='.$difference.auth()->user()->company->currency->symbol.'</h5></span></h4>',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '','',
            '',
            '',
        ];

            return $json;
    }
}
