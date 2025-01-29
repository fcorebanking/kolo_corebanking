<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LedgerSettings;
use App\Models\transactions;
use App\Models\LedgerAccountModel;
class LedgerController extends Controller
{
    public function index()
    {
        if (!cando('manage-investment|root')) return abort(403);
        $data = [
            'category_name' => 'accounting',
            'page_name' => 'ledgerAccountSettingData',
            'page_menu' => 'ledgerAccountSettingData',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'records' => LedgerSettings::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.ledgerSetting.setting')->with($data);
    }

    // public function store(Request $request){

    //     try{
    //         $auth = auth()->user();

    //         if($request->ledger_cr != $request->ledger_dr){
    //             $LedgerAccountModelCR = LedgerAccountModel::findorfail($request->ledger_cr);
    //             $LedgerAccountModelDR = LedgerAccountModel::findorfail($request->ledger_dr);
    //             if(!$LedgerAccountModelCR || !$LedgerAccountModelDR){
    //                 $this->response ="Invalide Ledger Account!";
    //                 $this->responseStatus =2;
    //                 return false;
    //             }
    //             if($LedgerAccountModelCR->type == $LedgerAccountModelDR->type){
    //                 //$this->response ="Same category of ledger not allowed, for you trial balance to be balance select an opposit ledger!";
    //                 //$this->responseStatus =2;
    //                 // return false;
    //             }
    //         }
    //         $checkDuplicate = LedgerSettings::where(['transaction_type_id'=>$this->page,'company_id'=>$auth->company_id])->count();
    //         if($checkDuplicate > 0){
    //             $this->response ="Settings for this transaction type already set!";
    //             $this->responseStatus =2;
    //             return false;
    //         }
    //         $addAccount =new LedgerSettings;
    //         $addAccount->transaction_type_id= $this->page;
    //         $addAccount->ledger_cr= $this->ledger_cr;
    //         $addAccount->ledger_dr= $this->ledger_dr;
    //         $addAccount->company_id= $auth->company_id;
    //         $addAccount->created_by= $auth->id;

    //         $addAccount->save();
    //         $this->show();
    //         $this->response ='Ledger added successfully!';
    //         $this->responseStatus =1;
    //     }catch(Exception $e){
    //         $this->response ="Error Processing request";
    //         $this->responseStatus =2;
    //     }
    // }
    // public function show(){
    // }
    // public function delete($id){
    //     LedgerSettings::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->delete();
    //     $this->response ='Ledger Deleted successfully!';
    //     $this->responseStatus =1;
    // }
}
