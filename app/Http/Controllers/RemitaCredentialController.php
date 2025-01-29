<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\RemitaCredential;
use App\Models\company_settings;

class RemitaCredentialController extends Controller
{
    public function store(Request $request){
        $validation = Validator::make($request->all(), [
            "apikey" => 'required',
            "apitoken" => 'required',
            "machant" => 'required',
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            $ra = array("status" => 0, "message" => "$errors");
            // return redirect()->back()->with('fail',$errors);
            return response()->json($ra);
        }
        $auth = auth()->user();
        $RemitaCredential = RemitaCredential::where('company_id',$auth->company_id)->first();
        if(!$RemitaCredential){
            $RemitaCredential = new RemitaCredential;
            $RemitaCredential->company_id =$auth->company_id;
        }
        $RemitaCredential->apiKey = $request->input('apikey');
        $RemitaCredential->apiToken = $request->input('apitoken');
        $RemitaCredential->machant= $request->input('machant');
        $RemitaCredential->ownership= $request->input('defaultCheck');
        $RemitaCredential->save();
        return response()->json(["status" => 1, "message" => "Record Updated"]);

    }
    public function show(){
        $RemitaCredential = RemitaCredential::where('company_id',auth()->user()->company_id)->first();
        return response()->json($RemitaCredential);
    }
    public function activateremitta(Request $request){
        $companySettings =company_settings::where('company_id',auth()->user()->company_id)->update(['activate_remitta'=>$request->status]);
        if($companySettings){
            return response()->json(["status" => 1, "message" => "Activate Settings!"]);
        }else{
            return response()->json(["status" => 0, "message" => "Activating Settings Failed!"]);
        }
    }
}
