<?php

namespace App\Http\Controllers;

use App\Models\banks;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use App\Models\Contries;
use App\Models\currencies;
use App\Models\company_types;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\company_settings;
use App\Models\Social_media;
use Illuminate\Support\Facades\Storage;
use App\Models\charge_customer_status;
use App\Models\instant_settlement_status;
use App\Models\teller_option;
use App\Models\auto_renewal;
use App\Models\back_date_status;
use App\Models\transactions;
use App\Models\smsapi;
use App\Models\plans;
use App\Models\CompanySettlement;
use App\Models\TransactionSettings;
use App\Models\request_methods;
use App\Models\CustomerReferalSetting;
use App\Http\Controllers\CompanyController;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


use App\Models\CompanyEmail;

class CompanySettingsController extends Controller
{

    function __construct()
    {
        $this->middleware("custom_permission:manage-company-settings|root");
    }
    public function index($company_name)
    {
        $auth = auth()->user();
        $companyApi = ApiKey::where('company_id', $auth->company_id)->first();
        if ($companyApi) {
            $send_notification = $companyApi->send_notification;
            $webhook_url = $companyApi->webhook_url;
        } else {
            $send_notification = 0;
            $webhook_url = "";
        }
        $data = [
            'category_name' => 'users',
            'page_name' => 'account_settings',
            'page_menu' => 'company_settings',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'currency' => currencies::all(),
            'country' => Contries::all(),
            'cooperative' => company_types::all(),
            'esusu_types' => [['id' => 1, 'esusu_type' => 'Type 1'], ['id' => 2, 'esusu_type' => 'Type 2']],
            'charge_customer_status' => charge_customer_status::all(),
            'instant_settlement_status' => instant_settlement_status::all(),
            'teller_option' => teller_option::all(),
            'auto_renewal' => auto_renewal::all(),
            'send_notification' => $send_notification,
            'webhook_url' => $webhook_url,
            'request_methods' => request_methods::all(),
            'back_date_status' => back_date_status::all(),
            'smsapi' => smsapi::where('company_id', '=', $auth->company_id)->get(),
            'company' => Company::where('id', '=', $auth->company_id)->get(),
            'social' => Social_media::where('company_id', '=', $auth->company_id)->get(),
            'company_settings' => company_settings::where('company_id', '=', $auth->company_id)->get(),
            'in_use_banks' => banks::where('in_use', '1')->get(),
            'banks' => banks::all(),
            'transactionSetting'=>TransactionSettings::where('company_id', $auth->company_id)->first(),
        ];


        return view('company.settings.main')->with($data);
    }
    public function showPaymentLink()
    {
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'company_payment_link',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];

        return view('company.settings.show_payment_links')->with($data);
    }

    public function showPaymentLinkPage()
    {
        $data = [
            'category_name' => 'forms', 
            'page_name' => 'wizards',
            'page_menu' => 'company_payment_link_page',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'paymentLink' => url("/transaction/payment-link/" . auth()->user()->company->wallet->refaccount)
            // 'status' => status::all(),

        ];
        // $pageName = 'html5';

        return view('company.settings.show_payment_links_page')->with($data);
    }

    public function store($company_name, Request $request)
    {
        // company_settings
        $validation = Validator::make($request->all(), [

            "maintenance_change_amount" => 'required|numeric|min:0',
            // "companycountry" => 'required',
            "companyaddress" => 'required',
            "teller_option" => 'required',
            "company_type" => 'required',
            "esusu_type" => 'required',
            "pin_verification" => 'required',
            "teller_commission" => 'required',
            "registration_fee" => 'required',
            "back_date_status" => 'required',
            "date_control" => 'required',
            "sms_charge" => 'required',
            "sms_charge_frequencie_id" => 'required',
            "min_withdraw_charge" => 'required|numeric|min:0',
            "max_withdraw_charge" => 'required|numeric|min:0',
            "bank_transfer_charge_state" => 'required|numeric|min:0',
            // "min_bank_transfer_charge_or_percentage" => 'required|numeric|min:0',
            // "max_bank_transfer_charge" => 'required|numeric|min:0',
            "withdraw_charge_state" => 'required',
            "charge_customer_maintenance_id" => 'required',
            "card_maintenance_fee" => 'required|numeric|min:0',
            "card_request_charge" => 'required|numeric|min:0',
            "ussd_charge" => 'required|numeric|min:0',
            "ussd_control" => 'required',
            "incoming_transfer_charge_state" => 'required',
            // "min_transfer_amount" => 'required|numeric|min:0',
            "internal_account_creation" => 'required|numeric',
            "max_onlinetransfer_amount" => 'required|numeric|min:0',
            "daily_transfer_limit_count" => 'required|numeric|min:0',
            "preferred_bank" => 'required|string',
            "who_to_pay_incoming_transfer_charge" => 'required',
            "investment_termination_control" => 'required',
            "additional_pos_charge_percent" => 'required|min:0',
            "pos_charge_capped_at" => 'required|min:0',
            "auto_renewal" => 'required',
            "whatsapp_control" => 'required',
            "withdraw_charge_state" => 'required',
            'company_prefix' => 'required'
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
        if (plans::count() == 0) {
            //  return redirect()->back()->with('fail',"Plan Fetcher Failed!");
            $ra = array("status" => 0, "message" => "Plan Fetcher Failed!");
            return response()->json($ra);
        }
        $company_table = Company::findorfail(auth()->user()->company_id);

        if ($company_table->transaction_pin != $request->pin_verification) {
            $ra = array("status" => 0, "message" => "Invalid Pin!");
            return response()->json($ra);
        }
        // $company_table->countries_id = (!empty($request->companycountry)) ? htmlentities($request->companycountry) : $company_table->countries_id;
        $company_table->address = (!empty($request->companyaddress)) ? htmlentities($request->companyaddress) : $company_table->address;

        $logo_Path = '';
        $company_logo = $request->file('logo');

        if ($company_logo) {
            if($company_table->logo) Storage::delete($company_table->logo);;

            $logoName = $company_logo->getClientOriginalName();
            // $imageLogoData = file_get_contents($company_logo->getRealPath());
            // $logo_Path = base64_encode($imageLogoData);
            $logo_Path = $company_logo->storeAs("public/company/profile/" . auth()->user()->company_id, $logoName);
            $company_table->logo = $logo_Path;
            // $company_table->ImageType = 'base64';
        }
        $company_cert = $request->file('cert');

        if ($company_cert) {
            if($company_table->license_certificate_image) Storage::delete($company_table->license_certificate_image);
            $certName = $company_cert->getClientOriginalName();

            $cert_Path = $company_cert->storeAs("public/company/certificate/" . auth()->user()->company_id, $certName);
            $company_table->license_certificate_image     = $cert_Path;
        }


        $company_table->save();

        //$email = htmlentities($request->get('email'));
        if (company_settings::where('company_id', '=', auth()->user()->company_id)->count() == 0) {
            $company_settings = new company_settings;
            $company_settings->company_id = auth()->user()->company_id;
            $company_settings->plan_id = 1;
        } else {
            $company_settings_data = company_settings::where('company_id', '=', auth()->user()->company_id)->get();
            foreach ($company_settings_data as $company_settings_dataid);
            $company_settings_id = $company_settings_dataid->id;
            $company_settings = company_settings::findorfail($company_settings_id);
        }

        $companyId = (int) auth()->user()->company_id;

$gtbCompanyIds = [46, 79, 80, 101, 105];

$preferredBankQuery = banks::where(
    "bank_code",
    $request->preferred_bank
);

if ($request->preferred_bank === '13') {
    if (!in_array($companyId, $gtbCompanyIds)) {
        return response()->json([
            'status' => 0,
            'message' => 'GT Bank is not available for your company.'
        ]);
    }
}

$preferredBank = $preferredBankQuery->first();

        if (!$preferredBank) {
            return response()->json(['status' => 0, 'message' => 'Invalid bank selected']);
        }

        logInfo($preferredBank, "Preference bank ");

        $company_settings->maintenance_change_amount = $request->maintenance_change_amount; //(!empty())?htmlentities($request->teller_option):$company_settings->teller_option;
        $company_settings->teller_option_id = $request->teller_option; //(!empty())?htmlentities($request->teller_option):$company_settings->teller_option;
        $company_settings->company_type_id = $request->company_type; //(!empty())?htmlentities($request->company_type):$company_settings->company_type;
        $company_settings->esusu_type_id = $request->esusu_type; //(!empty($request->esusu_type))?htmlentities($request->esusu_type):$company_settings->esusu_type;
        $company_settings->withdraw_charge_state = $request->withdraw_charge_state; //(!empty($request->esusu_type))?htmlentities($request->esusu_type):$company_settings->esusu_type;
        $company_settings->teller_commission = $request->teller_commission; //(!empty($request->teller_commission))?htmlentities($request->teller_commission):$company_settings->teller_commission;
        $company_settings->registration_fee = $request->registration_fee; //(!empty($request->registration_fee))?htmlentities($request->registration_fee):$company_settings->registration_fee;
        $company_settings->back_date_status_id = $request->back_date_status; //(!empty($request->back_date_status))?htmlentities($request->back_date_status):$company_settings->back_date_status;
        // $company_settings->charge_customer_status=$request->charge_customer_status; //(!empty($request->charge_customer_status))?htmlentities($request->charge_customer_status):$company_settings->charge_customer_status;
        $company_settings->date_control = $request->date_control; //(!empty($request->date_control))?htmlentities($request->date_control):$company_settings->date_control;
        $company_settings->sms_charge = $request->sms_charge;
        $company_settings->company_prefix = $request->company_prefix;
        $company_settings->minimum_balance = $request->minimum_balance;
        $company_settings->sms_charge_frequencie_id = $request->sms_charge_frequencie_id;
        $company_settings->min_withdraw_charge = $request->min_withdraw_charge;
        $company_settings->max_withdraw_charge = $request->max_withdraw_charge;
        $company_settings->min_bank_transfer_charge_or_percentage = $request->min_bank_transfer_charge_or_percentage;
        $company_settings->bank_transfer_charge_state = $request->bank_transfer_charge_state;
        $company_settings->max_bank_transfer_charge = $request->max_bank_transfer_charge;
        $company_settings->withdraw_charge_state = $request->withdraw_charge_state;
        $company_settings->charge_customer_maintenance_id = $request->charge_customer_maintenance_id;
        $company_settings->ussd_charge = $request->ussd_charge;
        $company_settings->internal_account_creation = $request->internal_account_creation;
        $company_settings->whatsapp_control = $request->whatsapp_control;
        $company_settings->whatsapp_charge = $request->whatsapp_charge;
        $company_settings->ussd_control = $request->ussd_control;
        $company_settings->investment_termination_control = $request->investment_termination_control;
        $company_settings->min_transfer_amount = $request->min_transfer_amount;
        $company_settings->birthday_msg = $request->birthday_msg; //birthday
        $company_settings->birthday_msg_channel = $request->birthday_msg_channel; //birthday
        $company_settings->max_onlinetransfer_amount = $request->max_onlinetransfer_amount;
        $company_settings->daily_transfer_limit_count = $request->daily_transfer_limit_count;
        $company_settings->account_approval = $request->account_approval;
        $company_settings->restrict_customers = $request->restrict_customer;
        $company_settings->preferred_bank_id = $preferredBank->id;

        $company_settings->auto_renewal_id = $request->auto_renewal;
        $company_settings->dom_account_duration = $request->dom_account_duration;

        $company_settings->additional_pos_charge_percent = $request->additional_pos_charge_percent * 0.01;
        $company_settings->pos_charge_capped_at = $request->pos_charge_capped_at;

        $company_settings->new_physical_card_charge = $request->card_request_charge;
        $company_settings->physical_card_maintenance_charge = $request->card_maintenance_fee;
        $company_settings->physical_card_withdrawal_charge = $request->card_withdrawal_charge;
        $company_settings->bills_payment_charge = $request->bills_payment_charge;
        $company_settings->preferred_virtual_card_currency = $request->preferred_virtual_card_currency;
        $company_settings->additional_virtual_card_funding_fee = $request->additional_virtual_card_funding_fee;

        $company_settings->incoming_transfer_charge_state = $request->incoming_transfer_charge_state;
        $company_settings->incoming_transfer_charge_amount = $request->incoming_transfer_charge_amount;
        $company_settings->incoming_transfer_charge_percentage = $request->incoming_transfer_charge_percentage;
        $company_settings->incoming_transfer_charge_capped_at = $request->incoming_transfer_charge_capped_at;
        $company_settings->who_to_pay_incoming_transfer_charge = $request->who_to_pay_incoming_transfer_charge;


        $company_settings->save();
        if($request->company_phone || $request->company_email){
            $company_table->company_phone = $request->company_phone;
            $company_table->company_email = $request->company_email;
            $company_table->save();
        }
        if (Social_media::where('company_id', '=', auth()->user()->company_id)->count() == 0) {

            $data = [
                ['name' => 'linkedin', 'media_link' => $request->link1, 'company_id' => auth()->user()->company_id],
                ['name' => 'Twitter', 'media_link' => $request->link2, 'company_id' => auth()->user()->company_id],
                ['name' => 'Facebook', 'media_link' => $request->link3, 'company_id' => auth()->user()->company_id],
                ['name' => 'Youtube', 'media_link' => $request->link4, 'company_id' => auth()->user()->company_id],
            ];
            //DB::table('social_media')->insert($data);
            Social_media::insert($data);
        } else {
            Social_media::where(['company_id' => auth()->user()->company_id, 'name' => 'linkedin'])->update(['media_link' => $request->link1]);
            Social_media::where(['company_id' => auth()->user()->company_id, 'name' => 'Twitter'])->update(['media_link' => $request->link2]);
            Social_media::where(['company_id' => auth()->user()->company_id, 'name' => 'Facebook'])->update(['media_link' => $request->link3]);
            Social_media::where(['company_id' => auth()->user()->company_id, 'name' => 'Youtube'])->update(['media_link' => $request->link4]);
        }



        $ra = array("status" => 1, "message" => "Settings Updated!");
        return response()->json($ra);
        // return redirect()->back()->with('success','Settings Updated!');
    }

    public function ajax_update_sms(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "smsapiurl" => 'required',
            "smsmessage" => 'required',
            "smsname" => 'required',
            "smsphone" => 'required',

        ]);

        $state = 'error';
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }

            $ra = array("status" => 0, "message" => "$errors");
            return response()->json($ra);
        }
        if ($request->smsphone != null && $request->smsapiurl != null && $request->smsmessage != null  && $request->smsname != null) {
            $existingsms = smsapi::where('company_id', '=', auth()->user()->company_id);
            if ($existingsms->count() == 0) {
                smsapi::create([
                    'company_id' => auth()->user()->company_id,
                    'phone_variable' => str_replace(' ', '', $request->smsphone),
                    'name' => $request->smsname,
                    'message_variable' => str_replace(' ', '', $request->smsmessage),
                    'api_url' => str_replace(' ', '', $request->smsapiurl),
                    'request_method_id' => $request->request_type,
                    'content_type' => $request->content_type,
                    'authorization' => $request->authorization,
                    'sender_variable' => $request->sender_variable,
                    'smscharge' => 0,
                ]);
                $state = 'Created';
            } else {
                smsapi::where([
                    'company_id' => auth()->user()->company_id,
                    'id' => $existingsms->first()->id
                ])->update([
                    'content_type' => $request->content_type,
                    'authorization' => $request->authorization,
                    'api_url' => $request->smsapiurl,
                    'message_variable' => $request->smsmessage,
                    'name' => $request->smsname,
                    'phone_variable' => $request->smsphone,
                    'request_method_id' => $request->request_type,
                    'sender_variable' => $request->sender_variable,
                    'smscharge' => 0
                ]);
                $state = 'Updated';
            }
            $ra = array("status" => 1, "message" => "SMS Settings $state!");
            return response()->json($ra);
        }
    }


    public function generateKey(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'company_reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            $ra = array("status" => false, "message" => $validator->errors()->all()[0], "data" => []);
            return response()->json($ra, 406);
        }

        $company = Company::where('company_reference', $request->company_reference)->first();

        if ($company) {

            $checkKey = ApiKey::where('company_id', $company->id)->first();

            $apiKey = generateAPIKey(32);

            if (!$checkKey) {

                $companySecret = ApiKey::create([
                    "company_id" => $company->id,
                    "api_key" => $apiKey,
                    "created_by" => 1
                ]);
            } else {
                $checkKey->api_key = $apiKey;
                $checkKey->modified_by = "1";
                $checkKey->save();
            }

            return response()->json(["status" => true, "message" => "API Key generated successfully", "data" => ["api_key" => $apiKey]]);
        } else {
            return response()->json(["status" => false, "message" => "Invalid company reference"], 400);
        }
    }

    public function saveDeveloperSettings(Request $request)
    {

        \logInfo($request->all(), "Save Dev Settings");

        $validator = Validator::make($request->all(), [
            'company_reference' => 'required|string',
            'send_notification' => 'required'
        ]);

        if ($validator->fails()) {
            $ra = array("status" => false, "message" => $validator->errors()->all()[0], "data" => []);
            return response()->json($ra, 406);
        }

        $company = Company::where('company_reference', $request->company_reference)->first();

        if ($company) {

            if ($request->send_notification == 1) {
                if (!$request->has('api_key') || $request->api_key == "") {
                    return \response()->json(['status' => false, 'message' => "API key is required", 'data' => ['api_key' => "error"]]);
                }
                $checkKey = ApiKey::where(['company_id' => $company->id, 'api_key' => $request->api_key])->first();

                if (!$checkKey) {
                    return \response()->json(['status' => false, 'message' => "Invalid API Key Provided", 'data' => ["error" => 'api_key']]);
                }

                if (!$request->has('webhook_url') || $request->webhook_url == "") {
                    return \response()->json(['status' => false, 'message' => "Webhook Url is required", 'data' => ["error" => 'webhook_url']]);
                }

                $body  = [
                    "account_number" => "2384958332",
                    "amount" => "12400",
                    "transaction_reference" => "KD02KDLSKDLSDILSKDS",
                    "narration" => "very important money",
                    "status" => "SUCCESSFUL",
                    "transaction_date" => "12-08-2022 00:12:23"
                ];


                $header = [
                    'Content-Type' => 'application/json',
                    'ApiKey' =>  $checkKey->api_key
                ];

                try {
                    $response = Http::withHeaders($header)->post($request->webhook_url, $body);

                    \logInfo($response->status(), "statussssssssssssssssssssssss");

                    if ($response->status() == 200) {
                        $checkKey->webhook_url = $request->webhook_url;
                        $checkKey->send_notification = $request->send_notification;
                        $checkKey->save();
                        return response()->json(["status" => true, "message" => "Developer Settings Updated Successfully"]);
                    } else {
                        return \response()->json(['status' => false, 'message' => 'Invalid webhook url. Please refer to Sample below']);
                    }
                } catch (Exception $e) {
                    return \response()->json(['status' => false, 'message' => 'Invalid webhook url. Please refer to Sample below']);
                }
            } else {

                $checkKey = ApiKey::where('company_id', $company->id)->first();

                if ($checkKey) {
                    $checkKey->webhook_url = "";
                    $checkKey->send_notification = $request->send_notification;
                    $checkKey->save();
                }
                return response()->json(["status" => true, "message" => "Developer Settings Updated Successfully"]);
            }
        } else {
            return response()->json(["status" => false, "message" => "Invalid company reference"]);
        }
    }


    public function smsAPI_list()
    {
        $smsapi_data = smsapi::where('company_id', '=', auth()->user()->company_id)->orWhere('access_setup', 1)->get();
        $company_settings_data = company_settings::where('company_id', '=', auth()->user()->company_id)->first();
        $selectsms = 0;
        $activesms = 0;
        if ($company_settings_data) {
            $selectsms = $company_settings_data->allsms_state;
            $activesms = $company_settings_data->smsapi_id;
        }
        $smsapi = [];
        if ($smsapi_data) {
            foreach ($smsapi_data as $smsapi_data_list) {
                if ($smsapi_data_list->access_setup == 0) {
                    $smsapi[] = ['id' => $smsapi_data_list->id, 'name' => $smsapi_data_list->name, 'phone_variable' => $smsapi_data_list->phone_variable, 'message_variable' => $smsapi_data_list->message_variable, 'api_url' => $smsapi_data_list->api_url, 'sender_variable' => $smsapi_data_list->sender_variable, 'content_type' => $smsapi_data_list->content_type, 'authorization' => $smsapi_data_list->authorization, 'request_method_id' => $smsapi_data_list->request_method_id, 'smscharge' => $smsapi_data_list->smscharge];
                } else {
                    $smsapi[] = ['name' => $smsapi_data_list->name, 'id' => $smsapi_data_list->id];
                }
            }
        }
        return response()->json(['api' => $smsapi, 'activesms' => $activesms, 'allsms' => $selectsms]);
    }
    public function sms_swap(Request $request)
    {
        // $smsapi_count=smsapi::where('id',$request->id)->where(['company_id'=>auth()->user()->company_id])->orWhere('access_setup',0)->count();
        // if( $smsapi_count > 0){
        $company_settings = company_settings::findorfail(auth()->user()->company->setting->id)->update(['smsapi_id' => $request->id]);
        if ($company_settings) {
            $ra = array("status" => 1, "message" => "SMS Changed!");
            return response()->json($ra);
        }
        // }
        $ra = array("status" => 0, "message" => "SMS Change Failed!");
        return response()->json($ra);
    }
    public function sms_allSMS($company, $id)
    {
        $company_settings = company_settings::findorfail(auth()->user()->company->setting->id);
        $company_settings->allsms_state = $id;
        $company_settings->save(); //update([''=>$id]);
        if ($company_settings) {
            $ra = array("status" => 1, "message" => $company_settings->allsms_state);
            return response()->json($ra);
        }
        $ra = array("status" => 0, "message" => "Operation Failed!");
        return response()->json($ra);
    }
    public function setDomain(Request $request)
    {
        try {
            if ($request->subdomain == null) {
                $ra = array("status" => 0, "message" => "Domain Name field is empty!");
                return response()->json($ra);
            } //
            $company_settings = company_settings::findorfail(auth()->user()->company->setting->id);
            if ($company_settings->subdomain != null) {
                $ra = array("status" => 0, "message" => "Your Account already have a domain and cannot be changed!");
                return response()->json($ra);
            }
            $company_settings_namecheck = company_settings::where('subdomain', $request->subdomain)->count();
            if ($company_settings_namecheck > 0) {
                $ra = array("status" => 0, "message" => "Domain Name not available!" . $company_settings_namecheck);
                return response()->json($ra);
            }
            $amount = ($request->domain_type == 1) ? config('app.subdomainprice') : config('app.maindomainprice');
            $company_wallet = company_wallet::where('company_id', auth()->user()->company_id)->first();
            if ($company_wallet->balance  < $amount) {
                $ra = array("status" => 0, "message" => "Insufficient Fund");
                return response()->json($ra);
            }
            $company_settings->subdomain = $request->subdomain;
            $company_settings->domain_type = $request->domain_type;
            $balanceBefore = $company_wallet->balance;
            $company_wallet->balance -= $amount;
            $txnType = ($request->domain_type == 1) ? 90 : 89;
            $transactionId = generate_txnID();
            $data = [
                "balance_before" => $balanceBefore,
                "amount" => $amount,
                "withdrawal_charge" => 0,
                "description" => "Domain Activation",
                "branch_id" => auth()->user()->branch_id,
                "transaction_reference" => $transactionId,
                "transaction_type_id" => $txnType, //debit 7
                "credit_debit_type_id" => 2, //debit 2
                "transaction_remarks_id" => 1, //success 2
                "balance_after" => $company_wallet->balance,
                "created_date"  => date('Y-m-d H:i:s'),
                "created_by" => auth()->user()->id,
                "company_id" => auth()->user()->company_id,
            ];
            $transaction = transactions::create($data);
            $company_wallet->save();
            $company_settings->save();
            $ra = array("status" => 1, "message" => "Domain Registered Successfully");
            return response()->json($ra);
        } catch (Exception $e) {
            $ra = array("status" => 0, "message" => "Operation Failed" . $e->getMessage());
            return response()->json($ra);
        }
    }
    public function saveSettlement(Request $request)
    {
        // company_settings
        $validation = Validator::make($request->all(), [
            "maximum_balance" => 'required|numeric',
            "bank" => 'required|numeric',
            "pin" => 'required|numeric|digits:4',
            "accountNumber" => 'required|numeric|digits:10',
            "minimum_balance_threshold" => 'required|numeric',
            "accountName" => "required"
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
        if ($auth->company->transaction_pin != $request->pin) {
            return response()->json(["status" => 0, "message" => "Invalid Pin"]);
        }
        $CompanySettlement = CompanySettlement::where('company_id', $auth->company_id)->first();
        $CompanySettlement = ($CompanySettlement) ? $CompanySettlement : new CompanySettlement;
        $CompanySettlement->company_id = $auth->company_id;
        $CompanySettlement->bank_id = $request->bank;
        $CompanySettlement->accountNumber = $request->accountNumber;
        $CompanySettlement->minimum_balance_threshold = $request->minimum_balance_threshold;
        $CompanySettlement->maximum_balance = $request->maximum_balance;
        $CompanySettlement->updated_by = $auth->id;
        $CompanySettlement->accountName = $request->accountName;

        $CompanySettlement->save();
        return response()->json(["status" => 1, "message" => "Settlement Saved"]);
    }
    public function saveTwoFactor(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "authfactorsetup" => 'required',
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
        $company_settings_id =  auth()->user()->company_id;
        $company_settings = company_settings::where('company_id', $company_settings_id)->first();
        $company_settings->authfactorsetup = $request->authfactorsetup;
        $company_settings->save();
        if($company_settings->authfactorsetup == 2){
            User::where(['company_id'=>auth()->user()->company_id])->update(['google2fa_secret'=>' ']);
        }

        return response()->json(["status" => 1, "message" => "Security Update"]);
    }
//
 //
        //  MAIL_MAILER=smtp
        //     MAIL_HOST=wiredbanking.ng
        //     MAIL_PORT=465
        //     MAIL_USERNAME=no-reply@wiredbanking.ng
        //     MAIL_PASSWORD=SecurePublic@12300
        //     MAIL_ENCRYPTION=ssl
        //     MAIL_FROM_ADDRESS=no-reply@wiredbanking.ng
        //     MAIL_FROM_NAME="${APP_NAME}"
public function updateSettingsMail(Request $request){
    $validation = Validator::make($request->all(), [
        "username_email" => 'required',
        "username_password" => 'required',
        "mail_from" => 'required',
        "mail_host"=>'required',
        "mail_host_port"=>'required'
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
    $company_settings_currency = CompanyEmail::where('company_id', auth()->user()->company_id)->first();

    $company_settings=($company_settings_currency)?$company_settings_currency:new CompanyEmail;
    $company_settings->company_id=auth()->user()->company_id;
    $company_settings->uuid = uniqid();
    $company_settings->MAIL_MAILER = 'smtp';
    $company_settings->MAIL_HOST = $request->input('mail_host');
    $company_settings->MAIL_PORT = $request->input('mail_host_port');
    $company_settings->MAIL_USERNAME = $request->input('username_email');
    $company_settings->MAIL_PASSWORD = $request->input('username_password');
    $company_settings->MAIL_FROM_NAME = $request->input('mail_from');
    $company_settings->MAIL_ENCRYPTION = $request->input('mail_encryption');
    $company_settings->MAIL_FROM_ADDRESS =$request->input('username_email');
    $company_settings->save();
    return response()->json(["status" => 1, "message" => "Email Updated"]);
}
public function updateSettingsReferral(Request $request){
    $validation = Validator::make($request->all(), [
        "direct_comission" => 'required',
        "credit_method" => 'required',
        "indirect_comission" => 'required',
    ]);
    if ($validation->fails()) {
        $errors = '';
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            foreach ($messages as $message) {
                $errors .= $message . ' ';
            }
        }
        $ra = array("status" => 0, "message" => "$errors");
        return response()->json($ra);
    }
    $company_settingsRef= CustomerReferalSetting::where('company_id', auth()->user()->company_id)->first();
    $company_settings=($company_settingsRef)?$company_settingsRef:new CustomerReferalSetting;
    $company_settings->company_id=auth()->user()->company_id;
    $company_settings->direct_comission = $request->input('direct_comission');
    $company_settings->credit_method = $request->input('credit_method');
    $company_settings->indirect_comission = $request->input('indirect_comission');
    $company_settings->save();
    return response()->json(["status" => 1, "message" => "Referral Updated!"]);
}
public function Transfer($page){
    if($page=='staff'){
        // User::where(['company_id' => auth()->user()->company_id, 'id' => $id, 'user_type_id' => 2])
    }else if($page=='branch'){

    }
}
}

