<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\api\v1\TransactionController;
use App\Http\Controllers\Controller;
use App\Models\AgentTerminal;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            \logInfo(json_encode($message), "Twilio Response Message");

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
            $message = $this->getTransferMessage($request, $stringArray);
        }
        if ($string == "C" || $string == strtolower("C")) {
            $message = $this->getAccountBalance($request, $stringArray);
        }
        if ($string == "D" || $string == strtolower("D")) {
            $message = $this->getPOSBalance($request, $stringArray);
        }
        if ($string == "E" || $string == strtolower("E")) {
            $message = $this->getChangePinMessage($request, $stringArray);
        }

        if ($string == "F" || $string == strtolower("F")) {
            $message = $this->getChangePasswordMessage($request, $stringArray);
        }

        if ($string == "G" || $string == strtolower("G")) {
            $message = $this->getBuyDataMessage($request);
        }

        if ($string == "H" || $string == strtolower("H")) {
            $message = $this->getPayBillMessage($request);
        }

        if ($string == "I" || $string == strtolower("I")) {
            $message = $this->getOtp($request);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "changepin" || $stringArray[0] == strtolower("changepin"))) {
            $message = $this->changePin($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "changepassword" || $stringArray[0] == strtolower("changepassword"))) {

            $message = $this->changePassword($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "transfer" || $stringArray[0] == strtolower("transfer"))) {
            $transferOption = $this->cleanString($stringArray[1]);

            if (($transferOption == "A" || $transferOption == strtolower("A")) || ($transferOption == "B" || $transferOption == strtolower("B"))) {
                $message = $this->initializeTransactionMessage();
            }

            if (is_numeric($transferOption) || (int) $transferOption == $transferOption) {
                $message = $this->initializeTransactionMessage();
            }

        }

        \logInfo($message, "Twilio Respond Whatsapp Message");

        $this->send($message, $phone, $type);
    }

    public function getAccountBalance(Request $request, $stringArray)
    {
        $user = $this->getUser($request);
        \logInfo(json_encode($user), "Twilio get Account Balance Whatsapp Message");
        if ($user != null) {
            $customerAccountId = wb_account_details::where('user_id', $user->id)->first()->id;
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

    public function getChangePinMessage(Request $request, $stringArray)
    {
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {
            $otp = $this->otpCode($user);
            $message .= $otp;
            $message .= "*Type Transaction Pin. (changepin, newpin, otpcode).\n\nFollow the example below to change your Pin*\n\n *Example* - changepin, 1234, 5678 \n";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function changePin(Request $request, $stringArray)
    {
        $newPin = $this->cleanString($stringArray[1]);
        $otp    = $this->cleanString($stringArray[2]);
        $user   = $this->getUser($request);
        if ($user != null) {
            $checkOTP = $this->validateOTP($user, $otp);
            if ($checkOTP) {
                $user->pin = $newPin;
                $user->save();
                $message .= "*Pin has been changed successfully*\n";
            } else {
                $message .= $checkOTP;
            }

            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function getChangePasswordMessage(Request $request, $stringArray)
    {
        $user = $this->getUser($request);

        if ($user != null) {
            $otp = $this->otpCode($user);
            $message .= $otp;
            $message .= "*Type Transaction Pin. (changepassword, newpassword, otpcode).\n\nFollow the example below to change yor password*\n\n *Example* - changepassword, 1234, 5678 \n";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function changePassword(Request $request, $stringArray)
    {
        $newPassword = $this->cleanString($stringArray[1]);
        $otp         = $this->cleanString($stringArray[2]);
        $user        = $this->getUser($request);

        if ($user != null) {
            $checkOTP = $this->validateOTP($user, $otp);
            if ($checkOTP) {
                $user->password    = Hash::make($newPassword);
                $user->login_fails = 0;
                $user->status_id   = 1;
                $user->save();
                $message .= "*Password has been changed successfully*\n";
            } else {
                $message .= $checkOTP;
            }

            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function getTransferMessage($request, $stringArray)
    {
        $message .= "*Choose Type of Transfer*.\n\nFollow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n ";
        return $message;
    }

    public function getTransferAccountMessage($request, $stringArray)
    {
        $user = $this->getUser($request);

        if ($user != null) {

            $message .= "*Transfer to Bank Account (transfer, amount)*.\n\nFollow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n ";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function getTransferWalletMessage($request, $stringArray)
    {
        $user = $this->getUser($request);

        if ($user != null) {

            $message .= "*Choose Type of Transfer*.\n\nFollow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n ";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function initializeTransactionMessage()
    {
        $message .= "*Initialise Transfer Trasaction*.\n\nFollow the example below to initialize transfer*\n\n *Example* - transfer, 5000, send to ola \n ";
        return $message;

    }

    public function initializeTransaction($request, $stringArray)
    {
        $amount      = $this->cleanString($stringArray[1]);
        $description = $this->cleanString($stringArray[1]);

        $user = $this->getUser($request);

        $transactionController     = new TransactionController();
        $request                   = new Request;
        $request->final_amount     = $amount;
        $request->transaction_type = 4;
        $request->description      = $description;
        $transaction               = $transactionController->initiateTransaction($request);

        \logInfo($transaction, "Twilio Respond Whatsapp Message");

        $initiateTransaction                     = json_decode($transaction, true);
        $initiateTransactionMessage              = $initiateTransaction['message'];
        $initiateTransactionCharge               = $initiateTransaction['data']['charge'];
        $initiateTransactionTransactionReference = $initiateTransaction['data']['transaction_reference'];
        $initiateTransactionTransactionTotal     = $initiateTransaction['data']['total'];

        $message .= "*$initiateTransactionMessage*\n\n *charge* - $initiateTransactionCharge\n*transaction_reference* - $initiateTransactionTransactionReference\n ";
        return $message;

    }

    public function otpCode($user)
    {
        $otpCode                   = generateOtp();
        $user->otp                 = $otpCode;
        $date                      = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');
        $user->otp_expiration_date = $date;
        $user->save();
        return "*{otpcode}* - {$otpCode}\n\n";
    }

    public function validateOTP($user, $otp)
    {
        if ($user->otp == $otp) {
            return true;
        } else {
            return "*OTP is invalid*";
        }
    }

    public function validatePin($user, $pin)
    {
        if ($user->pin == $pin) {
            return true;
        } else {
            return "*PIN is invalid*";
        }
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

    public function cleanString($input)
    {
        return str_replace(' ', '', $input);
    }

}
