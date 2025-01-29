<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BranchValut;
use App\Models\CompanyValut;
use App\Models\User;
use App\Models\StaffWallet;
use App\Models\CompanyWalletTXN;
use App\Models\branch;
use App\Models\transactions;

use App\Models\BranchWallet;
use Illuminate\Support\Facades\Crypt;

class CompanyValutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {

        //$this->middleware("custom_permission:approve-reject-vourcher-permission|manage-vault|root");

    }
    public function index()
    {
         if (!cando('manage-vault|root')) return   abort(403);

        $vault = CompanyValut::where([ 'company_id' => auth()->user()->company_id])->get();
        // 'branch' => branch::has('author')->where('company_id', auth()->user()->company_id)->where('status_id','!=',$branch_subscription_status_id)->get(),
        // $branch_subscription_status_id =14;
        $data = [
            'category_name' => 'vault',
            'page_name' => 'manage_vault',
            'page_menu' => 'manage_vault',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'vault'=>$vault,
            'company_vault'=>CompanyValut::where('company_id',auth()->user()->company_id)->get(),
            'alt_menu' => 0,
           'staff'=> User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->get(),
            'branchdata'=>auth()->user()->company->branch
            ,
        ];
        return view('company.vault.main')->with($data);
    }
    public function transferVoucher()
    {
        //if (!cando('manage-esusu|root')) return   abort(403);

        $vault = CompanyValut::where([ 'company_id' => auth()->user()->company_id])->get();
        $data = [
            'category_name' => 'vault',
            'page_name' => 'submit_vault',
            'page_menu' => 'submit_vault',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'vault'=>$vault,
            'company_vault'=>CompanyValut::where('company_id',auth()->user()->company_id)->get(),
            'alt_menu' => 0,
           'staff'=> User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2])->get(),
            'branchdata'=>auth()->user()->company->branch
        ];
        return view('company.vault.voucher')->with($data);
    }
    public function send_voucher(Request $request){

        if($request->amount == null || $request->amount < 100){
            return response()->json(['status'=>0,'message'=>"Amount field Required and must be greater than 100!"]);
        }
        $amount = $request->amount;
        $StaffWallet = StaffWallet::where('user_id',auth()->user()->id)->first();
        $vault = 'Staff_wallet_vault';
        if($StaffWallet){
            if($StaffWallet->balance >= $request->amount){
                $create_vault = CompanyValut::where(['vault' =>$vault, 'company_id' => auth()->user()->company_id]);
                $cout_exist = $create_vault->count();
                if($cout_exist > 0){
                    $create_vault = $create_vault->first();
                }else{
                    $acno = time();
                    while (CompanyValut::where(['accountno' => $acno])->count() > 0) {
                        $acno = $acno++;
                    }
                    $create_vault = CompanyValut::create(['accountno' => $acno, 'company_id' => auth()->user()->company_id, 'vault' => $vault, 'value' =>0, 'deleteable' => 0, 'created_by' => auth()->user()->id]);
                }
                $StaffWallet->balance -= $amount;
                $StaffWallet->save();
                BranchValut::create(['branch_id'=>auth()->user()->branch_id, 'company_valut_id'=>$create_vault->id,'value'=>$amount,'created_by'=>auth()->user()->id,'status'=>2]);
                return response()->json(['status'=>1,'message'=>"Your request has been sent for Approval!"]);

            }else{
                return response()->json(['status'=>0,'message'=>"Insufficient Balance!"]);

            }
        }else{
            return response()->json(['status'=>0,'message'=>"Fatal Error!"]);

        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function transfer_wallet(Request $request)
    {
        if (!cando('manage-vault|root')) return   abort(403);

        if($request->amount == null || $request->source_wallet == null || $request->destination_wallet == null){
            return response()->json(['status'=>0,'message'=>"All field Required!"]);

        }
        $amount = $request->amount;
        $source = $request->source_wallet;
        $destination = $request->destination_wallet;
        if($amount < 1){
            return response()->json(['status'=>0,'message'=>"Value must be above zero!"]);
        }
        if($source == $destination){
            return response()->json(['status'=>0,'message'=>"You cannot deposit on same wallet!"]);
        }
        $check_tocreate = CompanyValut::where(['id'=>$source,'company_id'=>auth()->user()->company_id]);
        $check_to_destination = CompanyValut::where(['id'=>$destination,'company_id'=>auth()->user()->company_id]);
        $cout_exist = $check_tocreate->count();
        $cout_desti_exist = $check_to_destination->count();
        $vault_branch_id = auth()->user()->branch_id;
        if($cout_exist > 0 && $cout_desti_exist > 0){
            $check_tocreate = $check_tocreate->first();
            if($check_tocreate->value < $amount){
                return response()->json(['status'=>0,'message'=>"Insuficient Balance!"]);
            }
            $check_tocreate_vault =$check_tocreate->vault;
            $check_to_destination = $check_to_destination->first();
            $check_to_destination_vault = $check_to_destination->vault;
            logInfo("Transfer $amount vault cash from $check_tocreate_vault to $check_to_destination_vault", "Transfer to $check_tocreate_vault vault");
            $amount_to_add =$check_tocreate->value - $amount;
            $update_vault =$check_tocreate->update(['value'=>$amount_to_add]);
            BranchValut::create(['branch_id'=>$vault_branch_id, 'company_valut_id'=>$check_tocreate->id,'value'=>$amount,'created_by'=>auth()->user()->id]);
            $cash = $check_to_destination->value + $amount;
            $check_to_destination->update(['value'=>$cash]);
            BranchValut::create(['branch_id'=>$vault_branch_id, 'company_valut_id'=>$check_to_destination->id,'value'=>$amount,'created_by'=>auth()->user()->id]);

            return response()->json(['status'=>1,'message'=>"Wallet Transfer Successfully!"]);
        }else{
            return response()->json(['status'=>0,'message'=>"Invalid ID!"]);
        }

    }
    public function deposit_wallet(Request $request)
    {
        if (!cando('manage-vault|root')) return   abort(403);

        $amount = $request->amount;
        $source = $request->wallet;
        $check_tocreate = CompanyValut::where(['id'=>$source,'company_id'=>auth()->user()->company_id])->first();
        if($check_tocreate){
            fundVault(['type'=>$check_tocreate->vault,'value'=>$amount,'company_id'=>auth()->user()->company_id,'branch_id'=>auth()->user()->branch_id,'transaction_id'=>null]);
            return response()->json(['status'=>1,'message'=>"Wallet Deposit Successfully!"]);

        } else{
            return response()->json(['status'=>0,'message'=>"Wallet not found!"]);

        }
    }

    public function staff_wallet(Request $request)
    {
        if (!cando('manage-vault|root|esusu-schedule-agent')) return   abort(403);

        $amount = $request->amount;
        if($amount < 100){
            return response()->json(['status'=>0,'message'=>"Amount must be above 100!"]);

        }
        $staff = $request->staff;

        $staff_credit_option =  $request->staff_credit_option;
        $check_tocreate = User::where(['uuid'=>$staff,'company_id'=>auth()->user()->company_id])->first();
        if($check_tocreate){
            $staff_wallet = $check_tocreate->wallet;
            if($staff_credit_option == 'debit'){
                if(is_numeric($staff_wallet->balance)){
                    if($staff_wallet->balance >= $amount){
                        $balance =  $staff_wallet->balance - $amount;
                    }else{
                        return response()->json(['status'=>0,'message'=>"Insufficient Fund!"]);
                    }
                }
            }else{
                if(is_numeric($staff_wallet->balance)){
                    $balance = $amount + $staff_wallet->balance;
                }
            }
            transactions::create(
                [
                    "balance_before" =>  $staff_wallet->balance,
                    "amount" => $amount,
                    "description" => "Wallet $request->staff_credit_option by admin",
                    "branch_id" => $check_tocreate->branch_id,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 45, //teller balance
                    "transaction_status_id" => 1,
                    "credit_debit_type_id" => ($staff_credit_option==='debit')?2:1, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after" => $balance,
                    "created_date"  => date('Y-m-d'),
                    "created_by" => $check_tocreate->id,
                    "modified_by" =>auth()->user()->id,
                    "ledger_cr" => (systemLedgerAccount('Staff Wallet', auth()->user()->company_id))->id, //
                    "ledger_dr" =>  (systemLedgerAccount('Customer Saving Deposit', auth()->user()->company_id))->id,

                ]
                );
            StaffWallet::find($staff_wallet->id)->update(['balance'=>$balance]);
            CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'staff_id'=>$check_tocreate->id,'type'=>$staff_credit_option, 'amount'=>$amount]);
            if($staff_credit_option == 'debit'){
                return response()->json(['status'=>1,'message'=>"Staff Wallet Debited Successfully!"]);
            }else{
                return response()->json(['status'=>1,'message'=>"Staff Wallet Funded Successfully!"]);

            }

        } else{
            return response()->json(['status'=>0,'message'=>"Wallet not found!"]);

        }
    }
    public function staff_comission(Request $request){
        try{
        if (!cando('manage-vault|root')) return   abort(403);
        $auth = auth()->user();
        $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger(20,$auth->company_id);
        if(!$get_ledger_setting_Commission){
            return ["status" => 0, "message" => "Transaction failed, Set staff commission transaction type ledger on ledger settings!"];
        }
        $staff = User::where(['user_type_id'=>2,'uuid'=>$request->staff])->first();
        if(!$staff){
            return ["status" => 0, "message" => "Staff error, contact admin!"];
        }
        $tellerbalance = $staff->wallet->commission;
        if(($request->staff_credit_option !="credit")){
            if($tellerbalance <  $request->amount){
                return ['status' => 0,'msg'=>'Insufficient Balance'];
            }
        }
        $tellerbalance = ($request->staff_credit_option=="credit")? $tellerbalance +$request->amount : $tellerbalance - $request->amount;
        transactions::create(
            [
                "balance_before" => $staff->wallet->commission,
                "amount" => $request->amount,
                "description" => "Staff $request->staff_credit_option Commission by admin",
                "branch_id" => $staff->branch_id,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" => 20, //teller_commission
                "transaction_status_id" => 1,
                "credit_debit_type_id" => ($request->staff_credit_option=="credit")?1:2, //debit 2
                "transaction_remarks_id" => 2, //success 2
                "balance_after" => $tellerbalance,
                "created_date"  => date('Y-m-d'),
                "created_by" => $auth->id,
                "ledger_cr"=>$get_ledger_setting_Commission->ledger_cr, //
                "ledger_dr"=>$get_ledger_setting_Commission->ledger_dr,
            ]
        );
        StaffWallet::where(['id' => $staff->wallet->id])->update(['commission' => $tellerbalance]);
        return response()->json(['status'=>1,'message'=>"Staff commission wallet ".$request->staff_credit_option."ed successfully!"]);
    }catch(Exception $e){
        return response()->json(['status'=>0,'message'=>$e->getMessage()]);

    }
    }

    public function branch_wallet(Request $request)
    {
        if (!cando('manage-vault|root')) return   abort(403);

        $amount = $request->amount;
        $branch = $request->branch;
        $check_tocreate = branch::where(['id'=>$branch,'company_id'=>auth()->user()->company_id])->first();
        if($check_tocreate){
            $branch_wallet = $check_tocreate->wallet;
            $balance = $amount + $branch_wallet->balance;
            BranchWallet::find($branch_wallet->id)->update(['balance'=>$balance]);
            CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'branch_id'=>$check_tocreate->id, 'amount'=>$amount]);
            return response()->json(['status'=>1,'message'=>"Branch Wallet Funded Successfully!"]);
        } else{
            return response()->json(['status'=>0,'message'=>"Wallet not found!"]);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyValut  $companyValut
     * @return \Illuminate\Http\Response
     */
    public function create_vault(Request $request)
    {
        if (!cando('manage-vault|root')) return   abort(403);

        $vault_name = $request->new_vault;
        $count_vault = CompanyValut::where(['vault'=>$vault_name,'company_id'=>auth()->user()->company_id])->count();
        if($count_vault == 0){

           $acno = time();
           while(CompanyValut::where(['accountno'=>$acno])->count() > 0){
                $acno = $acno++;
           }
           $CompanyValut = CompanyValut::create(['accountno'=>$acno,'vault'=>$vault_name, 'value'=>0 ,'deleteable'=>1,'created_by'=>auth()->user()->id,'company_id'=>auth()->user()->company_id]);
           if($CompanyValut){
            return response()->json(['status'=>1,'message'=>"Company Vault Created Successfully!"]);

           }else{
            return response()->json(['status'=>0,'message'=>"Company Vault Creation Failed!"]);

           }
        }else{
            return response()->json(['status'=>0,'message'=>"Company Vault Aready Exist!"]);

        }

    }
    public function display_vault_wallets(Request $request)
    {
        if (!cando('manage-vault|root')) return   abort(403);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'id',
            1 => 'accountno',
            2 => 'vault',
            3 => 'created_at',

        );
        $query = CompanyValut::where([ 'company_id' => auth()->user()->company_id]);

        if (!empty($filter)) {
            $query->where('vault', 'like', '%' . $filter . '%');
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
            $sn++;
            $btnlink = $infoData->id;
                $json['data'][] = [
                $sn,
                $infoData->accountno,
                $infoData->vault ,
                $infoData->value,
                $infoData->author->first_name.' '.$infoData->author->last_name,

            ];
        }


        return $json;
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyValut  $companyValut
     * @return \Illuminate\Http\Response
     */
    public function history(Request $request)
    {
        $isroot =cando('root|approve-reject-vourcher-permission');
        $isroot_only =cando('root');
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'desc'));
        $filter = $search['value'];
        //$status = $request->status;
        $sortColumns = array( //'branch_id', 'company_valut_id', 'transaction_id' ,'value'
            0 => 'branch_valuts.id',
            1 => 'users.first_name',
            2 => 'staff_wallet.accountno',
            3 => 'staff_wallet.balance',
            4 => 'branch_valuts.created_at',
            5 => 'company_valuts.vault',
            6 => 'branch_valuts.value',
            7 => 'branch_valuts.created_at',
            8 => 'branch_valuts.status',

        );
        $query = BranchValut::join('company_valuts','company_valuts.id','=','branch_valuts.company_valut_id')
        ->join('staff_wallet','staff_wallet.user_id','=','branch_valuts.created_by')
        ->join('users','staff_wallet.user_id','=','users.id')
        ->where([ 'company_valuts.company_id' => auth()->user()->company_id])
        ->select('branch_valuts.*','company_valuts.vault','staff_wallet.balance','staff_wallet.accountno','users.first_name','users.last_name');
        if(!$isroot){
            $query = $query->where(['branch_valuts.created_by'=>auth()->user()->id,'company_valuts.vault'=>'Staff_wallet_vault']);
        }
        if($request->page == 'voucher' && $isroot == 1){
            // if($isroot_only){
                $query = $query->where(['branch_valuts.status'=>2]);
            // }
        }
        if (!empty($filter)) {
            $query->where(function($query) use ($filter){
                $query->orwhere('company_valuts.vault', 'like', '%' . $filter . '%');
                $query->orwhere('staff_wallet.accountno', 'like', '%' . $filter . '%');
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
        // $query->orderBy('branch_valuts.status', 'desc')
            ->take($length)
            ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();
        $sn=0;
        foreach ($info as $infoData) {
            $sn++;
            $btnlink = $infoData->id;
            $id_decrypt = Crypt::encryptString($btnlink);
            $btn ='<div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Action
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item" href="#" onclick=approveVoucherModal("'.$id_decrypt.'","'.$infoData->value.'")>Approve</a>
              <a class="dropdown-item" href="#" onclick=rejectVoucher("'.$id_decrypt.'")>Reject</a>

            </div>
          </div>';
                   $json['data'][] = [
                $sn,
                $infoData->first_name.' '.$infoData->last_name,
                $infoData->accountno,
                $infoData->balance,
                $infoData->branch->branch_name,
                setTitle($infoData->vault),
                (isset($infoData->transactionID))?$infoData->transactionID->transaction_reference:"wba-".$infoData->id,
                $infoData->value,
                ($isroot ==1 && $infoData->status == 2)? $btn : (($infoData->status == 1)?'<span class="badge badge-success">Completed</span>':(($infoData->status == 2)?'<span class="badge badge-warning">Pending</span>':'<span class="badge badge-danger">Rejected</span>')),
            ];

        }


        return $json;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCompanyValutRequest  $request
     * @param  \App\Models\CompanyValut  $companyValut
     * @return \Illuminate\Http\Response
     */
    public function approve_voucher($id,Request $request)
    {

        try{
        if (!cando('approve-reject-vourcher-permission|root')) return   abort(403);

        $id =Crypt::decryptString($id);

       $txn = BranchValut::where('id',$id)->first();
       if(!isset($txn->id)){
        return response()->json(['status'=>0,'message'=>"Wallet issue!"]);
       }
       $staffwallet =StaffWallet::where('user_id',$txn->created_by)->first();
       $amount = $txn->value;

       if($staffwallet->balance < $amount){
            // return response()->json(['status'=>0,'message'=>"No balance in initiator wallet!"]);
       }
       if($txn->status ==1){
        return response()->json(['status'=>0,'message'=>"Request already approved!"]);

       }
    //    $staffwallet->balance -= $amount;
       if($request->selectedaction =="branchvault"){
            $branch = $request->selectedvaultid;
            $check_tocreate = branch::where(['id'=>$branch,'company_id'=>auth()->user()->company_id])->first();
            if($check_tocreate){
                $branch_wallet = $check_tocreate->wallet;
                $balance = $amount + $branch_wallet->balance;
                BranchWallet::find($branch_wallet->id)->update(['balance'=>$balance]);
                // $staffwallet->save();

                CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'branch_id'=>$check_tocreate->id, 'amount'=>$amount]);
                $txn->status = 1;
                $txn->save();
                return response()->json(['status'=>1,'message'=>"Branch Wallet Funded Successfully!"]);
            } else{
                return response()->json(['status'=>0,'message'=>"Wallet not found!"]);

            }
       }
       if($request->selectedaction =="othervault"){
            $check_tocreate = CompanyValut::where(['company_id'=>auth()->user()->company_id,'id'=>$request->selectedvaultid])->first();
            $check_tocreate->value += $amount;
            if($check_tocreate->save()){
                $txn->status = 1;
                $txn->save();
                // $staffwallet->save();

                return response()->json(['status'=>1,'message'=>"$check_tocreate->vault Wallet Funded Successfully!"]);
            }else{
                return response()->json(['status'=>0,'message'=>"Error Occured Try Again!"]);
            }
       }
       if($request->selectedaction =="staffvault"){
            $staff = $request->selectedvaultid;
            $check_tocreate = User::where(['uuid'=>$staff,'company_id'=>auth()->user()->company_id])->first();
            if($check_tocreate){
                $staff_wallet = $check_tocreate->wallet;
                if(is_numeric($staff_wallet->balance)){
                    $balance = $amount + $staff_wallet->balance;
                }
                // $staffwallet->save();
                StaffWallet::find($staff_wallet->id)->update(['balance'=>$balance]);
                CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'staff_id'=>$check_tocreate->id, 'amount'=>$amount]);
                $txn->status = 1;
                $txn->save();
                return response()->json(['status'=>1,'message'=>"Staff Wallet Funded Successfully!"]);
            } else{
                return response()->json(['status'=>0,'message'=>"Wallet not found!"]);

            }
       }
    }catch(\Exception $e){
        return response()->json(['status'=>0,'message'=>$e->getMessage()]);
    }

    }
    public function reject_voucher($id)
    {
        if (!cando('approve-reject-vourcher-permission|root')) return   abort(403);

        $id =Crypt::decryptString($id);
        $txn = BranchValut::where('id',$id)->first();
        $txn->status = 3;
        $StaffWallet = StaffWallet::where('user_id',$txn->created_by)->first();
        if($txn->save()){
            $StaffWallet->balance += $txn->value;
            $StaffWallet->save();
            return response()->json(['status'=>1,'message'=>"Fund Reversed!"]);
        }else{
            return response()->json(['status'=>0,'message'=>"Error Occured Try Again!"]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyValut  $companyValut
     * @return \Illuminate\Http\Response
     */
    public function show_vaults(Request $request)
    {

        $isroot =cando('manage-vault|root');

        if($isroot){
            $view_vault = CompanyValut::where(['company_id'=>auth()->user()->company_id])->get();
        }else{
                  $staff_vault = StaffWallet::where('user_id',auth()->user()->id)->first();

        }
        if(isset($view_vault))
            foreach($view_vault as $view_vault_data){
                $fname = (isset($view_vault_data->author->first_name))?$view_vault_data->author->first_name:'';
                echo '<div class="cpanel cpanel-blue" style="margin:2px;">
                <div class="icon-part">
                <i class="fa fa-money" aria-hidden="true"></i><br>
                <small>'.setTitle($view_vault_data->vault).'</small>
                <p class="text-light">'.auth()->user()->company->currency->symbol.' '.$view_vault_data->value.'</p>
                </div>
                <div class="card-content-part">
                '.$view_vault_data->accountno.' by '.$fname.'
                </div>
            </div>';
            }
        else
            echo '<div class="cpanel cpanel-blue"  style="margin:2px;">
            <div class="icon-part">
            <i class="fa fa-money" aria-hidden="true"></i><br>
            <small> Staff Wallet</small>
            <p class="text-light">'.auth()->user()->company->currency->symbol.' '.$staff_vault->balance.'</p>
            </div>
            <div class="card-content-part">
            '.$staff_vault->accountno.'
            </div>
        </div>';

        //return response()->json($view_vault);
    }
}
