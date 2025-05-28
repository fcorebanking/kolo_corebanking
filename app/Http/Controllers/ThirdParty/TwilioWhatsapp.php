<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\AgentTerminal;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class TwilioWhatsapp extends Controller
{

    public function send($message, $phone, $type)
    {

        if (env('APP_ENV') == "local") {

            $twilio        = new Client(env('TWILIO_ACCOUNT_SID_TEST'), env('TWILIO_AUTH_TOKEN_TEST'));
            $whatsappPhone = "whatsapp:" . env('TWILIO_WHATSAPP_NUMBER_TEST');

        } else {

            $twilio        = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
            $whatsappPhone = "whatsapp:" . env('TWILIO_WHATSAPP_NUMBER');
        }

        try {
            $twilio = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

            if ($type == "response") {
                $message = $twilio->messages->create($phone, [
                    'from' => $whatsappPhone,
                    'body' => $message,
                ]);
            } else {
                $message = $twilio->messages->create("whatsapp:" . $phone, [
                    'from' => $whatsappPhone,
                    'body' => $message,
                ]);
            }
            \logInfo($message->sidl, "Twilio Response Message");

        } catch (\Exception $e) {
            \logInfo(json_encode($e), "Twilio Response Error Message");

        }

    }

    public function getUpdates(Request $request)
    {

        $message = "*Quick Options*\n*A* - Open Account\n*B* - Transfer\n*C* - Check Account Balance\n*D* - POS Balance\n*E* - Change Pin\n*F* - Change Pasword\n*G* - Buy Data\n*H* - Pay Bills\n*I* - Get OTP\n";

        \logInfo(json_encode($request->From), "Twilio Response From");

        \logInfo(json_encode($request->Body), "Twilio Response Body Message");

        $phone       = $request->From;
        $type        = "response";
        $batch       = null;
        $channel     = null;
        $string      = $request->Body;
        $stringArray = explode(',', $string); // String array

        \logInfo($stringArray[0], "Twilio Response String Message");

        if (isset($stringArray[1])) {
            \logInfo($stringArray[1], "Twilio Response String 2 Message");

        }

        if ($string == "A" || $string == strtolower("A")) {
            $message = $this->openAccount($request, $stringArray);
        }
        if ($string == "B" || $string == strtolower("B")) {
            $message = $this->transfer($request, $stringArray);
        }
        if ($string == "C" || $string == strtolower("C")) {
            $message = $this->getAccountBalance($request, $stringArray);
        }
        if ($string == "D" || $string == strtolower("D")) {
            $message = $this->getPOSBalance($request, $stringArrayt);
        }
        if ($string == "E" || $string == strtolower("E")) {
            $message = $this->changePin($request, $stringArray);
        }

        if ($string == "F" || $string == strtolower("F")) {
            $message = $this->getOperatorResult($request);
        }

        if ($string == "G" || $string == strtolower("G")) {
            $message = $this->getOperatorResult($request);
        }

        if ($string == "H" || $string == strtolower("H")) {
            $message = $this->getOperatorResult($request);
        }

        if ($string == "I" || $string == strtolower("I")) {
            $message = $this->getOperatorResult($request);
        }

        \logInfo($message, "Twilio Respond Whatsapp Message");

        $this->send($message, $phone, $batch, $channel, $type);
    }

    public function getAccountBalance(Request $request, $stringArray)
    {
        $user = $this->getUser($request);

        if ($user != null) {
            $customerAccountId = wb_account_details::where('user_id', $user->virtual_account)->first()->id;
            $userWallet        = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

            return "*Wallet Balance* - {$userWallet->balance}\n";
        } else {
            return "User does not exist";
        }

    }

    public function getPOSBalance(Request $request, $stringArray)
    {
        $user    = $this->getUser($request);
        $userPOS = AgentTerminal::where('user_id', $user->id)->first();

        if ($userPOS != null) {

            return "*POS Balance* - {$userPOS->balance}\n";
        } else {
            return "User does not exist";
        }

    }

    public function openAccount(Request $request, $stringArray)
    {
        if (isset($stringArray[1]) && ($stringArray[0] == "Forecast" || $stringArray[0] == strtolower("Forecast"))) {
            $checkGame = $this->checkOperatorGame($stringArray[1]);
            file_put_contents(public_path('twilio.txt'), PHP_EOL . json_encode($checkGame), FILE_APPEND);
            $message = $this->getForecastGame($request, $stringArray);
        }
    }

    public function getTransactionPinMessage(Request $request, $stringArray)
    {
        $user = $this->getUser($request);
        if ($user != null) {
            $otp = $this->otpcode($user);
            $message .= "*{otpcode}* - {$otp}\n\n";
            $message .= "*Type Transaction Pin.*";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function getChangePinMessage(Request $request, $stringArray)
    {
        $user = $this->getUser($request);

        if ($user != null) {
            $otp = $this->otpcode($user);
            $message .= "*{otpcode}* - {$otp}\n\n";
            $message .= "*Type Transaction Pin. (changepin, pin, otpcode).\n\nFollow the example below*\n\n *Example* - changepin, 1234, 5678 \n";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function changePin(Request $request, $stringArray)
    {

        if (isset($stringArray[1]) && ($stringArray[0] == "changepin" || $stringArray[0] == strtolower("changepin"))) {
         
         
            $checkGame = $this->checkOperatorGame($stringArray[1]);
            file_put_contents(public_path('twilio.txt'), PHP_EOL . json_encode($checkGame), FILE_APPEND);
            $message = $this->getForecastGame($request, $stringArray);
        }
    }

    public function otpcode($user)
    {
        $otpCode                   = generateOtp();
        $user->otp                 = $otpCode;
        $date                      = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');
        $user->otp_expiration_date = $date;
        $user->save();
        return $otpCode;
    }

    public function getUser(Request $request)
    {
        $cleanNumber = str_replace('whatsapp:', '', $request->From);

        $param['tell'] = $cleanNumber;

        $formatPhone         = $this->format_number_area_code($param);
        $formatPhoneAreaCode = $this->format_number($param);

        $user = User::where(function ($query) use ($formatPhone, $formatPhoneAreaCode) {
            $query->where('phone', $formatPhone)
                ->orWhere('phone', $formatPhoneAreaCode);
        })->where('user_type_id', 1)->first();
        return $user;
    }

    public function format_number($param)
    {
        $tell = $param['tell'];

        \logInfo($tell, "Twilio Phone");

        $tell = str_replace('+', '', $tell);
        $len  = strlen($tell);
        if ($len == 11) {
            $tell = "234" . substr($tell, 1);
        } elseif ($len == 13) {
            $tell = $tell;
        }
        \logInfo($tell, "Twilio Phone Proccessed");
        return $tell;
    }

    public function format_number_area_code($param)
    {
        $tell = $param['tell'];
        \logInfo($tell, "Twilio Phone");
        $tell = str_replace('+', '', $tell);
        $len  = strlen($tell);
        if ($len == 13) {
            $tell = "0" . substr($tell, 3);

        }
        \logInfo($tell, "Twilio Area Phone Proccessed");
        return $tell;
    }

}
