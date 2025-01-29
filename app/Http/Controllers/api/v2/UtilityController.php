<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\banks;
use App\Models\Company;
use App\Models\company_settings;
class UtilityController extends Controller
{
    public function getBank(Request $request)
    {
        $allbanks = banks::all();
        return response()->json(
            [
                'status' => true,
                'message' => "Account Verified Successfully",
                'data' => $allbanks
            ]
        );
    }
    public function check_app_version($company){
        $companyRecord = Company::where(['company_reference' => $company])->join('company_settings','company_settings.company_id','=','company.id')->select('company_settings.mobile_app_update_Message','company_settings.mobile_app_versionCode','company_settings.mobile_app_googlelink','company_settings.mobile_app_ioslink','company_settings.mobile_app_force_update')->first();
        // $companyRecord->setting->company_id = 'xx';
        // $companyRecord->setting->id = 'xx';
        // $companyRecord->setting->teller_option_id = 'xx';
        // $companyRecord->setting->company_type_id = 'xx';
        // $companyRecord->setting->esusu_type_id = 'xx';
        // $companyRecord->setting->plan_id = 'xx';
        // $companyRecord->setting->auto_renewal_id = 'xx';

        return response()->json(
            [
                'status' => true,
                'message' => "No update",
                'data' => $companyRecord,
            ]
        );
    }
}
