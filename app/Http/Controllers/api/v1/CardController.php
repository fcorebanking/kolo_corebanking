<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\TransactionController;
use App\Models\company_settings;
use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;
use App\Models\transactions;
use App\Models\UserCard;
use App\Models\user_wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{

    private $transactionObj;

    private $flutterwave;

    public function __construct()
    {
        $this->transactionObj = new TransactionController();
        $this->flutterwave    = new FlutterwaveController();
    }

    public function createUserCard(Request $request)
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $transactionId = \generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "card_category" => 'required',
            "amount"        => 'required|numeric|gt:0',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $user = auth()->user();

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $cardCurrency = $companySettings->preferred_virtual_card_currency == "1" ? "NGN" : "USD";

        $checkCard                = UserCard::where('user_id', $user->id)->first();
        $customer_sudo_account_id = $checkCard->card_reference;

        if ($checkCard) {
            return \response()->json(['status' => false, 'message' => "Virtual Card already exists"], 400);
        }

        $cardFundingAmount      = $cardCurrency == "USD" ? 2 : 100;
        $cardFundingDebitAmount = $cardFundingAmount;

        $initialCreationFee = getWbaTransferCharges($companySettings->preferred_virtual_card_currency == "1" ? 30 : 29, 1000);
        $creationFee        = $initialCreationFee;

        if ($cardCurrency == "USD") {
            $flutterwaveController = new FlutterwaveController();

            $exhangeRate = $flutterwaveController->getExchangeRater($creationFee, auth()->user()->company->currency->code, $cardCurrency);

            if (! $exhangeRate['status']) {
                return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
            }

            $rate                   = $exhangeRate["data"]["rate"];
            $creationFee            = $rate * $initialCreationFee;
            $cardFundingDebitAmount = $rate * $cardFundingAmount;
        }

        $debitCustomer = debitCustomerAndCompanyWithCharge($user->accounts->first()->id, $user->company->id, $creationFee, 0, 0);

        if (! $debitCustomer['status']) {
            return \response()->json($debitCustomer, 400);
        }

        $description     = $request->description == "" ? "Funding Virtual Card" : $request->description;
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $creationFee,
            "balance_after"          => $debitCustomer['balance_after'],
            "account_id"             => $debitCustomer['account_id'],
            "description"            => "$cardCurrency Virtual Card Creation Fee and funding of $cardCurrency $cardFundingAmount",
            "transaction_reference"  => $transactionId,
            "company_id"             => $user->company_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $creationFee,
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 23,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $safehavenController = new SafehavenController();

        /*    $createVirtualCard = $safehavenController->createVirtualCard(
            $request->cardCompanyName,
            $cardFundingAmount,
            $cardCurrency,
            $user->first_name,
            $user->last_name,
            "1994/04/04",
            $user->email,
            $user->phone
        );*/

        $createVirtualCard = $safehavenController->createVirtualCard($request, $user, $customer_sudo_account_id);

        if (! $createVirtualCard['statusCode' == 400]) {
            return \response()->json($createVirtualCard['message'], 400);
        }

        \updateTransactionAsSuccessfulOnly($transactionId);

        $userCard = UserCard::create([
            "user_id"          => $user->id,
            "card_name"        => $createVirtualCard['data']["name_on_card"],
            "card_type_id"     => $createVirtualCard['data']["card_type"],
            "card_category_id" => $request->card_category,
            "card_number"      => $createVirtualCard['data']["card_pan"],
            "masked_pan"       => $createVirtualCard['data']["masked_pan"],
            "card_reference"   => $createVirtualCard['data']["id"],
            "currency_id"      => $companySettings->preferred_virtual_card_currency,
            "exp_date"         => $createVirtualCard['data']["expiration"],
            "cvv"              => $createVirtualCard['data']["cvv"],
            "created_by"       => $user->id,
        ]);

        $msg = "You have successfully created a Virtual Card, \nCard No: " . $createVirtualCard['data']["masked_pan"] . "\nExp Date: " . $createVirtualCard['data']["expiration"] . "\nCVV: " . $createVirtualCard['data']["cvv"];

        \sendSMS($user->id, $msg, true, true, "Virtual Card Creation");

        return \response()->json(['status' => true, 'message' => "Customer Card Created successfully", 'data' => ['card' => $userCard, 'balance' => $debitCustomer['charge_balance_after']]]);
    }

    public function debitCustomer($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            $user = auth()->user();

            if ($userWallet->balance >= $amount) {

                $userWallet->balance -= $amount;
                $userWallet->updated_at      = Carbon::now();
                $user->balance_fails         = 0;
                $user->transaction_pin_fails = 0;
                $user->save();
                $response = ["status" => true, 'message' => "Customer Debited", 'balance' => $userWallet->balance];
            } else {
                if ($user->balance_fails < 4) {
                    $user->balance_fails += 1;
                    $user->save();
                    $response = ["status" => false, 'message' => "Insufficient Funds"];
                } else {
                    // auth()->user()->status_id = 5;
                    $this->restrictUserAccount(2, "Multiple Insuficient balance trials");

                    $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function creditCustomer($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            $userWallet->balance += $amount;
            $userWallet->updated_at = Carbon::now();
            $userWallet->save();

            $response = ["status" => true, 'message' => "Customer Debited", 'balance' => $userWallet->balance];
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function checkCustomerBalance($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                auth()->user()->balance_fails         = 0;
                auth()->user()->transaction_pin_fails = 0;
                auth()->user()->save();
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->accounts->first()->id, 'balance' => $userWallet->balance];
            } else {
                if (auth()->user()->balance_fails < 4) {
                    auth()->user()->balance_fails += 1;
                    auth()->user()->save();
                    $response = ["status" => false, 'message' => "Insufficient Funds"];
                } else {
                    // auth()->user()->status_id = 5;
                    $this->restrictUserAccount(2, "Multiple Insuficient balance trials");

                    $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function checkCardExistsAndConfirmCardOwnership($cardReference)
    {

        $response = [];

        $virtualCard = UserCard::where('card_reference', $cardReference)->first();

        if ($virtualCard) {

            if ($virtualCard->user_id != auth()->user()->id) {
                $response = ['status' => false, 'message' => "This card does not belong to you. Please try again with your virtual card"];
            } else {
                $response = ['status' => true, 'message' => "Card is valid", 'data' => ['currency_code' => $virtualCard->currency->code]];
            }
        } else {
            return $response = ['status' => false, 'message' => "Invalid Virtual Card Reference"];
        }

        return $response;
    }

    public function fundVirtualCard(Request $request)
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $transactionId = \generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "amount"         => 'required|gt:0',
            "card_reference" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        \logInfo($validateCard, "validate card");
        if (! $validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $user = auth()->user();

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $cardCurrency = $validateCard['data']['currency_code'];

        $wbaFundingFee          = getWbaTransferCharges($cardCurrency == "NGN" ? 32 : 31, 1000);
        $totalFundingFee        = $wbaFundingFee + $companySettings->additional_virtual_card_funding_fee;
        $cardFundingDebitAmount = $request->amount;

        if ($cardCurrency == "USD") {
            $flutterwaveController = new FlutterwaveController();

            $exhangeRate = $flutterwaveController->getExchangeRater($totalFundingFee, auth()->user()->company->currency->code, $cardCurrency);

            if (! $exhangeRate['status']) {
                return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
            }

            $rate                   = $exhangeRate["data"]["rate"];
            $totalFundingFee        = $rate * $totalFundingFee;
            $wbaFundingFee          = $rate * $wbaFundingFee;
            $cardFundingDebitAmount = $rate * $request->amount;
        }

        $customerBalance = debitCustomerAndCompanyWithCharge($user->accounts->first()->id, $user->company->id, $cardFundingDebitAmount, $totalFundingFee, $wbaFundingFee);

        \logInfo($customerBalance, "Customer Balance AFter Debit VCard");
        if (! $customerBalance['status']) {
            return \response()->json($customerBalance, 400);
        }

        $description = "";

        $creditDebitType = 2;

        $message         = "Virtual card funded successfully";
        $description     = $request->description == "" ? "Funding Virtual Card with $cardCurrency$request->amount " : $request->description;
        $transactionType = 9;
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transactionType, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $customerBalance['balance_before'],
            "amount"                 => $cardFundingDebitAmount,
            "balance_after"          => $customerBalance["balance_after"],
            "account_id"             => $customerBalance['account_id'],
            "description"            => $description,
            "transaction_reference"  => $transactionId,
            "company_id"             => $user->company_id,
            "company_balance_before" => $customerBalance["company_balance_before"],
            "company_amount"         => $customerBalance["company_amount"],
            "company_balance_after"  => $customerBalance["company_balance_after"],
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => $transactionType,
            "credit_debit_type_id"   => $creditDebitType,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $chargeTransactionId = generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(100, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        $transaction = transactions::create([
            "balance_before"         => $customerBalance['charge_balance_before'],
            "amount"                 => $totalFundingFee,
            "balance_after"          => $customerBalance['charge_balance_after'],
            "company_balance_before" => $customerBalance['company_charge_balance_before'],
            "company_amount"         => $wbaFundingFee,
            "company_balance_after"  => $customerBalance['company_charge_balance_after'],
            "description"            => "Charge for Funding Virtual Card $cardCurrency$totalFundingFee",
            "transaction_reference"  => $chargeTransactionId,
            "transaction_remarks_id" => 2,
            "account_id"             => $customerBalance['account_id'],
            "company_id"             => $user->company->id,
            "transaction_type_id"    => 100,
            "credit_debit_type_id"   => $creditDebitType,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $bankAccount = $this->flutterwave->fundVirtualCard($request->card_reference, $request->amount, $validateCard['data']['currency_code']);

        if ($bankAccount["status"]) {

            $availableCardBalance = $this->flutterwave->getVirtualCardBalance($request->card_reference);

            \updateTransactionAsSuccessfulOnly($transactionId);

            return \response()->json(['status' => true, 'message' => $message, 'data' => ["cardBalance" => $availableCardBalance['data']['balance'], 'walletBalance' => $customerBalance['balance_after']]]);
        } else {
            return \response()->json(['status' => false, 'message' => "Failed to process withdrawal. Please try again", 'data' => $bankAccount]);
        }
    }

    public function blockUnblockCard(Request $request)
    {

        $transactionId = \generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
            "status"         => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $message = $request->status == "block" ? "Card Blocked successfully" : "Card Unblocked Successfully";

        $endpoint = "virtual-cards/$request->card_reference/status/$request->status";

        $bankAccount = \flutterWavePutRequest($endpoint);

        logInfo($bankAccount);

        if ($bankAccount["status"] == 'success') {

            return \response()->json(['status' => true, 'message' => $message]);
        } else {
            return \response()->json(['status' => false, 'message' => $bankAccount["message"]], 400);
        }
    }

    public function getBalance(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (! $validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $flutterwaveController = new FlutterwaveController();
        $availableCardBalance  = $flutterwaveController->getVirtualCardBalance($request->card_reference);

        logInfo($availableCardBalance);

        if ($availableCardBalance['status']) {
            return response()->json($availableCardBalance, 200);
        } else {
            return response()->json($availableCardBalance, 400);
        }
    }

    public function withdrawVirtualCard(Request $request)
    {
        return response()->json(["status" => false, 'message' => "This service is unavailable and will be restored soon"]);

        $transactionId = \generateTransactionReference();

        logInfo($request->all());

        $validation = Validator::make($request->all(), [
            "amount"         => 'required|numeric|gt:0',
            "card_reference" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (! $validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $body = ["amount" => $request->amount];

        $description = "";

        $creditDebitType = 1;

        $transactionType = 0;

        $message         = "Withdrawal Successful";
        $transactionType = 10;
        $description     = $request->description == "" ? "Withdrawal from Virtual Card" : $request->description;

        $userWallet      = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transactionType, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $userWallet->balance,
            "amount"                 => $request->amount,
            "account_id"             => auth()->user()->accounts->first()->id,
            "description"            => $description,
            "transaction_reference"  => $transactionId,
            "company_id"             => \auth()->user()->company_id,
            "company_balance_before" => \auth()->user()->company->wallet->balance,
            "company_amount"         => $request->amount,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => $transactionType,
            "credit_debit_type_id"   => $creditDebitType,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $withdrawal = $this->flutterwave->withdrawVirtualCard($request->card_reference, $request->amount);

        logInfo($withdrawal);

        if ($withdrawal["status"]) {

            $availableCardBalance = $this->flutterwave->getVirtualCardBalance($request->card_reference);

            //Update Transaction
            $creditCustomer = creditCustomerAndCompany(auth()->user()->accounts->first()->id, auth()->user()->company_id, $request->amount, $request->amount);

            \updateCustomerAndCompanyTransactionAsSuccessful($transactionId, $creditCustomer['balance_after'], $creditCustomer['company_balance_after']);

            return \response()->json([
                'status'  => true,
                'message' => $message,
                'data'    => [
                    "cardBalance"   => $availableCardBalance,
                    'walletBalance' => $creditCustomer['balance_after'],
                ],
            ]);
        } else {
            return \response()->json(['status' => false, 'message' => "Failed to process withdrawal. Please try again"]);
        }
    }

    public function getVirtualCard()
    {
        $virtualCard = UserCard::where('user_id', auth()->user()->id)->get();

        if ($virtualCard) {
            return response()->json(['status' => true, 'message' => 'Cards fetched successfully', 'data' => $virtualCard]);
        }
    }

    public function getCardDetails(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (! $validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $virtualCard = UserCard::where(['user_id' => auth()->user()->id, 'card_reference' => $request->card_reference])->first();

        if ($virtualCard) {
            return response()->json(['status' => true, 'message' => 'Card fetched successfully', 'data' => $virtualCard]);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid Card reference'], 400);
        }
    }

    public function restrictUserAccount($restrictionId, $remark)
    {
        if (count(auth()->user()->customerPermissions) > 0) {
            foreach (auth()->user()->customerPermissions as $customerPermission) {
                if ($customerPermission->id == $restrictionId) {
                    return;
                } else {
                    // auth()->user()->customerPermissions()->attach($restrictionId);

                    MainCustomerRestriction::create([
                        'user_id'                => auth()->user()->id,
                        'company_id'             => \auth()->user()->company_id,
                        'customer_permission_id' => $restrictionId,
                        "remark"                 => $remark,
                        "created_by"             => auth()->user()->id,
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id"                 => auth()->user()->id,
                        "customer_permission_id"  => $restrictionId,
                        "remark"                  => $remark,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 1,

                    ]);
                }
            }
        }
    }

    public function transactionHistory(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "card_reference" => 'required|string',
            "from"           => 'required|date',
            "to"             => 'required|date',
            "size"           => 'required|numeric|gt:0',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (! $validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $pageLimit = isset($request->limit) ? $request->limit : 10;

        $endpoint = "virtual-cards/$request->card_reference/transactions?from=$request->from&to=$request->to&index=1&size=$request->size";

        $flutterwaveController = new FlutterwaveController();

        $getTransactions = $flutterwaveController->getCardTransactionHistory($request->card_reference, $request->from, $request->to, $request->size);

        if ($getTransactions['status']) {

            return response()->json($getTransactions);
        } else {
            return response()->json($getTransactions, 400);
        }
    }
}
