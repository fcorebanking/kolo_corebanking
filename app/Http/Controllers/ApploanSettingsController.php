<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\loan_period;
use App\Models\loan_product;
use App\Models\loan; 
use App\Models\ApploanSettings; 
use App\Models\branch;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
class ApploanSettingsController extends Controller
{
    public function Appsetting(){
        if (!cando('app-loan-setting|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'loan_main_setting',
            'page_menu' => 'loan_main_setting',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'period'=>loan_period::all(),
            'product'=>loan_product::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.appsetting')->with($data);
    }
    public function PostAppsetting(Request $request){
        if (!cando('app-loan-setting|root')) return abort(403);
        $validator = Validator::make($request->all(), [
            'loan_products' => 'required',
            'loan_type' => 'required',
            'DurationPeriod' => 'required',
            'Duration' => 'required',
            'period' => 'required',
            'minamt' => 'required',
            'maxamt' => 'required',
            'transactionMin' => 'required',
            'transactionMax' => 'required',
            'minWalletBalance' => 'required',
            'maxWalletBalance' => 'required',
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
                'message' => $messages,
                'status' => 0,
            ];
            return response()->json($datax);
        }
        try{
            $settings = new ApploanSettings;
            $settings->loan_product_id = Crypt::decryptString($request->loan_products);
            $settings->loan_type = $request->loan_type;
            $settings->company_id = auth()->user()->company_id;
            $settings->durationPeriod = $request->DurationPeriod;
            $settings->duration = $request->Duration;
            $settings->repayment = Crypt::decryptString($request->period);
            $settings->minamt = $request->minamt;
            $settings->maxamt = $request->maxamt;
            $settings->transactionMin = $request->transactionMin;
            $settings->transactionMax = $request->transactionMax;
            $settings->minWalletBalance = $request->minWalletBalance;
            $settings->maxWalletBalance = $request->maxWalletBalance;
            $settings->NumOngoinAllowed = $request->NumOngoinAllowed;
            $settings->remark = $request->remark;
            $settings->save();
            return response()->json(['status'=>1,'message'=>'Loan Settings Saved!']);
        }catch (\Throwable $e) {
            return json_encode(['message' => 'Error saving Settings try again','status' => 0]);
        }
    }
    public function UpdatePostAppsetting(Request $request){
        if (!cando('app-loan-setting|root')) return abort(403);
        $validator = Validator::make($request->all(), [
            'loan_products' => 'required',
            'loan_type' => 'required',
            'DurationPeriod' => 'required',
            'Duration' => 'required',
            'period' => 'required',
            'minamt' => 'required',
            'maxamt' => 'required',
            'transactionMin' => 'required',
            'transactionMax' => 'required',
            'minWalletBalance' => 'required',
            'maxWalletBalance' => 'required',
            'setting_id'=> 'required',
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
                'message' => $messages,
                'status' => 0,
            ];
            return response()->json($datax);
        }
        try{
            
            $request->setting_id = Crypt::decryptString($request->setting_id);
            $settings =  ApploanSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$request->setting_id])->first();
            if(!$settings){
                return response()->json(['status'=>0,'message'=>'Invalid Token please refresh page!'.$request->setting_id]);
            }
            $settings->loan_product_id = Crypt::decryptString($request->loan_products);
            $settings->loan_type = $request->loan_type;
            $settings->durationPeriod = $request->DurationPeriod;
            $settings->duration = $request->Duration;
            $settings->repayment = Crypt::decryptString($request->period);
            $settings->minamt = $request->minamt;
            $settings->maxamt = $request->maxamt;
            $settings->transactionMin = $request->transactionMin;
            $settings->transactionMax = $request->transactionMax;
            $settings->minWalletBalance = $request->minWalletBalance;
            $settings->maxWalletBalance = $request->maxWalletBalance;
            $settings->NumOngoinAllowed = $request->NumOngoinAllowed;
            $settings->remark = $request->remark;
            $settings->save();
            return response()->json(['status'=>1,'message'=>'Loan Settings Saved!']);
        }catch (\Throwable $e) {
            return json_encode(['message' => 'Error Updating Settings try again'.$e->getMessage(),'status' => 0]);
        }
    }
    public function view_setting(Request $request){
        if (!cando('app-loan-setting|root')) return response()->json(['status'=>0,'message'=>'Access Denied!']);
       //Display Product
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 100);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];

        $sortColumns = array(
            0 => 'loan_product_id',
            1 => 'loan_product_id',
            2 => 'durationPeriod',
            3 => 'repayment',
            4 => 'duration',
            5 => 'maxamt',
            6 => 'minamt',
            7 => 'transactionMin',
            8 => 'transactionMax',
            9 => 'minWalletBalance',
            10 => 'maxWalletBalance',
            11 => 'status_id',
            12 => 'NumOngoinAllowed',
            13=>'id'
        );
        $query = ApploanSettings::where(['company_id'=>auth()->user()->company_id]);
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('durationPeriod', 'like', '%' . $filter . '%');
                $query->orWhere('repayment', 'like', '%' . $filter . '%');
                $query->orWhere('duration', 'like', '%' . $filter . '%');
                $query->orWhere('maxamt', 'like', '%' . $filter . '%');
                $query->orWhere('minamt', 'like', '%' . $filter . '%');
                $query->orWhere('minWalletBalance', 'like', '%' . $filter . '%');
                $query->orWhere('maxWalletBalance', 'like', '%' . $filter . '%');
                // $query->whereHas('LoanProduct', function ($q) use (&$filter){
                //     $q->where(['platoon_id'=>$myplatoon,'group_id'=>$mygroup]);
                //         })->
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
             $btnlink =Crypt::encryptString($infoData->id);
            $edit ="editSettings('$btnlink')";
            $delete ="permanent_delete('$btnlink')";
            $btn = '<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#" onclick='.$edit.'>Edit</a>
                    <a class="dropdown-item"  href="#" onclick='.$delete.'>Delete Permanently</a>
                </div>
            </div>';               
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->LoanProduct->product_name,
                    $infoData->durationPeriod,
                    $infoData->PeriodID->name,
                    $infoData->duration,
                    $infoData->maxamt,
                    $infoData->minamt,
                    $infoData->transactionMin,
                    $infoData->transactionMax,
                    $infoData->minWalletBalance,
                    $infoData->maxWalletBalance,
                    $infoData->NumOngoinAllowed,
                    $infoData->created_at,
                    ($infoData->status_id ==1)?"<span class='badge badge-success'>Active</span>":"<span class='badge badge-danger'>Disabled</span>",
                    $btn
                ];
            }
        return $json;
    }
    public function selectSettings($id){
        $id = Crypt::decryptString($id);
        $setting =ApploanSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->first();
        if($setting){
            $setting->ref = Crypt::encryptString($setting->id);
            $setting->id = Crypt::encryptString($setting->id);
            $setting->loan_product_id = $setting->LoanProduct->product_name;
            $setting->repayment = $setting->PeriodID->name;
        }
        return ($setting )?response()->json(['status'=>1,'message'=>'Success','data'=>$setting]):response()->json(['status'=>0,'message'=>'Failed']);
    }
    public function destroy($id){
        $id = Crypt::decryptString($id);
        if (!cando('app-loan-setting|root')) return response()->json(['status'=>0,'message'=>'Access Denied!']);
        if(ApploanSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->delete()){
            return response()->json(['status'=>1,'message'=>'App settings deleted successfully!']);
        }else{
            return response()->json(['status'=>0,'message'=>'Error Deleting Settings!']);
        }
    }
}
