<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ScheduleEsusuProduct;
use App\Models\ScheduleEsusuRepayment;
use App\Models\ScheduleEsusu;
use App\Models\User;
use App\Http\Controllers\CompanyValutController;
use Illuminate\Support\Facades\DB;

use App\Models\wb_account_details;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class ScheduleEsusuController extends Controller
{
    public function index()
    {


        if (!cando('root|manage-foodstash')) {
            abort(403);
            return 0;
        }
        $user =  User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2,'status_id'=>1])->select('id','first_name', 'last_name', 'email', 'uuid', 'phone', 'profile_pic', 'username', 'status_id', 'branch_id')->get(); //->whereDate('start_date','>=',date('Y-m-d'))

        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html55',
            'page_menu' => 'schedule_esusu_operation',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'staff'=>$user,
            'product'=>ScheduleEsusuProduct::where(['company_id'=>auth()->user()->company_id,'status'=>'active'])->whereDate('start_date','>=',date('Y-m-d'))->get(),
        ];
        // $pageName = 'html5';
        return view('company.esusu.schedule.esusu_operation')->with($data);
    }
    public function store(Request $request)
    {
        if (!cando('root|manage-foodstash')) {
            abort(403);
            return 0;
        }
        try{
        $validator = Validator::make($request->all(), [
            'account' => 'required',
            'rate' => 'required',
            'start_date' => 'required',
            'esusu_product' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = ['message' => $errors,'status' => 0];
            return response()->json($datax);
        }
        $user_id = $request->account;
        // if(date($request->start_date) < date('Y-m-d')){
        //     return response()->json(['status'=>0,'message'=>'Start date must be greater or equal to current date']);
        // }
        $scheduleEsusuProductCheck = ScheduleEsusuProduct::where('id',$request->esusu_product)->first();
        if(!$scheduleEsusuProductCheck){
            return response()->json(['status'=>0,'message'=>'Product fails!']);
        }

        //'minimun','maximal'
        if($scheduleEsusuProductCheck->minimun > $request->rate || $scheduleEsusuProductCheck->maximal < $request->rate){
            return response()->json(['status'=>0,'message'=>"Range is between $scheduleEsusuProductCheck->minimun - $scheduleEsusuProductCheck->maximal"]);
        }
        if($request->start_date > $scheduleEsusuProductCheck->start_date){
            return response()->json(['message' => "Pick a date behind or on the product startdate which is $scheduleEsusuProductCheck->start_date!",'status' => 0]);
        }

        $user =User::where(['uuid'=>$user_id,'company_id'=>auth()->user()->company_id,'status_id'=>1])->first();
        if(!$user){
            return response()->json(['status'=>0,'message'=>"User not found"]);
        }
        $scheduleEsusuCheck = ScheduleEsusu::where(['schedule_esusu_product_id'=> $scheduleEsusuProductCheck->id,'user_id'=>$user->id])->first();
        if($scheduleEsusuCheck){
            return response()->json(['status'=>0,'message'=>'Already have a running plan!']);
        }
        $useraccount = wb_account_details::where('virtual_accnum',$user->virtual_account)->first();
        if (!$useraccount) return response()->json(['status' => 0, 'message' => 'Block User Error!']);
        ScheduleEsusu::create([
            'schedule_esusu_product_id'=> $scheduleEsusuProductCheck->id,
            'user_id'=>$useraccount->user_id,
            'company_id'=>auth()->user()->company_id,
            'created_by'=>auth()->user()->id,
            'started_debiting_at'=>$request->start_date,
            'amount_paid'=>$request->rate,
            'wallet'=>0,
            'end_date'=>$scheduleEsusuProductCheck->end_date,
            'wb_wallet_id'=>$useraccount->id,
        ]);
        return response()->json(['status'=>1,'message'=>'Esusu created!']);
        }catch(Exception $e){
            return response()->json(['status'=>0,'message'=>$e->getMessage()]);

        }
    }

    public function show(Request $request)
    {
        if (!cando('root|manage-foodstash')) {
            abort(403);
            return 0;
        }
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];
        $sortColumns = array(
            0 => 'id',
            1 => 'id',
            2 => 'id',
            3 => 'id',
            4 => 'id',
            5 => 'id',
            6 => 'id',
            7 => 'id',
            8 => 'id',

        );
        $query  = ScheduleEsusu::with('User')->with('Product')->where('company_id',auth()->user()->company_id);
        if($request->status){
            $query = $query->where('status',$request->status);
        }
        if($request->product && $request->product !='null'){
            $query = $query->where('schedule_esusu_product_id',$request->product);
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
            $created_by = User::find($infoData->created_by);
            $btnlink = $infoData->id;
            $btn = '<div class="dropdown">
                            <button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Actions
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                    if(cando('withdraw-foodstash|root')){
                        $btn .= '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',1)">Topup</a>';

                    }
                     if(cando('topup-foodstash|root')){
                        $btn .= '<a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',2)">Withdraw</a>';

                    }
                     if(cando('reset-foodstash|root')){
                        $btn .= '<a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',4)">Reset</a>';
                    }
                    if(cando('set-foodstash-agent|root') && $infoData->status=='active'){
                        $btn .= '<a class="dropdown-item" href="#"  data-toggle="modal" data-target="#esusu_transaction" onclick="pop_esusu_txn(' . $btnlink . ',5)">Change Agent</a>';
                    }
                    $btn .= '</div>
                            </div>';
            $json['data'][] = [
                $infoData->product->name,
                $infoData->user->first_name . ' ' . $infoData->user->last_name,
                $infoData->user->virtual_account,
                number_format($infoData->amount_paid, 2),
                $infoData->started_debiting_at,
                // 0,
                // 0,
                // 0,
                $infoData->wallet,
                $infoData->end_date,
                $infoData->status,
                $created_by->first_name . ' ' . $created_by->last_name,
                $btn
            ];
        }

        return $json;
    }
    public function topup(Request $request){
        try{
            if (!cando('root|manage-foodstash|topup-foodstash')) {
                abort(403);
                return 0;
            }
            $validator = Validator::make($request->all(), [
                'esusuid' => 'required',
                'number' => 'required',
                'method' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = ['message' => $errors,'status' => 0];
                return response()->json($datax);
            }
            DB::beginTransaction();

            $esusuId =$request->esusuid;
            $rate = $request->number;
            $selectEsusu =ScheduleEsusu::with('Product')->where(['company_id'=>auth()->user()->company_id,'id'=>$esusuId])->first();
            if(!$selectEsusu){
                return response()->json(['message' => "Esusu ID invalide!",'status' => 0]);
            }
            if($selectEsusu->end_date < date('Y-m-d')){
                return response()->json(['message' => "Esusu Closed!",'status' => 0]);
            }
            $amount = $rate *$selectEsusu->amount_paid;
            if(($amount%$selectEsusu->amount_paid) >0){
                return response()->json(['message' => "Incorrect input!",'status' => 0]);
            }

            $date1 = Carbon::parse($selectEsusu->product->start_date);
            $date2 = Carbon::parse($selectEsusu->product->end_date);


            // Calculate the difference in days
            $numberOfDays = $date1->diffInDays($date2);
            // $currentDate = Carbon::now();
            // Number of days to add
            // $numberOfDaysAmt = $numberOfDays * $selectEsusu->amount_paid;
            $countInput = ScheduleEsusuRepayment::where(['company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'type'=>'credit'])->count('num_of_days');
            if(($countInput+$rate) > $numberOfDays){
                return response()->json(['message' => "Amount exceed esusu total expected cash!",'status' => 0]);
            }
            $requestAmt = new Request(['amount' => $amount,'staff'=> auth()->user()->uuid, 'staff_credit_option'=>'credit']);
            $companyValutController = new CompanyValutController();
            $response = $companyValutController->staff_wallet($requestAmt);
            $responseData = $response->getData();

            if($responseData->status !== 1){
                DB::rollBack();
                return $response;
            }
            ScheduleEsusuRepayment::create(['num_of_days'=>$rate,'company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'amount'=>$amount,'type'=>'credit']);

            $credit = ScheduleEsusuRepayment::where(['company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'type'=>'credit'])->sum('amount');
            $debit = ScheduleEsusuRepayment::where(['company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'type'=>'debit'])->sum('amount');
            $selectEsusu->wallet = $credit -$debit;
            $selectEsusu->save();

            if($request->method ==='wallet') {
                $wb_account_details = wb_account_details::where('user_id', $selectEsusu->user_id)->first();

                $userWallet = $wb_account_details->user_wallet;
                $balanceBefore = $userWallet->balance;
                $balanceAfter = $balanceBefore - $amount;
                $referalPaymentTransactionType =  (getTransactionIdByName('FoodStash Deposit'))->id;
                $ledgerCr = null;
                $ledgerDr = null;
                $get_ledger_setting = get_CR_DR_TXN_Type_Ledger($referalPaymentTransactionType, auth()->user()->company_id);
                if ($get_ledger_setting) {
                    $ledgerCr = $get_ledger_setting->ledger_cr;
                    $ledgerDr = $get_ledger_setting->ledger_dr;
                }
                $data = [
                    "balance_before" => $balanceBefore,
                    "amount" =>  $amount,
                    "description" => "Referal Comission",
                    "account_id" => $wb_account_details->id,
                    "branch_id" => $wb_account_details->user->branch_id,
                    "transaction_status_id" => 1,
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => $referalPaymentTransactionType, //debit 7
                    "credit_debit_type_id" => 2,
                    "transaction_remarks_id" => 2,
                    "balance_after" => $balanceAfter,
                    "created_date"  => date('Y-m-d'),
                    "created_by" => $wb_account_details->user->id,
                    "ledger_cr" => $ledgerCr,
                    "ledger_dr" => $ledgerDr,
                    "initiator" => $$wb_account_details->user->id,
                ];
                $data['userParamType'] = 2;
                $data['userId'] = $wb_account_details->id;
                $data['remark'] = 2;
                $data['chargeamt'] = 0;
                DebitCreditcustomer($data);
            }


            DB::commit();

            return response()->json(['message' => "Credit successful!",'status' => 1]);
        }catch(Exception $e){
            DB::rollBack();

            return response()->json(['message' => "An error occured!",'status' => 0]);

        }
    }
    public function withdraw(Request $request){
        try{
            if (!cando('root|manage-foodstash|withdraw-foodstash')) {
                abort(403);
                return 0;
            }
            $validator = Validator::make($request->all(), [
                'esusuid' => 'required',
                'amount' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = ['message' => $errors,'status' => 0];
                return response()->json($datax);
            }
            $esusuId =$request->esusuid;
            $amount = $request->amount;

            $selectEsusu =ScheduleEsusu::with('Product')->where(['company_id'=>auth()->user()->company_id,'id'=>$esusuId])->first();
            if(!$selectEsusu){
                return response()->json(['message' => "Esusu ID invalide!",'status' => 0]);
            }
            $wallet =$selectEsusu->wallet ;
            if($amount < 1){
                return response()->json(['message' => "Incorrect input!",'status' => 0]);
            }
            // if(($amount )  > $wallet){
            //     return response()->json(['message' => "Insufficient value!",'status' => 0]);
            // }
            DB::beginTransaction();

            $withdraw_out_charge =$selectEsusu->product->withdraw_out_charge;
            ScheduleEsusuRepayment::create(['num_of_days'=>0,'company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'amount'=>$amount,'type'=>'debit']);
            $credit = ScheduleEsusuRepayment::where(['company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'type'=>'credit'])->sum('amount');
            $debit = ScheduleEsusuRepayment::where(['company_id'=>auth()->user()->company_id,'schedule_esusu_product_id'=>$selectEsusu->schedule_esusu_product_id,'user_id'=>auth()->user()->id,'started_debiting_at'=>$selectEsusu->started_debiting_at,'schedule_esusu_id'=>$esusuId,'type'=>'debit'])->sum('amount');
            $selectEsusu->wallet = $credit -$debit;
            $selectEsusu->save();
            //Esusu Accum. Charge
            $wb_account_details = wb_account_details::where(['user_id' => $selectEsusu->user_id])->first();

            $balanceBefore = $wb_account_details->user_wallet->balance;
            $ledg =getTransactionIdByName('FoodStash withdraw');
            $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger($ledg->id, auth()->user()->company_id);
            if (!$get_ledger_setting_Commission) {
                return response()->json(["status" => 0, "message" => "Transaction failed! "]);
            }

            $balanceAfter =$balanceBefore - $amount;
            $data = [
                "balance_before" => $balanceBefore,
                "amount" =>  $amount,
                "description" => "FoodStash Withdrawal",
                "account_id" => $wb_account_details->id,
                "branch_id" => auth()->user()->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" => $ledg->id, //debit 7
                "credit_debit_type_id" => 1, //debit 2
                "transaction_remarks_id" => 2, //success 2
                "balance_after" => $balanceAfter,
                "created_date"  => date('Y-m-d'),
                "created_by" => auth()->user()->id,
                "instrumentCode" => null,
                "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                "initiator" => null,
                "userParamType"=>2,
                "userId"=>$wb_account_details->id,
                "remark"=>2,
                "chargeamt"=>0,
                "companyVault"=>null,
                "sendSMS"=>0,
            ];
            $CreditCustomer = DebitCreditcustomer($data);
            if (!$CreditCustomer['status']) {
                DB::rollBack();
                return $CreditCustomer;
            }
            $ledg_charge =getTransactionIdByName('Esusu Accum. Charge');

            $data['amount'] = ($withdraw_out_charge/100) * $amount;
            $data["credit_debit_type_id"] =2;
            $data["description"] = "Schedule Esusu Charges";
            $data["transaction_type_id"] = $ledg_charge->id;
            $data["balance_before"] = $balanceAfter;
            $data["balance_after"] = $balanceAfter - $data['amount'];
            $CreditCustomerStaff = DebitCreditcustomer($data);
            if (!$CreditCustomerStaff['status']) {
                DB::rollBack();
                return $CreditCustomer;
            }
            $requestAmt = new Request(['amount' => $amount,'staff'=> auth()->user()->uuid, 'staff_credit_option'=>'debit']);
            $companyValutController = new CompanyValutController();
            $response = $companyValutController->staff_wallet($requestAmt);
            $responseData = $response->getData();
            if($responseData->status !== 1){
                DB::rollBack();
                return $response;
            }
            if($selectEsusu->wallet < 1){
                $selectEsusu->status ='complete';
                $selectEsusu->save();
            }
            DB::commit();

            return response()->json(['message' => "Debit successful!",'status' => 1]);
        }catch(Exception $e){
            DB::rollback();

            return response()->json(['message' => "An error occured!",'status' => 0]);

        }
    }
    public function reset(Request $request){
        try{
            if (!cando('root|reset-foodstash')) {
                abort(403);
                return 0;
            }
            $validator = Validator::make($request->all(), [
                'esusuid' => 'required',
                'reset_rate' => 'required',
                'reset_date' => 'required',
                'product_id' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = ['message' => $errors,'status' => 0];
                return response()->json($datax);
            }
            $esusuId =$request->esusuid;
            $rate = $request->reset_rate;
            $start_date = $request->reset_date;
            $product_id = $request->product_id;
            if($start_date < date('Y-m-d')){
                return response()->json(['message' => "Pick a date ahead!",'status' => 0]);
            }
            $selectEsusu =ScheduleEsusu::with('Product')->where(['company_id'=>auth()->user()->company_id,'id'=>$esusuId])->first();
            if(!$selectEsusu){
                return response()->json(['message' => "Esusu ID invalide!",'status' => 0]);
            }
            if($selectEsusu->wallet > 0){
                return response()->json(['message' => "Clear wallet before editing loan!",'status' => 0]);
            }
            if($selectEsusu->product->minimun > $rate || $selectEsusu->product->maximal < $rate){
                return response()->json(['status'=>0,'message'=>"Range is between ".$selectEsusu->product->minimun." - ".$selectEsusu->product->maximal]);
            }
            if($start_date > $selectEsusu->product->start_date){
                return response()->json(['message' => "Pick a date behind the product!",'status' => 0]);
            }
            $selectEsusu->amount_paid = $rate;
            $selectEsusu->started_debiting_at = $start_date;
            $selectEsusu->schedule_esusu_product_id = $product_id;
            $selectEsusu->save();
            return response()->json(['message' => "Esusu reset successfully!",'status' => 1]);
        }catch(Exception $e){
            return response()->json(['message' => "An error occured!",'status' => 0]);
        }
        }
        public function agent(Request $request){

            if (!cando('root|set-foodstash-agent')) {
                abort(403);
                return 0;
            }
            $selectEsusu =ScheduleEsusu::where(['company_id'=>auth()->user()->company_id,'id'=>$request->esusuid])->first();
            $selectEsusu->created_by = $request->agent;
            $selectEsusu->save();
            return response()->json(['message' => "Esusu reset successfully!",'status' => 1]);
        }

}
