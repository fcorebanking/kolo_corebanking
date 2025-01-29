<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\currencies;
use App\Models\User;
use App\Models\Branch;
use App\Models\ClosingBalance;
use Exception;
class ClosingBalanceController extends Controller
{
    public  function index(){
        if (!cando('manage-closing-balance|root')) return abort(403);
        $data = [
            'category_name' => 'accounting',
            'page_name' => 'html5',
            'page_menu' => 'closingbalance',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'currencies' => currencies::all(),
        ];
        return view('company.report.closingbalance')->with($data);
    }
    public  function BranchPage(){
        if (!cando('manage-closing-balance|root')) return abort(403);
        $data = [
            'category_name' => 'accounting',
            'page_name' => 'html5',
            'page_menu' => 'closingbranchbalance',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'currencies' => currencies::all(),
            'branchstaff' => User::where('branch_id',auth()->user()->branch_id)->get(),
        ];
        return view('company.report.closingbalancebranch')->with($data);
    }
    public  function CompanyPage(){
        if (!cando('manage-closing-balance|root')) return abort(403);
        $data = [
            'category_name' => 'accounting',
            'page_name' => 'html5',
            'page_menu' => 'closingcompanybalance',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'currencies' => currencies::all(),
            'branch' => auth()->user()->company->branch,

        ];
        return view('company.report.closingbalancecompanybranch')->with($data);
    }
    public  function closeAccount(Request $request){
    try{
            if(ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id,'status'=>'opened'])->count() === 0){
                return response()->json(['status'=>false,'message'=>'No balance is open!']);
            }
            $close = ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id])->first();
            if($close->total != $request->total){
                return response()->json(['status'=>false,'message'=>'Balance doesnt correspond!']);

            }
            $closing =ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id])->first();
            $closing->currency = $request->currency;
            $closing->company_id = auth()->user()->company_id;
            $closing->staff_id = auth()->user()->id;
            $closing->branch_id = auth()->user()->branch_id;
            $closing->ten_thousand_quantity = $request->ten_thousand_quantity;
            $closing->ten_thousand_amount = $request->ten_thousand_amount;
            $closing->five_thousand_quantity = $request->five_thousand_quantity;
            $closing->five_thousand_amount = $request->five_thousand_amount;
            $closing->two_thousand_quantity = $request->two_thousand_quantity;
            $closing->two_thousand_amount = $request->two_thousand_amount;
            $closing->one_thousand_quantity = $request->one_thousand_quantity;
            $closing->one_thousand_amount = $request->one_thousand_amount;
            $closing->five_hundred_note_quantity = $request->five_hundred_note_quantity;
            $closing->five_hundred_note_amount = $request->five_hundred_note_amount;
            $closing->five_hundred_coin_quantity = $request->five_hundred_coin_quantity;
            $closing->five_hundred_coin_amount = $request->five_hundred_coin_amount;
            $closing->one_hundred_quantity = $request->one_hundred_quantity;
            $closing->one_hundred_amount = $request->one_hundred_amount;
            $closing->fifthy_quantity = $request->fifthy_quantity;
            $closing->fifthy_amount = $request->fifthy_amount;
            $closing->twenty_five_amount = $request->twenty_five_amount;
            $closing->twenty_five_quantity = $request->twenty_five_quantity;
            $closing->ten_quantity = $request->ten_quantity;
            $closing->ten_amount = $request->ten_amount;
            $closing->five_quantity = $request->five_quantity;
            $closing->five_amount = $request->five_amount;
            $closing->two_quantity = $request->two_quantity;
            $closing->two_amount = $request->two_amount;
            $closing->one_quantity = $request->one_quantity;
            $closing->one_amount = $request->one_amount;
            $closing->total = $request->total;
            $closing->input_date = $request->input_date;
            $closing->status = 'closed';
            $closing->save();
            return response()->json(['status'=>true,'message'=>'Account Closed!']);
        }catch(Exception $request){
            return response()->json(['status'=>false,'message'=>'Error!']);

        }
        }
        public  function openAccount(Request $request){
    try{
    if(ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id])->count() >0){
        if(ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'status'=>'closed','staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id])->count() === 0){
            return response()->json(['status'=>false,'message'=>'Close previous day records!']);
        }
    }

        $closing =ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id,'staff_id'=>auth()->user()->id,'branch_id'=>auth()->user()->branch_id])->first()??new ClosingBalance;
        $closing->currency = $request->currency;
        $closing->status = 'opened';
        $closing->company_id = auth()->user()->company_id;
        $closing->staff_id = auth()->user()->id;
        $closing->branch_id = auth()->user()->branch_id;
        $closing->ten_thousand_quantity = $request->ten_thousand_quantity;
        $closing->ten_thousand_amount = $request->ten_thousand_amount;
        $closing->five_thousand_quantity = $request->five_thousand_quantity;
        $closing->five_thousand_amount = $request->five_thousand_amount;
        $closing->two_thousand_quantity = $request->two_thousand_quantity;
        $closing->two_thousand_amount = $request->two_thousand_amount;
        $closing->one_thousand_quantity = $request->one_thousand_quantity;
        $closing->one_thousand_amount = $request->one_thousand_amount;
        $closing->five_hundred_note_quantity = $request->five_hundred_note_quantity;
        $closing->five_hundred_note_amount = $request->five_hundred_note_amount;
        $closing->five_hundred_coin_quantity = $request->five_hundred_coin_quantity;
        $closing->five_hundred_coin_amount = $request->five_hundred_coin_amount;
        $closing->one_hundred_quantity = $request->one_hundred_quantity;
        $closing->one_hundred_amount = $request->one_hundred_amount;
        $closing->fifthy_quantity = $request->fifthy_quantity;
        $closing->fifthy_amount = $request->fifthy_amount;
        $closing->twenty_five_amount = $request->twenty_five_amount;
        $closing->twenty_five_quantity = $request->twenty_five_quantity;
        $closing->ten_quantity = $request->ten_quantity;
        $closing->ten_amount = $request->ten_amount;
        $closing->five_quantity = $request->five_quantity;
        $closing->five_amount = $request->five_amount;
        $closing->two_quantity = $request->two_quantity;
        $closing->two_amount = $request->two_amount;
        $closing->one_quantity = $request->one_quantity;
        $closing->one_amount = $request->one_amount;
        $closing->total = $request->total;
        $closing->input_date = date('Y-m-d');
        $closing->save();
        return response()->json(['status'=>true,'message'=>'Account Opened!']);
}catch(Exception $request){
    return response()->json(['status'=>false,'message'=>'Error procesing request!']);

}
    }
    public  function verify(Request $request){
        $closing =ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id])->first();
        if($closing){
            return response()->json(['status'=>true,'data'=>$closing]);
        }else{
            return response()->json(['status'=>false,'message'=>'no account for this']);
        }
    }
    public  function verifyBranch(Request $request){
        $closing =ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id])->get();
        if($closing){
            $data = [];
            foreach($closing as $closingData){
            }
            return response()->json(['status'=>true,'data'=>$closing]);
        }else{
            return response()->json(['status'=>false,'message'=>'no account for this']);
        }
    }
    public  function verifyCompany(Request $request){
        $closing =ClosingBalance::where(['currency'=>$request->currency,'company_id'=>auth()->user()->company_id])->first();
        if($closing){
            return response()->json(['status'=>true,'data'=>$closing]);
        }else{
            return response()->json(['status'=>false,'message'=>'no account for this']);
        }
    }
}
