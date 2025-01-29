<?php

namespace App\Http\Controllers;

use App\Models\DomAccount;
use App\Models\User;
use App\Models\wb_account_details;
use App\Http\Requests\StoreDomAccountRequest;
use App\Http\Requests\UpdateDomAccountRequest;
use Illuminate\Http\Request;
use DB;
class DomAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware("custom_permission:manage-dom-account|root");
    }

    public function userDataSource(Request $request) {

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'dom_accounts.id',
            1 => 'dom_accounts.id',
            2 => 'dom_accounts.id',
            3 => 'dom_accounts.id',
            4 => 'dom_accounts.id',
            5 => 'dom_accounts.id',
            6 => 'dom_accounts.id',
            7 => 'dom_accounts.id',
            8 => 'dom_accounts.id',

        );
        $query = DomAccount::join('wb_account_details', 'wb_account_details.id', '=', 'dom_accounts.wb_account_detail_id')
        ->join('users', 'wb_account_details.user_id', '=', 'users.id')
        ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
        ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
        ->select('users.*',DB::raw('dom_accounts.uuid as domid'),'wb_account_details.virtual_accnum',DB::raw('wb_account_details.id as accountID'),DB::raw('savings_products.savings_product as accountsavingsp'),DB::raw('round(SUM(user_wallets.balance),1) as balance'))
        ->where(['users.user_type_id'=>'1','users.company_id'=>auth()->user()->company_id,'dom_accounts.status'=>0])->groupBy('users.id');
        if(!cando('root|manage-dom-account')){
            $query =  $query->where('users.account_officer_id',auth()->user()->id);
        }

        if (!empty($filter)) {
            $query->where(function($query2) use ($filter){
            $query2->orwhere('users.first_name', 'like', '%'.$filter.'%');
            $query2->orWhere('users.phone', 'like', '%' .$filter. '%');
            $query2->orWhere('savings_products.savings_product', 'like', '%' .$filter. '%');
            $query2->orWhere('user_wallets.balance', 'like', '%' .$filter. '%');
            $query2->orWhere('wb_account_details.virtual_accnum', 'like', '%' .$filter. '%');
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
        $loop =0;

        foreach ($info as $infoData) {
            $btnlink = $infoData->domid;
            $shortname =Auth()->user()->company->short_name;
            $btndom ="<button onclick=unblock('/company/$shortname/customer/unblock-dom/$btnlink') target='_blank' class='btn btn-outline-success btn-sm' ";
            $btndom .=" >Unblock</button>";
            $loop++;
            $json['data'][] = [
                '<td class="sorting_1"><input type="checkbox" name="Userid[]" class="checkSingle" value="'.$btnlink.'"> '.$loop.'</td>',
                $infoData->first_name,
                $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->phone,
                $infoData->accountsavingsp,
                $infoData->balance,
                $btndom,
            ];
        }
        return $json;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function unblock($company,$id){
        $dom = DomAccount::where(['uuid'=>$id,'status'=>0]);
        if($dom->count() > 0){
          $domaccount =   $dom->first();
          $wb_account_detail = wb_account_details::where('id',$domaccount->wb_account_detail_id)->first();
          $wb_account_details_user =User::find($wb_account_detail->user_id);
          if(cando('root|manage-dom-account') == false && $wb_account_details_user->account_officer_id != auth()->user()->id){
            return response(json_encode(['status'=>0,'message'=>'Restricted Access!']));
          }
          if($domaccount->update(['status'=>1,'created_by'=>auth()->user()->id])){

              $wb_account_details_user->status_id = 1;
              $wb_account_details_user->save();
            return response(json_encode(['status'=>1,'message'=>'Account Unblocked! Perform transaction before the end of the day']));
          }else{
            return response(json_encode(['status'=>0,'message'=>'Account Unblocked Failed!']));

          }
        }else{
            return response(json_encode(['status'=>0,'message'=>'Account not Block!']));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreDomAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function unblockAll(Request $request)
    {
        $message ='';
        if(isset($request->Userid)){


            for($i=0;$i<count($request->Userid);$i++){
                $id =$request->Userid[$i];
                $dom = DomAccount::where(['uuid'=>$id,'status'=>0]);
                $domaccount =   $dom->first();
                $wb_account_detail = wb_account_details::where('id',$domaccount->wb_account_detail_id)->first();
                if($wb_account_detail){
                    return json_encode(['status'=>0,'message'=>'error']);
                }
                $wb_account_details_user =User::find($wb_account_detail->user_id);
                if($wb_account_details_user->company_id != auth()->user()->company_id){
                    $message .= 'Restricted Access!<br>';
                    return json_encode(['status'=>0,'message'=>$message]);
                }
                if(cando('root|manage-dom-account') == false && $wb_account_details_user->account_officer_id != auth()->user()->id){
                    // return response(json_encode(['status'=>0,'message'=>'Restricted Access!']));
                    $message .='Restricted Access!<br>';
                    return json_encode(['status'=>0,'message'=>$message]);
                }
                $dom->update(['status'=>1,'created_by'=>auth()->user()->id]);
                $message .="Batch Unblock Successful <br>";
                $wb_account_details_user->status_id = 1;
                $wb_account_details_user->save();

            }
            return json_encode(['status'=>1,'message'=>$message]);
        }
        return json_encode(['status'=>0,'message'=>"Error processing request!"]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DomAccount  $domAccount
     * @return \Illuminate\Http\Response
     */
    public function show(DomAccount $domAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DomAccount  $domAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(DomAccount $domAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDomAccountRequest  $request
     * @param  \App\Models\DomAccount  $domAccount
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDomAccountRequest $request, DomAccount $domAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DomAccount  $domAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(DomAccount $domAccount)
    {
        //
    }
}
