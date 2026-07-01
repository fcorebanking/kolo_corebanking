<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\AmuchaController;
use App\Http\Controllers\ThirdParty\AssetMatrixController;
use App\Http\Controllers\ThirdParty\BaniController;
use App\Http\Controllers\ThirdParty\BridgeController;
use App\Http\Controllers\ThirdParty\CgateController;
use App\Http\Controllers\ThirdParty\CyberpayControllelr;
use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Http\Controllers\ThirdParty\FincraController;
use App\Http\Controllers\ThirdParty\GTBController;
use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\KudaBusinessController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\MapleradController;
use App\Http\Controllers\ThirdParty\MonnifyController;
use App\Http\Controllers\ThirdParty\NeonDataApiController;
use App\Http\Controllers\ThirdParty\NinePSBController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\StroWalletController;
use App\Http\Controllers\ThirdParty\TryluxController;
use App\Http\Controllers\ThirdParty\Trylux\TryluxPayload;
use App\Http\Controllers\ThirdParty\TwilioWhatsapp;
use App\Http\Controllers\ThirdParty\VPayController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Http\Resources\BenefeciariesResource;
use App\Mail\TransactionReport;
use App\Models\AgentTerminal;
use App\Models\ApiKey;
use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\CardRequestLog;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\GtbWebhook;
use App\Models\loan;
use App\Models\Mandate;
use App\Models\MandateLog;
use App\Models\PreferredEbankingPartner;
use App\Models\PreferredEbankingPartnerCompany;
use App\Models\savings_product;
use App\Models\SpendSave;
use App\Models\transactions;
use App\Models\transaction_remarks;
use App\Models\UnusedProvidusAccounts;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\wb_account_details;
use App\Models\WebhookLog;
use App\Models\WovenWebhook;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingCompanyService;
use App\Services\ebanking\EbankingService;
use App\Services\fortifyTransactionService;
use App\Services\PaystackService;
use App\Services\RemittaController;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        if (! $bankAccount['status']) {
            return response()->json($bankAccount, 400);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank      = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bank_name = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $accountName = $bankAccount["data"]["account_name"];

        $myAccountName = [];

        $myAccountName = explode(" ", $accountName);

        $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];
        $lastName  = count($myAccountName) > 2 ? (strlen($myAccountName[2]) < 3 ? $myAccountName[1] : $myAccountName[2]) : (count($myAccountName) > 1 ? $myAccountName[1] : "");

        return response()->json(
            [
                'status'  => true,
                'message' => "Account Verified Successfully",
                'data'    => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'bank_name'  => $bank_name,

                ],
            ]
        );
    }

    public function verifyBankAccountTest(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bank_code"      => 'required',
            "account_number" => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Bank code and account number is required"];
            return response()->json($ra, 406);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        \logInfo(json_encode($preferredEbankingPartner), "preferredEbankingPartner");

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        \logInfo(json_encode($bankAccount), "bankAccount");

        if (! $bankAccount['status']) {
            return response()->json($bankAccount, 400);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank      = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bank_name = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $accountName = $bankAccount["data"]["account_name"];

        $myAccountName = [];

        $myAccountName = explode(" ", $accountName);

        $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];
        $lastName  = count($myAccountName) > 2 ? (strlen($myAccountName[2]) < 3 ? $myAccountName[1] : $myAccountName[2]) : (count($myAccountName) > 1 ? $myAccountName[1] : "");

        return response()->json(
            [
                'status'  => true,
                'message' => "Account Verified Successfully",
                'data'    => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'bank_name'  => $bank_name,

                ],
            ]
        );
    }

    public function verifyBankAccountFull(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                "bank_code"      => 'required',
                "account_number" => 'required|numeric|gt:0',
                "ApiKey"         => 'required',
            ]);
            $header = $request->header('ApiKey');
            if ($validation->fails()) {
                $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
                return response()->json($ra, 406);
            }
            if ($request->ApiKey != "enter key") {
                $ra = ["status" => false, "message" => "Invalid API key provided"];
                return response()->json($ra, 400);
            }

            $bank = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();

            if (! $bank) {
                return response()->json(["status" => 0, 'message' => "Invalid bank code"], 400);
            }

            $preferredEbankingPartner = PreferredEbankingPartner::all();

            $ebankingService                 = new EbankingService($preferredEbankingPartner);
            $bankTransferType                = new BankTransferType();
            $bankTransferType->bankCode      = $request->bank_code;
            $bankTransferType->shortBankCode = $bank->short_code;

            return $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
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

    public function creditDebitCustomerAccount(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "type"           => 'required|in:credit,debit',
            "account_number" => 'required|numeric|gt:0',
            "amount"         => 'required|numeric|gt:0',
            "description"    => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $companyWallet = company_wallet::where('company_id', $checkApiKey->company_id)->first();

        $userAccount = wb_account_details::where('virtual_accnum', $request->account_number)->first();

        if (! $userAccount) {
            return response()->json(['status' => false, 'message' => 'Invalid account number'], 400);
        }

        $erroMessage = $request->type == "debit" ? "Cannot Debit this customer account" : "Cannot Credit this customer account";

        if ($checkApiKey->company_id != $userAccount->user->company_id) {
            return response()->json(['status' => false, 'message' => $erroMessage], 400);
        }

        $customerBalance = $request->type == "debit" ? debitCustomerOnly($userAccount->id, $request->amount, $userAccount->user) : creditCustomerOnly($userAccount->id, $request->amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        $transactionType = $request->type == 'credit' ? 109 : 110;

        $transactionId = generate_txnID();

        $transaction = transactions::create([
            "branch_id"              => $userAccount->user->branch_id,
            "account_id"             => $userAccount->id,
            "description"            => $request->description,
            "balance_before"         => $customerBalance['balance_before'],
            "balance_after"          => $customerBalance['balance_after'],
            "amount"                 => $request->amount,
            "transaction_reference"  => $transactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => $transactionType,
            "credit_debit_type_id"   => $request->type == "debit" ? 2 : 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $userAccount->user_id,
        ]);

        $message = $request->type == "debit" ? "Customer Debited Successfully" : "Customer Credited Successfully";

        if ($companyWallet) {
            return response()->json([
                'status'  => true,
                'message' => $message,
                'data'    => [
                    'balance'        => $customerBalance['balance_after'],
                    'transaction_id' => $transactionId,
                ],
            ]);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to completed transaction'], 400);
        }
    }

    public function companyBankTransfer(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "amount"                => "required|gt:0",
            "destination_account"   => "required",
            "bank_code"             => "required",
            "transaction_reference" => "sometimes|string",
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

            $chargeAmount = $request->amount > 1000000 ? 100 : $chargeAmount;

            // \logInfo($chargeAmount, "charge amount");
        } else {
            $chargeAmount = 0;
        }

        try {

            DB::beginTransaction();

            $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($company->id);

            if ($checkAllowedTransaction['status'] != 1) {
                $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
                return response()->json($checkAllowedTransaction);
            }

            $transactionId = generateTransactionReference();

            if ($request->has('transaction_reference')) {
                $transactionId           = $request->transaction_reference;
                $checkAllowedTransaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

                if ($checkAllowedTransaction) {
                    return response()->json(['status' => false, 'message' => 'Transaction already exists'], 409);
                }
            }
            $adminUser = User::where('company_id', $company->id)->first();

            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(8, $adminUser->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }

            $customerBalance = debitCompanyWithCharge($adminUser->company_id, $request->amount, $chargeAmount, $adminUser);

            if (! $customerBalance['status']) {
                return response()->json($customerBalance, 400);
            }

            $bank = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();

            $myDescription = $adminUser->first_name . " " . $adminUser->last_name . " / " . $adminUser->company->company_name . '/' . $bank->bank_name;

            $transaction = transactions::create([
                "balance_before"         => 0,
                "amount"                 => 0,
                "balance_after"          => 0,
                "description"            => $myDescription,
                "branch_id"              => $adminUser->branch_id,
                "company_id"             => $adminUser->company_id,
                "company_balance_before" => $customerBalance['balance_before'],
                "company_balance_after"  => $customerBalance['charge_balance_after'],
                "company_amount"         => $request->amount + $chargeAmount,
                "company_id"             => $company->id,
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 8,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            DB::commit();

            $preferredEbankingPartner = PreferredEbankingPartner::all();
            $ebankingService          = new EbankingService($preferredEbankingPartner);

            $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->destination_account);

            if (! $bankAccount['status']) {
                $bankAccount['status'] = 0;
                return response()->json($bankAccount);
            }

            $myDescription = $adminUser->first_name . " " . $adminUser->last_name . " / " . $adminUser->company->company_name . " / " . $bankAccount['data']['account_name'] . "/" . $request->destination_account . '/' . $bank->bank_name;

            $transaction->description = $myDescription;
            $transaction->save();

            $bankTransferType                       = new BankTransferType();
            $bankTransferType->bankCode             = $request->bank_code;
            $bankTransferType->shortBankCode        = $bank->short_code;
            $bankTransferType->accountNumber        = $request->destination_account;
            $bankTransferType->description          = isset($request->description) ? $request->description : "BM";
            $bankTransferType->amount               = $request->amount;
            $bankTransferType->transactionReference = $transaction->transaction_reference;

            $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

            // if ($bankTransfer['status'] == "01") {
            //     $reversalResult = debitReversal($transaction->id, $transaction->account_id, $transaction->company_id, $transaction->amount, $bankTransferInfo->charge + $bankTransferInfo->amount, $bankTransferInfo->transfer_entity_type);
            //     $bankAccount['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
            //     $transaction->transaction_remarks_id = 3;
            //     $transaction->save();

            //     logInfo($reversalResult, "Reversal of Transaction Result");

            //     return $bankTransfer;
            // }

            if ($bankTransfer['status'] != "00") {
                $transaction->transaction_remarks_id = 3;
                $transaction->save();
                $bankTransfer['status'] = 0;

                return $bankTransfer;
            }

            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            return response()->json(
                [
                    "status"  => true,
                    'message' => "Wallet to Bank Transfer Successful",
                    'data'    => [
                        'balance_before'        => $customerBalance['balance_before'],
                        'balance'               => $customerBalance['charge_balance_after'],
                        'balance_after'         => $customerBalance['charge_balance_after'],
                        'transaction_reference' => $transactionId,
                    ],
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            logInfo($e->getMessage());
        }
    }

    public function updateKycInfo(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "account_number" => "required",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $company = Company::where('id', $checkApiKey->company_id)->first();

        $userAccount = wb_account_details::where('virtual_accnum', $request->account_number)->first();

        if (! $userAccount) {
            return response()->json(['status' => false, 'message' => 'User account not found'], 400);
        }

        if ($userAccount->user->company_id != $company->id) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to perform this action'], 400);
        }

        $mainUser = $userAccount->user;

        if ($request->has('bvn')) {
            $mainUser->bvn = $request->bvn;
        }

        if ($request->has('kyc_level')) {
            $mainUser->kyc_level_id = $request->kyc_level;
        }

        if ($request->has('nin')) {
            $mainUser->nin = $request->nin;
        }

        $mainUser->save();

        return response()->json(["status" => true, 'message' => "KYC Information updated successfullly"]);
    }

    public function processSafeHavenVerifyAndReverseTransaction(Request $request, $transactionReference)
    {

        if (! $transactionReference) {
            return response()->json(['status' => 0, 'message' => "Transaction reference is required"]);
        }

        $transaction = transactions::where('transaction_reference', $transactionReference)->first();

        if (! $transaction) {
            return response()->json(['status' => 0, 'message' => "Invalid Transaction reference"]);
        }

        if ($transaction->transaction_type_id != 8) {
            return response()->json(['status' => 0, 'message' => "This transaction is not a bank transfer"]);
        }

        $startingDate    = Carbon::parse('2026-04-15 00:00:00');
        $transactionDate = Carbon::parse($transaction->created_at);

        if ($startingDate->gt($transactionDate)) {
            return response()->json(['status' => 0, 'message' => "This transaction cannot be reversed"]);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => 0, 'message' => "Transaction has already been reversed"]);
        }

        $transactions = ['BM2025122821591234654808724CITYAFRICA', 'BM2025123012292382492184995', 'BM2025123012375424851763987', 'BM2025123012394923398215462'];

        $reverseTransactionsMessages = reverseTransactionsMessages($request->transaction_reference);

        if ($transaction->transaction_remarks_id == 1 || $transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);
        }

        if ($transaction->created_at > Carbon::now()->subMinutes(1)) {
            return response()->json(['status' => 0, 'message' => "Transaction must be at least 1 minute before the transaction status can be confirmed"]);
        }

        $affliateCustomerCharge = 0;
        $affliateCompanyCharge  = 0;
        $whoToCredit            = $transaction->account_id == null ? 'company' : 'both';

        $childTransaction = transactions::where('parent_transaction_id', $transaction->id)->first();

        if ($childTransaction) {
            $affliateCustomerCharge = $childTransaction->amount;
            $affliateCompanyCharge  = $childTransaction->company_amount;
        }

        $kudaController = new KudaController();

        $partnerType = "others";

        $verifyTransaction = $kudaController->verifyTransaction($transactionReference);

        if ($verifyTransaction['status'] == "03") {
            $partnerType            = "kuda_business";
            $kudaBusinessController = new KudaBusinessController();
            $verifyTransaction      = $kudaBusinessController->verifyTransaction($transactionReference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType         = "safehaven";
            $safeHavenController = new SafehavenController();
            $verifyTransaction   = $safeHavenController->getTransactionStatus($transactionReference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType = "monnify";

            $monnifyController = new MonnifyController();
            $verifyTransaction = $monnifyController->getTransactionStatus($transactionReference);
        }

        if ($verifyTransaction['status'] == "00") {

            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            $verifyTransaction['status'] = 1;

            return response()->json($verifyTransaction);
        } else {

            logInfo($verifyTransaction, "Verify Transaction Before");
            if ($verifyTransaction['status'] == "02") {

                $verifyTransaction['status'] = 1;

                if ($partnerType == "safehaven" || $partnerType == "others") {
                    $totalCustomerAmount = $transaction->company_amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->company_amount + $affliateCompanyCharge;
                } else if ($partnerType != "safehaven") {
                    $totalCustomerAmount = $transaction->amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->amount + $affliateCompanyCharge;
                }

                debitReversal($transactionReference, $transaction->account_id, $transaction->company_id, $totalCustomerAmount, $totalCompanyAmount, $whoToCredit);
            } else {
                $verifyTransaction['status'] = 0;
            }

            logInfo($verifyTransaction, "Verify Transaction Res");
            return response()->json($verifyTransaction);
        }
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

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        if (! $bankAccount['status']) {
            $bankAccount['status'] = 0;
            return response()->json($bankAccount);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank                             = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bankAccount['data']['bank_name'] = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $bankAccount['status']            = 1;
        $bankAccount['data']['bank_name'] = $bank_name;
        return response()->json($bankAccount);
    }

    public function validateBetAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bet_company"    => 'required',
            "bet_account_id" => 'required',
        ]);

        return response()->json(
            [
                'status'  => true,
                'message' => "This service is not available at the moment",
            ]
        );

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $url = "/validate_bet_account.php?bet_company=$request->bet_company&bet_account=$request->bet_account_id&is_demo=$isDemo&api_exchange_key=$apiExchangeKey";

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

    public function checkUnusedProvidusAccounts(Request $request)
    {

        $providusAccounts = wb_account_details::where(['bankid' => '177', 'is_confirmed' => 0])->take(100)->get();

        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        if ($providusAccounts) {
            foreach ($providusAccounts as $providusAccount) {
                // transactions::where('account_id', $providusAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;
                $fundingTransaction = transactions::where('account_id', $providusAccount->id)->where('transaction_type_id', '=', 6)->first();

                if (! $fundingTransaction) {

                    $oldProvidusAccount = $providusAccount->virtual_accnum;

                    $assetMatrixController = new AssetMatrixController();
                    $assetMatrixAccount    = $assetMatrixController->generateAndVerifyAssetMatrixAccount();

                    logInfo($assetMatrixAccount, "New Account Generated");

                    $providusAccount->virtual_accnum = $assetMatrixAccount['data']['virtual_account'];
                    $providusAccount->acc_reference  = $assetMatrixAccount['data']['account_reference'];
                    $providusAccount->bankid         = 25;

                    $response['data'][] = "Providus Account with Account ID: $providusAccount->id changed from $oldProvidusAccount to $providusAccount->virtual_accnum";

                    UnusedProvidusAccounts::where('account_number', $oldProvidusAccount)->update(
                        [
                            'assigned_status' => 1,
                            'account_number'  => $assetMatrixAccount['data']['virtual_account'],
                        ]
                    );
                }

                $providusAccount->is_confirmed = 1;
                $providusAccount->save();
            }
            logInfo($response, "Providus Account Replacement Log");
        }

        return response()->json($response);
    }

    public function checkAssignedProvidusAccounts(Request $request)
    {

        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        $providusAccounts = UnusedProvidusAccounts::where('assigned_status', '1')->get();

        if ($providusAccounts) {
            foreach ($providusAccounts as $providusAccount) {
                // transactions::where('account_id', $providusAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;
                $fundingTransaction = wb_account_details::where('virtual_accnum', $providusAccount->account_number)->first();

                if (! $fundingTransaction) {
                    $providusAccount->assigned_status = 0;
                    $providusAccount->save();
                } else {
                    $response['data'][] = "Providus Account with $providusAccount->account_number";
                }
            }
        }

        return response()->json($response);
    }

    public function matchingWBAccountDetailsWithUserTable(Request $request)
    {
        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        $assetMatrixAccounts = wb_account_details::select('user_id')->distinct()->where('bankid', '25')->get();

        if ($assetMatrixAccounts) {
            $response['data']['count'] = count($assetMatrixAccounts);
            foreach ($assetMatrixAccounts as $assetMatrixAccount) {
                // transactions::where('account_id', $assetMatrixAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;

                $newAssetMatrix = wb_account_details::where('user_id', $assetMatrixAccount->user_id)->first();

                $userAccount = User::where('id', $newAssetMatrix->user_id)->first();

                $oldAccount = $userAccount->virtual_account;

                $userAccount->virtual_account = $newAssetMatrix->virtual_accnum;
                $userAccount->save();

                // if (!$fundingTransaction) {
                //     $assetMatrixAccount->assigned_status = 0;
                //     $assetMatrixAccount->save();
                // } else {

                $response['data'][] = "Providus Account with old Account: $oldAccount, New AM Account No: $newAssetMatrix->virtual_accnum";

                // }
            }
        }

        logInfo($response, "Providus Account Replacement Log");

        return response()->json($response);
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

        // return response()->json(['status' => false, 'message' => 'This service is not available at the moment'], 400);

        $reference = generateAccountReference();

        $transactionPinValidation = validatePinCheckCustomerBalance(auth()->user()->pin, $request->amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(24, $request->amount);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $debitCustomer   = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->amount, $companySettings->bills_payment_charge, $wbaCharge);
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(52, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
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
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
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
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
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

        $url = "/topup_bet_account.php";

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
        if ($request->account_number == \auth()->user()->virtual_account) {
            return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
        }
        $wbAccount = wb_account_details::where('virtual_accnum', $request->account_number)->orWhere('virtual_accnum', ($request->account_number . "_"))->first();

        if ($wbAccount) {

            // if ($wbAccount->user_id == \auth()->user()->id) {

            //     return response()->json(
            //         [
            //             'status' => false,
            //             'message' => "Cannot Transfer to Self",
            //         ],
            //         409
            //     );
            // }

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name'  => $wbAccount->user->first_name,
                        'last_name'   => $wbAccount->user->last_name,
                        'bank_name'   => $wbAccount->bank->bank_name,
                        'currency_id' => $wbAccount->currency_id ?? 1,
                    ],
                ]
            );
        }

        if ($wbAccount == null) {
            $companyAccount = company_wallet::where('vaccount', $request->account_number)->first();
            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name' => $companyAccount->company->company_name,
                        'last_name'  => "",
                        'bank_name'  => "",

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
        $userId  = auth()->user()->id;
        $lockKey = "user:last_request:{$userId}";
        $lock    = Cache::lock($lockKey, 20); // 10-second lock
        if ($lock->get()) {
            try {
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

                if ($request->bank_code == "090267") {
                    $ra = ["status" => false, "message" => "Service is not available right now"];
                    return response()->json($ra, 406);
                }

                $charge = 0;

                $currentUser = auth()->user();

                $validateSavingsProduct = $this->accountProductCharges($request->final_amount);

                if (! $validateSavingsProduct['status']) {
                    return \response()->json($validateSavingsProduct, 400);
                }

                $charge = isset($validateSavingsProduct['data']['charge']) ? $validateSavingsProduct['data']['charge'] : 0;

                $totalAmount = $request->final_amount + $charge;

                $checkAllowedTransaction = checkCompanyAllowedTransactionStatus($currentUser->company_id);

                if ($checkAllowedTransaction['status'] != 1) {
                    $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
                    return response()->json($checkAllowedTransaction);
                }

                if ($request->final_amount > 3000000) {
                    return response()->json(['status' => false, 'message' => 'Maximum transfer amount is 3,000,000']);
                }
                DB::beginTransaction();

                $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $totalAmount);
                if (! $transactionPinValidation['status']) {
                    return response()->json($transactionPinValidation, 400);
                }

                // $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
                $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $request->transaction_reference])->first();

                if ($transaction) {
                    if ($transaction->transaction_remarks_id == 2) {
                        return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                    } else if ($transaction->transaction_remarks_id == 3) {
                        return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                    } else if ($transaction->transaction_remarks_id == 5) {
                        return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                    } else if ($transaction->transaction_remarks_id == 1) {
                        return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                    } else {

                        $transaction->transaction_remarks_id = 1;
                        $transaction->save();

                        $userWallet = user_wallet::where('wb_account_detail_id', $currentUser->accounts->first()->id)->first();

                        if ($userWallet->balance >= $totalAmount) {

                            if (\checkcustomerrestriction($currentUser->id, '2')) {
                                return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                            }

                            $validateTransferAmount = $this->validateTransfer($request->final_amount);

                            if (! $validateTransferAmount['status']) {
                                return \response()->json($validateTransferAmount, 400);
                            }

                            $wbaCharge = getWbaTransferCharges(1, $request->final_amount);
                            logInfo(json_encode($wbaCharge), "wbaCharge");
                            //       $wbaCharge = $request->final_amount > 1000000 ? 100 : $wbaCharge;
                            logInfo(json_encode($wbaCharge), "wbaCharge > 1 million");
                            $wbaCharge = $request->final_amount > 9999 ? ($wbaCharge + 50) : $wbaCharge + 0;
                            $charge    = $request->final_amount > 9999 ? ($charge + 50) : $charge + 0;
                            logInfo(json_encode($wbaCharge), "wbaCharge stamp duty");
                            logInfo(json_encode($charge), "customer Charge stamp duty");

                            // $charge =  $charge < $wbaCharge && $charge > 0 ?  ($wbaCharge + ($wbaCharge * 0.01)) : $charge;
/*
                    $debitCustomer = debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge);

                    if (! $debitCustomer["status"]) {
                        return \response()->json($debitCustomer, 400);
                    }

                    $accountId                   = $debitCustomer['account_id'];
                    $customerBalanceBefore       = $debitCustomer['balance_before'];
                    $customerBalanceAfter        = $debitCustomer['balance_after'];
                    $customerChargeBalanceBefore = $debitCustomer['charge_balance_before'];
                    $customerChargeBalanceAfter  = $debitCustomer['charge_balance_after'];

                    $companyBalanceBefore       = $debitCustomer['company_balance_before'];
                    $companyAmount              = $debitCustomer['company_amount'];
                    $companyBalanceAfter        = $debitCustomer['company_balance_after'];
                    $companyChargeBalanceBefore = $debitCustomer['company_charge_balance_before'];
                    $companyChargeBalanceAfter  = $debitCustomer['company_charge_balance_after'];
*/

                            $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

                            if (! $customerBalance['status']) {
                                return response()->json($customerBalance, 400);
                            }

                            $company_debit_bankcode = $this->getCompanyBankCode();

                            $checkCompanyBalance = match ($company_debit_bankcode) {

                                90286 => ['status' => true], //7000299948
                                default
                                      => $this->checkCompanyBalanceThresholdTransaction($request->final_amount),
                            };

                            //   $checkCompanyBalance = $this->checkCompanyBalanceThresholdTransaction($request->final_amount);

                            if (! $checkCompanyBalance['status']) {
                                return response()->json($checkCompanyBalance, 400);
                            }

                            /*   if (auth()->user()->company_id != 1) {
                                $checkCompanyBalance = $this->checkCompanyBalanceThresholdTransaction($request->final_amount);

                                if (! $checkCompanyBalance['status']) {
                                    return response()->json($checkCompanyBalance, 400);
                                }
                            }*/

                            logInfo(json_encode($currentUser->company->id), "PreferredEbankingPartnerCompany");
                            $preferredEbankingPartner = PreferredEbankingPartnerCompany::where('company_id', $currentUser->company->id)->first();

                            if (isset($preferredEbankingPartner->company_id)) {
                                //logInfo(json_encode($currentUser->company->id), "PreferredEbankingPartnerCompany");

                                logInfo(json_encode($preferredEbankingPartner), "PreferredEbankingPartnerCompany");
                                $ebankingService = new EbankingCompanyService($preferredEbankingPartner);

                            } else {
                                $preferredEbankingPartner = PreferredEbankingPartner::all();
                                $ebankingService          = new EbankingService($preferredEbankingPartner);

                            }

                            $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->destination_account, $currentUser->company->id);

                            if (! $bankAccount['status']) {
                                $bankAccount['status'] = false;
                                return response()->json($bankAccount);
                            }

                            $bank               = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();
                            $gatewayDescription = $currentUser->first_name . " " . $currentUser->last_name . " / " . $currentUser->company->company_name;

                            // $transactionRef2 = generateTransactionReference();

                            // transactions::create([
                            //     "company_balance_before" => $companyChargeBalanceBefore,
                            //     "company_amount" => $wbaCharge,
                            //     "account_id" => $accountId > 0 ? $accountId : null,
                            //     "company_balance_after" => $companyChargeBalanceAfter,
                            //     "description" => "Charge for NGN Bank Transfer - " . $transaction->transaction_reference,
                            //     "transaction_reference" => $transactionRef2,
                            //     "parent_transaction_id" => $transaction->id,
                            //     "balance_before" => $customerChargeBalanceBefore,
                            //     "amount" => $charge,
                            //     "balance_after" => $customerChargeBalanceAfter,
                            //     "company_id" => $transaction->company_id,
                            //     "transaction_remarks_id" => 2,
                            //     "transaction_type_id" => 27,
                            //     "credit_debit_type_id" => 2,
                            //     "created_date"  => date('Y-m-d'),
                            //     "created_by" => $transaction->created_by,
                            //     "ledger_cr" => $ledger_cr,
                            //     "ledger_dr" => $ledger_dr
                            // ]);

                            $checkDebitCustomer = match ($company_debit_bankcode) {

                                90286 => checkCustomerAndCompanyWithChargeSafeHaven($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge), //7000299948
                                default
                                      => checkCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge),
                            };

                            // $checkDebitCustomer = checkCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge);
                            logInfo(json_encode($checkDebitCustomer), "checkDebitCustomer");
                            if (! $checkDebitCustomer['status']) {

                                $checkDebitCustomer['status'] = false;
                                return response()->json($checkDebitCustomer);
                            }

                            $myDescription = auth()->user()->first_name . " " . auth()->user()->last_name . " / " . \auth()->user()->company->company_name;
                            // $myDescription .= $request->description == "" ? "" : $request->description;
                            DB::commit();

                            $bankTransferType                       = new BankTransferType();
                            $bankTransferType->bankCode             = $request->bank_code;
                            $bankTransferType->shortBankCode        = $bank->short_code;
                            $bankTransferType->accountNumber        = $request->destination_account;
                            $bankTransferType->description          = $gatewayDescription;
                            $bankTransferType->amount               = $request->final_amount;
                            $bankTransferType->transactionReference = $transaction->transaction_reference;
                            $bankTransferType->sessionId            = $currentUser->virtual_account;
                            $bankTransferType->companyID            = $currentUser->company->id ?? "";
                            $bankTransferType->senderAccountNumber  = $currentUser->accounts->first()->virtual_accnum ?? "";

                            $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

                            // if ($bankTransfer['status'] == "01") {
                            //     $reversalResult = debitReversal($transaction->id, $transaction->account_id, $transaction->company_id, $transaction->amount, $bankTransferInfo->charge + $bankTransferInfo->amount, $bankTransferInfo->transfer_entity_type);
                            //     $bankAccount['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
                            //     $transaction->transaction_remarks_id = 3;
                            //     $transaction->save();

                            //     logInfo($reversalResult, "Reversal of Transaction Result");

                            //     return $bankTransfer;
                            // }

                            if ($bankTransfer['status'] != "00") {
                                $transaction->transaction_remarks_id = 3;
                                $transaction->save();
                                $bankTransfer['status'] = 0;

                                return $bankTransfer;
                            } else {
                                // debitCustomerAndCompanyWithChargeServiceWallet

                                $debitCustomer = match ($company_debit_bankcode) {

                                    90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge), //7000299948
                                    default
                                          => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge),
                                };
                                //       $debitCustomer = debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge);

                                $accountId                   = $debitCustomer['account_id'];
                                $customerBalanceBefore       = $debitCustomer['balance_before'];
                                $customerBalanceAfter        = $debitCustomer['balance_after'];
                                $customerChargeBalanceBefore = $debitCustomer['charge_balance_before'];
                                $customerChargeBalanceAfter  = $debitCustomer['charge_balance_after'];

                                $companyBalanceBefore       = $debitCustomer['company_balance_before'];
                                $companyAmount              = $debitCustomer['company_amount'];
                                $companyBalanceAfter        = $debitCustomer['company_balance_after'];
                                $companyChargeBalanceBefore = $debitCustomer['company_charge_balance_before'];
                                $companyChargeBalanceAfter  = $debitCustomer['company_charge_balance_after'];

                                $newdescription = $request->description == "" ? $currentUser->first_name : $request->description;

                                $newdescription = $newdescription . "/" . $request->receipient_name . "/" . $request->destination_account . " - " . $bank->bank_name;

                                $myDescription = "You transferred funds to " . $bankAccount['data']['account_name'] . "/" . $request->destination_account . " / " . $bank->bank_name . " / " . $currentUser->company->company_name;

                                $transaction->description = $myDescription;

                                $transaction->balance_before = $customerBalanceBefore;
                                //  $transaction->amount         = $request->final_amount + $charge;
                                $transaction->amount        = $request->final_amount;
                                $transaction->balance_after = $customerChargeBalanceAfter;

                                $transaction->company_balance_before = $companyBalanceBefore;
                                $transaction->company_amount         = $request->final_amount + $wbaCharge;
                                $transaction->company_balance_after  = $companyChargeBalanceAfter;

                                $transaction->save();

                                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $transaction->company_id);
                                if ($ledger_Settings) {
                                    $ledger_cr = $ledger_Settings->ledger_cr;
                                    $ledger_dr = $ledger_Settings->ledger_dr;
                                } else {
                                    return ['status' => false, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
                                }

                                if ($currentUser->company->id == 33) {
                                    $phone            = $currentUser->phone;
                                    $transaction_type = "transaction";
                                    $amount           = $request->final_amount;
                                    $balance          = $debitCustomer['charge_balance_after'];
                                    $account_id       = $currentUser->id;

                                    goldencreditSMSPost($phone, $transaction_type, $amount, $balance, $account_id);
                                } else {
                                    sendSMS($currentUser->id, "Debit Amt: | $request->final_amount, Desc: App/ {$request->description} " . "Acc: {$bankAccount['data']['account_name']} " . "Bal: N {$debitCustomer['charge_balance_after']} " . "Date: " . date('Y-m-d') . " Ref: " . $request->transaction_reference, true, true, 'Debit Transaction');
                                    // $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();
                                }
                            }

                            $transaction->transaction_remarks_id = 2;
                            $transaction->save();

                            $check_transfer_log = Beneficiary::where('accountNo', $request->destination_account)
                                ->whereNull('transaction_id')
                                ->first();

                            if ($check_transfer_log) {

                            } else {
                                $transfer_log            = new Beneficiary;
                                $transfer_log->name      = $request->receipient_name;
                                $transfer_log->bank_id   = $bank->id;
                                $transfer_log->accountNo = $request->destination_account;
                                $transfer_log->user_id   = auth()->user()->id;
                                $transfer_log->save();
                            }

                            $subtext = "Debit Transaction";
                            $reason  = "";
                            $msg     = "Debit Amt:$request->final_amount,<br><br> Desc: App/" . $bankAccount['data']['account_name'] . "$request->description" . "<br><br> Avail Bal:" . $debitCustomer['charge_balance_after'] . "<br><br>Date:" . date('Y-m-d') . " <br><br>Ref: " . $request->transaction_reference;

                            $this->sendTransactionReportEmail($subtext, $msg, $reason, $currentUser);
                            //   spendSaveRecordTransaction($debitCustomer, $transaction);

                            return response()->json([
                                "status"  => true,
                                'message' => "Wallet to Bank Transfer Successful",
                                'data'    => [
                                    'balance' => $debitCustomer['charge_balance_after'],
                                ],
                            ]);
                        } else {
                            return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                        }
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
                }
            } finally {
                $lock->release();
            }
        } else {
            return response()->json(['message' => "Transaction is in progress. Try again."], 400);
        }

    }

    public function getBeneficiaries(Request $request)
    {
        $beneficiaries = Beneficiary::where('user_id', auth()->user()->id)->select('bank_id', 'name', 'accountNo')->distinct()->get();
        if ($beneficiaries != null) {
            return response()->json([
                "status"  => true,
                'message' => "Beneficiary Retieved Successful",
                'data'    => [
                    'accounts' => BenefeciariesResource::collection($beneficiaries),
                ],
            ]);
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

        if ($request->api_key != "enter your key") {
            $ra = ["status" => false, "message" => "Invalid API key provided"];
            return response()->json($ra, 400);
        }

        // $newdescription = $request->description == "" ? "From " . \auth()->user()->first_name : $request->description;
        $newdescription = $request->description ?? "From " . \auth()->user()->first_name;
        // $newdescription = auth()->user()->first_name . " " . auth()->user()->last_name . " / " . auth()->user()->company->company_name;
        // $newdescription = "Transfer using USSD";

        $bank = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();

        if (! $bank) {
            return response()->json(["status" => false, 'message' => "Invalid bank code"], 400);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankTransferType                       = new BankTransferType();
        $bankTransferType->bankCode             = $request->bank_code;
        $bankTransferType->shortBankCode        = $bank->short_code;
        $bankTransferType->accountNumber        = $request->destination_account;
        $bankTransferType->description          = $newdescription;
        $bankTransferType->amount               = $request->amount;
        $bankTransferType->transactionReference = $request->transaction_reference;

        $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

        if ($bankTransfer['status'] == "01") {

            $bankTransfer['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
            $bankTransfer['status']  = false;

            return response()->json($bankTransfer, 400);
        }

        $bankTransfer['status'] = true;

        return response()->json($bankTransfer);
    }

    public function verifyBankAccountUSSD(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "account_number" => "required",
            "bank_code"      => "required|string",
            "ApiKey"         => "required|string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        if ($request->ApiKey != "enter your key") {
            $ra = ["status" => false, "message" => "Invalid API key provided"];
            return response()->json($ra, 400);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        if (! $bankAccount['status']) {
            $bankAccount['status'] = 0;
            return response()->json($bankAccount);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank                             = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bankAccount['data']['bank_name'] = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $bankAccount['data']['bank_name'] = $bank_name;
        return response()->json($bankAccount);
    }

    public function walletTransfer(Request $request)
    {
        $description   = $request->description;
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
        if ($request->final_amount == null) {
            $ra = ["status" => false, "message" => "Please enter amount"];
            return response()->json($ra, 406);
        }
        $wbAccount = wb_account_details::where('virtual_accnum', $request->destination_account)->orWhere('virtual_accnum', ($request->destination_account . "_"))->first();

        /*  if (! $wbAccount) {
            $companyAcc = $this->walletTransferCompany($request, $description, $transactionId);

            $ra = ["status" => false, "message" => "Beneficiary not found!"];
            return response()->json($ra, 406);
        }*/

        if (
            in_array(auth()->user()->company_id, [87, 88, 89])

        ) {
            // $sourceWbAccount = wb_account_details::where('virtual_accnum', $request->source_account)->orWhere('virtual_accnum', ($request->source_account . "_"))->orWhere('user_id', auth()->user()->id)->first();

            $sourceWbAccount = wb_account_details::where('virtual_accnum', $request->source_account)->first() ?? wb_account_details::where('virtual_accnum', $request->source_account . "_")->first() ?? wb_account_details::where('user_id', auth()->user()->id)->first();
            logInfo(json_encode($sourceWbAccount), "walletTransfer sourceWbAccoun");
            if (! $sourceWbAccount) {
                $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
                return response()->json($ra, 406);
            }
        } else {
            $sourceWbAccount = wb_account_details::where(['user_id' => auth()->user()->id, 'currency_id' => $wbAccount->currency_id])->first();
            if (! $sourceWbAccount) {
                $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
                return response()->json($ra, 406);
            }
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount, $sourceWbAccount->id);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else {
                $customerBalance = $this->checkCustomerBalance($request->final_amount, $sourceWbAccount->id);
                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }
                if ($wbAccount) {

                    if ($request->destination_account == \auth()->user()->virtual_account) {
                        return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
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

                    if (
                        in_array(auth()->user()->company_id, [87, 88, 89]) && ($wbAccount->currency_id != $sourceWbAccount->currency_id)) {
                        $final_amount = $this->getExchangeRates($request, $wbAccount->currency_id, $sourceWbAccount->currency_id);
                    } else {
                        $final_amount = $request->final_amount;
                    }

                    $debitCustomer = \debitCustomerOnly($customerBalance['account_id'], $request->final_amount, \auth()->user());
                    // $debitCustomer = $this->debitCustomer($request);
                    if ($debitCustomer['status']) {
                        $creditCustomer = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
                        // $creditCustomer = \creditCustomerOnly("1", "1000");
                        $myDescription       = "You received payment from " . \auth()->user()->first_name . " - " . $request->description . $sourceWbAccount->virtual_accnum;
                        $debitTransactionRef = \generate_txnID();
                        $ledger_cr           = null;
                        $ledger_dr           = null;
                        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(32, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $creditTransaction  = transactions::create([
                            "balance_before"         => $creditCustomer->balance,
                            "amount"                 => $final_amount,
                            "balance_after"          => $creditCustomer->balance + $final_amount,
                            "account_id"             => $wbAccount->id,
                            "description"            => $myDescription,
                            "branch_id"              => $wbAccount->user->branch_id,
                            "transaction_reference"  => $debitTransactionRef,
                            "parent_transaction_id"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 32,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);
                        $creditCustomer->balance += $final_amount;
                        $creditCustomer->save();
                        $descArray  = explode("-", $myDescription);
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

    public function walletTransferCompany(Request $request, $description, $transactionId)
    {

        $wbAccount = company_wallet::where('vaccount', $request->destination_account)->first();
        logInfo(json_encode($wbAccount), "walletTransferCompany");
        if (! $wbAccount) {
            $ra = ["status" => false, "message" => "Beneficiary not found!"];
            return response()->json($ra, 406);
        }
        $sourceWbAccount = wb_account_details::where(['user_id' => auth()->user()->id, 'currency_id' => 1])->first();

        if (! $sourceWbAccount) {
            $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount, $sourceWbAccount->id);

        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else {
                $customerBalance = $this->checkCustomerBalance($request->final_amount, $sourceWbAccount->id);
                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }
                if ($wbAccount) {

                    if ($request->destination_account == \auth()->user()->virtual_account) {
                        return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                    }
                    if ($wbAccount->id != 14) {
                        return response()->json(["status" => false, 'message' => "Invalid account number"], 409);
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
                        $myDescription       = "You received payment from " . \auth()->user()->first_name . " - " . $request->description . $sourceWbAccount->virtual_accnum;
                        $debitTransactionRef = \generate_txnID();
                        $ledger_cr           = null;
                        $ledger_dr           = null;
                        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(32, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $creditTransaction  = transactions::create([
                            "balance_before"         => $creditCustomer->balance,
                            "amount"                 => $request->final_amount,
                            "balance_after"          => $creditCustomer->balance + $request->final_amount,
                            "account_id"             => $wbAccount->id,
                            "description"            => $myDescription,
                            "branch_id"              => $wbAccount->user->branch_id,
                            "transaction_reference"  => $debitTransactionRef,
                            "parent_transaction_id"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 32,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);
                        $creditCustomer->balance += $request->final_amount;
                        $creditCustomer->save();
                        $descArray  = explode("-", $myDescription);
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

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] != 1) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction, 400);
        }

        $description = $request->description;

        if ($request->final_amount > 0) {

            if ($request->transaction_type == 3) {
                $companyName     = auth()->user()->company->company_name;
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => auth()->user()->company->wallet->balance,
                    "amount"                 => $request->amount,
                    "description"            => "Funding Admin Wallet",
                    "transaction_reference"  => $transactionId,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_remarks_id" => 6,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
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

                if ($request->final_amount > 0) {

                    if (auth()->user()->company->wallet->balance >= $request->final_amount) {

                        $charge          = 0;
                        $ledger_cr       = null;
                        $ledger_dr       = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $transaction = transactions::create([
                            "balance_before"         => auth()->user()->company->wallet->balance,
                            "amount"                 => $request->final_amount,
                            "description"            => $description,
                            "transaction_reference"  => $transactionId,
                            "branch_id"              => \auth()->user()->branch_id,
                            "company_id"             => auth()->user()->company_id,
                            "transaction_remarks_id" => 6,
                            "transaction_type_id"    => $request->transaction_type,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);

                        return response()->json(
                            [
                                "status"  => true,
                                'message' => "Transaction initialized",
                                'data'    => [
                                    'charge'            => $charge ?? 0.00,
                                    'transactionId'     => $transaction->id,
                                    'transactionTypeId' => $request->transaction_type,
                                    'exchangeAmount'    => $request->final_amount,
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

                    $validateSavingsProduct = $this->accountProductCharges($request->final_amount);

                    if (! $validateSavingsProduct['status']) {
                        return \response()->json($validateSavingsProduct, 400);
                    }

                    $charge = $validateSavingsProduct['data']['charge'];
                    logInfo($charge, "Charge for Transaction type 8");

                    $amount      = $request->final_amount;
                    $totalAmount = $request->final_amount + $charge;
                    $description = $description ?? "You sent money to $request->receipient_name via Bank Transfer";
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
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
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
                    "transaction_remarks_id" => 6,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Pending Transaction initialized",
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
                //  return response()->json("Not available now", 400);
                $amount      = 0;
                $totalAmount = 0;

                $amount      = $request->final_amount;
                $totalAmount = $request->final_amount;
                $name        = isset($request->receipient_name) ? $request->receipient_name : "N/A";
                $description = "You sent payment to $name $request->description / $request->destination_account ";

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
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
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
                    "transaction_remarks_id" => 6,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Pending Transaction initialized",
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

        if ($request->transaction_type != "TRANSFER") {
            return;
        }

        $wovenController = new WovenController();

        $wovenController->insertIntoWebhookTable($request);

        $verifyPaymentResponse = $wovenController->verifyWovenTransaction($request->unique_reference);

        if (! $verifyPaymentResponse['status']) {
            return response()->json($verifyPaymentResponse, 400);
        }

        if ($verifyPaymentResponse['data']['nuban'] != $request->nuban) {
            \logInfo("Verification nuban " . $verifyPaymentResponse['data']['nuban'] . " " . $request->nuban, "Nuban Information");
            return response()->json(['status' => false, 'message' => "Invalid Webhook account number"], 400);
        }

        $wovenController->transactionReference = $request->unique_reference;
        $wovenController->wbaCharge            = getWbaTransferCharges(3, $verifyPaymentResponse['data']['amount']);
        $wovenController->virtualAccount       = $request->nuban;
        $wovenController->amount               = $verifyPaymentResponse['data']['amount'];
        $wovenController->webhookType          = 'woven';
        $wovenController->description          = "You received Funds from $request->source_account_name";
        $wovenController->transactionDate      = date('Y-m-d');
        $wovenController->accountReference     = $request->transaction_date;
        $wovenController->request              = $request;
        $wovenController->fromTempWebhook      = 'false';

        $handleWebhook = $wovenController->handleWebhook();
        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function cyberpayWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {
            $webhookLog = WebhookLog::where('unique_reference', $request->PaymentReference)->first();

            if (! $webhookLog) {
                WebhookLog::create([
                    'webhook_type'     => 'cyberpay',
                    'body'             => json_encode($request->all()),
                    'unique_reference' => $request->PaymentReference,
                ]);
            }

            $mandate = Mandate::where('mandate_code', $request->MandateCode)->first();

            if (! $mandate) {
                return response()->json(['status' => false, "message" => "Invalid Mandate code Provided"]);
            }

            $mandateLog = MandateLog::where('payment_reference', $request->PaymentReference)->first();

            if ($mandateLog) {
                return response()->json(['status' => false, "message" => "Payment Reference already exists"]);
            }

            $cyberpayController = new CyberpayControllelr();

            $verifyPaymentResponse = $cyberpayController->verifyMandateStatus($request->MandateCode);

            if (! $verifyPaymentResponse['status']) {
                return response()->json($verifyPaymentResponse, 400);
            }

            MandateLog::create([
                "mandate_id"           => $mandate->id,
                'payment_reference'    => $request->PaymentReference,
                'marchant_ref'         => $request->MarchantRef,
                'response_description' => $request->ResponseDescription,
                'amount'               => $request->Amount / 100,
                'narration'            => $request->Narration,
                'charge'               => $request->Charge / 100,
                'payment_status'       => $request->PaymentStatus,
                'payment_date'         => $request->PaymentDate,
            ]);

            if ($request->ResponseCode == "00") {

                $companyAmount   = $request->Amount / 100;
                $creditCompany   = creditCompanyWithCharge($mandate->user->company->id, $companyAmount, 0);
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(100, $mandate->user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_amount"         => $companyAmount,
                    'company_balance_before' => $creditCompany['balance_before'],
                    'company_balance_after'  => $creditCompany['balance_after'],
                    "description"            => "Payment received for mandate deduction $request->MandateCode",
                    "system_description"     => "Payment received for mandate deductin with NIBBS $request->MandateCode",
                    "transaction_reference"  => $request->PaymentReference,
                    "transaction_remarks_id" => 2,
                    "company_id"             => $mandate->user->company_id,
                    "transaction_type_id"    => 100,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $mandate->user->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            }

            return response()->json(['status' => true, "message" => "Cyberpay webhook handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to Handle webhook... Please contact support"], 400);
        }
    }

    public function kudaWebhook(Request $request)
    {

        logInfo($request->all(), "Kuda Web Hook Request body");

        $kudaController = new KudaController();

        if ($request->transactionType != "Credit") {
            return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
        }

        $kudaController->transactionReference = $request->transactionReference;
        $kudaController->wbaCharge            = getWbaTransferCharges(2, $request->amount / 100);
        $kudaController->virtualAccount       = $request->accountNumber;
        $kudaController->amount               = $request->amount;
        $kudaController->webhookType          = 'kuda';
        $kudaController->description          = "You received Funds from $request->senderName";
        $kudaController->transactionDate      = date('Y-m-d');
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = 'false';

        $handleWebhook = $kudaController->handleWebhook();

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200);
    }

    public function kudaWebMaintenanceWebhook(Request $request)
    {

        logInfo($request->all(), "Kuda Web Hook for Maintenance Request body");

        $kudaController = new KudaController();

        if ($request->transactionType != "Credit") {
            return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
        }

        $kudaController->transactionReference = $request->transactionReference;
        $kudaController->wbaCharge            = getWbaTransferCharges(2, $request->amount / 100);
        $kudaController->virtualAccount       = $request->accountNumber;
        $kudaController->amount               = $request->amount;
        $kudaController->webhookType          = 'kuda';
        $kudaController->description          = "You received Funds from $request->senderName";
        $kudaController->transactionDate      = date('Y-m-d');
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = 'false';

        $handleWebhook = $kudaController->processCompanyMaintenanceWebhook();

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200);
    }

    public function providusWebhook(Request $request)
    {
        try {
            if ($request->event == "customer_account_created" || $request->input('data.status') == "pending") {
                return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
            }
            $providusController = new ProvidusController();

            //remove providus verification
/*
            $verifyWebhook = $providusController->verifyIncomingWebhook($request);

            if (! $verifyWebhook['status']) {
                return response()->json($verifyWebhook, 400);
            }

            $verifyTransaction = $providusController->verifyProvidusTransaction($request->settlementId);

            if (! $verifyTransaction["status"]) {
                return response()->json($verifyTransaction, 400);
            }

            //remove providus verification
*/
            /*    $providusController->transactionReference = $request->settlementId;
            $providusController->wbaCharge            = getWbaTransferCharges(9, $request->transactionAmount);
            $providusController->virtualAccount       = $request->accountNumber;
            $providusController->amount               = $request->transactionAmount;
            $providusController->webhookType          = 'providus';
            $providusController->description          = "You received Funds from $request->sourceAccountName";
            $providusController->transactionDate      = date('Y-m-d');
            $providusController->request              = $request;
            $providusController->fromTempWebhook      = 'false';*/

            $providusController->transactionReference = $request->input('data.reference');
            $providusController->wbaCharge            = getWbaTransferCharges(9, $request->input('data.amount'));
            $providusController->virtualAccount       = $request->input('data.beneficiaryAccountNumber');
            $providusController->amount               = $request->input('data.amount');
            $providusController->webhookType          = 'providus';
            $providusController->description          = "You received Funds from {$request->input('data.originatorAccountName')}";
            $providusController->transactionDate      = date('Y-m-d');
            $providusController->request              = $request;
            $providusController->fromTempWebhook      = 'false';

            $status = $request->input('data.status');
            if ($status == "failed") {
                return \response()->json(['status' => false, 'message' => 'Transaction Failed']);
            }

            $handleWebhook = $providusController->handleWebhook();

            return response()->json($handleWebhook);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Error");

            return ['responseCode' => "02", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"];

            // return response()->json(['status' => false, 'message' => "FAiled to handled"], 400);
        }
    }

    public function gtbWebhook(Request $request)
    {
        $gtbController = new GTBController();
        $verifyWebhook = $gtbController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }

        $gtbController->insertIntoWebhookTable($request);

        $gtbController->transactionReference = $request->transaction_reference;
        $gtbController->wbaCharge            = getWbaTransferCharges(8, $request->principal_amount);
        $gtbController->virtualAccount       = $request->virtual_account_number;
        $gtbController->amount               = $request->principal_amount;
        $gtbController->webhookType          = 'gtb';
        $gtbController->description          = "You received Funds from " . $gtbController->getSenderName($request->remarks);
        $gtbController->transactionDate      = $request->transaction_date;
        $gtbController->request              = $request;
        $gtbController->fromTempWebhook      = 'false';

        $handleWebhook = $gtbController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function vpayWebhook(Request $request)
    {

        $vpayController = new VPayController();
        /* $verifyWebhook  = $vpayController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }*/

        $vpayController->transactionReference = $request->reference;
        $vpayController->wbaCharge            = getWbaTransferCharges(7, $request->amount);
        $vpayController->virtualAccount       = $request->account_number;
        $vpayController->amount               = $request->amount;
        $vpayController->webhookType          = 'vpay';
        $vpayController->description          = "You received Funds from $request->originator_account_name";
        $vpayController->transactionDate      = $request->timestamp;
        $vpayController->request              = $request;
        $vpayController->fromTempWebhook      = 'false';

        $handleWebhook = $vpayController->handleVPayWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function fairMoneyWebhook(Request $request)
    {

        $fairmoneyController = new FairMoneyController();
        $verifyWebhook       = $fairmoneyController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        $currentRequest = $request->all();

        if (isset($currentRequest['eventType'])) {
            if ($currentRequest['eventType'] == "Fund Transfer") {
                return response()->json(['status' => false, 'meesage' => 'Event Not virtual account funding'], 400);
            }
        }

        if ($currentRequest['Data']['TransactionStatus'] != "APPROVED") {
            return response()->json(['status' => false, 'meesage' => 'Transaction not approved'], 400);
        }

        if ($currentRequest['EventType'] != "Virtual Account Funding") {
            return response()->json(['status' => false, 'meesage' => 'Event Not virtual account funding'], 400);
        }

        $fairmoneyController->transactionReference = $currentRequest["Data"]["TransactionReference"];
        $fairmoneyController->wbaCharge            = getWbaTransferCharges(34, $currentRequest["Data"]["Amount"]);
        $fairmoneyController->virtualAccount       = $currentRequest["Data"]["CustomerAccountNumber"];
        $fairmoneyController->amount               = $currentRequest["Data"]["Amount"];
        $fairmoneyController->webhookType          = 'fairmoney';
        $fairmoneyController->description          = $currentRequest["Data"]["Narration"];
        $fairmoneyController->transactionDate      = $currentRequest["Data"]["TransactionDate"];
        $fairmoneyController->request              = $request;
        $fairmoneyController->fromTempWebhook      = 'false';

        $handleWebhook = $fairmoneyController->handleWebhook();

        logInfo($handleWebhook, "Handle webhhook result");

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function baniWebhook(Request $request)
    {

        $baniController = new BaniController();
        $verifyWebhook  = $baniController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        // array (
        //     'event' => 'payin_bank_transfer',
        //     'data' =>
        //     array (
        //       'pay_ref' => 'BNPay-1gnhbs85zehwne6maqmxvg3jb86jt3',
        //       'pay_ext_ref' => NULL,
        //       'holder_first_name' => 'Godwin',
        //       'holder_last_name' => 'Famous',
        //       'custom_data' => NULL,
        //       'pay_amount' => 1000.0,
        //       'pay_method' => 'bank_transfer',
        //       'holder_phone' => NULL,
        //       'holder_phone_carrier' => NULL,
        //       'order_details' => NULL,
        //       'holder_currency' => 'NGN',
        //       'holder_country_code' => 'NG',
        //       'pay_status' => 'activated',
        //       'holder_account_number' => '6151196689',
        //       'pub_date' => '2023-09-21T09:33:02.298556Z',
        //       'modified_date' => '2023-09-21T09:34:21.964342Z',
        //       'holder_bank_name' => '9Payment Service Bank',
        //       'customer_ref' => 'MC-24734437117661638951',
        //       'merch_currency' => 'NGN',
        //       'merch_amount' => 995.0,
        //       'pay_amount_collected' => 1000.0,
        //       'pay_amount_outstanding' => '0.00',
        //       'holder_account_type' => 'permanent',
        //       'transaction_ref' => 'dyn4nnk6zc5a35pwhr02xvqa1',
        //       'source_account_name' => 'WBALITE-SHEMA EMMANUEL / SETTLEMENT',
        //       'actual_amount_paid' => 1000.0,
        //       'narration' => 'Inflow from WBALITE-SHEMA EMMANUEL / SETTLEMENT|SHEMA EMMANUEL   SETTLEMENT',
        //     ),
        //   ),

        $currentRequest = $request->all();

        if ($currentRequest['event'] != "payin_bank_transfer") {
            return response()->json(['status' => false, 'meesage' => 'Webhook not of bank transfer type'], 400);
        }

        if ($currentRequest['data']['pay_method'] != "bank_transfer") {
            return response()->json(['status' => false, 'meesage' => 'Payment method is not bank transfer'], 400);
        }

        $baniController->transactionReference = $currentRequest["data"]["transaction_ref"];
        $baniController->wbaCharge            = getWbaTransferCharges(35, $currentRequest["data"]["pay_amount"]);
        $baniController->virtualAccount       = $currentRequest["data"]["holder_account_number"];
        $baniController->amount               = $currentRequest["data"]["pay_amount"];
        $baniController->webhookType          = 'bani';
        $baniController->description          = $currentRequest['data']['narration'];
        $baniController->transactionDate      = $currentRequest["data"]["pub_date"];
        $baniController->request              = $request;
        $baniController->fromTempWebhook      = 'false';

        $handleWebhook = $baniController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function amuchaWebhook(Request $request)
    {
        $currentRequest = $request->all();

        // if (!isset($currentRequest["data"]["transaction"]['transactionId'])) {
        //     return response()->json(['status' => true, 'message' => 'testing successful']);
        // }

        $baniController = new AmuchaController();
        // $verifyWebhook = $baniController->verifyIncomingWebhook($request);

        // if (!$verifyWebhook['status']) {
        //     return response()->json($verifyWebhook, 400);
        // }

        // if ($currentRequest['event_type'] != "payment_success") {
        //     return response()->json(['status' => false, 'meesage' => 'Webhoook was not successful'], 400);
        // }

        // if (!isset($currentRequest["data"]["transaction"]["aliasAccountNumber"])) {
        //     $forwardWebhookResponse = forwardRequestToWirelesspay('user/card/webhook', $request->all());

        //     logInfo($forwardWebhookResponse, "Response of forwarded Amucha Webhook");
        //     return $forwardWebhookResponse;
        // }

        $baniController->transactionReference = $currentRequest["data"]["transaction"]['transactionId'];
        $baniController->wbaCharge            = getWbaTransferCharges(37, $currentRequest["data"]["transaction"]["transactionAmount"]);
        $baniController->virtualAccount       = $currentRequest["data"]["transaction"]["aliasAccountNumber"];
        $baniController->amount               = $currentRequest["data"]["transaction"]["transactionAmount"];
        $baniController->webhookType          = 'amucha';
        $baniController->description          = "you received payment from " . ($currentRequest["data"]["customer"]["billerId"] ?? $currentRequest["data"]["customer"]["senderName"] ?? 'Unknown');
        $baniController->transactionDate      = $currentRequest["data"]["transaction"]["time"];
        $baniController->request              = $request;
        $baniController->fromTempWebhook      = 'false';

        $handleWebhook = $baniController->handleWebhook();

        logInfo($handleWebhook, "Amucha Webhook response");

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 400);
    }

    public function verifyCardOrderTransaction(Request $request, $transactionReference)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->verifyTransaction($transactionReference);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function transferFunds(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->transferFunds($request->amount, $request->receiver_account_id, $request->merchant_ref, $request->account_id);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function assignTerminal(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->assignTerminal($request->serial_number, $request->terminal_label, $request->account_id);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function unassignTerminal(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->unassignTerminal($request->serial_number, $request->terminal_label);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function registerCustomer(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->registerCustomer($request->account_ref, $request->phone_number, $request->email, $request->bvn, $request->pin, $request->account_name, $request->call_back_url, $request->expiry_date);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function safeHavenWebhook(Request $request)
    {

        $safeHavenController = new SafehavenController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        logInfo($request->all(), "SafehavenWeb Hook Request body");

        $currentRequest = $request->all();

        if ($currentRequest['type'] != "transfer") {
            return response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $safeHavenController->transactionReference = $currentRequest['data']['paymentReference'];
        $safeHavenController->wbaCharge            = getWbaTransferCharges(38, $currentRequest['data']['amount']);
        $safeHavenController->virtualAccount       = $currentRequest['data']['creditAccountNumber'];
        $safeHavenController->amount               = $currentRequest['data']['amount'];
        $safeHavenController->webhookType          = 'safehaven';
        $safeHavenController->senderName           = $currentRequest['data']['debitAccountName'];
        $safeHavenController->description          = "You received Funds from" . $currentRequest['data']['debitAccountName'];
        $safeHavenController->transactionDate      = $currentRequest['data']['createdAt'];
        $safeHavenController->request              = $request;
        $safeHavenController->fromTempWebhook      = 'false';

        $handleWebhook = $safeHavenController->handleWebhook();

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        return response()->json($handleWebhook, $httpCode);
    }

    public function safeHavenCardMaintenanceWebhook(Request $request)
    {

        $safeHavenController = new SafehavenController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        logInfo($request->all(), "SafehavenWeb Hook Request body");

        $currentRequest = $request->all();

        if ($currentRequest['type'] != "transfer") {
            return response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $safeHavenController->transactionReference = $currentRequest['data']['paymentReference'];
        $safeHavenController->wbaCharge            = getWbaTransferCharges(38, $currentRequest['data']['amount']);
        $safeHavenController->virtualAccount       = $currentRequest['data']['creditAccountNumber'];
        $safeHavenController->amount               = $currentRequest['data']['amount'];
        $safeHavenController->webhookType          = 'safehaven';
        $safeHavenController->senderName           = $currentRequest['data']['debitAccountName'];
        $safeHavenController->description          = "You received Funds from" . $currentRequest['data']['debitAccountName'];
        $safeHavenController->transactionDate      = $currentRequest['data']['createdAt'];
        $safeHavenController->request              = $request;
        $safeHavenController->fromTempWebhook      = 'false';

        $handleWebhook = $safeHavenController->processCompanyCardSudoMaintenanceWebhook();

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        return response()->json($handleWebhook, $httpCode);
    }

    public function safeHavenSudoWebhook(Request $request)
    {

        $safeHavenController = new SafehavenController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $safeHavenController->handleSudoWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Sudo result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function mapleradWebhook(Request $request)
    {

        $mapleradController = new MapleradController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $mapleradController->handleMapleradWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook maplerad result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function fincraWebhook(Request $request)
    {
        $fincraController = new FincraController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $fincraController->handleFincraWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook fincra result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function strowalletWebhook()
    {
        $stroWalletController = new StroWalletController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $stroWalletController->handleStroWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook StroWallet result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
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
            $terminalResponse = forwardRequestToAM("services-webhook", $request->all());
            return $terminalResponse;
        }

        if ($request->transaction_type != "inward") {
            if ($request->pin != "") {
                if ($request->pin == $terminal->agent->pin) {
                    return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
                } else {
                    return \response()->json(['is_pin_valid' => false, "message" => 'Invalid transaction pin']);
                }
            }
        }
        return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
    }

    public function transferPosFunds(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "serial_number"      => 'required|string',
            "transaction_amount" => 'required|numeric|gt:0',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->all()[0]]);
        }

        $user = auth()->user();

        $userPos = AgentTerminal::where('terminal_code', $request->serial_number)->first();

        if (! $userPos) {
            return response()->json(['status' => false, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => false, 'message' => 'POS Serial number does to belong to the user']);
        }

        $transactionRef = generate_txnID();

        if ($userPos->balance < $request->transaction_amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient POS balance']);
        }

        $creditCustomerAndCompany = creditCustomerAndCompanyPOSAmountWithCharge($user->accounts[0]->id, $userPos->terminal_code, auth()->user()->company_id, $request->transaction_amount, 0, 0, $user);

        logInfo($creditCustomerAndCompany, "Credit Customer and Company POS");
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(100, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $creditCustomerAndCompany['pos_balance_before'],
            "amount"                 => $request->transaction_amount,
            "balance_after"          => $creditCustomerAndCompany['pos_balance_after'],
            "description"            => "POS Wallet Debit - $userPos->terminal_code to credit Savings wallet",
            "transaction_reference"  => $transactionRef,
            "account_id"             => $user->accounts[0]->id,
            "company_balance_before" => 0,
            "company_amount"         => 0,
            "company_balance_after"  => 0,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 69,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $companyTransactionRef = generate_txnID();
        $ledger_cr             = null;
        $ledger_dr             = null;
        $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(100, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $creditCustomerAndCompany['balance_before'],
            "amount"                 => $request->transaction_amount,
            "balance_after"          => $creditCustomerAndCompany['balance_after'],
            "description"            => "Received payment from POS Terminal - $userPos->terminal_code. the sum of $request->transaction_amount",
            "transaction_reference"  => $companyTransactionRef,
            "account_id"             => $user->accounts[0]->id,
            "company_id"             => auth()->user()->company_id,
            "company_balance_before" => $creditCustomerAndCompany['company_balance_before'],
            "company_amount"         => $request->transaction_amount,
            "company_balance_after"  => $creditCustomerAndCompany['company_balance_after'],
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 74,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        sendSMS($user->accounts[0]->id, "Credit Amt:$request->transaction_amount TXNcharge:0 Desc: POS Wallet Credit - $userPos->terminal_code Avail Bal:$userPos->balance Date:" . date('Y-m-d') . " Ref:$transactionRef", false, true, 'Credit Transaction');

        return response()->json(['status' => true, 'message' => 'POS Terminal Debited Successfully']);
    }

    public function newTransaction(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->resdTransaction($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function retryTransaction(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->retryTransaction($tryluxPayload);

        return response()->json($response, $tryluxPayload['status'] ? 200 : 400);
    }

    public function cardBalance(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->terminalId     = $request->terminal_id;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->cardBalance($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function networkManagement(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->stan            = $request->stan;
        $tryluxPayload->serialNumber    = $request->serial_number;
        $tryluxPayload->onlyAccountInfo = $request->only_account_info;

        $response = $tryluxController->resdNetworkManagement($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function getPosTerminals(Request $request)
    {
        $terminal = AgentTerminal::where('user_id', auth()->user()->id)->get();

        if (count($terminal) == 0) {
            return \response()->json(['status' => false, 'message' => "No Terminals found for This user"], 400);
        }

        return \response()->json(['status' => true, 'message' => 'Terminals fetched successfully', 'data' => $terminal]);
    }

    public function servicesWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $currentRequest = $request->all();

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
            //     "ServiceCode": "C01",
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
                return response()->json(['status' => false, 'responseMessage' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {
                return response()->json(['status' => false, 'message' => "Invalid Terminal Serial Number"], 400);
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company_id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            $customerCharge = 0;
            $companyCharge  = 0;

            if ($request->PostingType == "Cr") {

                // Get Erandpay POS WBA Charges
                // Get amount to be deducted from company
                $companyCharge = getWbaTransferCharges(11, $request->Amount);

                $companySettings = company_settings::where('company_id', $companyId)->first();

                $customerCharge  = $companySettings->additional_pos_charge_percent * $request->Amount;
                $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                $customerCharge += $companyCharge;
                $customerAmount -= $customerCharge;
            }

            if ($request->TransactionType == "FundTransfer") {
                $customerCharge    = getWbaTransferCharges(1, $request->Amount);
                $companyCommission = 2;
            }

            $transactionType = 0;

            $transactionType = $request->TransactionType == "CashOut" ? 63 : ($request->TransactionType == "FundTransfer" ? 65 : 64);

            $description        = "";
            $companyDescription = "";

            if ($request->TransactionType == "CashOut") {
                $description        = "You recieved payment from POS Terminal: $terminal->terminal_code";
                $companyDescription = "You recieved commission from POS Terminal - $terminal->terminal_code payment received";
                $systemDescription  = "Payment received from POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                $systemDescription  = "Card Details Masked PAN: " . $currentRequest["AdditionalDetails"]["MaskedPAN"] . " Exp Date: " . $currentRequest["AdditionalDetails"]["CardExpiry"];
                $systemDescription .= " TerminalID " . $currentRequest["AdditionalDetails"]["TerminalID"];
            } else if ($request->TransactionType == "FundTransfer") {
                $description        = "Funds transfer via termial: $terminal->terminal_code";
                $companyDescription = "Funds transfer commission via terminal - $terminal->terminal_code";
                $systemDescription  = "Funds Transferred via POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                $systemDescription  = "Terminal ID: $terminal->terminal_code, Card Details Destination Acc. Name: " . $currentRequest["AdditionalDetails"]["DestinationAccountName"] . " Destination Account Number: " . $currentRequest["AdditionalDetails"]["DestinationAccountNumber"];
                $systemDescription .= " Destination Bank Name " . $currentRequest["AdditionalDetails"]["DestinationBankName"];
            } else if ($request->TransactionType == "BillsPayment") {
                $customerCharge        = 0;
                $initialCustomerCharge = 0;
                $description           = "You paid bills via termial: $terminal->terminal_code";
                $companyCommission     = 0;

                $systemDescription  = "You purchased " . $currentRequest["AdditionalDetails"]["BillCategory"] . " from " . $currentRequest["AdditionalDetails"]["BillService"];
                $systemDescription .= " for " . $currentRequest["AdditionalDetails"]["Beneficiary"];
            }

            $creditCustomer = $request->PostingType == "Cr" ? creditPOSTerminal($terminal->terminal_code, $originalAmount, $customerCharge, $initialCustomerCharge, $companyId)
                : \debitCustomerAndCompanyPOSWithChargeAndCommission($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCommission);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transactionType, $customer->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
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
                "transaction_reference"  => $request->TransactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $transactionType,
                "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $customer->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ]);

            if ($creditCustomer['company_amount'] > 0) {

                $chargeTransactionRef = generate_txnID();
                $ledger_cr            = null;
                $ledger_dr            = null;
                $ledger_Settings      = get_CR_DR_TXN_Type_Ledger(70, $customer->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
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
                    "ledger_dr"              => $ledger_dr,
                    "ledger_cr"              => $ledger_cr,
                ]);
            }

            if ($request->TransactionType != "FundTransfer") {

                if ($customerCharge > 0) {
                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(76, $customer->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['pos_charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "company_amount"         => 0,
                        "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                        'company_balance_before' => 0,
                        'company_balance_after'  => 0,
                        "description"            => "Charge from POS payment received from Terminal - $terminal->terminal_code",
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 76,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customer->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);
                }
            }

            $balanceAfter = $creditCustomer['pos_charge_balance_after'];
            $desc         = "You recieved payment from POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->settlementId";

            // $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction', '', 0, false);

            $message = $request->PostingType == "Cr" ? "POS Credited successfully" : "POS Debited successfully";

            return response()->json(['status' => true, 'message' => $message]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function errandPayFundsTransferWebhook(Request $request)
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
            //     "ServiceCode": "C01",
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
                return response()->json(['status' => false, 'message' => "Invalid Terminal Id"], 400);
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

            $customerCharge  = $companySettings->additional_pos_charge_percent * 0.01 * $request->Amount;
            $customerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
            $customerAmount -= $customerCharge;

            $creditCustomer  = \creditCustomerAndCompanyPOSAmountWithCharge($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCharge);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(68, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
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
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
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
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $balanceAfter = $creditCustomer['charge_balance_after'];
            $desc         = "You Received funds via POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Credit Amt:" . $creditCustomer['charge_balance_after'] . " Desc:$desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->TransactionReference";

            $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction', '', 0, false);

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
            //     "ServiceCode": "C01",
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
                return response()->json(['status' => false, 'message' => "Invalid Terminal Id"], 400);
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
                    $customerCharge  = $companySettings->incoming_transfer_charge_amount;
                    $customerAmount -= $customerCharge;
                } else {
                    $customerCharge  = $companySettings->incoming_transfer_charge_percentage * 0.01 * $request->Amount;
                    $customerCharge  = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                    $customerAmount -= $customerCharge;
                }
            }

            $creditCustomer   = \debitCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);
            $ledger_cr        = null;
            $ledger_dr        = null;
            $ledger_Settings  = get_CR_DR_TXN_Type_Ledger(6, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction  = transactions::create([
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
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            if ($customerCharge > 0) {
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
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
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            } else {
                if ($companyCharge > 0) {
                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
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
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);
                }
            }

            $balanceAfter = $creditCustomer['charge_balance_after'];
            $desc         = "You recieved payment from POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Debit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->settlementId";

            $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction');

            return response()->json(['status' => true, 'message' => "Webhook Handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function monnifyWebhook(Request $request)
    {

        $monnifyController = new MonnifyController();
        $verifyWebhook     = $monnifyController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }

        //Verify Transaction status from monnify
        $transactionStatus = $monnifyController->getTransactionStatus($request->transactionReference);
        if ($transactionStatus['status'] == "00") {
            $transactionStatus['status'] = false;
            return response()->json($transactionStatus, 400);
        }

        $monnifyController->transactionReference = $request->reference;
        $monnifyController->wbaCharge            = getWbaTransferCharges(10, $transactionStatus['data']['amount_paid']);
        $monnifyController->virtualAccount       = $request->account_number;
        $monnifyController->amount               = $transactionStatus['data']['amount_paid'];
        $monnifyController->webhookType          = 'monnify';
        $monnifyController->description          = "You received Funds from " . $request->accountDetails['accountName'];
        $monnifyController->transactionDate      = date('Y-m-d');
        $monnifyController->accountReference     = $request->product['reference'];
        $monnifyController->request              = $request;
        $monnifyController->fromTempWebhook      = 'false';

        $handleWebhook = $monnifyController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function cgateWebhook(Request $request)
    {

        $cgateController = new CgateController();
        $verifyWebhook   = $cgateController->verifyIncomingWebhook($request);

        $newResponse = $verifyWebhook;

        if ($verifyWebhook['status'] != "00") {
            return response()->json(['ResponseCode' => "01", 'ResponseMessage' => $newResponse['message']], 400);
        }

        //Verify Transaction status from monnify
        $transactionStatus = $cgateController->verifyTransaction($request->paymentReference);
        // if (!$transactionStatus['status'] != '00') {
        //     return response()->json($transactionStatus, 400);
        // }

        $cgateController->transactionReference = $request->paymentReference;
        $cgateController->wbaCharge            = getWbaTransferCharges(28, $request->amount);
        $cgateController->virtualAccount       = $request->customerRef;
        $cgateController->amount               = $request->amount;
        $cgateController->webhookType          = 'cgate';
        $cgateController->description          = "You received the sum of $request->amount via USSD, Sender: " . $request->mobileNumber . " with reference $request->paymentReference";
        $cgateController->transactionDate      = $request->transactionDate;
        $cgateController->request              = $request;
        $cgateController->fromTempWebhook      = 'false';

        $handleWebhook = $cgateController->handleWebhook();

        $newResponse = $handleWebhook;

        if ($handleWebhook['status'] == "00") {
            $newResponse = ['ResponseCode' => "00", 'ResponseMessage' => 'Successful'];
            $httpCode    = isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200;
        } else {
            $newResponse = ['ResponseCode' => "01", 'ResponseMessage' => $newResponse['message']];
            $httpCode    = isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 400;
        }

        return response()->json($newResponse, $httpCode);
    }

    public function assetMatrixWebhook(Request $request)
    {

        $assetMatrixController = new AssetMatrixController();
        $assetMatrixController->insertIntoWebhookTable($request);

        $verifyWebhook = $assetMatrixController->verifyTransaction($request->payment_reference);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        $assetMatrixController->transactionReference = $request->sessionId;
        $assetMatrixController->wbaCharge            = getWbaTransferCharges(6, $request->amount);
        $assetMatrixController->virtualAccount       = $request->sourceAccountNumber;
        $assetMatrixController->amount               = $request->amount;
        $assetMatrixController->webhookType          = 'assetmatrix';
        $assetMatrixController->description          = "You received the sum of $request->amount from " . $request->sourceAccountName;
        $assetMatrixController->transactionDate      = date("Y-m-d");
        $assetMatrixController->request              = $request;
        $assetMatrixController->fromTempWebhook      = 'false';

        $handleWebhook = $assetMatrixController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
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
        // It's a good idea to log all received events.

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

    public function getExchangeRates(Request $request, $destinationCurrencyID, $sourceCurrencyID)
    {

        try {
            $destinationCurrency = $this->matchCurrency($destinationCurrencyID);
            $sourceCurrency      = $this->matchCurrency($sourceCurrencyID);
            $amount              = $request->final_amount;
            \logInfo("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount", "Get Exhange URL");
            $response = Http::get("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount");
            \logInfo(json_encode($response), "Get Exhange Response");

            return $response['conversion_result'];

        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Get Exhange Error");
            return response()->json(["status" => 0, 'message' => "Failed to Verify Account"], 400);
        }

    }

    private function matchCurrency($currency_id)
    {
        return match ($currency_id) {
            2       => "USD",
            3       => "CAD",
            4       => "GBP",
            5       => "ZAR",
            6       => "CNY",
            7       => "EUR",
            8       => "GHS",

            default => "NGN",
        };
    }
    /* public function getTerminalBalance(Request $request)
    {

        $currentRequest = $request->all();

        if (isset($currentRequest['data']['transaction']['type']) && $currentRequest['data']['transaction']['type'] == 'vact_transfer') {

            $amuchaResponse = $this->amuchaWebhook($request);
        }

        try {
            if (isset($currentRequest['action_type'])) {

                $userPOS = AgentTerminal::where('terminal_code', $currentRequest['data']['terminal']['serialNo'])->first();

                //  logInfo(json_encode($userPOS), "Agent Terminal response");

                if (! $userPOS) {
                    if ($currentRequest['action_type'] == "pre-balance-check-auth") {
                        return response()->json(["status" => false, 'message' => "Invalid POS terminal Code"]);
                    }

                    if ($currentRequest['action_type'] == "pre-payout-auth") {

                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '03',
                            ],
                        ]]);
                    }
                }

                $user = $userPOS->agent;

                if ($user->pin != $currentRequest['data']['terminal']['pin']) {

                    if ($currentRequest['action_type'] == "pre-balance-check-auth") {
                        return $response = response()->json(["status" => "INVALID_PIN", 'message' => "Invalid authentication"]);
                    }

                    if ($currentRequest['action_type'] == "pre-payout-auth") {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '05',
                            ],
                        ]]);
                    }
                }

                $customerAccount = $user->wb_account_details[0];

                if ($currentRequest['action_type'] == "pre-balance-check-auth") {

                    return response()->json([
                        "data" => [
                            "user" => [
                                "balance"  => number_format($userPOS->balance, 2, '.', ''),
                                "currency" => "NGN",
                            ],
                        ],
                    ]);
                } else if ($currentRequest['action_type'] == "pre-payout-auth") {
                    if ($userPOS->status == 0) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '02',
                            ],
                        ]]);
                    }

                    $companySettings = company_settings::where('company_id', $user->company_id)->first();

                    if ($companySettings->pos_setting == 2) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '02',
                            ],
                        ]]);
                    }

                    $transaction = transactions::where('transaction_reference', $currentRequest['data']['transaction']['rrn'])->first();

                    if ($transaction) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '01',
                            ],
                        ]]);
                    }

                    $charge = $currentRequest['data']['transaction']["type"] == "transfer" ? 25 : 25;

                    $customerAmount = $currentRequest['data']['transaction']['amount'];

                    $theCharge = $companySettings->min_bank_transfer_charge_or_percentage;

                    // $customerCharge = ($companySettings->min_bank_transfer_charge_or_percentage * 0.01) * $currentRequest['data']['transaction']['amount'];
                    $customerCharge = $companySettings->max_bank_transfer_charge;

                    if ($currentRequest['data']['transaction']['type'] == "topup" || $currentRequest['data']['transaction']['type'] == "data") {
                        $theCharge      = 0;
                        $charge         = 0;
                        $customerCharge = 0;
                    }

                    $debitCustomer = debitPOSTerminalWithCharge($userPOS->id, $currentRequest['data']['transaction']['amount'], $customerCharge, $charge, $user);

                    if (! $debitCustomer['status']) {

                        if ($debitCustomer['responseCode'] == 0) {
                            return response()->json(['data' => [
                                'transaction' => [
                                    'shouldActionProceed' => false,
                                    'reasonCode'          => '07',
                                ],
                            ]]);
                        } else if ($debitCustomer['responseCode'] == 1) {
                            return response()->json(['data' => [
                                'transaction' => [
                                    'shouldActionProceed' => false,
                                    'reasonCode'          => '01',
                                ],
                            ]]);
                        }
                    }

                    $description = "WirelessPay miniPos - You carried out " . $currentRequest['data']['transaction']["type"] . " " .
                        $currentRequest['data']['transaction']['customerBillerId'] . " " .
                        $currentRequest['data']['transaction']['productId'] . " " .
                        $currentRequest['data']['terminal']['serialNo'] . " " .
                        $currentRequest['data']['transaction']['rrn'] . " " .
                        $currentRequest['data']['transaction']['billerAccountName'];

                    transactions::create([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $debitCustomer['amount'],
                        "balance_after"          => $debitCustomer['balance_after'],
                        "account_id"             => $customerAccount->id,
                        "description"            => $description,
                        "branch_id"              => $user->branch_id,
                        "company_balance_before" => $debitCustomer['company_balance_before'],
                        "company_amount"         => $debitCustomer['company_amount'],
                        "company_balance_after"  => $debitCustomer['company_balance_after'],
                        "company_id"             => $user->company_id,
                        "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 124,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customerAccount->id,
                    ]);

                    return response()->json(['data' => [
                        'transaction' => [
                            'shouldActionProceed' => true,
                            'reasonCode'          => '00',
                        ],
                    ]]);
                }
            } else if (isset($currentRequest['event_type'])) {

                if ($currentRequest['event_type'] == "payment_success") {
                    if ($currentRequest['data']['transaction']['type'] == "withdrawal" || $currentRequest['data']['transaction']['type'] == "purchase") {

                        $userPOS = AgentTerminal::where('terminal_code', $currentRequest['data']['transaction']['terminalSerialNumber'])->first();

                        if (! $userPOS) {
                            return response()->json(['status' => '00', 'message' => 'Invalid POS Serial Number']);
                        }

                        $transaction = transactions::where('transaction_reference', $currentRequest['data']['transaction']['rrn'])->first();

                        if ($transaction) {
                            return response()->json(['status' => '00', 'message' => 'Transaction already exists']);
                        }

                        $user = $userPOS->agent;

                        $companySettings = company_settings::where('company_id', $user->company_id)->first();

                        $description = "WirelessPay miniPos - " . $currentRequest['data']['transaction']["transactionId"] . " " .
                            $currentRequest['data']['transaction']['type'] . " " .
                            $currentRequest['data']['transaction']['transactionAmount'] . " " .
                            $currentRequest['data']['transaction']['terminalSerialNumber'] . " " .
                            $currentRequest['data']['transaction']['rrn'];

                        // $posSetting = $terminal->agent->company->setting->pos_setting;

                        // logInfo($posSetting, "My POS Setting is...");

                        $originalAmount  = $currentRequest['data']['transaction']['transactionAmount'];
                        $customerAccount = $user->wb_account_details[0];
                        $accountId       = $customerAccount->id;
                        $branchId        = $user->branch->id;
                        $companyId       = $user->company_id;

                        $companyCharge = getWbaTransferCharges(39, $originalAmount);

                        if ($companySettings->pos_setting == 2) {

                            $posCreditDebitType = false;

                            $charge = 0;

                            $customerAmount = $currentRequest['data']['transaction']['transactionAmount'];

                            $theCharge = $companySettings->additional_pos_charge_percent;

                            $customerCharge  = ($companySettings->additional_pos_charge_percent / 100) * $customerAmount;
                            $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                            $customerCharge += $charge;
                            $customerAmount -= $customerCharge;

                            $creditCustomer = creditCustomerOnly($customerAccount->id, $originalAmount, $customerCharge);

                            logInfo($creditCustomer, "POS Credit customer Only");

                            if (! $creditCustomer['status']) {
                                return response()->json($creditCustomer, 400);
                            }

                            $transactionType = 109;

                            logInfo($creditCustomer, "POS Credit customer Only");

                            $transaction = transactions::create([
                                "branch_id"              => $branchId,
                                "account_id"             => $accountId,
                                "description"            => "You Received funds via POS Terminal: " . $userPOS->terminal_code,
                                "balance_before"         => $creditCustomer['balance_before'],
                                "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                                "amount"                 => $originalAmount + $customerCharge,
                                "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => $transactionType,
                                "credit_debit_type_id"   => 1,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => $customer->id,
                            ]);
                        } else {

                            $charge         = $currentRequest['data']['transaction']["type"] == "transfer" ? 0 : 0;
                            $customerAmount = $currentRequest['data']['transaction']['transactionAmount'];

                            $customerCharge  = ($companySettings->additional_pos_charge_percent / 100) * $customerAmount;
                            $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                            $customerCharge += $charge;
                            $customerAmount -= $customerCharge;

                            $creditCustomer = creditPOSTerminal($userPOS->terminal_code, $originalAmount, $customerCharge, $companyCharge, $companyId);

                            logInfo($creditCustomer, "Credit terminal log");

                            $transaction = transactions::create([
                                "balance_before"         => $creditCustomer['pos_balance_before'],
                                "amount"                 => $creditCustomer['pos_amount'],
                                "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                                "company_amount"         => $creditCustomer['company_amount'],
                                'company_balance_before' => $creditCustomer['company_balance_before'],
                                'company_balance_after'  => $creditCustomer['company_balance_after'],
                                "description"            => $description,
                                "branch_id"              => $branchId,
                                "company_id"             => $companyId,
                                "account_id"             => $accountId,
                                "system_description"     => $description,
                                "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => 63,
                                "credit_debit_type_id"   => 1,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => $user->id,
                            ]);
                        }

                        // if ($companySettings->pos_setting == 2) {
                        //     return response()->json(['status' => '00', 'message' => 'Cannot process this transaction']);
                        // }

                        // $customerAccount = $user->wb_account_details[0];

                        // $charge = 0;

                        // $customerAmount = $currentRequest['data']['transaction']['amount'];

                        // $theCharge = $companySettings->additional_pos_charge_percent;

                        // $customerCharge = $companySettings->additional_pos_charge_percent * $request->Amount;
                        // $customerCharge = $initialCustomerCharge = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                        // $customerCharge += $charge;
                        // $customerAmount -= $customerCharge;

                        // $debitCustomer = creditCustomerOnly($customerAccount->id, $currentRequest['data']['transaction']['transactionAmount'], $customerCharge);

                        // if (!$debitCustomer['status']) {
                        //     return response()->json($debitCustomer);
                        // }

                        // transactions::create([
                        //     "balance_before" => $debitCustomer['balance_before'],
                        //     "amount" => $debitCustomer['amount'],
                        //     "balance_after" => $debitCustomer['balance_after'],
                        //     "account_id" => $customerAccount->id,
                        //     "description" => $description,
                        //     "branch_id" => $user->branch_id,
                        //     "company_balance_before" => 0,
                        //     "company_amount" => 0,
                        //     "company_balance_after" => 0,
                        //     "transaction_reference" => $currentRequest['data']['transaction']['rrn'],
                        //     "transaction_remarks_id" => 2,
                        //     "transaction_type_id" => 63,
                        //     "credit_debit_type_id" => 1,
                        //     "created_date"  => date('Y-m-d'),
                        //     "created_by" => $customerAccount->id
                        // ]);

                        return response()->json(['status' => '00', 'message' => 'Webhook handled successfully']);
                    }
                }
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }*/

    public function getTerminalBalance(Request $request)
    {
        try {
            $data = $request->all();

            // Handle VACT transfer
            if (data_get($data, 'data.transaction.type') === 'vact_transfer') {
                return $this->amuchaWebhook($request);
            }

            // Action-based flow
            if (isset($data['action_type'])) {
                return $this->handleAction($data);
            }

            // Event-based flow
            if (isset($data['event_type'])) {
                return $this->handleEvent($data);
            }

            return response()->json(['status' => false, 'message' => 'Invalid request'], 400);

        } catch (\Exception $e) {
            logInfo($e->getMessage(), "Conversion error");
            return response()->json(["status" => false, 'message' => "Failed"], 400);
        }
    }

    private function handleAction(array $data)
    {
        $terminal = $this->getTerminal($data);

        if (! $terminal) {
            return $this->invalidTerminalResponse($data['action_type']);
        }

        $user = $terminal->agent;

        if (! $this->isValidPin($user, $data)) {
            return $this->invalidPinResponse($data['action_type']);
        }

        return match ($data['action_type']) {
            'pre-balance-check-auth' => $this->handleBalanceCheck($terminal),
            'pre-payout-auth'        => $this->handlePayout($terminal, $user, $data),
            default                  => response()->json(['status' => false], 400),
        };
    }

    private function getTerminal($data)
    {
        $serial = data_get($data, 'data.terminal.serialNo');

        return AgentTerminal::where('terminal_code', $serial)->first();
    }

    private function invalidTerminalResponse($type)
    {
        return $type === "pre-payout-auth"
            ? response()->json(['data' => ['transaction' => ['shouldActionProceed' => false, 'reasonCode' => '03']]])
            : response()->json(["status" => false, 'message' => "Invalid POS terminal Code"]);
    }

    private function isValidPin($user, $data): bool
    {
        return $user->pin == data_get($data, 'data.terminal.pin');
    }

    private function handleBalanceCheck($terminal)
    {
        return response()->json([
            "data" => [
                "user" => [
                    "balance"  => number_format($terminal->balance, 2, '.', ''),
                    "currency" => "NGN",
                ],
            ],
        ]);
    }

    private function invalidPinResponse(string $actionType)
    {
        return match ($actionType) {

            'pre-balance-check-auth' => response()->json([
                "status"  => "INVALID_PIN",
                "message" => "Invalid authentication",
            ]),

            'pre-payout-auth'        => response()->json([
                'data' => [
                    'transaction' => [
                        'shouldActionProceed' => false,
                        'reasonCode'          => '05',
                    ],
                ],
            ]),

            default                  => response()->json([
                "status"  => false,
                "message" => "Invalid PIN",
            ], 400),
        };
    }

    private function handlePayout($terminal, $user, array $data)
    {
        // 1. Validate terminal status
        if ($terminal->status == 0) {
            return $this->payoutResponse(false, '02');
        }

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        if ($companySettings->pos_setting == 2) {
            return $this->payoutResponse(false, '02');
        }

        // 2. Prevent duplicate transaction
        $rrn = data_get($data, 'data.transaction.rrn');

        if (transactions::where('transaction_reference', $rrn)->exists()) {
            return $this->payoutResponse(false, '01');
        }

        // 3. Extract transaction data safely
        $amount = (float) data_get($data, 'data.transaction.amount');
        $type   = data_get($data, 'data.transaction.type');

        // 4. Calculate charges
        [$charge, $customerCharge] = $this->calculateCharges($companySettings, $amount, $type);

        // 5. Debit terminal (VERY IMPORTANT → wrap in DB transaction ideally)
        $debit = debitPOSTerminalWithCharge(
            $terminal->id,
            $amount,
            $customerCharge,
            $charge,
            $user
        );

        if (! $debit['status']) {
            return $this->handleDebitFailure($debit);
        }

        // 6. Save transaction
        $this->createTransaction($terminal, $user, $data, $debit);

        // 7. Success
        return $this->payoutResponse(true, '00');
    }

    private function payoutResponse(bool $status, string $code)
    {
        return response()->json([
            'data' => [
                'transaction' => [
                    'shouldActionProceed' => $status,
                    'reasonCode'          => $code,
                ],
            ],
        ]);
    }

    private function calculateCharges($settings, $amount, $type): array
    {
        $charge         = 25;
        $customerCharge = $settings->max_bank_transfer_charge;

        if (in_array($type, ['topup', 'data'])) {
            return [0, 0];
        }

        return [$charge, $customerCharge];
    }

    private function handleDebitFailure($debit)
    {
        return match ($debit['responseCode']) {
            0       => $this->payoutResponse(false, '07'),
            1       => $this->payoutResponse(false, '01'),
            default => $this->payoutResponse(false, '99'),
        };
    }

    private function createTransaction($terminal, $user, $data, $debit)
    {
        $account = $user->wb_account_details[0];

        $description = $this->buildDescription($data);

        transactions::create([
            "balance_before"         => $debit['balance_before'],
            "amount"                 => $debit['amount'],
            "balance_after"          => $debit['balance_after'],
            "account_id"             => $account->id,
            "description"            => $description,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debit['company_balance_before'],
            "company_amount"         => $debit['company_amount'],
            "company_balance_after"  => $debit['company_balance_after'],
            "company_id"             => $user->company_id,
            "transaction_reference"  => data_get($data, 'data.transaction.rrn'),
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 124,
            "credit_debit_type_id"   => 2,
            "created_date"           => now()->toDateString(),
            "created_by"             => $account->id,
        ]);

    }

    private function buildDescription($data): string
    {
        return "WirelessPay miniPos - You carried out " .
        data_get($data, 'data.transaction.type') . " " .
        data_get($data, 'data.transaction.customerBillerId') . " " .
        data_get($data, 'data.transaction.productId') . " " .
        data_get($data, 'data.terminal.serialNo') . " " .
        data_get($data, 'data.transaction.rrn') . " " .
        data_get($data, 'data.transaction.billerAccountName');
    }
    private function handleEvent(array $data)
    {
        if ($data['event_type'] !== 'payment_success') {
            return response()->json(['status' => '00']);
        }

        $type = data_get($data, 'data.transaction.type');

        if (! in_array($type, ['withdrawal', 'purchase'])) {
            return response()->json(['status' => '00']);
        }

        return $this->handlePaymentSuccess($data);
    }

    private function handlePaymentSuccess(array $data)
    {
        $terminal = $this->getTerminal($data);

        if (! $terminal) {
            return response()->json(['status' => '00', 'message' => 'Invalid POS Serial Number']);
        }

        // 2. Prevent duplicate
        $rrn = data_get($data, 'data.transaction.rrn');

        if (transactions::where('transaction_reference', $rrn)->exists()) {
            return response()->json(['status' => '00', 'message' => 'Transaction already exists']);
        }

        $user     = $terminal->agent;
        $settings = company_settings::where('company_id', $user->company_id)->first();

        $amount = (float) data_get($data, 'data.transaction.transactionAmount');

        // 3. Prepare shared data
        $account   = $user->wb_account_details[0];
        $branchId  = $user->branch->id;
        $companyId = $user->company_id;

        // 4. Calculate charges
        [$customerCharge, $companyCharge] = $this->calculatePaymentCharges($settings, $amount);

        // 5. Process credit (wrapped in DB transaction ⚠️)
        return \DB::transaction(function () use (
            $settings,
            $terminal,
            $user,
            $account,
            $amount,
            $customerCharge,
            $companyCharge,
            $branchId,
            $companyId,
            $data,
            $rrn
        ) {

            if ($settings->pos_setting == 2) {
                $credit = $this->creditCustomerFlow($account, $amount, $customerCharge);

                $this->storeCustomerTransaction($credit, $account, $branchId, $rrn, $data);

            } else {
                $credit = $this->creditTerminalFlow($terminal, $amount, $customerCharge, $companyCharge, $companyId);

                $this->storeTerminalTransaction($credit, $terminal, $user, $branchId, $companyId, $rrn, $data);
            }

            return response()->json(['status' => '00', 'message' => 'Webhook handled successfully']);
        });

    }

    private function calculatePaymentCharges($settings, $amount): array
    {
        $customerCharge = ($settings->additional_pos_charge_percent / 100) * $amount;

        $customerCharge = min($customerCharge, $settings->pos_charge_capped_at);

        $companyCharge = getWbaTransferCharges(39, $amount);

        return [$customerCharge, $companyCharge];
    }

    private function creditCustomerFlow($account, $amount, $charge)
    {
        $credit = creditCustomerOnly($account->id, $amount, $charge);

        if (! $credit['status']) {
            throw new \Exception('Customer credit failed');
        }

        return $credit;
    }

    private function creditTerminalFlow($terminal, $amount, $charge, $companyCharge, $companyId)
    {
        $credit = creditPOSTerminal(
            $terminal->terminal_code,
            $amount,
            $charge,
            $companyCharge,
            $companyId
        );

        if (! $credit['status']) {
            throw new \Exception('Terminal credit failed');
        }

        return $credit;
    }

    private function storeCustomerTransaction($credit, $account, $branchId, $rrn, $data)
    {
        transactions::create([
            "branch_id"             => $branchId,
            "account_id"            => $account->id,
            "description"           => "You Received funds via POS Terminal",
            "balance_before"        => $credit['balance_before'],
            "balance_after"         => $credit['pos_charge_balance_after'],
            "amount"                => $credit['amount'],
            "transaction_reference" => $rrn,
            "transaction_type_id"   => 109,
            "credit_debit_type_id"  => 1,
            "created_date"          => now()->toDateString(),
            "created_by"            => $account->id,
        ]);
    }

    private function storeTerminalTransaction($credit, $terminal, $user, $branchId, $companyId, $rrn, $data)
    {
        $description = $this->buildWebhookDescription($data);

        transactions::create([
            "balance_before"         => $credit['pos_balance_before'],
            "amount"                 => $credit['pos_amount'],
            "balance_after"          => $credit['pos_charge_balance_after'],
            "company_amount"         => $credit['company_amount'],
            "company_balance_before" => $credit['company_balance_before'],
            "company_balance_after"  => $credit['company_balance_after'],
            "description"            => $description,
            "branch_id"              => $branchId,
            "company_id"             => $companyId,
            "account_id"             => $user->wb_account_details[0]->id,
            "transaction_reference"  => $rrn,
            "transaction_type_id"    => 63,
            "credit_debit_type_id"   => 1,
            "created_date"           => now()->toDateString(),
            "created_by"             => $user->id,
        ]);
    }

    private function buildWebhookDescription($data): string
    {
        return "WirelessPay miniPos - " .
        data_get($data, 'data.transaction.transactionId') . " " .
        data_get($data, 'data.transaction.type') . " " .
        data_get($data, 'data.transaction.transactionAmount') . " " .
        data_get($data, 'data.transaction.terminalSerialNumber') . " " .
        data_get($data, 'data.transaction.rrn');
    }

    public function processOrder(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "amount"         => 'required|numeric|gt:0',
            "card_exp_month" => 'required|numeric|gt:0',
            "card_exp_year"  => 'required|numeric|gt:0',
            "card_number"    => 'required|numeric|gt:0',
            "cvv"            => 'required|numeric|gt:0',
            "card_pin"       => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        try {

            $amuchaController = new AmuchaController();

            $processOrderResult = $amuchaController->processOrder($request->amount, $request->cvv, $request->card_exp_month, $request->card_exp_year, $request->card_number, $request->card_pin);

            if (! $processOrderResult['status']) {
                return response()->json($processOrderResult, 400);
            }

            return response()->json($processOrderResult);
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }

    public function verifyCardOtp(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "order_reference" => 'required|string',
            "transaction_id"  => 'required|string',
            "otp"             => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        try {

            $amuchaController = new AmuchaController();

            $verifyCardPinResult = $amuchaController->verifyCardOtp($request->otp, $request->order_reference, $request->transaction_id);

            if (! $verifyCardPinResult['status']) {
                return response()->json($verifyCardPinResult, 400);
            }

            return response()->json($verifyCardPinResult);
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }

    public function checkCustomerBalance($amount, $selectedwallet = null)
    {

        $response = [];

        if ($amount > 0) {
            $selectedwb_wallet = $selectedwallet ?? auth()->user()->accounts->first()->id;
            $userWallet        = user_wallet::where('wb_account_detail_id', $selectedwb_wallet)->first();

            \logInfo($userWallet->balance, "userWallet balance from $selectedwb_wallet");

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

            \logInfo(auth()->user()->accounts->first()->id, "wb_account_detail_id");

            \logInfo($userWallet->balance, "userWallet balance from checkCustomerAndCompanyBalance");

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

    public function verifyCgateDetails(Request $request)
    {

        $cgateController = new CgateController();

        $response = [];

        $verifyHeader = $cgateController->verifyCgateIncomingHeader($request);

        if ($verifyHeader['status'] != '00') {
            return response()->json($verifyHeader, 400);
        }

        if (strlen($request->customerRef) != 5 && strlen($request->customerRef) != 10) {
            return response()->json(["status" => "02", 'message' => "Invalid Customer Reference Format"], 400);
        }

        if (strlen($request->customerRef) == 5) {
            $accountCode = (int) $request->customerRef;

            $adminUser = company_wallet::where('company_id', $accountCode)->first();

            if (! $adminUser) {

                // $forwardRequest = forwardRequestToWirelesspay('user/ussd/create', $request->all());

                return $forwardRequest;
            }

            $response = [
                'responseCode'   => "00",
                "customerName"   => $adminUser->company->company_name,
                "displayMessage" => $adminUser->company->company_name,
            ];
        } else {
            $loanRecord = loan::where('ref', $request->customerRef)->first();

            if (! $loanRecord) {

                $adminUser = wb_account_details::where('virtual_accnum', $request->customerRef)->first();

                if (! $adminUser) {
                    return response()->json(["status" => "01", 'message' => "Invalid Customer Reference"], 400);
                }

                return response()->json([
                    'responseCode'   => "00",
                    "customerName"   => $adminUser->user->first_name . " " . $adminUser->user->last_name,
                    "displayMessage" => $adminUser->user->first_name . " " . $adminUser->user->last_name,
                ]);
            }

            $amount = $loanRecord->loan_status_id == 10 ? 0.0 : ($loanRecord->EachRepayment <= $loanRecord->loanBalance ? $loanRecord->EachRepayment : $loanRecord->loanBalance);

            $response = [
                'responseCode'      => "00",
                "customerName"      => "Loan with " . $loanRecord->user->company->company_name,
                "displayMessage"    => "Loan with " . $loanRecord->user->company->company_name,
                "amount"            => $amount . "",
                "subMerchantName"   => null,
                "passBackReference" => null,
                "billerCode"        => null,
                "billerPrefix"      => null,
                "traceId"           => null,
            ];
        }

        return response()->json($response);
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

                    $userWallet->balance    -= $amount;
                    $userWallet->updated_at  = Carbon::now();
                    $userWallet->save();

                    $request->user()->company->wallet->balance    -= $amount;
                    $request->user()->company->wallet->updated_at  = Carbon::now();
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
        $accountTier     = auth()->user()->tier;
        $wbaAccount      = auth()->user()->accounts->first();

        if (! $companySettings) {
            return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        if ($amount < $accountTier->minimum_transfer) {
            return ["status" => false, 'message' => "Minimum amount allowed is NGN$accountTier->minimum_transfer"];
        }

        // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => auth()->user()->accounts->first()->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
        $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->sum('amount');

        $lastTransaction = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->whereDate('created_at', Carbon::today())->orderBy('id', 'DESC')->first();

        if ($lastTransaction) {
            if ($lastTransaction->created_at > Carbon::now()->subSeconds(60)) {
                return ["status" => false, 'message' => "Cannot make multiple transactions within 1 minute"];
            }

            if ($dailySum + $amount > $accountTier->daily_transaction_limit) {
                return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
            }
            if ($sum >= $accountTier->daily_transaction_count) {
                return ["status" => false, 'message' => "Daily Transaction Count Exceeded"];
            }
        }

        return ["status" => \true, 'message' => "Transaction Validated"];
    }

    public function accountProductCharges($amount)
    {
        $wbaAccount = auth('api')->user()->accounts->first();

        logInfo(auth('api')->user()->id, "wbaAccount account id");

        logInfo($wbaAccount->account_product_id, "wbaAccount accountProductCharges");

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        logInfo($accoountProduct->id, "wbaAccount accountProduct");

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $wbaAccount->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($accoountProduct->minimum_balance, "accountProduct minimum_balance");

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        logInfo($amountLeft, "Amount left after Deduction");
        logInfo($accoountProduct->minimum_balance, "Amount left after Deduction");

        if ($amount < $accoountProduct->minimum_withdraw) {
            return ["status" => false, 'message' => "Transfer Amount cannot be less than $accoountProduct->minimum_withdraw"];
        }

        if ($amount > $accoountProduct->max_withdraw) {
            return ["status" => false, 'message' => "Transfer Amount cannot exceed $accoountProduct->max_withdraw"];
        }

        if ($accoountProduct->bank_transfer_charge_type == 1) {
            $charge = $amount * ($accoountProduct->percentage_transfer_charge / 100);

            $charge = $charge > $accoountProduct->max_bank_transfer_charge ? $accoountProduct->max_bank_transfer_charge : $charge;
        } else {
            $charge = $accoountProduct->max_bank_transfer_charge;
        }

        return ["status" => true, 'message' => "Savings products validated", 'data' => ['charge' => $charge]];
    }

    public function buyAirtime(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required|string",
            "transaction_pin"  => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
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

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        /* if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $checkCustomerAndCompanyBalanceBeforeBuyAirtime = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0, 0.01);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyAirtime['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyAirtime['message'], 400);
        }

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        //   $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

        $company_debit_bankcode = $this->getCompanyBankCode();

        $checkVTPASSBalance = match ($company_debit_bankcode) {

            90286 => true, //7000299948
            default
                  => $this->checkVTPASSBalance($request->final_amount),
        };

        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 11, "Buy Airtime Initialized");

        //($customerAccountId, $(int) $company_id;companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
        // $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.05, 0.015);

        logInfo($company_debit_bankcode, "company_debit_bankcode");

        $debitCustomer = match ($company_debit_bankcode) {

            90286 => \debitCustomerAndCompanyWithCompanyCashbackServiceWallet(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.05, 0.015), //7000299948
            default
                  => \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.05, 0.015),
        };

        if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }

        $buyAirtime = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyAirTime', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyAirTime', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        if ($buyAirtime["status"] != false) {

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(11, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }*/

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
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
                        "description"            => "Purchase of Airtime worth $request->final_amount for $request->phone_number",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 11,
                        "credit_debit_type_id"   => 2,

                    ]);

                    $cashbackTransactionId = \generate_txnID();
                    $ledger_cr             = null;
                    $ledger_dr             = null;
                    $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
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
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer["balance_after"]);

                    $subtext = "Airtime Purchase Successful";
                    $reason  = "";
                    $msg     = "Purchase of Airtime worth $request->final_amount for $request->phone_number";
                    $user    = auth()->user();
                    $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
                    //   spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json(
                        [
                            "status"  => true,
                            'message' => "Airtime Purchase Successful",
                            'data'    => [
                                'balance' => $debitCustomer['balance_after'],
                            ],
                        ]
                    );
                }
            }

        } else {
            return response()->json(["status" => false, 'message' => "Failed to purchase airtime. Kindly contact admin for assistance if you have been debited", 'data' => $transactionId], 400);
        }
    }

    public function getCompanyBankCode()
    {
        $company_bank_id         = company_wallet::where('company_id', auth()->user()->company_id)->first()->bank_id;
        $bank                    = banks::find($company_bank_id);
        $company_debit_bank_code = (int) $bank->bank_code;
        return $company_debit_bank_code;
    }

    public function buyDataBundle(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required",
            "data_plan"        => "required",
            "transaction_pin"  => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
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

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        /* if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        // $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

        $company_debit_bankcode = $this->getCompanyBankCode();

        $checkVTPASSBalance = match ($company_debit_bankcode) {

            90286 => true, //7000299948
            default
                  => $this->checkVTPASSBalance($request->final_amount),
        };

        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getDataBundlesList', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getDataBundlesList', [
                'request' => $request,

            ]),
        };

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

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->network_provider,
            "variation_code" => $request->data_plan,
            "amount"         => $selectedDataBundle['variation_amount'],
            "phone"          => $request->phone_number,
        ];

        $checkCustomerAndCompanyBalanceBeforeBuyAirtime = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0, 0.01);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyAirtime['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyAirtime['message'], 400);
        }

        $endpoint = "pay";

        $selectedDataBundleDescription = $selectedDataBundle['variation_amount'];
        $initialdescriptionCTUP        = "Buy Data Bundle Initialized - $selectedDataBundleDescription";

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 12, $initialdescriptionCTUP);

        //($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
        // $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.05, 0.015);

        $debitCustomer = match ($company_debit_bankcode) {

            90286 => \debitCustomerAndCompanyWithCompanyCashbackServiceWallet(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.05, 0.015), //7000299948
            default
                  => \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.05, 0.015),
        };

        if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }

        $buyData = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyData', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyData', [
                'request'            => $request,
                'transactionId'      => $transactionId,
                'selectedDataBundle' => $selectedDataBundle,
            ]),
        };

        if ($buyData["status"]) {

/*
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(12, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
*/
            if (! $debitCustomer['status']) {
                response()->json($debitCustomer, 400);
            }

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $selectedDataBundle['variation_amount'],
                        "balance_after"          => $debitCustomer["balance_after"],
                        "company_balance_before" => $debitCustomer["company_balance_before"],
                        "company_balance_after"  => $debitCustomer["company_balance_after"],
                        "company_amount"         => $debitCustomer["company_amount"],
                        "company_id"             => auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "account_id"             => $transactionPinValidation['account_id'],
                        "description"            => "Purchase of Data worth " . $selectedDataBundle['variation_amount'] . " for $request->phone_number",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 12,
                        "credit_debit_type_id"   => 2,

                    ]);

                    $cashbackTransactionId = \generate_txnID();
                    $ledger_cr             = null;
                    $ledger_dr             = null;
                    $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
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
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after']);

                    $subtext = "Data Purchase Successful";
                    $reason  = "";
                    $msg     = "Purchase of Data worth " . $selectedDataBundle['variation_amount'] . " for $request->phone_number";
                    $user    = auth()->user();
                    $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
                    // spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json(
                        [
                            "status"  => true,
                            'message' => "Data Plan Purchase Successful",
                            'data'    => [
                                'balance' => $debitCustomer['balance_after'],
                            ],
                        ]
                    );
                }

            }

        } else {
            return response()->json(["status" => false, 'message' => $buyData['message']], 400);
        }
    }

    public function debitCustomeFromApi(Request $request)
    {

        $transaction = transactions::where("transaction_reference", $request->transaction_reference)->first();

        if (! $transaction) {
            return response()->json(['status' => false, 'message' => 'Transaction record not found'], 400);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => false, 'message' => 'Transaction has already been reversed'], 400);
        }

        if ($transaction->transaction_type_id != 6) {
            return response()->json(['status' => false, 'message' => 'Transaction is not a deposit transaction'], 400);
        }

        $adminUser = User::find($transaction->created_by);

        if (! $adminUser) {
            return response()->json(['status' => false, 'message' => "Admin user not found $transaction->company_id"], 400);
        }

        logInfo($adminUser->branch_id);

        if ($transaction->account_id != null) {
            $debitCustomer = \debitCustomerAndCompanyWithCharge($transaction->account_id, $transaction->company_id, $transaction->amount, 50, 0, $adminUser);

            if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }

            $newtransaction = transactions::create([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $transaction->amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "account_id"             => $transaction->account_id,
                "company_id"             => $transaction->company_id,
                "branch_id"              => $adminUser->branch_id,
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Error Bank transaction Depot reversal",
                "transaction_reference"  => generateTransactionReference(),
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 27,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
            ]);
        } else {

            $debitCustomer = debitCompanyWithCharge($transaction->company_id, $transaction->amount, 50, $adminUser);

            if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }

            $newtransaction = transactions::create([
                "balance_before"         => 0,
                "amount"                 => 0,
                "balance_after"          => 0,
                "description"            => "Error  Bank transaction Depot reversal",
                "branch_id"              => $adminUser->branch_id,
                "company_id"             => $transaction->company_id,
                "company_balance_before" => $debitCustomer['balance_before'],
                "company_balance_after"  => $debitCustomer['balance_after'],
                "company_amount"         => $request->amount,
                "transaction_reference"  => generateTransactionReference(),
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 8,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
            ]);
        }

        $transaction->transaction_remarks_id = 5;
        $transaction->save();

        return \response()->json($newtransaction);
    }

    public function buyElectricity(Request $request)
    {
        // $transactionId = $this->randomNumber(12);
        logInfo(json_encode($request->all()), "buyElectricity Response");

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"      => "required|numeric|gt:0",
            "phone_number"      => "required|numeric|gt:0",
            "meter_number"      => "required|numeric|gt:0",
            "electricity_disco" => "required",
            "meter_type"        => "required",
            "transaction_pin"   => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(13, $request->final_amount);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $accountId = auth()->user()->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $request->final_amount;

        /* $checkCompanyBalance = checkCompanyBalance($checkCompanyBalanceAmount);

        if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $checkCustomerAndCompanyBalanceBeforeBuyElectricity = $this->checkCustomerAndCompanyBalanceBeforeBuyElectricity($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyElectricity['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyElectricity['message'], 400);
        }

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        //  $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

        $company_debit_bankcode = $this->getCompanyBankCode();

        $checkVTPASSBalance = match ($company_debit_bankcode) {

            90286 => true, //7000299948
            default
                  => $this->checkVTPASSBalance($request->final_amount),
        };

        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 14, "Buy Electricity Initialized");

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->electricity_disco,
            "variation_code" => $request->meter_type,
            "billersCode"    => $request->meter_number,
            "amount"         => $request->final_amount,
            "phone"          => $request->phone_number,
        ];

        /*   if (\auth()->user()->company_id == 80) {
            $remitaController = new RemittaController();

            $buyElectricity = $remitaController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number
            );

            logInfo($buyElectricity, "Remita Electricity Response");

        } else {

            $buyElectricity = match ($company_debit_bankcode) {

                90286 => $this->safeHavenControllerSercice('buyElectricity', [
                    'request'       => $request,
                    'transactionId' => $transactionId,
                ]), //7000299948
                default
                      => $this->vtPassControllerSercice('buyElectricity', [
                    'request'       => $request,
                    'transactionId' => $transactionId,
                ]),
            };

        }*/

        $buyElectricity = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyElectricity', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyElectricity', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        if (! $buyElectricity['status']) {
            return response()->json($buyElectricity, 400);
        } else {
            //     $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

            $debitCustomer = match ($company_debit_bankcode) {

                90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge), //7000299948
                default
                      => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge),
            };
            /*   if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }*/

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(14, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
                */
            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
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

                    ]);
                    $transactionChargeId = \generate_txnID();
                    $ledger_cr           = null;
                    $ledger_dr           = null;
                    $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    // Save Charges to transaction table
                    $chargetransaction = transactions::create([
                        "balance_before"         => $debitCustomer['charge_balance_before'],
                        "amount"                 => $companySettings->bills_payment_charge,
                        "balance_after"          => $debitCustomer["charge_balance_after"],
                        "account_id"             => $transactionPinValidation['account_id'],
                        "company_id"             => \auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "company_balance_before" => $debitCustomer["company_charge_balance_before"],
                        "company_amount"         => $wbaCharge,
                        "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
                        "description"            => "Charge for Electricity Purchase",
                        "transaction_reference"  => $transactionChargeId,
                        "parent_transaction_id"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 51,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $electricityToken = $buyElectricity['data']['token'];

                    $transaction->transaction_remarks_id = 2;
                    $transaction->description            = "Purchased Electricity Token: $electricityToken Amount: $request->final_amount";
                    $transaction->save();

                    $buyElectricity['data']['balance'] = $debitCustomer["charge_balance_after"];

                    sendSMS(auth()->user()->id, "Purchased Electricity Token $electricityToken, Amount: $request->final_amount, Avail Bal: " . $debitCustomer["charge_balance_after"], true, true, 'Electricity Token');
// spendSaveRecordTransaction($debitCustomer, $transaction);
                    return response()->json($buyElectricity);
                }
            }

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
            "transaction_pin"   => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(12, ($request->amount / 100));

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        /*  $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }
            */

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        $company_debit_bankcode = $this->getCompanyBankCode();

        $checkVTPASSBalance = match ($company_debit_bankcode) {

            90286 => true, //7000299948
            default
                  => $this->checkVTPASSBalance($request->final_amount),
        };

        // $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);
        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 13, "Subscribe TV Initialized");

        //  $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

        $debitCustomer = match ($company_debit_bankcode) {

            90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge), //7000299948
            default
                  => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge),
        };

        if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }

        $subscribeCableTV = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('subscribeCableTV', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('subscribeCableTV', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        logInfo($subscribeCableTV, "VTPass Subscribe Cable TV Log");

        if ($subscribeCableTV['status']) {

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(13, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }*/

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();

            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([

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

                    ]);
                    $transactionChargeId = \generate_txnID();
                    $ledger_cr           = null;
                    $ledger_dr           = null;
                    $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
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
                        "description"            => "Charge for Cable TV Subscription",
                        "transaction_reference"  => $transactionChargeId,
                        "parent_transaction_id"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 51,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_before'], false);

                    $subscribeCableTV['data']['balance'] = $debitCustomer['charge_balance_after'];

                    spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json($subscribeCableTV);
                }

            }

        } else {
            return response()->json($subscribeCableTV, 400);
        }
    }

    public function verifyBVN(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bvn" => 'sometimes|numeric|gt:0',
            "dob" => 'sometimes',
            'nin' => 'sometimes',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "BVN and Date of birth is required"];
            return response()->json($ra, 406);
        }

        $companyId = auth()->user()->company->id ?? $request->company_id;

        logInfo($companyId, "verifyBVN company id");

        $bankID = company_wallet::where('company_id', $companyId)->first()->bank_id;

        logInfo($bankID, "verifyBVN bank id");

        $company_bank_id = (int) $bankID;

        return match ($company_bank_id) {

            190 => $this->verifyIdentityWithSafeHaven($request), //7000299948
            default
                => $this->debitCompanyWithSubscriptionBVN($request, $companyId),
        };

    }

    public function debitBVNFromCompany($debitCompanyWithCharge, $companyId, $charge)
    {
        $companyBalanceBefore       = $debitCompanyWithCharge['balance_before'];
        $companyBalanceAfter        = $debitCompanyWithCharge['balance_after'];
        $companyChargeBalanceBefore = $debitCompanyWithCharge['charge_balance_before'];
        $companyChargeBalanceAfter  = $debitCompanyWithCharge['charge_balance_after'];

        $transactionRef2 = generateTransactionReference();

        transactions::create([
            "company_balance_before" => $companyChargeBalanceBefore,
            "company_amount"         => 0,
            "account_id"             => null,
            "company_balance_after"  => $companyChargeBalanceAfter,
            "description"            => "Charge for BVN Check",
            "transaction_reference"  => $transactionRef2,
            "balance_before"         => $companyBalanceBefore,
            "amount"                 => $charge,
            "balance_after"          => $companyBalanceAfter,
            "company_id"             => $companyId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 43,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $companyId,

        ]);

    }

    public function verifyWithIdentityPass(Request $request)
    {
        $identityPassController = new IdentityPassController();

        $bvnResponse = $identityPassController->verifyBVNMobile($request->bvn);

        $time = strtotime($request->dob);

        if (isset($bvnResponse['data']['verification']['status']) && $bvnResponse['data']['verification']['status'] == "VERIFIED") {
            $firstName       = $bvnResponse['data']['data']["firstName"];
            $lastName        = $bvnResponse['data']['data']["lastName"];
            $dob             = $bvnResponse['data']['data']["dateOfBirth"] ?? $bvnResponse['data']['data']["dob"];
            $bvnFromResponse = $request->bvn;
            $phone           = $bvnResponse['data']['data']["phoneNumber"] ?? null;
            $address         = $bvnResponse['data']['data']["residentialAddress"] ?? null;

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
                            "address"    => trim($address),
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

    public function debitCompanyWithSubscriptionBVN(Request $request, $companyId)
    {
        $charge = 50;

        logInfo($companyId, "Debit company id");

        $debitCompany = debitCompanyWithSubscriptionBalance($companyId, $charge, 0, );

        if (! $debitCompany['status']) {
            return response()->json(['status' => false, 'message' => 'Switcher Error. Kindly contact support'], 400);
        }

        logInfo($debitCompany, "Debit company log");

        $this->debitBVNFromCompany($debitCompany, $companyId, $charge);

        $time = strtotime($request->dob);

        $this->verifyWithIdentityPass($request);
    }

    public function verifyIdentityWithSafeHaven(Request $request)
    {
        return $this->safeHavenIdentityCheckResponse($request);

    }

    public function safeHavenIdentityCheckResponse(Request $request)
    {

        $safehavenController = new SafehavenController();
        if ($request->has('nin')) {
            $bvnResponse = $safehavenController->verifyNIN($request->nin, $request->company_id);
        } else {
            $bvnResponse = $safehavenController->verifyBVN($request->bvn, $request->company_id);
        }

        if ($bvnResponse['status']) {
            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'id'             => $bvnResponse['data']['_id'],
                        'clientId'       => $bvnResponse['data']['clientId'],
                        "identityNumber" => $bvnResponse['data']['identityNumber'],
                        "type"           => $bvnResponse['data']['type'],
                        'otpId'          => trim($bvnResponse['data']['otpId']),
                        "message"        => trim($bvnResponse['message']),

                    ],

                ]
            );
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

    public function verifyBVNAlt(Request $request, $bvn)
    {
        if (! $bvn) {
            $ra = ["status" => false, "message" => "BVN is required"];
            return response()->json($ra, 406);
        }

        $safeHavenController = new SafehavenController();

        $result = $safeHavenController->verifyBVN($bvn);

        return response()->json($result, $result['status'] ? 200 : 400);
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

        $company_debit_bankcode = $this->getCompanyBankCode();

        $verifyMeterResult = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('verifyMeterNumber', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('verifyMeterNumber', [
                'request' => $request,

            ]),
        };

        if (! $verifyMeterResult['status']) {
            return response()->json($verifyMeterResult, 400);
        }
        return response()->json($verifyMeterResult);
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

        $company_debit_bankcode = $this->getCompanyBankCode();

        $verifyCableTV = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('verifyCableTV', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('verifyCableTV', [
                'request' => $request,

            ]),
        };

        if (! $verifyCableTV["status"]) {
            return response()->json($verifyCableTV, 400);
        }
        return response()->json($verifyCableTV);
    }

    public function getAllBanks(Request $request)
    {
        $type = $request->has('type') ? $request->type : 'long';

        $banks = \getBanksFromDB($type);

        return response()->json(['status' => true, 'message' => 'Banks fetched successfully', 'data' => $banks]);
    }

    public function transactionHistory(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 100;

        $transactions = transactions::where(['account_id' => $request->user()->accounts->first()->id]);
        // exclude bank transfer,sms charges,registration,
        $hidecharge = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? '1';

        $hideChargeLog = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? "1";

        \logInfo($hideChargeLog, "Hide Charge ");

        if ($hidecharge == '2') {
            // $get_list_of_excluded_transaction_ids = getTransactionIdByName(['USSD Session Charge','Physical Card Funding Charge','Virtual Card Funding Charge','USSD Wallet Funding Charge','Customer Whatsapp Charge','Company Whatsapp Charge','POS Credit Charge']);
            //$get_list_of_excluded_transaction_ids = [108, 107, 100, 99, 92, 91, 76, 62, 58, 55, 54, 51, 42, 41, 39, 38, 33, 27, 6];
            $get_list_of_excluded_transaction_ids = [125, 108, 107, 100, 99, 92, 91, 76, 55, 51, 27];
            $transactions                         = $transactions->whereNotIn('transaction_type_id', $get_list_of_excluded_transaction_ids);
        }
        if ($request->from != null && $request->to != null) {
            $transactions = $transactions->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to);

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();

            $transactions = $transactions->map(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; // 👈 add your parameter
                return $transaction;
            });
        } else {
            $transactions = $transactions->orderBy('created_at', 'DESC')->paginate($pageLimit);
            $transactions->getCollection()->transform(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; //add your parameter
                return $transaction;
            });
        }

//ransactionResource::collection($transactions)
        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions], 200);
    }

    public function transactionHistoryMobile(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 100;

        $transactions = transactions::where(['account_id' => $request->user()->accounts->first()->id]);
        // exclude bank transfer,sms charges,registration,
        $hidecharge = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? '1';

        $hideChargeLog = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? "1";

        \logInfo($hideChargeLog, "Hide Charge ");

        if ($hidecharge == '2') {
            // $get_list_of_excluded_transaction_ids = getTransactionIdByName(['USSD Session Charge','Physical Card Funding Charge','Virtual Card Funding Charge','USSD Wallet Funding Charge','Customer Whatsapp Charge','Company Whatsapp Charge','POS Credit Charge']);
            //$get_list_of_excluded_transaction_ids = [108, 107, 100, 99, 92, 91, 76, 62, 58, 55, 54, 51, 42, 41, 39, 38, 33, 27, 6];
            $get_list_of_excluded_transaction_ids = [125, 108, 107, 100, 99, 92, 91, 76, 55, 51, 27];
            $transactions                         = $transactions->whereNotIn('transaction_type_id', $get_list_of_excluded_transaction_ids);
        }
        if ($request->from != null && $request->to != null) {
            $transactions = $transactions->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to);

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();

            $transactions = $transactions->map(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; // 👈 add your parameter
                return $transaction;
            });
        } else {
            return response()->json(['status' => true, 'message' => 'Transactions fetched failed', 'data' => "error"], 400);
        }

//ransactionResource::collection($transactions)
        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions], 200);
    }

    public function posTransactionHistory(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 20;

        $userId = auth()->user()->id;

        if ($request->from != null && $request->to != null) {
            $transactions = transactions::where('description', 'like', '%WirelessPay miniPos%')
                ->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to)
                ->where(function ($query) use ($userId) {
                    $query->where('created_by', $userId)
                        ->orWhere('account_id', $userId);
                });

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();
        } else {

            $transactions = transactions::where('description', 'like', '%WirelessPay miniPos%')
                ->where(function ($query) use ($userId) {
                    $query->where('created_by', $userId)
                        ->orWhere('account_id', $userId);
                })->orderBy('created_at', 'DESC')->paginate($pageLimit);
        }

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function getDataBundles($networkProvider)
    {
        $company_debit_bankcode = $this->getCompanyBankCode();

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]),
        };

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function getDataBundlesList($networkProvider)
    {
        $company_debit_bankcode = $this->getCompanyBankCode();

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]),
        };

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function getSavingsProducts()
    {
        $savingsProduct = savings_product::where('company_id', auth()->user()->company_id)->get();

        if ($savingsProduct) {
            return ['status' => true, 'message' => 'Savings Products fetched successfully', 'data' => $savingsProduct];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Savings', 'data' => []];
        }
    }

    public function getSubscriptions(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "cableTvType" => 'nullable',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        logInfo($request['cableTvType'], "VTPass Subscribe tv request 1");

        logInfo($request->input('cableTvType'), "VTPass Subscribe tv request 2");

        $cableTV = $request['cableTvType'] ?? $request->query('cableTvType');

        $company_debit_bankcode = $this->getCompanyBankCode();

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getSubscriptions', [
                'cableTV' => $cableTV,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getSubscriptions', [
                'cableTV' => $cableTV,

            ]),
        };

        if (! $dataBundles['status']) {
            return response()->json($dataBundles, 400);
        }

        return response()->json($dataBundles);
    }

    public function getBettingCompanies(Request $request)
    {

        $cableTV = $request['cableTvType'];

        $endpoint = "betting";

        return response()->json(['status' => false, 'message' => 'This service is unavailable at the moment'], 400);

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $bettings = \getRequest([], [], "/get_subscriptions.php?is_demo=$isDemo&api_exchange_key=$apiExchangeKey");

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

                    \logInfo($response->json(), "Forwarded webhook Response");
                } else {
                    \logInfo("Not sending wehook from settings");
                }
            } else {
                \logInfo("Not sending wehook");
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
                'api_secret:  00',
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
                ["id" => "aba-electric", "name" => "ABA Electricity"], ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "benin-electric", "name" => "Benin Electricity"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "kaduna-electric", "name" => "Kaduna Electricity"], ["id" => "yola-electric", "name" => "YOLA Electricity"],

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
                        ["id" => "aba-electric", "name" => "ABA Electricity"], ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "benin-electric", "name" => "Benin Electricity"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "kaduna-electric", "name" => "Kaduna Electricity"], ["id" => "yola-electric", "name" => "YOLA Electricity"],

                    ], 'type' => [
                        ["id" => "prepaid", "name" => "Prepaid"],
                        ["id" => "postpaid", "name" => "Postpaid"],
                    ],

                ],
            ]
        );
    }

    public function getBets(Request $request)
    {

        $kudaController = new KudaController();

        $kudaController->transactionReference = "";
        $kudaController->wbaCharge            = "";
        $kudaController->virtualAccount       = "";
        $kudaController->amount               = "";
        $kudaController->webhookType          = "";
        $kudaController->description          = "";
        $kudaController->transactionDate      = "";
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = "";

        $getBetsResult = $kudaController->getBet();

        if (! $getBetsResult['status']) {
            return response()->json($getBetsResult, 400);
        }
        return response()->json($getBetsResult);
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
        if ($request->is_agent != null) {
            $transaction = transactions::whereHas('beneficiary', function ($q) {
                $q->whereHas('user', function ($e) {
                    $e->where(['agent_id' => auth()->user()->id]);
                });
            })->where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->with('beneficiary')->first();
        } else {
            $transaction = transactions::whereHas('beneficiary', function ($q) {
                $q->where(['user_id' => auth()->user()->id]);
            })->where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->with('beneficiary')->first();
        }

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
    public function txnInfoTwo(Request $request)
    {
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->first();
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

    public function buyAirtimeTest(Request $request)
    {
        $type           = "electricity";
        $network        = $request->network;
        $accountName    = auth()->user()->first_name;
        $originalAmount = $request->amount;
        return $this->checkVTPASSBalance($originalAmount);
        $kudaController = new KudaController();
        return $kudaController->getBiller($type, $network);
    }

    public function twilioWebhook(Request $request)
    {
        \logInfo($request->all(), "Twilio Response Message");
        $twilioWebHook = new TwilioWhatsapp();
        return $twilioWebHook->getUpdates($request);
    }

    public function finovaWebhook(Request $request)
    {

        $secret         = 'finova-my-secret';
        $receivedSecret = $request->header('Signature');

        // Optional: log the full webhook
        \logInfo($request->all(), "Finova Received Webhook");

        // Example logic
        if ($request->has('data')) {
            // Process the payload
            $data = $request->input('data');

            \logInfo($data, "Finova ebhook Data Received:");
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function testAPIExternal(Request $request)
    {
        $remitaController = new RemittaController();

        //   return $remitaController->getElectricityCategory();
        $electrictyDisco = null;
        $transactionId   = generateTransactionReference();
        $meterType       = "prepaid";
        $meterNumber     = "45702489712";
        $amount          = 500;
        $phoneNumber     = "08067058555";
        return $remitaController->buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber);

    }

    public function finova4tsWebhook(Request $request)
    {

        $secret         = 'finova-my-secret';
        $receivedSecret = $request->header('x-fortify-secret');

        if ($receivedSecret != null) {
            // Optional: log the full webhook
            \logInfo($request->all(), "4ts Finova Received Webhook");
            $amount               = $request->amount;
            $virtualAccount       = $request->account_number;
            $wb_account_detail_id = wb_account_details::where('virtual_accnum', $virtualAccount)->first();
            $acoountID            = $wb_account_detail_id->id;
            $user                 = User::where('id', $wb_account_detail_id->user_id)->first();
            $transactionService   = new fortifyTransactionService();
            $debitCustomer        = $transactionService->directDebitMandate($amount, $user);
            if (! $debitCustomer['status']) {
                response()->json("Could not debit cuustomer", 400);
            } else {

                return response()->json(['status' => 'ok', "message" => $debitCustomer], 200);
            }

        }

    }

    public function bridgeWebhook(Request $request)
    {

        $bridgeController = new BridgeController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        $handleWebhook = $bridgeController->handleBridgeWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Sudo result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);

    }

    public function ninetyPSBWebhook(Request $request)
    {

        $ninePSBController = new NinePSBController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $ninePSBController->handleNinePSBWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Ninepsb result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);

    }

    public function reportTransaction(Request $request)
    {
        \logInfo(json_encode($request->ref_id), "Report transaction ref_id :");
        \logInfo(json_encode($request->reason), "Report transaction  reason:");
        $transaction = transactions::where(['transaction_reference' => $request->ref_id])->first();
        \logInfo(json_encode($transaction), "Report transaction :");
        $company = Company::where('id', $transaction->company_id)->first();
        $user    = User::where('company_id', $company->id)->where('user_type_id', 2)->first();
        $data    = [
            'subtext' => "Transaction issue for {$request->ref_id}",
            'reason' => $request->reason,
            'desc'   => "There is an issue with a transaction , {$request->ref_id}. ",
            'company_name' => $company->company_name,
            'user' => $company->company_name,
        ];
        \logInfo("$user->email", "Mail from user ");
        // initSMTPconfiguration($company->companyEmail);
        $subtext = "Transaction issue for {$request->ref_id}";
        $reason  = $request->reason;
        $msg     = "There is an issue with a transaction , {$request->ref_id}. ";
        $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
        return response()->json(['status' => 'ok'], 200);
    }

    public function paystackWebhook(Request $request)
    {
        $paystack = new PaystackService;
        $paystack->handleWebhook($request);
    }

    public function vtpassWebhook(Request $request)
    {
        $vtPassController = new VTPassController();
        $vtPassController->handleWebhook($request);
    }

    public function checkVTPASSBalance($originalAmount)
    {
        $vtPassController = new VTPassController();
        $response         = $vtPassController->checkVTPASSBalance($originalAmount);
        if ($originalAmount > $response['balance']) {
            return false;
        } else {
            return true;
        }
    }

    public function ninetyPSBTestHash(Request $request)
    {

        $ninePSBController = new NinePSBController();
        return $ninePSBController->getHashed($request);
    }

    public function checkCustomerAndCompanyBalanceBeforeBuyAirtime(
        $customerAccountId,
        $companyId,
        $originalAmount,
        $customerCashback = 0,
        $companyCashback = null,
        $user = null
    ) {
        $response = [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "An unexpected error occurred",
        ];

        // Determine user
        $currentUser = Auth::check() ? auth()->user() : $user;

        if ($originalAmount <= 0) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid transaction amount",
            ];
        }

        // Get customer wallet
        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

        if (! $userWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid customer provided",
            ];
        }

        $customerInitialBalance = $userWallet->balance;

        // Compute cashback values (safe defaults)
        $customerCashbackAmount = (float) $customerCashback * $originalAmount;
        $companyCashbackAmount  = (float) $companyCashback * $originalAmount;

        // ✅ Ensure customer has enough to pay after cashback
        if ($customerInitialBalance < ($originalAmount - $customerCashbackAmount)) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Insufficient customer balance",
            ];
        }

        // Fetch company and check its wallet
        $company = Company::find($companyId);

        if (! $company || ! $company->wallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid company or wallet not found",
            ];
        }

        $companyIDs       = [1, 29, 36, 63, 67, 92, 97];
        $companyIDsStatus = in_array($company->id, $companyIDs);

        $companyAfterBalance = $company->wallet->balance - $originalAmount;

        logInfo(json_encode($companyAfterBalance), "checkCustomerAndCompanyBalanceBeforeBuyAirtime Company after balance for cash back");

        // ✅ Logic for switcher company wallet balance
        if ($company->wallet->balance > 100000 && $companyAfterBalance > 100000) {
            $companyIDsStatus = true;
        }

        if ($company->wallet->balance > 100100 || $companyIDsStatus) {
            return [
                "status"       => true,
                "responseCode" => 1,
                "message"      => "Amount is valid",
            ];
        }

        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Switcher-related error... Please contact support",
        ];
    }

    public function checkCustomerAndCompanyBalanceBeforeBuyElectricity(
        $customerAccountId,
        $companyId,
        $originalAmount,
        $customerCharge,
        $companyCharge,
        $user = null
    ) {
        logInfo($customerAccountId, "checkCustomerAndCompanyBalanceBeforeBuyElectricity customerAccountId");
        logInfo($companyId, "checkCustomerAndCompanyBalanceBeforeBuyElectricity companyId");

        // Default response (prevents returning null)
        $response = [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Unknown error occurred",
        ];

        // Resolve user
        $currentUser = Auth::check() ? auth()->user() : $user;

        // Validate amount
        if ($originalAmount <= 0) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid transaction amount",
            ];
        }

        // Lock customer wallet for concurrency safety
        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)
            ->lockForUpdate()
            ->first();

        if (! $userWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid customer provided",
            ];
        }

        logInfo(json_encode($userWallet), "Customer wallet to be debited");

        $customerInitialBalance = $userWallet->balance;
        $totalDebit             = $originalAmount + $customerCharge;

        logInfo($originalAmount, "Original Amount");
        logInfo($customerCharge, "Customer Charge");

        // ✅ Step 1: Ensure customer has sufficient balance
        if ($customerInitialBalance < $totalDebit) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Insufficient customer balance",
            ];
        }

        // ✅ Step 2: Get company wallet
        $company = Company::find($companyId);

        if (! $company) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid company specified",
            ];
        }

        $companyWallet = company_wallet::where('company_id', $companyId)->lockForUpdate()->first();

        if (! $companyWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Company wallet not found",
            ];
        }

        logInfo(json_encode($companyWallet), "Company wallet to be debited");

        $companyIDs       = [1, 29, 36, 63, 67, 92, 97];
        $companyIDsStatus = in_array($company->id, $companyIDs);

        $companyAfterBalance = $companyWallet->balance - ($originalAmount + $companyCharge);

        logInfo($companyAfterBalance, "Company after balance");

        // ✅ Step 3: Ensure company wallet has safe limits
        if ($companyWallet->balance > 100000 && $companyAfterBalance > 100000) {
            $companyIDsStatus = true;
        }

        if (($companyWallet->balance >= ($originalAmount + $companyCharge)) && $companyIDsStatus) {
            $response = [
                "status"       => true,
                "responseCode" => 1,
                "message"      => "Amount is valid",
            ];
        } else {
            $response = [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Switcher-related error. Please contact support",
            ];
        }

        return $response;
    }

    public function getPOSBalance(Request $request)
    {
        $userPOS = AgentTerminal::where('user_id', auth()->user()->id)->first();

        if ($userPOS != null) {

            return response()->json(["status" => true,
                'data'                            => $userPOS->balance], 200);
        } else {
            return response()->json(["status" => true,
                'message'                         => "POS does not "], 400);
        }

    }

    public function processVerifyAndReverseTransaction(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "transaction_reference" => "required|string",
        ]);

        $partnerType = "others";

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

        logInfo($transaction, "transaction_reference reverse mobile");

        if (! $transaction) {
            return response()->json(['status' => 0, 'message' => "Invalid Transaction reference"]);
        }

        if ($transaction->company_id == 61) {
            return response()->json(['status' => 0, 'message' => "Blocked Reversal"]);
        }

        if ($transaction->transaction_type_id != 8) {
            return response()->json(['status' => 0, 'message' => "This transaction is not a bank transfer"]);
        }

        $startingDate    = Carbon::parse('2026-05-12 00:00:00');
        $transactionDate = Carbon::parse($transaction->created_at);

        logInfo($transactionDate, "transaction_reference reverse mobile transactionDate");

        if ($startingDate->gt($transactionDate)) {
            return response()->json(['status' => 0, 'message' => "This transaction cannot be reversed"]);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => 0, 'message' => "Transaction has already been reversed"]);
        }

        /*  if ($transaction->transaction_remarks_id == 1 || $transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);
        }*/

        if ($transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);

        }

        if ($transaction->created_at > Carbon::now()->subMinutes(30)) {
            return response()->json(['status' => 0, 'message' => "Transaction must be at least 30 minutes before the transaction status can be confirmed"]);
        }

        $affliateCustomerCharge = 0;
        $affliateCompanyCharge  = 0;
        $whoToCredit            = $transaction->account_id == null ? 'company' : 'both';

        $childTransaction = transactions::where('parent_transaction_id', $transaction->id)->first();

        if ($childTransaction) {
            $affliateCustomerCharge = $childTransaction->amount;
            $affliateCompanyCharge  = $childTransaction->company_amount;
        }

        $kudaController = new KudaController();

        $verifyTransaction = $kudaController->verifyTransaction($request->transaction_reference);
        logInfo(json_encode($verifyTransaction), "transaction_reference reverse verifyTransaction mobile");

        if ($verifyTransaction['status'] == "03") {
            $partnerType            = "kuda_business";
            $kudaBusinessController = new KudaBusinessController();
            $verifyTransaction      = $kudaBusinessController->verifyTransaction($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType         = "safehaven";
            $safeHavenController = new SafehavenController();
            $verifyTransaction   = $safeHavenController->getTransactionStatus($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType = "monnify";

            $monnifyController = new MonnifyController();
            $verifyTransaction = $monnifyController->getTransactionStatus($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "00") {

            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            $verifyTransaction['status'] = 1;

            return response()->json($verifyTransaction);
        } else {

            logInfo($verifyTransaction, "Verify Transaction Before");
            if ($verifyTransaction['status'] == "02") {

                $verifyTransaction['status'] = 1;

                if ($partnerType == "safehaven" || $partnerType == "others" || $partnerType == "kuda_business") {

                    logInfo($transaction, "Transaction Records for Safe haven");
                    $totalCustomerAmount = $transaction->company_amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->company_amount + $affliateCompanyCharge;
                } else if ($partnerType != "safehaven") {
                    $totalCustomerAmount = $transaction->amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->amount + $affliateCompanyCharge;
                }

                $reversalResult = debitReversal($request->transaction_reference, $transaction->account_id, $transaction->company_id, $totalCustomerAmount, $totalCompanyAmount, $whoToCredit);

                if (! $reversalResult['status']) {
                    $reversalResult['status'] = 0;
                    return response()->json($reversalResult);
                }
            } else {
                $verifyTransaction['status'] = 0;
            }

            logInfo($verifyTransaction, "Verify Transaction Res");
            return response()->json($verifyTransaction);
        }

    }

    public function verifyAndReverseTransactionAdmin(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "transaction_reference" => "required|string",
        ]);

        $partnerType = "others";

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        return $this->processVerifyAndReverseTransaction($request);

    }

    public function createPhysicalCard(Request $request)
    {
        // Disable service (optional)
        // return response()->json(['status' => 0, 'message' => 'This service has been disabled']);

        $transactionId                   = generateTransactionReference();
        $customer                        = null;
        $userID                          = $request->user_id;
        $first_name                      = $request->first_name;
        $last_name                       = $request->last_name;
        $customer_sudo_account_id        = $request->customerId;
        $preferred_virtual_card_currency = 1;
        $auth                            = User::where('id', $userID)->first();

        try {
            // Call Safehaven API for physical card creation
            $safehaven    = new SafehavenController();
            $cardResponse = $safehaven->createPhysicalCard(
                $request,
                $transactionId,
                $customer,
                $customer_sudo_account_id
            );

            if (! $cardResponse['status']) {
                //  DB::rollback();
                return response()->json(['status' => 0, 'message' => $cardResponse['message']]);
            } else {

                $card2FAResponse = $safehaven->enrollcard2fa($cardResponse['result']['data']["_id"]);
                if ($card2FAResponse) {
                    $cardDetailsResponse = $safehaven->getPhysicalCardDetials($cardResponse['result']['data']["_id"]);
                }

            }

            $cardTypeMap = [
                'visa'       => 1,
                'verve'      => 2,
                'mastercard' => 3,
                'afrigo'     => 4,
            ];

// Normalize input to lowercase (to avoid case issues)
            $inputCardType = strtolower($request->card_type);

// Get matching code (or null if not found)
            $cardTypeCode = $cardTypeMap[$inputCardType] ?? null;

            UserCard::updateOrCreate(
                [
                    "sudo_customer_id" => $cardResponse['customer_sudo_account_id'],

                    "exp_date"         => $cardResponse['result']['data']["expiryYear"] ?? "",
                ],
                [
                    "user_id"   => $userID,
                    "card_name" => "{$first_name} {$last_name}",
                    "card_number"      => $cardResponse['result']['data']["maskedPan"],
                    "masked_pan"       => $cardResponse['result']['data']["maskedPan"],
                    "card_category_id" => 2,
                    "card_type_id"     => $cardTypeCode,
                    "card_reference"   => $cardResponse['result']['data']["_id"],
                    "currency_id"      => $preferred_virtual_card_currency,
                    "cvv"              => "",
                    "created_by"       => $userID,

                ]);

            // Save card details
            /*   UserCard::create([
                "user_id"   => $customer->id,
                "card_name" => "{$customer->first_name} {$customer->last_name}",
                "card_type_id"     => $cardTypeCode,
                "card_category_id" => 2,
                "card_number"      => $cardResponse['result']['data']["maskedPan"],
                "masked_pan"       => $cardResponse['result']['data']["maskedPan"],
                "card_reference"   => $cardResponse['result']['data']["_id"],
                "currency_id"      => $companySettings->preferred_virtual_card_currency,
                "exp_date"         => $cardResponse['result']['data']["expiryYear"] ?? "",
                "cvv"              => "",
                "created_by"       => $auth->id,
                "sudo_customer_id" => $cardResponse['sudo_customer_id'],
            ]);*/

            CardRequestLog::create([
                "user_id"    => $userID,
                "company_id" => $auth->company_id,
            ]);

            $pin = $cardDetailsResponse['data']['defaultPin'];
            return response()->json(['status' => 1, 'message' => "Card mapped successfully Deafult Pin is {$pin} ", 'data' => $cardResponse]);

        } catch (\Exception $e) {
            // DB::rollback();
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function getGiftCardSubCategories()
    {
        $neonDataApi = new NeonDataApiController();
        return $neonDataApi->getGiftCardSubCategories();
    }

    public function postGiftTransaction(Request $request)
    {
        $neonDataApi = new NeonDataApiController();
        $response    = $neonDataApi->giftCardTransaction($request);
        if ($response) {
            return response()->json(['status' => 1, 'message' => "Gift Card Transaction Successful"], 200);
        } else {
            response()->json(['status' => 0, 'message' => "Gift Card Transaction failed"], 400);
        }
    }

    public function createVPayAccount(Request $request)
    {
        $vpay = new VPayController();

        $companyRecord    = Company::where('id', $request->company)->first();
        $userRecord       = User::where('company_id', $request->company)->where('user_type_id', 2)->first();
        $businessname     = $companyRecord->short_name;
        $email            = $userRecord->email;
        $phone            = $userRecord->phone;
        $contactfirstname = $businessname;
        $contactlastname  = $companyRecord->first_name;
        $bvn              = $userRecord->bvn;

        return $vpay->createAccount($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn);

    }

    public function setSpendSave(Request $request)
    {
        $user       = auth('api')->user();
        $company_id = $user->company_id;
        $charge     = $request->spend_save_charge_or_percentage / 100;
        $settings   = SpendSave::where('user_id', $user->id)->first();
        if ($request->spend_save_charge_or_percentage != null) {
            $charge = ($request->spend_save_charge_or_percentage / 100);
        } else {
            $charge = $settings->spend_save_charge_or_percentage;
        }

        $newSettings = SpendSave::updateOrCreate(
            [
                "user_id" => $user->id,

            ],
            [

                "company_id"                      => $company_id,
                "spend_save_charge_or_percentage" => $charge,
                "balance"                         => $settings->balance ?? 0,
                "status_id"                       => $request->status ?? 1,

            ]);

        return response()->json(["status" => 1, "message" => "Spend Save settings is saved", "spend_save_charge" => $newSettings->spend_save_charge_or_percentage]);

    }

    public function withdrawSpendSave(Request $request)
    {
        $user                   = auth('api')->user();
        $company_id             = $user->company_id;
        $amount                 = $request->amount;
        $debitCustomerSpendSave = debitCustomerWithdrawSpendSave($user->id, $amount);

        $spendSaveTransactionId = \generate_txnID();
        $ledger_cr              = null;
        $ledger_dr              = null;
        $ledger_Settings        = get_CR_DR_TXN_Type_Ledger(104, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $debitCustomerSpendSave['balance_before'],
            "amount"                 => $amount,
            "account_id"             => $user->id,
            "balance_after"          => $debitCustomerSpendSave["balance_after"],
            "company_balance_before" => 0,
            "company_balance_after"  => 0,
            "company_amount"         => 0,
            "description"            => "Withdraw from Spend/Save",
            "branch_id"              => $user->branch_id,
            "company_id"             => $user->company_id,
            "transaction_reference"  => $spendSaveTransactionId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 104,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        return response()->json(["status" => 1, "message" => "Withdraw  is successfull"]);

    }

    public function getSpendSave(Request $request)
    {
        $user       = auth('api')->user();
        $company_id = $user->company_id;
        $settings   = SpendSave::where('user_id', $user->id)->first();

        return response()->json(["status" => 1, "message" => "Spend Save settings", "spend_save_charge" => $settings->spend_save_charge_or_percentage, "balance" => $settings->balance, "spend_save_status" => $settings->status_id]);

    }

    public function getUserToken(Request $request)
    {
        $user  = auth('api')->user();
        $token = generateToken();
        $user->update([
            'fcm_token' => $token,

        ]);

        return response()->json(["status" => 1, "message" => "Token Generated Successflly", "token" => $token]);

    }

    public function initiateCreateSafehavenMaintenanceAccount(Request $request)
    {

        $company = Company::with('allStaff')->find($request->company);

        $admin = $company->allStaff->first();

        $companyIDforSafeHavenAccountCheck = 14; //finova account

        $this->safeHavenIdentityCheckResponse($admin->bvn, $companyIDforSafeHavenAccountCheck);
    }

    public function createSafehavenMaintenancAccount(Request $request)
    {
        $company = Company::with('allStaff')->find($request->company);

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo(["company_id" => $company->id, "error" => "No admin found"], 'Create Safehaven Account');
            response()->json(['status' => 0, 'message' => "No admin found"], 400);
        }
        $firstName = $company->short_name;

        $lastName = "";

        $email = $admin->email;

        $phone = $admin->phone;

        $customerReference = generateTransactionReference();

        $bvn = $admin->bvn;

        $otp = $request->otp;

        $bvnId = $request->bvnId;

        $companyIDforSafeHavenAccountCheck = 14; //finova account

        $safeHavenController = new SafehavenController();

        return $safeHavenController->createVirtualAccount($firstName, $lastName, $email, $phone, $customerReference, $bvn, $otp, $bvnId, $companyIDforSafeHavenAccountCheck);
    }

    private function createTransanctionforUtilityPayment(Request $request, $transactionId, $customerBalance, $transaction_type, $description)
    {

        $amount      = 0;
        $totalAmount = 0;

        $amount      = $request->final_amount;
        $totalAmount = $request->final_amount;

        $ledger_cr = null;
        $ledger_dr = null;

        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transaction_type, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
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
            "transaction_remarks_id" => 6,
            "branch_id"              => \auth()->user()->branch_id,
            "transaction_type_id"    => $transaction_type,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);
    }

    private function sendTransactionReportEmail($subtext, $msg, $reason, $user)
    {

        $data = [
            'subtext'      => $subtext,
            'reason'       => $reason,
            'desc'         => $msg,
            'company_name' => $user->company->company_name,
            'user'         => $user->company->company_name,
        ];

        try {
            Mail::to($user->email)->send(new TransactionReport($data));
        } catch (\Throwable $e) {
            \Log::error("Failed to send TransactionReport email: " . $e->getMessage());
            // continue silently
        }
    }

    private function checkCompanyBalanceThresholdTransaction($amount)
    {

        $response   = [];
        $userWallet = company_wallet::where('company_id', auth()->user()->company_id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($amountLeft, "amountLeft for company");

        if ($amount > 0) {

            if ($amountLeft < 100001) {
                $response = ["status" => false, 'message' => "Issue with this service. Contact Support"];
            } else {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->company_id, 'balance' => $userWallet->balance];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;

    }

    private function vtPassControllerSercice($action, array $data = [])
    {

        $transactionId      = $data['transactionId'] ?? null;
        $networkProvider    = $data['networkProvider'] ?? null;
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;

        $vtPassController = new VTPassController();

        if ($action == "buyAirTime") {
            $networkProvider = $request->network_provider == '9mobile' ? 'etisalat' : $request->network_provider;

            $service = $vtPassController->buyAirtime($transactionId, $networkProvider, $request->final_amount, $request->phone_number);
        }
        if ($action == "buyData") {
            $service = $vtPassController->buyData($transactionId, $request->network_provider, $request->data_plan, $selectedDataBundle['variation_amount'], $request->phone_number);
        }
        if ($action == "buyElectricity") {
            $service = $vtPassController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number
            );
            logInfo($service, "VTPass Electricity Response");
        }
        if ($action == "subscribeCableTV") {
            $subscribeCableTV = $vtPassController->subscribeCableTV($transactionId, $request->cable_tv_type, $request->subscription_plan, $request->smart_card_number, $request->final_amount, $request->phone_number);
        }
        if ($action == "verifyMeterNumber") {
            $service = $vtPassController->verifyMeterNumber($request->meter_number, $request->electricity_disco, $request->type);
        }
        if ($action == "verifyCableTV") {
            $service = $vtPassController->verifyCableTV($request->smart_card_number, $request->cable_tv_type);
        }
        if ($action == "getDataBundles") {

            $service = $vtPassController->getDataBundles($networkProvider);
        }

        if ($action == "getDataBundlesList") {
            $networkProvider = $request->network_provider == '9mobile' ? 'etisalat' : $request->network_provider;
            $service         = $vtPassController->getDataBundles($networkProvider);
        }

        if ($action == "getSubscriptions") {

            $service = $vtPassController->getSubscriptions($cableTV);
        }

        return $service;
    }
    private function safeHavenControllerSercice($action, array $data = [])
    {

        $transactionId      = $data['transactionId'] ?? null;
        $networkProvider    = $data['networkProvider'] ?? null;
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;

        $safeHavenController = new SafehavenController();
        $company_id          = company_wallet::where('company_id', auth()->user()->company_id)->first()->company_id;
        $company_id          = (int) $company_id;

        $getService = $safeHavenController->getService($action, $company_id);

        if ($request->network_provider != null) {
            $networkProvider = strtolower($request->network_provider);

            $networkProvider = preg_replace('/-data$/', '', $networkProvider);

            $networkProvider    = $networkProvider === '9mobile' ? 'etisalat' : $networkProvider;
            $getServiceCategory = $safeHavenController->getServiceCategory($getService, $company_id, strtoupper($networkProvider));
        } else {
            if ($request->electricity_disco != null) {
                $disco = $request->electricity_disco;
                $map   = [
                    'abuja-electric'        => 'AEDC',
                    'ibadan-electric'       => 'IBEDC',
                    'eko-electric'          => 'EKEDC',
                    'ikeja-electric'        => 'IKEDC',
                    'portharcourt-electric' => 'PHEDC',
                    'jos-electric'          => 'JEDC',
                    'kano-electric'         => 'KEDCO',
                    'enugu-electric'        => 'EEDC',
                    'yola-electric'         => 'YEDC',
                    'benin-electric'        => 'BEDC',
                    'kaduna-electric'       => 'KAEDC',
                    // future actions can go here
                ];

                $provider = $map[$disco];

            }
            $getServiceCategory = $safeHavenController->getServiceCategory($getService, $company_id, strtoupper($provider));
        }

        $getServiceCategoryID = $getServiceCategory['data']['_id'];

        if ($action == "buyAirTime") {

            $service = $safeHavenController->buyAirtime($transactionId, $getServiceCategoryID, $request->final_amount, $request->phone_number, $company_id);
        }
        if ($action == "buyData") {

            $service = $safeHavenController->buyData($transactionId, $getServiceCategoryID, $getServiceCategory['data']['service'], $request->final_amount, $request->phone_number, $company_id);
        }

        if ($action == "buyElectricity") {

            $service = $safeHavenController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number,
                $company_id
            );
            logInfo($service, "VTPass Electricity Response");
        }

        if ($action == "subscribeCableTV") {

            $service = $safeHavenController->subscribeCableTV($transactionId, $getServiceCategoryID, $request->subscription_plan, $request->smart_card_number, $request->final_amount, $request->phone_number, $company_id);
        }

        if ($action == "verifyMeterNumber") {
            $service = $safeHavenController->verifyMeterNumber($request->meter_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "verifyCableTV") {
            $service = $safeHavenController->verifyCableTV($request->smart_card_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "getDataBundles") {

            $service = $safeHavenController->getDataBundles($networkProvider);
        }

        return $service;
    }
}
