<?php
namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\AssetMatrixController;
use App\Http\Controllers\ThirdParty\CyberpayControllelr;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\MonnifyController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Models\AgentTerminal;
use App\Models\ApiKey;
use App\Models\banks;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\Mandate;
use App\Models\MandateLog;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\WbaCompanyCharge;
use App\Models\wb_account_details;
use App\Models\WebhookLog;
use App\Models\WovenWebhook;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{

    private $flutterwaveSecretKey;
    private $baseUrl;

    public function __construct()
    {

        $this->baseUrl = url('/');
    }

    public function verifyBankAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bank_code"      => 'required',
            "account_number" => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Bank code and account number is required"];
            return response()->json($ra, 406);
        }

        $wovenController = new WovenController();

        $bankAccount = $wovenController->verifyBankAccountNumberGuzzle($request->bank_code, $request->account_number);

        if ($bankAccount['status'] == 'success') {
            $accountName = $bankAccount["data"]["account_name"];

            $myAccountName = [];

            $myAccountName = explode(" ", $accountName);

            $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];
            $lastName  = count($myAccountName) > 2 ? (strlen($myAccountName[2]) < 3 ? $myAccountName[1] : $myAccountName[2]) : $myAccountName[1];

            $bankName = $bankAccount["data"]["bank_name"];

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'bank_name'  => $bankName,

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify bank account",
                ],
                400
            );
        }
    }

    public function getCompanyBalance(Request $request)
    {

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $companyWallet = company_wallet::where('company_id', $checkApiKey->company_id)->first();

        if ($companyWallet) {
            return response()->json(['status' => true, 'message' => 'Company Balance fetched successfully', 'data' => ['balance' => $companyWallet->balance]], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to get Company balance'], 400);
        }
    }

    public function companyBankTransfer(Request $request)
    {
        $description = $request->description;

        $validation = Validator::make($request->all(), [
            "amount"              => "required|gt:0",
            "destination_account" => "required",
            "bank_code"           => "required",
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $company = Company::where('id', $checkApiKey->company_id)->first();

        if ($company->currency->id == 1) {
            $chargeAmount = getWbaTransferCharges(1, $request->amount);
        } else {
            $chargeAmount = 0;
        }

        $transactionId = generateTransactionReference();

        $customerBalance = debitCompanyWithCharge($company->id, $request->amount, $chargeAmount);

        $adminUser = User::where('company_id', $company->id)->first();

        $transaction = transactions::create([
            "balance_before"         => 0,
            "amount"                 => 0,
            "balance_after"          => 0,
            "description"            => $description,
            "branch_id"              => $adminUser->branch_id,
            "company_id"             => $adminUser->company_id,
            "company_balance_before" => $customerBalance['balance_before'],
            "company_balance_after"  => $customerBalance['balance_after'],
            "company_amount"         => $request->amount,
            "company_id"             => $company->id,
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 8,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $adminUser->id,
        ]);

        $transactionRef2 = generateTransactionReference();

        $newdescription = $request->description == "" ? "From " . $company->company_name : $request->description;

        if ($chargeAmount > 0) {
            //Create a successful transaction for the Recipient
            $transaction = transactions::create([
                "balance_before"         => $customerBalance['charge_balance_before'],
                "amount"                 => $chargeAmount,
                "balance_after"          => $customerBalance['charge_balance_after'],
                "description"            => "Charge for NGN Bank Transfer - " . $newdescription,
                "transaction_reference"  => $transactionRef2,
                "branch_id"              => $adminUser->branch_id,
                "company_id"             => $adminUser->company_id,
                "transaction_remarks_id" => 2,
                "parent_transacton_id"   => $transaction,
                "transaction_type_id"    => 27,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
            ]);
        }

        // $wovenController = new WovenController();
        // $bankTransfer = $wovenController->transferToBank($request->destinationAccount, $firstName, $request->bankCode, $newdescription, $request->amount, $request->transactionId);

        $myDescription = "Transfer from " . $company->company_name;

        $myDescription .= $request->description == "" ? "" : $request->description;

        $kudaController = new KudaController();

        $bankAccount = $kudaController->verifyBankAccount($request->bank_code, $request->destination_account);

        if (! $bankAccount['status']) {
            $bankAccount['message'] = "Failed to Complete Bank transfer. If you have been debited kindly contact support for reversal";

            return response()->json($bankAccount);
        }

        $bankTransfer = $kudaController->bankTransfer($transactionId, $request->destination_account, $request->bank_code, $bankAccount['data']['session_id'], $request->amount, $bankAccount['data']['account_name'], $myDescription);

        // $monnifyController = new MonnifyController();
        // $monnifyLogin = $monnifyController->login();

        // if(!$monnifyLogin['status']){
        //     return response()->json(['status' => 0, 'message' => 'Failed to Complete bank transfer'], 400);
        // }

        // $bankTransfer = $monnifyController->bankTransfer($request->amount, $request->transactionId, $newdescription, $request->bankCode, $request->destinationAccount);

        // $bankTransfer = ["status" => true, 'message' => "Bank Transfer Successful"];

        if (! $bankTransfer['status']) {
            $bankTransfer['status'] = 0;
            return response()->json($bankTransfer, 200);
        }

        $wallet = company_wallet::where('company_id', $company->id)->first();

        $transaction                         = transactions::where('transaction_reference', $transactionId)->first();
        $transaction->transaction_remarks_id = 2;
        $transaction->save();

        return response()->json(
            [
                "status"  => 1,
                'message' => "Wallet to Bank Transfer Successful",
                'data'    => [
                    'balance_before'        => $customerBalance['balance_before'],
                    'balance'               => $wallet->balance,
                    'balance_after'         => $customerBalance['charge_balance_after'],
                    'transaction_reference' => $transactionId,
                ],
            ]
        );
    }

    public function verifyBankAccountFullname(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bank_code"      => 'required',
            "account_number" => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Bank code and account number is required"];
            return response()->json($ra, 406);
        }

        $assetMatrixController = new AssetMatrixController();

        $bankAccount = $assetMatrixController->verifyBankAccount($request->bank_code, $request->account_number);

        if ($bankAccount['status']) {
            $accountName = $bankAccount["data"]["account_name"];

            $bank = banks::where('short_code', $request->bank_code)->first();

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'account_name' => $accountName,
                        'bank_name'    => $bank->bank_name,

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify bank account",
                ],
                400
            );
        }
    }

    public function validateBetAccount(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "bet_company"    => 'required',
            "bet_account_id" => 'required',
        ]);

        // return response()->json(
        //     [
        //         'status' => true,
        //         'message' => "This service is not available at the moment",
        //     ]
        // );

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $url = "https://partner.com/bettings/validate_bet_account.php?bet_company=$request->bet_company&bet_account=$request->bet_account_id&is_demo=$isDemo&api_exchange_key=$apiExchangeKey";

        $betAccount = postRequest([], [], $url);

        if ($betAccount['success']) {

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'customer_name' => $betAccount['data']['firstName'] . " " . $betAccount['data']['lastName'],
                        'bet_company'   => $betAccount['data']['provider'],

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify bet account",
                    'data'    => $betAccount,
                ],
                400
            );
        }
    }

    public function topupBetting(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "bet_company"    => 'required',
            "bet_account_id" => 'required',
            "amount"         => 'required|numeric|gt:0|min:100',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        return response()->json(['status' => false, 'message' => 'This service is not available at the moment'], 400);

        $reference = generateAccountReference();

        $transactionPinValidation = validatePinCheckCustomerBalance(auth()->user()->pin, $request->amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(24, ($request->amount / 100));

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->amount, $companySettings->bills_payment_charge, $wbaCharge);

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $request->amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Bet Account Topup worth $request->amount",
            "transaction_reference"  => $reference,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 52,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $transactionChargeId = \generate_txnID();

        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Bet Account Topup",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $reference,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $isDemo         = env("APP_MODE");
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $body = [
            "amount"           => $request->amount,
            "bet_account_id"   => $request->bet_account_id,
            "bet_company"      => $request->bet_company,
            "reference"        => $reference,
            "is_demo"          => $isDemo,
            "api_exchange_key" => $apiExchangeKey,
        ];

        $url = "https://partner.com/bettings/topup_bet_account.php";

        $betAccount = postRequest([], $body, $url);

        if ($betAccount['success']) {

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Successfully Topup Bet Account",
                    'data'    => [
                        'status'        => $betAccount['success'],
                        'order_no'      => $betAccount['data']['orderNo'],
                        'reference'     => $betAccount['data']['reference'],
                        'error_message' => $betAccount['data']['status'],
                        'balance'       => $debitCustomer["charge_balance_after"],

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to Topup bet account",
                    'data'    => $betAccount,
                ],
                400
            );
        }
    }

    public function verifyWalletAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "account_number" => 'required',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $wbAccount = wb_account_details::where('virtual_accnum', $request->account_number)->first();

        if ($wbAccount) {

            if ($wbAccount->user_id == \auth()->user()->id) {

                return response()->json(
                    [
                        'status'  => false,
                        'message' => "Cannot Transfer to Self",
                    ],
                    409
                );
            }

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name' => $wbAccount->user->first_name,
                        'last_name'  => $wbAccount->user->last_name,
                        'bank_name'  => $wbAccount->bank->bank_name,

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify Wallet account",
                ],
                400
            );
        }
    }

    public function transferToBankAccount(Request $request)
    {
        $description = $request->description;

        $validation = Validator::make($request->all(), [
            "final_amount"          => "required|numeric|gt:0",
            "transaction_reference" => "required",
            "destination_account"   => "required",
            "receipient_name"       => "required|string",
            "bank_code"             => "required",
            "transaction_pin"       => "required|numeric|gt:0",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $charge = 0;

        if (\auth()->user()->company->setting->withdraw_charge_state == 1) {
            $charge = $request->final_amount * (\auth()->user()->company->setting->min_withdraw_charge / 100);

            $charge = $charge > \auth()->user()->company->setting->max_withdraw_charge ? \auth()->user()->company->setting->max_withdraw_charge : $charge;
        } else {
            $charge = \auth()->user()->company->setting->max_withdraw_charge;
        }

        $totalAmount = $request->final_amount + $charge;

        if ($request->final_amount > 1000000) {
            return response()->json(['status' => 0, 'message' => 'Maximum transfer amount is 1,000,000']);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $totalAmount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();

        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else if ($transaction->transaction_remarks_id == 3) {
                return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
            } else {

                $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

                if ($userWallet->balance >= $totalAmount) {

                    if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                        return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                    }

                    $validateTransferAmount = $this->validateTransfer($request->final_amount);

                    if (! $validateTransferAmount['status']) {
                        return \response()->json($validateTransferAmount, 400);
                    }

                    $wbaCharge = WbaCharge::find(1);

                    $charge = $charge < $wbaCharge->bank_transfer_charge ? ($wbaCharge->bank_transfer_charge + ($wbaCharge->bank_transfer_charge * 0.01)) : $charge;

                    $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $charge, $wbaCharge->charge);

                    if (! $debitCustomer["status"]) {
                        return \response()->json($debitCustomer);
                    }

                    // $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                    $transaction->balance_after          = $debitCustomer['balance_after'];
                    $transaction->company_balance_before = $debitCustomer['company_balance_before'];
                    $transaction->company_amount         = $debitCustomer['company_amount'];
                    $transaction->company_balance_after  = $debitCustomer['company_balance_after'];
                    $transaction->updated_at             = Carbon::now();
                    $transaction->modified_by            = auth()->user()->id;
                    $transaction->save();

                    if ($charge > 0 || $wbaCharge->bank_transfer_charge) {

                        $transactionChargeId = \generate_txnID();

                        // Save Charges to transaction table
                        $chargeTransaction = transactions::create([
                            "balance_before"         => $debitCustomer['charge_balance_before'],
                            "amount"                 => $charge,
                            "balance_after"          => $debitCustomer["charge_balance_after"],
                            "account_id"             => $transactionPinValidation['account_id'],
                            "company_id"             => \auth()->user()->company_id,
                            "branch_id"              => auth()->user()->branch_id,
                            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
                            "company_amount"         => $wbaCharge->bank_transfer_charge,
                            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
                            "description"            => "Charge for Bank Transfer",
                            "transaction_reference"  => $transactionChargeId,
                            "parent_transaction_id"  => $request->transaction_reference,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                        ]);
                    }

                    $newdescription = $request->description == "" ? "From " . \auth()->user()->first_name : $request->description;

                    // $myAccountName = [];

                    // $myAccountName = explode(" ", $request->receipient_name);

                    // $firstName = $myAccountName[0];

                    // $wovenController = new WovenController();
                    // $bankTransfer = $wovenController->transferToBank($request->destination_account, $firstName, $request->bank_code, $newdescription, $request->final_amount, $transaction->transaction_reference);
                    // $bankTransfer = ["status" => true, 'message' => "Bank Transfer Successful"];+

                    // if (auth()->user()->company_id != 4) {

                    $kudaController = new KudaController();

                    $bankAccount = $kudaController->verifyBankAccount($request->bank_code, $request->destination_account);

                    if (! $bankAccount['status']) {
                        $debitReversal = debitReversal($request->transaction_reference, $transactionPinValidation['account_id'], auth()->user()->company_id, $charge + $request->final_amount, $wbaCharge->bank_transfer_charge + $request->final_amount, 'both');

                        if (! $debitReversal['status']) {
                            return response()->json($debitReversal, 400);
                        }
                        $bankAccount['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";

                        // $bankAccount['message'] = "Failed to Complete Bank transfer. If you have been debited kindly contact support for reversal";

                        return response()->json($bankAccount);
                    }

                    $bankTransfer = $kudaController->bankTransfer($request->transaction_reference, $request->destination_account, $request->bank_code, $bankAccount['data']['session_id'], $request->final_amount, $bankAccount['data']['account_name'], $newdescription);
                    // } else {

                    // $assetMatrixController = new AssetMatrixController();

                    // $bankTransfer = $assetMatrixController->bankTransfer($request->transaction_reference, $request->destination_account, $request->bank_code, $request->final_amount, $newdescription);
                    // // }

                    $bankTransfer["data"] = ['balance' => $debitCustomer['charge_balance_after']];

                    if (! $bankTransfer['status']) {
                        $transaction->transaction_remarks_id = 3;
                        $transaction->save();
                        return response()->json($bankTransfer, 400);
                    }

                    $this->updateCustomerAndCompanyTransactionAsSuccessful($request->transaction_reference, $debitCustomer['balance_after'], $debitCustomer["company_balance_after"], false);
                    // sendSMS(['txnid' => $transaction->id, 'userid' => auth()->user()->id], "Debit Amt:$request->final_amount Desc: " . $newdescription . " Avail Bal:" . $debitCustomer["balance_after"] . " Date:" . date('Y-m-d') . " Ref:$transaction->transaction_reference", false, true, 'Debit Transaction');

                    sendSMS(auth()->user()->id, "Debit Amt:$request->final_amount, Desc: $transaction->description Avail Bal:" . $debitCustomer['charge_balance_after'] . "Date:" . date('Y-m-d') . " Ref: " . $request->transaction_reference, true, true, 'Debit Transaction');

                    return response()->json($bankTransfer);
                } else {

                    return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                }
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
        }
    }

    public function transferToBankAccountUSSD(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "amount"                => "required|numeric|gt:0",
            "transaction_reference" => "required",
            "destination_account"   => "required",
            "receipient_name"       => "required|string",
            "bank_code"             => "required|string",
            "api_key"               => "required|string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        if ($request->api_key != "apikey") {
            $ra = ["status" => false, "message" => "Invalid API key provided"];
            return response()->json($ra, 400);
        }

        $newdescription = $request->description ?? "From " . \auth()->user()->first_name;

        $kudaController = new KudaController();

        $bankAccount = $kudaController->verifyBankAccount($request->bank_code, $request->destination_account);

        if (! $bankAccount['status']) {
            $bankAccount['message'] = "Failed to Complete Bank transfer. If you have been debited kindly contact support for reversal";

            return response()->json($bankAccount);
        }

        $bankTransfer = $kudaController->bankTransfer(
            $request->transaction_reference,
            $request->destination_account,
            $request->bank_code,
            $bankAccount['data']['session_id'],
            $request->amount,
            $bankAccount['data']['account_name'],
            $newdescription, 2);

        if (! $bankTransfer['status']) {
            return response()->json($bankTransfer, 400);
        }

        return response()->json($bankTransfer);
    }

    public function walletTransfer(Request $request)
    {
        $description = $request->description;

        $transactionId = generateTransactionReference();
        $validation    = Validator::make($request->all(), [

            "transaction_reference" => "required|string",
            "destination_account"   => "required|numeric",
            "transaction_pin"       => "required|numeric|gt:0",
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->final_amount != null) {
        } else if ($request->amount != null) {
            $request->final_amount = $request->amount;
        } else {
            $ra = ["status" => false, "message" => "Please enter amount"];
            return response()->json($ra, 406);
        }
        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else {
                $customerBalance = $this->checkCustomerBalance($request->final_amount);
                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }
                $wbAccount = wb_account_details::where('virtual_accnum', $request->destination_account)->first();
                if ($wbAccount) {
                    if ($request->destination_account == \auth()->user()->virtual_account) {
                        return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                    }
                    if ($wbAccount->user->company_id != \auth()->user()->company_id) {
                        return response()->json(["status" => false, 'message' => "Invalid account number"], 409);
                    }
                    if (\checkcustomerrestriction($wbAccount->user_id, 1)) {
                        return response()->json(["status" => false, 'message' => "Destinaion account is not allowed to recieve payment at the moment"], 400);
                    }
                    $validateTransferAmount = $this->validateTransfer($request->final_amount);

                    if (! $validateTransferAmount['status']) {
                        return \response()->json($validateTransferAmount, 400);
                    }
                    if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                        return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                    }
                    $debitCustomer = \debitCustomerOnly($customerBalance['account_id'], $request->final_amount, \auth()->user());
                    // $debitCustomer = $this->debitCustomer($request);
                    if ($debitCustomer['status']) {
                        $creditCustomer = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
                        // $creditCustomer = \creditCustomerOnly("1", "1000");
                        $myDescription       = "You received payment from " . \auth()->user()->first_name . " - " . $request->description;
                        $debitTransactionRef = \generate_txnID();
                        $creditTransaction   = transactions::create([
                            "balance_before"         => $creditCustomer->balance,
                            "amount"                 => $request->final_amount,
                            "balance_after"          => $creditCustomer->balance + $request->final_amount,
                            "account_id"             => $wbAccount->id,
                            "description"            => $myDescription,
                            "branch_id"              => $wbAccount->user->branch_id,
                            "company_id"             => $wbAccount->user->company_id,
                            "transaction_reference"  => $debitTransactionRef,
                            "parent_transaction_id"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 32,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                        ]);
                        $creditCustomer->balance += $request->final_amount;
                        $creditCustomer->save();
                        $descArray = explode("-", $myDescription);
                        $this->updateTransactionAsSuccessful($request->transaction_reference, $debitCustomer['balance_after']);
                        // sendSMS(['txnid' => $creditTransaction->id, 'userid' => $wbAccount->id], "Credit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:$creditCustomer->balance Date:" . date('Y-m-d') . " Ref:$creditTransaction->transaction_reference", false, true, 'Credit Transaction');
                        // sendSMS(['txnid' => $transaction->id, 'userid' => auth()->user()->id], "Debit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:" . $debitCustomer["balance_after"] . " Date:" . date('Y-m-d') . " Ref:$transaction->transaction_reference", false, true, 'Debit Transaction');
                        return response()->json(["status" => true, 'message' => "Wallet Transfer Successful", 'data' => ['balance' => $debitCustomer['balance_after']]]);
                    } else {
                        return response()->json(["status" => false, 'message' => "Wallet Transfer failed"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid User Wallet Account"], 400);
                }
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
        }
    }

    public function initiateTransaction(Request $request)
    {

        $transactionId = generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "transaction_type" => "required|numeric|gt:0",
            "description"      => "string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $description = $request->description;

        if ($request->final_amount > 0) {

            if ($request->transaction_type == 3) {
                $companyName = auth()->user()->company->company_name;

                $transaction = transactions::create([
                    "balance_before"         => auth()->user()->company->wallet->balance,
                    "amount"                 => $request->amount,
                    "description"            => "Funding Admin Wallet",
                    "transaction_reference"  => $transactionId,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_remarks_id" => 1,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                ]);

                $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
                $body   = [
                    "tx_ref"          => $transactionId,
                    "amount"          => $request->amount,
                    "currency"        => auth()->user()->company->currency->code,
                    "redirect_url"    => $this->baseUrl . "/" . "company/" . auth()->user()->company->short_name . "/transaction/verify-fund-wallet",
                    "payment_options" => "card",
                    "customer"        => [
                        "email"       => Auth()->user()->email,
                        "phonenumber" => Auth()->user()->phone,
                        "name"        => Auth()->user()->first_name . ' ' . Auth()->user()->last_name,
                    ],
                    "customizations"  => [
                        "title"       => "Funding WBA Wallet",
                        "description" => "Fund Wired Banking Wallet",
                        "logo"        => "https://wiredbanking.ng/img/logo/logo-dark.png",
                    ],
                ];
                $url = 'https://api.flutterwave.com/v3/payments';

                $jsonData = \postRequest($header, $body, $url);

                // {"status":"success","message":"Hosted Link","data":{"link":"https:\/\/ravemodal-dev.herokuapp.com\/v3\/hosted\/pay\/79d6b15bf0bcb701e77d"}}
                return response()->json(["status" => true, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => " " . generateTransactionReference()]);
            } else if ($request->transaction_type == 5) {

                $description = $description ?? "Company Wallet Transfer";

                if ($request->finalAmount > 0) {

                    if (auth()->user()->company->wallet->balance >= $request->final_amount) {

                        $charge = 0;

                        $transaction = transactions::create([
                            "balance_before"         => auth()->user()->company->wallet->balance,
                            "amount"                 => $request->finalAmount,
                            "description"            => $description,
                            "transaction_reference"  => $transactionId,
                            "branch_id"              => \auth()->user()->branch_id,
                            "company_id"             => auth()->user()->company_id,
                            "transaction_remarks_id" => 1,
                            "transaction_type_id"    => $request->transaction_type,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                        ]);

                        return response()->json(
                            [
                                "status"  => true,
                                'message' => "Transaction initialized",
                                'data'    => [
                                    'charge'            => $charge ?? 0.00,
                                    'transactionId'     => $transaction->id,
                                    'transactionTypeId' => $request->transaction_type,
                                    'exchangeAmount'    => $request->finalAmount,
                                    'total'             => $request->finalAmount,
                                ],
                            ]
                        );
                    } else {
                        return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid Transfer amount"], 400);
                }
            } else if ($request->transaction_type == 8 || $request->transaction_type == 19) {

                $amount      = 0;
                $totalAmount = 0;
                $charge      = 834;

                if ($request->transaction_type == 19) {
                    $description = $description ?? "Mobile Money Transfer";
                    $totalAmount = $request->finalAmount;
                    $amount      = $request->finalAmount;
                } else if ($request->transaction_type == 8) {

                    if (\auth()->user()->company->setting->withdraw_charge_state == 1) {
                        $charge = $request->final_amount * (\auth()->user()->company->setting->min_withdraw_charge / 100);
                    } else {
                        $charge = \auth()->user()->company->setting->max_withdraw_charge;
                    }

                    $amount      = $request->final_amount;
                    $totalAmount = $request->final_amount + $charge;
                    $description = "You sent money to $request->receipient_name via Bank Transfer";
                }

                if ($charge == null || $charge == "") {
                    $charge = 0.00;
                }

                $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount);

                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
                }

                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $amount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "branch_id"              => \auth()->user()->branch_id,
                    "company_balance_before" => $customerBalance['company_balance'],
                    "company_amount"         => $amount,
                    "system_description"     => auth()->user()->company->company_name . " - " . auth()->user()->first_name . " sent money to " . $request->receipient_name,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 1,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Transaction initialized",
                        'data'    => [
                            'charge'                => $charge ?? 0.00,
                            'transaction_reference' => $transactionId,
                            'transactionTypeId'     => $request->transaction_type,
                            'transfer_amount'       => $amount,
                            'total'                 => $totalAmount,
                        ],
                    ]
                );

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else if ($request->transaction_type == 4) {

                $amount      = 0;
                $totalAmount = 0;

                $amount      = $request->final_amount;
                $totalAmount = $request->final_amount;
                $name        = isset($request->receipient_name) ? $request->receipient_name : "N/A";
                $description = "You sent payment to $name $request->description";

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount);

                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                if ($request->destination_account == \auth()->user()->virtual_account) {
                    return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                }

                $charge = 0;

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
                }

                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $totalAmount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "transaction_reference"  => $transactionId,
                    "company_balance_before" => $customerBalance["company_balance"],
                    "company_amount"         => 0,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_remarks_id" => 1,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Transaction initialized",
                        'data'    => [
                            'charge'                => $charge ?? 0.00,
                            'transaction_reference' => $transactionId,
                            'transactionTypeId'     => $request->transaction_type,
                            'transfer_amount'       => $amount,
                            'total'                 => $totalAmount,
                        ],
                    ]
                );

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else {
                return response()->json(["status" => false, 'message' => "Invalid Transaction Type"], 400);
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Amount"], 400);
        }
    }

    public function wovenWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {
            if ($request->transaction_type != "TRANSFER") {
                return;
            }

            $webhook = WovenWebhook::where('unique_reference', $request->unique_reference)->first();

            if (! $webhook) {

                WovenWebhook::create([
                    "nuban"                 => $request->nuban,
                    "bank_code"             => $request->bank_code,
                    "account_name"          => $request->account_name,
                    "account_email"         => $request->account_email,
                    "account_mobile_number" => $request->account_mobile_number,
                    "is_test"               => $request->is_test,
                    "account_reference"     => $request->account_reference,
                    "source_bank_code"      => $request->source_bank_code,
                    "source_nuban"          => $request->source_nuban,
                    "source_account_name"   => $request->source_account_name,
                    "source_bvn"            => $request->source_bvn,
                    "amount"                => $request->amount,
                    "fee"                   => $request->fee,
                    "currency"              => $request->currency,
                    "transaction_time"      => $request->transaction_time,
                    "nip_session_id"        => $request->nip_session_id,
                    "unique_reference"      => $request->unique_reference,
                    "transaction_type"      => $request->transaction_type,
                    "status"                => $request->status,
                    "settled_status"        => $request->settled_status,
                    "merchantId"            => $request->merchantId,
                    "vnubanId"              => $request->vnubanId,
                    "requestId"             => $request->requestId,
                    "amount_payable"        => $request->amount_payable,
                    "hash"                  => $request->hash,
                ]);

                WebhookLog::create([
                    'webhook_type' => 'woven',
                    'body'         => json_encode($request->all()),
                ]);
            }

            $wovenController = new WovenController();

            $verifyPaymentResponse = $wovenController->verifyWovenTransaction($request->unique_reference);

            if (! $verifyPaymentResponse['status']) {
                return response()->json($verifyPaymentResponse, 400);
            }

            if ($verifyPaymentResponse['data']['nuban'] != $request->nuban) {

                return response()->json(['status' => false, 'message' => "Invalid Webhook account number"], 400);
            }

            $transaction = transactions::where('transaction_reference', $request->unique_reference)->first();

            if ($transaction) {
                return ['status' => false, 'message' => "Duplicate transaction reference. Transaction already exists"];
            }

            $customer = User::where('virtual_account', $request->nuban)->first();

            if ($customer) {

                if ($customer->user_type_id == 1 || $customer->user_type_id == 4) {

                    $companyId = $customer->company->id;
                    $branchId  = $customer->branch->id;
                    $accountId = $customer->accounts->first()->id;

                    $customerAmount = $verifyPaymentResponse['data']['amount'];

                    $originalAmount = $verifyPaymentResponse['data']['amount'];

                    $customerCharge = 0;

                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(3, $originalAmount);

                    $companySettings = company_settings::where('company_id', $companyId)->first();

                    if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                        if ($companySettings->incoming_transfer_charge_state == 0) {
                            $customerCharge = $companySettings->incoming_transfer_charge_amount;
                            $customerAmount -= $customerCharge;
                        } else {
                            $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $verifyPaymentResponse['data']['amount'];
                            $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                            $customerAmount -= $customerCharge;
                        }
                    }

                    if ($customer->user_type_id == '4') {
                        $checkSendWebhookNotification = $this->forwardToWebhookUrlAndSweepAccount($request->all(), $companyId);

                        if ($checkSendWebhookNotification) {

                            $creditCustomer = sweepCustomerAccountAndCreditCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);
                        } else {

                            $creditCustomer = \creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);
                        }
                    } else {

                        $creditCustomer = \creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);
                    }

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['balance_before'],
                        "amount"                 => $verifyPaymentResponse['data']['amount'],
                        "balance_after"          => $creditCustomer['balance_after'],
                        'company_balance_before' => $creditCustomer['company_balance_before'],
                        "company_amount"         => $verifyPaymentResponse['data']['amount'],
                        'company_balance_after'  => $creditCustomer['company_balance_after'],
                        "description"            => "You recieved payment from $request->source_account_name",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "transaction_reference"  => $request->unique_reference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($customerCharge > 0) {

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['charge_balance_before'],
                            "amount"                 => $customerCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                            "company_amount"         => $companyCharge,
                            'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    } else {
                        if ($companyCharge > 0) {
                            $transaction = transactions::create([
                                "balance_before"         => 0,
                                "amount"                 => 0,
                                "balance_after"          => 0,
                                "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                                "company_amount"         => $companyCharge,
                                "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                                "description"            => "Charge from payment received",
                                "company_id"             => $companyId,
                                "branch_id"              => $branchId,
                                "parent_transaction_id"  => $transaction->id,
                                "transaction_reference"  => $transactionId,
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => 27,
                                "credit_debit_type_id"   => 2,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => 1,
                            ]);
                        }
                    }

                    $balanceAfter = $creditCustomer['charge_balance_after'];
                    $desc         = "You recieved payment from $request->source_account_name";
                    $currentDate  = Carbon::now();

                    $message = "Credit Amt:$originalAmount Ref:$request->unique_reference Desc:$desc Avail Bal:$balanceAfter Date:$currentDate";

                    $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                    return ['status' => true, 'message' => "Webhook Completed successfully", 'data' => $verifyPaymentResponse['data']];
                } else {
                }
            } else {
                $companyWallet = company_wallet::where('vaccount', $request->nuban)->first();

                if ($companyWallet) {

                    $companyId = $companyWallet->company_id;

                    $originalAmount = $verifyPaymentResponse['data']['amount'];

                    $companyCharge = 0;

                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(3, $originalAmount);

                    $creditCustomer = creditCompanyWithCharge($companyId, $originalAmount, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['balance_before'],
                        "company_balance_before" => $creditCustomer['balance_before'],
                        "company_balance_after"  => $creditCustomer['balance_after'],
                        "amount"                 => $verifyPaymentResponse['data']['amount'],
                        "company_amount"         => $verifyPaymentResponse['data']['amount'],
                        "balance_after"          => $creditCustomer['balance_after'],
                        "description"            => "You recieved payment from $request->source_account_name",
                        "company_id"             => $companyId,
                        "transaction_reference"  => $request->unique_reference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($companyCharge > 0) {

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['charge_balance_before'],
                            "company_balance_before" => $creditCustomer['charge_balance_before'],
                            "company_balance_after"  => $creditCustomer['charge_balance_after'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }

                    $balanceAfter = $creditCustomer['charge_balance_after'];
                    $desc         = "You recieved payment from $request->source_account_name";
                    $currentDate  = Carbon::now();

                    $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->unique_reference";

                    $adminUser = User::where('company_id', $companyId)->first();

                    if ($adminUser) {
                        $smsstate = sendSMS($adminUser->id, $message, true, true, 'Credit Transaction');
                    }

                    return ['status' => true, 'message' => "Webhook Completed successfully", 'data' => $verifyPaymentResponse['data']];
                } else {
                    return response()->json(['status' => false, 'message' => "Failed to verify Account"], 400);
                }
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "laravel error");
            return response()->json(['status' => false, 'message' => "Failed to Handle webhook... Please contact support"], 400);
        }
    }

    public function cyberpayWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();
        logInfo($request->all(), "Kuda Web Hook Request body");
        try {

            if ($request->paymentStatus == "Successful") {
                WebhookLog::create([
                    'webhook_type' => 'cyberpay',
                    'body'         => json_encode($request->all()),
                ]);

                $mandate = Mandate::where('mandate_code', $request->mandateCode)->first();

                if (! $mandate) {
                    return response()->json(['status' => false, "message" => "Invalid Mandate code Provided"]);
                }

                $mandateLog = MandateLog::where('payment_reference', $request->paymentReference)->first();

                if ($mandateLog) {
                    return response()->json(['status' => false, "message" => "Payment Reference already exists"]);
                }

                $cyberpayController = new CyberpayControllelr();

                $verifyPaymentResponse = $cyberpayController->verifyMandateStatus($request->mandateCode);

                if (! $verifyPaymentResponse['status']) {
                    return response()->json($verifyPaymentResponse, 400);
                }

                MandateLog::create([
                    "mandate_id"           => $mandate->id,
                    'payment_reference'    => $request->paymentReference,
                    'marchant_ref'         => $request->marchantRef,
                    'response_description' => $request->responseDescription,
                    'amount'               => $request->amount,
                    'narration'            => $request->narration,
                    'charge'               => $request->charge,
                    'payment_status'       => $request->paymentStatus,
                    'payment_date'         => $request->paymentDate,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => "Failed to Handle webhook... Please contact support"], 400);
        }
    }

    public function kudaWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        logInfo($request->all(), "Kuda Web Hook Request body");

        if ($request->transactionType != "Credit") {
            return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
        }

        try {

            $userWallet = wb_account_details::where('virtual_accnum', $request->accountNumber)->first();

            if (! $userWallet) {
                return \response()->json(['status' => false, 'message' => 'Invalid account provided']);
            }

            $transaction = transactions::where('transaction_reference', $request->transactionReference)->first();

            if ($transaction) {
                return \response()->json(['status' => false, 'message' => "Duplicate transaction reference. Transaction already exists"]);
            }

            $kudaAmount = $request->amount >= 1000000 ? ($request->amount - 5000) : $request->amount;

            $kudaController = new KudaController();

            // $verifyPaymentResponse = $kudaController->webhookWithdraw($request->transactionReference, $kudaAmount, $request->narrations, $userWallet->acc_reference);

            /* if (!$verifyPaymentResponse['status']) {
                return response()->json($verifyPaymentResponse, 400);
            }*/

            $customer = User::where('virtual_account', $request->accountNumber)->first();

            if ($customer) {

                if ($customer->user_type_id == 1) {

                    $companyId = $customer->company->id;
                    $branchId  = $customer->branch->id;
                    $accountId = $customer->accounts->first()->id;

                    $customerAmount = $request->amount / 100;

                    $originalAmount = $request->amount / 100;

                    $customerCharge = 0;

                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(2, ($request->amount / 100));

                    $companySettings = company_settings::where('company_id', $companyId)->first();

                    if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                        if ($companySettings->incoming_transfer_charge_state == 0) {
                            $customerCharge = $companySettings->incoming_transfer_charge_amount;
                            $customerAmount -= $customerCharge;
                        } else {
                            $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * ($request->amount / 100);
                            $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                            $customerAmount -= $customerCharge;
                        }
                    }

                    $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['balance_before'],
                        "amount"                 => ($request->amount / 100),
                        "balance_after"          => $creditCustomer['balance_after'],
                        'company_balance_before' => $creditCustomer['company_balance_before'],
                        "company_amount"         => ($request->amount / 100),
                        'company_balance_after'  => $creditCustomer['company_balance_after'],
                        "description"            => "You recieved payment from $request->senderName",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "transaction_reference"  => $request->transactionReference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($customerCharge > 0) {

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['charge_balance_before'],
                            "amount"                 => $customerCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                            "company_amount"         => $companyCharge,
                            'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    } else {
                        if ($companyCharge > 0) {
                            $transaction = transactions::create([
                                "balance_before"         => 0,
                                "amount"                 => 0,
                                "balance_after"          => 0,
                                "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                                "company_amount"         => $companyCharge,
                                "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                                "description"            => "Charge from payment received",
                                "company_id"             => $companyId,
                                "branch_id"              => $branchId,
                                "parent_transaction_id"  => $transaction->id,
                                "transaction_reference"  => $transactionId,
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => 27,
                                "credit_debit_type_id"   => 2,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => 1,
                            ]);
                        }
                    }

                    $balanceAfter = $creditCustomer['charge_balance_after'];
                    $desc         = "You recieved payment from $request->senderName";
                    $currentDate  = Carbon::now();

                    $message = "Credit Amt:$originalAmount Ref:$request->unique_reference Desc:$desc Avail Bal:$balanceAfter Date:$currentDate";

                    $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                    return ['status' => true, 'message' => "Webhook Completed successfully"];
                } else {
                }
            } else {
                $companyWallet = company_wallet::where('vaccount', $request->nuban)->first();

                if ($companyWallet) {

                    $companyId = $companyWallet->company_id;

                    $originalAmount = $verifyPaymentResponse['data']['amount'];

                    $companyCharge = 0;

                    $companyCharge = getWbaTransferCharges(2, ($request->amount / 100));

                    $creditCustomer = creditCompanyWithCharge($companyId, $originalAmount, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['balance_before'],
                        "company_balance_before" => $creditCustomer['balance_before'],
                        "company_balance_after"  => $creditCustomer['balance_after'],
                        "amount"                 => $verifyPaymentResponse['data']['amount'],
                        "company_amount"         => $verifyPaymentResponse['data']['amount'],
                        "balance_after"          => $creditCustomer['balance_after'],
                        "description"            => "You recieved payment from $request->source_account_name",
                        "company_id"             => $companyId,
                        "transaction_reference"  => $request->unique_reference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($companyCharge > 0) {

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['charge_balance_before'],
                            "company_balance_before" => $creditCustomer['charge_balance_before'],
                            "company_balance_after"  => $creditCustomer['charge_balance_after'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }

                    $balanceAfter = $creditCustomer['charge_balance_after'];
                    $desc         = "You recieved payment from $request->source_account_name";
                    $currentDate  = Carbon::now();

                    $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->unique_reference";

                    $adminUser = User::where('company_id', $companyId)->first();

                    if ($adminUser) {
                        $smsstate = sendSMS($adminUser->id, $message, true, true, 'Credit Transaction', '', 0, false);
                    }

                    return ['status' => true, 'message' => "Webhook Completed successfully", 'data' => $verifyPaymentResponse['data']];
                } else {
                    return response()->json(['status' => false, 'message' => "Failed to verify Account"], 400);
                }
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => "Failed to Handle webhook... Please contact support"], 400);
        }
    }

    public function providusWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        $signature = $request->header('X-Auth-Signature');

        $localSignature = env('APP_MODE') == 'live' ? config('providus.auth_signature_live') : config('providus.auth_signature_test');

        if ($signature != $localSignature) {
            return response()->json(['responseCode' => "02", "requestSuccessful" => false, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"], 400);
        }

        $providusController = new ProvidusController();

        $verifyTransaction = $providusController->verifyProvidusTransaction($request->sessionId);

        if (! $verifyTransaction["status"]) {
            return response()->json($verifyTransaction, 400);
        }

        try {

            $webhook = transactions::where('transaction_reference', $request->sessionId)->first();

            if ($webhook) {
                return response()->json(['responseCode' => "01", "requestSuccessful" => false, 'sessionId' => $request->sessionId, 'responseMessage' => "duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'providus',
                'body'         => json_encode($request->all()),
            ]);

            $account = wb_account_details::where('virtual_accnum', $request->accountNumber)->first();

            if ($account) {

                $customer  = $account->user;
                $companyId = $account->user->company->id;
                $branchId  = $account->user->branch->id;
                $accountId = $account->id;

                $customerAmount = $verifyTransaction["data"]["amount"];

                $originalAmount = $verifyTransaction["data"]["amount"];

                $customerCharge = 0;

                // Get Providus WBA Charges
                // Get amount to be deducted from company
                $companyCharge = getWbaTransferCharges(9, $request->transactionAmount);

                $companySettings = company_settings::where('company_id', $companyId)->first();

                if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                    if ($companySettings->incoming_transfer_charge_state == 0) {
                        $customerCharge = $companySettings->incoming_transfer_charge_amount;
                        $customerAmount -= $customerCharge;
                    } else {
                        $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $request->transactionAmount;
                        $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                        $customerAmount -= $customerCharge;
                    }
                }

                $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "company_amount"         => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => "You recieved payment from $request->sourceAccountName",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "transaction_reference"  => $request->sessionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 6,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                ]);

                if ($customerCharge > 0) {

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "company_amount"         => $companyCharge,
                        "balance_after"          => $creditCustomer['charge_balance_after'],
                        'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                        'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                        "description"            => "Charge from payment received",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);
                } else {
                    if ($companyCharge > 0) {
                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['company_charge_balance_before'],
                            "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "balance_after"          => $creditCustomer['company_charge_balance_after'],
                            "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }
                }

                $balanceAfter = $creditCustomer['charge_balance_after'];
                $desc         = "You recieved payment from $request->sourceAccountName";
                $currentDate  = Carbon::now();

                $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->sessionId";

                $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                return response()->json(['responseCode' => "00", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "success"]);
            } else {

                $companyWallet = company_wallet::where('vaccount', $request->accountNumber)->first();

                if ($companyWallet) {

                    $companyId = $companyWallet->company_id;

                    $originalAmount = $request->transactionAmount;

                    $companyCharge = 0;

                    // Get Providus WBA Charges
                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(9, $request->transactionAmount);

                    $creditCompany = creditCompanyWithCharge($companyId, $originalAmount, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCompany['balance_before'],
                        "company_balance_before" => $creditCompany['balance_before'],
                        "amount"                 => $request->transactionAmount,
                        "company_amount"         => $request->transactionAmount,
                        "balance_after"          => $creditCompany['balance_after'],
                        "company_balance_after"  => $creditCompany['balance_after'],
                        "description"            => "You recieved payment from $request->sourceAccountName",
                        "company_id"             => $companyId,
                        "transaction_reference"  => $request->sessionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($companyCharge > 0) {
                        $transaction = transactions::create([
                            "company_balance_before" => $creditCompany['charge_balance_before'],
                            "balance_before"         => $creditCompany['charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "company_balance_after"  => $creditCompany['charge_balance_after'],
                            "balance_after"          => $creditCompany['charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }

                    $balanceAfter = $creditCompany['charge_balance_after'];
                    $desc         = "You recieved payment from $request->sourceAccountName";
                    $currentDate  = Carbon::now();

                    $adminUser = User::where('company_id', $companyId)->first();

                    $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->unique_reference";

                    $smsstate = sendSMS($adminUser->id, $message, true, true, 'Credit Transaction', '', 0, false);

                    return response()->json(['responseCode' => "00", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "success"]);
                } else {

                    $response = Http::withHeaders($providusController->providusHeader())->post("https://wiredbanking.ng/api/webhook/webhookapi/providus_webhook/settlement_notif", $request->all());

                    return \response()->json($response->json());

                    // return response()->json(['responseCode' => "02", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"], 400);
                }
            }
        } catch (Exception $e) {
            return response()->json(['responseCode' => "01", "requestSuccessful" => false, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"], 400);
        }
    }

    public function gtbWebhook(Request $request)
    {

        $transactionId = generateTransactionReference();

        $signature = $request->header('x-squad-signature');

        $localSignature = env('APP_MODE') == 'live' ? config('gtb.secret_key_live') : config('gtb.secret_key_live');

        if ($signature !== hash_hmac('sha512', json_encode($request->all(), JSON_UNESCAPED_SLASHES), $localSignature)) {
            return response()->json(['response_code' => 400, 'transaction_reference' => $request->transaction_reference, 'response_description' => "The Webhook request is not from SQAD"], 400);
        }

        try {

            $webhook = transactions::where('transaction_reference', $request->transaction_reference)->first();

            if ($webhook) {
                return response()->json(['response_code' => 400, 'transaction_reference' => $request->transaction_reference, 'response_description' => "Duplicate transaction"], 400);
            }

            WebhookLog::create([
                'webhook_type' => 'gtb',
                'body'         => json_encode($request->all()),
            ]);

            $account = wb_account_details::where('virtual_accnum', $request->virtual_account_number)->first();

            if ($account) {

                $customer  = $account->user;
                $companyId = $account->user->company->id;
                $branchId  = $account->user->branch->id;
                $accountId = $account->id;

                $customerAmount = $request->principal_amount;

                $originalAmount = $request->principal_amount;

                $customerCharge = 0;

                // Get GTB WBA Charges
                // Get amount to be deducted from company
                $companyCharge = getWbaTransferCharges(8, $request->principal_amount);

                $companySettings = company_settings::where('company_id', $companyId)->first();

                if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                    if ($companySettings->incoming_transfer_charge_state == 0) {
                        $customerCharge = $companySettings->incoming_transfer_charge_amount;
                        $customerAmount -= $customerCharge;
                    } else {
                        $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $request->principal_amount;
                        $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                        $customerAmount -= $customerCharge;
                    }
                }

                $newdescription = str_replace("| [$customer->account_reference] TO WOBILO AFRICA LIMITED", "", $request->remarks);

                $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "company_amount"         => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => $newdescription,
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "transaction_reference"  => $request->transaction_reference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 6,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => $request->transaction_date,
                    "created_by"             => 1,
                ]);

                if ($customerCharge > 0) {

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "company_amount"         => $companyCharge,
                        "balance_after"          => $creditCustomer['charge_balance_after'],
                        'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                        'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                        "description"            => "Charge from payment received",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => $request->transaction_date,
                        "created_by"             => 1,
                    ]);
                } else {
                    if ($companyCharge > 0) {
                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['company_charge_balance_before'],
                            "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "balance_after"          => $creditCustomer['company_charge_balance_after'],
                            "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => $request->transaction_date,
                            "created_by"             => 1,
                        ]);
                    }
                }

                $balanceAfter = $creditCustomer['charge_balance_after'];
                $desc         = "You recieved payment";
                $currentDate  = Carbon::now();

                $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date: $request->transaction_date Ref:$request->transaction_reference";

                $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                return response()->json(['response_code' => 200, 'transaction_reference' => $request->transaction_reference, 'response_description' => "Success"]);
            } else {

                $companyWallet = company_wallet::where('vaccount', $request->virtual_account_number)->first();

                if ($companyWallet) {

                    $companyId = $companyWallet->company_id;

                    $originalAmount = $request->principal_amount;

                    $companyCharge = 0;

                    // Get GTB WBA Charges
                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(8, $request->principal_amount);

                    $newdescription = str_replace("TO WOBILO AFRICA LIMITED", "", $request->remarks);

                    $creditCompany = creditCompanyWithCharge($companyId, $originalAmount, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCompany['balance_before'],
                        "company_balance_before" => $creditCompany['balance_before'],
                        "amount"                 => $request->principal_amount,
                        "company_amount"         => $request->principal_amount,
                        "balance_after"          => $creditCompany['balance_after'],
                        "company_balance_after"  => $creditCompany['balance_after'],
                        "description"            => $newdescription,
                        "company_id"             => $companyId,
                        "transaction_reference"  => $request->transaction_reference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => $request->transaction_date,
                        "created_by"             => 1,
                    ]);

                    if ($companyCharge > 0) {
                        $transaction = transactions::create([
                            "company_balance_before" => $creditCompany['charge_balance_before'],
                            "balance_before"         => $creditCompany['charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "company_amount"         => $companyCharge,
                            "company_balance_after"  => $creditCompany['charge_balance_after'],
                            "balance_after"          => $creditCompany['charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }

                    $balanceAfter = $creditCompany['charge_balance_after'];
                    $desc         = $newdescription;
                    $currentDate  = Carbon::now();

                    $adminUser = User::where('company_id', $companyId)->first();

                    $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$request->transaction_date Ref:$request->transaction_reference";

                    $smsstate = sendSMS($adminUser->id, $message, true, true, 'Credit Transaction', '', 0, false);

                    //

                    return response()->json(['response_code' => 200, 'transaction_reference' => $request->transaction_reference, 'response_description' => "Success"]);
                } else {
                    return response()->json(['response_code' => 400, 'transaction_reference' => $request->transaction_reference, 'response_description' => "Invalid Company or customer account"], 400);
                }
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['response_code' => 500, 'transaction_reference' => $request->transaction_reference, 'response_description' => "Invalid Company or customer account"], 500);

            // return response()->json(['status' => false, 'message' => "Webhook handling error"], 400);
        }
    }

    public function getCustomerDetails(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "SerialNumber" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

        if (! $terminal) {

            $terminalResponse = forwardRequestToAM("get-details", $request->all());
            return $terminalResponse;
        }

        $agent = $terminal->agent;

        $bank = banks::find(is_null($agent->bank_id) ? 25 : $agent->bank_id);

        return \response()->json([
            'code'    => "200",
            'message' => "success",
            "data"    => [
                "firstName"     => $agent->first_name,
                "lastName"      => $agent->last_name,
                "bvn"           => $agent->bvn,
                "accountNumber" => $agent->virtual_account,
                "bankName"      => $bank->bank_name,

            ],
        ]);
    }

    public function errandPaycheckBalance(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "serial_number"    => 'required|string',
            "transaction_type" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $terminal = AgentTerminal::where('terminal_code', $request->serial_number)->first();

        if (! $terminal) {

            $terminalResponse = forwardRequestToAM("check-balance", $request->all());
            return $terminalResponse;
        }

        $company = $terminal->agent->company;

        $posSetting = $terminal->agent->company->setting->pos_setting;

        if (($posSetting == 2 || $posSetting == 3) && ($request->serviceCode != "CO1" && $request->serviceCode != "C01" && $request->serviceCode != "PU1" && $request->serviceCode != "BLE1")) {
            return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->agent->wb_account_details->first()->user_wallet->balance, 'agent_status' => "InActive"]);
        }

        if ($request->has("pin")) {
            if ($request->pin != "") {
                if ($request->pin != $terminal->agent->pin && (($request->serviceCode != "CO1" && $request->serviceCode != "C01" && $request->serviceCode != "PU1") || $request->serviceCode == "BLE1")) {
                    return \response()->json(['is_pin_valid' => false, "balance" => '0.00', "agent_status" => $terminal->status != 1 ? "InActive" : "Active"]);
                }
            } else {
                if ($request->pin != $terminal->agent->pin && $request->serviceCode == "FT1") {
                    return \response()->json(['is_pin_valid' => false, "balance" => '0.00', 'agent_status' => false]);
                }
            }
        }

        $transactionType = 0;

        if ($request->transaction_type == "outward") {
            if ($request->debitAgent) {

                $customerCharge    = 0;
                $companyCommission = 0;

                $description        = "";
                $companyDescription = "";
                $systemDescription  = "";

                if ($request->serviceCode == "FT1" && $request->amount > 250000) {
                    return response()->json(['status' => false, 'message' => 'Transaction limit exceed. Cannot transfer above 250,000']);
                }

                if (str_starts_with($request->serviceCode, "FT")) {
                    $transactionType = 65;
                } else if (str_starts_with($request->serviceCode, "BAT")) {
                    $companyCommission = $request->amount * 0.01;
                    $transactionType   = 71;
                } else if (str_starts_with($request->serviceCode, "BMD")) {
                    $companyCommission = $request->amount * 0.01;
                    $transactionType   = 72;
                } else if (str_starts_with($request->serviceCode, "BUB")) {
                    $transactionType = 84;
                } else if (str_starts_with($request->serviceCode, "BCT")) {
                    $transactionType = 82;
                } else if (str_starts_with($request->serviceCode, "BBT")) {
                    $transactionType = 83;
                }

                if ($request->serviceCode == "FT1") {

                    // Get GTB WBA Charges
                    // Get amount to be deducted from company
                    $customerCharge    = getWbaTransferCharges(1, $request->amount);
                    $companyCommission = 2;

                    $description        = "Funds transfer via termial: $terminal->terminal_code";
                    $companyDescription = "Funds transfer commission via terminal - $terminal->terminal_code";
                    $systemDescription  = "Funds Transferred via POS Terminal: $terminal->terminal_code, Amount: $request->amount";

                    $systemDescription = "Terminal ID: $terminal->terminal_code, Card Details Destination Acc";
                    $systemDescription .= " Destination Bank Name ";
                }

                if (! $request->has("reference")) {
                    return response()->json(['status' => false, 'message' => 'Transaction reference is required']);
                }

                if ($request->reference == "") {
                    return response()->json(['status' => false, 'message' => 'Transaction reference is required']);
                }

                $checkTransaction = transactions::where('transaction_reference', $request->reference)->first();

                if ($checkTransaction) {
                    return response()->json(['status' => false, 'message' => 'Transaction Already exists']);
                }

                if ($terminal->status != 1) {
                    return response()->json(['status' => false, 'message' => 'POS Terminal is inactive']);
                }

                // $debitCustomerPOS = \debitCustomerAndCompanyPOSWithChargeAndCommission($terminal->agent->accounts[0]->id, $terminal->terminal_code, $terminal->agent->company_id, $request->amount, $customerCharge, $companyCommission);

                $creditCustomer = \debitCustomerAndCompanyPOSWithChargeAndCommission($terminal->agent->accounts[0]->id, $terminal->terminal_code, $terminal->agent->company_id, $request->amount, $customerCharge, $companyCommission, $terminal->agent);

                if (! $creditCustomer['status']) {
                    return response()->json($creditCustomer, 400);
                }

                $transactionReference = generate_txnID();

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['pos_balance_before'],
                    "amount"                 => $creditCustomer['pos_amount'],
                    "balance_after"          => $creditCustomer['pos_balance_after'],
                    "company_amount"         => 0,
                    'company_balance_before' => 0,
                    'company_balance_after'  => 0,
                    "description"            => $description,
                    "branch_id"              => $terminal->agent->branch_id,
                    "account_id"             => $terminal->agent->accounts[0]->id,
                    "system_description"     => $systemDescription,
                    "transaction_reference"  => $request->reference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => $transactionType,
                    "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $terminal->agent->id,
                ]);

                if ($creditCustomer['company_amount'] > 0) {

                    $chargeTransactionRef = generate_txnID();

                    $transaction = transactions::create([
                        "balance_before"         => 0,
                        "amount"                 => 0,
                        "balance_after"          => 0,
                        "company_amount"         => $creditCustomer['company_amount'],
                        'company_balance_before' => $creditCustomer['company_balance_before'],
                        'company_balance_after'  => $creditCustomer['company_balance_after'],
                        "description"            => $companyDescription,
                        "parent_transaction_id"  => $transaction->id,
                        "company_id"             => $terminal->agent->company_id,
                        "branch_id"              => $terminal->agent->branch_id,
                        "transaction_reference"  => $chargeTransactionRef,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 70,
                        "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $terminal->agent->id,
                    ]);
                }
                return \response()->json(['is_pin_valid' => true, 'balance' => $creditCustomer['pos_balance_before'], 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
            } else {
                if ($request->serviceCode == "BLE1") {
                    if ($request->pin != "") {

                        if ($posSetting == 2 || $posSetting == 3) {
                            return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->agent->wb_account_details->first()->user_wallet->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
                        }

                        if ($request->pin == $terminal->agent->pin) {
                            return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
                        } else {
                            return \response()->json(['is_pin_valid' => false, "message" => 'Invalid transaction pin']);
                        }
                    }
                    return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
                }
            }
        }

        if ($posSetting == 2 || $posSetting == 3) {
            return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->agent->wb_account_details->first()->user_wallet->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
        } else {
            if ($request->serviceCode == "CO1" || $request->serviceCode == "C01") {
                return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
            }
        }
    }

    public function servicesWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $currentRequest = $request->all();

        try {

            $checkWebhook = WebhookLog::where('unique_reference', $request->TransactionReference)->first();

            if (! $checkWebhook) {

                WebhookLog::create([
                    'webhook_type'     => 'errandpay',
                    'unique_reference' => $request->TransactionReference,
                    'body'             => json_encode($request->all()),
                ]);
            }

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {

                $terminalResponse = forwardRequestToAM("webhook", $request->all());

                logInfo($terminalResponse, "Terminal Response from Wirelessbeta");
                logInfo("--------------===============", "another response log again");
                return $terminalResponse;
            }

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if (! $webhook && $request->TransactionType != "CashOut") {
                return response()->json(['status' => false, 'responseMessage' => "Invalid transaction reference"]);
            }

            if ($webhook && $request->TransactionType == "CashOut") {
                return response()->json(['status' => false, 'responseMessage' => "Duplicate transaction reference"]);
            }

            if ($request->TransactionType != "CashOut") {
                if ($webhook->transaction_remarks_id == 5) {
                    return response()->json(['status' => false, 'responseMessage' => "Transaction has already been handled"]);
                }

                if ($webhook->credit_debit_type_id != 2 && $request->TransactionType == "Reversal") {
                    return response()->json(['status' => false, 'responseMessage' => "This transaction cannot be reversed"]);
                }
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company_id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            $customerCharge = 0;
            $companyCharge  = 0;

            if ($request->TransactionType == "FundTransfer") {
                $customerCharge    = getWbaTransferCharges(1, $request->Amount);
                $companyCommission = 2;
            }

            if ($request->TransactionType == "CashOut" || $request->TransactionType == "Reversal") {

                if ($request->TransactionType == "CashOut") {
                    $description        = "You recieved payment from POS Terminal: $terminal->terminal_code";
                    $companyDescription = "You recieved commission from POS Terminal - $terminal->terminal_code payment received";
                    $systemDescription  = "Payment received from POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                    $systemDescription = "Card Details Masked PAN: " . $currentRequest["AdditionalDetails"]["MaskedPAN"] . " Exp Date: " . $currentRequest["AdditionalDetails"]["CardExpiry"];
                    $systemDescription .= " TerminalID " . $currentRequest["AdditionalDetails"]["TerminalID"];
                } else if ($request->TransactionType == "Reversal") {
                    $description        = $currentRequest['StatusDescription'] . ". Terminal Code: $terminal->terminal_code, Transaction Ref: " . $currentRequest['TransactionReference'];
                    $companyDescription = "";
                    $systemDescription  = $description;

                    // $systemDescription .= " TerminalID " . $currentRequest["AdditionalDetails"]["TerminalID"];
                }

                if ($terminal->status != 1) {
                    return response()->json(['status' => false, 'message' => 'POS Terminal is inactive']);
                }

                $customerAmount        = $request->Amount;
                $initialCustomerCharge = 0;
                $customerChargeOnly    = 0;

                $posCreditDebitType = true;

                if ($request->TransactionType == "CashOut") {

                    // Get Errandpay POS WBA Charges
                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(11, $request->Amount);

                    logInfo($companyCharge, "Company POS Charge amount");

                    $companySettings = company_settings::where('company_id', $companyId)->first();

                    $customerCharge = $companySettings->additional_pos_charge_percent * $request->Amount;
                    $customerCharge = $customerChargeOnly = $initialCustomerCharge = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                    $customerCharge += $companyCharge;
                    $customerAmount -= $customerCharge;
                } else {
                    $webhook->transaction_remarks_id = 5;
                    $webhook->save();
                }

                if ($request->PostingType == "Cr") {

                    $posSetting = $terminal->agent->company->setting->pos_setting;

                    logInfo($posSetting, "My POS Setting is...");

                    if ($posSetting == 2) {

                        $posCreditDebitType = false;

                        $creditCustomer = \creditCustomerOnly($accountId, $originalAmount, $customerChargeOnly);

                        logInfo($creditCustomer, "POS Credit customer Only");

                        if (! $creditCustomer['status']) {
                            return response()->json($creditCustomer, 400);
                        }

                        $transactionType = 109;

                        $transaction = transactions::create([
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "description"            => "You Received funds via POS Terminal: " . $terminal->terminal_code,
                            "balance_before"         => $creditCustomer['balance_before'],
                            "balance_after"          => $creditCustomer['balance_after'],
                            "amount"                 => $originalAmount + $customerChargeOnly,
                            "transaction_reference"  => $request->TransactionReference,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => $transactionType,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => $customer->id,
                        ]);
                    } else if ($posSetting == 3) {

                        $posCreditDebitType = false;

                        $creditCustomer = \creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                        logInfo($creditCustomer, "POS Credit customer And Company");

                        if (! $creditCustomer['status']) {
                            return response()->json($creditCustomer, 400);
                        }

                        $transactionType = 109;

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['balance_before'],
                            "amount"                 => $originalAmount - $customerCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            "description"            => "You Received funds via POS Terminal: " . $terminal->terminal_code,
                            "account_id"             => $accountId,
                            "transaction_reference"  => $request->TransactionReference,
                            "company_id"             => $companyId,
                            "company_balance_before" => $creditCustomer['company_balance_before'],
                            "company_amount"         => $originalAmount - $companyCharge,
                            "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 63,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => $customer->id,
                        ]);
                    } else {

                        $creditCustomer = creditPOSTerminal($terminal->terminal_code, $originalAmount, $customerCharge, $initialCustomerCharge, $companyId);
                    }
                } else {
                    $creditCustomer = \debitCustomerAndCompanyPOSWithChargeAndCommission($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCommission);
                }

                if ($posCreditDebitType) {

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['pos_balance_before'],
                        "amount"                 => $creditCustomer['pos_amount'],
                        "balance_after"          => $creditCustomer['pos_balance_after'],
                        "company_amount"         => 0,
                        'company_balance_before' => 0,
                        'company_balance_after'  => 0,
                        "description"            => $description,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "system_description"     => $systemDescription,
                        "transaction_reference"  => $request->TransactionType == "Reversal" ? generate_txnID() : $request->TransactionReference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => $request->TransactionType == "Reversal" ? 115 : 63,
                        "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customer->id,
                    ]);

                    if ($creditCustomer['company_amount'] > 0) {

                        $chargeTransactionRef = generate_txnID();

                        $transaction = transactions::create([
                            "balance_before"         => 0,
                            "amount"                 => 0,
                            "balance_after"          => 0,
                            "company_amount"         => $creditCustomer['company_amount'],
                            'company_balance_before' => $creditCustomer['company_balance_before'],
                            'company_balance_after'  => $creditCustomer['company_balance_after'],
                            "description"            => $companyDescription,
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "transaction_reference"  => $chargeTransactionRef,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 70,
                            "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => $customer->id,
                        ]);
                    }
                }

                $message = $request->PostingType == "Cr" ? "POS Credited successfully" : "POS Debited successfully";

                return response()->json(['status' => true, 'message' => $message]);
            } else {
                if ($request->TransactionType == "FundTransfer") {
                    $description        = "Funds transfer via termial: $terminal->terminal_code";
                    $companyDescription = "Funds transfer commission via terminal - $terminal->terminal_code";
                    $systemDescription  = "Funds Transferred via POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                    $systemDescription = "Terminal ID: $terminal->terminal_code, Card Details Destination Acc. Name: " . $currentRequest["AdditionalDetails"]["DestinationAccountName"] . " Destination Account Number: " . $currentRequest["AdditionalDetails"]["DestinationAccountNumber"];
                    $systemDescription .= " Destination Bank Name " . $currentRequest["AdditionalDetails"]["DestinationBankName"];
                } else if ($request->TransactionType == "BillsPayment") {
                    $customerCharge        = 0;
                    $initialCustomerCharge = 0;
                    $description           = "You paid bills via termial: $terminal->terminal_code";
                    $companyCommission     = 0;

                    $systemDescription = "You purchased " . $currentRequest["AdditionalDetails"]["BillCategory"] . " from " . $currentRequest["AdditionalDetails"]["BillService"];
                    $systemDescription .= " for " . $currentRequest["AdditionalDetails"]["Beneficiary"] . " from POS Terminal: " . $terminal->terminal_code;
                }

                $webhook->description        = $systemDescription;
                $webhook->system_description = $systemDescription;
                $webhook->save();

                $message = $request->PostingType == "Cr" ? "POS Credited successfully" : "POS Debited successfully";

                return response()->json(['status' => true, 'message' => $message]);
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function gruppServicesWebhook(Request $request)
    {
        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $currentRequest = $request->all();

        $secretKey = env('APP_MODE') == 'test' ? config('grupp.secret_key_test') : config('grupp.secret_key_live');

        $stringToEncrypt = "$secretKey|$request->reference|$request->amount|$request->transactionDate";

        $hashed = hash("sha512", $stringToEncrypt);

        // if ($hashed != $request->hash) {
        //     return response()->json(['status' => false, 'message' => "Failed to authorize webhook"], 400);
        // }

        try {

            $checkWebhook = WebhookLog::where('unique_reference', $request->reference)->first();

            if (! $checkWebhook) {

                WebhookLog::create([
                    'webhook_type'     => 'grupp',
                    'unique_reference' => $request->reference,
                    'body'             => json_encode($request->all()),
                ]);
            }

            if ($request->status != "success") {
                return response()->json(['status' => false, 'responseMessage' => "Transaction was not successful"]);
            }

            $terminal = AgentTerminal::where('terminal_code', $request->serialNumber)->first();

            if (! $terminal) {
                return response()->json(['status' => false, 'message' => 'Invalid Terminal Id'], 400);
            }
            // if (!$terminal) {

            //     $terminalResponse  = forwardRequestToAM("webhook", $request->all());

            //     logInfo($terminalResponse, "Terminal Response from Wirelessbeta");
            //     logInfo("--------------===============", "another response log again");
            //     return $terminalResponse;
            // }

            $webhook = transactions::where('transaction_reference', $request->reference)->first();

            if (! $webhook && $request->transactionType != "card") {
                return response()->json(['status' => false, 'responseMessage' => "Invalid transaction reference"]);
            }

            if ($webhook && $request->transactionType == "card") {
                return response()->json(['status' => false, 'responseMessage' => "Duplicate transaction reference"]);
            }

            if ($request->transactionType != "card") {
                // if ($webhook->transaction_remarks_id == 5) {
                return response()->json(['status' => false, 'responseMessage' => "Transaction is not a card transaction"]);
                // }

                // if($webhook->credit_debit_type_id != 2 && $request->transactionType == "Reversal"){
                //     return response()->json(['status' => false, 'responseMessage' => "This transaction cannot be reversed"]);
                // }
            }

            $originalAmount = $request->amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company_id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            $customerCharge = 0;
            $companyCharge  = 0;

            if ($request->transactionType == "card") {
                $companyCharge     = getWbaTransferCharges(1, $request->amount);
                $companyCommission = 2;
            }

            if ($request->transactionType == "card") {

                if ($request->transactionType == "card") {
                    $description        = "You recieved payment from POS Terminal: $terminal->terminal_code";
                    $companyDescription = "Charge for receiving POS payment  via POS Terminal - $terminal->terminal_code";
                    $systemDescription  = "Payment received from POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                    $systemDescription = "Card Details Masked PAN: " . $request->pan . " Exp Date: " . $request->cardExpiry;
                    $systemDescription .= " TerminalID " . $terminal->terminal_code;
                } else if ($request->transactionType == "Reversal") {
                    $description        = $request->statusDescription . ". Terminal Code: $terminal->terminal_code, Transaction Ref: " . $request->reference;
                    $companyDescription = "";
                    $systemDescription  = $description;

                    // $systemDescription .= " TerminalID " . $currentRequest["AdditionalDetails"]["TerminalID"];
                }

                if ($terminal->status != 1) {
                    return response()->json(['status' => false, 'message' => 'POS Terminal is inactive']);
                }

                $customerAmount        = $request->amount;
                $initialCustomerCharge = 0;

                if ($request->transactionType == "card") {

                    // Get Errandpay POS WBA Charges
                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(36, $request->amount);

                    $companySettings = company_settings::where('company_id', $companyId)->first();

                    $customerCharge = $companySettings->additional_pos_charge_percent * $request->amount;
                    $customerCharge = $initialCustomerCharge = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                    $customerCharge += $companyCharge;
                    $customerAmount -= $customerCharge;
                } else {
                    $webhook->transaction_remarks_id = 5;
                    $webhook->save();
                }

                $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                logInfo($creditCustomer, "Customer Credit Log");

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    "company_amount"         => $originalAmount,
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => $description,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "system_description"     => $systemDescription,
                    "transaction_reference"  => $request->reference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 63,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $customer->id,
                ]);

                if ($companyCharge > 0) {

                    $chargeTransactionRef = generate_txnID();

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "balance_after"          => $creditCustomer['charge_balance_after'],
                        "company_amount"         => $companyCharge,
                        "account_id"             => $accountId,
                        'company_balance_before' => $creditCustomer['charge_balance_before'],
                        'company_balance_after'  => $creditCustomer['company_balance_after'],
                        "description"            => $companyDescription,
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "transaction_reference"  => $chargeTransactionRef,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 70,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customer->id,
                    ]);
                }

                $message = "POS Credited successfully";

                return response()->json(["handshakeId" => $request->reference]);
            } else {
                if ($request->TransactionType == "FundTransfer") {
                    $description        = "Funds transfer via termial: $terminal->terminal_code";
                    $companyDescription = "Funds transfer commission via terminal - $terminal->terminal_code";
                    $systemDescription  = "Funds Transferred via POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                    $systemDescription = "Terminal ID: $terminal->terminal_code, Card Details Destination Acc. Name: " . $currentRequest["AdditionalDetails"]["DestinationAccountName"] . " Destination Account Number: " . $currentRequest["AdditionalDetails"]["DestinationAccountNumber"];
                    $systemDescription .= " Destination Bank Name " . $currentRequest["AdditionalDetails"]["DestinationBankName"];
                } else if ($request->TransactionType == "BillsPayment") {
                    $customerCharge        = 0;
                    $initialCustomerCharge = 0;
                    $description           = "You paid bills via termial: $terminal->terminal_code";
                    $companyCommission     = 0;

                    $systemDescription = "You purchased " . $currentRequest["AdditionalDetails"]["BillCategory"] . " from " . $currentRequest["AdditionalDetails"]["BillService"];
                    $systemDescription .= " for " . $currentRequest["AdditionalDetails"]["Beneficiary"] . " from POS Terminal: " . $terminal->terminal_code;
                }

                $webhook->description        = $systemDescription;
                $webhook->system_description = $systemDescription;
                $webhook->save();

                $message = $request->PostingType == "Cr" ? "POS Credited successfully" : "POS Debited successfully";

                return response()->json(['status' => true, 'message' => $message]);
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function errandPayFundsTransferWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if ($webhook) {
                return response()->json(['status' => false, 'message' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {
                $terminalResponse = forwardRequestToAM("check-balance", $request->all());
                return $terminalResponse;
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company->id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            // Get Errandpay POS WBA Charges
            // Get amount to be deducted from company
            $companyCharge = getWbaTransferCharges(11, $request->Amount);

            $companySettings = company_settings::where('company_id', $companyId)->first();

            $customerCharge = $companySettings->additional_pos_charge_percent * 0.01 * $request->Amount;
            $customerCharge = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
            $customerAmount -= $customerCharge;

            $posSetting = $terminal->agent->company->setting->pos_setting;

            logInfo($posSetting, "My POS Setting is...");

            if ($posSetting == 2) {

                $creditCustomer = \creditCustomerOnly($accountId, $originalAmount);

                logInfo($creditCustomer, "POS Credit customer Only");

                if (! $creditCustomer['status']) {
                    return response()->json($creditCustomer, 400);
                }

                $transactionType = 109;

                $transaction = transactions::create([
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "description"            => "You Received funds via POS Terminal",
                    "balance_before"         => $creditCustomer['balance_before'],
                    "balance_after"          => $creditCustomer['balance_after'],
                    "amount"                 => $originalAmount,
                    "transaction_reference"  => $request->TransactionReference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => $transactionType,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $customer->id,
                ]);
            } else if ($posSetting == 3) {

                $creditCustomer = \creditCustomerAndCompany($accountId, $companyId, $originalAmount, $originalAmount);

                logInfo($creditCustomer, "POS Credit customer And Company");

                if (! $creditCustomer['status']) {
                    return response()->json($creditCustomer, 400);
                }

                $transactionType = 109;

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    "description"            => "You Received funds via POS Terminal",
                    "account_id"             => $accountId,
                    "transaction_reference"  => generate_txnID(),
                    "company_id"             => $companyId,
                    "company_balance_before" => $creditCustomer['company_balance_before'],
                    "company_amount"         => $originalAmount,
                    "company_balance_after"  => $creditCustomer['company_balance_after'],
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 109,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $customer->id,
                ]);
            } else {

                $creditCustomer = \creditCustomerAndCompanyPOSAmountWithCharge($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCharge);

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    "description"            => "You Received funds via POS Terminal",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "transaction_reference"  => $request->TransactionReference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 68,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                ]);

                $chargeTransaction = transactions::create([
                    "balance_before"         => $creditCustomer['charge_balance_before'],
                    "amount"                 => $customerCharge,
                    "company_amount"         => $companyCharge,
                    "balance_after"          => $creditCustomer['charge_balance_after'],
                    'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                    "description"            => "Charge from payment received",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "parent_transaction_id"  => $transaction->id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 27,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                ]);

                $balanceAfter = $creditCustomer['charge_balance_after'];
                $desc         = "You Received funds via POS Terminal";
                $currentDate  = Carbon::now();

                $message = "Credit Amt:" . $creditCustomer['charge_balance_after'] . " Desc:$desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->TransactionReference";

                $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction', '', 0, false);
            }

            return response()->json(['status' => true, 'message' => "Webhook Handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function errandPayBillsPaymentWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {

            // {
            //     "StatusCode": "00",
            //     "StatusDescription": "Successful",
            //     "SerialNumber": "82912785",
            //     "Amount": 10.00,
            //     "TransactionReference": "384345838903",
            //     "Currency": "NGN",
            //     "TransactionDate": "07/03/2022",
            //     "TransactionTime": "10:44:03 AM",
            //     "TransactionType": "CashOut",
            //     "ServiceCode": "CO1",
            //     "Fee": 0.05,
            //     "PostingType": "Cr",
            //     "AdditionalDetails": {
            //         "MaskedPAN": "506110xxxxxxxxx0460",
            //         "Stan": "838903",
            //         "CardExpiry": "2404",
            //         "TerminalID": "2033CXFD"
            //     }
            // }

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if ($webhook) {
                return response()->json(['status' => false, 'message' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {

                $terminalResponse = forwardRequestToAM("check-balance", $request->all());
                return $terminalResponse;
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company->id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            // Get Errandpay WBA Charges
            // Get amount to be deducted from company
            $companyCharge = getWbaTransferCharges(11, $request->Amount);

            $companySettings = company_settings::where('company_id', $companyId)->first();

            $customerCharge = 0;

            if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                if ($companySettings->incoming_transfer_charge_state == 0) {
                    $customerCharge = $companySettings->incoming_transfer_charge_amount;
                    $customerAmount -= $customerCharge;
                } else {
                    $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $request->Amount;
                    $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                    $customerAmount -= $customerCharge;
                }
            }

            $creditCustomer = \debitCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

            $transaction = transactions::create([
                "balance_before"         => $creditCustomer['balance_before'],
                "amount"                 => $originalAmount,
                "company_amount"         => $originalAmount,
                "balance_after"          => $creditCustomer['balance_after'],
                'company_balance_before' => $creditCustomer['company_balance_before'],
                'company_balance_after'  => $creditCustomer['company_balance_after'],
                "description"            => "You Paid Bill using POS Terminal",
                "company_id"             => $companyId,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "transaction_reference"  => $request->TransactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 6,
                "credit_debit_type_id"   => 1,
                "created_date"           => date('Y-m-d'),
                "created_by"             => 1,
            ]);

            if ($customerCharge > 0) {

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['charge_balance_before'],
                    "amount"                 => $customerCharge,
                    "company_amount"         => $companyCharge,
                    "balance_after"          => $creditCustomer['charge_balance_after'],
                    'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                    "description"            => "Charge from payment received",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "parent_transaction_id"  => $transaction->id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 27,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                ]);
            } else {
                if ($companyCharge > 0) {
                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['company_charge_balance_before'],
                        "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                        "amount"                 => $companyCharge,
                        "company_amount"         => $companyCharge,
                        "balance_after"          => $creditCustomer['company_charge_balance_after'],
                        "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                        "description"            => "Charge from Terminal payment",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);
                }
            }

            $balanceAfter = $creditCustomer['charge_balance_after'];
            $desc         = "You recieved payment from POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Debit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->sessionId";

            $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction');

            return response()->json(['status' => true, 'message' => "Webhook Handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function monnifyWebhook(Request $request)
    {

        $transactionId = generateTransactionReference();

        WebhookLog::create(['webhook_type' => 'monnify', 'body' => json_encode($request->all()), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

        $stringToEncrypt = config('monnify.secret_key_live') . "|$request->paymentReference|$request->amountPaid|$request->paidOn|$request->transactionReference";

        $hashed = hash("sha512", $stringToEncrypt);

        if (strtolower($request->transactionHash) != $hashed) {
            return response()->json(['status' => false, 'message' => "Failed to verify Transaction Hash"], 400);
        }

        $monnifyController = new MonnifyController();

        //Login to monnify
        $loginMonnify = $monnifyController->login();
        if (! $loginMonnify['status']) {
            return response()->json($loginMonnify, 400);
        }

        //Verify Transaction status from monnify
        $transactionStatus = $monnifyController->getTransactionStatus($request->transactionReference);
        if ($transactionStatus['status'] == "00") {
            $transactionStatus['status'] = false;
            return response()->json($transactionStatus, 400);
        }

        //Check if transaction already exists on our database
        $checkTransaction = $this->checkTransactionExists($request->transactionReference);
        if (! $checkTransaction['status']) {

            //Check if the transaction was already handles previously
            $currentTransaction = $checkTransaction['data']['transaction'];
            if ($currentTransaction->transaction_remarks_id == 2) {
                return response()->json(['status' => false, 'message' => "Transaction has already been handled"], 400);
            }
        }

        $account = wb_account_details::where('acc_reference', $request->product['reference'])->first();

        // if (!$account) {
        //     return response()->json(['status' => false, 'message' => "User Account does not exist"], 400);
        // }

        try {

            if ($account->acc_reference == $request->product['reference']) {

                $customer  = $account->user;
                $companyId = $account->user->company->id;
                $branchId  = $account->user->branch->id;
                $accountId = $account->id;

                $customerAmount = $transactionStatus['data']['amount_paid'];

                $originalAmount = $transactionStatus['data']['amount_paid'];

                $customerCharge = 0;

                // Get Monnify WBA Charges
                // Get amount to be deducted from company
                $companyCharge = getWbaTransferCharges(10, $originalAmount);

                $companySettings = company_settings::where('company_id', $companyId)->first();

                if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                    if ($companySettings->incoming_transfer_charge_state == 0) {
                        $customerCharge = $companySettings->incoming_transfer_charge_amount;
                        $customerAmount -= $customerCharge;
                    } else {
                        $customerCharge = $companySettings->incoming_transfer_charge_percentage * 0.01 * $transactionStatus['data']['amount_paid'];
                        $customerCharge = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                        $customerAmount -= $customerCharge;
                    }
                }

                $creditCustomer = creditCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);

                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['balance_before'],
                    "amount"                 => $originalAmount,
                    "balance_after"          => $creditCustomer['balance_after'],
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    "company_amount"         => $originalAmount,
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => "You recieved payment from " . $request->accountDetails['accountName'],
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "transaction_reference"  => $request->transactionReference,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 6,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                ]);

                if ($customerCharge > 0) {

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "balance_after"          => $creditCustomer['charge_balance_after'],
                        'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                        "company_amount"         => $companyCharge,
                        'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                        "description"            => "Charge from payment received",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);
                } else {
                    if ($companyCharge > 0) {
                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['company_charge_balance_before'],
                            "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "balance_after"          => $creditCustomer['company_charge_balance_after'],
                            "company_amount"         => $companyCharge,
                            "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "branch_id"              => $branchId,
                            "account_id"             => $accountId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }
                }

                $balanceAfter = $creditCustomer['charge_balance_after'];
                $desc         = "You recieved payment from " . $request->accountDetails['accountName'];
                $currentDate  = Carbon::now();

                $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->transactionReference";

                $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction', '', 0, false);

                return response()->json(['responseCode' => true, 'message' => "Successfully Handled webhook"]);
            } else {

                $companyWallet = company_wallet::where('refaccount', $request->product['reference'])->first();

                if ($companyWallet) {

                    $companyId = $companyWallet->company_id;

                    $originalAmount = $transactionStatus['data']['amount_paid'];

                    $companyCharge = 0;

                    // Get Monnify WBA Charges
                    // Get amount to be deducted from company
                    $companyCharge = getWbaTransferCharges(10, $originalAmount);

                    $creditCustomer = creditCompanyWithCharge($companyId, $originalAmount, $companyCharge);

                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['balance_before'],
                        "company_balance_before" => $creditCustomer['balance_before'],
                        "amount"                 => $transactionStatus['data']['amount_paid'],
                        "company_amount"         => $transactionStatus['data']['amount_paid'],
                        "balance_after"          => $creditCustomer['balance_after'],
                        "company_balance_after"  => $creditCustomer['balance_after'],
                        "description"            => "You recieved payment from " . $request->accountDetails['accountName'],
                        "company_id"             => $companyId,
                        "transaction_reference"  => $request->transactionReference,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 6,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                    ]);

                    if ($companyCharge > 0) {

                        $transaction = transactions::create([
                            "balance_before"         => $creditCustomer['charge_balance_before'],
                            "company_balance_before" => $creditCustomer['charge_balance_before'],
                            "amount"                 => $companyCharge,
                            "balance_after"          => $creditCustomer['charge_balance_after'],
                            "company_amount"         => $companyCharge,
                            "company_balance_after"  => $creditCustomer['charge_balance_after'],
                            "description"            => "Charge from payment received",
                            "company_id"             => $companyId,
                            "parent_transaction_id"  => $transaction->id,
                            "transaction_reference"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 27,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => 1,
                        ]);
                    }

                    $balanceAfter = $creditCustomer['charge_balance_after'];
                    $desc         = "You recieved payment from " . $request->accountDetails['accountName'];
                    $currentDate  = Carbon::now();

                    $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->transactionReference";

                    // $smsstate = sendSMS($customer->id, $message, true, true, 'Credit Transaction');

                    return response()->json(['responseCode' => "00", "requestSuccessful" => true, 'transaction_reference' => $request->transactionReference, 'responseMessage' => "success"]);
                } else {
                    return response()->json(['responseCode' => "02", "requestSuccessful" => true, 'transaction_reference' => $request->transactionReference, 'responseMessage' => "rejected transaction"], 400);
                }
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "laravel error");
            return response()->json(['status' => "01", 'message' => "Failed to Complete webhook. contact admin"], 400);
        }
    }

    public function flutterwaveWebhook(Request $request)
    {

        $transactionId = generateTransactionReference();

        // If you specified a secret hash, check for the signature
        $secretHash = env('APP_MODE') == 'live' ? env('FLUTTERWAVE_SECRET_HASH_LIVE') : env('FLUTTERWAVE_SECRET_HASH_TEST');
        $signature  = $request->header('verif-hash');
        if (! $signature || ($signature !== $secretHash)) {
            // This request isn't from Flutterwave; discard
            abort(401);
        }
        $payload = $request->all();

        if (isset($request->Type)) {

            $card = UserCard::where('card_reference', $request->CardId)->first();

            if ($card) {
                $phone = $card->owner->phone;

                if ($request->Type == "Notficiation") {
                    sendSMS($card->owner->user_id, "Your Transaction OTP is $request->Otp, Please do not share this OTP with anyone.", true, true, "Transaction OTP", '', 0, false);
                } else if ($request->Type == "Debit") {
                    sendSMS($card->owner->user_id, "Debit Amt:$request->Amount Desc: Virtual card Debit Avail Bal:$request->Balance Date:" . Date('Y-m-d') . " Ref:$request->TransactionId", false, true, 'Debit Transaction', '', 0, false);
                }
            }
        }

        return response(200);
    }

    public function checkDuplicateTransaction($transactionReference)
    {
        $transactionStatus = transactions::where(['transaction_reference' => $transactionReference, 'transaction_remarks_id' => 2])->first();

        if ($transactionStatus) {
            return response()->json(['status' => false, 'message' => "duplicate transaction"]);
        } else {
            return response()->json(['status' => true, 'message' => "No duplicate transaction found"]);
        }
    }

    public function getAllTransactions(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "start_date" => 'date',
            "end_date"   => 'date',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $transactions = transactions::where(['company_id' => $checkApiKey->company_id]);

        if ($request->has('start_date')) {
            $transactions = $transactions->whereDate('created_at', '>=', date_format(date_create($request->start_date), 'Y-m-d'));
        }

        if ($request->has('end_date')) {
            $transactions = $transactions->whereDate('created_at', '<=', date_format(date_create($request->end_date), 'Y-m-d'));
        }
        $transactions = $transactions->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function getUserTransactions(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "start_date"       => 'date',
            "end_date"         => 'date',
            "virtual_account"  => 'required|numeric|digits:10',
            "transaction_type" => 'required|string|in:credit,debit',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $user = User::where(['virtual_account' => $request->virtual_account, 'company_id' => $checkApiKey->company_id])->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Invalid Virtual Account passed']);
        }

        $transactions = transactions::where(['account_id' => $user->accounts[0]->id]);

        if ($request->has('start_date')) {
            $transactions = $transactions->whereDate('created_at', '>=', date_format(date_create($request->start_date), 'Y-m-d'));
        }

        if ($request->has('end_date')) {
            $transactions = $transactions->whereDate('created_at', '<=', date_format(date_create($request->end_date), 'Y-m-d'));
        }
        $transactions = $transactions->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function checkTransactionExists($transactionReference)
    {
        $transactionStatus = transactions::where(['transaction_reference' => $transactionReference])->first();

        if ($transactionStatus) {
            return ['status' => false, 'message' => "Transaction already exists", 'data' => ['transaction' => $transactionStatus]];
        } else {
            return ['status' => true, 'message' => "No transaction found"];
        }
    }

    public function getExchangeRates(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "countryCurrency" => 'required',
            "exchangeAmount"  => 'required|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Currency Code and Exchange amount is required"];
            return response()->json($ra, 406);
        }

        try {

            $destinationCurrency = $request->countryCurrency;
            $sourceCurrency      = $request->sourceCurrency;
            // $sourceCurrency = $request->user()->company->currency->code;
            $exchangeAmount = $request->exchangeAmount;

            $flutterwaveController = new FlutterwaveController();

            $exhangeRate = $flutterwaveController->getExchangeRate($exchangeAmount, $sourceCurrency, $destinationCurrency);

            if (! $exhangeRate['status']) {
                return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
            }

            $rate   = $exhangeRate["data"]["rate"];
            $amount = $exhangeRate["data"]["final_amount"];

            $finalAmount = $amount;

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Exchange rate received",
                    'data'    => [
                        'rate'         => $rate,
                        'amount'       => $amount,
                        'final_amount' => $finalAmount,
                    ],
                ]
            );
        } catch (Exception $e) {
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }

    public function checkCustomerBalance($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->accounts->first()->id, 'balance' => $userWallet->balance];
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function checkCustomerAndCompanyBalance($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                $companyWallet = company_wallet::where('company_id', \auth()->user()->company_id)->first();

                if ($companyWallet->balance >= $amount) {

                    $response = [
                        "status"          => true,
                        'message'         => "Amount is Valid",
                        'account_id'      => auth()->user()->accounts->first()->id,
                        'balance'         => $userWallet->balance,
                        'company_balance' => $companyWallet->balance,
                    ];
                } else {
                    $response = ["status" => false, 'message' => "Switcher Error... Please contact support $companyWallet->balance $amount"];
                }
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function validateTransactionPin($transactionPin)
    {
        $response = [];

        $userWallet = User::where(['id' => auth()->user()->id, 'pin' => $transactionPin])->first();

        if ($userWallet) {
            $response = ["status" => true, 'message' => "Pin is Valid"];
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction pin"];
        }

        return $response;
    }

    public function getCustomerBalance()
    {

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

        return $userWallet->balance;
    }

    public function randomNumber($length)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(1, 9);
        }

        return $result;
    }

    public function vtPassRequestId()
    {
        $result     = '';
        $dateString = \str_replace("-", "", Carbon::now()->format('Y-m-d H:i:s'));
        $dateString = \str_replace(" ", "", $dateString);
        $dateString = \str_replace(":", "", $dateString);
        // return $dateString . $this->randomNumber(8);
        return $dateString;
    }

    public function updateTransactionAsSuccessful($transactionId, $balance = 0, $updateBalance = true)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function updateCustomerAndCompanyTransactionAsSuccessful($transactionId, $customerBalance, $companyBalance, $updateBalance = true)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after         = $customerBalance;
            $transaction->company_balance_after = $companyBalance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function debitCustomer($request)
    {

        $amount = $request->final_amount;

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                if ($request->user()->company->wallet->balance >= $amount) {

                    $userWallet->balance -= $amount;
                    $userWallet->updated_at = Carbon::now();
                    $userWallet->save();

                    $request->user()->company->wallet->balance -= $amount;
                    $request->user()->company->wallet->updated_at = Carbon::now();
                    $request->user()->company->wallet->save();

                    $response = ["status" => true, 'message' => "Customer Debited", 'balance' => $userWallet->balance];
                } else {
                    $response = ["status" => false, 'message' => "Switcher Error... Please contact support for assistance"];
                }
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function validateTransfer($amount)
    {

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        if ($companySettings->max_onlinetransfer_amount < $amount) {
            return ["status" => false, 'message' => "Maximum transaction amount exceeded $companySettings->max_onlinetransfer_amount $amount"];
        }

        $wbAccount = auth()->user()->accounts->first();

        $userWallet = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        if ($amountLeft < $companySettings->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');

        if (($sum + $amount) > $wbAccount->accountType->daily_withdraw_limit_count) {
            return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
        }

        return ["status" => \true, 'message' => "Transaction Validated"];
    }

    public function buyAirtime(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required|string",
            "transaction_pin"  => "required|numeric|gt:0|digits:4",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        if ($request->final_amount > 50000) {
            return \response()->json(['status' => false, 'message' => 'Airtime purchase cannot be greater 50,000']);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $accountId = auth()->user()->accounts->first()->id;

        $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0, 0.01);

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $request->final_amount,
            "account_id"             => $accountId,
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_id"             => auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "account_id"             => $transactionPinValidation['account_id'],
            "description"            => "Purchase of Airtime worth $request->final_amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 11,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $cashbackTransactionId = \generate_txnID();

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['cashback_balance_before'],
            "amount"                 => $debitCustomer["customer_cashback"],
            "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $accountId : null,
            "balance_after"          => $debitCustomer["cashback_balance_after"],
            "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
            "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
            "company_amount"         => $debitCustomer["company_cashback"],
            "description"            => "Cashback on Airtime Purcahse",
            "branch_id"              => auth()->user()->branch_id,
            "company_id"             => auth()->user()->company_id,
            "transaction_reference"  => $cashbackTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 66,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $vtPassController = new VTPassController();

        $networkProvider = $request->network_provider == '9mobile' ? 'etisalat' : $request->network_provider;

        $buyAirtime = $vtPassController->buyAirtime($transactionId, $networkProvider, $request->final_amount, $request->phone_number);

        if ($buyAirtime["status"]) {

            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer["balance_after"]);

            return response()->json(
                [
                    "status"  => true,
                    'message' => "Airtime Purchase Successful",
                    'data'    => [
                        'balance' => $debitCustomer['balance_after'],
                    ],
                ]
            );
        } else {
            return response()->json(["status" => false, 'message' => "Failed to purchase airtime. Kindly contact admin for assistance if you have been debited", 'data' => $transactionId], 400);
        }
    }

    public function buyDataBundle(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required",
            "data_plan"        => "required",
            "transaction_pin"  => "required|numeric|gt:0|digits:4",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, 1);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $dataBundles = $this->getDataBundlesList($request->network_provider);

        if (! $dataBundles['status']) {
            return response()->json(["status" => false, 'message' => "Failed to get Data bundles"], 400);
        }

        $isDataPlanValid = false;

        $selectedDataBundle = [];

        foreach ($dataBundles['data'] as $value) {
            if ($value['variation_code'] == $request->data_plan) {
                $selectedDataBundle = $value;
                $isDataPlanValid    = true;
            }
        }
        if (! $isDataPlanValid) {
            return response()->json(["status" => false, 'message' => "Invalid Data bundle"], 400);
        }

        $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0, 0.01);

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $selectedDataBundle['variation_amount'],
            "balance_after"          => $debitCustomer["balance_after"],
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_id"             => auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "account_id"             => $transactionPinValidation['account_id'],
            "description"            => "Purchase of Data worth " . $selectedDataBundle['variation_amount'],
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 12,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $cashbackTransactionId = \generate_txnID();

        $cashbackTransaction = transactions::create([
            "balance_before"         => $debitCustomer['cashback_balance_before'],
            "amount"                 => $debitCustomer["customer_cashback"],
            "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $transactionPinValidation['account_id'] : null,
            "balance_after"          => $debitCustomer["cashback_balance_after"],
            "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
            "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
            "company_amount"         => $debitCustomer["company_cashback"],
            "description"            => "Cashback on Data Purchase",
            "company_id"             => auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "parent_transaction_id"  => $transaction->id,
            "transaction_reference"  => $cashbackTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 66,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->network_provider,
            "variation_code" => $request->data_plan,
            "amount"         => $selectedDataBundle['variation_amount'],
            "phone"          => $request->phone_number,
        ];

        $endpoint = "pay";

        $vtPassController = new VTPassController();

        $buyAirtime = $vtPassController->buyData($transactionId, $request->network_provider, $request->data_plan, $selectedDataBundle['variation_amount'], $request->phone_number);

        if ($buyAirtime["status"]) {

            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after']);

            return response()->json(
                [
                    "status"  => true,
                    'message' => "Data Plan Purchase Successful",
                    'data'    => [
                        'balance' => $debitCustomer['balance_after'],
                    ],
                ]
            );
        } else {
            return response()->json(["status" => false, 'message' => $buyAirtime['message']], 400);
        }
    }

    public function buyElectricity(Request $request)
    {

        // $transactionId = $this->randomNumber(12);
        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"      => "required|numeric|gt:0",
            "phone_number"      => "required|numeric|gt:0",
            "meter_number"      => "required|numeric|gt:0",
            "electricity_disco" => "required",
            "meter_type"        => "required",
            "transaction_pin"   => "required|numeric|gt:0|digits:4",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = WbaCompanyCharge::find(1);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge->electricity_charge);

        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $request->final_amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Purchase of Electricity worth $request->final_amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 14,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $transactionChargeId = \generate_txnID();

        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge->electricity_charge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Electricity Purchase",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->electricity_disco,
            "variation_code" => $request->meter_type,
            "billersCode"    => $request->meter_number,
            "amount"         => $request->final_amount,
            "phone"          => $request->phone_number,
        ];

        $endpoint = "pay";

        $buyElectricity = \vtpassPostRequest($endpoint, $body);

        if (isset($buyElectricity)) {
            if ($buyElectricity["code"] == "000") {

                $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after']);

                if ($buyElectricity['content']['transactions']['status'] == 'pending') {
                    return response()->json(
                        [
                            "status"  => true,
                            'message' => "Transaction is Processing. you will receive a token shortly",
                        ]
                    );
                }

                $returnTypeResponse = $request->meter_type == 'prepaid' ? [
                    'balance'         => $debitCustomer['balance_after'],
                    'token'           => $buyElectricity['Token'],
                    'purchased_units' => $buyElectricity['units'],
                ] : [
                    'balance' => $debitCustomer['balance_after'],
                ];

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Electricity Unit Purchase Successful",
                        'data'    => $returnTypeResponse,
                    ]
                );
            } else {
                return response()->json(["status" => false, 'message' => $buyElectricity['response_description']], 400);
            }
        } else {
            return response()->json(["status" => false, 'message' => "Failed to Purchase Electricity"], 400);
        }
    }

    public function subscribeCableTV(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"      => "required|numeric|gt:0",
            "phone_number"      => "required|numeric|gt:0",
            "smart_card_number" => "required|numeric|gt:0",
            "subscription_plan" => "required|string",
            "cable_tv_type"     => "required|string",
            "transaction_pin"   => "required|numeric|gt:0|digits:4",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = WbaCompanyCharge::find(1);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge->electricity_charge);

        $transaction = transactions::create([

            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $request->final_amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Cable TV Subscription worth $request->final_amount",
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 13,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $transactionChargeId = \generate_txnID();

        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge->electricity_charge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Cable TV Subscription",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $transactionId,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
        ]);

        $body = [
            "request_id"        => $transactionId,
            "serviceID"         => $request->cable_tv_type,
            "variation_code"    => $request->subscription_plan,
            "billersCode"       => $request->smart_card_number,
            "subscription_type" => "change",
            "amount"            => $request->final_amount,
            "phone"             => $request->phone_number,
        ];

        $endpoint = "pay";

        $subscribeCableTV = \vtpassPostRequest($endpoint, $body);

        if (isset($subscribeCableTV["code"])) {
            if ($subscribeCableTV["code"] == "000") {

                $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_before'], false);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Cable TV Purchase Successful",
                        'data'    => [
                            'balance' => $debitCustomer['charge_balance_after'],
                        ],
                    ]
                );
            } else {
                return response()->json(["status" => false, 'message' => $subscribeCableTV['response_description']], 400);
            }
        } else {
            return response()->json(["status" => false, 'message' => "Failed to subscribe cable TV"], 400);
        }
    }

    public function verifyBVN(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bvn" => 'required|numeric|gt:0',
            "dob" => 'required',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "BVN and Date of birth is required"];
            return response()->json($ra, 406);
        }

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'prod_sk_opOycQfcwBrqTrBLovXN4jFyR',
            'AppId'         => '61d0b13d49c296003276f53b',
        ];

        $url = "https://api.dojah.io/api/v2/kyc/bvn/advance?bvn=$request->bvn";

        $time        = strtotime($request->dob);
        $bvnResponse = \getRequest($header, [], $url);

        if (isset($bvnResponse['entity']['bvn'])) {
            $firstName       = $bvnResponse["entity"]["first_name"];
            $lastName        = $bvnResponse["entity"]["last_name"];
            $dob             = $bvnResponse["entity"]["date_of_birth"];
            $bvnFromResponse = $bvnResponse["entity"]["bvn"];
            $phone           = $bvnResponse["entity"]["phone_number1"];

            $newformat = date('Y-m-d', $time);

            $bvnDob = strtotime($dob);

            if ($time == $bvnDob) {

                return response()->json(
                    [
                        'status'  => true,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'first_name' => trim($firstName),
                            "last_name"  => trim($lastName),
                            "dob"        => $newformat,
                            "bvn"        => trim($bvnFromResponse),
                            "phone"      => trim($phone),
                        ],
                    ]
                );
            } else {

                return response()->json(
                    [
                        'status'  => false,
                        'message' => "Failed to verify BVN with Date of birth",
                        'data'    => $request->bvn,
                    ],
                    400
                );
            }
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify BVN",
                ],
                400
            );
        }
    }

    public function verifyMeterNumber(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "meter_number"      => 'required',
            "electricity_disco" => 'required',
            "type"              => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $body = [
            "billersCode" => $request->meter_number,
            "serviceID"   => $request->electricity_disco,
            "type"        => $request->type,
        ];

        $endpoint = "merchant-verify";

        $meterResponse = \vtpassPostRequest($endpoint, $body);

        if (isset($meterResponse["code"])) {
            if ($meterResponse["code"] == "000") {

                if (isset($meterResponse['content']['error'])) {

                    return response()->json(
                        [
                            'status'  => false,
                            'message' => $meterResponse['content']['error'],
                        ],
                        400
                    );
                }

                return response()->json(
                    [
                        'status'  => true,
                        'message' => "Meter Verified Successfully",
                        'data'    => [
                            'meter_name'   => trim($meterResponse['content']['Customer_Name']),
                            'address'      => trim($meterResponse['content']['Address']),
                            'meter_type'   => trim($request->type),
                            'meter_number' => $request->electricity_disco == "abuja-electric" ? trim($meterResponse['content']['MeterNumber']) : trim($meterResponse['content']['Meter_Number']),
                        ],
                    ]
                );
            } else {

                return response()->json(
                    [
                        'status'  => false,
                        'message' => "Failed to verify meter number",
                    ],
                    400
                );
            }
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify meter number. Please contact support",
                ],
                400
            );
        }
    }

    public function verifySmartCard(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "smart_card_number" => 'required|numeric|gt:0',
            "cable_tv_type"     => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $body = [
            "billersCode" => $request->smart_card_number,
            "serviceID"   => $request->cable_tv_type,
        ];

        $endpoint = "merchant-verify";

        $meterResponse = \vtpassPostRequest($endpoint, $body);

        if (isset($meterResponse["code"])) {
            if ($meterResponse["code"] == "000") {

                if (isset($meterResponse['content']['error'])) {
                    return response()->json(
                        [
                            'status'  => false,
                            'message' => $meterResponse['content']['error'],
                        ],
                        400
                    );
                }

                return response()->json(
                    [
                        'status'  => true,
                        'message' => "Cable TV Verified Successfully",
                        'data'    => [
                            'customer_name'        => trim($meterResponse['content']['Customer_Name']),
                            'cable_tv'             => trim($meterResponse['content']['Customer_Type']),
                            'current_bouquet'      => trim($meterResponse['content']['Current_Bouquet']),
                            'current_bouquet_code' => trim($meterResponse['content']['Current_Bouquet_Code']),
                            'renewal_amount'       => trim($meterResponse['content']['Renewal_Amount']),
                        ],
                    ]
                );
            } else {

                return response()->json(
                    [
                        'status'  => false,
                        'message' => "Failed to verify meter number",
                    ],
                    400
                );
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to verify smart card. Please contact support'], 400);
        }
    }

    public function getAllBanks(Request $request)
    {
        $type = $request->has('type') ? $request->type : 'long';

        $banks = \getBanksFromDB($type);

        return response()->json(['status' => true, 'message' => 'Banks fetched successfully', 'data' => $banks]);
    }

    public function transactionHistory(Request $request)
    {

        // return response()->json($request->all());

        $pageLimit = isset($request->limit) ? $request->limit : 10;

        // $userWallet = user_wallet::where('wb_account_detail_id', $request->user()->accounts->first()->id)->first();

        $transactions = transactions::where(['account_id' => $request->user()->accounts->first()->id])->orderBy('created_at', 'DESC')->paginate($pageLimit);

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function getDataBundles($networkProvider)
    {
        $endpoint = "service-variations?serviceID=$networkProvider";

        $dataBundles = \vtpassGetRequest($endpoint);

        if ($dataBundles['response_description'] == "000") {
            return response()->json(['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['content']['varations']]);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []], 400);
        }
    }

    public function getDataBundlesList($networkProvider)
    {
        $vtPassController = new VTPassController();
        $dataBundles      = $vtPassController->getDataBundles($networkProvider);

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function getSubscriptions(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "cableTvType" => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $cableTV = $request['cableTvType'];

        $endpoint = "service-variations?serviceID=$cableTV";

        $dataBundles = \vtpassGetRequest($endpoint);

        if ($dataBundles['response_description'] == "000") {
            return response()->json(['status' => true, 'message' => 'Cable TV Subscriptions fetched successfully', 'data' => $dataBundles['content']['varations']]);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to fetch Cable Subscriptions', 'data' => []], 400);
        }
    }

    public function getBettingCompanies(Request $request)
    {

        $cableTV = $request['cableTvType'];

        $endpoint = "betting";

        // return response()->json(['status' => false, 'message' => 'This service is unavailable at the moment'], 400);

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $bettings = \getRequest([], [], "https://partner.com/bettings/get_subscriptions.php?is_demo=$isDemo&api_exchange_key=$apiExchangeKey");

        if ($bettings['success']) {

            $providers = [];

            foreach ($bettings['data'] as $key => $bettingCompany) {
                $providers[] = ['providers' => $bettingCompany['provider']];
            }

            return response()->json(['status' => true, 'message' => 'Bettings fetched Successfuly', 'data' => $providers]);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to fetch bettings', 'data' => $bettings], 400);
        }
    }

    public function forwardToWebhookUrlAndSweepAccount($incomingRequest, $companyId)
    {

        $sweepAccount = false;

        try {

            $checkWebhook = ApiKey::where('company_id', $companyId)->first();

            if ($checkWebhook) {
                if ($checkWebhook->send_notification == 1) {
                    $sweepAccount = true;

                    $body = [
                        "account_number"        => $incomingRequest['nuban'],
                        "amount"                => $incomingRequest['amount'],
                        "transaction_reference" => $incomingRequest['account_reference'],
                        "narration"             => "Funds received from " . $incomingRequest['source_account_name'],
                        "status"                => $incomingRequest['status'],
                        "transaction_date"      => $incomingRequest['transaction_time'],
                    ];

                    $header = [
                        'Content-Type' => 'application/json',
                        'ApiKey'       => $checkWebhook->api_key,
                    ];
                    $response = Http::withHeaders($header)->post($checkWebhook->webhook_url, $body);
                } else {
                }
            } else {
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Forward Webhook Error");
        }
        return $sweepAccount;
    }

    public function verifyBankAccountNumber($bankCode, $accountNumber)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.woven.finance/v2/api/nuban/enquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => "account_number=$accountNumber&bank_code=$bankCode",
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'api_secret:  your key',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }
    public function getMeterType()
    {

        return json_encode(
            [
                ["id" => "prepaid", "name" => "Prepaid"],
                ["id" => "postpaid", "name" => "Postpaid"],
            ]
        );
    }
    public function getMeterService()
    {
        return json_encode(
            [
                ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"],

            ]
        );
    }
    public function getMeterServiceType()
    {

        return response()->json(
            [
                'status'  => true,
                'message' =>
                'Response Successful',
                'data'    => [
                    'service' => [
                        ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"],

                    ], 'type' => [
                        ["id" => "prepaid", "name" => "Prepaid"],
                        ["id" => "postpaid", "name" => "Postpaid"],
                    ],

                ],
            ]
        );
    }
    public function getNetworks()
    {
        $network = [
            'network' => [
                ["id" => "mtn", "name" => "MTN"],
                ["id" => "glo", "name" => "GLO"],
                ["id" => "airtel", "name" => "Airtel"],

                ["id" => "etisalat", "name" => "9mobile"],
            ],
            'data'    => [
                "mtn"      => [
                    ["id" => "500", "name" => "500mb/30days - 350.00", "amount" => 350],
                    ["id" => "1000", "name" => "1.0GB/30days - 550.00", "amount" => 550],
                    ["id" => "2000", "name" => "2.0GB/30days - 750.00", "amount" => 750],
                    ["id" => "5000", "name" => "5.0GB/30days - 2,700.00", "amount" => 2700],
                    ["id" => "10000.01", "name" => "10.0GB/30days - 5,000.00", "amount" => 5000],
                ],
                "glo"      => [

                    ["id" => "1050.01", "name" => "1GB for 30Days - 1,100.00", "amount" => 1100],
                    ["id" => "2500.01", "name" => "2.5GB for 30Days - 1,500.00", "amount" => 1500],

                    ["id" => "5800.01", "name" => "5.8GB for 30Days - 2,500.00", "amount" => 2500],
                    ["id" => "10000.01", "name" => "10GB for 30Days - 3,500.00", "amount" => 3500],
                ],
                "airtel"   => [
                    ["id" => "999.00", "name" => "1.5GB/30days - 1,200.00", "amount" => 1200],
                    ["id" => "1499.01", "name" => "3.5GB/30days - 1,650.00", "amount" => 1650],
                    ["id" => "1999.00", "name" => "4.5GB/30days - 2,050.00 ", "amount" => 2050],

                    ["id" => "2499.01", "name" => "6.0GB/30days - 2,499.01", "amount" => 2550],
                    ["id" => "2999.02", "name" => "8.0GB/30days - 3,500", "amount" => 3500],

                ],
                "etisalat" => [
                    ["id" => "500.01", "name" => "500MB/30days - 550", "amount" => 550],
                    ["id" => "1500.01", "name" => "1.5GB/30days - 1,500", "amount" => 1500],
                    ["id" => "4500.01", "name" => "4.5GB/30days - 2,500", "amount" => 2500],

                    ["id" => "15000.01", "name" => "15GB/30days - 7,000", "amount" => 7000],

                    ["id" => "40000.01", "name" => "40.0GB/30days - 14,500", "amount" => 14500],
                ],
            ],
        ];
        return response()->json($network);
    }
    public function txnInfo(Request $request)
    {
        $transaction = transactions::whereHas('beneficiary', function ($q) {
            $q->where(['user_id' => auth()->user()->id]);
        })->where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->with('beneficiary')->first();
        if ($transaction) {
            $transaction->id                     = 0;
            $transaction->account_id             = 0;
            $transaction->account_id             = 0;
            $transaction->branch_id              = null;
            $transaction->parent_transaction_id  = null;
            $transaction->company_id             = null;
            $transaction->company_balance_before = null;
            $transaction->company_amount         = null;
            $transaction->company_balance_after  = null;
            $transaction->transaction_type_id    = null;
            $transaction->created_by             = null;
            $transaction->modified_by            = null;
            return response()->json(['status' => true, 'message' => 'Transaction details fetched successfully', 'data' => $transaction], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid transaction reference'], 400);
        }
    }
}
