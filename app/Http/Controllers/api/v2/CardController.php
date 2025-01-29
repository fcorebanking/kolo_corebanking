<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Models\currencies;
use App\Models\transactions;
use App\Models\user_wallet;
use App\Models\UserCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerRestrictionLog;
use App\Models\MainCustomerRestriction;

class CardController extends Controller
{

    private $transactionObj;

    private $flutterwave;

    function __construct()
    {
        $this->transactionObj = new TransactionController();
        $this->flutterwave = new FlutterwaveController();
    }

    public function createUserCard(Request $request)
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        logInfo($request->all());;

        $transactionId = \generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "card_category" => 'required',
            "card_currency" => 'required',
            "amount" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $checkCard = UserCard::where('user_id', \auth()->user()->id)->first();

        if ($checkCard) {
            return \response()->json(['status' => false, 'message' => "Virtual Card already exists"], 400);
        }

        $debitCustomer = debitCustomerAndCompanyWithCharge(auth()->user()->accounts->first()->id, auth()->user()->company->id, 1000, $request->amount, $request->amount);

        if (!$debitCustomer['status']) {
            return \response()->json($debitCustomer, 400);
        }

        $description = $request->description == "" ? "Funding Virtual Card" : $request->description;

        $transaction = transactions::create([
            "balance_before" => $debitCustomer['balance_before'],
            "amount" => 1000,
            "balance_after" => $debitCustomer['balance_after'],
            "account_id" => $debitCustomer['account_id'],
            "description" => "Virtual Card Processing Fee",
            "transaction_reference" => $transactionId,
            "company_id" => \auth()->user()->company_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount" => 1000,
            "company_balance_after" => $debitCustomer["company_balance_after"],
            "transaction_remarks_id" => 1,
            "transaction_type_id" => 23,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id
        ]);

        $chargeTransactionId = generateTransactionReference();

        $chargeTransaction = transactions::create([
            "balance_before" => $debitCustomer['charge_balance_before'],
            "amount" => $request->amount,
            "balance_after" => $debitCustomer['charge_balance_after'],
            "description" => "Funding of Virtual Card",
            "transaction_reference" => $chargeTransactionId,
            "transaction_remarks_id" => 1,
            "company_id" => \auth()->user()->company_id,
            "company_charge_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount" => $request->amount,
            "company_charge_balance_after" => $debitCustomer["company_charge_balance_after"],
            "parent_transaction_id" => $transaction->id,
            "transaction_type_id" => 9,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id
        ]);

        $currency = currencies::find($request->card_currency);

        \logInfo($currency, "currency object");
        \logInfo($currency->code, "currency code");

        $createVirtualCard = $this->flutterwave->createVirtualCard(\auth()->user()->first_name . " " . \auth()->user()->last_name, $request->amount, $currency->code);

        \logInfo($createVirtualCard, "Create Virtual Card Log");

        if (!$createVirtualCard['status']) {
            return \response()->json($createVirtualCard, 400);
        }

        \updateTransactionAsSuccessfulOnly($transactionId);
        \updateTransactionAsSuccessfulOnly($chargeTransactionId);

        $userCard = UserCard::create([
            "user_id" => auth()->user()->id,
            "card_name" => $createVirtualCard['data']["name_on_card"],
            "card_type_id" => $createVirtualCard['data']["card_type"],
            "card_category_id" => $request->card_category,
            "card_number" => $createVirtualCard['data']["card_pan"],
            "masked_pan" => $createVirtualCard['data']["masked_pan"],
            "card_reference" => $createVirtualCard['data']["id"],
            "currency_id" => $request->card_currency,
            "exp_date" => $createVirtualCard['data']["expiration"],
            "cvv" => $createVirtualCard['data']["cvv"],
            "created_by" => auth()->user()->id
        ]);

        $msg = "You have successfully created a Virtual Card, \nCard No: " . $createVirtualCard['data']["masked_pan"] . "\nExp Date: " . $createVirtualCard['data']["expiration"] . "\nCVV: " . $createVirtualCard['data']["cvv"];

        \sendSMS(auth()->user()->id, $msg, true, true, "Virtual Card Creation");

        return \response()->json(['status' => true, 'message' => "Customer Card Created successfully", 'data' => ['card' => $userCard, 'balance' => $debitCustomer['charge_balance_after']]]);
    }


    public function debitCustomer($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                $userWallet->balance -= $amount;
                $userWallet->updated_at = Carbon::now();
                auth()->user()->balance_fails = 0;
                auth()->user()->transaction_pin_fails = 0;
                auth()->user()->save();
                $response = ["status" => true, 'message' => "Customer Debited", 'balance' => $userWallet->balance];
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

                auth()->user()->balance_fails = 0;
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

        logInfo($request->all());;

        $transactionId = \generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "amount" => 'required|gt:0',
            "card_reference" => 'required'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        \logInfo($validateCard, "validate card");
        if (!$validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $customerBalance = debitCustomerAndCompanyWithCharge(auth()->user()->accounts->first()->id, auth()->user()->company->id, $request->amount, 0, 0);

        \logInfo($customerBalance);
        if (!$customerBalance['status']) {
            return \response()->json($customerBalance, 400);
        }

        $description = "";

        $creditDebitType = 2;

        $message = "Virtual card funded successfully";
        $description = $request->description == "" ? "Funding Virtual Card" : $request->description;
        $transactionType = 9;

        $transaction = transactions::create([
            "balance_before" => $customerBalance['balance_before'],
            "amount" => $request->amount,
            "balance_after" => $customerBalance["balance_after"],
            "account_id" => $customerBalance['account_id'],
            "description" => $description,
            "transaction_reference" => $transactionId,
            "company_id" => \auth()->user()->company_id,
            "company_balance_before" => $customerBalance["company_balance_before"],
            "company_amount" => $customerBalance["company_amount"],
            "company_balance_after" => $customerBalance["company_balance_after"],
            "transaction_remarks_id" => 1,
            "transaction_type_id" => $transactionType,
            "credit_debit_type_id" => $creditDebitType,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id
        ]);

        $bankAccount = $this->flutterwave->fundVirtualCard($request->card_reference, $request->amount, $validateCard['data']['currency_code']);

        if ($bankAccount["status"]) {

            $availableCardBalance = $this->flutterwave->getVirtualCardBalance($request->card_reference);

            \updateTransactionAsSuccessfulOnly($transactionId);

            return \response()->json(['status' => 1, 'message' => $message, 'data' => ["cardBalance" => $availableCardBalance, 'walletBalance' => $customerBalance['balance_after']]]);
        } else {
            return \response()->json(['status' => 3, 'message' => "Failed to process withdrawal. Please try again", 'data' => $bankAccount]);
        }
    }

    public function blockUnblockCard(Request $request)
    {

        logInfo($request->all());;

        $transactionId = \generateTransactionReference();

        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
            "status" => 'required'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
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

        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (!$validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $flutterwaveController = new FlutterwaveController();
        $availableCardBalance = $flutterwaveController->getVirtualCardBalance($request->card_reference);

        logInfo($availableCardBalance);

        if ($availableCardBalance['status']) {
            return response()->json($availableCardBalance, 200);
        } else {
            return response()->json($availableCardBalance, 400);
        }
    }

    public function withdrawVirtualCard(Request $request)
    {

        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $transactionId = \generateTransactionReference();

        logInfo($request->all());;

        $validation = Validator::make($request->all(), [
            "amount" => 'required|numeric|gt:0',
            "card_reference" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (!$validateCard['status']) {
            return response()->json($validateCard, 400);
        }

        $body = ["amount" => $request->amount];

        $description = "";

        $creditDebitType = 1;

        $transactionType = 0;

        $message = "Withdrawal Successful";
        $transactionType = 10;
        $description = $request->description == "" ? "Withdrawal from Virtual Card" : $request->description;

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

        $transaction = transactions::create([
            "balance_before" => $userWallet->balance,
            "amount" => $request->amount,
            "account_id" => auth()->user()->accounts->first()->id,
            "description" => $description,
            "transaction_reference" => $transactionId,
            "company_id" => \auth()->user()->company_id,
            "company_balance_before" => \auth()->user()->company->wallet->balance,
            "company_amount" => $request->amount,
            "transaction_remarks_id" => 1,
            "transaction_type_id" => $transactionType,
            "credit_debit_type_id" => $creditDebitType,
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id
        ]);

        $withdrawal = $this->flutterwave->withdrawVirtualCard($request->card_reference, $request->amount);

        logInfo($withdrawal);

        if ($withdrawal["status"]) {

            $availableCardBalance = $this->flutterwave->getVirtualCardBalance($request->card_reference);

            //Update Transaction 
            $creditCustomer = creditCustomerAndCompany(auth()->user()->accounts->first()->id, auth()->user()->company_id, $request->amount, $request->amount);

            \updateCustomerAndCompanyTransactionAsSuccessful($transactionId, $creditCustomer['balance_after'], $creditCustomer['company_balance_after']);

            return \response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    "cardBalance" => $availableCardBalance,
                    'walletBalance' => $creditCustomer['balance_after']
                ]
            ]);
        } else {
            return \response()->json(['status' => false, 'message' => "Failed to process withdrawal. Please try again"]);
        }
    }

    public function getVirtualCard()
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $virtualCard = UserCard::where('user_id', auth()->user()->id)->get();

        if ($virtualCard) {
            return response()->json(['status' => true, 'message' => 'Cards fetched successfully', 'data' => $virtualCard]);
        }
    }

    public function getCardDetails(Request $request)
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (!$validateCard['status']) {
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
                        'user_id' => auth()->user()->id,
                        'company_id' => \auth()->user()->company_id,
                        'customer_permission_id' => $restrictionId,
                        "remark" => $remark,
                        "created_by" => auth()->user()->id
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id" => auth()->user()->id,
                        "customer_permission_id" => $restrictionId,
                        "remark" => $remark,
                        "restricted_by_system" => 1,
                        "restriction_log_type_id" => 1

                    ]);
                }
            }
        }
    }


    public function transactionHistory(Request $request)
    {
        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $validation = Validator::make($request->all(), [
            "card_reference" => 'required|string',
            "from" => 'required|date',
            "to" => 'required|date',
            "size" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => false, "message" => $validation->errors()->all()[0]);
            return response()->json($ra, 406);
        }

        $validateCard = $this->checkCardExistsAndConfirmCardOwnership($request->card_reference);

        if (!$validateCard['status']) {
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
