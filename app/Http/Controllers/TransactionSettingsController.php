<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionSettings;
class TransactionSettingsController extends Controller
{
    public function store(Request $request){
        try{
        $data = request()->validate([
            'view_vault_on_txn' => 'required',
            'hide_charge_on_statement' => 'required',
            'view_instrument_number' => 'required',
            'customer_can_transfer'=>'required',
        ]);
        $auth =auth()->user();
        $existdata =TransactionSettings::where('company_id',$auth->company_id)->first();
        $TransactionSettings =($existdata)?$existdata: new TransactionSettings;
        $TransactionSettings->company_id =$auth->company_id;
        $TransactionSettings->view_vault_on_txn =$request->view_vault_on_txn;
        $TransactionSettings->hide_charge_on_statement=$request->hide_charge_on_statement;
        $TransactionSettings->view_instrument_number=$request->view_instrument_number;
        $TransactionSettings->customer_can_transfer=$request->customer_can_transfer;
        $TransactionSettings->save();
        return response()->json(['status'=>true, 'message' =>"Settings Updated"], 200);

    }catch(Exception $e){
        return response()->json(['status'=>false, 'message' => $e->getMessage()], 500);
    }
    }
}
