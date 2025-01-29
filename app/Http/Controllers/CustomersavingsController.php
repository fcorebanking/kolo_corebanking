<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaystackService;
use App\Models\Savingstransactionlog;
use App\Models\Pendingcustomersaving;
use App\Models\Customersavings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\User ;
class CustomersavingsController extends Controller
{
    public function paystack_Test(){
        $paystack = new PaystackService;
        $amount =2000;
        $email ="oaniekeyi@gmail.com";
        $plan = ($paystack->createPlan($amount,"Saviour","daily",2))->json();
        return $request->paystack_Sub($email,$plan['data']['plan_code'],$amount);
        // {"status":true,"message":"Plan created","data":{"name":"Saviour","interval":"daily","amount":200000,"invoice_limit":2,"integration":171540,"domain":"test","currency":"NGN","plan_code":"PLN_ig4ymjlaznnegv2","send_invoices":true,"send_sms":true,"hosted_page":false,"migrate":false,"is_archived":false,"id":855932,"createdAt":"2023-07-01T22:47:15.990Z","updatedAt":"2023-07-01T22:47:15.990Z"}}
    }
    public function paystack_Sub($email,$planCode,$amount){
        $paystack = new PaystackService;
        $subscription = ($paystack->createSubscription($email,$planCode,$amount))->json();
        return $subscription;
    }
    public function webhook(Request $request){
        $paystack = new PaystackService;
        return $paystack->handleWebhook($request);
    }
    public function callBack(Request $request){
        $paystack = new PaystackService;
        $data = $paystack->paymentCallBack($request->reference);
        $status = 'fail';
        // if(!isset($data['data']['gateway_response'])){
        //     return false;
        // }
        // dd($data);
        $message = $data['message'];

        if(isset($data['data'])){
            $status =  $data['data']['status'];
            $checkSavingstransactionlog =Savingstransactionlog::where(['reference'=>$request->reference,'customer_code'=>$data['data']['customer']['customer_code']])->count();
            if($checkSavingstransactionlog == 0){
                $message = $data['message'];
                $customersavings = Customersavings::where(['plan_code'=>$data['data']['plan']])->first();
                $savingstransactionlog =new Savingstransactionlog;
                $savingstransactionlog->uuid = uniqid();
                $savingstransactionlog->customersaving_id=$customersavings->id;
                $savingstransactionlog->amount=$data['data']['amount']/100;
                $savingstransactionlog->reference=$data['data']['reference'];
                $savingstransactionlog->gateway_response=$data['data']['gateway_response'];
                $savingstransactionlog->paid_at=$data['data']['paid_at'];
                $savingstransactionlog->createdAt=$data['data']['created_at'];
                $savingstransactionlog->channel=$data['data']['channel'];
                $savingstransactionlog->currency=$data['data']['currency'];
                $savingstransactionlog->ip_address=$data['data']['ip_address'];
                $savingstransactionlog->metadata=$data['data']['metadata'];
                $savingstransactionlog->log=json_encode($data['data']['log']);
                $savingstransactionlog->email=$data['data']['customer']['email'];
                $savingstransactionlog->customer_code=$data['data']['customer']['customer_code'];
                $savingstransactionlog->authorization=json_encode($data['data']['authorization']);
                $savingstransactionlog->save();
                if($data['data']['gateway_response'] == 'Successful' && $data['data']['status'] == 'success'){
                    $pendingcustomersaving = Pendingcustomersaving::where(function ($query) use ($request) {
                        $query->whereNull('reference')
                            ->orWhere('reference', '!=', $request->reference);
                    })
                    ->where('status', 'pending')
                    ->where('customersaving_id', $customersavings->id)
                    ->first();
                    if($pendingcustomersaving){
                        $pendingcustomersaving->paid_at = $data['data']['paid_at'];
                        $pendingcustomersaving->status = 'complete';
                        $pendingcustomersaving->reference = $request->reference;
                        $pendingcustomersaving->save();
                        $customersavings->balance += $data['data']['amount']/100;
                        $customersavings->save();
                    }
                }
            }
        }
        $data = [
            'category_name' => 'auth',
            'page_name' => 'auth_default',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'page_menu' => 'Customer Savings Payment',
            'message'=>$message,
            'status'=>$status,
            'alt_menu' => '',
            'isReferenceValid' => false
        ];
        return view('inc.success')->with($data);
    }
    public function storeTargetSaving(Request $request){
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                "name" => "required",
                "frequency" => 'required',
                "selectPeriod" => 'required',
                "duration" => 'required|numeric',
                "target" => 'required|numeric',
                "customer"=>'required'
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
        return redirect()->back()->with('fail', $errors);

            }
            $auth = User::where('uuid',$request->customer)->first();
            $method ='wallet';
            $freqInPeriod = checkFrequencyInPeriod($request->frequency,$request->selectPeriod);
            $totalRepayment = $freqInPeriod *$request->duration;
            $customersavings = new Customersavings;
            $customersavings->name = $request->name;
            $customersavings->uuid = uniqid();

            $customersavings->agent_id = $request->agent_id;
            $customersavings->rate = round($request->target/$totalRepayment,2);
            $customersavings->frequency = $request->frequency;
            $customersavings->duration = $request->duration;
            $customersavings->period = $request->selectPeriod;
            $customersavings->status ='active';
            $customersavings->target = $request->target;
            $customersavings->user_id = $auth->id;
            $customersavings->start_date = date("Y-m-d");
            $customersavings->payment_method =$method;
            $currentDate = date("Y-m-d");
            $frequency =convertLY($request->frequency)['key'];
            $request->frequency = strtolower($request->frequency);
            $convertToDay = convertToDate($frequency);
            $contoday = ($convertToDay *$totalRepayment)-$convertToDay;
            $endDate = date("Y-m-d", strtotime("+$contoday days", strtotime($currentDate)));
            $customersavings->end_date =$endDate;
            $customersavings->created_by = $auth->id;
            if($customersavings->save()){
                $nextPay = $currentDate;
                for($i=0;$i<($totalRepayment);$i++){
                    $pendingsaving =new Pendingcustomersaving;
                    $pendingsaving->status='pending';
                    $pendingsaving->uuid = uniqid();
                    $pendingsaving->amount=$customersavings->rate;
                    $pendingsaving->paymentDate=$nextPay;
                    $pendingsaving->method=$method;
                    $pendingsaving->customersaving_id = $customersavings->id;
                    $pendingsaving->save();
                    $convertToDay = convertToDate($frequency);
                    $nextPay = date("Y-m-d", strtotime("+$convertToDay days", strtotime($nextPay)));
                }
                DB::commit();
        return redirect()->back()->with('success', "Savings created successfully");

            }
        return redirect()->back()->with('fail', "Fail to save data");

        }catch(Exception $e){
            DB::rollback();
        return redirect()->back()->with('fail', $e->getMessage());
        }
    }
    public function changeAgent(Request $request){

    try{
        $customersavings =  Customersavings::where('id',$request->selectedSaving)->first();
        $customersavings->agent_id = $request->agent_id;
        $customersavings->save();
        return redirect()->back()->with('success', "Savings agent changed successfully");

    }catch(Exception $e){
        DB::rollback();
    return redirect()->back()->with('fail', $e->getMessage());
    }
}
}
