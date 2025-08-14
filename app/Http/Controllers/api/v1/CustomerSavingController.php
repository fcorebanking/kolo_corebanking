<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ScheduleEsusuController;
use App\Models\Customersavings;
use App\Models\Pendingcustomersaving;
use App\Models\ScheduleEsusu;
use App\Models\ScheduleEsusuProduct;
use App\Models\User;
use App\Models\wb_account_details;
use App\Services\PaystackService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerSavingController extends Controller
{
    public function __construct()
    {
        $this->checkActive();
    }
    public function checkActive()
    {
        // $active_bvn_status_id =1;
        // if(auth()->user()->bvn_status_id !=$active_bvn_status_id){
        //     return response()->json(["status" => false, "message" => 'BVN must be validated before performing target savings!'], 409);
        // }
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $auth               = auth()->user();
            $authorizeSignature = null;
            $validation         = Validator::make($request->all(), [
                "name"         => "required|unique:customersavings,name,NULL,id,user_id,$auth->id",
                "frequency"    => 'required',
                "selectPeriod" => 'required',
                "duration"     => 'required|numeric',
                "target"       => 'required|numeric',
                "method"       => 'required',
            ]);
            if ($validation->fails()) {
                $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
                return response()->json($ra, 406);
            }

            //check for pending customer saving with same name and user id
            $freqInPeriod   = checkFrequencyInPeriod($request->frequency, $request->selectPeriod);
            $totalRepayment = $freqInPeriod * $request->duration;

            $customersavings       = new Customersavings;
            $customersavings->name = $request->name;
            $customersavings->uuid = uniqid();
            $customersavings->rate = round($request->target / $totalRepayment, 2);

            $customersavings->frequency      = $request->frequency;
            $customersavings->duration       = $request->duration;
            $customersavings->period         = $request->selectPeriod;
            $customersavings->status         = 'active';
            $customersavings->target         = $request->target;
            $customersavings->user_id        = $auth->id;
            $customersavings->start_date     = date("Y-m-d");
            $customersavings->payment_method = $request->method;
            $currentDate                     = date("Y-m-d");
            $frequency                       = convertLY($request->frequency)['key'];
            $request->frequency              = strtolower($request->frequency);
            $convertToDay                    = convertToDate($frequency);
            $contoday                        = ($convertToDay * $totalRepayment) - $convertToDay;

            $endDate                     = date("Y-m-d", strtotime("+$contoday days", strtotime($currentDate)));
            $customersavings->end_date   = $endDate;
            $customersavings->created_by = $auth->id;

            if ($customersavings->save()) {
                $nextPay = $currentDate;
                for ($i = 0; $i < ($totalRepayment); $i++) {
                    $pendingsaving                    = new Pendingcustomersaving;
                    $pendingsaving->status            = 'pending';
                    $pendingsaving->uuid              = uniqid();
                    $pendingsaving->amount            = $customersavings->rate;
                    $pendingsaving->paymentDate       = $nextPay;
                    $pendingsaving->method            = $request->method;
                    $pendingsaving->customersaving_id = $customersavings->id;
                    $pendingsaving->save();
                    $convertToDay = convertToDate($frequency);
                    $nextPay      = date("Y-m-d", strtotime("+$convertToDay days", strtotime($nextPay)));
                }
                $amount = $customersavings->rate;
                if ($request->method == 'paystack') {
                    $paystack = new PaystackService;
                    $plan     = ($paystack->createPlan($customersavings->rate, $auth->first_name . $request->name, $request->frequency, $request->duration))->json();
                    if ($plan['status'] !== true) {
                        DB::rollback();
                        return response()->json(["status" => false, "message" => $plan['message'], "state" => $plan['status']], 400);
                    }
                    $subscription                 = ($paystack->createSubscription($auth->email, $plan['data']['plan_code'], $amount))->json();
                    $customersavings->paystack_id = $plan['data']['id'];
                    $customersavings->integration = $plan['data']['integration'];
                    $customersavings->plan_code   = $plan['data']['plan_code'];
                    $customersavings->save();
                    if (! $subscription['status']) {
                        DB::rollback();
                        return response()->json(["status" => false, "message" => "Error: Plan created but subscription was incomplete, " . $subscription['message'] . " go to savings plan and click complete process to finish subscription"], 400);
                    }
                    $authorizeSignature                 = $subscription['data']['authorization_url'];
                    $customersavings->authorization_url = $authorizeSignature;
                    $customersavings->access_code       = $subscription['data']['access_code'];
                    $customersavings->reference         = $subscription['data']['reference'];
                    $customersavings->save();
                }
                DB::commit();
                $ra = ["status" => true, "message" => "Savings created successfully", 'data' => $customersavings->uuid, 'auth_url' => $authorizeSignature];
                return response()->json($ra, 200);
            }
            $ra = ["status" => false, "message" => "Fail to save data"];
            return response()->json($ra, 400);
        } catch (Exception $e) {
            DB::rollback();
            $ra = ["status" => false, "message" => $e->getMessage()];
            return response()->json($ra, 406);
        }
    }
    public function show(Request $request)
    {
        $return = Customersavings::with('payments')->where('user_id', auth()->user()->id)->get();
        return response()->json(["status" => ($return) ? true : false, "message" => ($return) ? "Fetched!" : "No record found!", "data" => $return], ($return) ? 200 : 404);
    }
    public function cancel($id)
    {
        try {
            $return = Customersavings::where(['user_id' => auth()->user()->id, 'id' => $id, 'status' => 'active'])->first();
            if (! $return) {
                return response()->json(["status" => false, "message" => 'Operation not allowed'], 409);
            }
            $return->status = 'cancel';
            $return->save();
            Pendingcustomersaving::where(['customersaving_id' => $return->id, 'status' => 'pending'])->update(['status' => 'cancel']);
            // if($pendingrepayment){
            //     foreach($pendingrepayment as $pendingrepaymentData){
            //         $pendingrepaymentData->status = 'cancel';
            //         $pendingrepaymentData->save();
            //     }
            // }

            return response()->json(["status" => true, "message" => "Savings Terminated"], 200);

        } catch (Exception $e) {
            return response()->json(["status" => false, "message" => 'Error processing request'], 404);

        }
    }
    public function listSavingsPayments($id)
    {
        $authID = auth()->user()->id;
        $pend   = Pendingcustomersaving::where(['customersaving_id' => $id, 'company_id' => $authID])->get();
        return response()->json(["status" => ($pend) ? true : false, "message" => ($pend) ? "Fetched!" : "No record found!", "data" => $pend], ($pend) ? 200 : 404);

    }
    public function disable($id)
    {
        $return = Customersavings::where(['user_id' => auth()->user()->id, 'id' => $id])->first();
        if (! $return) {
            return response()->json(["status" => false, "message" => "Error"], 404);
        }

        $return->status = 'in-active';
        $return->save();
        $ra = ["status" => true, "message" => "Savings diabled successfully"];
        return response()->json($ra, 200);
    }
    public function enable()
    {
        $return = Customersavings::where(['user_id' => auth()->user()->id, 'id' => $id])->first();
        if (! $return) {
            return response()->json(["status" => false, "message" => "Error"], 404);
        }

        $return->status = 'active';
        $return->save();
        $ra = ["status" => true, "message" => "Savings enabled successfully"];
        return response()->json($ra, 200);
    }
    //
    public function viewEsusuScheduleProduct()
    {
        $return = ScheduleEsusuProduct::where('company_id', auth()->user()->company_id)->get();
        return response()->json(["status" => ($return) ? true : false, "message" => ($return) ? "Fetched!" : "No record found!", "data" => $return], ($return) ? 200 : 404);
    }
    public function viewEsusuSchedule()
    {
        $return = ScheduleEsusu::with('Product')->where('user_id', auth()->user()->id)->get();
        return response()->json(["status" => ($return) ? true : false, "message" => ($return) ? "Fetched!" : "No record found!", "data" => $return], ($return) ? 200 : 404);
    }
    public function postEsusuSchedule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rate'          => 'required',
                'start_date'    => 'required',
                'esusu_product' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = ['message' => $errors, 'status' => 0];
                return response()->json($datax, 400);
            }
            $auth    = auth()->user();
            $user_id = $auth->uuid;
            // if(date($request->start_date) < date('Y-m-d')){
            //     return response()->json(['status'=>0,'message'=>'Start date must be greater or equal to current date'],409);
            // }
            $scheduleEsusuProductCheck = ScheduleEsusuProduct::where('id', $request->esusu_product)->first();
            if (! $scheduleEsusuProductCheck) {
                return response()->json(['status' => 0, 'message' => 'Product fails!'], 400);
            }

            //'minimun','maximal'
            if ($scheduleEsusuProductCheck->minimun > $request->rate || $scheduleEsusuProductCheck->maximal < $request->rate) {
                return response()->json(['status' => 0, 'message' => "Range is between $scheduleEsusuProductCheck->minimun - $scheduleEsusuProductCheck->maximal"], 409);
            }
            if ($request->start_date > $scheduleEsusuProductCheck->start_date) {
                return response()->json(['message' => "Pick a date behind or on the product start date ($scheduleEsusuProductCheck->start_date)!", 'status' => 0], 409);
            }
            $user = User::where(['uuid' => $user_id, 'company_id' => auth()->user()->company_id, 'status_id' => 1])->first();
            if (! $user) {
                return response()->json(['status' => 0, 'message' => "User not found"], 404);
            }
            $scheduleEsusuCheck = ScheduleEsusu::where(['schedule_esusu_product_id' => $scheduleEsusuProductCheck->id, 'user_id' => $user->id])->first();
            if ($scheduleEsusuCheck) {
                return response()->json(['status' => 0, 'message' => 'Already have a running plan!']);
            }
            $useraccount = wb_account_details::where('virtual_accnum', $user->virtual_account)->first();
            if (! $useraccount) {
                return response()->json(['status' => 0, 'message' => 'Block User Error!'], 409);
            }

            ScheduleEsusu::create([
                'schedule_esusu_product_id' => $scheduleEsusuProductCheck->id,
                'user_id'                   => $useraccount->user_id,
                'company_id'                => auth()->user()->company_id,
                'created_by'                => auth()->user()->id,
                'started_debiting_at'       => $request->start_date,
                'amount_paid'               => $request->rate,
                'wallet'                    => 0,
                'end_date'                  => $scheduleEsusuProductCheck->end_date,
                'wb_wallet_id'              => $useraccount->id,
            ]);
            return response()->json(['status' => 1, 'message' => 'Esusu created!'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);

        }
    }
    public function cancelEsusuSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esusuid' => 'required',
            'amount'  => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = ['message' => $errors, 'status' => 0];
            return response()->json($datax, 409);
        }
        $return = ScheduleEsusu::with('Product')->where(['user_id' => auth()->user()->id, 'id' => $request->esusuid])->first();
        if (! $return) {
            return response()->json(['message' => 'You dont have access to this product!', 'status' => 0], 409);
        }
        $scheduleController = new ScheduleEsusuController;
        $withdraw           = $scheduleController->withdraw($request);
        return $withdraw;
        // return response()->json(["status" => ($return)?true:false, "message" =>($return)?"Fetched!":"No record found!","data"=>$withdraw],($return)?200:404);
    }
}
