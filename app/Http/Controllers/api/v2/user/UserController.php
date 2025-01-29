<?php

namespace App\Http\Controllers\api\v2\user;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\CarouselImage;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\UserCard;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        \logInfo($request->all(), "index request log");

        try {
            return response()->json($request->user());
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
        // return User::all();
    }
    //
    public function getDetails(Request $request)
    {
        try {

            $userDetails = [];

            // return $request->user();

            if ($request->user()->user_type_id == 1) {

                $userWallet = user_wallet::where('wb_account_detail_id', $request->user()->accounts->first()->id)->first();

                $myUserWallets = [];

                foreach ($request->user()->accounts as $key => $wallet) {
                    $userWallet = user_wallet::where('wb_account_detail_id', $wallet->id)->first();

                    $myUserWallets[] = array("account_id" => $wallet->id, "account_number" => $wallet->virtual_accnum, "bank_id" => $wallet->bank->id, "bank" => $wallet->bank->bank_name, 'product_name' => $wallet->accountType->savings_product, "wallet_balance" => $userWallet->balance);
                }

                $virtualCard = UserCard::where('user_id', auth()->user()->id)->get();

                $sliders = CarouselImage::where('status_id', 1)->get("image_url");


                if (!$virtualCard) {
                    $virtualCard = [];
                }

                $userDetails = [
                    "first_name" => $request->user()->first_name,
                    "last_name" => $request->user()->last_name,
                    "phone" => $request->user()->phone,
                    "profile_pic" => $request->user()->profile_pic,
                    "username" => $request->user()->username,
                    "bvn" => $request->user()->bvn,
                    "bvn_status_id" => $request->user()->bvn_status_id,
                    "nin" => $request->user()->nin,
                    "dob" => $request->user()->dob,
                    "signature" => $request->user()->signature,
                    "email" => $request->user()->email,
                    "address" => $request->user()->address,
                    "sex" => $request->user()->sex,
                    "otp" => $request->user()->otp,
                    "virtual_account" => $request->user()->virtual_account,
                    "account_reference" => $request->user()->account_reference,
                    "bank_id" => $request->user()->bank_id,
                    "validID_file" => $request->user()->validID_file,
                    "otherID_file" => $request->user()->otherID_file,
                    "company_id" => $request->user()->company_id,
                    "status_id" => $request->user()->status_id,
                    "phone_verification_status_id" => $request->user()->phone_verification_status_id,
                    "account_officer_id" => $request->user()->account_officer_id,
                    "branch_id" => $request->user()->branch_id,
                    "balance" => $userWallet->balance,
                    "wallets" => $myUserWallets,
                    "cards" => $virtualCard,
                    "sliders" => $sliders,
                    "is_card_available" => count($virtualCard) > 0,
                    "accounts" => wb_account_details::with('user_wallet')->with('accountType')->where('user_id', $request->user()->id)->get(), //$request->user()->wb_account_details,
                ];

                return response()->json(['status' => true, 'message' => 'User Details Fetched successfully', 'data' => $userDetails]);
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to fetch user details', 'data' => $userDetails]);
            }
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
        // return User::all();
    }


    public function uploadFile(Request $request)
    {
        \logInfo($request->all(), "uploadFile request log");

        $validate = Validator::make($request->all(), [
            'image_file' => 'required|mimes:jpg,jpeg,png|file|max:512',
            'type' => 'required',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = $request->user();

        $pathLocation = "";

        if ($request->type == "passport") {
            $pathLocation = "passports";
        } else if ($request->type == "valid_id") {
            $pathLocation = "valid_ids";
        } else if ($request->type == "signature") {
            $pathLocation = "signatures";
        } else {
            $pathLocation = "other_doc";
        }

        $result = $request->file("image_file")->store($pathLocation);

        // $request->image->move(public_path("images") , $imageName) // Store file in public folder
        if ($result) {

            $imageUrl = \url("wba/storage/app/" . $result);

            // return $result;
            // if ($request->type == "passport") {
            //     $userAccunt->profile_pic = $imageUrl;
            // } else if ($request->type == "valid_id") {
            //     $userAccunt->validID_file = $imageUrl;
            // } else if ($request->type == "signature") {
            //     $userAccunt->signature = $imageUrl;
            // } else {
            //     $userAccunt->otherID_file = $imageUrl;
            // }

            // $userAccunt->save();

            return response()->json(["status" => true, "message" => "Image Upload successful",  "data" => ["image_url" => \url("wba/storage/app/" . $result)]]);
        } else
            return response()->json(["status" => false, "message" => "Failed to upload image"], 400);
    }

    public function verifyOTP(Request $request)
    {
        \logInfo($request->all(), "verifyOTP request log");

        logInfo($request->all());;

        $validate = Validator::make($request->all(), [
            'user_reference' => 'required',
            'otp_code' => 'required|numeric|gt:0',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => "Otp Code and user reference are required");
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'otp' => $request->otp_code])->first();

        if ($userAccunt) {

            $newdate = Carbon::now();

            if (Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date)) > Carbon::now()) {

                $userAccunt->phone_verification_status_id = 2;
                $userAccunt->last_login = Carbon::now();
                $userAccunt->save();

                return response(['status' => true, 'message' => 'OTP Verification Successful']);
            } else {
                return response(['status' => false, 'message' => 'You OTP has Expired', 'data' => "Expiration: " . Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date))->format('d-m-Y / h:i:s A') . " ***** CurrentDate: " . Carbon::now()->format('d-m-Y / h:i:s A')]);
            }
        } else {
            return response(['status' => false, 'message' => "Invalid user reference or OTP supplied"]);
        }
    }

    public function confirmOTP(Request $request)
    {
        \logInfo($request->all(), "confirmOTP request log");

        logInfo($request->all());;

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'otp_code' => 'required|numeric|gt:0',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => "Otp Code and Phone number are required");
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['phone' => $request->phone_number, 'otp' => $request->otp_code])->first();

        if ($userAccunt) {

            $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

            if ($userAccunt->otp_expiration_date < $date) {

                return response(['status' => true, 'message' => 'OTP Verification Successful']);
            } else {
                return response(['status' => false, 'message' => 'Invalid or Expired OTP']);
            }
        } else {
            return response(['status' => false, 'message' => 'Invalid Phone number or OTP supplied']);
        }
    }

    public function restrictUserAccount(Request $request)
    {
        \logInfo($request->all(), "restrict request log");

        $validate = Validator::make($request->all(), [
            'user_id' => 'required|numeric|gt:0',
            'remark' => 'required|string',
            'restriction_type' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = User::find($request->user_id);

        if ($userAccunt) {

            $restrictUserAccount = \restrictUserAccountAPI($request->user_id, $request->restriction_type, $request->remark);

            return \response()->json($restrictUserAccount);
        } else {
            return response(['status' => false, 'message' => 'Invalid User Id']);
        }
    }
    public function resetPin(Request $request){

        $validate = Validator::make($request->all(), [
            'current_pin' => 'required|max:4|min:4',
            'new_pin' => 'required|max:4|min:4',
        ]);
        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }
        if($request->new_pin == $request->current_pin){
            $ra = array("status" => false, "message" => "New Pin cannot be same as Current Pin");
            return response()->json($ra, 406);
        }
        if (auth()->user()->pin == $request->current_pin) {
            User::where('id',auth()->user()->id)->update(['pin'=>$request->new_pin]);
            $ra = array("status" => true, "message" => "Pin Reset Successful");
            return response()->json($ra, 200);
        }else{
            $ra = array("status" => false, "message" => "Invalide Pin");
            return response()->json($ra, 406);
        }
    }

    public function getVirtualAccounts(Request $request)
    {
        \logInfo($request->all(), "restrict request log");

        $perPage = $request->input('limit', 10);
        if (!is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $customers = DB::table('users')
            ->select('users.virtual_account', 'users.account_reference', 'banks.bank_name',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS account_name"), 'user_wallets.balance')
            ->join('banks', 'banks.id', '=', 'users.bank_id')
            ->join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
            ->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Customers Fetched successfully', 'data' => $customers]);
    }

    public function resetPassword(Request $request)
    {
        \logInfo($request->all(), "resetPassword request log");

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'otp_code' => 'required|numeric|gt:0',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => "Otp Code and Phone number are required");
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['phone' => $request->phone_number, 'otp' => $request->otp_code])->first();

        if ($userAccunt) {

            $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

            if ($userAccunt->otp_expiration_date < $date) {

                $userAccunt->password = Hash::make($request->password);
                $userAccunt->login_fails = 0;
                $userAccunt->status_id = 1;
                $userAccunt->save();

                return response(['status' => true, 'message' => 'Password Reset Successfully']);
            } else {
                return response(['status' => false, 'message' => 'Invalid or Expired OTP']);
            }
        } else {
            return response(['status' => false, 'message' => 'Invalid Phone number or OTP supplied']);
        }
    }

    public function changePassword(Request $request)
    {
        \logInfo($request->all(), "changePassword request log");

        $validate = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required'
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['id' => \auth()->user()->id])->first();

        if (Hash::check($request->current_password, $userAccunt->password)) {

            $userAccunt->password = Hash::make($request->new_password);
            $userAccunt->updated_at = Carbon::now();
            $userAccunt->save();

            return response(['status' => true, 'message' => 'Password changed successfully']);
        } else {
            return response(['status' => false, 'message' => 'Invalid current password provided']);
        }
    }

    public function updateProfile(Request $request)
    {
        \logInfo($request->all(), "updateProfile request log");

        $validate = Validator::make($request->all(), [
            'phone' => 'required|numeric|gt:0',
            'email' => 'required|email',
            'dob' => 'required|date',
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = $request->user();

        if ($userAccunt) {

            if ($request->filled('profile_pic_url')) {
                $userAccunt->profile_pic = $request->profile_pic_url;
            }
            if ($request->filled('nin')) {
                $userAccunt->nin = $request->nin;
            }

            if ($request->filled('dob')) {
                $userAccunt->dob = $request->dob;
            }
            if ($request->filled('signature_url')) {
                $userAccunt->signature = $request->signature_url;
            }
            if ($request->filled('sex')) {
                $userAccunt->sex = $request->sex;
            }
            if ($request->filled('bvn')) {
                $userAccunt->bvn = $request->bvn;
            }
            if ($request->filled('valid_id_url')) {
                $userAccunt->validID_file = $request->valid_id_url;
            }
            if ($request->filled('other_doc_url')) {
                $userAccunt->otherID_file = $request->other_doc_url;
            }
            $userAccunt->save();

            return response(['status' => true, 'message' => 'Profile Updated Successfuls', 'data' => $userAccunt]);
        } else {
            return response(['status' => false, 'message' => 'Failed to Update Profile']);
        }
    }

    public function resendOtp(Request $request)
    {
        \logInfo($request->all(), "resendOtp request log");

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'user_reference' => 'required'
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'user_type_id' => 1])->first();

        if ($userAccunt) {

            //Third party SMS (Send OTP Code goes here)

            $checkPhoneWithAccountReference = User::where(['uuid' => $request->user_reference, 'phone' => $request->phone_number])->first();

            if ($checkPhoneWithAccountReference) {
                // $otpCode = "2222";

                $otpCode = \generateOtp();

                $userAccunt->otp = $otpCode;
                $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                $userAccunt->otp_expiration_date = $date;
                $userAccunt->save();

                $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone. alternative dial *7006*80*00%23
                To verify your phone without OTP.";

                $sendSMS = \sendSMS($userAccunt->id, $msg, \true, false, "OTP Confirmation", '', 0, false);

                return response(['status' => true, 'message' => 'OTP Sent Successfully']);
            } else {
                $checkPhone = User::where(['phone' => $request->phone_number])->first();

                if ($checkPhone) {
                    return response(['status' => false, 'message' => 'User phone number already exists'], 409);
                } else {
                    $otpCode = \generateOtp();

                    $userAccunt->otp = $otpCode;
                    $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                    $userAccunt->otp_expiration_date = $date;
                    $userAccunt->save();

                    $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone. alternative dial *7006*80*00%23
                    To verify your phone without OTP.";

                    $sendSMS = \sendSMS($userAccunt->id, $msg, \true, false, "OTP Confirmation", '', 0, \false);

                    return response(['status' => true, 'message' => 'OTP Sent Successfully']);
                }
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid user reference or phone number'], 400);
        }
    }

    public function sendOtp(Request $request)
    {
        \logInfo($request->all(), "sendOtp request log");

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'user_reference' => 'required'
        ]);

        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'user_type_id' => 1])->first();

        if ($userAccunt) {

            //Third party SMS (Send OTP Code goes here)

            if ($userAccunt->phone_verification_status_id == 2 && $request->phone_number != $userAccunt->phone) {
                return response()->json(['status' => false, 'message' => 'Invalid phone number provided'], 400);
            }

            if ($userAccunt->status_id == 5) {
                return response()->json(['status' => false, 'message' => 'User Account blocked. Kindly contact admin to unblock'], 400);
            }

            $checkPhoneWithAccountReference = User::where(['uuid' => $request->user_reference, 'phone' => $request->phone_number])->first();

            if ($checkPhoneWithAccountReference) {
                // $otpCode = "2222";
                $otpCode = \generateOtp();

                $userAccunt->otp = $otpCode;
                $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                $userAccunt->otp_expiration_date = $date;
                $userAccunt->save();

                $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";

                $sendSMS = \sendSMS($checkPhoneWithAccountReference->id, $msg, \true, false, "OTP Confirmation", '', 0, false);

                return response(['status' => true, 'message' => 'OTP Sent Successfully']);
            } else {
                $checkPhone = User::where(['phone' => $request->phone_number])->first();

                if ($checkPhone) {
                    return response(['status' => false, 'message' => 'User phone number already exists'], 409);
                } else {
                    // $otpCode = "2222";
                    $otpCode = \generateOtp();

                    $userAccunt->otp = $otpCode;
                    $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                    $userAccunt->otp_expiration_date = $date;
                    $userAccunt->phone = $request->phone_number;
                    $userAccunt->save();

                    $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";

                    $sendSMS = \sendSMS($userAccunt->id, $msg, \true, false, "OTP Confirmation", '', 0, false);

                    return response(['status' => true, 'message' => 'OTP Sent Successfully']);
                }
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid user reference or phone number'], 400);
        }
    }

    public function forgotPasswordSendOTP(Request $request)
    {
        \logInfo($request->all(), "forgotPasswordSendOTP request log");

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required|numeric|gt:0',
        ]);
        if ($validate->fails()) {
            $ra = array("status" => false, "message" => $validate->errors()->all()[0]);
            return response()->json($ra, 406);
        }
        $userAccunt = User::where(['phone' => $request->phone_number, 'user_type_id' => 1])->first();
        if ($userAccunt) {
            //Third party SMS (Send OTP Code goes here)
            // $otpCode = "2222";
            $otpCode = \generateOtp();
            $userAccunt->otp = $otpCode;
            $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');
            $userAccunt->otp_expiration_date = $date;
            $userAccunt->save();
            $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";
            $sendSMS = \sendSMS($userAccunt->id, $msg, '', 0, true);
            return response(['status' => true, 'message' => 'OTP Sent Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'This account could not be found'], 400);
        }
    }
}
