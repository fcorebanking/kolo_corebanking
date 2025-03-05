<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function sendOTP()
    {
        $data = [
            'category_name'    => 'smsOTP',
            'page_name'        => 'smsOTP',
            'page_menu'        => 'smsOTP',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('auth.2faSMS')->with($data);
    }
    public function verifyOTP(Request $request)
    {
        $user = $request->user();
        if ($user->otp_expiration_date < now()) {
            return response()->json(["status" => 0, "message" => "OTP Expired!"]);
        }
        if ($user->otp == $request->otp) {
            $user->update(['login_sms_otp_status' => 1]);
            return response()->json(["status" => 1, "message" => "Verification Complete"]);
        } else {
            return response()->json(["status" => 0, "message" => "Verification Failed"]);
        }
    }
    public function resendOTP(Request $request)
    {
        $user             = $request->user();
        $otpgen           = generateOtp();
        $current_time     = date("Y-m-d H:i:s");                                          // get current time
        $incremented_time = date("Y-m-d H:i:s", strtotime($current_time . "+5 minutes")); // add 5 minutes
        $user->update(['otp' => $otpgen, 'otp_expiration_date' => $incremented_time]);
        $sendSMS = sendSMS($user->id, "Your 2Factor OTP is $otpgen at " . now(), true, false, 'Finova Login Security');
        \logInfo($sendSMS, "resend OTP response");
        return response()->json(["status" => 1, "message" => "OTP Sent!"]);
    }
}
