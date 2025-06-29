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
            \logInfo(json_encode($e->getMessage()), "Twilio Response Error Message");

        }

    }

    public function getUpdates(Request $request)
    {

        $message = $this->checkMessage($request);
        \logInfo(json_encode($request->From), "Twilio Response From");

        $phone = $request->From;
        $type  = "response";

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

            $message .= "*Type Your Transaction Pin*\n\n*Example (Check Account Balance)* - balance, 1234 \n\n*Example (Check POS Balance)* - posbalance, 1234 \n";
            return $message;
        } else {
            return "User does not exist";
        }
    }

    public function initializeTransactionBankMessage()
    {
        $message = "";
        $message .= "*Initialise Transfer Trasaction*.\n\n*Follow the example below to initialize transfer*\n\n *Example* - transfer, amount, description, destination bank name, destination account number \n\n transfer, 5000, send to ola, zenith bank, 2001362794 \n";
        return $message;

    }

    public function initializeTransactionWalletMessage()
    {
        $message = "";
        $message .= "*Initialise Transfer Trasaction*.\n\n*Follow the example below to initialize transfer*\n\n *Example* - transfer, amount, description, destination bank name, destination account number \n\n transfer, 5000, send to ola, zenith bank, 2001362794 \n";
        return $message;

    }

    public function transferTransactionMessage()
    {
        $message = "";
        $message .= "\n\n*Follow the example below to confirm transfer shown above*\n\n *Example* - confirm-transfer, transaction_pin\n\nconfirm-transfer, 1234";
        return $message;

    }

    public function getBuyDataMessage($request, $stringArray)
    {
        $message = "";
        $message .= "*Choose Network Provider*.\n\n*Follow the example below to pick the options*\n\n *Example* - data, A \n\n*A* - MTN\n*B* - Airtel\n*C* - 9Mobile\n*D* - Glo\n";
        return $message;
    }

    public function getListDataMessage($request, $stringArray, $plans, $networkProvider)
    {
        $user    = $this->getUser($request);
        $message = "";
        $message .= "*Choose Data Plan*.\n\n";
        if ($networkProvider == "glo-data" || $networkProvider == "airtel-data") {
            for ($x = 0; $x < count($plans['data']); $x += 2) {
                $message .= "*{$x}* - {$plans['data'][$x]['name']}\n";
            }
        } else {
            for ($x = 0; $x < count($plans['data']); $x++) {
                $message .= "*{$x}* - {$plans['data'][$x]['name']}\n";
            }
        }

        Cache::put("whatsappData:{$user->id}", [
            'network_provider' => $networkProvider,
            'plan'             => "",
            'amount'           => 0,
            'phone_number'     => 0,

        ], 600);

        $data = Cache::get("whatsappData:{$user->id}");
        \logInfo(json_encode($data), "Twilio Cache Data Plan Whatsapp Message");

        Cache::put("whatsappDataBundle:{$user->id}", $plans, 600);
        $dataBundle = Cache::get("whatsappDataBundle:{$user->id}");
        \logInfo(json_encode($dataBundle), "Twilio Cache Data Plan Bundle Whatsapp Message");

        $message .= "\n\n*Follow the example below to purchase data*\n\n *Example* - buy-data, option, phone nuumber, amount \n\n buy-data, 5, 08076890890, 100 \n";
        return $message;
    }

    public function initiateBuyDataMessage($request, $stringArray)
    {
        $user        = $this->getUser($request);
        $option      = $this->cleanString($stringArray[1]);
        $phoneNumber = $this->cleanString($stringArray[2]);
        $amount      = $this->cleanString($stringArray[3]);
        $message     = "";

        $dataBundle = Cache::get("whatsappDataBundle:{$user->id}");
        $data       = Cache::get("whatsappData:{$user->id}");
        \logInfo(json_encode($data), "Twilio Cache Data Initiate Buy Whatsapp Message");
        \logInfo(json_encode($dataBundle), "Twilio Cache Data Plan Bundle Initiate Buy Whatsapp Message");

        if ($dataBundle != null && $data != null) {
            Cache::put("whatsappData:{$user->id}", [
                'network_provider' => $data['network_provider'],
                'plan'             => $dataBundle['data'][$option],
                'amount'           => $amount,
                'phone_number'     => $phoneNumber,

            ], 600);

            $dataUpdate = Cache::get("whatsappData:{$user->id}");
            \logInfo(json_encode($dataUpdate), "Twilio Cache Data Update Plan Whatsapp Message");

            $networkProvider = $dataUpdate['network_provider'];
            $plan            = $dataUpdate['plan']['name'];

            $message .= "*Network Provider* -  $networkProvider\n*Plan* - $plan\n*Amount* - $amount\n*Phone Number* - $phoneNumber\n\n*Follow the example below to confirm data purchase as shown above*\n\n *Example* - confirm-data, transaction_pin\n\nconfirm-data, 1234";
        } else {
            $message .= "There is a problem with Data Plan Transaction";
        }

        return $message;
    }

    public function getPayBillMessage($request, $stringArray)
    {
        $message = ""; //
        $message .= "*Choose Bill Payment*.\n\n*Follow the example below to pick the options*\n\n *Example* - bill, A \n\n*A* - Electricity Bill\n*B* - Cable TV\n";
        return $message;
    }

    public function getElectricityMessage($request)
    {
        $user    = $this->getUser($request);
        $message = "";
        $message .= "*Electricity Bill Payment*.\n\n";
        $message .= "*Electric Disco*.\n\nabuja-electric(Prepaid)\nibadan-electric(Prepaid)\neko-electric(Prepaid)\nikeja-electric(Prepaid)\nportharcourt-electric(Prepaid)\njos-electric(Prepaid)\nkano-electric(Prepaid)\nenugu-electric(Prepaid)\n\n";
        $message .= "*Follow the example below to purchase bill payment*\n\n *Example* - buy-electricity, meter number, electric-disco, phone number, amount \n\n buy-electricity, 54567676, ikeja-electric, 08076890890, 750 \n";

        return $message;
    }

    public function initializeBankTransaction($request, $stringArray)
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

        if (! isset($destionation_account['data'])) {
            $message .= "Issue with Account Details";
        } else {

            \logInfo(json_encode($initiateTransaction), "Twilio Respond Whatsapp Message");

            $initiateTransactionMessage              = $initiateTransaction['message'];
            $initiateTransactionCharge               = $initiateTransaction['data']['charge'];
            $initiateTransactionTransactionReference = $initiateTransaction['data']['transaction_reference'];
            $initiateTransactionTransactionTotal     = $initiateTransaction['data']['total'];
            $bankCode                                = $bank_code;
            $bankName                                = $destionation_account['data']['bank_name'];
            $receipientName                          = $destionation_account['data']['first_name'] . " " . $destionation_account['data']['last_name'];

            Cache::put("whatsappTransfer:{$user->id}", [
                'description'           => $description,
                'amount'                => $initiateTransactionTransactionTotal,
                'transaction_reference' => $initiateTransactionTransactionReference,
                'receipientName'        => $receipientName,
                'destination_account'   => $destination_bank_account_number,
                'bank_code'             => $bankCode,
                'bank'                  => $bankName,

            ], 600);

            $data = Cache::get("whatsappTransfer:{$user->id}");
            \logInfo(json_encode($data), "Twilio Cache Data Whatsapp Message");

            $message .= "*Amount* - $initiateTransactionTransactionTotal\n*Charge* - $initiateTransactionCharge\n*Transaction Reference* - $initiateTransactionTransactionReference\n*Receipient Name* - $receipientName\n*Destination Account* - $destination_bank_account_number\n*Bank Code* - $bankCode\n*Bank Name* - $bankName\n";
            $message .= $this->transferTransactionMessage();
        }
        return $message;

    }

    public function initializeWalletTransaction($request, $stringArray)
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

        if (! isset($destionation_account['data'])) {
            $message .= "Issue with Account Details";
        } else {

            \logInfo(json_encode($initiateTransaction), "Twilio Respond Whatsapp Message");

            $initiateTransactionMessage              = $initiateTransaction['message'];
            $initiateTransactionCharge               = $initiateTransaction['data']['charge'];
            $initiateTransactionTransactionReference = $initiateTransaction['data']['transaction_reference'];
            $initiateTransactionTransactionTotal     = $initiateTransaction['data']['total'];
            $bankCode                                = $bank_code;
            $bankName                                = $destionation_account['data']['bank_name'];
            $receipientName                          = $destionation_account['data']['first_name'] . " " . $destionation_account['data']['last_name'];

            Cache::put("whatsappTransfer:{$user->id}", [
                'description'           => $description,
                'amount'                => $initiateTransactionTransactionTotal,
                'transaction_reference' => $initiateTransactionTransactionReference,
                'receipientName'        => $receipientName,
                'destination_account'   => $destination_bank_account_number,
                'bank_code'             => $bankCode,
                'bank'                  => $bankName,

            ], 600);

            $data = Cache::get("whatsappTransfer:{$user->id}");
            \logInfo(json_encode($data), "Twilio Cache Data Whatsapp Message");

            $message .= "*Amount* - $initiateTransactionTransactionTotal\n*Charge* - $initiateTransactionCharge\n*Transaction Reference* - $initiateTransactionTransactionReference\n*Receipient Name* - $receipientName\n*Destination Account* - $destination_bank_account_number\n*Bank Code* - $bankCode\n*Bank Name* - $bankName\n";
            $message .= $this->transferTransactionMessage();
        }
        return $message;

    }

    public function initializeElectricityMessage($request, $stringArray)
    {

        $amount         = $this->cleanString($stringArray[4]);
        $meter_number   = $this->cleanString($stringArray[1]);
        $electric_disco = $this->cleanString($stringArray[2]);
        $phone_number   = $this->cleanAccountNameString($stringArray[3]);
        $type           = "prepaid";
        $message        = "";

        $user                 = $this->getUser($request);
        $meterCustomerDetails = $this->verifyMeterNumber($meter_number, $electric_disco, $type);

        \logInfo(json_encode($meterCustomerDetails), "Twilio Respond Whatsapp Meter Customer Details Message");

        if ($meterCustomerDetails) {
            $meter_name             = $meterCustomerDetails['data']['meter_name'];
            $meter_address          = $meterCustomerDetails['data']['address'];
            $meter_confirmed_number = $meterCustomerDetails['data']['address'];
            $meter_type             = $meterCustomerDetails['data']['meter_type'];

            Cache::put("whatsappElectricity:{$user->id}", [
                'meter_number'      => $meter_confirmed_number,
                'electricity_disco' => $electric_disco,
                'type'              => $meter_type,
                'amount'            => $amount,
                'phone_number'      => $phone_number,

            ], 600);

            $data = Cache::get("whatsappElectricity:{$user->id}");
            \logInfo(json_encode($data), "Twilio Cache Electricity Whatsapp Message");

            $message .= "*Amount* - $amount\n*Meter Name* - $meter_name\n*Meter Address* - $meter_address\n*Meter Nmber* - $meter_confirmed_number\n*Meter Type* - $destination_bank_account_number\n*Bank Code* - $bankCode\n*Bank Name* - $bankName\n";
            $message .= "\n\n*Follow the example below to purchase electricity bill*\n\n *Example* - confirm-electricity, transacion_pin \n\nconfirm-electricity, 1234 \n";
        } else {
            $message .= "Issue with Meter Details";
        }
    }

    public function bankTransferTransaction($request, $stringArray)
    {
        $transactionPin = $this->cleanString($stringArray[1]);
        $user           = $this->getUser($request);
        $validatePin    = $this->validatePin($user, $transactionPin);
        $message        = "";
        \logInfo(json_encode($validatePin), "Twilio validate pin for transfer transaction ");
        if ($validatePin != false) {

            $data = Cache::get("whatsappTransfer:{$user->id}");

            if ($data != null) {

                \logInfo(json_encode($data), "Twilio Cache Data Whatsapp Message for Transfer");
                $description           = $data['description'];
                $amount                = $data['amount'];
                $transaction_reference = $data['transaction_reference'];
                $destination_account   = $data['destination_account'];
                $bank_code             = $data['bank_code'];
                $transaction_pin       = $transactionPin;
                $receipient_name       = $data['receipientName'];

                $transactionController = new TransactionService();
                $transferStatus        = $transactionController->transferToBankAccount($description, $amount, $transaction_reference, $destination_account, $bank_code, $transaction_pin, $user, $receipient_name);

                if ($transferStatus) {
                    $message .= "*Transaction was successul*\n";
                }
            } else {
                $message .= "*There was an issue with this transaction*\n";
            }

        } else {
            $message .= $this->getInvalidPinMessage();

        }
        return $message;

    }

    public function buyData($request, $stringArray)
    {
        $transactionPin = $this->cleanString($stringArray[1]);
        $user           = $this->getUser($request);
        $validatePin    = $this->validatePin($user, $transactionPin);
        $message        = "";
        \logInfo(json_encode($validatePin), "Twilio validate pin for buy data transaction ");
        if ($validatePin != false) {

            $data = Cache::get("whatsappData:{$user->id}");

            if ($data != null) {

                \logInfo(json_encode($data), "Twilio Cache Data Whatsapp Message for Transfer");

                $network_provider = $data['network_provider'];
                $amount           = $data['amount'];
                $data_plan        = $data;
                $transaction_pin  = $transactionPin;
                $phone_number     = $data['phone_number'];

                $transactionController = new TransactionService();
                $buyDataStatus         = $transactionController->buyDataBundle($transaction_pin, $amount, $user, $network_provider, $data_plan, $phone_number);

                if ($buyDataStatus) {
                    $message .= "*Transaction was successul*\n";
                }
            } else {
                $message .= "*There was an issue with this transaction*\n";
            }

        } else {
            $message .= $this->getInvalidPinMessage();

        }
        return $message;
    }

    public function buyElectricity($request, $stringArray)
    {
        $transactionPin = $this->cleanString($stringArray[1]);
        $user           = $this->getUser($request);
        $validatePin    = $this->validatePin($user, $transactionPin);
        $message        = "";
        \logInfo(json_encode($validatePin), "Twilio validate pin for buy data transaction ");
        if ($validatePin != false) {

            $data = Cache::get("whatsappElectricity:{$user->id}");

            if ($data != null) {

                \logInfo(json_encode($data), "Twilio Cache Electricity Whatsapp Message ");

                $meter_number      = $data['meter_number'];
                $amount            = $data['amount'];
                $electricity_disco = $data['electricity_disco'];
                $transaction_pin   = $transactionPin;
                $meter_type        = $data['type'];
                $phone_number      = $data['phone_number'];

                $transactionController = new TransactionService();
                $buyDataStatus         = $transactionController->buyElectricity($user, $amount, $transaction_pin, $electricity_disco, $meter_type, $meter_number, $phone_number);

                if ($buyDataStatus) {
                    $message .= "*Transaction was successul*\n";
                }
            } else {
                $message .= "*There was an issue with this transaction*\n";
            }

        } else {
            $message .= $this->getInvalidPinMessage();

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

    public function verifyMeterNumber($meter_number, $electricity_disco, $type)
    {
        $transactionController = new TransactionService();
        return $transactionController->verifyMeterNumber($meter_number, $electricity_disco, $type);
    }

    public function checkMessage($request)
    {
        $message = "*Quick Options*\n*1* - Open Account\n*2* - Transfer\n*3* - Check Account Balance\n*4* - POS Balance\n*5* - Change Pin\n*6* - Change Pasword\n*7* - Buy Data\n*8* - Pay Bills\n*9* - Get OTP\n";

        \logInfo(json_encode($request->Body), "Twilio Response Body Message");

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
            $message = $this->getBuyDataMessage($request, $stringArray);
        }

        if ($string == "8") {
            $message = $this->getPayBillMessage($request, $stringArray);
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

        if (isset($stringArray[1]) && ($stringArray[0] == "balance" || $stringArray[0] == strtolower("balance"))) {

            $message = $this->getAccountBalance($request, $stringArray);
        }
        if (isset($stringArray[1]) && ($stringArray[0] == "posbalance" || $stringArray[0] == strtolower("posbalance"))) {

            $message = $this->getPOSBalance($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "transfer" || $stringArray[0] == strtolower("transfer"))) {
            $transferOption = $this->cleanString($stringArray[1]);

            if ($transferOption == "A" || $transferOption == strtolower("A")) {
                $message = $this->initializeTransactionBankMessage();
            }

            if ($transferOption == "B" || $transferOption == strtolower("B")) {
                $message = $this->initializeTransactionWalletMessage();
            }

            if (is_numeric($transferOption) || (int) $transferOption == $transferOption) {
                $message = $this->initializeBankTransaction($request, $stringArray);
            }

        }

        if (isset($stringArray[1]) && ($stringArray[0] == "confirm-transfer" || $stringArray[0] == strtolower("comfirm-transfer"))) {
            $message = $this->bankTransferTransaction($request, $stringArray);

        }

        if (isset($stringArray[1]) && ($stringArray[0] == "data" || $stringArray[0] == strtolower("data"))) {
            $providerString = $this->cleanString($stringArray[1]);
            $provider       = strtolower($providerString);

            switch ($provider) {
                case 'a':
                    $networkProvider = "mtn-data";
                    break;
                case 'b':
                    $networkProvider = "airtel-data";
                    break;
                case 'c':
                    $networkProvider = "9mobile-data";
                    break;
                default:
                    $networkProvider = "glo-data";
            }
            $transactionController = new TransactionService();
            $plans                 = $transactionController->getDataBundlesList($networkProvider);
            \logInfo(json_encode($plans), "Twilio get Data plans for $networkProvider Whatsapp Message");

            if ($plans) {
                $message = $this->getListDataMessage($request, $stringArray, $plans, $networkProvider);
            } else {
                $message = "There was a problem with this request";
            }

        }

        if (isset($stringArray[1]) && ($stringArray[0] == "buy-data" || $stringArray[0] == strtolower("buy-data"))) {
            $message = $this->initiateBuyDataMessage($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "confirm-data" || $stringArray[0] == strtolower("confirm-data"))) {
            $message = $this->buyData($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "bill" || $stringArray[0] == strtolower("bill"))) {
            $billOption = $this->cleanString($stringArray[1]);

            if ($billOption == "A" || $billOption == strtolower("A")) {
                $message = $this->getElectricityMessage($request);
                //
            }

            if ($billOption == "B" || $billOption == strtolower("B")) {
                $message = $this->getCableMessage();
                //  $message = $this->initializeCableMessage();
            }

        }

        if (isset($stringArray[1]) && ($stringArray[0] == "buy-electricity" || $stringArray[0] == strtolower("buy-electricity"))) {
            $message = $this->initializeElectricityMessage($request, $stringArray);
        }

        if (isset($stringArray[1]) && ($stringArray[0] == "confirm-electricity" || $stringArray[0] == strtolower("confirm-electricity"))) {
            $message = $this->buyData($request, $stringArray);
        }

        \logInfo($message, "Twilio Respond Whatsapp Message");

        return $message;
    }

}
