<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\banks;
use App\Models\branch;
use App\Models\Company;
use App\Models\savings_product;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function branch(Request $request)
    {
        try {
            $companyRecord = Company::where(['company_reference' => $request->company])->first();
            return response()->json(['data' => branch::with('currency')->where('company_id', $companyRecord->id)->get(), 'status' => true]);
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }
    public function product(Request $request)
    {
        try {
            $companyRecord = Company::where(['company_reference' => $request->company])->first();
            return response()->json(['data' => $companyRecord->savingsProducts, 'status' => true]);
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
        // return User::all();
    }

    public function register(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'required|string',
            'email'      => 'required|email|string',
            'dob'        => 'required|date',
            'username'   => 'required|string',
            'company'    => 'required|string',
            'pin'        => 'required|numeric|gt:0',
            'password'   => 'required|string',
        ];
        if ($request->has('nin')) {
            $rules['nin'] = 'required|string';
        }
        if ($request->has('bvn')) {
            $rules['bvn'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->nin) {
            $userNIN = User::where('nin', $request->nin)->get();
            if (count($userNIN) > 0) {
                return response()->json(["status" => false, "message" => "NIN already exist!"], 409);
            }
        }
        if ($request->bvn) {
            $userNIN = User::where('bvn', $request->bvn)->get();
            if (count($userNIN) > 0) {
                return response()->json(["status" => false, "message" => "BVN already exist!"], 409);
            }
        }
        // if (!empty($request->nin) && $request->nin === "") {
        //     $ra = array("status" => false, "message" => "NIN is required!");
        //     return response()->json($ra, 406);
        // }

        // if(isset($request->nin)){
        // if($request->nin==""){
        //     $ra = array("status" => false, "message" => "NIN is required!");
        //     return response()->json($ra, 406);
        // }

        // }
        // if(isset($request->bvn)){
        // if($request->bvn==""){
        //     $ra = array("status" => false, "message" => "BVN is required!");
        //     return response()->json($ra, 406);
        // }
        // }
        $companyRecord = Company::where(['company_reference' => $request->company])->orWhere(['id' => $request->company])->first();
        if (! $companyRecord || $companyRecord == 27 ) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }

        $savings_product = $companyRecord->savingsProducts[0];
        $selectedProduct = $savings_product;

        if (! ($selectedProduct)) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return response()->json($ra, 400);
        }

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            // return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }
        $userPhone = $request->phone;

        $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Email already exists"];
            return response()->json($ra, 409);
        }

        $user = User::where([['phone', $userPhone], ['company_id', $companyRecord->id]])->get();
        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Phone number already exists"];
            return response()->json($ra, 409);
        }

        $user = User::where('username', $request->username)->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Username already exiss"];
            return response()->json($ra, 409);
        }

        \logInfo($user, "Current user");

        // $customerReference = generateAccountReference();

        // $assetMatrix = new AssetMatrixController();

        // $response = $assetMatrix->createAccount($customerReference, $request->email, $request->phone, $request->first_name, $request->last_name, "");
        // echo \json_encode($createAdminAccount);

        $bank_id      = $companyRecord->setting->preferred_bank_id ?? 126;
        $selectedBank = banks::where('id', $bank_id)->first();
        if (! isset($request->account_type) || $request->account_type != 'regular') {
            $virtualAccount = new VirtualAccountType();

            \logInfo($bank_id, "Register Bank Id ");
            \logInfo($selectedBank->bank_code, "Register Bank code ");

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
        }
        $branch = $request->branch ?? $companyRecord->branch[0]->id;
        // if($request->branch){
        $branchData = (branch::where('id', $request->branch)->first());
        // }
        // logInfo($response, "Create Account service response");
        $customerReference = $response['data']['account_reference'] ?? rand(1000000000, 9999999999);

        $virtual_acccount_number = (! isset($request->account_type) || $request->account_type != 'regular') ? $response['data']['virtual_account'] : rand(1000000000, 9999999999);
        if ($request->selected_product) {
            $savings_product = savings_product::Where('id', $request->selected_product)->first();
            $selectedProduct = $savings_product;
            if (! $savings_product) {
                $ra = ["status" => false, "message" => "Product doesn't exist!"];
                return response()->json($ra, 400);
            }
            if (isset($request->account_type) && $request->account_type == 'regular') {

                if (isset($savings_product->code)) {
                    if ($savings_product->code != null) {
                        $length                  = strlen($savings_product->code);
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $savings_product->code, 0, $length);
                    }
                }
            }
        }
        if (isset($request->account_type) && $request->account_type == 'regular') {

            if (isset($branchData->code)) {
                if ($branchData->code != null) {
                    $branchCodeLength = strlen($branchData->code);
                    $branchCodeLength -= 1;
                    $virtual_acccount_number = substr_replace($virtual_acccount_number, $branchData->code, -$branchCodeLength);
                }
            }
        }

        $accountOfficer = User::where('company_id', $companyRecord->id)->first();
        //
        //  GENERATE AND SEND OTP TO USER HERE
        //
        $otp = generateOtp();
        // $otp = '7777';
        DB::beginTransaction();

        try {
            // referral_id
            $dataraw = [
                'first_name'                   => $request->first_name,
                'last_name'                    => $request->last_name,
                'company_id'                   => $companyRecord->id,
                'email'                        => $request->email,
                'bvn'                          => $request->bvn,
                'phone'                        => $userPhone,
                'otp'                          => $otp,
                'nin'                          => $request->nin ?? null,
                'bank_id'                      => $bank_id ?? 126,
                'pin'                          => $request->pin,
                'address'                      => $request->address,
                'dob'                          => $request->dob,
                'sms'                          => 1,
                'kyc_level_id'                 => 1,
                'status_id'                    => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                'branch_id'                    => $branch,
                'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                'user_type_id'                 => 1,
                'phone_verification_status_id' => 1,
                'virtual_account'              => $virtual_acccount_number,
                'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                'account_reference'            => $customerReference,
                'username'                     => $request->username,
                'account_officer_id'           => $accountOfficer->id,
                'uuid'                         => $customerReference,
                'password'                     => Hash::make($request->password),
                'phone_verification_status_id' => (isset($branchData->currency_id)) ? (($branchData->currency_id == 1) ? null : 1) : null,
                //
            ];

            if ($request->referral) {
                $refeUser = User::where(['company_id' => $companyRecord->id, 'username' => $request->referral])->first();
                if (! $refeUser) {
                    return response()->json(['status' => false, 'message' => 'Invalid referral username']);
                }
                $dataraw['referral_id'] = $refeUser->id;
            }

            $user = User::create($dataraw);
            if (! $user) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration Err::09'], 400);
            }

            $wb_account_details = wb_account_details::create([
                'user_id'            => $user->id,
                'account_product_id' => $selectedProduct->id,
                'bankid'             => $bank_id ?? 126,
                'virtual_accnum'     => $virtual_acccount_number,
                'acc_reference'      => $customerReference,
                'has_atm_card'       => 0,
                'currency_id'        => $branchData->currency_id ?? 1,
            ]);
            if (! $wb_account_details) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration Err::10'], 400);
            }

            $user_wallet = user_wallet::create([
                'wb_account_detail_id' => $wb_account_details->id,
                'balance'              => 0,
            ]);
            if (! $user_wallet) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration Err::11'], 400);
            }

            \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

            $bank = banks::where('id', $bank_id)->first();

            if (! $bank) {
                DB::rollBack();
                response()->json(['status' => false, 'message' => 'Invalid bank selected']);
            }

            // if ($user && $user_wallet) {
            $user->assignRole([4]);

            $accountNumber = $virtual_acccount_number;

            $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";

            // $welcomeMessage = "Welcome FirstName! Your account number is 5080135881. Bank Name: Asset Matrix MFB.  upload your Valid ID and link BVN. Have Access via USSD. Simply dial *7006*10#. T&Cs applyEnjoy 24/7.";
         //             sendSMS($userid, $msg, $type = true, $charge_customer = true, $title = 'Create Account', $accno = '', $usesms = 0, $isApi = false, $all_sms_email = 1, $sms_logdata = [])
            $smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00%23 To verify your phone from the registered phone number", true, false, 'Verify OTP', '');
            $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);
            // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);

            \logInfo($smsstate, "SMS response");

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Registration Successful',
                'data'    => [
                    'user_reference' => $customerReference,
                    'account_number' => $accountNumber,
                    'bank'           => $bank->bank_name,
                ],
            ]);

        } catch (\Exception $e) {

            \logInfo($e->getTraceAsString(), "Registration failure");
            DB::rollback();
            // something went wrong
            return response()->json(['status' => false, 'message' => 'Failed to complete Registration', 'data' => $e->getMessage() . ' ' . $e->getLine()], 400);
        }

    }

    public function ussdRegister(Request $request)
    {

        logInfo($request->all(), "Register Request log");

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'string',
            'email'      => 'email|string',
            'dob'        => 'date',
            'company'    => 'required|string',
        ]);

        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (! $companyRecord) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }
        $selectedProduct = $companyRecord->savingsProducts[0];

        if (! ($selectedProduct)) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }

        // $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // if ($companyRecord->country->phone_code == "234") {
        //     $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // } else {
        //     $userPhone = $request->phone;
        // }
        $userPhone = $request->phone;

        // $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->orwhere([['phone', $userPhone], ['company_id', $companyRecord->id]])->orwhere('username', $request->username)->get();

        // \logInfo($user, "Current user");

        // if (count($user) > 0) {
        //     $ra = array("status" => false, "message" => "User is already existing");
        //     return response()->json($ra, 409);
        // }

        if ($validator->passes()) {

            $bank_id = $companyRecord->setting->preferred_bank_id ?? 126;

            logInfo($bank_id, "Preferred bank");

            $selectedBank = banks::find($bank_id);

            $virtualAccount = new VirtualAccountType();
            logInfo($selectedBank->bank_code, "Preferred bank code");

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            logInfo($response, "Create Account service response");
            $customerReference       = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];

            if ($response["status"]) {
                // if (true) {

                $accountOfficer = User::where('company_id', $companyRecord->id)->first();
                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name'                   => $request->first_name,
                        'last_name'                    => $request->last_name,
                        'company_id'                   => $companyRecord->id,
                        'email'                        => $request->email,
                        'bvn'                          => $request->bvn,
                        'phone'                        => $userPhone,
                        'otp'                          => $otp,
                        'bank_id'                      => $bank_id ?? 126,
                        'pin'                          => $request->pin,
                        'dob'                          => $request->dob,
                        'sms'                          => 1,
                        'kyc_level_id'                 => 1,
                        'status_id'                    => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                        'branch_id'                    => $companyRecord->branch[0]->id,
                        'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id'                 => 1,
                        'phone_verification_status_id' => 1,
                        'virtual_account'              => $virtual_acccount_number,
                        'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                        'account_reference'            => $customerReference,
                        'username'                     => $request->username,
                        'account_officer_id'           => $accountOfficer->id,
                        'uuid'                         => $customerReference,
                        'password'                     => Hash::make($request->password),
                    ]);

                    $regfee = $companyRecord->setting->registration_fee;

                    $wb_account_details = wb_account_details::create([

                        'user_id'            => $user->id,
                        'account_product_id' => $selectedProduct->id,
                        'bankid'             => $bank_id ?? 126,
                        'virtual_accnum'     => $virtual_acccount_number,
                        'acc_reference'      => $customerReference,
                        'has_atm_card'       => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance'              => (0 - $regfee),
                    ]);

                    \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

                    $bank = banks::where('id', $bank_id)->first();

                    if (! $bank) {
                        response()->json(['status' => false, 'message' => 'Invalid bank selected']);
                    }

                    if ($user && $user_wallet) {
                        $user->assignRole([4]);

                        $accountNumber = $virtual_acccount_number;

                        $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";

                        // $welcomeMessage = "Welcome FirstName! Your account number is 5080135881. Bank Name: Asset Matrix MFB.  upload your Valid ID and link BVN. Have Access via USSD. Simply dial *7006*10#. T&Cs applyEnjoy 24/7.";

                        $smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00%23 To verify your phone from the registered phone number", true, false, 'Verify OTP', '', 0, false);
                        $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);

                        // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);

                        \logInfo($smsstate, "SMS response");

                        DB::commit();

                        return response(['status' => true, 'message' => 'Registration Successful', 'data' => ['account_number' => $virtual_acccount_number, 'user_reference' => $customerReference]]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                    }
                } catch (\Exception $e) {

                    \logInfo($e->getTraceAsString(), "Registration failure");
                    DB::rollback();
                    // something went wrong
                    return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration', 'data' => $response], 400);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            // return back()->with('fail', $errors);
        }
    }

    public function apiRegister(Request $request)
    {

        logInfo($request->all(), "Register Request log");

        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:50|min:2',
            'last_name'      => 'required|string|max:50|min:2',
            'phone'          => 'required|string',
            'preferred_bank' => 'required',
            'email'          => 'required|email|string',
            'dob'            => 'required|date',
            'company'        => 'required|string',
        ]);

        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (! $companyRecord) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }

        if (count($companyRecord->branch) < 1) {
            $ra = ["status" => false, "message" => "Kindly setup your company branch to be able to acesss API. or contact Wired banking support for assistance tech@wbalite.com"];
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }

        $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);

        $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->get();

        \logInfo($user, "Current user");

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "User is already existing"];
            return response()->json($ra, 409);
        }

        if ($validator->passes()) {

            $bank_id = $companyRecord->setting->preferred_bank_id ?? 126;

            logInfo($bank_id, "Preferred bank");

            $selectedBank = banks::find($request->preferred_bank);

            if (! $selectedBank) {
                $ra = ["status" => false, "message" => "Invalid bank code. Kindly provide a valid bank code to proceed"];
                return response()->json($ra, 409);
            }

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            if ($selectedBank->bank_code == '090286') {
                $virtualAccount->bvnId = $request->identity_id;
                $virtualAccount->otp   = $request->otp;
            }

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            logInfo($response, "Create Account service response");
            $customerReference       = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];

            // echo \json_encode($createAdminAccount);

            if ($response["status"]) {

                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name'                   => $request->first_name,
                        'last_name'                    => $request->last_name,
                        'company_id'                   => $companyRecord->id,
                        'email'                        => $request->email,
                        'bvn'                          => $request->bvn,
                        'phone'                        => $userPhone,
                        'otp'                          => $otp,
                        'bank_id'                      => $request->preferred_bank,
                        'pin'                          => "0000",
                        'dob'                          => $request->dob,
                        'sms'                          => 1,
                        'kyc_level_id'                 => 1,
                        'status_id'                    => 7,
                        'branch_id'                    => $companyRecord->branch[0]->id,
                        'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id'                 => 4,
                        'phone_verification_status_id' => 1,
                        'virtual_account'              => $virtual_acccount_number,
                        'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                        'account_reference'            => $customerReference,
                        'username'                     => strtolower($request->first_name) . "" . $otp,
                        'uuid'                         => $customerReference,
                        'password'                     => "00000000",
                    ]);

                    $wb_account_details = wb_account_details::create([

                        'user_id'            => $user->id,
                        'account_product_id' => 1,
                        'bankid'             => $request->preferred_bank,
                        'virtual_accnum'     => $virtual_acccount_number,
                        'acc_reference'      => $customerReference,
                        'has_atm_card'       => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance'              => 0,
                    ]);

                    if ($user && $user_wallet) {
                        $user->assignRole([4]);

                        $accountNumber = $virtual_acccount_number;

                        $welcomeMessage = "Welcome $request->username Account No: $accountNumber. Kindly upload your Valid ID & Recent Utility bill. USSD Access: *7006*10#. T&Cs apply";

                        // $sendSMS = \sendVansoSMS($userPhone, "Welcome to Asset Matrix. Your OTP is $otp");
                        // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);
                        $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);

                        \logInfo($smsstate, "SMS response");

                        DB::commit();

                        return response([
                            'status'  => true,
                            'message' => 'Registration Successful',
                            'data'    => [
                                'account_number' => $accountNumber,
                                'account_name'   => "$request->first_name $request->last_name",
                                'bank_name'      => $selectedBank->bank_name,
                                'bank_code'      => $selectedBank->bank_code,
                            ],
                        ]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                    }
                } catch (\Exception $e) {

                    \logInfo($e->getTraceAsString(), "Registration failure");
                    DB::rollback();
                    // something went wrong
                    return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration', 'data' => $createAdminAccount], 400);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            // return back()->with('fail', $errors);
        }

        // return response(['status' => false, 'message' => 'Failed to register']);
    }
}
