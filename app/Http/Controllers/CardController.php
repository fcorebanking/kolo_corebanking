<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Models\company_wallet;
use App\Models\CompanyCard;
use App\Models\currencies;
use Illuminate\Http\Request;
use App\Models\frequency;
use App\Models\transactions;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    private $flutterwave;
    private $flutterwaveSecretKey;
    private $baseUrl;

    function __construct()
    {
        $this->middleware("custom_permission:card-management||root");
        if (env('APP_MODE') == 'test') {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_TEST');
        } else {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_LIVE');
        }

        $this->baseUrl = url('/');

        $this->flutterwave = new FlutterwaveController();
    }

    public function companyCardManagement($company_name)
    {
        if (!cando('root'))  return abort(403);

        $data = [
            'category_name' => 'card_management',
            'page_name' => 'card_management',
            'page_menu' => 'card_management_menu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'status' => frequency::all(),

        ];

        return view('company.card_management.company_card_managment')->with($data);
    }

    public function companyCardManagementPage(Request $request)
    {
        if (!cando('card-management|root'))  return abort(403);

        $virtualCard = CompanyCard::where(['company_id' => \auth()->user()->company->id, 'card_category_id' => 1])->first();
        $physicalCard = CompanyCard::where(['company_id' => \auth()->user()->company->id, 'card_category_id' => 2])->first();

        $cardDetails = [];
        $physicalCardDetails = [];

        if ($virtualCard) {

            $availableCardBalanceResult = $this->flutterwave->getVirtualCardBalance($virtualCard->card_reference);

            $availableCardBalance = "Not Available";

            if ($availableCardBalanceResult['status'])
                $availableCardBalance = $availableCardBalanceResult['data']['balance'];

            \logInfo($availableCardBalance, "my available card balance");

            $companyVirtualCard = $virtualCard->card_number;
            $cardDetails = $virtualCard->card_type_id == 1 || $virtualCard->card_type_id == 3 ? [
                'name' => $virtualCard->card_name,
                'type' => $virtualCard->card_type_id,
                'cvv' => $virtualCard->cvv,
                'cardCurrency' => $virtualCard->currency->symbol,
                'cardReference' => $virtualCard->card_reference,
                'availableCardBalance' => $availableCardBalance,
                'exp' => $virtualCard->exp_date,
                'firstFour' => substr($companyVirtualCard, 0, 4),
                'secondFour' => substr($companyVirtualCard, 4, 4),
                'thirdFour' => substr($companyVirtualCard, 8, 4),
                'lastFour' => substr($companyVirtualCard, 12, 4),
            ] : [
                'name' => $virtualCard->card_name,
                'type' => $virtualCard->card_type_id,
                'cvv' => $virtualCard->cvv,
                'cardCurrency' => $virtualCard->currency->symbol,
                'cardReference' => $virtualCard->card_reference,
                'exp' => $virtualCard->exp_date,
                'availableCardBalance' => $availableCardBalance,
                'firstFour' => \substr($virtualCard->card_number, 0, 4),
                'secondFour' => \substr($virtualCard->card_number, 4, 8),
                'thirdFour' => \substr($virtualCard->card_number, 8, 12),
                'fourthThree' => \substr($virtualCard->card_number, 12, 15),
                'lastFour' => \substr($virtualCard->card_number, 15, 16),
            ];
        }

        if ($physicalCard) {
            $companyPhysicalCard = $physicalCard->card_number;

            $physicalCardDetails = $physicalCard->card_type_id == 1 ? [
                'name' => $physicalCard->card_name,
                'type' => $physicalCard->card_type_id,
                'cvv' => $physicalCard->cvv,
                'cardCurrency' => $physicalCard->currency->symbol,
                'cardReference' => $physicalCard->card_reference,
                'exp' => $physicalCard->exp_date,
                'firstFour' => substr($companyPhysicalCard, 0, 4),
                'secondFour' => substr($companyPhysicalCard, 4, 4),
                'thirdFour' => substr($companyPhysicalCard, 8, 4),
                'lastFour' => substr($companyPhysicalCard, 12, 4),
            ] : [
                'name' => $physicalCard->card_name,
                'type' => $physicalCard->card_type_id,
                'cvv' => $physicalCard->cvv,
                'cardCurrency' => $physicalCard->currency->code,
                'cardReference' => $physicalCard->card_reference,
                'exp' => $physicalCard->exp_date,
                'firstFour' => \substr($physicalCard->card_number, 0, 4),
                'secondFour' => \substr($physicalCard->card_number, 4, 8),
                'thirdFour' => \substr($physicalCard->card_number, 8, 12),
                'fourthThree' => \substr($physicalCard->card_number, 12, 15),
                'lastFour' => \substr($physicalCard->card_number, 15, 16),
            ];
        }

        $data = [
            'category_name' => 'card_management_page',
            'page_name' => 'html5',
            'page_menu' => 'card_management_page',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'card_details' => $cardDetails,
            'physical_card_details' => $physicalCardDetails
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.card_management.company_card_management_page')->with($data);
    }

    public function createCompanyCard(Request $request)
    {

        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        if (!cando('root')) return response()->json(['status' => 0, 'message' => "Access Denied! (Required Root permisssion)"]);

        $transactionId = generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "cardCompanyName" => 'required',
            "companyCardCategory" => 'required',
            "cardCurrency" => 'required',
            "amount" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => 0, "message" => "All fields are required");
            return response()->json($ra, 406);
        }

        $user = auth()->user();

        $fundingAmount = 0;

        $currency = currencies::find($request->cardCurrency);

        $companyCard = CompanyCard::where('company_id', $user->company_id)->first();

        if ($companyCard) {
            \logInfo("Company Virtual card already exists", "Create virtual card response");
            return response()->json(['status' => 0, 'message' => "Company Virtual card already exists"]);
        }

        if ($request->cardCurrency == 1) {
            $debitCompany = \debitCompanyWithCharge($user->company->id, 1000, $request->amount);
            $fundingAmount = $request->amount;
            $cardProcessingFee = 1000;
        } else {

            if ($request->amount < 5) {
                return response()->json(['status' => 0, 'message' => "Minimum Fund amount is $5"]);
            }

            $exchangeAmount = $this->flutterwave->getExchangeRater(6, $user->company->currency->code, $currency->code);

            if (!$exchangeAmount['status']) {
                $exchangeAmount['status'] = 0;
                return response()->json($exchangeAmount);
            }

            \logInfo($exchangeAmount);

            $fundingAmount = $exchangeAmount['data']['rate'] * $request->amount;
            $cardProcessingFee = $exchangeAmount['data']['final_amount'];

            $debitCompany = \debitCompanyWithCharge($user->company->id, $cardProcessingFee, $fundingAmount);
        }

        if (!$debitCompany['status']) {
            $debitCompany['status'] = 0;
            return response()->json(['status' => 0, "message" => "Insufficient wallet balance"]);
        }
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $user->company_id);
        $ledger_cr =null;
        $ledger_dr =null;
        if($ledger_Settings){
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before" => $debitCompany['balance_before'],
            "amount" => $cardProcessingFee,
            "balance_after" => $debitCompany['balance_after'],
            "description" => "Virtual Card creation",
            "transaction_reference" => $transactionId,
            "transaction_remarks_id" => 1,
            "company_id" => $user->company_id,
            "transaction_type_id" => 23,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => $user->id,
            "ledger_cr"=>$ledger_cr,
            "ledger_dr"=>$ledger_dr,
        ]);

        $chargeTransactionId = generateTransactionReference();

        $chargeTransaction = transactions::create([
            "balance_before" => $debitCompany['charge_balance_before'],
            "amount" => $fundingAmount,
            "balance_after" => $debitCompany['charge_balance_after'],
            "description" => "Funding of Virtual Card",
            "transaction_reference" => $chargeTransactionId,
            "transaction_remarks_id" => 1,
            "parent_transaction_id" => $transaction->id,
            "transaction_type_id" => 23,
            "company_id" => $user->company_id,
            "credit_debit_type_id" => 2,
            "created_date"  => date('Y-m-d'),
            "created_by" => $user->id,
            "ledger_cr"=>$ledger_cr,
            "ledger_dr"=>$ledger_dr,
        ]);

        $createVirtualCard = $this->flutterwave->createVirtualCard(
            $request->cardCompanyName,
            $request->amount,
            $currency->code,
            $user->first_name,
            $user->last_name,
            "1994/04/04",
            $user->email,
            $user->phone
        );

        \logInfo($createVirtualCard);

        if (!$createVirtualCard['status']) {
            $createVirtualCard['status'] = 0;

            \creditCompanyWithCharge($user->company->id, 1000, $request->amount);
            return \response()->json($createVirtualCard);
        }

        transactions::where('transaction_reference', $transactionId)->orWhere('transaction_reference', $chargeTransactionId)->update(['transaction_remarks_id' => 2]);

        CompanyCard::create([
            "company_id" => $user->company->id,
            "card_name" => $createVirtualCard['data']["name_on_card"],
            "card_type_id" => $createVirtualCard['data']['card_type'],
            "card_category_id" => $request->companyCardCategory,
            "card_number" => $createVirtualCard['data']["card_pan"],
            "masked_pan" => $createVirtualCard['data']["masked_pan"],
            "card_reference" => $createVirtualCard['data']["id"],
            "currency_id" => $request->cardCurrency,
            "exp_date" => $createVirtualCard['data']["expiration"],
            "cvv" => $createVirtualCard['data']["cvv"],
            "created_by" => $user->id
        ]);

        return \response()->json(['status' => 1, 'message' => "Company Card Created successfully"]);
    }

    public function createCompanyPhysicalCard(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "cardCompanyName" => 'required',
            "companyCardCategory" => 'required',
            "cardCurrency" => 'required',
            "amount" => 'required|numeric|gt:0'
        ]);

        if ($validation->fails()) {
            $ra = array("status" => 0, "message" => "All fields are required");
            return response()->json($ra, 406);
        }

        $companyWallet = company_wallet::where('company_id', auth()->user()->company->id)->first();

        if ($request->amount > $companyWallet->balance) {
            return response()->json(['status' => 0, "message" => "Insufficient wallet balance"]);
        }

        $companyWallet->balance -= $request->amount;
        $companyWallet->save();

        $currency = currencies::find($request->cardCurrency);

        $body = [
            "currency" => $currency->code,
            "amount" => $request->amount,
            "billing_name" => $request->cardCompanyName,
            "billing_address" => "333 Freemont Road",
            "billing_city" => "San Francisco",
            "billing_state" => "CA",
            "billing_postal_code" => "984105",
            "billing_country" => "US",
            "debit_currency" => $currency->code,
            "callback_url" => url("/api/v1/transactions/flutterwave-webhook")
        ];
        $endpoint = "virtual-cards";

        $bankAccount = \flutterWavePostRequest($endpoint, $body);

        if ($bankAccount["status"] == 'success') {

            $cardType = $bankAccount['data']['card_type'];

            $cardTypeId = 1;

            if ($cardType == 'visa') {
                $cardTypeId = 1;
            } else if ($cardType == 'verve') {
                $cardTypeId = 2;
            } else if ($cardType == 'mastercard') {
                $cardTypeId = 3;
            }

            CompanyCard::create([
                "company_id" => auth()->user()->company->id,
                "card_name" => $bankAccount['data']["name_on_card"],
                "card_type_id" => $cardTypeId,
                "card_category_id" => $request->companyCardCategory,
                "card_number" => $bankAccount['data']["card_pan"],
                "masked_pan" => $bankAccount['data']["masked_pan"],
                "card_reference" => $bankAccount['data']["id"],
                "currency_id" => $request->cardCurrency,
                "exp_date" => $bankAccount['data']["expiration"],
                "cvv" => $bankAccount['data']["cvv"],
                "created_by" => auth()->user()->id
            ]);

            return \response()->json(['status' => 1, 'message' => "Company Card Created successfully"]);
        } else {
            return \response()->json(['status' => 3, 'message' => "Failed to create company card", 'data' => $bankAccount]);
        }
    }


    public function fundWithdrawVirtualCard(Request $request)
    {

        return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $transactionId = \generateTransactionReference();

        \logInfo($request->all(), "Fund or Withdrawal Request log");


        $validation = Validator::make($request->all(), [
            "amount" => 'required|numeric|gt:0',
            "cardReference" => 'required',
            "fundWithdrawType" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = array("status" => 0, "message" => $validation->errors()->all()[0]);
            return response()->json($ra);
        }
        if ($request->fundWithdrawType == 2)
            return \response()->json(['status' => false, 'message' => 'This service is currently unavailable and will be restored soon']);

        $virtualCard = CompanyCard::where('card_reference', $request->cardReference)->first();

        if ($virtualCard) {

            if ($request->fundWithdrawType == 2) {
                if ($request->amount > auth()->user()->company->wallet->balance) {
                    return \response()->json(['status' => 2, 'message' => "Insufficient Balance"]);
                }
            }

            $description = "";

            $creditDebitType = $request->fundWithdrawType == 1 ? 2 : 1;

            $transactionType = 0;

            $balanceAfter = 0;

            $flutterwaveController = new FlutterwaveController();

            $exhangeRate = $flutterwaveController->getExchangeRater($request->amount, \auth()->user()->company->currency->code, $virtualCard->currency->code);

            \logInfo($exhangeRate, "Exhange Rate log");

            if ($exhangeRate['data']['charge_by'] == "percentage") {
                $charges = $exhangeRate['data']['fee'] + 0.01;
                $charges *= $request->amount;
            } else {
                $charges = $exhangeRate['data']['fee']  + ($exhangeRate['data']['fee'] / 2);
            }

            $debitAmount = $exhangeRate['data']['amount'];

            if ($request->fundWithdrawType == 1) {

                $message = "Company virtual card funded successfully";
                $description = $request->description == "" ? "Funding Virtual Card" : $request->description;
                $transactionType = 9;

                $description .= ":: Virtual Card Funding of $" . $request->amount;

                $chargeAmount = $exhangeRate['data']['rate'] * 1.5;

                // $validateCustomerPin = validatePinCheckCustomerBalance($request->transaction_pin, $chargeAmount + $debitAmount);

                // if (!$validateCustomerPin['status']) {
                //     $validateCustomerPin['status'] = 0;
                //     return response()->json($validateCustomerPin);
                // }

                $debitCompany = \debitCompanyWithCharge(auth()->user()->company_id, $debitAmount, $chargeAmount);

                if (!$debitCompany['status']) {
                    $debitCompany['status'] = 0;
                    return \response()->json($debitCompany);
                }

                $balanceAfter = $debitCompany['balance_after'];
            } else {
                $message = "Withdrawal Successful";
                $transactionType = 10;
                $description = $request->description == "" ? "Withdrawal from Virtual Card" : $request->description;
            }

            $companyWallet = company_wallet::where('company_id', auth()->user()->company->id)->first();

            $transaction = transactions::create([
                "balance_before" => 0,
                "amount" => 0,
                "balance_after" => 0,
                "company_balance_before" => $debitCompany['balance_before'],
                "company_amount" => $debitAmount,
                "company_balance_after" => $request->fundWithdrawType == 1 ? $debitCompany['balance_after'] : null,
                "description" => $description,
                "transaction_reference" => $transactionId,
                "transaction_remarks_id" => 1,
                "company_id" => auth()->user()->company->id,
                "transaction_type_id" => $transactionType,
                "credit_debit_type_id" => $creditDebitType,
                "created_date"  => date('Y-m-d'),
                "created_by" => auth()->user()->id
            ]);

            if ($request->fundWithdrawType == 1) {

                $chargeTransactionId = generate_txnID();

                $transaction = transactions::create([
                    "balance_before" => 0,
                    "amount" => 0,
                    "balance_after" => 0,
                    "company_balance_before" => $debitCompany['charge_balance_before'],
                    "company_amount" => $chargeAmount,
                    "company_balance_after" => $debitCompany['charge_balance_after'],
                    "description" => "Charge for Funding Virtual Card of $1.5",
                    "transaction_reference" => $chargeTransactionId,
                    "transaction_remarks_id" => 2,
                    "company_id" => auth()->user()->company->id,
                    "transaction_type_id" => 100,
                    "credit_debit_type_id" => $creditDebitType,
                    "created_date"  => date('Y-m-d'),
                    "created_by" => auth()->user()->id
                ]);
            }

            $bankAccount = $request->fundWithdrawType == 1 ? $this->flutterwave->fundVirtualCard($request->cardReference, $request->amount, $virtualCard->currency->code) : $this->flutterwave->withdrawVirtualCard($request->cardReference, $request->amount);

            if ($bankAccount["status"]) {

                $availableCardBalance = $this->flutterwave->getVirtualCardBalance($virtualCard->card_reference);

                $transaction = transactions::where('transaction_reference', $transactionId)->first();

                if ($request->fundWithdrawType != 1) {
                    $creditCompany = \creditCompanyWithCharge(\auth()->user()->company_id, $debitAmount, 0);

                    if (!$creditCompany['status']) {
                        $creditCompany['status'] = 0;

                        return \response()->json($creditCompany);
                    }
                    $balanceAfter = $creditCompany['balance_after'];
                }

                updateTransactionAsSuccessful($transactionId, $balanceAfter, true);

                return \response()->json(['status' => 1, 'message' => $message, 'data' => ["cardBalance" => $availableCardBalance['data']['balance'], 'walletBalance' => $balanceAfter]]);
            } else {
                return \response()->json(['status' => 3, 'message' => "Failed to process withdrawal. Please try again", 'data' => $bankAccount]);
            }
        } else {
            return \response()->json(['status' => 0, 'message' => "Invalid Virtual Card Id"]);
        }
    }
}
