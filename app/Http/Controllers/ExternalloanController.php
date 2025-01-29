<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\api\v1\LoanController;
use App\Http\Controllers\PublicTransactionController;
use App\Models\loan_period;
use App\Models\frequency;
use App\Models\company_settings;
use App\Models\banks;
use App\Models\branch;
use App\Models\savings_product;
use App\Models\company_wallet;

class ExternalloanController extends Controller
{
    public function index($company_reference){
        $company = company_wallet::where('refaccount', $company_reference)->first();

        if ($company) {

            $companySetting  = company_settings::where('company_id', $company->company_id)->first();

            if(!$companySetting){
                $data = [
                    'category_name' => 'auth',
                    'page_name' => 'auth_default',
                    'has_scrollspy' => 0,
                    'scrollspy_offset' => '',
                    'page_menu' => '',
                    'alt_menu' => '',
                    'status' => frequency::all(),
                    'isReferenceValid' => false
                ];


                return view('external.index')->with($data);
            }
            $preferredBankId = $company->company->setting->preferred_bank_id;
            $selectedBank = banks::find($preferredBankId);

            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => 'custom_register_link_public_loan',
                'alt_menu' => '',
                'isReferenceValid' => true,
                'company_reference' => $company_reference,
                'company_name' => $company->company->company_name,
                'company' => $company->company,
                'supportEmail' => $company->company->company_email,
                'supportPhone' => $company->company->company_phone,
                'company_logo' => $company->company->logo,
                'branch' => branch::has('author')->where('company_id', $company->company_id)->get()
            ];
            $account_types = savings_product::where('company_id', $company->company_id)->get();
            $banks = banks::where('in_use', 1)->get();
            return view('external.index', [
                'account_types' => $account_types,
                'banks' => $banks,
                'selectedbank' => $selectedBank,
                'allbank'=>banks::where('short_code','!=',null)->get(),
            ])->with($data);
        }

    }
    public function externalLoan(Request $request){
        try{
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            // 'duration' =>  'required|numeric|min:0|max:20',
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
                'subtext' => $messages,
                'status' => 0,
            ];
            return response()->json($datax);
        }
        $userid =null;
        if(!auth()->check()){
            $request->fullresponse = 'rrxgfuja'; //merge([$request->fullresponse => 'rrxgfuja']);
            $MemberController = (new PublicTransactionController)->create_customer_account($request);
            $memberResponse = json_decode($MemberController->getContent());
            if(!$memberResponse->status) return response()->json(['status' => false, 'message'=>$memberResponse->message]);
            $userid = $memberResponse->userid;
        }else{
            $userid = auth()->user()->id;
        }

        if(!$userid)return response()->json(['status'=>0,'message'=>'Error fetching user']);

        $request->userid =$userid;// merge(['userid'=>$userid]);
        $loansData = (new LoanController)->PostBooklLoan($request);
        $loans = json_decode($loansData->getContent());
        if(!$loans->status) return response()->json(['status' => false, 'message'=>$loans->message]);
        auth()->logout();
        return response()->json(['status'=>1,'message'=>"Loan Booked Successfully",'loan'=>$loans,'account'=>$memberResponse]);

    }catch(Exception $e){
        return response()->json(['status'=>0,'message'=>$e->getMessage()]);
    }
    }
}
