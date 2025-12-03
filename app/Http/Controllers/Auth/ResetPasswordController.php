<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\company_wallet;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(Request $data)
    {
        // Validator::extend('without_spaces', function($attr, $value){
        //     return preg_match('/^\S*$/u', $value);});
        return Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
    }

    public function resetPasswordPage(Request $request)
    {

        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'page_menu'        => '',
            'alt_menu'         => '',
            'status'           => 'success',
            'isReferenceValid' => true,
            'title'            => "Reset Password",
            'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to validate your account",
        ];
        return view('auth.passwords.forgot_password')->with($data);
    }

    protected function submitResetRequest(Request $request)
    { //regex:/^\S*$/u

        logInfo($request->all(), "Forgot Password Request");

        $validation = Validator::make($request->all(), [
            "account_number" => 'nullable|numeric',
            "username"       => 'nullable|string',
        ]);

        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'page_menu'        => '',
            'alt_menu'         => '',
            'status'           => 'success',
            'isReferenceValid' => true,
            'title'            => "Reset Password",
            'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to validate your account",
        ];

        if ($validation->fails()) {
            Session::flash("fail", $validation->errors()->all()[0]);
            return \redirect()->back()->with($data);
        }
        if ($request->has('account_number') && $request->account_number != null) {
            $companyInfo = company_wallet::where('vaccount', $request->account_number)->first();

            if (! $companyInfo) {
                Session::flash("fail", 'Invalid Company account provided');
                return \redirect()->back()->with($data);
                // return \response()->json(['status' => 0, 'message' => "Invalid Company account provided"]);
            }
            $userInfo = User::where('company_id', $companyInfo->id)->first();
        } else {
            $userInfo = User::where('username', $request->username)->first();
        }

        if (! $userInfo) {
            Session::flash("fail", 'No user found with this company');
            return \redirect()->back()->with($data);
            // return \response()->json(['status' => 0, 'message' => "No user found with this company"]);
        }

        $verificationHash = \generateAPIKey(12);

        $userInfo->updated_at        = Carbon::now();
        $userInfo->verification_hash = $verificationHash;
        $userInfo->save();

        $url = url("password/reset-password/" . $verificationHash);

        $msg = 'If you did not make this request then please ignore this email. Otherwise, please click this link to change your password:<br><br>';
        $msg .= '<a href="' . $url . '">Reset Password</a>';

        logInfo($url, "Reset Password User");

        logInfo($userInfo->email, "email data before send email");

        $sendSms = sendEmail("Request to Change Password", $msg, $userInfo);

        //  $subtext = "Reset Password";
        //   $reason  = "";
//
        //   $this->sendTransactionReportEmail($subtext, $msg, $reason, $userInfo);

        Session::flash("success", "A Reset Password email has been sent to your email account. Follow the instructions to reset account");

        return \redirect()->back()->with($data);
    }

    public function resetPassword($password_hash)
    {
        $verification = User::where('verification_hash', $password_hash)->first();

        // \logInfo($verification, );
        if ($verification) {

            if (Carbon::now()->subMinute(5) > $verification->updated_at) {

                $data = [
                    'category_name'    => 'auth',
                    'page_name'        => 'auth_default',
                    'has_scrollspy'    => 0,
                    'scrollspy_offset' => '',
                    'page_menu'        => '',
                    'alt_menu'         => '',
                    'isReferenceValid' => true,
                    'status'           => 'expired',
                    'resendUrl'        => url("resend-email/$password_hash"),
                    'title'            => "Link Expired",
                    'description'      => "This link has expired.<br>Kindly Click the button below to resend activation email",
                ];
            } else {

                $data = [
                    'category_name'     => 'auth',
                    'page_name'         => 'auth_default',
                    'has_scrollspy'     => 0,
                    'scrollspy_offset'  => '',
                    'page_menu'         => '',
                    'alt_menu'          => '',
                    'status'            => 'successful',
                    'verification_hash' => $password_hash,
                    'isReferenceValid'  => true,
                    'title'             => "Reset Password",
                    'description'       => "Your account has been activated successfully.<br>Kindly Login to Complete your profile",
                ];
            }
        } else {
            $data = [
                'category_name'    => 'auth',
                'page_name'        => 'auth_default',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'page_menu'        => '',
                'alt_menu'         => '',
                'status'           => 'failed',
                'isReferenceValid' => true,
                'title'            => "Invalid Link",
                'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to Reset your password",
            ];
        }

        return view('auth.passwords.new_password')->with($data);
    }

    public function setNewPassword(Request $request)
    {

        logInfo($request->all(), "Set New Password Request Log");

        $validation = Validator::make($request->all(), [
            "new_password"      => 'required|string',
            "confirm_password"  => 'required|string',
            "verification_hash" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => 2, "message" => $validation->errors()->all()[0]];
            return response()->json($ra);
        }

        if ($request->new_password != $request->confirm_password) {
            return \response()->json(['status' => 2, 'message' => 'Passwords do not match']);
        }

        $userAccount = User::where(['verification_hash' => $request->verification_hash])->first();

        if (! $userAccount['status']) {
            return \response()->json(['status' => 0, 'Failed to update password']);
        }

        if (Carbon::now()->subMinutes(5) > $userAccount->updted_at) {
            $userAccount->password = Hash::make($request->new_password);
            $userAccount->save();

            return \response()->json(['status' => 1, 'message' => 'Password Updated Successfully']);
        } else {
            return \response()->json(['status' => 0, 'message' => 'Password Link Expired. Kindly resend password link and try again']);
        }
    }

}
