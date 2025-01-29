<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Models\banks;
use App\Models\Company;
use App\Models\sub;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;


class RegisterController extends Controller
{
    public function register(Request $request)
    {

        logInfo($request->all(), "Register Request log");;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name' => 'required|string|max:50|min:2',
            'phone' => 'required|string',
            'email' => 'required|email|string',
            'dob' => 'required|date',
            'username' => 'required|string',
            'company' => 'required|string',
            'pin' => 'required|numeric|gt:0',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            $ra = array("status" => false, "message" => $validator->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (!$companyRecord) {
            $ra = array("status" => false, "message" => "Invalid company provided");
            return response()->json($ra, 400);
        }

        if (count($companyRecord->savingsProducts) < 1) {
            $ra = array("status" => false, "message" => "No Savings product found for this company");
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = array("status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system");
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = array("status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later");
            return response()->json($ra, 400);
        }

        // $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // if ($companyRecord->country->phone_code == "234") {
        //     $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // } else {
        //     $userPhone = $request->phone;
        // }
        $userPhone = $request->phone;

        $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->orwhere([['phone', $userPhone], ['company_id', $companyRecord->id]])->orwhere('username', $request->username)->get();

        \logInfo($user, "Current user");

        if (count($user) > 0) {
            $ra = array("status" => false, "message" => "User is already existing");
            return response()->json($ra, 409);
        }

        if ($validator->passes()) {

            // $customerReference = generateAccountReference();

            // $assetMatrix = new AssetMatrixController();

            // $response = $assetMatrix->createAccount($customerReference, $request->email, $request->phone, $request->first_name, $request->last_name, "");
            // echo \json_encode($createAdminAccount);

            $bank_id = $companyRecord->setting->preferred_bank_id ?? 126;

            logInfo($bank_id, "Preferred bank");

            if ($bank_id == 126) {

                $kudaController = new KudaController();

                $requestReference = generateAccountReference();
                $trackingReference = generateAccountReference();

                $response = $kudaController->createAccount($requestReference, $request->email, $request->phone, $request->first_name, $request->last_name, $trackingReference);


                if (!$response['status']) {
                    return response()->json($response);
                }

                $customerReference = $trackingReference;

                $virtual_acccount_number = $companyRecord->currency->code == "NGN" ? $response['data']['account_number'] : $response['data']['account_number'] . "_";
            } else {

                $wovenController = new WovenController();

                $customer_reference = generateAccountReference();

                $response = $wovenController->createCustomerAccount("$request->first_name $request->last_name", $request->email, $request->phone, $bank_id, $customer_reference);

                if (!$response['status']) {
                    return response()->json($response);
                }

                $customerReference = $customer_reference;

                $virtual_acccount_number = $companyRecord->currency->code == "NGN" ? $response['data']['vnuban'] : $response['data']['vnuban'] . "_";
            }

            if ($response["status"]) {
                // if (true) {

                $accountOfficer = User::where('company_id',  $companyRecord->id)->first();
                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'company_id' => $companyRecord->id,
                        'email' => $request->email,
                        'bvn' => $request->bvn,
                        'phone' => $userPhone,
                        'otp' => $otp,
                        'bank_id' => $bank_id ?? 126,
                        'pin' => $request->pin,
                        'dob' => $request->dob,
                        'sms' => 1,
                        'kyc_level_id' => 1,
                        'status_id' => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                        'branch_id' => $companyRecord->branch[0]->id,
                        'profile_pic' => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id' => 1,
                        'phone_verification_status_id' => 1,
                        'virtual_account' => $virtual_acccount_number,
                        'otp_expiration_date' => Carbon::now()->addMinutes(10),
                        'account_reference' => $customerReference,
                        'username' => $request->username,
                        'account_officer_id' => $accountOfficer->id,
                        'uuid' => $customerReference,
                        'password' => Hash::make($request->password),
                    ]);


                    $wb_account_details = wb_account_details::create([

                        'user_id' => $user->id,
                        'account_product_id' => $companyRecord->savingsProducts[0]->id,
                        'bankid' => $bank_id ?? 126,
                        'virtual_accnum' => $virtual_acccount_number,
                        'acc_reference' => $customerReference,
                        'has_atm_card' => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance' => 0
                    ]);

                    \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

                    $bank = banks::where('id', $bank_id)->first();

                    if (!$bank) {
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

                        return response(['status' => true, 'message' => 'Registration Successful', 'data' => ['user_reference' => $customerReference]]);
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

        logInfo($request->all(), "Register Request log");;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name' => 'required|string|max:50|min:2',
            'phone' => 'required|string',
            'email' => 'required|email|string',
            'dob' => 'required|date',
            'preferred_bank' => 'required|in:45,222,194',
            'company' => 'required|string'
        ]);

        if ($validator->fails()) {
            $ra = array("status" => false, "message" => $validator->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (!$companyRecord) {
            $ra = array("status" => false, "message" => "Invalid company provided");
            return response()->json($ra, 400);
        }

        if (count($companyRecord->branch) < 1) {
            $ra = array("status" => false, "message" => "Kindly setup your company branch to be able to acesss API. or contact Wired banking support for assistance tech@wbalite.com");
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = array("status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system");
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = array("status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later");
            return response()->json($ra, 400);
        }

        $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);

        $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->orwhere([['phone', $userPhone], ['company_id', $companyRecord->id]])->get();

        \logInfo($user, "Current user");

        if (count($user) > 0) {
            $ra = array("status" => false, "message" => "User is already existing");
            return response()->json($ra, 409);
        }

        if ($validator->passes()) {

            $prefBank = banks::where('id', $request->preferred_bank)->first();

            $customerReference = generateAccountReference();

            $wovenController = new WovenController();

            $accountName = $request->first_name . ' ' . $request->last_name;

            $createAdminAccount = $wovenController->createCustomerAccount($accountName, $request->email, $request->phone, $prefBank->bank_code, $customerReference);

            if (!$createAdminAccount['status']) {
                return \response()->json($createAdminAccount);
            }

            $virtual_acccount_number = $companyRecord->currency->code == "NGN" ? $createAdminAccount['data']['vnuban'] : $createAdminAccount['data']['vnuban'] . "_";

            // echo \json_encode($createAdminAccount);

            if ($createAdminAccount["status"]) {

                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'company_id' => $companyRecord->id,
                        'email' => $request->email,
                        'bvn' => $request->bvn,
                        'phone' => $userPhone,
                        'otp' => $otp,
                        'bank_id' => $request->preferred_bank,
                        'pin' => "0000",
                        'dob' => $request->dob,
                        'sms' => 1,
                        'kyc_level_id' => 1,
                        'status_id' => 7,
                        'branch_id' => $companyRecord->branch[0]->id,
                        'profile_pic' => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id' => 4,
                        'phone_verification_status_id' => 1,
                        'virtual_account' => $virtual_acccount_number,
                        'otp_expiration_date' => Carbon::now()->addMinutes(10),
                        'account_reference' => $customerReference,
                        'username' => strtolower($request->first_name) . "" . $otp,
                        'uuid' => $customerReference,
                        'password' => "00000000"
                    ]);


                    $wb_account_details = wb_account_details::create([

                        'user_id' => $user->id,
                        'account_product_id' => 1,
                        'bankid' => $request->preferred_bank,
                        'virtual_accnum' => $virtual_acccount_number,
                        'acc_reference' => $customerReference,
                        'has_atm_card' => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance' => 0
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
                            'status' => true,
                            'message' => 'Registration Successful',
                            'data' => [
                                'account_number' => $accountNumber,
                                'account_name' => $accountName,
                                'bank_name' => $createAdminAccount['data']['bank_name'],
                                'bank_code' => $createAdminAccount['data']['bank_code']
                            ]
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
