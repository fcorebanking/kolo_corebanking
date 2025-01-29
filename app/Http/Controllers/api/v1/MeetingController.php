<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\company_settings;

class MeetingController extends Controller
{
    public function meetingDetails(){
        $data =company_settings::where('company_id',auth()->user()->company_id)->select('meetingID', 'meetingToken','editable_meetingID','editable_displayname')->first();
        return (($data->meetingID != null))?response()->json(['status'=>true,'message'=>$data], 200):response()->json(['status'=>false,'message'=>'Error fetching meeting ID'], 404);
    }
}
