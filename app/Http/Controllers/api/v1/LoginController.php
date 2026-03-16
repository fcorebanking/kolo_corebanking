<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Mail\TransactionReport;
use App\Models\CarouselImage;
use App\Models\Company;
use App\Models\savings_product;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // return response()->json(['status' => false, 'message' => 'Running a quick maintenace'], 401);

        logInfo($request->all());
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        if ($validate->fails()) {
            $ra = ["status" => false, "message" => "Username and password fields are required"];
            return response()->json($ra, 406);
        }
        // return response()->json(['status' => false, 'message' => 'Failed to Login. Service will be restored soon'], 401);
        // $company
        // unset($request->company);
        // $login = ['username' => $request->username];
        $login = ['username' => $request->username, 'password' => $request->password];
        // die(var_dump($login));
        if (! Auth::attempt($login)) {
            $user = User::where('username', $request->username)->where('status_id', '!=', 13)->first(); //13 is deleted
            if ($user) {
                if ($user->login_fails == 4) {
                    $user->status_id = 6;
                } else {
                    $user->login_fails += 1;
                }

                $user->save();
                if ($user->status_id == 6) {
                    return response()->json(['status' => false, 'message' => 'User Account has been blocked. Please contact admin to unlock Account'], 401);
                }

                $attemptTxt = $user->login_fails == 3 ? 'attempt' : 'attempts';
                return response()->json(['status' => false, 'message' => 'Invalid Login credentials. Your account will be deactivated after ' . (5 - $user->login_fails) . " wrong $attemptTxt"], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Invalid Login Credentials'], 401);
            }
        }

        if (Auth::user()->company->status_id == 13) {
            return response()->json(['status' => false, 'message' => 'Invalid Login Credentials'], 404);
        }
        if (Auth::user()->company->status_id != 1) {
            return response()->json(['status' => false, 'message' => 'Encounter error while trying to connect to  institution.. please try again later'], 401);
        }
        /*  if (Auth::user()->company->id == 92) {
            return response()->json(['status' => false, 'message' => 'Encounter error while trying to connect to  institution.. please try again later'], 401);
        }*/

        if (Auth::user()->user_type_id == 2) {
            return response()->json(['status' => false, 'message' => 'This Account is not a Customer Account'], 401);
        }

        if (Auth::user()->status_id == 7) {
            return response()->json(['status' => false, 'message' => 'Your Account is currently being reviewed and will be approved soon', 'user' => Auth::user()], 401);
        }

        if (Auth::user()->status_id == 5) {
            return response()->json(['status' => false, 'message' => 'Your account has been blocked. Kindly contact support to unblock.'], 401);
        }

        if (Auth::user()->status_id == 6) {
            return response()->json(['status' => false, 'message' => 'Your account has been blocked. Kindly Reset Password'], 401);
        }

        if (Auth::user()->company->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }

        if (Auth::user()->phone_verification_status_id == 2) {

            $mUser              = Auth::user();
            $mUser->login_fails = 0;
            $mUser->save();

            $virtualCard = UserCard::where('user_id', auth()->user()->id)->get();

            if (! $virtualCard) {
                $virtualCard = [];
            }

            $userWallet = user_wallet::where('wb_account_detail_id', Auth::user()->accounts->first()->id)->first();

            $myUserWallets = [];

            foreach (Auth::user()->accounts as $key => $wallet) {
                $userWallet      = user_wallet::where('wb_account_detail_id', $wallet->id)->first();
                $myUserWallets[] = ["account_id" => $wallet->id, "account_number" => $wallet->virtual_accnum, "bank_id" => $wallet->bank->id, "bank" => $wallet->bank->bank_name, 'product_name' => $wallet->accountType->savings_product, "wallet_balance" => $userWallet->balance, "currency" => $wallet->currency->code ?? 'NGN'];
            }

            //   \logInfo(json_encode($myUserWallets), "User Wallets");

            $adminUser = User::where('company_id', $request->user()->company_id)->first();

            $sliders = CarouselImage::where(['status_id' => 1, 'company_id' => auth()->user()->company_id])->get();

            $userDetails = [
                "first_name"                   => $request->user()->first_name,
                "last_name"                    => $request->user()->last_name,
                "uuid"                         => $request->user()->uuid,
                "phone"                        => $request->user()->phone,
                "profile_pic"                  => $request->user()->profile_pic ?? "",
                "username"                     => $request->user()->username,
                "bvn"                          => $request->user()->bvn,
                "bvn_status_id"                => $request->user()->bvn_status_id,
                "nin"                          => $request->user()->nin,
                "email"                        => $request->user()->email,
                "address"                      => $request->user()->address,
                "sex"                          => $request->user()->sex,
                "is_agent"                     => $request->user()->is_agent,
                // "otp" => $request->user()->otp,
                "virtual_account"              => $request->user()->virtual_account,
                "account_reference"            => $request->user()->account_reference,
                "bank_id"                      => $request->user()->bank_id,
                "validID_file"                 => $request->user()->validID_file,
                "otherID_file"                 => $request->user()->otherID_file,
                "company_id"                   => $request->user()->company_id,
                "status_id"                    => $request->user()->status_id,
                "phone_verification_status_id" => $request->user()->phone_verification_status_id,
                "account_officer_id"           => $request->user()->account_officer_id,
                "branch_id"                    => $request->user()->branch_id,
                "tier"                         => $request->user()->kyc_level_id,
                "balance"                      => $userWallet->balance,
                "wallets"                      => $myUserWallets,
                "cards"                        => $virtualCard,
                "sliders"                      => $sliders,
                "loanCount"                    => count($request->user()->allLoan),
                "company"                      => ["phone" => $adminUser->company->company_phone, "email" => $adminUser->company->company_email],
                "is_card_available"            => count($virtualCard) > 0,
                "currency"                     => $request->user()->company->currency->code,

            ];

            $userDetails['is_card_available'] = count($virtualCard) > 0;

            $accessToken = auth()->user()->createToken('authToken')->accessToken;

            // $accessToken = $this->getTokenAndRefreshToken($request->username, $request->password);

            return response(['status' => true, 'message' => 'Login Successful', 'data' => ['user' => $userDetails, 'access_token' => $accessToken]]);
        } else {

            $userAccunt = Auth::user();

            $otpCode = \generateOtp();

            $userAccunt->otp = $otpCode;
            $date            = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

            $userAccunt->otp_expiration_date = $date;
            $userAccunt->save();
            $adminUser      = User::where('company_id', $request->user()->company_id)->first();
            $getCompanyName = $adminUser->company->short_name;

            $msg = "kindly find OTP.. $otpCode  Do not share with anyone. ($getCompanyName) alternative dial *7006*80*00%23 to verify your phone without OTP.";

            if (Auth::user()->company->id == 33) {
                $phone            = $userAccunt->phone;
                $transaction_type = "otp";
                $code             = $otpCode;
                goldencreditSMSPost($phone, $transaction_type, $code);
            } else {
                $sendSMS = \sendSMS($userAccunt->id, $msg, true, false, "OTP Confirmation", '', 0, true);
                \logInfo(json_encode($sendSMS), "My SMS response to pending login");
            }

            $subtext = "OTP Confirmation";
            $reason  = "";
            $this->sendEmail($subtext, $msg, $reason, $userAccunt);
            return response()->json(['status' => true, 'message' => 'Account Pending Verification', 'data' => ['user_reference' => Auth::user()->uuid, "phone_number" => Auth::user()->phone]], 203);
        }
    }

    public function getTokenAndRefreshToken($email, $password)
    {

        $http     = new Client;
        $response = $http->request('POST', 'http://safe.wbalite.com/oauth/token', [
            'form_params' => [
                'grant_type'    => 'password',
                'client_id'     => 1,
                'client_secret' => "yGTxmSZ0rwMzaP0xt5EE2GPmy5PSj4TVQS6zQkge",
                'username'      => $email,
                'password'      => $password,
                'scope'         => '*',
            ],
        ]);
        $result = json_decode((string) $response->getBody(), true);
        return response()->json($result, $this->successStatus);
    }

    private function sendEmail($subtext, $msg, $reason, $user)
    {

        $data = [
            'subtext'      => $subtext,
            'reason'       => $reason,
            'desc'         => $msg,
            'company_name' => $user->company->company_name,
            'user'         => $user->company->company_name,
        ];

        try {
            Mail::to($user->email)->send(new TransactionReport($data));
        } catch (\Throwable $e) {
            \Log::error("Failed to send TransactionReport email: " . $e->getMessage());
            // continue silently
        }
    }
}
