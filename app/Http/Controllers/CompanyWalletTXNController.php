<?php

namespace App\Http\Controllers;
use App\Models\company_wallet;

use App\Services\FlutterWave;
use App\Services\LipilaService;
use Session;
use Illuminate\Http\Request;

use App\Models\transactions;

class CompanyWalletTXNController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addWallet(Request $request)
    {
        try
        {
        $auth =auth()->user();
        $company_wallet =company_wallet::where(['company_id'=>$auth->company_id,'currency_code'=>$request->currency])->first();
        if(!$company_wallet){
            $company_wallet_init =company_wallet::where(['id'=>$auth->company_id])->first();
            $company_wallet = company_wallet::create(['currency_code'=>$request->currency,
            'company_id'=>auth()->user()->company_id,
            'balance'=>0,
            'sms'=>0,
            'ussd'=>0,
            'bank_id'=>$company_wallet_init->bank_id,
            'whatsapp'=>0
        ]);
        Session::flash('success',"Currency added successfully");
                return redirect()->back();
        }else{
            Session::flash('fail',"Currency already exists");
            return redirect()->back();
        }
    }catch(Exception $e){
        Session::flash('fail',"Error adding currency");
        return redirect()->back();
    }
    }
    public function topup(Request $request)
    {
        //
        // $company_wallet = company_wallet::where(['company_id'=>auth()->user()->company_id,'currency_code'=>$request->currencytoTopup])->first();
        // if(!$company_wallet){

        // }
        try
        {
        $baniController = new FlutterWave();
        $lipilaService =new LipilaService();
        $transactionId = generate_txnID();
            $dataT = [];
        if($request->currencytoTopup == 'XAF'){
            $dataT["phone_number"] = $request->phone;
            $dataT["amount"] = $request->amount;
            $dataT["narration"] = "Wallet topup of $request->amount XAF";
            $dataT["email"] = auth()->user()->email;
            $dataT["fullname"] = auth()->user()->company->company_name;
            $dataT["tx_ref"] = $transactionId;
        }elseif($request->currencytoTopup == 'ZMW'){
            $dataT["accountNumber"] = $request->phone;
            $dataT["fullName"] =  auth()->user()->company->company_name;
            $dataT["phoneNumber"]=  $request->phone;
            $dataT["email"] =  auth()->user()->email;
            $dataT["externalId"]= "$transactionId";
            $dataT["narration"]=   "Topup $request->amount ZMW";
            $dataT["amount"] = $request->amount;

        }else{
            Session::flash('fail', 'Currency topup not available!');
            return redirect()->back();
        }
        $dataT["currency"] = $request->currencytoTopup;//
            $response = ($request->currencytoTopup  == 'XAF')?($baniController->receiveMoney($dataT)) : $lipilaService->receiveMobileMoney($dataT);
            if(!isset($response['status'])){
                Session::flash('fail', 'Error: cash must be 100 or above');
                return redirect()->back();
            }
            if($response['status'] =='success' || $response['status'] =='Successful' || $response['status'] =='Pending'){
                $txnID = ($request->currencytoTopup == 'XAF')? $response['data']['id']:$response['transactionId'];
                $dataT['instrumentCode'] =  $txnID;
            $dataT["phone"] = $request->phone;

                return view('stripe_admin.receivingpay',compact('txnID','dataT'));
            }else{
                Session::flash('fail', $response['message']);
                return redirect()->back();
            }
        }catch(Exception $e){
            Session::flash('fail', 'Error');
                return redirect()->back();
            // return response()->json(['status' => true, 'message' => $e->getMessage()]);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewResponse(Request $request)
    {
        $FLutterController = new FlutterWave();
        $lipilaService =new LipilaService();
        if($request->currency =='XAF'){
            $returndata = $FLutterController->webhook($request->reference);
        }else{
            $returndata = $lipilaService->checkTransactionStatus($request->reference);
        }
        if(!isset($returndata['status'])){
        return response()->json(['status' => false, 'message' => 'Failed to run','data'=>$returndata['data']]);
        }

        if($returndata['status']=='success' || $returndata['status']=='Successful'){
            if($returndata['data']['status']=='successful' || $returndata['status']=='Successful'){
                $transaction =   transactions::where(['instrumentCode'=>$request->reference,'transaction_remarks_id'=>1])->first();
                if($transaction){
                    $data = [
                        "amount" =>  $transaction->amount,
                        "description" => $transaction->description,
                        "account_id" => $transaction->account_id,
                        "branch_id" => $transaction->branch_id,
                        "transaction_status_id" => 1,
                        "transaction_reference" => $transaction->transaction_reference,
                        "transaction_type_id" => $transaction->transaction_type_id, //debit 7
                        "credit_debit_type_id" => 1, //debit 2
                        "transaction_remarks_id" => 2, //success 2
                        "created_date"  => $transaction->created_date,
                        "created_by" => $transaction->created_by,
                        "ledger_cr" => $transaction->ledger_cr,
                        "ledger_dr" => $transaction->ledger_dr,
                        "initiator" =>'self',
                    ];
                    $data['userParamType'] = 2;
                    $data['remark'] = 2;
                    $data['chargeamt'] = 0;
                    $transaction->transaction_remarks_id=2;
                    $transaction->save();
                    $data['transactionExist'] = $transaction;
                    $transferCompanyId = getTransactionIdByName('Company Mobile Money Transaction');
                    $created_by = $transaction->author;
                    $company_data=[];
                    $company_data['auth'] =$created_by;
                    $company_data['transaction_id']=$transferCompanyId->id;
                    $company_data['desc'] =$request->reference;
                    $company_data['currency']='XAF';
                    $company_data['amount'] =$transaction->amount;
                    $company_data['credit_debit_type_id'] = 1;
                    $companytxn = debitCreditCompany($company_data);
                    $txnID = ($request->currency == 'XAF')? $returndata['data']['id']:$returndata['transactionId'];
                    transactions::where('id',$companytxn->id)->update(['instrumentCode'=>$txnID]);
                    return response()->json(['status' => true, 'message' => 'Success','data'=>$returndata]);
                }
            }
    }
        return response()->json(['status' => false, 'message' => 'Failed','data'=>$returndata]);
    }

}
