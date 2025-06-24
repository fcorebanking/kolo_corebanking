<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use App\Models\AgentTerminal;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $message = "*Quick Options*\n*1* - Open Account\n*2* - Transfer\n*3* - Check Account Balance\n*4* - POS Balance\n*5* - Change Pin\n*6* - Change Pasword\n*7* - Buy Data\n*8* - Pay Bills\n*9* - Get OTP\n";

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

        if ($string == "1") {
            $message = $this->openAccount($request, $stringArray);
        }
        if ($string == "2") {
            $message = $this->getTransferMessage($request, $stringArray);
        }
        if ($string == "3") {
            $message = $this->getTransferPinMessage($request, $stringArray);
        }
        if ($string == "4") {
            $message = $this->getTransferPinMessage($request, $stringArray);
        }
        if ($string == "5") {
            $message = $this->getChangePinMessage($request, $stringArray);
        }

        if ($string == "6") {
            $message = $this->getChangePasswordMessage($request, $stringArray);
        }

        if ($string == "7") {
            $message = $this->getBuyDataMessage($request);
        }

        if ($string == "8") {
            $message = $this->getPayBillMessage($request);
        }

        if ($string == "9") {
            $message = $this->getOtp($request);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "changepin" || $stringArray[0] == strtolower("changepin"))) {
            $message = $this->changePin($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "changepassword" || $stringArray[0] == strtolower("changepassword"))) {

            $message = $this->changePassword($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "transferservice" || $stringArray[0] == strtolower("transferservice"))) {
            $message = $this->changePin($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "balance" || $stringArray[0] == strtolower("balance"))) {

            $message = $this->getAccountBalance($request, $stringArray);
        }
        if (isset($stringArray[1]) && ($stringArray[0] == "posbalance" || $stringArray[0] == strtolower("posbalance"))) {

            $message = $this->getPOSBalance($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "transfer" || $stringArray[0] == strtolower("transfer"))) {
            $transferOption = $this->cleanString($stringArray[1]);

            if (($transferOption == "A" || $transferOption == strtolower("A")) || ($transferOption == "B" || $transferOption == strtolower("B"))) {
                $message = $this->initializeTransactionMessage();
            }

            if (is_numeric($transferOption) || (int) $transferOption == $transferOption) {
                $message = $this->initializeTransaction($request, $stringArray);
            }

        }

        \logInfo($message, "Twilio Respond Whatsapp Message");

        $this->send($message, $phone, $type);
    }

    public function getAccountBalance(Request $request, $stringArray)
    {
        $user           = $this->getUser($request);
        $transactionPin = $this->cleanString($stringArray[1]);
        $validatePin    = $this->validatePin($user, $transactionPin);
        \logInfo(json_encode($user), "Twilio get Account Balance Whatsapp Message");
        \logInfo(json_encode($validatePin), "Twilio validate pin");
        if ($user != null) {
            if ($validatePin != false) {
                $customerAccountId = wb_account_details::where('user_id', $user->id)->first()->id;
                $userWallet        = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

                return "*Wallet Balance* - {$userWallet->balance}\n";
            } else {
                return $this->getInvalidPinMessage();
            }
        } else {
            return "User does not exist";
        }

    }

    public function getPOSBalance(Request $request, $stringArray)
    {
        $user           = $this->getUser($request);
        $userPOS        = AgentTerminal::where('user_id', $user->id)->first();
        $transactionPin = $this->cleanString($stringArray[1]);
        $validatePin    = $this->validatePin($user, $transactionPin);
        if ($userPOS != null) {
            if ($validatePin != false) {return "*POS Balance* - {$userPOS->balance}\n";} else {
                return $this->getInvalidPinMessage();

            }

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
            $message .= "*Type Transaction Pin. (changepin, newpin, otpcode).*\n\n*Follow the example below to change your Pin*\n\n *Example* - changepin, 1234, 5678 \n";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function changePin(Request $request, $stringArray)
    {
        $newPin  = $this->cleanString($stringArray[1]);
        $otp     = $this->cleanString($stringArray[2]);
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {
            $checkOTP = $this->validateOTP($user, $otp);
            if ($checkOTP != false) {
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
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {
            $otp = $this->otpCode($user);
            $message .= $otp;
            $message .= "*Type Transaction Pin. (changepassword, newpassword, otpcode, transactionpin).*\n\n*Follow the example below to change yor password*\n\n *Example* - changepassword, 1234, 5678, 1234\n";
            return $message;
        } else {
            return "User does not exist";
        }

    }

    public function changePassword(Request $request, $stringArray)
    {
        $newPassword    = $this->cleanString($stringArray[1]);
        $otp            = $this->cleanString($stringArray[2]);
        $transactionPin = $this->cleanString($stringArray[3]);
        $user           = $this->getUser($request);
        $message        = "";
        if ($user != null) {
            $checkOTP    = $this->validateOTP($user, $otp);
            $validatePin = $this->validatePin($user, $transactionPin);
            if (($checkOTP != false) && ($validatePin != false)) {
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
        $message = "";
        $message .= "*Choose Type of Transfer*.\n\n*Follow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n";
        return $message;
    }

    public function getTransferAccountMessage($request, $stringArray)
    {
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {

            $message .= "*Transfer to Bank Account (transfer, amount)*.\n\nFollow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n ";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function getTransferWalletMessage($request, $stringArray)
    {
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {

            $message .= "*Choose Type of Transfer*.\n\nFollow the example below to pick the options*\n\n *Example* - transfer, A \n\n*A* - Transfer to Bank\n*B* - Transfer to Wallet\n ";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function getTransferPinMessage($request, $stringArray)
    {
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {

            $message .= "*Type Your Transaction Pin*\n\n *Example (Transfer)* - transferservice, 1234 \n\n*Example (Check Account Balance)* - balance, 1234 \n\n*Example (Check POS Balance)* - posbalance, 1234 \n\n*Example (Buy Data)* - buydata, 1234 \n\n*Example (Pay Bill)* - paybill, 1234 \n\n ";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function initializeTransactionMessage()
    {
        $message = "";
        $message .= "*Initialise Transfer Trasaction*.\n\n*Follow the example below to initialize transfer*\n\n *Example* - transfer, amount, description, destination bank name, destination account number \n\n transfer, 5000, send to ola, zenith bank, 2001362794 \n";
        return $message;

    }

    public function transferTransactionMessage()
    {
        $message = "";
        $message .= "\n\n*Transfer Trasaction*.\n\n*Follow the example below to confirm transfer*\n\n *Example* - confirm-transfer, transaction_pin\n\nconfirm-transfer, 1234";
        return $message;

    }

    public function initializeTransaction($request, $stringArray)
    {
        $amount                          = $this->cleanString($stringArray[1]);
        $description                     = $this->cleanString($stringArray[2]);
        $destination_bank_name           = $this->cleanAccountNameString($stringArray[3]);
        $destination_bank_account_number = $this->cleanString($stringArray[4]);

        $message               = "";
        $transactionController = new TransactionService();
        $user                  = $this->getUser($request);
        $bank_code             = $this->getBankCode($destination_bank_name);

        $request->mergeIfMissing([
            'final_amount'     => $amount,
            'transaction_type' => 4,
            'description'      => $description,
        ]);

        $initiateTransaction  = $this->initiateTransfer($request, $user);
        $destionation_account = $this->verifyBankAccount($bank_code, $destination_bank_account_number);

        if (! $destionation_account) {
            $message .= "Issue with Account Details";
        } else {

            \logInfo(json_encode($initiateTransaction), "Twilio Respond Whatsapp Message");

            $initiateTransactionMessage              = $initiateTransaction['message'];
            $initiateTransactionCharge               = $initiateTransaction['data']['charge'];
            $initiateTransactionTransactionReference = $initiateTransaction['data']['transaction_reference'];
            $initiateTransactionTransactionTotal     = $initiateTransaction['data']['total'];
            $bankCode                                = $bank_code;
            $bankName                                = $destionation_account['data']['bank_name'];
            $receipientName                          = $destionation_account['data']['first_name'] . $destionation_account['data']['last_name'];

            Cache::put("whatsappTransfer:{$user->id}", [
                'description'           => $description,
                'amount'                => $initiateTransactionTransactionTotal,
                'transaction_reference' => $initiateTransactionTransactionReference,
                'receipientName'        => $receipientName,
                'destination_account'   => $destination_bank_account_number,
                'bank_code'             => $bankCode,
                'bank'                  => $bankName,

            ], 600);

            $message .= "*Amount* - $initiateTransactionTransactionTotal\n*Charge* - $initiateTransactionCharge\n*Transaction Reference* - $initiateTransactionTransactionReference\n*Receipient Name* - $destination_bank_account_number\n*Destination Account* - $destination_bank_account_number\n*Bank Code* - $bankCode\n*Bank Code* - $bankCode\n*Bank Name* - $bankName\n";
            $message .= $this->transferTransactionMessage();
        }
        return $message;

    }

    public function otpCode($user)
    {
        $otpCode                   = generateOtp();
        $user->otp                 = $otpCode;
        $date                      = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');
        $user->otp_expiration_date = $date;
        $user->save();
        return "*otpcode* - {$otpCode}\n\n";
    }

    public function validateOTP($user, $otp)
    {
        if ($user->otp == $otp) {
            return true;
        } else {
            return false;
        }
    }

    public function validatePin($user, $pin)
    {
        \logInfo(json_encode($user->pin), "Twilio user pin");
        \logInfo(json_encode($pin), "reqest user pin");
        if ($user->pin == $pin) {
            return true;
        } else {
            return false;
        }
    }

    public function getOtp($request)
    {
        $user    = $this->getUser($request);
        $message = "";
        if ($user != null) {
            $otp = $this->otpCode($user);
            $message .= $otp;
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function getInvalidPinMessage()
    {
        return "*PIN is invalid*";
    }

    public function getInvalidOTPMessage()
    {
        return "*OTP is invalid*";
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

    public function cleanAccountNameString($input)
    {
        return ltrim($input);
    }

    public function getBankCode($input)
    {
        $transactionController = new TransactionService();
        return $transactionController->getBankCode($input);
    }

    public function initiateTransfer(Request $request, $user)
    {
        $transactionController = new TransactionService();
        return $transactionController->initiateTransfer($request, $user);
    }

    public function verifyBankAccount($bank_code, $destination_bank_account_number)
    {
        $transactionController = new TransactionService();
        return $transactionController->verifyBankAccount($bank_code, $destination_bank_account_number);
    }

}
