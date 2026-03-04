<?php
namespace App\Http\Controllers\api\v1\user;

use App\Http\Controllers\Controller;
use App\Mail\ResetAccountMail;
use App\Mail\SendTierMail;
use App\Models\AgentRequest;
use App\Models\ApiKey;
use App\Models\banks;
use App\Models\branch;
use App\Models\CarouselImage;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\Esusu;
use App\Models\Esusulog;
use App\Models\EsusuTXN;
use App\Models\esusu_types;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // \logInfo($request->all(), "index request log");

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

                $myUserWallets   = [];
                $myUserTerminals = [];

                foreach ($request->user()->accounts as $key => $wallet) {
                    $userWallet      = user_wallet::where('wb_account_detail_id', $wallet->id)->first();
                    $myUserWallets[] = ["account_id" => $wallet->id, "account_number" => $wallet->virtual_accnum, "bank_id" => $wallet->bank->id, "bank" => $wallet->bank->bank_name, 'product_name' => $wallet->accountType->savings_product, "wallet_balance" => $userWallet->balance, "currency" => $wallet->currency->code ?? 'NGN'];
                }

                if ($request->user()->terminals != null) {
                    \logInfo(json_encode($request->user()->terminals), "Login terminals");
                    foreach ($request->user()->terminals as $key => $terminal) {
                        $myUserTerminals[] = ["account_id" => $terminal->terminal_code, "account_number" => $terminal->terminal_code, "bank_id" => $terminal->terminal_code, "bank" => "Nomba", 'product_name' => "POS Account $wallet->terminal_code", "wallet_balance" => $terminal->balance, "currency" => 'NGN'];
                    }

                }

                $virtualCard = UserCard::where('user_id', auth()->user()->id)->get();

                // $sliders = CarouselImage::where('status_id', 1)->get("image_url");
                $sliders = CarouselImage::where(['status_id' => 1, 'company_id' => auth()->user()->company_id])->get("image_url");

                if (! $virtualCard) {
                    $virtualCard = [];
                }

                $wb_accounts = wb_account_details::with('currency')->with('user_wallet')->with('accountType')->with('bank')->where('user_id', $request->user()->id)->get();

                \logInfo(json_encode($wb_accounts), "User Wallets Get Details");
                \logInfo(json_encode($myUserTerminals), "User Terminals Get Details");

                $userDetails = [
                    "currency"                     => $request->user()->branch->currency ?? 'NGN',
                    "first_name"                   => $request->user()->first_name,
                    "last_name"                    => $request->user()->last_name,
                    "phone"                        => $request->user()->phone,
                    "profile_pic"                  => $request->user()->profile_pic,
                    "username"                     => $request->user()->username,
                    "bvn"                          => $request->user()->bvn,
                    "bvn_status_id"                => $request->user()->bvn_status_id,
                    "nin"                          => $request->user()->nin,
                    "dob"                          => $request->user()->dob,
                    "signature"                    => $request->user()->signature,
                    "email"                        => $request->user()->email,
                    "address"                      => $request->user()->address,
                    "sex"                          => $request->user()->sex,
                    "otp"                          => $request->user()->otp,
                    "virtual_account"              => $request->user()->virtual_account,
                    "account_reference"            => $request->user()->account_reference,
                    "bank_id"                      => $request->user()->bank_id,
                    "validID_file"                 => $request->user()->validID_file,
                    "otherID_file"                 => $request->user()->otherID_file,
                    "company_id"                   => $request->user()->company_id,
                    "status_id"                    => $request->user()->status_id,
                    "referral_id"                  => $request->user()->id,
                    "kyc_level_id"                 => $request->user()->kyc_level_id,
                    "phone_verification_status_id" => $request->user()->phone_verification_status_id,
                    "account_officer_id"           => $request->user()->account_officer_id,
                    "branch_id"                    => $request->user()->branch_id,
                    "balance"                      => $userWallet->balance,
                    "wallets"                      => $myUserWallets,
                    "cards"                        => $virtualCard,
                    "sliders"                      => $sliders,
                    "is_card_available"            => count($virtualCard) > 0,
                    "accounts"                     => $wb_accounts,
                    "terminals"                    => $myUserTerminals,
                    //$request->user()->wb_account_details,
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
        // \logInfo($request->all(), "uploadFile request log");

        $validate = Validator::make($request->all(), [
            'image_file' => 'required|mimes:jpg,jpeg,png|file|max:5120',
            'type'       => 'required',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
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

        // $result = $request->file("image_file")->store("public/$pathLocation");

        $result = $request->file("image_file")->store($pathLocation, 'public');

        // $request->image->move(public_path("images") , $imageName) // Store file in public folder
        if ($result) {

            //   $imageUrl = \url("wba/storage/app/" . $result);

            //  $imageUrl = \url("$result");
            $imageUrl = asset(Storage::url($result));

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

            return response()->json(["status" => true, "message" => "Image Upload successful", "data" => ["image_url" => \url("storage/$result"), "image_file" => $result]]);
        } else {
            return response()->json(["status" => false, "message" => "Failed to upload image"], 400);
        }

    }

    public function sendTierNotice(Request $request)
    {
        \logInfo($request->all(), "veSend Tier request log");
        $user  = auth('api')->user();
        \logInfo(json_encode($user), "user send tier");
        $username   = $user->username;
        $userid     = $user->user_id;
        $valid_id   = $request->valid_id;
        $nepa_bill  = $request->nepa_bill;
        $tier       = $request->tier;
        $subtext    = "Tier Upgrade Request";
        $reason     = "";
        $msg        = '';
        $msg       .= "$username has requested for a tier upgrade to $tier <br><br>";
        $msg       .= "Find the resources below from user id  $userid <br><br>";
        $msg       .= "Nepa bill :   $nepa_bill <br><br>";
        $msg       .= "Valid id :  $valid_id <br>";

        $this->sendTierReportEmail($subtext, $msg, $reason, $user);

        return response()->json(["status" => true, "message" => "Mail Sent successful"], 200);

    }
    public function verifyOTP(Request $request)
    {
        \logInfo($request->all(), "verifyOTP request log");

        logInfo($request->all());

        $validate = Validator::make($request->all(), [
            'user_reference' => 'required',
            'otp_code'       => 'required|numeric|gt:0',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => "Otp Code and user reference are required"];
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'otp' => $request->otp_code])->first();

        if ($userAccunt) {

            $newdate = Carbon::now();

            if (Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date)) > Carbon::now()) {

                $userAccunt->phone_verification_status_id = 2;
                $userAccunt->last_login                   = Carbon::now();
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

        logInfo($request->all());

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'otp_code'     => 'required|numeric|gt:0',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => "Otp Code and Phone number are required"];
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
            'user_id'          => 'sometimes|numeric|gt:0',
            'account_number'   => 'sometimes|string',
            'remark'           => 'required|string',
            'restriction_type' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userId = null;

        if ($request->has('user_id')) {
            $userAccunt = User::find($request->user_id);

            $userId = $userAccunt->id;
        } else if ($request->has('account_number')) {
            $userAccunt = wb_account_details::where('virtual_accnum', $request->account_number)->first();

            $userId = $userAccunt->user_id;
        }

        if ($userId != null) {

            $restrictUserAccount = \restrictUserAccountAPI($userId, $request->restriction_type, $request->remark);

            return \response()->json($restrictUserAccount);
        } else {
            return response(['status' => false, 'message' => 'Invalid User Id or Account Number']);
        }
    }

    public function unrestrictUserAccount(Request $request)
    {
        \logInfo($request->all(), "restrict request log");

        $validate = Validator::make($request->all(), [
            'user_id'          => 'sometimes|numeric|gt:0',
            'account_number'   => 'sometimes|string',
            'restriction_type' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userId = null;

        if ($request->has('user_id')) {
            $userAccunt = User::find($request->user_id);
            $userId     = $userAccunt->id;
        } else if ($request->has('account_number')) {
            $wbAccountDetails = wb_account_details::where('virtual_accnum', $request->account_number)->first();

            if (! $wbAccountDetails) {
                return response(['status' => false, 'message' => 'Invalid Account Number']);
            }

            $userId = $wbAccountDetails->user_id;

            $userAccunt = $wbAccountDetails->user;
        }

        if ($userAccunt) {

            logInfo($userAccunt, "User to be freed");
            logInfo($userId, "User to be freed");

            $restrictUserAccount = \removeUserRestrictionAPI($userId, $request->restriction_type, $request->remark, $userAccunt);

            return \response()->json($restrictUserAccount);
        } else {
            return response(['status' => false, 'message' => 'Invalid User Id or Account Number']);
        }
    }
    public function resetPin(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'current_pin' => 'required|max:4|min:4',
            'new_pin'     => 'required|max:4|min:4',
        ]);
        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->new_pin == $request->current_pin) {
            $ra = ["status" => false, "message" => "New Pin cannot be same as Current Pin"];
            return response()->json($ra, 406);
        }
        if (auth()->user()->pin == $request->current_pin) {
            User::where('id', auth()->user()->id)->update(['pin' => $request->new_pin]);
            $ra = ["status" => true, "message" => "Pin Reset Successful"];
            return response()->json($ra, 200);
        } else {
            $ra = ["status" => false, "message" => "Invalide Pin"];
            return response()->json($ra, 406);
        }
    }

    public function getVirtualAccounts(Request $request)
    {
        \logInfo($request->all(), "restrict request log");

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $customers = DB::table('users')
            ->select(
                'users.virtual_account',
                'users.account_reference',
                'banks.bank_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS account_name"),
                'user_wallets.balance'
            )
            ->join('banks', 'banks.id', '=', 'users.bank_id')
            ->join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
            ->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Customers Fetched successfully', 'data' => $customers]);
    }

    public function getAccountBalance(Request $request, $accountNumber)
    {
        \logInfo($request->all(), "restrict request log");

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $customerAccount = wb_account_details::where('virtual_accnum', $accountNumber)->first();

        if ($checkApiKey->company->id != $customerAccount->user->company_id) {
            return response()->json(['status' => true, 'message' => 'Cannot get this customer account balance'], 400);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Customers balance fetched successfully',
            'data'    => [
                'balance' => $customerAccount->user_wallet->balance,
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        \logInfo($request->all(), "resetPassword request log");

        $validate = Validator::make($request->all(), [
            'phone_number' => 'required',
            'otp_code'     => 'required|numeric|gt:0',
            'password'     => 'required',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => "Otp Code and Phone number are required"];
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['phone' => $request->phone_number, 'otp' => $request->otp_code])->first();

        if ($userAccunt) {

            $date = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

            if ($userAccunt->otp_expiration_date < $date) {

                $userAccunt->password    = Hash::make($request->password);
                $userAccunt->login_fails = 0;
                $userAccunt->status_id   = 1;
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
            'new_password'     => 'required',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['id' => \auth()->user()->id])->first();

        if (Hash::check($request->current_password, $userAccunt->password)) {

            $userAccunt->password   = Hash::make($request->new_password);
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
            'dob'   => 'required|date',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
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
            'phone_number'   => 'required',
            'user_reference' => 'required',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'user_type_id' => 1])->first();

        $getCompanyName = $userAccunt->company->short_name;

        if ($userAccunt) {

            //Third party SMS (Send OTP Code goes here)

            $checkPhoneWithAccountReference = User::where(['uuid' => $request->user_reference, 'phone' => $request->phone_number])->first();

            if ($checkPhoneWithAccountReference) {
                // $otpCode = "2222";

                $otpCode = \generateOtp();

                $userAccunt->otp = $otpCode;
                $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                $userAccunt->otp_expiration_date = $date;
                $userAccunt->save();

                // $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.($getCompanyName) Alternatively, dial *7006*80*00";

                $msg = "Your $getCompanyName Verification Pin is $otpCode . it expires in 30 mins.";
                if ($userAccunt->company->id == 33) {
                    $phone            = $userAccunt->phone;
                    $transaction_type = "otp";
                    $code             = $otpCode;
                    goldencreditSMSPost($phone, $transaction_type, $code);
                } else {
                    $sendSMS = \sendSMS($userAccunt->id, $msg, true, false, "OTP Confirmation", '', 0, false);
                    \logInfo(json_encode($sendSMS), "resend OTP response");
                }
                $data = [
                    'subtext'      => "Reset Password",
                    'name'         => $userAccunt->first_name,
                    'desc'         => $msg,
                    'user'         => $userAccunt->first_name,
                    'company_name' => $userAccunt->company->company_name,
                ];
                Mail::to($userAccunt->email)->send(new ResetAccountMail($data));

                return response(['status' => true, 'message' => 'OTP Sent Successfully']);
            } else {
                $checkPhone = User::where(['phone' => $request->phone_number])->first();

                if ($checkPhone) {
                    return response(['status' => false, 'message' => 'User phone number already exists'], 409);
                } else {
                    $otpCode = \generateOtp();

                    $userAccunt->otp = $otpCode;
                    $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                    $userAccunt->otp_expiration_date = $date;
                    $userAccunt->save();

                    //   $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone. Alternatively, dial *7006*80*00";
                    $msg = "Your $getCompanyName Verification Pin is $otpCode . it expires in 30 mins.";

                    $sendSMS = \sendSMS($userAccunt->id, $msg, true, false, "OTP Confirmation", '', 0, \false);
                    $data    = [
                        'subtext'      => "Reset Password",
                        'name'         => $userAccunt->first_name,
                        'desc'         => $msg,
                        'user'         => $userAccunt->first_name,
                        'company_name' => $userAccunt->company->company_name,
                    ];
                    Mail::to($userAccunt->email)->send(new ResetAccountMail($data));
                    \logInfo($sendSMS, "resend OTP response");

                    return response(['status' => true, 'message' => 'OTP Sent Successfully']);
                }
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid user reference or phone number'], 400);
        }
    }

    public function getUserInfo(Request $request)
    {

        if (! $request->unique_identifier) {
            return response()->json(['status' => false, 'message' => 'Account Number is required'], 400);
        }

        $headerKey = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $headerKey)->first();

        $wbAccountDetails = wb_account_details::where(['virtual_accnum' => $request->unique_identifier])->first();

        if (! $wbAccountDetails) {
            return response()->json(['status' => false, 'message' => 'Invalid Account number'], 400);
        }

        $userData = [];

        $returnedUser = $wbAccountDetails->user;

        $userData['first_name']   = $returnedUser->first_name;
        $userData['last_name']    = $returnedUser->last_name;
        $userData['email']        = $returnedUser->email;
        $userData['phone']        = $returnedUser->phone;
        $userData['address']      = $returnedUser->address;
        $userData['dob']          = $returnedUser->dob;
        $userData['bvn']          = $returnedUser->bvn;
        $userData['kyc_level_id'] = $returnedUser->kyc_level_id;
        $userData['gender']       = "M";

        return response()->json([
            'status' => true, 'message' => 'User Information fetched successfully', 'data' => $userData,
        ]);
    }

    public function getUserCardBalance(Request $request)
    {

        if (! $request->unique_identifier) {
            return response()->json(['status' => "05", 'message' => 'Account Number is required'], 400);
        }

        $headerKey = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $headerKey)->first();

        $wbAccountDetails = wb_account_details::where(['virtual_accnum' => $request->unique_identifier])->first();

        if (! $wbAccountDetails) {
            return response()->json(['status' => "01", 'message' => 'Invalid Account number'], 400);
        }

        if ($checkApiKey->company->id != $wbAccountDetails->user->company_id) {
            return response()->json(['status' => "01", 'message' => 'Invalid Account number'], 400);
        }

        $isActive = $wbAccountDetails->user->status_id == 1;

        if (! $isActive) {

            return response()->json([
                'status'  => "00",
                'message' => 'Customer Balance fetched successfully',
                'data'    => [
                    'balance'      => $wbAccountDetails->user_wallet->balance,
                    'account_name' => $wbAccountDetails->user->first_name . " " . $wbAccountDetails->user->last_name,
                    'is_active'    => $isActive,
                ],
            ]);
        }

        if ($request->has('type')) {
            if ($request->type == "capture") {

                if ($request->event == "card.authorization.closed" && $request->status == "approved") {
                    $msg  = "Debit Transaction Alert: Amount: " . ($request->amount / 100) . ",  Date: " . date("Y-m-d H:i:s") . " Avail Bal: " . $wbAccountDetails->user_wallet->balance . ".";
                    $msg .= " Direct ATM Withdrawal";

                    // $sendSMS = \sendSMS($wbAccountDetails->user_id, $msg, \true, false, "ATM Withdrawal", '', 0, \false);

                    return response()->json([
                        'status'  => "00",
                        'message' => 'Transaction approved successfully',
                        'data'    => [
                            'balance'   => $wbAccountDetails->user_wallet->balance,
                            'is_active' => $isActive,
                        ],
                    ]);
                }

                if (($request->event == "card.authorization.closed" && $request->status == "declined") || ($request->event == "card.authorization.update" && $request->status == "reversed")) {
                    $checkTransaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                    if (! $checkTransaction) {
                        return response()->json([
                            'status'  => "09",
                            'message' => 'Invalid transaction Reference',
                        ], 400);
                    }

                    if ($checkTransaction->transaction_remarks_id == 5) {
                        return response()->json([
                            'status'  => "03",
                            'message' => 'Transaction already reversed',
                        ], 400);
                    }

                    $debitReversal = debitReversal($request->transaction_reference, $wbAccountDetails->id, $wbAccountDetails->user->company_id, $checkTransaction->amount, 0, "both", "atm");

                    $msg  = "Credit Transaction Alert: Amount: $request->amount, Avail Bal: " . $debitReversal['data']['balance_after'] . "  Date: " . date("Y-m-d H:i:s") . ".";
                    $msg .= " ATM Debit Reversal";

                    // $sendSMS = \sendSMS($wbAccountDetails->user_id, $msg, true, true, "ATM Withdrawal", '', 0, false);

                    return response()->json([
                        'status'  => "00",
                        'message' => 'Transaction Declined and has been reversed',
                        'data'    => [
                            'balance'   => $debitReversal['data']['balance_after'],
                            'is_active' => $isActive,
                        ],
                    ]);
                }

                if ($request->event == "card.authorization.update" && $request->status != "reversed") {
                    $checkTransaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                    if (! $checkTransaction) {
                        return response()->json([
                            'status'  => "09",
                            'message' => 'Invalid transaction Reference',
                        ], 400);
                    }

                    $oldAmount = $checkTransaction->amount;

                    $newAmount = $request->amount;

                    if ($newAmount < 0) {
                        return response()->json([
                            'status'  => "10",
                            'message' => 'Invalid transaction amount',
                        ], 400);
                    }

                    $debitAmount = $newAmount - $oldAmount;

                    $userWallet = user_wallet::where('wb_account_details_id', $wbAccountDetails->id)->first();

                    $userWallet->balance -= $debitAmount;
                    $userWallet->save();

                    $currentBalance = $wbAccountDetails->user_wallet->balance + $oldAmount;

                    $checkTransaction->balance_before = $currentBalance;
                    $checkTransaction->amount         = $request->amount;
                    $checkTransaction->balance_after  = $currentBalance - $request->amount;

                    $checkTransaction->save();

                    return response()->json([
                        'status'  => "00",
                        'message' => 'Transaction updated and has been reversed',
                        'data'    => [
                            'balance'   => $checkTransaction->balance_after,
                            'is_active' => $isActive,
                        ],
                    ]);
                }

                try {

                    $checkTransaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                    if ($checkTransaction) {
                        return response()->json([
                            'status'  => "03",
                            'message' => 'Duplicate transaction record',
                        ], 400);
                    }

                    $companyCharge = $checkApiKey->company->setting;

                    $companyCharge = $checkApiKey->company->setting->physical_card_withdrawal_charge;

                    $debitAmount = $request->amount + $companyCharge;

                    $customerBalance = debitCustomerOnly($wbAccountDetails->id, $debitAmount, $wbAccountDetails->user);

                    if (! $customerBalance['status']) {
                        $customerBalance['status'] = "02";
                        return response()->json($customerBalance, 400);
                    }

                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(103, $wbAccountDetails->user->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    //Create a successful transaction for the Recipient
                    $transaction = transactions::create([
                        "company_balance_before" => 0,
                        "company_amount"         => 0,
                        "company_balance_after"  => 0,
                        "balance_before"         => $customerBalance['balance_before'],
                        "amount"                 => $debitAmount,
                        "account_id"             => $wbAccountDetails->id,
                        "balance_after"          => $customerBalance['balance_after'],
                        "description"            => "ATM Debit Transaction",
                        "transaction_reference"  => $request->transaction_reference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 103,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $wbAccountDetails->user->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    return response()->json([
                        'status'  => "00",
                        'message' => 'Transaction completed successfully',
                        'data'    => [
                            'balance'      => $customerBalance['balance_after'],
                            'account_name' => $wbAccountDetails->user->first_name . " " . $wbAccountDetails->user->last_name,
                            'is_active'    => $isActive,
                        ],
                    ]);
                } catch (Exception $e) {
                    logInfo($e->getMessage(), "Exception with ATM Debit Transaction");

                    return response()->json([
                        'status'  => "05",
                        'message' => 'Failed to complete transaction. (Server Error)',
                    ], 500);
                }
            } else {

                return response()->json([
                    'status'  => "00",
                    'message' => 'Customer Balance fetched successfully',
                    'data'    => [
                        'balance'      => $wbAccountDetails->user_wallet->balance,
                        'account_name' => $wbAccountDetails->user->first_name . " " . $wbAccountDetails->user->last_name,
                        'is_active'    => $isActive,
                    ],
                ]);
            }
        }
        // if ($checkApiKey->company->id != $wbAccountDetails->user->company_id) {
        //     return response()->json(['status' => false, 'message' => 'Account does not belong to your company'], 400);
        // }

        return response()->json([
            'status'  => "00",
            'message' => 'Customer Balance fetched successfully',
            'data'    => [
                'balance'      => $wbAccountDetails->user_wallet->balance,
                'account_name' => $wbAccountDetails->user->first_name . " " . $wbAccountDetails->user->last_name,
                'is_active'    => $isActive,
            ],
        ]);
    }

    public function sendOtp(Request $request)
    {
        \logInfo($request->all(), "sendOtp request log");

        $validate = Validator::make($request->all(), [
            'phone_number'   => 'required',
            'user_reference' => 'required',
        ]);

        if ($validate->fails()) {
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userAccunt = User::where(['uuid' => $request->user_reference, 'user_type_id' => 1])->first();

        $getCompanyName = $userAccunt->company->short_name;

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
                $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                $userAccunt->otp_expiration_date = $date;
                $userAccunt->save();

                // $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone. $getCompanyName";
                $msg = "Your $getCompanyName Verification Pin is $otpCode . it expires in 30 mins.";
                if ($userAccunt->company->id == 33) {
                    $phone            = $userAccunt->phone;
                    $transaction_type = "otp";
                    $code             = $otpCode;
                    goldencreditSMSPost($phone, $transaction_type, $code);
                } else {
                    $sendSMS = \sendSMS($checkPhoneWithAccountReference->id, $msg, \true, false, "OTP Confirmation", '', 0, false);
                }
                return response(['status' => true, 'message' => 'OTP Sent Successfully']);
            } else {
                $checkPhone = User::where(['phone' => $request->phone_number])->first();

                if ($checkPhone) {
                    return response(['status' => false, 'message' => 'User phone number already exists'], 409);
                } else {
                    // $otpCode = "2222";
                    $otpCode = \generateOtp();

                    $userAccunt->otp = $otpCode;
                    $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

                    $userAccunt->otp_expiration_date = $date;
                    $userAccunt->phone               = $request->phone_number;
                    $userAccunt->save();

                    //     $msg = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";
                    $msg = "Your $getCompanyName Verification Pin is $otpCode . it expires in 30 mins.";
//                             sendSMS($userid, $msg, $type = true, $charge_customer = true, $title = 'Create Account', $accno = '', $usesms = 0, $isApi = false, $all_sms_email = 1, $sms_logdata = [])
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
            $ra = ["status" => false, "message" => $validate->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        //   $userAccunt = User::where(['phone' => $request->phone_number, 'user_type_id' => 1])->orwhere(['virtual_account' => $request->phone_number, 'user_type_id' => 1])->first();

        $userAccunt = User::where(function ($query) use ($request) {
            $query->where('phone', $request->phone_number)
                ->orWhere('virtual_account', $request->phone_number);
        })->where('user_type_id', 1)->first();

        \logInfo(json_encode($userAccunt), "User Accont first");
        $getCompanyName = $userAccunt->company->short_name;
        if ($userAccunt) {
            //Third party SMS (Send OTP Code goes here)
            // $otpCode = "2222";
            $otpCode         = \generateOtp();
            $userAccunt->otp = $otpCode;

            $date                            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');
            $userAccunt->otp_expiration_date = $date;
            $userAccunt->save();
            // $msg     = "You request to reset your Password/Pin.. kindly find OTP.. $otpCode  Do not share with anyone.";
            $msg     = "Your $getCompanyName Verification Pin is $otpCode . it expires in 30 mins.";
            $sendSMS = \sendSMS($userAccunt->id, $msg, true, true, 'Reset Password OTP');
            $data    = [
                'subtext'      => "Reset Password",
                'name'         => $userAccunt->first_name,
                'desc'         => $msg,
                'user'         => $userAccunt->first_name,
                'company_name' => $userAccunt->company->company_name,
            ];

            Mail::to($userAccunt->email)->send(new ResetAccountMail($data));
            return response(['status' => true, 'message' => 'OTP Sent Successfully', 'user_reference' => $userAccunt->account_reference]);
        } else {
            return response()->json(['status' => false, 'message' => 'This account could not be found'], 400);
        }
    }
    public function addAgentCustomer(Request $request)
    {
        \logInfo($request->all(), "Agent User Registration Request");
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|string|max:50|min:2',
            'last_name'     => 'required|string|max:50|min:2',
            'phone'         => 'required|string',
            'amount'        => 'required|numeric|gt:0',
            'address'       => 'required',
            'accountNo'     => 'required',
            'bankcode'      => 'required',
            'esusu_product' => 'required',
        ]);
        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        $companyRecord = Company::where(['id' => auth()->user()->company_id])->first();
        if (! $companyRecord) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }
        if (count($companyRecord->savingsProducts) < 1) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return response()->json($ra, 400);
        }
        \logInfo($companyRecord, "Branch first");
        //is_agent
        if (auth()->user()->is_agent != 1) {
            return response()->json(["status" => false, "message" => "Only approve agent can perform such action"], 400);
        }
        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }
        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }
        $userPhone               = $request->phone;
        $virtual_acccount_number = $request->accountNo;
        $banks                   = banks::where('id', $request->bankcode)->first();
        if (! $banks) {
            $ra = ["status" => false, "message" => "Bank not found!"];
            return response()->json($ra, 400);
        }
        $bank_id = $banks->id;
        $user    = User::where([['phone', $userPhone], ['company_id', $companyRecord->id]])->get();
        // \logInfo($user, "Current user");
        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "User is already existing"];
            return response()->json($ra, 409);
        }
        if ($validator->passes()) {
            // if (true) {
            $customerReference = generateAccountReference();
            $accountOfficer    = User::where('company_id', $companyRecord->id)->first();
            //
            //  GENERATE AND SEND OTP TO USER HERE
            //
            // $otp = generateOtp();
            // $otp = '7777';
            DB::beginTransaction();
            try {
                $user = User::create([
                    'first_name'                   => $request->first_name,
                    'last_name'                    => $request->last_name,
                    'company_id'                   => $companyRecord->id,
                    'phone'                        => $userPhone,
                    'bank_id'                      => $bank_id,
                    'status_id'                    => 2,
                    'branch_id'                    => $companyRecord->branch[0]->id,
                    'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                    'user_type_id'                 => 3,
                    'phone_verification_status_id' => 1,
                    'virtual_account'              => $virtual_acccount_number,
                    'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                    'uuid'                         => $customerReference,
                    'address'                      => $request->address,
                    'agent_id'                     => auth()->user()->id,
                    'account_officer_id'           => $companyRecord->id,
                ]);
                $wb_account_details = wb_account_details::create([
                    'user_id'            => $user->id,
                    'account_product_id' => $companyRecord->savingsProducts[0]->id,
                    'bankid'             => $bank_id ?? 126,
                    'virtual_accnum'     => $virtual_acccount_number,
                    'acc_reference'      => $customerReference,
                    'has_atm_card'       => 0,
                ]);
                $user_wallet = user_wallet::create([
                    'wb_account_detail_id' => $wb_account_details->id,
                    'balance'              => 0,
                ]);
                $bank = banks::where('id', $bank_id)->first();
                if (! $bank) {
                    response()->json(['status' => false, 'message' => 'Invalid bank selected']);
                }
                if ($user && $user_wallet) {
                    // $user->assignRole([4]);
                    $accountNumber  = $virtual_acccount_number;
                    $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";
                    //$smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00 To verify your phone from the registered phone number", true, false, 'Verify OTP', '', 0, false);
                    //$smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);
                    // \logInfo($smsstate, "SMS response");
                    $request->rate       = $request->amount;
                    $request->savetype   = $wb_account_details->id;
                    $request->start_date = date("Y-m-d");
                    $createEsusu         = $this->create_esusu_wallet($request);
                    if ($createEsusu['status'] == 0) {
                        DB::rollback();
                        return response()->json(['status' => false, 'message' => $createEsusu['message']], 400);
                    }
                    DB::commit();
                    return response(['status' => true, 'message' => 'Registration Successful', 'data' => ['user_reference' => $customerReference, 'esusu' => $createEsusu]]);
                } else {
                    return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                }
            } catch (\Exception $e) {
                \logInfo($e->getTraceAsString(), "Registration failure");
                DB::rollback();
                // something went wrong
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration ' . $e->getMessage()], 400);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
        }
    }
    public function create_esusu_wallet($request)
    {
        try {
            $esusu_type_id        = $request->esusu_product;
            $wb_account_detail_id = $request->savetype;
            $rate                 = $request->rate;
            $start_date           = $request->start_date;
            $useraccount          = wb_account_details::where('id', $wb_account_detail_id)->first();
            if (! $useraccount) {
                return ['status' => 0, 'message' => 'Block User Error!'];
            }

            //restricted
            if ($useraccount->user->company_id != auth()->user()->company_id) {
                return ['status' => 0, 'message' => 'User Access Denied !'];
            }
            $esusu_type_data = $datacount = esusu_types::where(['id' => $esusu_type_id, 'company_id' => auth()->user()->company_id]);
            if ($datacount->count() == 0) {
                return ['status' => 0, 'message' => 'Esusu Type no Found! '];
            }
            $esusu_type_data = $esusu_type_data->first();
            if ($esusu_type_data->min_rate > $rate) {
                return ['status' => 0, 'message' => 'The minimum rate for this plan is ' . $esusu_type_data->min_rate . " $rate given"];
            }
            if ($esusu_type_data->max_rate < $rate) {
                return ['status' => 0, 'message' => 'The maximum rate for this plan is ' . $esusu_type_data->max_rate . " $rate given"];
            }
            $esusu_already_exist = Esusu::where(['wb_account_detail_id' => $wb_account_detail_id, 'esusu_type_id' => $esusu_type_id])->count();
            if ($esusu_already_exist) {
                return ['status' => 0, 'message' => 'Esusu Already Exist for this account!'];
            }

            // $esusu_type = $esusu_type_data->first();
            // $minrate = $esusu_type->min_rate;
            // $maxrate = $esusu_type->max_rate;
            // if ($rate < $minrate || $rate > $maxrate) {
            //     return ['status' => 0, 'message' => "The Min and Max Rate for this Plan is $minrate  to $maxrate!"];
            // }
            $esusu = Esusu::create(
                [
                    'wb_account_detail_id' => $wb_account_detail_id,
                    'rate'                 => $rate,
                    'commence_date'        => $start_date,
                    'prev_card'            => 0,
                    'esusu_type_id'        => $esusu_type_id,
                    'start_date'           => $start_date, // esusu txn
                    'withdrawable'         => 0,
                    'wallet'               => 0,
                    'overdraft'            => 0,
                    'missed_day'           => 0,
                    'status_id'            => 1,
                    'hasAgent'             => 1,
                ]
            );
            if ($esusu) {
                return ['status' => 1, 'message' => 'Esusu Account Created!', 'data' => $esusu];
            } else {
                return ['status' => 0, 'message' => 'Failed to Register Esusu Account!'];
            }
        } catch (Exception $e) {
            return ['status' => 0, 'message' => 'Failed to Register Esusu Account!'];
        }
    }
    public function viewmyCustomer(Request $request)
    {
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('esusus', 'esusus.wb_account_detail_id', '=', 'wb_account_details.id')
            ->join('esusu_types', 'esusu_types.id', '=', 'esusus.esusu_type_id')
            ->join('banks', 'banks.id', '=', 'users.bank_id')
            ->where(['users.agent_id' => auth()->user()->id])->select('users.first_name', 'users.last_name', 'users.virtual_account', 'esusus.*', DB::raw('esusus.id as esusuID'), 'esusu_types.esusu_type', 'esusu_types.circle_charge', 'esusu_types.duration', 'banks.bank_name');
        if ($request->search) {
            $filter = $request->search;
            $query  = $query->where(function ($query) use ($filter) {
                $query->where('users.virtual_account', $filter);
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
            });
        }
        $query = $query->get();
        // $user = User::where(['agent_id'=>auth()->user()->id])->get();
        if ($query) {
            if (count($query) > 0) {
                return response()->json(['status' => true, 'message' => $query], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No record!"], 400);
            }
        } else {
            return response()->json(['status' => false, 'message' => "No record!"], 400);
        }
    }
    public function topup_script(Request $request)
    {
        // \logInfo('Mobile App Esusu deposit ', "Esusu deposit");
        try {
            $validator = Validator::make($request->all(), [
                'esusuid' => 'required',
                'amount'  => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    // Go through each message for this field.
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = [
                    'message' => $errors,
                    'status'  => 0,
                ];
                return response()->json($datax);
            }
            if ($request->amount < 1) {
                return response()->json(['status' => false, 'message' => "Amount should be greater than zero!"], 400);
            }
            $agent_id  = auth()->user()->id;
            if (auth()->user()->is_agent != 1) {
                return response()->json(["status" => false, "message" => "Only approve agent can perform such action"], 400);
            }
            $agentAccount = wb_account_details::where(['user_id' => $agent_id])->first();
            $agent        = $agentAccount->user_wallet;
            if ($agent->balance < $request->amount) {
                return response()->json(['status' => false, 'message' => "Insufficent Balance!"], 400);
            }
            $esusu = Esusu::where(['id' => $request->esusuid, 'hasAgent' => 1])->first();
            if (! $esusu) {
                return response()->json(['status' => 0, 'message' => 'Error Processing Request! ' . $request->esusuid]);
            }
            $start_date = $esusu->commence_date;
            $wb_id      = $esusu->wb_account_detail_id;
            $cash       = $request->amount;
            if (($cash % $esusu->rate) > 0) { //validate input
                return response()->json(['status' => false, 'message' => "Invalide Topup Amount"], 400);
            }
            $number       = $cash / $esusu->rate;
            $esusuid      = $request->esusuid;
            $rate         = $esusu->rate;
            $walletfinder = wb_account_details::where(['id' => $wb_id])->first();
            if (! $walletfinder) {
                return response()->json(['status' => 0, 'message' => 'Error Processing Account!'], 400);
            }
            $balanceBefore = $walletfinder->user_wallet->balance;
            $transactions  = transactions::where('account_id', $wb_id)->orderBy('id', 'desc')->first();
            if ($transactions) {
                $dbtimestamp = strtotime($transactions->created_at);
                if (time() - $dbtimestamp < 1 * 60) {
                    return response()->json(["status" => 0, "message" => env('txn_restrict_msg')]);
                }
            }

            DB::beginTransaction();
            $created_by           = User::where('id', auth()->user()->id)->first();
            $agent_balance_before = $agent->balance;
            $agent_balance_after  = $agent->balance - $cash;
            $Agent_user_wallet    = user_wallet::where('id', $agent->id)->update(['balance' => $agent_balance_after]);
            $transactionId        = generate_txnID();
            $backdate             = date('Y-m-d h:i:s');
            if (EsusuTXN::where(['esusu_id' => $esusuid])->wheredate('created_date', '=', $start_date)->count() == 0) {
                $this->commissionAgentCompany(['rate' => $rate, 'agent_id' => $agent_id, 'created_by' => $created_by]);
            }
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(86, $created_by->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $agentdata = [
                "balance_before"         => $agent_balance_before,
                "amount"                 => $cash,
                "account_id"             => $agentAccount->id,
                "description"            => "Debited for crediting $walletfinder->virtual_accnum",
                "branch_id"              => $created_by->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 86, //debit 7
                "credit_debit_type_id"   => 2,  //debit 2
                "transaction_remarks_id" => 2,  //success 2
                "balance_after"          => $agent_balance_after,
                "created_date"           => $backdate,
                "created_by"             => $created_by->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ];
            $transaction_agent = transactions::create($agentdata);
            if (! $transaction_agent) {
                return response()->json(['status' => 0, 'message' => "Error processing agent transaction"], 400);
            }

            $balanceAfter    = $balanceBefore + $cash;
            $transactionId   = generate_txnID();
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(35, $created_by->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $data_credit = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $cash,
                "description"            => "Esusu Wallet Deposit",
                "account_id"             => $wb_id,
                "branch_id"              => $walletfinder->user->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 35, //debit 7
                "credit_debit_type_id"   => 1,  //debit 2
                "transaction_remarks_id" => 2,  //success 2
                "balance_after"          => $balanceAfter,
                "created_date"           => $backdate,
                "created_by"             => $created_by->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ];
            $esusu->withdrawable += $cash;

            $duration          = $esusu->esusu_type->duration;
            $this_date_deposit = EsusuTXN::where(['esusu_id' => $esusuid])->wheredate('created_date', '=', $start_date)->sum('no_card');
            $count_num_sofar   = $this_date_deposit + $number;
            if ($count_num_sofar > $duration) {
                $supposenum = $count_num_sofar - $duration;
                return response()->json(['status' => 0, 'message' => "Number of input exceed maximum required to complete this period. Put a number to complete the first period! $count_num_sofar $duration extra ($supposenum) "], 400);
            }
            $suppose_day        = add_day_to_date($duration, $start_date);
            $days_so_far        = diff_btw_date($start_date, $backdate);
            $transaction_credit = transactions::create($data_credit);
            $EsusuTXN           = EsusuTXN::create(['transaction_id' => $transaction_credit->id, 'esusu_id' => $esusuid, 'no_card' => $number, 'created_date' => $esusu->commence_date]);
            if ($esusu->save()) {
                $Esusulog_circle_out = Esusulog::where(['esusu_id' => $esusuid])->count();
                if (($count_num_sofar >= $duration)) {
                    // circle_out_charge
                    $circle_charge_percentage  = $esusu->esusu_type->circle_charge;
                    $circle_charge             = ($circle_charge_percentage / 100) * $rate;
                    $esusu_circleout           = Esusulog::where(['esusu_id' => $esusuid])->count();
                    $today_card                = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '=', date('d'))
                        ->sum('no_card');
                    $prev_card  = EsusuTXN::where(['esusu_id' => $esusuid])->whereDay('created_at', '<', date('d'))
                        ->sum('no_card');
                    $today_cash  = $today_card * $rate;
                    $logtxn      = Esusulog::create([
                        'circle_out_charge' => $circle_charge,
                        'esusu_id'          => $esusuid,
                        'rate'              => $rate,
                        'commence_date'     => $esusu->commence_date,
                        'prev_card'         => $prev_card,
                        'today_card'        => $today_card,
                        'today_cash'        => $today_cash,
                        'circle_out'        => $esusu_circleout,
                        'withdrawable'      => $esusu->withdrawable,
                        'wallet'            => $esusu->wallet,
                        'overdraft'         => $esusu->overdraft,
                        'missed_day'        => $esusu->missed_day,
                        'status_id'         => 1,
                    ]);
                    $esusu->wallet         = $wallet         = ($esusu->wallet + ($esusu->withdrawable));
                    $esusu->accum_charge  += $circle_charge;
                    $esusu->withdrawable   = 0;
                    $esusu->commence_date  = add_day_to_date(1, $start_date);
                    $esusu->save();
                }
                DB::commit();
                return response()->json(["status" => true, "message" => "Topup Successful!"], 200);
            }
            return response()->json(["status" => 0, "message" => "Error processing request"], 400);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["status" => 0, "message" => $e->getMessage() . $e->getLine()], 400);
        }
    }
    public function commissionAgentCompany($commisionDetails)
    {
        //esusu_comission increament 50% of the proc to agent

        try {
            $rate                 = $commisionDetails['rate'];
            $agent_id             = $commisionDetails['agent_id'];
            $created_by           = $commisionDetails['created_by'];
            $agentAccount         = wb_account_details::where(['user_id' => $agent_id])->first();
            $agent                = $agentAccount->user_wallet;
            $agent_balance_before = $agent->balance;
            $agent_profit         = ($rate / 2);

            $agent_balance_after = $agent_balance_before + $agent_profit;
            $transactionId       = generate_txnID();
            $ledger_cr           = null;
            $ledger_dr           = null;
            $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(87, $created_by->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $agentdata = [
                "balance_before"         => $agent_balance_before,
                "amount"                 => $agent_profit,
                "account_id"             => $agentAccount->id,
                "description"            => "Credit from $agentAccount->virtual_accnum Esusu Commission",
                "branch_id"              => $created_by->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 87, //debit 7
                "credit_debit_type_id"   => 1,  //debit 2
                "transaction_remarks_id" => 2,  //success 2
                "balance_after"          => $agent_balance_after,
                "created_date"           => now(),
                "created_by"             => $created_by->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ];
            $transaction_agent = transactions::create($agentdata);
            $Agent_user_wallet = user_wallet::where('id', $agent->id)->update(['balance' => $agent_balance_after]);
            //add to company vault
            fundVault(['type' => 'Agent_Esusu_profit', 'value' => $agent_profit, 'company_id' => $created_by->company_id, 'created_by' => $created_by->id, 'branch_id' => $created_by->branch_id, 'transaction_id' => $transaction_agent->id]);
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function requestAgent(Request $request)
    {
        $agentcount = AgentRequest::where('user_id', $request->user_id)->count();
        try {
            if ($agentcount == 0) {
                $agent          = new AgentRequest;
                $agent->user_id = auth()->user()->id;
                $agent->remark  = "=> Agent Request <br>";
                $agent->save();
                User::where('id', auth()->user()->id)->update(['is_agent' => 2]);
                return response()->json(["status" => 1, "message" => "Request Sent!"], 200);
            } else {
                return response()->json(["status" => 0, "message" => "Request Already Pending!"], 400);
            }
        } catch (Exception $e) {
            return response()->json(["status" => 0, "message" => $e->getMessage()], 400);
        }
    }
    public function esusuProduct()
    {
        $esusu_types = esusu_types::where(['company_id' => auth()->user()->company_id, 'status' => 'active'])->get();
        if (count($esusu_types) > 0) {
            return response()->json(["status" => 1, "message" => $esusu_types], 200);
        } else {
            return response()->json(["status" => 0, "message" => "No Product Selected"], 400);
        }
    }
    public function EsusTXNhistory(Request $request)
    {
        $transactions = transactions::with('creditDebitType')->with('type')->with('remark')
            ->where('created_by', auth()->user()->id)
            ->where('transaction_type_id', '>=', '86')
            ->where('transaction_type_id', '<=', '87');
        if ($request->from != null) {
            $transactions = $transactions->where('created_at', '>=', $request->from);
        }
        if ($request->to != null) {
            $transactions = $transactions->where('created_at', '<=', $request->to);
        }
        // if($request->from !=null){ $transactions = $transactions->where(['transaction_reference'=>$request->txn]);}
        $transactions = $transactions->get();
        if (count($transactions) > 0) {
            return response()->json(["status" => 1, "message" => $transactions], 200);
        } else {
            return response()->json(["status" => 0, "message" => "No record found"], 400);
        }
    }
    public function initWithdraw(Request $request)
    {
        \logInfo('Mobile App Esusu Withdrawal ', "Esusu withdraw");
        try {
            $validator = Validator::make($request->all(), [
                'esusuid' => 'required',
                'amount'  => 'required',
            ]);
            if ($validator->fails()) {
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    // Go through each message for this field.
                    foreach ($messages as $message) {
                        $errors .= "$message ";
                    }
                }
                $datax = [
                    'message' => $errors,
                    'status'  => 0,
                ];
                return response()->json($datax);
            }
            if ($request->amount < 1) {
                return response()->json(['status' => false, 'message' => "Amount should be greater than zero!"], 400);
            }
            $auth        = auth()->user();
            $created_by  = User::where('id', $auth->id)->first();
            $esusu       = Esusu::where(['id' => $request->esusuid, 'hasAgent' => 1])->first();
            //if(auth()->user()->is_agent != 1){ return response()->json(["status" => false, "message" => "Only approve agent can perform such action"], 400);}
            $wb_id         = $esusu->wb_account_detail_id;
            $cash          = $request->amount;
            $number        = $cash / $esusu->rate;
            $walletfinder  = wb_account_details::where(['id' => $wb_id])->first();
            if (! $walletfinder) {
                return response()->json(['status' => 0, 'message' => 'Error Processing Account!'], 400);
            }
            if ($esusu->wallet < $cash) {
                return response()->json(['status' => 0, 'message' => 'Insufficient Fund!'], 400);
            }
            $company_wallet = company_wallet::where('company_id', $auth->company_id)->first();
            if (! $company_wallet) {
                return response()->json(['status' => 0, 'message' => 'Error!'], 400);
            }
            if ($company_wallet->balance < $cash) {
                return response()->json(['status' => 0, 'message' => 'Error(409) Insufficient Fund!'], 400);
            }

            $company_wallet->balance -= $cash;

            $data_caompany = [
                "balance_before"         => ($company_wallet->balance += $cash),
                "amount"                 => $cash,
                "withdrawal_charge"      => 0,
                "description"            => 'Esusu App Withdrawal',
                //   "account_id" => $account_typeid,
                "branch_id"              => $auth->branch_id,
                "transaction_reference"  => generate_txnID(),
                "transaction_type_id"    => 7, //debit 7
                "credit_debit_type_id"   => 2, //debit 2
                "transaction_remarks_id" => 1, //success 2
                "balance_after"          => $company_wallet->balance,
                "created_date"           => date('Y-m-d H:i:s'),
                "created_by"             => $auth->id,
            ];

            $company_wallet->balance -= $cash;
            $ledger_cr               = null;
            $ledger_dr               = null;
            $ledger_Settings         = get_CR_DR_TXN_Type_Ledger(7, $auth->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $data_caompany = [
                "balance_before"         => ($company_wallet->balance += $cash),
                "amount"                 => $cash,
                "withdrawal_charge"      => 0,
                "description"            => 'Esusu App Withdrawal',
                //   "account_id" => $account_typeid,
                "branch_id"              => $auth->branch_id,
                "transaction_reference"  => generate_txnID(),
                "transaction_type_id"    => 7, //debit 7
                "credit_debit_type_id"   => 2, //debit 2
                "transaction_remarks_id" => 1, //success 2
                "balance_after"          => $company_wallet->balance,
                "created_date"           => date('Y-m-d H:i:s'),
                "created_by"             => $auth->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ];

            $balanceBefore  = $esusu->wallet;
            $balanceAfter   = $esusu->wallet   = $esusu->wallet - $cash;
            DB::beginTransaction();
            $transactionId  = generate_txnID();
            //walletfinder
            if ($walletfinder->bank->bank_code == null) {
                return response()->json(['status' => 0, 'message' => 'Error processing Bank Info!'], 400);
            }
            $bankcode       = $walletfinder->bank->bank_code;
            $virtual_accnum = $walletfinder->virtual_accnum;
            $customerName   = $walletfinder->user->first_name . ' ' . $walletfinder->user->last_name;
            // die("$bankcode $virtual_accnum $customerName");
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(88, $created_by->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $data_credit = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $cash,
                "description"            => "Esusu Withdrawal Request",
                "account_id"             => $wb_id,
                "branch_id"              => $walletfinder->user->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 88, //esusu Pending txn 88
                "credit_debit_type_id"   => 2,  //debit 2
                "transaction_remarks_id" => 1,  //success 2
                "balance_after"          => $balanceAfter,
                "created_date"           => date("Y-m-d"),
                "created_by"             => $created_by->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ];
            $transaction = transactions::create($data_credit);
            $esusu->save();
            $transactionCompany = transactions::create($data_caompany);
            //the above code as the following error Uncaught Error: Canvas is already in use. Chart with ID '0' must be destroyed before the canvas with ID '' can be reused.
            DB::commit();
            $company_wallet->save();
            $desc = $data_credit['description'];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://partner.com/api/bank-transfer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => '{
            "amount": "' . $cash . '",
            "transaction_reference": "' . $transactionId . '",
            "destination_account": "' . $virtual_accnum . '",
            "receipient_name": "' . $customerName . '",
            "bank_code": "' . $bankcode . '",
            "description": "' . $desc . '",

            "api_key": "yourkey"
        }',
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);
            $cur = json_decode($response);
            if ($cur->status) {
                $transaction->transaction_remarks_id = 2;
                $transaction->save();
                $transactionCompany->transaction_remarks_id = 2;
                $transactionCompany->save();
                return response()->json(['status' => 1, 'message' => "Withdrawal Successful ($cur->message)!", 'data' => $transaction], 200);
            } else {
                return response()->json(['status' => 0, 'message' => $cur->message], 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["status" => 0, "message" => $e->getMessage() . $e->getLine()], 400);
        }
    }

    public function playground()
    {
        echo 10 % 3;
    }
    public function deleteRequest()
    {
        try {
            $User = User::where('id', auth()->user()->id)->update(['status_id' => 13]);
            return response()->json(["status" => true, "message" => "Account Deleted"], 200);
        } catch (Exception $e) {
            return response()->json(["status" => false, "message" => "Operation Failed" . $e->getMessage()], 400);
        }
    }

    public function addAccount(Request $request)
    {
        try {
            $user = User::where(['user_type_id' => '1', 'uuid' => $request->uuid, 'company_id' => auth()->user()->company_id]);

            $user = $user->first();
            \logInfo(json_encode($user), "api saving account user");
            \logInfo(json_encode(auth()->user()->company_id), "api saving account company");
            \logInfo(json_encode($request->savings_product_type), "api saving account saving type");
            $bankId = 126;
            $saving = savings_product::where(['company_id' => auth()->user()->company_id, 'id' => $request->savings_product_type])->first();
            \logInfo(json_encode($saving), "api saving account ");

            $companyRecord = Company::where(['id' => auth()->user()->company_id])->first();

            //   $bank_id      = $companyRecord->setting->preferred_bank_id ?? 126;
            $bank_id      = 126;
            $selectedBank = banks::where('id', $bank_id)->first();

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = \auth()->user()->company->currency->code;
            $virtualAccount->firstName                    = $user->first_name;
            $virtualAccount->companyShortName             = auth()->user()->company->short_name;
            $virtualAccount->lastName                     = $user->last_name;
            $virtualAccount->email                        = $user->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $user->phone;
            $virtualAccount->dob                          = $user->dob;
            $virtualAccount->gender                       = $user->gender;
            $virtualAccount->bvn                          = $user->bvn;
            $virtualAccount->address                      = $user->address;
            $virtualAccount->country                      = auth()->user()->company->currency_id ?? "";
            $virtualAccount->companyAccountCreationStatus = auth()->user()->company->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            logInfo($response, "Create Account service response");
            $customer_reference      = $response['data']['account_reference'] ?? $customer_reference;
            $virtual_acccount_number = $response['data']['virtual_account'] ?? $response['data']['account_number'];

            $user->virtual_account   = $virtual_acccount_number;
            $user->account_reference = $customer_reference;
            $user->save();

            if ($saving) {
                if ($user) {
                    $wb = wb_account_details::where(['account_product_id' => $saving->id, 'user_id' => $user->id])->first();
                    if ($wb && ($request->replace_account != "yes")) {
                        return response()->json(['status' => 0, 'message' => 'Error! Account Type Already Exist']);
                    }

                    if ($request->replace_account == "yes") {
                        $wb_account_details = wb_account_details::where('user_id', $user->id)->first();
                        $wb_account_details->update([
                            'virtual_accnum' => $virtual_acccount_number,
                            'acc_reference'  => $customer_reference,
                            'bankid'         => $bankId,
                            "currency_id"    => $request->currency_id,
                        ]);
                    } else {

                        $wb_account_details = wb_account_details::create([
                            'user_id'            => $user->id,
                            'account_product_id' => $saving->id,
                            'bankid'             => $bankId,
                            'virtual_accnum'     => $virtual_acccount_number,
                            'acc_reference'      => $customer_reference,
                            'has_atm_card'       => 0,
                            "currency_id"        => $request->currency_id,
                        ]);
                        user_wallet::create([
                            'wb_account_detail_id' => $wb_account_details->id,
                            'balance'              => 0,
                        ]);
                    }

                    return response()->json(['status' => 1, 'message' => 'Account Added!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
                }
            } else {
                return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        }
    }

    private function sendTierReportEmail($subtext, $msg, $reason, $user)
    {

        $data = [
            'subtext'      => $subtext,
            'reason'       => $reason,
            'desc'         => $msg,
            'company_name' => auth('api')->user()->company->company_name,
            'user'         => auth('api')->user()->company->company_name,
        ];

        try {
            Mail::to(auth('api')->user()->company->company_email)->send(new SendTierMail($data));
        } catch (\Throwable $e) {
            \Log::error("Failed to send TierReport email: " . $e->getMessage());
            // continue silently
        }
    }

}
