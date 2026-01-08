<?php
// namespace App\helpers;
use App\Mail\AccountCreated;
use App\Models\AgentTerminal;
use App\Models\banks;
use App\Models\BankTransferInformation;
use App\Models\BranchValut;
use App\Models\BranchWallet;
use App\Models\ClosingBalance;
use App\Models\Company;
use App\Models\CompanyValut;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\currencies;
use App\Models\currencies as Currency;
use App\Models\CustomerRestrictionLog;
use App\Models\DailySummary;
use App\Models\Expenses;
use App\Models\LedgerAccountModel;
use App\Models\LedgerSettings;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\loan_product;
use App\Models\MainCustomerRestriction;
use App\Models\Notification;
use App\Models\OtherIncome;
use App\Models\PendingSaving;
use App\Models\PreferredEbankingPartner;
use App\Models\smsapi;
use App\Models\StaffpermissionTable;
use App\Models\Temp_Charges;
use App\Models\transactions;
use App\Models\transaction_types;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\wb_account_details;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

//allCashLoanRepayment
if (! function_exists('generateAccountReference')) {
    function generateAccountReference()
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 20))), 1, 20);
    }
}

if (! function_exists('generateTransactionReference')) {
    function generateTransactionReference()
    {
        // return "BM" . mt_rand(10000000000, 99999999999);
        return 'BM' . now()->format('YmdHis') . random_int(10000000000, 99999999999);
    }
}

if (! function_exists('user_email')) {
    function user_email()
    {
        $user = Auth::user();
        return $user->email;
    }
}

function generateAccountNumber()
{
    return mt_rand(1000000000, 9999999999);
}

function moneyFormat($value)
{

    return number_format($value, 2);
}

function moneyFormater($value)
{

    return bcadd($value, '0', 2);
}
function setTitle($menu)
{

    return ucwords(str_replace('_', ' ', $menu));
}
function removeMinusText($menu)
{

    return ucwords(str_replace('-', ' ', $menu));
}
// class helpers{
function cando($permission)
{
    $auth = auth()->user();
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $userrole = StaffpermissionTable::has('staffrole')
        ->join('users', 'users.id', 'staffpermission.user_id')
        ->where(['user_id' => $auth->id, 'users.company_id' => $auth->company_id])->get();
    $staff_permission = [];

    if (count($userrole) == 0) {
        return 0;
    }

    foreach ($userrole as $userroles);

    if ($userroles->staffrole->id != $auth->staffpermission->staffrole_id) {
        return 0;
    }

    foreach ($userroles->staffrole->has_permissions as $role_as_permission) {
        $staff_permission[] = $role_as_permission->permission->name;
    }
    $permissions = is_array($permission) ? $permission : explode('|', $permission);

    $result = array_intersect($permissions, $staff_permission);
    //  var_dump($result);
    if (count($result) > 0) {
        return 1;
    } else {
        return 0;
    }
}

function postRequest($headers, $body, $url)
{

    logInfo($url);

    $response = Http::withHeaders($headers)->post($url, $body);

    logInfo($response, "our endrespnose");

    return $response->json();
}

function postAsFormRequest($headers, $body, $url)
{
    $response = Http::withHeaders($headers)->asForm()->post($url, $body);

    return $response->json();
}

function putRequest($headers, $body, $url)
{
    $response = Http::withHeaders($headers)->put($url, $body);

    return $response->json();
}

function generateOtp()
{
    $FourDigitRandomNumber = mt_rand(1111, 9999);
    return $FourDigitRandomNumber;
}

function flutterWavePostRequest($endpoint, $body)
{

    $response = Http::timeout(40)->withHeaders(flutterWaveHeader())->post(flutterwaveBaseUrl($endpoint), $body);
    return $response->json();
}

function vtpassPostRequest($endpoint, $body)
{
    $response = Http::withHeaders(vtpassHeader())->post(vtPassBaseUrl($endpoint), $body);
    return $response->json();
}

function giftBillsTopupPostRequest($endpoint, $body)
{
    $json    = '{"amount":"' . $body['amount'] . '","customerId":"' . $body['customerId'] . '","provider":"' . $body['provider'] . '","reference":"' . $body['reference'] . '"}';
    $sec_key = hash_hmac('sha512', $json, giftBillsEncryptionKey());

    $final_array = array_merge(giftBillsHeader(), ["Encryption" => $sec_key, "MerchantId" => giftBillsMerchantId()]);

    $response = Http::withHeaders($final_array)->post(giftBillsBaseUrl($endpoint), $body);
    return $response->json();
}

function giftBillsPostRequest($endpoint, $body)
{
    $response = Http::withHeaders(giftBillsHeader())->post(giftBillsBaseUrl($endpoint), $body);
    return $response->json();
}

function flutterWaveHeader()
{
    $header               = [];
    $flutterwaveSecretKey = '';

    // if (env('APP_MODE') == 'test') {
    //     $flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_TEST');
    // } else {
    //     $flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_LIVE');
    // }

    $flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_LIVE');

    $header = ['Authorization' => 'Bearer ' . $flutterwaveSecretKey];

    logInfo($header, "Flutterwave header");

    return $header;
}

function giftBillsHeader()
{
    $header             = [];
    $giftBillsSecretKey = '';

    if (env('APP_MODE') == 'test') {
        $giftBillsSecretKey = env('GIFTBILLS_API_KEY_TEST');
    } else {
        $giftBillsSecretKey = env('GIFTBILLS_API_KEY_LIVE');
    }

    $header = ['Authorization' => 'Bearer ' . $giftBillsSecretKey];

    return $header;
}

function vtpassHeader()
{
    $header         = [];
    $basicAuthToken = '';

    if (env('APP_MODE') == 'test') {
        $basicAuthToken = base64_encode(env('VTUPASS_USERNAME_TEST') . ":" . env('VTUPASS_PASSWORD_TEST'));
    } else {
        $basicAuthToken = base64_encode(env('VTUPASS_USERNAME_LIVE') . ":" . env('VTUPASS_PASSWORD_LIVE'));
    }
    $header = ['Authorization' => 'Basic ' . $basicAuthToken];

    return $header;
}

function loginReloadly()
{

    $clientId     = '';
    $clientSecret = '';

    if (env('APP_MODE') == 'test') {
        $clientId     = env('RELOADLY_CLIENT_ID_TEST');
        $clientSecret = env('RELOADLY_CLIENT_SECRET_TEST');
    } else {
        $clientId     = env('RELOADLY_CLIENT_ID_LIVE');
        $clientSecret = env('RELOADLY_CLIENT_SECRET_LIVE');
    }
    $clientId     = env('RELOADLY_CLIENT_ID_LIVE');
    $clientSecret = env('RELOADLY_CLIENT_SECRET_LIVE');

    $body = [

        "client_id"     => $clientId,
        "client_secret" => $clientSecret,
        "grant_type"    => "client_credentials",
        "audience"      => "https://topups.reloadly.com",

    ];

    $url = "https://auth.reloadly.com/oauth/token";

    $loginResult = postRequest(["Content-Type" => "application/json"], $body, $url);

    return isset($loginResult['access_token']) ? $loginResult['access_token'] : null;
}

function cronJobBankTransfer($transactionId)
{
    $transaction = transactions::find($transactionId);

    if (! $transaction) {
        return ['status' => false, 'message' => 'Transaction not found'];
    }

    if ($transaction->transaction_remarks_id == 2 || $transaction->transaction_remarks_id == 5 || $transaction->transaction_remarks_id == 3) {
        return ['status' => false, 'message' => 'Cannot process this transaction'];
    }

    $bankTransferInfo = BankTransferInformation::where('transaction_id', $transactionId)->first();

    if (! $bankTransferInfo) {
        return ['status' => false, 'message' => 'Bank transfer info not found ' . $transactionId];
    }

    $bank = banks::where('bank_code', $bankTransferInfo->bank_code)->orwhere('short_code', $bankTransferInfo->bank_code)->first();

    if (! $bank) {
        return response()->json(["status" => 0, 'message' => "Invalid bank code"], 400);
    }

    $customerBalanceBefore       = 0;
    $customerBalanceAfter        = 0;
    $customerChargeBalanceBefore = 0;
    $customerChargeBalanceAfter  = 0;
    $accountId                   = 0;

    $customerCharge             = 0;
    $companyBalanceBefore       = 0;
    $companyBalanceAfter        = 0;
    $companyChargeBalanceBefore = 0;
    $companyChargeBalanceAfter  = 0;

    $entityType = "both";

    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $transaction->company_id);
    if ($ledger_Settings) {
        $ledger_cr = $ledger_Settings->ledger_cr;
        $ledger_dr = $ledger_Settings->ledger_dr;
    } else {
        return ['status' => 0, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
    }

    if ($bankTransferInfo->transfer_entity_type == 'both') {

        $entityType = "both";

        $myDescription = $bankTransferInfo->description;

        $descriptionArray = explode("/", $myDescription);

        if (count($descriptionArray) > 1) {
            $gatewayDescription = $descriptionArray[0] . "/" . $descriptionArray[1];
        } else {
            $myDescription = $bankTransferInfo->description;
        }

        if ($bankTransferInfo->customer_charge != null) {
            $customerCharge = $bankTransferInfo->customer_charge;
        }

        $wbAccountDetails = wb_account_details::find($transaction->account_id);

        $currentUser = $wbAccountDetails->user;

        $validateTransfer = validateTransfer($transaction->account_id, $transaction->company_id, $currentUser, $bankTransferInfo->amount);

        if (! $validateTransfer['status']) {
            $transaction->transaction_remarks_id = 3;
            $transaction->save();
            return $validateTransfer;
        }

        $debitCustomerAndCompany = debitCustomerAndCompanyWithCharge($transaction->account_id, $transaction->company_id, $bankTransferInfo->amount, $bankTransferInfo->customer_charge, $bankTransferInfo->company_charge, $currentUser);

        if (! $debitCustomerAndCompany['status']) {
            $debitCustomerAndCompany['status']   = 0;
            $transaction->transaction_remarks_id = 3;
            $transaction->save();
            return $debitCustomerAndCompany;
        }

        $accountId                   = $debitCustomerAndCompany['account_id'];
        $customerBalanceBefore       = $debitCustomerAndCompany['balance_before'];
        $customerBalanceAfter        = $debitCustomerAndCompany['balance_after'];
        $customerChargeBalanceBefore = $debitCustomerAndCompany['charge_balance_before'];
        $customerChargeBalanceAfter  = $debitCustomerAndCompany['charge_balance_after'];

        $companyBalanceBefore       = $debitCustomerAndCompany['company_balance_before'];
        $companyAmount              = $debitCustomerAndCompany['company_amount'];
        $companyBalanceAfter        = $debitCustomerAndCompany['company_balance_after'];
        $companyChargeBalanceBefore = $debitCustomerAndCompany['company_charge_balance_before'];
        $companyChargeBalanceAfter  = $debitCustomerAndCompany['company_charge_balance_after'];

        $transaction->balance_before         = $customerBalanceBefore;
        $transaction->balance_after          = $customerBalanceAfter;
        $transaction->company_balance_before = $companyBalanceBefore;
        $transaction->company_balance_after  = $companyBalanceAfter;

        $transaction->save();

        $chargeTransaction = transactions::where('parent_transaction_id', $transaction->id)->first();

        if ($chargeTransaction) {
            $chargeTransaction->balance_before         = $customerChargeBalanceBefore;
            $chargeTransaction->balance_after          = $customerChargeBalanceAfter;
            $chargeTransaction->company_balance_before = $companyChargeBalanceBefore;
            $chargeTransaction->company_balance_after  = $companyChargeBalanceAfter;
            $chargeTransaction->transaction_remarks_id = 2;

            $chargeTransaction->save();
        }
    } else {

        $entityType = "company";

        $adminUser = User::find($bankTransferInfo->created_by);

        $validateTransfer = validateCompanyTransfer($adminUser, $bankTransferInfo->amount);

        if (! $validateTransfer['status']) {
            return $validateTransfer;
        }

        $debitCompanyWithCharge = debitCompanyWithCharge($transaction->company_id, $bankTransferInfo->amount, $bankTransferInfo->company_charge);
        if (! $debitCompanyWithCharge['status']) {
            $debitCompanyWithCharge['status']    = $debitCompanyWithCharge['responseCode'];
            $transaction->transaction_remarks_id = 3;
            $transaction->save();
            return $debitCompanyWithCharge;
        }

        $companyBalanceBefore       = $debitCompanyWithCharge['balance_before'];
        $companyBalanceAfter        = $debitCompanyWithCharge['balance_after'];
        $companyChargeBalanceBefore = $debitCompanyWithCharge['charge_balance_before'];
        $companyChargeBalanceAfter  = $debitCompanyWithCharge['charge_balance_after'];

        $transaction->company_balance_before = $companyBalanceBefore;
        $transaction->company_balance_after  = $companyBalanceAfter;
        $transaction->transaction_remarks_id = 2;

        $transaction->save();

        $transactionRef2 = generateTransactionReference();

        transactions::create([
            "company_balance_before" => $companyChargeBalanceBefore,
            "company_amount"         => $bankTransferInfo->company_charge,
            "account_id"             => $accountId > 0 ? $accountId : null,
            "company_balance_after"  => $companyChargeBalanceAfter,
            "description"            => "Charge for NGN Bank Transfer - " . $transaction->transaction_reference,
            "transaction_reference"  => $transactionRef2,
            "balance_before"         => $customerChargeBalanceBefore,
            "amount"                 => $customerCharge,
            "balance_after"          => $customerChargeBalanceAfter,
            "company_id"             => $transaction->company_id,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 27,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $transaction->created_by,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $company = Company::find($transaction->company_id);

        $gatewayDescription = $transaction->description;
        logInfo($gatewayDescription, "Main Gateway description");
    }

    $preferredEbankingPartner = PreferredEbankingPartner::all();

    logInfo($gatewayDescription);

    $ebankingService                        = new EbankingService($preferredEbankingPartner);
    $bankTransferType                       = new BankTransferType();
    $bankTransferType->bankCode             = $bankTransferInfo->bank_code;
    $bankTransferType->shortBankCode        = $bank->short_code;
    $bankTransferType->accountNumber        = $bankTransferInfo->account_number;
    $bankTransferType->description          = $bankTransferInfo->description;
    $bankTransferType->amount               = $bankTransferInfo->amount;
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

    $newdescription = $transaction->description;

    $bank = banks::where('bank_code', $bankTransferInfo->bank_code)->orwhere('short_code', $bankTransferInfo->bank_code)->first();

    $newdescription = $newdescription . "/" . $bankTransferInfo->account_name . "/" . $bank->bank_name . " - " . $bankTransferInfo->account_number;

    $transaction                         = transactions::where('transaction_reference', $transaction->transaction_reference)->first();
    $transaction->transaction_remarks_id = 2;
    $transaction->description            = $newdescription;
    $transaction->save();

    // if ($bankTransferInfo->transfer_entity_type == 'user'){

    //     sendSMS(auth()->user()->id, "Debit Amt:" . ($bankTransferInfo->amount + $bankTransferInfo->charge) . ", Desc: $transaction->description Avail Bal:" . $transaction->balance_after . " Date:" . date('Y-m-d') . " Ref: " . $transaction->transaction_reference, true, true, 'Debit Transaction');
    // }
}

function validateTransfer($accountId, $companyId, $user, $amount)
{
    $companySettings = company_settings::where('company_id', $companyId)->first();
    $accountTier     = $user->tier;
    $wbaAccount      = $user->accounts->first();

    if (! $companySettings) {
        return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
    }

    if ($amount > $accountTier->maximum_transfer) {
        return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
    }

    $userWallet = user_wallet::where('wb_account_detail_id', $accountId)->first();
    $amountLeft = $userWallet->balance - $amount;

    if ($amountLeft < $companySettings->minimum_balance) {
        return ["status" => false, 'message' => "Insufficient Customer balance"];
    }

    if ($amount < $accountTier->minimum_transfer) {
        return ["status" => false, 'message' => "Minimum amount allowed is NGN$accountTier->minimum_transfer"];
    }

    // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
    $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $accountId])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
    $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $accountId])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->sum('amount');

    if ($dailySum + $amount > $accountTier->daily_transaction_limit) {
        return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
    }
    if ($sum >= $accountTier->daily_transaction_count) {
        return ["status" => false, 'message' => "Daily Transaction Count Exceeded"];
    }

    return ["status" => true, 'message' => "Transaction Validated"];
}

function validateCompanyTransfer($user, $amount)
{
    $accountTier = $user->tier;

    $companyWallet = company_wallet::where('company_id', $user->company_id)->first();

    if (! $companyWallet) {
        return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
    }

    if ($amount > $accountTier->maximum_transfer) {
        return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
    }

    // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
    $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'company_id' => $user->company_id])->where('transaction_type_id', '<>', '8')->whereDate('created_at', Carbon::today())->count();
    $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'company_id' => $user->company_id])->where('transaction_type_id', '8')->whereDate('created_at', Carbon::today())->sum('company_amount');

    if (($dailySum + $amount) > $accountTier->daily_transaction_limit) {
        return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
    }

    if ($sum >= $accountTier->daily_transaction_count) {
        return ["status" => false, 'message' => "Daily Transaction Count Exceeded"];
    }

    return ["status" => true, 'message' => "Transaction Validated"];
}

function logInfo($content, $title = "No Title")
{
    Log::info("<<<<<<<<<<<<<");
    Log::info($title);
    Log::info(">>>>>>>>>>>>>>>>>>>>>>");
    Log::info($content);
}

function flutterwaveBaseUrl($endpoint)
{
    return "https://api.flutterwave.com/v3/$endpoint";
}

function giftBillsBaseUrl($endpoint)
{
    return env('APP_MODE') == 'test' ? env("GIFTBILLS_BASE_URL_TEST") . "/$endpoint" : env("GIFTBILLS_BASE_URL_LIVE") . "/$endpoint";
}

function vtPassBaseUrl($endpoint)
{
    return env('APP_MODE') == 'test' ? "https://sandbox.vtpass.com/api/$endpoint" : "https://vtpass.com/api/$endpoint";
}

function giftBillsEncryptionKey()
{
    return env('APP_MODE') == 'test' ? env("GIFTBILLS_ENCRYPTION_KEY_TEST") : env("GIFTBILLS_ENCRYPTION_KEY_LIVE");
}

function giftBillsMerchantId()
{
    return env('APP_MODE') == 'test' ? env("GIFTBILLS_MERCHANT_ID_TEST") : env("GIFTBILLS_MERCHANT_ID_LIVE");
}

function getRequest($headers, $body, $url)
{
    $response = Http::timeout(40)->withHeaders($headers)->get($url);
    return $response->json();
}

function flutterWaveGetRequest($endpoint)
{
    $response = Http::timeout(40)->withHeaders(flutterWaveHeader())->get(flutterwaveBaseUrl($endpoint));
    return $response->json();
}

function flutterWavePutRequest($endpoint)
{
    $response = Http::timeout(40)->withHeaders(flutterWaveHeader())->put(flutterwaveBaseUrl($endpoint));
    return $response->json();
}

function vtpassGetRequest($endpoint)
{
    $response = Http::withHeaders(vtpassHeader())->get(vtPassBaseUrl($endpoint));
    return $response->json();
}

function giftBillsGetRequest($endpoint)
{
    $response = Http::withHeaders(giftBillsHeader())->get(giftBillsBaseUrl($endpoint));
    return $response->json();
}

function generateAPIKey($length = 25)
{
    $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function createVirtualAccount($name, $bvn)
{

    $curl = curl_init();
    $bvn  = $bvn == '' ? '' : $bvn;

    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://vps.providusbank.com/vps/api/PiPCreateReservedAccountNumber',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => '{
        "account_name":"' . $name . '",
        "bvn":"' . $bvn . '"

    }',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Client-Id: M1JtQF9XSXIjZC5QcjBWKCkzMjUyMzIz',
            'X-Auth-Signature: dd02585c6a0a87859ca50f8cf5a1947d10b2fdf5ca510f49652aee8dc15b3a77a10129c993f4e9039e4c11dcced0f3c1b4a27341e089ad3de4f997290048b72f',
        ],
    ]);

    $response = curl_exec($curl);

    curl_close($curl);
    return \json_decode($response, true);

    // return $response->json();
}

function diff_btw_date($fdate, $tdate)
{

    $datetime1 = new DateTime($fdate);
    $datetime2 = new DateTime($tdate);
    $interval  = $datetime1->diff($datetime2);
    $days      = $interval->format('%a');
    return $days;
}
function cleaninput_space($str)
{
    $str = str_replace(['\'', '"', ',', ';', '<', '>', '*', ')', '(', ' '], '', $str);

    return $str;
}
function generate_txnID()
{
    $temp_id = generateTransactionReference();
    while (transactions::where('transaction_reference', $temp_id)->count() > 0) {
        $temp_id = generateTransactionReference();
    }

    if (auth()->user()) {
        $finalTXN = $temp_id . cleaninput_space(auth()->user()->company->short_name);
    } else {
        $finalTXN = $temp_id . 'cron';
    }

    return $finalTXN;
}
function add_day_to_date($daysToAdd, $premiumDate)
{
    $date    = Carbon::createFromFormat('Y-m-d', $premiumDate);
    $newdate = $date->addDays($daysToAdd);
    return date_format($newdate, "Y-m-d");
}

function debitReversal($transactionReference, $customerAccountId, $companyId, $customerAmount, $companyAmount, $whoToCredit, $type = "normal")
{
    $mainTransaction = transactions::where('transaction_reference', $transactionReference)->first();

    if (! $mainTransaction) {
        return ['status' => false, 'message' => 'Invalid transaction Reference. Cannot reverse this transaction'];
    }

    $creditTransactionResult = [];
    if ($whoToCredit == 'company') {
        logInfo($companyAmount, "Main Company Transactiion Amount");
        $creditTransaction = creditCompanyWithCharge($companyId, $companyAmount, 0);
        logInfo($creditTransaction, "Main Credit Transaction");

        if (! $creditTransaction['status']) {
            return $creditTransaction;
        }

        $customer_balance_before = 0;
        $customer_amount         = 0;
        $customer_balance_after  = 0;
        $company_balance_before  = $creditTransaction["balance_before"];
        $company_amount          = $companyAmount;
        $company_balance_after   = $creditTransaction["balance_after"];
    } else if ($whoToCredit == 'both') {
        $creditTransaction = creditCustomerAndCompany($customerAccountId, $companyId, $customerAmount, $companyAmount);
        logInfo($creditTransaction, "Main Credit Transaction");

        if (! $creditTransaction['status']) {
            return $creditTransaction;
        }

        $customer_balance_before = $creditTransaction["balance_before"];
        $customer_amount         = $customerAmount;
        $customer_balance_after  = $creditTransaction["balance_after"];
        $company_balance_before  = $creditTransaction["company_balance_before"];
        $company_amount          = $companyAmount;
        $company_balance_after   = $creditTransaction["company_balance_after"];
    }

    $mainTransaction->transaction_remarks_id = 5;
    $mainTransaction->save();

    $transaction = transactions::create([
        "balance_before"         => $customer_balance_before,
        "amount"                 => $customer_amount,
        "balance_after"          => $customer_balance_after,
        "description"            => $type != "atm" ? "Ref: $transactionReference funds reversal for failed transfer" : "Failed ATM withdrawal reversal with ref: $transactionReference",
        "account_id"             => $customerAccountId ?? null,
        "transaction_reference"  => generate_txnID(),
        "company_id"             => $type == "atm" ? null : ($companyId ?? null),
        "parent_transaction_id"  => $mainTransaction->id,
        "company_balance_before" => $company_balance_before,
        "company_amount"         => $company_amount,
        "company_balance_after"  => $company_balance_after,
        "transaction_remarks_id" => 2,
        "transaction_type_id"    => $type == "atm" ? 112 : 96,
        "credit_debit_type_id"   => 1,
        "created_date"           => date('Y-m-d'),
        "created_by"             => $mainTransaction->created_by,
    ]);

    return [
        'status'  => true,
        'message' => 'Debit reversal successful',
        'data'    => [
            'balance_before'        => $customer_balance_before,
            'amount'                => $customer_amount,
            'balance_after'         => $customer_balance_after,
            'account_id'            => $customerAccountId,
            'transaction_reference' => $transactionReference,
        ],
    ];
}

function getWbaTransferCharges($chargeId, $amount)
{
    $wbaCharge = WbaCharge::where('id', $chargeId)->first();

    if ($wbaCharge->charge_by == 1) {
        $wbaChargePercentage = $wbaCharge->charge * 0.01;
        $companyCharge       = $wbaChargePercentage * $amount;

        if ($wbaCharge->minimum_charge_amount > 0) {
            $companyCharge = $companyCharge < $wbaCharge->minimum_charge_amount ? $wbaCharge->minimum_charge_amount : $companyCharge;
        }

        if ($wbaCharge->maximum_charge_amount > 0) {
            $companyCharge = $companyCharge > $wbaCharge->maximum_charge_amount ? $wbaCharge->maximum_charge_amount : $companyCharge;
        }

    } else {
        $companyCharge = $wbaCharge->charge;
    }
    return $companyCharge;
}
function padAccountCode($number)
{
    return sprintf("%05d", $number);
}

function posDebitReversal($transactionReference, $customerAccountId, $companyId, $customerAmount, $whoToCredit)
{
    $mainTransaction = transactions::where('transaction_reference', $transactionReference)->first();

    if (! $mainTransaction) {
        return ['status' => false, 'message' => 'Invalid transaction Reference. Cannot reverse this transaction'];
    }

    $companyTransaction = transactions::where('parent_transaction_id', $mainTransaction->id)->first();

    $creditTransactionResult = [];
    if ($whoToCredit == 'company') {
        $creditTransaction       = creditCompanyWithCharge($companyId, $companyTransaction->company_amount, 0);
        $customer_balance_before = 0;
        $customer_amount         = 0;
        $customer_balance_after  = 0;
        $company_balance_before  = $creditTransaction["balance_before"];
        $company_amount          = $companyTransaction->company_amount;
        $company_balance_after   = $creditTransaction["balance_after"];
    } else if ($whoToCredit == 'both') {
        $creditTransaction       = creditCustomerAndCompany($customerAccountId, $companyId, $customerAmount, $companyTransaction->company_amount);
        $customer_balance_before = $creditTransaction["balance_before"];
        $customer_amount         = $customerAmount;
        $customer_balance_after  = $creditTransaction["balance_after"];
        $company_balance_before  = $creditTransaction["company_balance_before"];
        $company_amount          = $companyTransaction->company_amount;
        $company_balance_after   = $creditTransaction["company_balance_after"];
    }

    $mainTransaction->transaction_remarks_id = 5;
    $mainTransaction->save();

    $transaction = transactions::create([
        "balance_before"         => $customer_balance_before,
        "amount"                 => $customer_amount,
        "balance_after"          => $customer_balance_after,
        "description"            => "Ref: $transactionReference funds reversal for failed transfer",
        "account_id"             => $customerAccountId ?? null,
        "transaction_reference"  => generate_txnID(),
        "company_id"             => $companyId ?? null,
        "parent_transaction_id"  => $mainTransaction->id,
        "company_balance_before" => $company_balance_before,
        "company_amount"         => $company_amount,
        "company_balance_after"  => $company_balance_after,
        "transaction_remarks_id" => 2,
        "transaction_type_id"    => 25,
        "credit_debit_type_id"   => 1,
        "created_date"           => date('Y-m-d'),
        "created_by"             => $mainTransaction->created_by,
    ]);

    return ['status' => true, 'message' => 'Debit reversal successful'];
}

function sendVansoSMS($phone, $msg)
{

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://sms.vanso.com/rest/sms/submit',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => '{
            "account": {
                "password": "gLlUpU0y",
                "systemId": "NG.101.0222"
                },
                "sms": {
                    "dest": "' . $phone . '",
                    "src": "Cash Matrix",
                    "text": "' . $msg . '"
                }
            }',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic TkcuMTAxLjAyMjI6Z0xsVXBVMHk=',
        ],
    ]);

    $response = curl_exec($curl);
    logInfo($response, "Vanso response log");
}

function sendEmail($title, $msg, $userinfo)
{
    $datax = [
        'message'        => "Notification",
        'subtext'        => "$title",
        'desc'           => $msg,
        'status'         => 1,
        'name'           => $userinfo->first_name . ' ' . $userinfo->last_name,
        'email'          => $userinfo->email,
        'account_number' => $userinfo->virtual_account,
        'from'           => $userinfo->company->createdby->email,
        'company_name'   => $userinfo->company->company_name,
        'user'           => $userinfo,
    ];
    $to          = $userinfo->email;
    $company     = $userinfo->company;
    $companyname = $company_name = $company->company_name;
    logInfo($userinfo->email, "email data");
    Mail::to($userinfo->email)->send(new AccountCreated($datax));
}

function changePhoneFormatLeadingZero($phone)
{
    $newPhone       = $phone;
    $firstCharacter = substr($newPhone, 0, 3);
    if ($firstCharacter == '234') {
        $str      = ltrim($newPhone, '234');
        $newPhone = '0' . $str;
    }

    return $newPhone;
}

function sendResetEmail($title, $msg, $userinfo)
{
    try {
        $datax = [
            'message'        => "Notification",
            'subtext'        => "$title",
            'desc'           => $msg,
            'status'         => 1,
            'name'           => $userinfo->first_name . ' ' . $userinfo->last_name,
            'email'          => $userinfo->email,
            'account_number' => $userinfo->virtual_account,
            'from'           => $userinfo->company->createdby->email,
            'company_name'   => $userinfo->company->company_name,
            'user'           => $userinfo,
        ];
        $company = $userinfo->company;
        initSMTPconfiguration($company->companyEmail);
        Mail::to($userinfo->email)->send(new AccountCreated($datax));
    } catch (\Exception $e) {
        // Log the exception for internal debugging purposes
        \Log::error($e);

        // Handle specific SMTP authentication errors
        if (
            $e instanceof \Symfony\Component\Mailer\Exception\TransportException
            && $e->getPrevious() instanceof \Symfony\Component\Mailer\Exception\TransportException
        ) {
            $errorDetails = "Failed to send email: Incorrect authentication data. Please check your email credentials.";
            //         email: Incorrect authentication data. Please check your email credentials
            // email: Incorrect authentication data. Please check your email
            // email: Incorrect authentication data.
            // email: Incorrect authentication
            // email: Incorrect
            // ;
        } else {
            // Handle other exceptions generically
            $errorDetails = "Failed to send email: " . $e->getMessage();
        }

        // Return a user-friendly error response
        return (["status" => 0, "message" => $errorDetails]);
    }
}

function whatsapp($phone, $msg, $type = 'transaction', $param = [])
{ //  whatsapp($phone, $msg, 'transaction', [$companywallet, $userinfo, $charge_customer, $parent_txn_id, $session]);
    $i = 1;
    try {
        $amount = config('app.whatsappPrice');

        if (isset($param[3])) {

            $company_wallet       = $param[0];
            $customerInfo         = $param[1];
            $chargeCustomer       = $param[2];
            $txnid                = $param[3];
            $session              = $param[4];
            $auth                 = $session;
            $company              = $session->company;
            $ledgerWhatsappCharge = systemLedgerAccount('Expenses: Company Whatsapp Charge', $auth->company_id)->id;
            $ledgerWhatsappWallet = systemLedgerAccount('Company Whatsapp Wallet', $auth->company_id)->id;
            if ($company_wallet->whatsapp < $amount) {
                return ['status' => 0, 'message' => 'insufficient fund'];
                exit();
            }
            $balanceBefore = $company_wallet->whatsapp;

            $company_wallet->whatsapp -= $amount;
            $whatsappcharge = $company->setting->whatsapp_charge;
            $companyUpdate  = ['whatsapp' => $company_wallet->whatsapp];
            $transactionId  = generate_txnID();
            logInfo($transactionId, "Whatsapp API");
            $data_company = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $amount,
                "withdrawal_charge"      => 0,
                "description"            => "Company Whatsapp Charge",
                "branch_id"              => $customerInfo->branch_id,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 91, //company whatsapp charge
                "credit_debit_type_id"   => 2,  //debit 2
                "transaction_remarks_id" => 1,  //success 2
                "balance_after"          => $company_wallet->balance,
                "created_date"           => date('Y-m-d H:i:s'),
                "created_by"             => $session->id,
                "parent_transaction_id"  => $txnid,
                "ledger_dr"              => $ledgerWhatsappCharge,
                "ledger_cr"              => $ledgerWhatsappWallet,
            ];
            if ($chargeCustomer) {
                if ($data_company != null) {
                    $transaction = transactions::where('id', $txnid)->first();
                    if (! $transaction) {
                        return ['status' => 0, 'message' => 'Transaction error'];
                        exit();
                    }
                    $accountInfodata = wb_account_details::where('id', $transaction->account_id)->first();
                    if ($accountInfodata->user_wallet->balance < $whatsappcharge) {
                        return ['status' => 0, 'message' => 'Insufficient customer fund'];
                        exit();
                    }
                    $balanceBefore  = $accountInfodata->user_wallet->balance;
                    $chargesBalance = $accountInfodata->user_wallet->balance - $whatsappcharge;
                    user_wallet::where('wb_account_detail_id', $accountInfodata->id)->update(['balance' => $chargesBalance]);
                    $data = [
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $whatsappcharge,
                        "withdrawal_charge"      => 0,
                        "description"            => "Customer Whatsapp Charge",
                        "branch_id"              => $customerInfo->branch_id,
                        "transaction_reference"  => generate_txnID(),
                        "transaction_type_id"    => 92, //Customer whatsapp charge
                        "credit_debit_type_id"   => 2,  //debit 2
                        "transaction_remarks_id" => 1,  //success 2
                        "balance_after"          => $chargesBalance,
                        "created_date"           => date('Y-m-d H:i:s'),
                        "created_by"             => $session->id,
                        "parent_transaction_id"  => $txnid,
                        "ledger_cr"              => systemLedgerAccount('Customer WhatsApp Charge', $auth->company_id)->id,
                        "ledger_dr"              => systemLedgerAccount('Customer Saving Deposit', $auth->company_id)->id,
                    ];
                    $transaction = transactions::create($data);
                }
            }
            $transaction = transactions::create($data_company);
            company_wallet::where('id', $company_wallet->id)->update($companyUpdate);
        } else {
            if (Auth::check()) {
                if (isset($param[0])) {
                    $auth                    = auth()->user();
                    $companywallet           = company_wallet::where('company_id', $auth->company_id)->first();
                    $balanceBefore           = $companywallet->whatsapp;
                    $balanceafter            = $balanceBefore - $amount;
                    $companywallet->whatsapp = $balanceafter;
                    $companywallet->save();
                    $customerInfo         = $param[0];
                    $auth                 = $customerInfo;
                    $ledgerWhatsappCharge = systemLedgerAccount('Expenses: Company Whatsapp Charge', $auth->company_id)->id;
                    $ledgerWhatsappWallet = systemLedgerAccount('Company Whatsapp Wallet', $auth->company_id)->id;
                    $data_company         = [
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $amount,
                        "withdrawal_charge"      => 0,
                        "description"            => "Company Whatsapp Charge",
                        "branch_id"              => $customerInfo->branch_id,
                        "transaction_type_id"    => 91, //company whatsapp charge
                        "credit_debit_type_id"   => 2,  //debit 2
                        "transaction_remarks_id" => 1,  //success 2
                        "balance_after"          => $balanceafter,
                        "created_date"           => date('Y-m-d H:i:s'),
                        "created_by"             => $auth->id,
                        "transaction_reference"  => generate_txnID(),
                    ];
                    $transaction = transactions::create($data_company);
                }
            }
        }
        /*  $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://graph.facebook.com/v15.0/111039541873466/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => '{
        "messaging_product": "whatsapp",
        "to": "' . $phone . '",
        "type": "template",
        "template": {
        "name": "' . $type . '",
        "language": {
            "code": "en_US",
            "policy": "deterministic"
        },
        "components": [
            {
            "type": "body",
            "parameters": [
                {
                    "type": "text",
                    "text": "' . $msg . '"
                }
            ]
            }
        ]
        }
    }',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer 77',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response; */
    } catch (Exception $e) {
        // logInfo($e, "Whatsapp API Exception line helper 745");
    }
    $i++;
}
function initSMTPconfiguration($companyEmailConfig)
{
    if (isset($companyEmailConfig->MAIL_FROM_NAME) && isset($companyEmailConfig->MAIL_FROM_ADDRESS)) {
        config(['mail.from.name' => $companyEmailConfig->MAIL_FROM_NAME]);
        config(['mail.from.address' => $companyEmailConfig->MAIL_FROM_ADDRESS]);
    }
    if ($companyEmailConfig->companyemail_active) {
        if (isset($companyEmailConfig->MAIL_HOST) && isset($companyEmailConfig->MAIL_USERNAME) && isset($companyEmailConfig->MAIL_PASSWORD) && isset($companyEmailConfig->MAIL_FROM_NAME)) {
            config(['mail.default' => 'smtp']);
            config(['mail.mailers.smtp.host' => $companyEmailConfig->MAIL_HOST]);
            config(['mail.mailers.smtp.port' => $companyEmailConfig->MAIL_PORT]);
            config(['mail.mailers.smtp.username' => $companyEmailConfig->MAIL_USERNAME]);
            config(['mail.mailers.smtp.password' => $companyEmailConfig->MAIL_PASSWORD]);
            config(['mail.mailers.smtp.encryption' => $companyEmailConfig->MAIL_ENCRYPTION]);
        }
    }
}
function sendSMS($userid, $msg, $type = true, $charge_customer = true, $title = 'Create Account', $accno = '', $usesms = 0, $isApi = false, $all_sms_email = 1, $sms_logdata = [])
{
    try {
        $data_in_array  = 0;
        $external_phone = "";
        $is_external    = false;
        $fname          = '';
        $lname          = '';
        logInfo("$userid", "SMS 2FA userid");

        if (is_array($userid)) {
            $data_in_array = $userid;
            $userid        = $data_in_array['userid'];
            if (isset($data_in_array['isExternal'])) {
                if ($data_in_array['isExternal']) {
                    $external_phone = $data_in_array['phone'];
                    $g_email        = $data_in_array['email'];
                    $is_external    = $data_in_array['isExternal'];
                    $lname          = $data_in_array['lname'];
                    $fname          = $data_in_array['fname'];
                }
            }
        }

        $userType = 1;

        if ($type) {
            $userinfo        = User::find($userid);
            $userType        = $userinfo->user_type_id;
            $accountInfodata = wb_account_details::where('user_id', $userid)->first();
        } else {
            $accountInfodata = wb_account_details::find($userid);
            $userinfo        = User::find($accountInfodata->user_id);
        }
        if ($is_external) {
            $phone                = $external_phone;
            $userinfo->email      = $g_email;
            $userinfo->last_name  = $lname;
            $userinfo->first_name = $fname;
        } else {
            $phone = $userinfo->phone;
        }
        if (auth()->user() == null) {
            $auth = User::where('company_id', $userinfo->company_id)->first();
        } else {
            $auth = auth()->user();
        }

        logInfo("$userinfo->username", "SMS 2FA Username");

        $totalchar  = strlen($msg);
        $sms_charge = $userinfo->company->setting->sms_charge;
        $num_page   = 1;
        if ($totalchar > 150) {
            $num_page = 2;
        }
        $sms_charge *= $num_page;
        $datax = [
            'message'        => "Notification",
            'subtext'        => "$title",
            'desc'           => $msg,
            'status'         => 1,
            'name'           => $userinfo->first_name . ' ' . $userinfo->last_name,
            'email'          => $userinfo->email,
            'account_number' => $accno,
            'from'           => $userinfo->company->createdby->email,
            'company_name'   => $userinfo->company->company_name,
            'user'           => $userinfo,
        ];
        $to = $userinfo->email;

        logInfo("$to", "SMS 2FA to");
        logInfo("$all_sms_email", "SMS 2FA all_sms_email");
        logInfo("$usesms", "SMS 2FA usesms");

        $company = $userinfo->company;
        $session = User::where('company_id', $company->id)->first();

        // }
        if (! $isApi) {

        } else {
            $company_short = $company->short_name;
        }

        if (($all_sms_email == 1 || $all_sms_email == 3) && ! empty($to)) {
            // if ($userinfo->email_verified_at != null) {
            if (isset($session->company->companyEmail->MAIL_USERNAME)) {
                logInfo("{$session->company->companyEmail->MAIL_USERNAME}", "SMS 2FA  mail username");
                // initSMTPconfiguration($session->company->companyEmail);
                // }
                // Mail::to($userinfo->email)->send(new NewAccountCreated($datax));
                sendResetEmail($title, $msg, $userinfo);
            }
        }

        if (Auth::check()) {
            $companywallet = company_wallet::where('company_id', $auth->company_id)->first();
        } else {
            $companywallet = company_wallet::where('company_id', $userinfo->company_id)->first();
        }

        $firstCharacter = substr($phone, 0, 1);
        if ($firstCharacter == 0) {
            $str   = ltrim($phone, '0');
            $phone = '234' . $str;
        }
        if ($all_sms_email == 4) { //for broadcast message
            return whatsapp($phone, $msg, 'transaction', [$userinfo]);
        }
        if ($userinfo->whatsapp == 1) {
            $parent_txn_id = null;
            if (is_array($data_in_array)) {
                $parent_txn_id = $data_in_array['txnid'];
            }
            if (Auth::check()) {
                $session = auth()->user();
            }

            whatsapp($phone, $msg, 'transaction', [$companywallet, $userinfo, $charge_customer, $parent_txn_id, $session]);
        }

        logInfo("$phone", "SMS 2FA phone");
        // $usesms = 1;

        if (($all_sms_email == 1 || $all_sms_email == 2) && ($usesms > 0)) { // if specific sms is tergetted where usesms  is an smsapi ID

            $smsapi          = smsapi::where('id', $usesms)->first();
            $smsapi->api_url = $smsapi->api_url . "&" . $smsapi->sender_variable . "=" . $company_short;
            $msg1            = $msg;
            $msg             = str_replace(' ', '%20', $msg);
            $apicur          = "$smsapi->api_url&$smsapi->phone_variable=$phone&$smsapi->message_variable=$msg";
            $method          = $smsapi->requestMethod->request_method;
            //mail
            $header = [
                'Content-Type' => $smsapi->content_type,
            ];
            if ($smsapi->authorization != null) {
                $header['Authorization'] = $smsapi->authorization;
            }
            if ($method == 'POST') {
                if ($smsapi->name == 'Type 1') {
                    if (! $isApi) {
                        $company_short = $company->short_name;
                    } else {
                        $company_short = "Cash Matrix";
                    }

                    sendSMSWithVanso($phone, $company_short, $msg1);
                } else if ($smsapi->name == 'Type 7') {
                    // curl --location --request POST 'https://konnect.dotgo.com/api/v1/Accounts/1EYPUrmut4yQrg2kkYs_FA==/Messages' \
                    // --header 'Authorization: R3neQIQiwEvzISbolgv_vJio3xQhZGteN0eelO5+NVA=' \
                    // --header 'Content-Type: application/json' \
                    // --data-raw '{
                    //     "id":"4000006578",
                    //     "to":["2348055988585","2349030306672"],
                    //     "sender_mask":"Lowo",
                    //     "body":"2Your charging for Subscription is successful, you are subscribed for a period of 30 days"
                    //     }'
                } else {
                    $response = Http::withHeaders($header)->post($smsapi->api_url, [
                        $smsapi->phone_variable   => $phone,
                        $smsapi->message_variable => $msg,
                    ]);
                }
                $sms_logdata = [
                    'user_id'    => $userinfo->id,
                    'subject'    => $title,
                    'message'    => $msg,
                    'num_page'   => $num_page,
                    'amount'     => $sms_charge,
                    'hello'      => "fhdf",
                    'created_by' => (Auth::check()) ? auth()->user()->id : $session->id,
                ];
                $notify = Notification::create($sms_logdata);
                return $response;
            } else {
                if ($smsapi->authorization == null && $smsapi->authorization == null) {
                    $response = Http::get($apicur);
                } else {
                    $response = Http::withHeaders($header)->get($apicur);
                }
                $sms_logdata = [
                    'user_id'    => $userinfo->id,
                    'subject'    => $title,
                    'message'    => $msg,
                    'num_page'   => $num_page,
                    'amount'     => $sms_charge,
                    'hello'      => "fhdf",
                    'created_by' => (Auth::check()) ? auth()->user()->id : $session->id,
                ];
                //}
                // if($sms_charge > 0){
                $notify = Notification::create($sms_logdata);
                // die(var_dump($sms_logdata));
                if (! $notify) {
                    return $notify;
                }
                return $response;
            }
        }
        if ($all_sms_email == 1 || $all_sms_email == 2) {
            if ($userinfo->sms == 1 || $is_external == true) {
                $setting = $company->setting;
                logInfo("$setting", "SMS 2FA setting");
                logInfo("$sms_charge", "SMS 2FA sms charge");
                logInfo("$charge_customer", "SMS 2FA charge customer");
                if ($setting->allsms_state) {
                    $sms_charge               = $setting->sms_charge;
                    $can_proceed              = false;
                    $sms_charge_frequencie_id = $setting->sms_charge_frequencie_id;
                    if (1 == $sms_charge_frequencie_id) { //if instant
                        if ($charge_customer) {
                            if ($userType == 1) {
                                $mywallet  = $accountInfodata->user_wallet;
                                $mybalance = $mywallet->balance;
                            } else {
                                $mywallet  = $userinfo->company->wallet;
                                $mybalance = $mywallet->balance;
                            }
                            if ($sms_charge <= $mybalance && $companywallet->sms >= $sms_charge) {
                                $can_proceed       = true;
                                $user              = $mywallet;
                                $userbalanceBefore = $user->balance;
                                $user->balance     = $user->balance - $sms_charge;
                                $user->save();
                                $parent_txn_id = null;
                                if (is_array($data_in_array)) {
                                    $parent_txn_id = $data_in_array['txnid'];
                                }
                                // logInfo($accountInfodata->id, "before inserting transaction");
                                logInfo($userinfo->id, "staff ID before inserting transaction");
                                $data = [
                                    "balance_before"         => $userbalanceBefore,
                                    "amount"                 => $sms_charge,
                                    "withdrawal_charge"      => 0,
                                    "description"            => 'Instant SMS Charge',
                                    "account_id"             => $userType == 1 ? $accountInfodata->id : null,
                                    "branch_id"              => $userinfo->branch_id,
                                    "company_id"             => $userinfo->company_id,
                                    "transaction_reference"  => generate_txnID(),
                                    "transaction_type_id"    => 33, //debit 7
                                    "company_balance_before" => $mybalance,
                                    "company_amount"         => $sms_charge,
                                    "company_balance_after"  => $mybalance - $sms_charge,
                                    "credit_debit_type_id"   => 2, //debit 2
                                    "transaction_remarks_id" => 2, //success 2
                                    "balance_after"          => $user->balance,
                                    "created_date"           => date('Y-m-d'),
                                    "created_by"             => $userinfo->id,
                                    "parent_transaction_id"  => $parent_txn_id,
                                    "ledger_cr"              => systemLedgerAccount('Customer SMS Charge', $auth->company_id)->id,
                                    "ledger_dr"              => systemLedgerAccount('Customer Saving Deposit', $auth->company_id)->id,
                                ];
                                if ($sms_charge > 0) {
                                    $smstxt      = transactions::create($data);
                                    $sms_logdata = [
                                        'user_id'    => $userinfo->id,
                                        'subject'    => $title,
                                        'message'    => $msg,
                                        'num_page'   => $num_page,
                                        'amount'     => $sms_charge,
                                        'hello'      => "fhdf",
                                        'created_by' => (Auth::check()) ? auth()->user()->id : $session->id,
                                    ];
                                    $notify = Notification::create($sms_logdata);
                                    fundVault(['type' => 'sms_charge', 'value' => $sms_charge, 'company_id' => $userinfo->company_id, 'created_by' => $userinfo->id, 'branch_id' => $userinfo->branch_id, 'transaction_id' => $smstxt->id]); //173
                                }
                            } else {
                                return "no money in balance";
                            }
                        } else {
                            $can_proceed = true;
                        }
                    } else {

                        $can_proceed = true;
                        $due         = date('Y-m-d');
                        if ($sms_charge_frequencie_id == 2) {
                            $due = add_day_to_date(7, $due);
                        }
                        if ($sms_charge_frequencie_id == 3) {
                            $due = add_day_to_date(30, $due);
                        }
                        if ($sms_charge_frequencie_id == 4) {
                            $due = add_day_to_date(365, $due);
                        }
                        $tempcharge = [];
                        if ($userType == 1) {
                            $tempcharge = [
                                'wb_account_detail_id' => $accountInfodata->id,
                                'amount'               => $sms_charge,
                                'type'                 => 'SMS',
                                'date_due'             => $due,
                                'status'               => 0,
                                'created_by'           => $auth->id,
                            ];
                            Temp_Charges::create($tempcharge);
                        }
                    }
                    logInfo("$can_proceed", "SMS 2FA can proceed status");
                    if ($can_proceed) {
                        $smsapi = $setting->smsapi;
                        $msg1   = $msg;

                        //          $smsapi->api_url = $smsapi->api_url . "&" . $smsapi->sender_variable . "=" . $company_short;
                        // $msg    = str_replace(' ', '%20', $msg);
                        $apicur = "$smsapi->api_url&$smsapi->phone_variable=$phone&$smsapi->message_variable=$msg";
                        $method = $smsapi->requestMethod->request_method;
                        //wba SMS charge
                        $proceedTwo = false;
                        if ($smsapi->access_setup) {
                            if ($companywallet->sms > $smsapi->smscharge) {
                                if ($smsapi->id == 20 && $company->id == 38) {
                                    //ignore tmc capital
                                    $proceedTwo = true;
                                } else {
                                    $companywallet->sms = $companywallet->sms - $smsapi->smscharge;
                                }
                                //log company debit here
                                if ($companywallet->save()) {
                                    $proceedTwo = true;
                                }
                            }
                        } else {
                            $proceedTwo = true;
                        }
                        logInfo("$proceedTwo", "SMS 2FA can proceed two status");
                        if ($proceedTwo) {
                            $response = null;
                            //mail
                            $header = [
                                'Content-Type' => $smsapi->content_type,

                            ];
                            logInfo("$smsapi->authorization", "SMS 2FA authorization");
                            if ($smsapi->authorization != null) {
                                $header['Authorization'] = $smsapi->authorization;
                            }
                            logInfo("$method", "SMS 2FA sms api method");
                            if ($method == 'POST') {
                                logInfo("$smsapi->name", "SMS 2FA sms api name");
                                logInfo("$smsapi->id", "SMS 2FA sms api id");
                                if ($smsapi->name == 'Type 1' || $smsapi->id == 20) {

                                    $firstCharacter = substr($phone, 0, 1);
                                    if ($firstCharacter == 0) {
                                        $str   = ltrim($phone, '0');
                                        $phone = '234' . $str;
                                    }
                                    if (! $isApi) {
                                        $company_short = $company->short_name;
                                    } else {
                                        $company_short = "WBA";
                                    }
                                    if ($company->id == 38) {
                                        raw_venso_api($phone, $company_short, $msg1, 'NG.104.0321', 'Tqvjk9dV', 'TkcuMTA0LjAzMjE6VHF2ams5ZFY=');
                                    } else {
                                        raw_venso_api($phone, $company_short, $msg1, 'NG.101.0222', 'gLlUpU0y', 'TkcuMTAxLjAyMjI6Z0xsVXBVMHk=');
                                    }
                                } else {
                                    $response = Http::withHeaders($header)->post($smsapi->api_url, [
                                        $smsapi->phone_variable   => $phone,
                                        $smsapi->message_variable => $msg,
                                    ]);
                                }
                            } else {
                                if ($smsapi->authorization == null && $smsapi->authorization == null) {

                                    /*  if ($company->short_name == "GCREDIT") {
                                        logInfo("{$smsapi->api_url}", "SMS 2FA Get api response Golden Credit");

                                    } else {
                                        logInfo("{$apicur}", "SMS 2FA Get api response");

                                        $response = Http::get($apicur);
                                    }*/
                                    logInfo("{$apicur}", "SMS 2FA Get api response");

                                    // $response = Http::get($apicur);
                                    $response = smsCustomer($phone, $msg, $company->short_name, $title);
                                    //$response = smsCustomer($phone, $msg, $company->short_name);

                                } else {
                                    logInfo("{$apicur}", "SMS 2FA Get api response with Headers");
                                    $response = Http::withHeaders($header)->get($apicur);
                                }
                            }
                            //$result = $response->body();
                            $result = $response;
                            logInfo(json_encode($result), "SMS 2FA sms api response");
                            if (isset($userinfo->id)) {
                                $sms_logdata = [
                                    'user_id'    => $userinfo->id,
                                    'subject'    => $title,
                                    'message'    => str_replace('%20', ' ', $msg),
                                    'num_page'   => $num_page,
                                    'amount'     => $sms_charge,
                                    'hello'      => "fhdf",
                                    'created_by' => (Auth::check()) ? auth()->user()->id : $session->id,
                                ];
                                $result_sms_data = json_encode($sms_logdata);
                                logInfo("{$result_sms_data}", "SMS 2FA sms api log Data");

                                $notify = Notification::create($sms_logdata);
                            }
                            return $response;
                        } else {
                            return "Company Wallet empty" . $companywallet->sms . ">." . $smsapi->smscharge;
                        }
                    } else {
                        return "can not proceed";
                    }
                } else {
                    return "all sms off";
                }
            } else {
                return "user sms off";
            }
        }
    } catch (Exception $e) {
        return 'Message: ' . $e->getMessage() . ' ' . $e->getLine();
    }
}
function raw_venso_api($phone, $company_short, $msg1, $systemID = 'NG.101.0222', $password = 'gLlUpU0y', $basicAuth = 'TkcuMTAxLjAyMjI6Z0xsVXBVMHk=')
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://sms.vanso.com/rest/sms/submit',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => '{
                                            "account": {
                                                "password": "' . $password . '",
                                                "systemId": "' . $systemID . '"
                                            },
                                            "sms": {
                                                "dest": "' . $phone . '",
                                                "src": "' . $company_short . '",
                                                "text": "' . $msg1 . '"
                                            }
                                        }',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $basicAuth,
        ],
    ]);
    $response = curl_exec($curl);
    logInfo($response, "vanso sms log4");
    curl_close($curl);
    return $response;
}
function sendSMSWithHollatag($phone, $company_short, $msg1)
{
    $curl = curl_init();

    $message = urlencode($msg1);

    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://sms.hollatags.com/api/send/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => "user=shema&pass=shema%40123&from=$company_short&to=$phone&msg=$message",
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: nuM_session=p%2B9P3hjrQUl4bV2BALzBEz%2FRSenfJuvpFreFbySNZ%2Ff2qQG%2Bz81RGa%2F2AVMYil8TCfmP5BxQPgndcwBjZ%2FbRLq58w8T8MFzd%2BhL6kHRY7xZeAIOmNx6BEHEW8F0ASgirUGu4%2FT9FBsDX0hn5Kr5LeKMPXxBZOx3pp%2FdUyBmfiCeXMcOJhbWXAiPOLxeAnbvh1wgtYgBE8wus4LC01Wu1sx%2BVrjiYWnVLw3%2FZtGoD6n6f9MpHsfsQ9%2FxUuEjcp66SPULN24RVSh0UgTP3WKUWQU9W77f1Qee1L%2B5R8TlvOIo%3D',
        ],
    ]);

    $response = curl_exec($curl);

    curl_close($curl);
    $response;
}

function sendSMSWithVanso($phone, $company_short, $msg1)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://sms.hollatags.com/api/send/?user=shema&pass=shema@123',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => '{
            "account": {
                "password": "gLlUpU0y",
                "systemId": "NG.101.0222"
                },
                "sms": {
                    "dest": "' . $phone . '",
                    "src": "' . $company_short . '",
                    "text": "' . $msg1 . '"
                }
            }',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic shema=',
        ],
    ]);

    $response = curl_exec($curl);

    logInfo($response, "vanso sms log");

    curl_close($curl);
}

function checkdate_day($n)
{
    $day = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Sarturday', 'Sunday'];
    return $day[$n];
}

function nomoney()
{
    return "Insufficient Balance";
}
function checkcustomerrestriction($userid, $restriction)
{
    //->orwhere(['user_id' => $userid,'customer_permissions.permission'=>'block-transaction'])
    $customerrestriction = DB::select("SELECT count(*) as restrictionCount FROM `main_customer_restrictions` WHERE `user_id` = $userid AND (`customer_permission_id` = 3 OR `customer_permission_id` = $restriction)");

    if ($customerrestriction[0]->restrictionCount > 0) {
        return true;
    }
    return false;
}
function checkcustomerrestrictionData($userid, $restriction)
{
    return DB::select("SELECT remark FROM `main_customer_restrictions` WHERE `user_id` = $userid AND (`customer_permission_id` = 3 OR `customer_permission_id` = $restriction)");
}

function getBanksFromDB($type)
{
    return $banks = $type == 'long' ? banks::orderBy('bank_name', 'ASC')->get() : banks::where('short_code', '<>', '')->get(['id', 'bank_name', 'short_code AS bank_code']);
}

function checkCustomerBalance($accountId, $amount)
{

    $response = [];

    if ($amount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $accountId)->first();

        if ($userWallet->balance >= $amount) {
            $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => $accountId, 'balance' => $userWallet->balance];
        } else {
            $response = ["status" => false, 'message' => "Insufficient Funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function checkCompanyBalance($amount)
{

    $response = [];

    if ($amount > 0) {

        $userWallet = company_wallet::where('company_id', auth()->user()->company_id)->first();

        if ($userWallet->balance >= $amount) {
            $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->company_id, 'balance' => $userWallet->balance];
        } else {
            $response = ["status" => false, 'message' => "Insufficient Funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;

}

function getCompanyBankTransferCharge($companyId, $amount)
{
    $charge = 0;

    $response = [];

    if ($amount > 0) {
        $companySettings = company_settings::where('company_id', $companyId)->first();

        if ($companySettings->bank_transfer_charge_state == 1) {
            $charge = $amount * ($companySettings->min_bank_transfer_charge_or_percentage / 100);
            $charge = $companySettings->max_bank_transfer_charge < $charge ? $companySettings->max_bank_transfer_charge : $charge;
        } else {
            $charge = $companySettings->max_bank_transfer_charge;
        }
        $response = ["status" => true, 'message' => "Successfully fetched Charge", 'data' => ['charge' => $charge]];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function creditCustomerAndCompany($customerAccountId, $companyId, $customerAmount, $companyAmount)
{

    $response = [];

    if ($customerAmount > 0 || $companyAmount > 0) {

        $userWallet             = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();
        $customerInitialBalance = $userWallet->balance;
        $customerFinalBalance   = $userWallet->balance + $customerAmount;
        $userWallet->balance    = $customerFinalBalance;
        $userWallet->save();
        if (isset($userWallet->wb_account_details->user->branch_id)) {
            $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
            $BranchWallet->balance += $customerAmount;
            $BranchWallet->save();
        }
        $company               = Company::find($companyId);
        $companyInitialBalance = $company->wallet->balance;
        $company->wallet->balance += $companyAmount;
        $company->wallet->save();

        $response = [
            "status"                 => true,
            'message'                => "Amount is Valid",
            'account_id'             => $customerAccountId,
            'balance_before'         => $customerInitialBalance,
            'balance_after'          => $customerFinalBalance,
            'company_balance_before' => $companyInitialBalance,
            'company_balance_after'  => $company->wallet->balance,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function creditCustomerAndCompanyWithCharge($customerAccountId, $companyId, $originalAmount, $customerCharge, $companyCharge)
{

    $response = [];

    if ($originalAmount > 0) {

        $userWallet             = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();
        $customerInitialBalance = $userWallet->balance;
        $customerBalanceAfter   = $customerInitialBalance + $originalAmount;

        // $customerChargeBalanceAfter = $customerBalanceAfter - $customerCharge;
        $amountPay            = ($originalAmount - $customerCharge);
        $customerFinalBalance = $userWallet->balance += $amountPay;
        $userWallet->save();
        if (isset($userWallet->wb_account_details->user->branch_id)) {
            $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
            $BranchWallet->balance += $amountPay;
            $BranchWallet->save();
        }

        $company               = Company::find($companyId);
        $companyCurrentBalance = $company->wallet->balance;
        $companyBalanceAfter   = $company->wallet->balance + $originalAmount;
        $companyFinalBalance   = $company->wallet->balance += ($originalAmount - $companyCharge);
        $company->wallet->save();

        $response = [
            "status"                        => true,
            'message'                       => "Amount is Valid",
            'account_id'                    => $customerAccountId,
            'company_balance_before'        => $companyCurrentBalance,
            'company_balance_after'         => $companyBalanceAfter,
            'company_charge_balance_before' => $companyBalanceAfter,
            'company_charge_balance_after'  => $companyFinalBalance,
            'balance_before'                => $customerInitialBalance,
            'balance_after'                 => $customerBalanceAfter,
            'charge_balance_before'         => $customerBalanceAfter,
            'charge_balance_after'          => $customerFinalBalance,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function creditPOSTerminal($terminalCode, $originalAmount, $charge, $companyCharge, $companyId)
{

    $response = [];

    if ($originalAmount > 0) {

        $agentTerminal = AgentTerminal::where('terminal_code', $terminalCode)->first();

        if (! $agentTerminal) {
            $response = ["status" => false, 'message' => "Invalid POS Terminal"];
        }

        $currentAgentBalance = $agentTerminal->balance;
        $agentBalanceAfter   = $currentAgentBalance + $originalAmount;
        $customerCharge      = $charge + $companyCharge;
        $amountToCredit      = $originalAmount - $customerCharge;
        $finalAgentBalance   = $agentTerminal->balance += $amountToCredit;
        $agentTerminal->save();

        $company               = Company::find($companyId);
        $companyCurrentBalance = $company->wallet->balance;
        $companyFinalBalance   = $company->wallet->balance += ($charge);
        $company->wallet->save();

        $response = [
            "status"                    => true,
            'message'                   => "Amount is Valid",
            'company_balance_before'    => $companyCurrentBalance,
            'company_balance_after'     => $companyFinalBalance,
            'company_amount'            => $charge,
            'pos_balance_before'        => $currentAgentBalance,
            'pos_balance_after'         => $agentBalanceAfter,
            'pos_amount'                => $amountToCredit,
            'pos_charge_balance_before' => $agentBalanceAfter,
            'pos_charge_balance_after'  => $finalAgentBalance,
            'pos_charge_amount'         => $charge,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function sweepCustomerAccountAndCreditCompanyWithCharge($customerAccountId, $companyId, $originalAmount, $customerCharge, $companyCharge)
{

    $response = [];

    if ($originalAmount > 0) {

        $userWallet             = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();
        $customerInitialBalance = $userWallet->balance;
        $customerBalanceAfter   = $customerInitialBalance + $originalAmount;

        // $customerChargeBalanceAfter = $customerBalanceAfter - $customerCharge;

        $customerFinalBalance = $userWallet->balance += ($originalAmount - $customerCharge);

        $company               = Company::find($companyId);
        $companyCurrentBalance = $company->wallet->balance;
        $companyBalanceAfter   = $company->wallet->balance + $originalAmount;
        $companyFinalBalance   = $company->wallet->balance += ($originalAmount - $companyCharge);
        $company->wallet->save();

        $response = [
            "status"                        => true,
            'message'                       => "Amount is Valid",
            'account_id'                    => $customerAccountId,
            'company_balance_before'        => $companyCurrentBalance,
            'company_balance_after'         => $companyBalanceAfter,
            'company_charge_balance_before' => $companyBalanceAfter,
            'company_charge_balance_after'  => $companyFinalBalance,
            'balance_before'                => $customerInitialBalance,
            'balance_after'                 => $customerInitialBalance,
            'charge_balance_before'         => $customerInitialBalance,
            'charge_balance_after'          => $customerInitialBalance,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function creditCustomerOnly($customerAccountId, $originalAmount, $charge = 0.0)
{

    $response = [];

    if ($originalAmount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->lockForUpdate()->first();

        logInfo($userWallet, "User wallet log");
        $customerInitialBalance = $userWallet->balance;

        $creditAmount = $originalAmount - $charge;

        $customerBalanceAfter = $userWallet->balance += $creditAmount;
        $userWallet->save();
        if (isset($userWallet->wb_account_details->user->branch_id)) {
            $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
            $BranchWallet->balance += $creditAmount;
            $BranchWallet->save();
        }

        $response = [
            "status"         => true,
            'message'        => "Amount is Valid",
            'amount'         => $creditAmount,
            'account_id'     => $customerAccountId,
            'balance_before' => $customerInitialBalance,
            'balance_after'  => $customerBalanceAfter,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitCustomerOnly($customerAccountId, $originalAmount, $user)
{

    $response = [];

    if ($originalAmount > 0) {

        $userWallet             = user_wallet::where('wb_account_detail_id', $customerAccountId)->lockForUpdate()->first();
        $customerInitialBalance = $userWallet->balance;

        $minBalance = $user->company->setting->min_withdraw_charge;

        if (($customerInitialBalance - $minBalance) > $originalAmount) {

            $customerBalanceAfter = $userWallet->balance -= $originalAmount;
            $userWallet->save();
            if (isset($userWallet->wb_account_details->user->branch_id)) {
                $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                $BranchWallet->balance -= $originalAmount;
                $BranchWallet->save();
            }
            $response = [
                "status"         => true,
                'message'        => "Amount is Valid",
                'account_id'     => $customerAccountId,
                'balance_before' => $customerInitialBalance,
                'balance_after'  => $customerBalanceAfter,
            ];
        } else {
            $response = ["status" => false, 'message' => "Not Sufficient funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitCustomerWithCharge($customerAccountId, $originalAmount, $charge, $user)
{

    $response = [];

    if ($originalAmount > 0) {

        $userWallet             = user_wallet::where('wb_account_detail_id', $customerAccountId)->lockForUpdate()->first();
        $customerInitialBalance = $userWallet->balance;

        $debitAmount = $originalAmount + $charge;

        $minBalance = $user->company->setting->min_withdraw_charge;

        if (($customerInitialBalance - $minBalance) > $debitAmount) {

            $customerBalanceAfter = $userWallet->balance -= $debitAmount;
            $userWallet->save();

            if (isset($userWallet->wb_account_details->user->branch_id)) {
                $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                $BranchWallet->balance -= $debitAmount;
                $BranchWallet->save();
            }

            $response = [
                "status"         => true,
                'amount'         => $debitAmount,
                'message'        => "Amount is Valid",
                'message'        => "Amount is Valid",
                'account_id'     => $customerAccountId,
                'balance_before' => $customerInitialBalance,
                'balance_after'  => $customerBalanceAfter,
            ];
        } else {
            $response = ["status" => false, 'message' => "Not Sufficient funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitPOSTerminalWithCharge($terminalId, $originalAmount, $charge, $companyCharge, $user)
{

    $response = [];

    try {

        if ($originalAmount > 0) {

            $userPOS = AgentTerminal::where('id', $terminalId)->first();

            $customerInitialBalance = $userPOS->balance;

            $debitAmount = $originalAmount + $charge + $companyCharge;

            $minBalance = $user->company->setting->min_withdraw_charge;

            if (($customerInitialBalance - $minBalance) > $debitAmount) {

                $customerBalanceAfter = $userPOS->balance -= $debitAmount;
                $userPOS->save();

                $companyWallet = company_wallet::where('company_id', $user->company_id)->lockForUpdate()->first();

                $companyBalanceBefore = $companyWallet->balance;
                $companyBalanceAfter  = $companyWallet->balance += $charge;
                $companyWallet->save();

                $response = [
                    "status"                 => true,
                    'amount'                 => $debitAmount,
                    'message'                => "Amount is Valid",
                    'balance_before'         => $customerInitialBalance,
                    'balance_after'          => $customerBalanceAfter,
                    'company_balance_before' => $companyBalanceBefore,
                    'company_amount'         => $charge,
                    'company_balance_after'  => $companyBalanceAfter,
                ];
            } else {
                $response = ["status" => false, 'responseCode' => 0, 'message' => "Not Sufficient funds"];
            }
        } else {
            $response = ["status" => false, 'responseCode' => 1, 'message' => "Invalid Transaction Amount"];
        }
        return $response;

    } catch (Exception $e) {
        logInfo($e->getMessage(), "Server error things");
        $response = ["status" => false, 'message' => "Server error"];
    }
}

function debitCustomerAndCompanyWithCharge($customerAccountId, $companyId, $originalAmount, $customerCharge, $companyCharge, $user = null)
{
    logInfo($customerAccountId, "customerAccountId");
    logInfo($companyId, "companyId");

    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->lockForUpdate()->first();

        if (! $userWallet) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
        }

        logInfo(json_encode($userWallet), "User to be debitted");

        $customerInitialBalance = $userWallet->balance;

        logInfo($originalAmount, "Original Amount to be debitted");
        logInfo($customerCharge, "Charge to be debitted");
        if ($customerInitialBalance > ($originalAmount + $customerCharge)) {

            $company = Company::find($companyId);

            $companyIDs = [1, 29, 36, 43];

            $companyIDsStatus = false;

            if (in_array($company->id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            $companyWallet = company_wallet::where('company_id', $companyId)->lockForUpdate()->first();

            logInfo(json_encode($companyWallet), "Company to be debitted");

            $companyAfterBalance = $companyWallet->balance - ($originalAmount + $customerCharge);

            logInfo(json_encode($companyAfterBalance), "Company after balance");

            if ($companyWallet->balance > 100000 && ($companyAfterBalance > 100000)) {
                $companyIDsStatus = true;
            }

            if (($companyWallet->balance > ($originalAmount + $customerCharge)) && ($companyIDsStatus)) {

                $customerBalanceAfter = $customerInitialBalance - $originalAmount;
                $paymentcash          = ($originalAmount + $customerCharge);
                $customerFinalBalance = $userWallet->balance -= $paymentcash;

                if ($userWallet->save()) {
                    $BranchWallet = BranchWallet::where('branch_id', $currentUser->branch_id)->first();
                    $BranchWallet->balance -= $paymentcash;
                    $BranchWallet->save();
                }
                $companyInitialBalance = $companyWallet->balance;
                $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                $companyFinalBalance = $companyWallet->balance -= ($originalAmount + $companyCharge);
                $companyWallet->save();

                $currentUser->balance_fails = 0;
                $currentUser->save();

                $response = [
                    "status"                        => true,
                    'message'                       => "Amount is Valid",
                    'account_id'                    => $customerAccountId,
                    'balance_before'                => $customerInitialBalance,
                    'balance_after'                 => $customerBalanceAfter,
                    'charge_balance_before'         => $customerBalanceAfter,
                    'charge_balance_after'          => $customerFinalBalance,
                    'company_balance_before'        => $companyInitialBalance,
                    'company_amount'                => $originalAmount,
                    'company_balance_after'         => $companyBalanceAfter,
                    'company_charge_balance_before' => $companyBalanceAfter,
                    'company_charge_balance_after'  => $companyFinalBalance,
                ];

                logInfo(json_encode($response), "debitCustomerAndCompanyWithCharge");

            } else {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
            }
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds Customer Funds"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Insuficient balance trials");

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function checkCustomerAndCompanyWithCharge($customerAccountId, $companyId, $originalAmount, $customerCharge, $companyCharge, $user = null)
{
    logInfo($customerAccountId, "customerAccountId");
    logInfo($companyId, "companyId");

    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->lockForUpdate()->first();

        if (! $userWallet) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
        }

        logInfo(json_encode($userWallet), "User to be debitted");

        $customerInitialBalance = $userWallet->balance;

        logInfo($originalAmount, "Original Amount to be debitted");
        logInfo($customerCharge, "Charge to be debitted");
        if ($customerInitialBalance > ($originalAmount + $customerCharge)) {

            $company = Company::find($companyId);

            $companyIDs = [1, 29, 36, 43];

            $companyIDsStatus = false;

            if (in_array($company->id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            $companyWallet = company_wallet::where('company_id', $companyId)->lockForUpdate()->first();

            logInfo(json_encode($companyWallet), "Company to be debitted");

            $companyAfterBalance = $companyWallet->balance - ($originalAmount + $customerCharge);

            logInfo(json_encode($companyAfterBalance), "Company after balance");

            if ($companyWallet->balance > 100000 && ($companyAfterBalance > 100000)) {
                $companyIDsStatus = true;
            }

            if (($companyWallet->balance > ($originalAmount + $customerCharge)) && ($companyIDsStatus)) {

                $customerBalanceAfter = $customerInitialBalance - $originalAmount;
                $paymentcash          = ($originalAmount + $customerCharge);
                $customerFinalBalance = $userWallet->balance -= $paymentcash;

                if ($userWallet) {
                    $BranchWallet = BranchWallet::where('branch_id', $currentUser->branch_id)->first();
                    $BranchWallet->balance -= $paymentcash;
                    //      $BranchWallet->save();
                }
                $companyInitialBalance = $companyWallet->balance;
                $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                $companyFinalBalance = $companyWallet->balance -= ($originalAmount + $companyCharge);
                //     $companyWallet->save();

                $currentUser->balance_fails = 0;
                //    $currentUser->save();

                $response = [
                    "status"                        => true,
                    'message'                       => "Amount is Valid",
                    'account_id'                    => $customerAccountId,
                    'balance_before'                => $customerInitialBalance,
                    'balance_after'                 => $customerBalanceAfter,
                    'charge_balance_before'         => $customerBalanceAfter,
                    'charge_balance_after'          => $customerFinalBalance,
                    'company_balance_before'        => $companyInitialBalance,
                    'company_amount'                => $originalAmount,
                    'company_balance_after'         => $companyBalanceAfter,
                    'company_charge_balance_before' => $companyBalanceAfter,
                    'company_charge_balance_after'  => $companyFinalBalance,
                ];

                logInfo(json_encode($response), "debitCustomerAndCompanyWithCharge");

            } else {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
            }
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds Customer Funds"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Insuficient balance trials");

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function tryToDebitCustomerAndCompanyWithCharge($customerAccountId, $companyId, $originalAmount, $customerCharge, $companyCharge, $user = null)
{

    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

        if (! $userWallet) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
        }

        //  logInfo($userWallet, "User to be debitted");

        $customerInitialBalance = $userWallet->balance;

        logInfo($originalAmount, "Original Amount to be debitted");
        logInfo($customerCharge, "Charge to be debitted");
        if ($customerInitialBalance > ($originalAmount + $customerCharge)) {

            $company = Company::find($companyId);
            if ($company->wallet->balance > ($originalAmount + $customerCharge)) {

                $customerBalanceAfter = $customerInitialBalance - $originalAmount;
                $paymentcash          = ($originalAmount + $customerCharge);
                $customerFinalBalance = $userWallet->balance -= $paymentcash;

                // if ($userWallet->save()) {
                $BranchWallet = BranchWallet::where('branch_id', $currentUser->branch_id)->first();
                $BranchWallet->balance -= $paymentcash;
                // $BranchWallet->save();
                // }
                $companyInitialBalance = $company->wallet->balance;
                $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                $companyFinalBalance = $company->wallet->balance -= ($originalAmount + $companyCharge);
                // $company->wallet->save();

                $currentUser->balance_fails = 0;
                $currentUser->save();

                $response = [
                    "status"                        => true,
                    'message'                       => "Amount is Valid",
                    'account_id'                    => $customerAccountId,
                    'balance_before'                => $customerInitialBalance,
                    'balance_after'                 => $customerBalanceAfter,
                    'charge_balance_before'         => $customerBalanceAfter,
                    'charge_balance_after'          => $customerFinalBalance,
                    'company_balance_before'        => $companyInitialBalance,
                    'company_amount'                => $originalAmount,
                    'company_balance_after'         => $companyBalanceAfter,
                    'company_charge_balance_before' => $companyBalanceAfter,
                    'company_charge_balance_after'  => $companyFinalBalance,
                ];
            } else {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
            }
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds Customer Funds"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Insuficient balance trials");

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitCustomerAndCompanyPOSWithChargeAndCommission($customerAccountId, $terminalCode, $companyId, $originalAmount, $customerCharge, $companyCommission, $user = null)
{
    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $userPOS = AgentTerminal::where('terminal_code', $terminalCode)->first();

        if (! $userPOS) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid POS terminal Code"];
        }

        $customerInitialBalance = $userPOS->balance;

        if ($customerInitialBalance > ($originalAmount + $customerCharge)) {

            $company = Company::find($companyId);

            $customerBalanceAfter = $customerInitialBalance - $originalAmount;

            $customerFinalBalance = $userPOS->balance -= ($originalAmount + $customerCharge);
            $userPOS->save();

            $companyInitialBalance = $company->wallet->balance;
            $companyBalanceAfter   = $companyInitialBalance;

            $companyFinalBalance = $company->wallet->balance += ($companyCommission);
            $company->wallet->save();

            $currentUser->balance_fails = 0;
            $currentUser->save();

            $response = [
                "status"                        => true,
                'message'                       => "Amount is Valid",
                'account_id'                    => $customerAccountId,
                'pos_amount'                    => $originalAmount + $customerCharge,
                'pos_balance_before'            => $customerInitialBalance,
                'pos_balance_after'             => $customerFinalBalance,
                'pos_charge_balance_before'     => $customerBalanceAfter,
                'pos_charge_balance_after'      => $customerFinalBalance,
                'company_balance_before'        => $companyInitialBalance,
                'company_amount'                => $companyCommission,
                'company_balance_after'         => $companyFinalBalance,
                'company_charge_balance_before' => $companyBalanceAfter,
                'company_charge_balance_after'  => $companyFinalBalance,
            ];
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Insuficient balance trials", $currentUser);

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function checkCompanyAllowedTransactionStatus($companyId)
{
    $company = Company::find($companyId);

    if (! $company) {
        return ["status" => 0, 'message' => "Company Not found"];
    }

    if ($company->allow_transaction_setting == 4) {
        return ["status" => $company->allow_transaction_setting, 'message' => "Sorry we are unable to honor your request at the moment please try again later."];
    }

    return ["status" => $company->allow_transaction_setting, 'message' => "Sorry, $company->company_name is not profiled for bank transfer at the moment due to network downtime , please try again later or  contact support"];
}

function creditCustomerAndCompanyPOSAmountWithCharge($customerAccountId, $terminal_code, $companyId, $originalAmount, $customerCharge, $companyCharge, $user = null)
{

    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $agentTerminal = AgentTerminal::where('terminal_code', $terminal_code)->first();

        if (! $agentTerminal) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid POS Terminal Code"];
        }

        $posInitialBalance = $agentTerminal->balance;

        $company = Company::find($companyId);

        $posBalanceAfter = $posInitialBalance - $originalAmount;

        //Debit POS Terminal
        $customerFinalBalance = $agentTerminal->balance -= ($originalAmount + $customerCharge);
        $agentTerminal->save();

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

        //Credit Customer Account
        $customerInitialBalance = $userWallet->balance;
        $customerBalanceAfter   = $userWallet->balance += $originalAmount;
        $userWallet->save();
        if (isset($userWallet->wb_account_details->user->branch_id)) {
            $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
            $BranchWallet->balance += $originalAmount;
            $BranchWallet->save();
        }
        $companyInitialBalance = $company->wallet->balance;
        $companyBalanceAfter   = $companyInitialBalance + $originalAmount;

        $companyFinalBalance = $company->wallet->balance += $originalAmount;
        $company->wallet->save();

        $response = [
            "status"                        => true,
            'message'                       => "Amount is Valid",
            'account_id'                    => $customerAccountId,
            'pos_balance_before'            => $posInitialBalance,
            'pos_balance_after'             => $posBalanceAfter,
            'balance_before'                => $customerInitialBalance,
            'balance_after'                 => $customerBalanceAfter,
            'charge_balance_before'         => $customerBalanceAfter,
            'charge_balance_after'          => $customerFinalBalance,
            'company_balance_before'        => $companyInitialBalance,
            'company_amount'                => $originalAmount,
            'company_balance_after'         => $companyBalanceAfter,
            'company_charge_balance_before' => $companyBalanceAfter,
            'company_charge_balance_after'  => $companyFinalBalance,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitCustomerAndCompanyWithCompanyCashback($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
{

    $response = [];

    if (! Auth::check()) {
        $currentUser = $user;
    } else {
        $currentUser = auth()->user();
    }

    if ($originalAmount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

        if (! $userWallet) {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
        }

        $customerInitialBalance = $userWallet->balance;

        $customerCashbackAmount = $customerCashback * $originalAmount;
        $companyCashbackAmount  = $companyCashback * $originalAmount;

        if ($customerInitialBalance > ($originalAmount - $customerCashbackAmount)) {

            $company    = Company::find($companyId);
            $companyIDs = [1, 29, 36];

            $companyIDsStatus = false;

            if (in_array($company->id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            $companyAfterBalance = $company->wallet->balance - $originalAmount;

            logInfo($company->wallet->balance, "Company balance");
            logInfo(json_encode($companyAfterBalance), "Company after balance for cash back");
            logInfo(json_encode($companyIDsStatus), "Company id status");

            if ($company->wallet->balance > 100000 && ($companyAfterBalance > 100000)) {
                $companyIDsStatus = true;
            }

            if (($company->wallet->balance > ($originalAmount - $companyCashbackAmount)) && ($companyIDsStatus)) {

                $customerBalanceAfter = $customerInitialBalance - $originalAmount;

                $customerFinalBalance = $userWallet->balance -= ($originalAmount - $customerCashbackAmount);
                $userWallet->save();
                if (isset($userWallet->wb_account_details->user->branch_id)) {
                    $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                    $BranchWallet->balance -= $originalAmount;
                    $BranchWallet->save();
                }
                $companyInitialBalance = $company->wallet->balance;
                $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                $companyFinalBalance = $company->wallet->balance -= ($originalAmount - $companyCashbackAmount);
                $company->wallet->save();

                $currentUser->balance_fails = 0;
                $currentUser->save();

                $response = [
                    "status"                          => true,
                    'message'                         => "Amount is Valid",
                    'account_id'                      => $customerAccountId,
                    'balance_before'                  => $customerInitialBalance,
                    'balance_after'                   => $customerBalanceAfter,
                    'cashback_balance_before'         => $customerBalanceAfter,
                    'cashback_balance_after'          => $customerFinalBalance,
                    'company_balance_before'          => $companyInitialBalance,
                    'company_amount'                  => $originalAmount,
                    'customer_cashback'               => $customerCashbackAmount,
                    'company_cashback'                => $companyCashbackAmount,
                    'company_balance_after'           => $companyBalanceAfter,
                    'company_cashback_balance_before' => $companyBalanceAfter,
                    'company_cashback_balance_after'  => $companyFinalBalance,
                ];
            } else {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
            }
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Insuficient balance trials");

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function creditCompanyWithCharge($companyId, $originalAmount, $companyCharge)
{

    $response = [];

    if ($originalAmount > 0) {

        $company               = Company::find($companyId);
        $companyInitialBalance = $company->wallet->balance;
        $companyBalanceAfter   = $companyInitialBalance + $originalAmount;
        $companyFinalBalance   = $company->wallet->balance += ($originalAmount - $companyCharge);
        $company->wallet->save();

        $response = [
            "status"                => true,
            'message'               => "Amount is Valid",
            'balance_before'        => $companyInitialBalance,
            'balance_after'         => $companyBalanceAfter,
            'charge_balance_before' => $companyBalanceAfter,
            'charge_balance_after'  => $companyFinalBalance,
        ];
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function debitCompanyWithCharge($companyId, $originalAmount, $companyCharge, $user = null)
{

    $currentUser = $user == null ? auth()->user() : $user;

    if (! $currentUser) {
        $currentUser = User::where('company_id', $companyId)->first();
    }

    $response = [];

    if ($originalAmount > 0) {

        $company = Company::find($companyId);

        $company_wallet = company_wallet::where('company_id', $companyId)->lockForUpdate()->first();

        logInfo(json_encode($company_wallet), "Company Wallet debitCompanyWithCharge");

        $companyInitialBalance = $company_wallet->balance;

        $debitAmount = $originalAmount + $companyCharge;

        $companyAfterBalance = $companyInitialBalance - $debitAmount;

        $companyIDs = [1, 29, 36, 43];

        $companyIDsStatus = false;

        if (in_array($company->id, $companyIDs)) {
            $companyIDsStatus = true;
        }

        if ($companyInitialBalance > 100000 && $companyAfterBalance > 100000) {
            $companyIDsStatus = true;
        }

        if ($companyInitialBalance > $debitAmount && $companyIDsStatus) {

            $companyBalanceAfter = $companyInitialBalance - $originalAmount;
            $companyFinalBalance = $company_wallet->balance -= $debitAmount;
            $company_wallet->save();

            $currentUser->balance_fails = 0;
            $currentUser->save();

            $response = [
                "status"                => true,
                'message'               => "Amount is Valid",
                'balance_before'        => $companyInitialBalance,
                'balance_after'         => $companyBalanceAfter,
                'amount'                => $debitAmount,
                'charge_balance_before' => $companyBalanceAfter,
                'charge_balance_after'  => $companyFinalBalance,
            ];
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
            } else if (! $companyIDsStatus) {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
            } else {
                restrictUserAccount(2, "Multiple Insuficient balance trials", $currentUser);

                $response = ["status" => false, "responseCode" => 1, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, "responseCode" => 2, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function tryToDebitCompanyWithCharge($companyId, $originalAmount, $companyCharge, $user = null)
{

    $currentUser = $user == null ? auth()->user() : $user;

    if (! $currentUser) {
        $currentUser = User::where('company_id', $companyId)->first();
    }

    $response = [];

    if ($originalAmount > 0) {

        $company               = Company::find($companyId);
        $companyInitialBalance = $company->wallet->balance;

        if ($companyInitialBalance > ($originalAmount + $companyCharge)) {

            $companyBalanceAfter = $companyInitialBalance - $originalAmount;
            $companyFinalBalance = $company->wallet->balance -= ($originalAmount + $companyCharge);
            // $company->wallet->save();

            $currentUser->balance_fails = 0;
            $currentUser->save();

            $response = [
                "status"                => true,
                'message'               => "Amount is Valid",
                'balance_before'        => $companyInitialBalance,
                'balance_after'         => $companyBalanceAfter,
                'charge_balance_before' => $companyBalanceAfter,
                'charge_balance_after'  => $companyFinalBalance,
            ];
        } else {
            if ($currentUser->balance_fails < 4) {
                $currentUser->balance_fails += 1;
                $currentUser->save();
                $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
            } else {
                restrictUserAccount(2, "Multiple Insuficient balance trials", $currentUser);

                $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function checkPinDebitCompanyWithCharge($transactionPin, $companyId, $originalAmount, $companyCharge)
{

    $response = [];

    logInfo($transactionPin, "Transaction Pin");

    if ($originalAmount > 0) {

        $company               = Company::find($companyId);
        $companyInitialBalance = $company->wallet->balance;

        if ($transactionPin != auth()->user()->pin) {

            if (auth()->user()->transaction_pin_fails < 4) {
                auth()->user()->transaction_pin_fails += 1;
                auth()->user()->save();
                return $response = ["status" => false, "responseCode" => 2, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - auth()->user()->transaction_pin_fails) . " wrong attempts"];
            } else {
                // auth()->user()->status_id = 5;
                restrictUserAccount(2, "Multiple Transaction pin trials");

                return $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    }

    if ($companyInitialBalance > ($originalAmount + $companyCharge)) {

        auth()->user()->balance_fails         = 0;
        auth()->user()->transaction_pin_fails = 0;
        auth()->user()->save();
        $companyBalanceAfter = $companyInitialBalance - $originalAmount;
        $companyFinalBalance = $company->wallet->balance -= ($originalAmount + $companyCharge);
        $company->wallet->save();

        $response = [
            "status"                => true,
            'message'               => "Amount is Valid",
            'balance_before'        => $companyInitialBalance,
            'balance_after'         => $companyBalanceAfter,
            'charge_balance_before' => $companyBalanceAfter,
            'charge_balance_after'  => $companyFinalBalance,
        ];
    } else {
        if (auth()->user()->balance_fails < 4) {
            auth()->user()->balance_fails += 1;
            auth()->user()->save();
            $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
        } else {
            // auth()->user()->status_id = 5;
            restrictUserAccount(2, "Multiple Insuficient balance trials");

            $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
        }
    }

    return $response;
}

function checkCustomerBalanceAndDebit($accountId, $amount)
{

    $response = [];

    if ($amount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $accountId)->first();

        if ($userWallet->balance >= $amount) {
            $currentBalance = $userWallet->balance;
            $newBalance     = $userWallet->balance -= $amount;
            $userWallet->save();
            if (isset($userWallet->wb_account_details->user->branch_id)) {
                $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                $BranchWallet->balance += $amount;
                $BranchWallet->save();
            }
            $response = [
                "status"         => true,
                'message'        => "Amount Debited Successfully",
                'account_id'     => $accountId,
                'balance_before' => $currentBalance,
                'balance_after'  => $newBalance,
            ];
        } else {
            $response = ["status" => false, 'message' => "Insufficient Funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function checkCompanyBalanceAndDebit($amount)
{

    $response = [];

    if ($amount > 0) {

        $userWallet = company_wallet::where('company_id', auth()->user()->company_id)->first();

        if ($userWallet->balance >= $amount) {
            $currentBalance = $userWallet->balance;
            $newBalance     = $userWallet->balance -= $amount;
            $userWallet->save();
            $response = [
                "status"         => true,
                'message'        => "Amount Debited Successfully",
                'balance_before' => $currentBalance,
                'balance_after'  => $newBalance,
            ];
        } else {
            $response = ["status" => false, 'message' => "Insufficient Funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function validatePinCheckCustomerBalance($transactionPin, $amount, $selectedwallet = null)
{
    $response = [];

    try {

        $user = auth()->user();

        if ($amount > 0) {
            $validatePin = User::where(['id' => $user->id, 'pin' => $transactionPin])->first();

            if ($validatePin) {
                $sourceWbAccount = ($selectedwallet != null) ? $selectedwallet : $user->accounts->first()->id;
                $userWallet      = user_wallet::where('wb_account_detail_id', $sourceWbAccount)->lockForUpdate()->first();

                if ($userWallet->balance >= $amount) {
                    if ($user->company->wallet->balance >= $amount) {
                        $user->balance_fails         = 0;
                        $user->transaction_pin_fails = 0;
                        $user->save();
                        $response = ["status" => true, 'message' => "Amount and pin are Valid", 'account_id' => $user->accounts->first()->id, 'balance' => $userWallet->balance];
                    } else {
                        $response = ["status" => false, 'message' => "Switcher Error... Please contact support for assistance"];
                    }
                } else {
                    if ($user->balance_fails < 4) {
                        $user->balance_fails += 1;
                        $user->save();
                        $response = ["status" => false, 'message' => "Insufficient Funds"];
                    } else {
                        // $user->status_id = 5;
                        restrictUserAccount(2, "Multiple Insuficient balance trials");

                        $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                    }
                }
            } else {
                if ($user->transaction_pin_fails < 4) {
                    $user->transaction_pin_fails += 1;
                    $user->save();
                    $response = ["status" => false, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - $user->transaction_pin_fails) . " wrong attempts"];
                } else {
                    restrictUserAccount(2, "Multiple Invalid transaction pin");
                    $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
    } catch (Exception $e) {
        logInfo($e->getMessage(), "customer Debit error");
        $response = ["status" => false, 'message' => "Server Error"];
    }

    return $response;
}

function validatePinDebitCompanyBalance($transactionPin, $amount)
{
    $response = [];

    try {
        if ($amount > 0) {

            logInfo($transactionPin, "Transaction pin log");
            logInfo(auth()->user()->company->id, "company id log");

            if ($transactionPin == auth()->user()->pin) {

                $balanceBefore = auth()->user()->company->wallet->balance;

                if ($balanceBefore >= $amount) {

                    auth()->user()->balance_fails         = 0;
                    auth()->user()->transaction_pin_fails = 0;
                    auth()->user()->save();

                    $companywallet = company_wallet::where('company_id', auth()->user()->company->id)->first();

                    $balanceAfter              = $companywallet->balance -= $amount;
                    $companywallet->updated_at = Carbon::now();
                    $companywallet->save();

                    $response = ["status" => true, "responseCode" => 1, 'message' => "Amount and pin are Valid", 'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter, 'balance' => $companywallet->balance];
                } else {
                    if (auth()->user()->balance_fails < 4) {
                        auth()->user()->balance_fails += 1;
                        auth()->user()->save();
                        $response = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
                    } else {
                        // auth()->user()->status_id = 5;
                        restrictUserAccount(2, "Multiple Insuficient balance trials");

                        $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
                    }
                }
            } else {
                if (auth()->user()->transaction_pin_fails < 4) {
                    auth()->user()->transaction_pin_fails += 1;
                    auth()->user()->save();
                    $response = ["status" => false, "responseCode" => 2, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - auth()->user()->transaction_pin_fails) . " wrong attempts"];
                } else {
                    restrictUserAccount(2, "Multiple Invalid transaction pin");
                    $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid Transaction Amount"];
        }
    } catch (Exception $e) {
        logInfo($e->getMessage(), "Company Debit error");
        $response = ["status" => false, "responseCode" => 0, 'message' => "Server Error"];
    }

    return $response;
}

function restrictUserAccount($restrictionId, $remark, $user = null)
{
    if ($user != null) {
        $auth = $user;
    } else {
        $auth = auth()->user();
    }
    $hasPermission = (isset($auth->customerPermissions)) ? $auth->customerPermissions : [];

    if (count($hasPermission) > 0) {
        foreach ($auth->customerPermissions as $customerPermission) {
            if ($customerPermission->id == $restrictionId) {
                return;
            } else {

                logInfo("Attaching permissions", "Restrict user account title");
                $auth->customerPermissions()->attach($restrictionId);

                CustomerRestrictionLog::create([
                    "user_id"                 => $auth->id,
                    "customer_permission_id"  => $restrictionId,
                    "remark"                  => $remark,
                    "restricted_by_system"    => 1,
                    "restriction_log_type_id" => 1,
                ]);
            }
        }
    } else {

        logInfo("Attaching permissions", "Restrict user account title");
        // auth()->user()->customerPermissions()->attach($restrictionId);

        MainCustomerRestriction::create([
            'user_id'                => $auth->id,
            'customer_permission_id' => $restrictionId,
            'company_id'             => $auth->company_id,
            "remark"                 => $remark,
            "created_by"             => $auth->id,
        ]);

        CustomerRestrictionLog::create([
            "user_id"                 => $auth->id,
            "customer_permission_id"  => $restrictionId,
            "remark"                  => $remark,
            "restricted_by_system"    => 1,
            "restriction_log_type_id" => 1,

        ]);
    }
}

function restrictUserAccountAPI($userId, $restrictionId, $remark, $transactionRef = "", $sessionId = "")
{

    $userAccount = User::find($userId);

    if ($userAccount) {

        if (count($userAccount->customerPermissions) > 0) {
            foreach ($userAccount->customerPermissions as $customerPermission) {
                if ($customerPermission->id == $restrictionId) {
                    return ['status' => false, 'response_code' => 2, 'message' => "User restriction already exists"];
                } else {

                    logInfo("Attaching permissions other permissions", "Restrict user account title");
                    // $userAccount->customerPermissions()->syncWithPivotValues([$restrictionId], ['remark' => "lskdjflsjdflkjsdfs"]);

                    MainCustomerRestriction::create([
                        'transaction_reference'  => $transactionRef,
                        'user_id'                => $userAccount->id,
                        'company_id'             => $userAccount->company_id,
                        'customer_permission_id' => $restrictionId,
                        "session_id"             => $sessionId,
                        "remark"                 => $remark,
                        "created_by"             => $userAccount->id,
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id"                 => $userAccount->id,
                        "customer_permission_id"  => $restrictionId,
                        "remark"                  => $remark,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 1,
                        'transaction_reference'   => $transactionRef,
                        "session_id"              => $sessionId,
                    ]);

                    return ['status' => true, 'message' => "User restriction added successfully"];
                }
            }
        } else {

            logInfo("Attaching permissions", "Restrict user account title");
            // $userAccount->customerPermissions()->attach($restrictionId);

            MainCustomerRestriction::create([
                'transaction_reference'  => $transactionRef,
                'user_id'                => $userAccount->id,
                'company_id'             => $userAccount->company_id,
                'customer_permission_id' => $restrictionId,
                "session_id"             => $sessionId,
                "remark"                 => $remark,
                "created_by"             => $userAccount->id,
            ]);

            CustomerRestrictionLog::create([
                "user_id"                 => $userAccount->id,
                "customer_permission_id"  => $restrictionId,
                "remark"                  => $remark,
                "restricted_by_system"    => 1,
                "restriction_log_type_id" => 1,
                'transaction_reference'   => $transactionRef,
                "session_id"              => $sessionId,
            ]);

            return ['status' => true, 'message' => "User restriction added successfully"];
        }
    } else {
        return ['status' => false, 'message' => "Invalid username provided"];
    }
}

function restrictUserAccountNIBBS($userId, $remark, $transactionRef = "", $sessionId = "")
{

    $userAccount = User::find($userId);

    if ($userAccount) {

        if (count($userAccount->customerPermissions) > 0) {
            foreach ($userAccount->customerPermissions as $customerPermission) {
                if ($customerPermission->transaction_reference == $transactionRef || $customerPermission->session_id == $sessionId) {
                    return ['status' => true, 'message' => "User restriction already exists"];
                } else {

                    logInfo("Attaching permissions other permissions", "Restrict user account title");
                    // $userAccount->customerPermissions()->syncWithPivotValues([$restrictionId], ['remark' => "lskdjflsjdflkjsdfs"]);

                    MainCustomerRestriction::create([
                        'transaction_reference'  => $transactionRef,
                        'user_id'                => $userAccount->id,
                        'company_id'             => $userAccount->company_id,
                        'customer_permission_id' => 1,
                        "session_id"             => $sessionId,
                        "remark"                 => $remark,
                        "created_by"             => $userAccount->id,
                    ]);

                    MainCustomerRestriction::create([
                        'transaction_reference'  => $transactionRef,
                        'user_id'                => $userAccount->id,
                        'company_id'             => $userAccount->company_id,
                        'customer_permission_id' => 2,
                        "session_id"             => $sessionId,
                        "remark"                 => $remark,
                        "created_by"             => $userAccount->id,
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id"                 => $userAccount->id,
                        "customer_permission_id"  => 2,
                        "remark"                  => $remark,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 1,
                        'transaction_reference'   => $transactionRef,
                        "session_id"              => $sessionId,
                    ]);

                    CustomerRestrictionLog::create([
                        "user_id"                 => $userAccount->id,
                        "customer_permission_id"  => 2,
                        "remark"                  => $remark,
                        "restricted_by_system"    => 2,
                        "restriction_log_type_id" => 1,
                        'transaction_reference'   => $transactionRef,
                        "session_id"              => $sessionId,
                    ]);

                    return ['status' => true, 'message' => "User restriction added successfully"];
                }
            }
        } else {

            logInfo("Attaching permissions", "Restrict user account title");
            // $userAccount->customerPermissions()->attach($restrictionId);

            MainCustomerRestriction::create([
                'transaction_reference'  => $transactionRef,
                'user_id'                => $userAccount->id,
                'company_id'             => $userAccount->company_id,
                'customer_permission_id' => 1,
                "session_id"             => $sessionId,
                "remark"                 => $remark,
                "created_by"             => $userAccount->id,
            ]);

            MainCustomerRestriction::create([
                'transaction_reference'  => $transactionRef,
                'user_id'                => $userAccount->id,
                'company_id'             => $userAccount->company_id,
                'customer_permission_id' => 2,
                "session_id"             => $sessionId,
                "remark"                 => $remark,
                "created_by"             => $userAccount->id,
            ]);

            CustomerRestrictionLog::create([
                "user_id"                 => $userAccount->id,
                "customer_permission_id"  => 2,
                "remark"                  => $remark,
                "restricted_by_system"    => 1,
                "restriction_log_type_id" => 1,
                'transaction_reference'   => $transactionRef,
                "session_id"              => $sessionId,
            ]);

            CustomerRestrictionLog::create([
                "user_id"                 => $userAccount->id,
                "customer_permission_id"  => 2,
                "remark"                  => $remark,
                "restricted_by_system"    => 2,
                "restriction_log_type_id" => 1,
                'transaction_reference'   => $transactionRef,
                "session_id"              => $sessionId,
            ]);

            return ['status' => true, 'message' => "User restriction added successfully"];
        }
    } else {
        return ['status' => false, 'message' => "Invalid username provided"];
    }
}

function removeUserRestrictionAPI($userId, $restrictionId, $remark, $createdBy, $transactionRef = "", $sessionId = "")
{

    $userAccount = User::find($userId);

    if ($userAccount) {

        logInfo($userAccount->customerPermissions()->get(), "The User permissions");

        if (count($userAccount->customerPermissions) > 0) {
            foreach ($userAccount->customerPermissions as $customerPermission) {
                if ($customerPermission->id == $restrictionId) {

                    logInfo("Attaching permissions", "Restrict user account title");
                    $userAccount->customerPermissions()->detach($restrictionId);

                    CustomerRestrictionLog::create([
                        "user_id"                 => $userAccount->id,
                        "customer_permission_id"  => $restrictionId,
                        "remark"                  => $remark,
                        "transaction_reference"   => $transactionRef,
                        "session_id"              => $sessionId,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 2,
                        "created_by"              => $createdBy->id,
                    ]);

                    MainCustomerRestriction::where([
                        'user_id'                => $userAccount->id,
                        'customer_permission_id' => $restrictionId,
                    ])->delete();

                    return ['status' => true, 'message' => "User restriction removed successfully"];
                }
            }
            return ['status' => true, 'message' => "User restriction Removed successfully"];
        } else {
            return ['status' => true, 'message' => "No restriction is found on this account"];
        }
    } else {
        return ['status' => false, 'message' => "Invalid username provided"];
    }
}

function removeUserRestrictionNIBBS($userId, $remark, $createdBy, $transactionRef = "", $sessionId = "")
{

    $userAccount = User::find($userId);

    if ($userAccount) {

        if (count($userAccount->customerPermissions) > 0) {
            foreach ($userAccount->customerPermissions as $customerPermission) {
                if ($customerPermission->transaction_reference == $transactionRef || $customerPermission->session_id == $sessionId) {

                    logInfo("Attaching permissions", "Restrict user account title");

                    $userAccount->customerPermissions()->detach($customerPermission->id);

                    CustomerRestrictionLog::where([
                        "user_id"                 => $userAccount->id,
                        'transaction_reference'   => $transactionRef,
                        "customer_permission_id"  => $customerPermission->id,
                        "remark"                  => $remark,
                        "session_id"              => $sessionId,
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 2,
                        "created_by"              => $createdBy->id,
                    ]);

                    return ['status' => true, 'message' => "User restriction removed successfully"];
                }
            }
            return ['status' => true, 'message' => "User restriction added successfully"];
        } else {
            return ['status' => true, 'message' => "No restriction is found on this account"];
        }
    } else {
        return ['status' => false, 'message' => "Invalid username provided"];
    }
}

function checkForDebitRestriction()
{
    $isDebitPermissionAvailable = ["status" => true, "responseCode" => 0, 'message' => "Account is Active"];

    if (count(auth()->user()->customerPermissions) > 0) {
        foreach (auth()->user()->customerPermissions as $customerPermission) {
            if ($customerPermission->id == 2) {
                $isDebitPermissionAvailable = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
            }
        }
    }
    return $isDebitPermissionAvailable;
}

function updateTransactionAsSuccessful($transactionId, $balance, $isCompany = false)
{
    $transaction = transactions::where('transaction_reference', $transactionId)->first();
    if ($isCompany) {
        $transaction->company_balance_after = $balance;
    } else {
        $transaction->balance_after = $balance;
    }

    $transaction->transaction_remarks_id = 2;
    $transaction->updated_at             = Carbon::now();
    $transaction->modified_by            = auth()->user()->id;
    $transaction->save();
}

function updateTransactionAsSuccessfulOnly($transactionId)
{
    $transaction = transactions::where('transaction_reference', $transactionId)->first();

    $transaction->transaction_remarks_id = 2;
    $transaction->updated_at             = Carbon::now();
    $transaction->modified_by            = auth()->user()->id;
    $transaction->save();
}

function updateCustomerAndCompanyTransactionAsSuccessful($transactionId, $customerBalance, $companyBalance, $updateBalance = true)
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

function checkCustomerBalanceCompanyBalanceAndDebit($accountId, $amount)
{

    $companyId = auth()->user()->company_id;
    $response  = [];

    if ($amount > 0) {

        $userWallet = user_wallet::where('wb_account_detail_id', $accountId)->first();

        if ($userWallet->balance >= $amount) {

            $company = Company::find($companyId);

            if ($company->wallet->balance >= $amount) {

                $company->wallet->balance -= $amount;
                $company->wallet->save();

                $currentBalance = $userWallet->balance;
                $newBalance     = $userWallet->balance -= $amount;
                $userWallet->save();
                if (isset($userWallet->wb_account_details->user->branch_id)) {
                    $BranchWallet = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                    $BranchWallet->balance += $amount;
                    $BranchWallet->save();
                }
                $response = [
                    "status"         => true,
                    'message'        => "Amount Debited Successfully",
                    'account_id'     => $accountId,
                    'balance_before' => $currentBalance,
                    'balance_after'  => $newBalance,
                ];
            } else {
                $response = ["status" => false, 'message' => "Switcher Error. Please contact support " . $company->wallet->balance . " $amount"];
            }
        } else {
            $response = ["status" => false, 'message' => "Insufficient Funds"];
        }
    } else {
        $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
    }
    return $response;
}

function startsWith($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}
function fundVaultByID($vault_info)
{
    try {
        $CompanyValut = CompanyValut::where(['id' => $vault_info['vaultID'], 'company_id' => $vault_info['company_id']])->first();
        if ($CompanyValut) {
            $vault_info['type'] = $CompanyValut->vault;
            return fundVault($vault_info);
        }
    } catch (Exception $e) {
        return 0;
    }
}
function fundVault($vault_info)
{
    //esusu_circle_out_charge
    //withdrawal_charge
    //sms_charge
    $vault_type       = $vault_info['type'];
    $vault_value      = $vault_info['value'];
    $vault_company_id = $vault_info['company_id'];
    $vault_created_by = ! isset($vault_info['created_by']) ? auth()->user()->id : $vault_info['created_by']; //
    $vault_branch_id  = $vault_info['branch_id'];
    // $vault_company_valut_id= $vault_info['company_valut_id'];
    $vault_transaction_id = $vault_info['transaction_id'];

    $creatorId = $vault_created_by;

    if (Auth::check()) {
        $creatorId = auth()->user()->id;
    }

    $check_tocreate = $valutcount = CompanyValut::where(['vault' => $vault_type, 'company_id' => $vault_company_id]);
    $cout_exist     = $valutcount->count();
    if ($cout_exist == 0) {
        $acno = time();
        while (CompanyValut::where(['accountno' => $acno])->count() > 0) {
            $acno = $acno++;
        }
        $create_vault = CompanyValut::create(['accountno' => $acno, 'company_id' => $vault_company_id, 'vault' => $vault_type, 'value' => $vault_value, 'deleteable' => 1, 'created_by' => $vault_created_by]);
        BranchValut::create(['branch_id' => $vault_branch_id, 'company_valut_id' => $create_vault->id, 'transaction_id' => $vault_transaction_id, 'value' => $vault_value, 'created_by' => $creatorId]);

        return 1;
    } else {
        $check_tocreate = $check_tocreate->first();
        $amount_to_add  = $check_tocreate->value + $vault_value;
        logInfo("Updating Company $vault_type vault and adding new balance $amount_to_add value $vault_value ", "Crediting Company $vault_type vault");

        $update_vault = $check_tocreate->update(['value' => $amount_to_add]);
        BranchValut::create(['branch_id' => $vault_branch_id, 'company_valut_id' => $check_tocreate->id, 'transaction_id' => $vault_transaction_id, 'value' => $vault_value, 'created_by' => $creatorId]);
        return 1;
    }
    return 0;
}

function restrict_to_just_my_company($user_array = [])
{
    if (! is_array($user_array)) {
        return 0;
    }

    $right = (cando('root|allow-staff-view-all-customers'));
    $auth  = auth()->user();
    if (isset($user_array['user_id'])) {
        $user_id = $user_array['user_id'];
        return ($right) ? User::where(['id' => $user_id, 'company_id' => $auth->company_id])->count() : User::where(['id' => $user_id, 'company_id' => auth()->user()->company_id, 'account_officer_id' => auth()->user()->id])->count();
    } else if (isset($user_array['user_uuid'])) {
        $user_uuid = $user_array['user_uuid'];
        return ($right) ? User::where(['uuid' => $user_uuid, 'company_id' => $auth->company_id])->count() : User::where(['uuid' => $user_uuid, 'company_id' => auth()->user()->company_id, 'account_officer_id' => auth()->user()->id])->count();
    }
}

function formatPhoneNumber($phone, $countryPhonePrefix)
{

    $phonePrefix = \substr($countryPhonePrefix, 1);

    $userPhone = $phone;

    logInfo(substr($phone, 0), "Phone prefix");

    if (substr($phone, 0, 1) === "0") {
        $userPhone = $phonePrefix . "" . substr($phone, 1);
    }

    if (substr($phone, 0, strlen($phonePrefix)) === $phonePrefix) {
        $userPhone = $phonePrefix . "" . substr($phone, strlen($phonePrefix));
    } else if (substr($phone, 0, strlen($countryPhonePrefix)) === $countryPhonePrefix) {
        $userPhone = $phonePrefix . "" . substr($phone, strlen($countryPhonePrefix));
    }

    return $userPhone;
}
function convertLY($period)
{
    if ($period == 'Daily') {
        return ['key' => 'days', 'value' => 1];
    } else if ($period == 'Weekly') {
        return ['key' => 'weeks', 'value' => 1];
    } else if ($period == 'Bi-Weekly') {
        return ['key' => 'weeks', 'value' => 2];
    } else if ($period == 'Monthly') {
        return ['key' => 'months', 'value' => 1];
    } else if ($period == 'Bi-Monthly') {
        return ['key' => 'months', 'value' => 2];
    } else if ($period == 'Quarterly') {
        return ['key' => 'months', 'value' => 3];
    } else if ($period == 'Every 4 Months') {
        return ['key' => 'months', 'value' => 4];
    } else if ($period == 'Semi-Annual') {
        return ['key' => 'months', 'value' => 6];
    } else if ($period == 'Every 9 Months') {
        return ['key' => 'months', 'value' => 9];
    } else if ($period == 'Yearly') {
        return ['key' => 'months', 'value' => 12];
    } else {
        // return 0;
        return ['key' => 'days', 'value' => 0];
    }
}
function calculatePeriodByPeriod($period, $Y)
{
    if ($period == 'Daily' && $Y == 'days') {
        return 1;
    } else if ($period == 'Daily' && $Y == 'weeks') {
        return 7;
    } else if ($period == 'Daily' && $Y == 'months') {
        return 30;
    } else if ($period == 'Daily' && $Y == 'years') {
        return 365;
    } else if ($period == 'Weekly' && $Y == 'weeks') {
        return 1;
    } else if ($period == 'Weekly' && $Y == 'months') {
        return 4;
    } else if ($period == 'Weekly' && $Y == 'years') {
        return 52;
    } else if ($period == 'Bi-Weekly' && $Y == 'months') {
        return 2;
    } else if ($period == 'Bi-Weekly' && $Y == 'years') {
        return 26;
    } else if ($period == 'Monthly' && $Y == 'months') {
        return 1;
    } else if ($period == 'Monthly' && $Y == 'years') {
        return 12;
    } else if ($period == 'Bi-Monthly' && $Y == 'years') {
        return 6;
    } else if ($period == 'Quarterly' && $Y == 'years') {
        return 4;
    } else if ($period == 'Every 4 Months' && $Y == 'years') {
        return 3;
    } else if ($period == 'Semi-Annual' && $Y == 'years') {
        return 2;
    } else if ($period == 'Every 9 Months' && $Y == 'years') {
        return 1;
    } else if ($period == 'Yearly' && $Y == 'years') {
        return 1;
    } else {
        return 1;
    }
}

function encrypt_decrypt($action, $string)
{
    $output         = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key     = 'iamsaviourthatli';
    $secret_iv      = '1234545456';
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

function Stringlimiter($string, $length)
{
    $string = strip_tags($string);
    if (strlen($string) > $length) {

        // truncate string
        $stringCut = substr($string, 0, $length);
        $endPoint  = strrpos($stringCut, ' ');

        //if the string doesn't contain any space then it will cut without word basis.
        $string = $endPoint ? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
        $string .= '...';
    }
    return $string;
}
function dateDiffInDays($date1, $date2)
{
    // Calculating the difference in timestamps
    $diff = strtotime($date2) - strtotime($date1);

    // 1 day = 24 hours
    // 24 * 60 * 60 = 86400 seconds
    return abs(round($diff / 86400));
}
function notificationBar()
{
    if (auth()->check()) {
        $is_staff    = cando('root');
        $pendingUser = User::where(['company_id' => auth()->user()->company_id, 'status_id' => 7, 'user_type_id' => 1]);
        if ($is_staff) {
            $pendingUser = $pendingUser->count();
        } else {
            $pendingUser = $pendingUser->where('account_officer_id', auth()->user()->id)->count();
        }
        $pendingLoan = loan::join('users', 'users.id', '=', 'loans.user_id')->where(['users.company_id' => auth()->user()->company_id, 'loans.loan_status_id' => 3]);
        if ($is_staff) {
            $pendingLoan = $pendingLoan->count();
        } else {
            $pendingLoan = $pendingLoan->where('LoanOfficer_id', auth()->user()->id)->count();
        }
        return ['pending_User' => $pendingUser, 'pending_loan' => $pendingLoan];
    } else {
        return ['pending_User' => 0, 'pending_loan' => 0];
    }
}
function getStaff()
{
    $pendingUser = User::where(['company_id' => auth()->user()->company_id, 'status_id' => 1, 'user_type_id' => 2])->get();
    return $pendingUser;
}

function decryptJWT($endcoded, $key)
{
    try {
        $newSign = explode('.', $endcoded);

        $jsonToken  = base64_decode($newSign[1]);
        $arrayToken = json_decode($jsonToken, true);

        return ['status' => true, 'message' => "Decrypted successfully", 'data' => ['value' => $arrayToken[$key]]];
    } catch (Exception $e) {
        logInfo($e->getMessage(), "Decrypt exception");
        return ['status' => false, 'message' => "Invalid Auth token provided"];
    }
}

// $table->double('ten_thousand_amount', 12, 2);
//             $table->unsignedBigInteger('five_thousand_quantity')->default(0);
//             $table->double('five_thousand_amount', 12, 2);
//             $table->unsignedBigInteger('two_thousand_quantity')->default(0);
//             $table->double('two_thousand_amount', 12, 2);
//             $table->unsignedBigInteger('one_thousand_quantity')->default(0);
//             $table->double('one_thousand_amount', 12, 2);
//             $table->unsignedBigInteger('five_hundred_note_quantity')->default(0);
//             $table->double('five_hundred_note_amount', 12, 2);
//             $table->unsignedBigInteger('five_hundred_coin_quantity')->default(0);
//             $table->double('five_hundred_coin_amount', 12, 2);
//             $table->unsignedBigInteger('one_hundred_quantity')->default(0);
//             $table->double('one_hundred_amount', 12, 2);
//             $table->unsignedBigInteger('fifthy_quantity')->default(0);
//             $table->double('fifthy_amount', 12, 2);
//             $table->unsignedBigInteger('twenty_five_quantity')->default(0);
//             $table->double('twenty_five_amount', 12, 2);
//             $table->unsignedBigInteger('ten_quantity')->default(0);
//             $table->double('ten_amount', 12, 2);
//             $table->unsignedBigInteger('five_quantity')->default(0);
//             $table->double('five_amount', 12, 2);
//             $table->unsignedBigInteger('two_quantity')->default(0);
//             $table->double('two_amount', 12, 2);
//             $table->unsignedBigInteger('one_quantity')->default(0);
function DebitCreditcustomer($data = [])
{
    try {
        if (count($data) < 8) {
            return (["status" => 0, 'message' => "Invalide Parameter!"]);
        }
        if (isset($data['auth'])) {
            $auth = $data['auth'];
        } else {
            if (auth()->check()) {
                $auth = auth()->user();
            }
        }

        $userParamType        = $data['userParamType'];
        $userId               = $data['userId'];
        $amount               = $data['amount'];
        $desc                 = $data['description'];
        $transaction_type_id  = $data['transaction_type_id'];
        $credit_debit_type_id = $data['credit_debit_type_id'];
        $remark               = $data['remark'];
        $backdate             = $data['created_date'];
        $created_by_id        = $data['created_by'];
        $chargeamt            = $data['chargeamt'];
        $companyVault         = $data['companyVault'] ?? null;
        $ledger_cr            = $data['ledger_cr'];
        $ledger_dr            = $data['ledger_dr'];
        $initiator            = $data['initiator'] ?? null;
        $sendSMS              = ($data['sendSMS']) ?? true;
        $request              = $data['request'] ?? null;

        $transactionId = generate_txnID();
        DB::beginTransaction();

        //, 'virtual_accnum' => $account_type
        $wb_account_details = ($userParamType == 1) ? wb_account_details::where(['user_id' => $userId])->first() : wb_account_details::where(['id' => $userId])->first();
        $user_wallet        = null;
        $vaultpayment       = 0;
        if (! $wb_account_details) {
            return ["status" => 0, 'message' => "Invalide Account Details!"];
        }
        if ($request) {
            $customerCurrency = Currency::where('id', $wb_account_details->currency_id)->first();

            if ($customerCurrency) {
                $closing = ClosingBalance::where(['company_id' => $auth->company_id, 'staff_id' => $auth->id, 'branch_id' => $auth->branch_id, 'currency' => $customerCurrency->code])->first();
                if ($closing) {
                    if ($closing->status == 'closed') {
                        return ["status" => 0, "message" => "Open Till Balance before performing transaction, goto accounting report and then click on Close/Open Account and use the open button"];
                    }
                    if ($credit_debit_type_id == 2) {
                        $closing->ten_thousand_quantity -= $request->ten_thousand_quantity;
                        $closing->ten_thousand_amount -= $request->ten_thousand_quantity ?? 0 * 10000;
                        $closing->five_thousand_quantity -= $request->five_thousand_quantity;
                        $closing->five_thousand_amount -= $request->five_thousand_quantity ?? 0 * 5000;
                        $closing->two_thousand_quantity -= $request->two_thousand_quantity;
                        $closing->two_thousand_amount -= $request->two_thousand_quantity ?? 0 * 2000;
                        $closing->one_thousand_quantity -= $request->one_thousand_quantity;
                        $closing->one_thousand_amount -= $request->one_thousand_quantity ?? 0 * 1000;
                        $closing->five_hundred_note_quantity -= $request->five_hundred_note_quantity;
                        $closing->five_hundred_note_amount -= $request->five_hundred_note_quantity ?? 0 * 500;
                        $closing->five_hundred_coin_quantity -= $request->five_hundred_coin_quantity;
                        $closing->five_hundred_coin_amount -= $request->five_hundred_coin_quantity ? ($request->five_hundred_coin_quantity / 100) : 0;
                        $closing->one_hundred_quantity -= $request->one_hundred_quantity;
                        $closing->one_hundred_amount -= $request->one_hundred_quantity ?? 0 * 100;
                        $closing->fifthy_quantity -= $request->fifthy_quantity;
                        $closing->fifthy_amount -= $request->fifthy_quantity ?? 0 * 50;
                        $closing->twenty_five_amount -= $request->twenty_five_quantity ?? 0 * 25;
                        $closing->twenty_five_quantity -= $request->twenty_five_quantity;
                        $closing->ten_quantity -= $request->ten_quantity;
                        $closing->ten_amount -= $request->ten_quantity ?? 0 * 10;
                        $closing->five_quantity -= $request->five_quantity;
                        $closing->five_amount -= $request->five_quantity ?? 0 * 5;
                        $closing->two_quantity -= $request->two_quantity;
                        $closing->two_amount -= $request->two_quantity ?? 0 * 2;
                        $closing->one_quantity -= $request->one_quantity;
                        $closing->one_amount -= $request->one_amount ?? 0 * 1;
                    } else {
                        $closing->ten_thousand_quantity += $request->ten_thousand_quantity;
                        $closing->ten_thousand_amount += ($request->ten_thousand_quantity ?? 0) * 10000;
                        $closing->five_thousand_quantity += $request->five_thousand_quantity;
                        $closing->five_thousand_amount += ($request->five_thousand_quantity ?? 0) * 5000;
                        $closing->two_thousand_quantity += $request->two_thousand_quantity;
                        $closing->two_thousand_amount += ($request->two_thousand_quantity ?? 0) * 2000;
                        $closing->one_thousand_quantity += $request->one_thousand_quantity;
                        $closing->one_thousand_amount += ($request->one_thousand_quantity ?? 0) * 1000;
                        $closing->five_hundred_note_quantity += $request->five_hundred_note_quantity;
                        $closing->five_hundred_note_amount += ($request->five_hundred_note_quantity ?? 0) * 500;
                        $closing->five_hundred_coin_quantity += $request->five_hundred_coin_quantity;
                        $closing->five_hundred_coin_amount += $request->five_hundred_coin_quantity ? ($request->five_hundred_coin_quantity / 100) : 0;
                        $closing->one_hundred_quantity += $request->one_hundred_quantity;
                        $closing->one_hundred_amount += ($request->one_hundred_quantity ?? 0) * 100;
                        $closing->fifthy_quantity += $request->fifthy_quantity;
                        $closing->fifthy_amount += ($request->fifthy_quantity ?? 0) * 50;
                        $closing->twenty_five_amount += ($request->twenty_five_quantity ?? 0) * 25;
                        $closing->twenty_five_quantity += $request->twenty_five_quantity;
                        $closing->ten_quantity += $request->ten_quantity;
                        $closing->ten_amount += ($request->ten_quantity ?? 0) * 10;
                        $closing->five_quantity += $request->five_quantity;
                        $closing->five_amount += ($request->five_quantity ?? 0) * 5;
                        $closing->two_quantity += $request->two_quantity;
                        $closing->two_amount += ($request->two_quantity ?? 0) * 2;
                        $closing->one_quantity += $request->one_quantity;
                        $closing->one_amount += ($request->one_amount ?? 0) * 1;
                    }
                    $closing->total = ($closing->one_amount + $closing->two_amount + $closing->five_amount + $closing->ten_amount + $closing->twenty_five_amount + $closing->fifthy_amount + $closing->one_hundred_amount + $closing->five_hundred_coin_amount + $closing->five_hundred_note_amount + $closing->one_thousand_amount + $closing->two_thousand_amount + $closing->five_thousand_amount + $closing->ten_thousand_amount);
                }
            }
        }
        if ($credit_debit_type_id == 2) {
            $user_wallet = user_wallet::where('id', $wb_account_details->user_wallet->id)->where('balance', '>=', $amount)->first();
            if (! $user_wallet) {
                return ["status" => 0, 'message' => "Insufficient Balance!"];
            }
            $balanceBefore = $user_wallet->balance;
            $balanceAfter  = $balanceBefore - $amount;
            $vaultpayment  = 0 - $amount;
        } else if ($credit_debit_type_id == 1) {
            $user_wallet   = user_wallet::where('id', $wb_account_details->user_wallet->id)->first();
            $balanceBefore = $user_wallet->balance;
            $balanceAfter  = $balanceBefore + $amount;
            $vaultpayment  = $amount;
        } else {
            return ["status" => 0, 'message' => "Error!"];
        }
        $account_typeid = $wb_account_details->id;
        $data           = [
            "balance_before"         => $balanceBefore,
            "amount"                 => $amount,
            "withdrawal_charge"      => $chargeamt,
            "description"            => $desc,
            "account_id"             => $wb_account_details->id,
            "branch_id"              => $wb_account_details->user->branch_id,
            "transaction_status_id"  => 1,
            "transaction_reference"  => $transactionId,
            "transaction_type_id"    => $transaction_type_id,  //debit 7
            "credit_debit_type_id"   => $credit_debit_type_id, //debit 2
            "transaction_remarks_id" => $remark,               //success 2
            "balance_after"          => $balanceAfter,
            "created_date"           => $backdate,
            "created_by"             => $created_by_id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
            "initiator"              => $initiator,
            "currency_id"            => $wb_account_details->currency_id,
        ];
        // die(var_dump($data));
        $transaction = ($data['transactionExist']) ?? transactions::create($data);
        if ($transaction) {
            $user_wallet = user_wallet::where('id', $user_wallet->id)->update(['balance' => $balanceAfter]);
            if (isset($user_wallet->wb_account_details->user->branch_id)) {
                $BranchWallet = BranchWallet::where('branch_id', $user_wallet->wb_account_details->user->branch_id)->first();
                $BranchWallet->balance += $amount;
                $BranchWallet->save();
            }
            if ($companyVault) {
                if (isset($auth->company->Transactionsettings)) {
                    if ($auth->company->Transactionsettings->view_vault_on_txn == 1) {
                        fundVaultByID(['vaultID' => $companyVault, 'value' => $vaultpayment, 'company_id' => $auth->company_id, 'created_by' => $auth->id, 'branch_id' => $auth->branch_id, 'transaction_id' => $transaction->id]);
                    }
                }
            }
            if ($chargeamt > 0) {
                fundVault(['type' => 'withdrawal_charge', 'value' => $chargeamt, 'company_id' => $auth->company_id, 'created_by' => $auth->id, 'branch_id' => $auth->branch_id, 'transaction_id' => $transaction->id]);
            }
            $BranchWallet          = BranchWallet::where('branch_id', $wb_account_details->user->branch_id)->first();
            $BranchWallet->balance = ($credit_debit_type_id == 1) ? ($BranchWallet->balance + $amount) : ($BranchWallet->balance - $amount);
            $BranchWallet->save();
            $currencycode = 'NGN';
            if (isset($wb_account_details->currency->code)) {
                $currencycode = $wb_account_details->currency->code;
            }
            $msg     = ($credit_debit_type_id == 1) ? "Credit Amt:$currencycode $amount Desc: $desc Avail Bal:$currencycode $balanceAfter Date:$backdate Ref:$transactionId" : "Debit Amt:$currencycode $amount  Desc: $desc Avail Bal:$currencycode $balanceAfter Date:$backdate Ref:$transactionId";
            $subject = ($credit_debit_type_id == 1) ? 'Credit Transaction' : 'Debit Transaction';
            if ($request) {
                if (isset($customerCurrency)) {
                    if ($customerCurrency) {
                        if ($closing) {
                            $closing->save();
                        }
                    }
                }
            }
            $smsres = 0;
            if ($sendSMS) {
                $smsres = sendSMS(['txnid' => $transaction->id, 'userid' => $account_typeid], $msg, false, true, $subject);
            }
            DB::commit();
            return ['status' => 1, 'message' => 'Transaction Successful!', 'data' => $transaction, 'statesms' => $smsres];
        } else {
            DB::rollback();
            return ['status' => 0, 'message' => 'Error loging txn'];
        }
    } catch (\Exception $e) {
        DB::rollback();
        return ['status' => 0, 'message' => 'Error  Completing Transaction' . $e->getMessage() . $e->getLine()];
    }
}

function alertData()
{
    // $pending_Customer_account = User::where([''])
    //vouccher //withdrawal // pending transfer
    if (auth()->check()) {
        $pendingtXN = PendingSaving::join('wb_account_details', 'wb_account_details.virtual_accnum', '=', 'pending_savings.account_type')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->where(['users.company_id' => auth()->user()->company_id, 'pending_savings.status_id' => 1])
            ->select('pending_savings.*', 'users.first_name', 'users.last_name', 'wb_account_details.virtual_accnum');
        $is_root = cando('pending-credit-debit|root');
        if (! $is_root) {
            $pendingtXN = $pendingtXN->where('pending_savings.created_by', auth()->user()->id);
        }
        $pendingtXN = $pendingtXN->count();

        $pending_transfer = transactions::with('TXNBankDetails')->where(
            [
                "credit_debit_type_id"   => 2,
                "transaction_type_id"    => 8,
                "transaction_remarks_id" => 4,
            ]
        )->count();

        $isroot = cando('root|approve-reject-vourcher-permission');
        $query  = BranchValut::join('company_valuts', 'company_valuts.id', '=', 'branch_valuts.company_valut_id')
            ->join('staff_wallet', 'staff_wallet.user_id', '=', 'branch_valuts.created_by')
            ->join('users', 'staff_wallet.user_id', '=', 'users.id')
            ->where(['company_valuts.company_id' => auth()->user()->company_id])
            ->select('branch_valuts.*', 'company_valuts.vault', 'staff_wallet.balance', 'staff_wallet.accountno', 'users.first_name', 'users.last_name');
        if (! $isroot) {
            $query = $query->where(['branch_valuts.created_by' => auth()->user()->id, 'company_valuts.vault' => 'Staff_wallet_vault']);
        }
        if ($isroot) {
            $query = $query->where(['branch_valuts.status' => 2]);
        }
        $vouccher = $query->count();
        $sum      = $vouccher + $pending_transfer + $pendingtXN;
        return ['total' => $sum, 'voucher' => [$vouccher], 'pending_transfer' => [$pending_transfer], 'pendingTXN' => [$pendingtXN]];
    } else {
        return ['total' => 0, 'voucher' => 0, 'pending_transfer' => 0, 'pendingTXN' => 0];
    }
}
function getbranchlist()
{
    return auth()->user()->company->branch;
}
function getProductList()
{
    return loan_product::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get();
}
function transactionType($type = null)
{
    $query = transaction_types::query();
    if ($type != null) {
        // if(is_array($type)){

        // }else{

        // }
        $query = $query->orWhereIn('id', $type);
    }
    $query = $query->get();
    return $query;
}
function ExpensesSum($from = null, $to = null, $branchID = null, $ledger = null, $officer = null)
{
    $Expenses = Expenses::join('users', 'users.id', '=', 'expenses.created_by')
        ->where('users.company_id', auth()->user()->company_id);
    if ($officer != null) {
        $Expenses = $Expenses->where('users.created_by', $officer);
    }
    if ($branchID != null) {
        $Expenses = $Expenses->where('users.branch_id', $branchID);
    }
    if ($from != null) {
        $Expenses = $Expenses->where('expenses.created_at', '>=', $from);
    }
    if ($to != null) {
        $Expenses = $Expenses->where('expenses.created_at', '<=', $to);
    }
    if ($branchID != null) {
        $Expenses = $Expenses->where('users.branch_id', $branchID);
    }
    if ($ledger) {
        $Expenses = $Expenses->where('expenses.ledger_dr', $ledger);
    }
    $Expenses = $Expenses->sum('expenses.amount');
    return $Expenses;
}
function OtherIncomeSum($from = null, $to = null, $branchID = null, $ledger = null, $officer = null)
{
    $OtherIncome = OtherIncome::join('users', 'users.id', '=', 'other_incomes.created_by')
        ->where('users.company_id', auth()->user()->company_id);
    if ($officer != null) {
        $OtherIncome = $OtherIncome->where('users.created_by', $officer);
    }
    if ($branchID != null) {
        $OtherIncome = $OtherIncome->where('users.branch_id', $branchID);
    }
    if ($from != null) {
        $OtherIncome = $OtherIncome->where('other_incomes.created_at', '>=', $from);
    }
    if ($to != null) {
        $OtherIncome = $OtherIncome->where('other_incomes.created_at', '<=', $to);
    }
    if ($ledger) {
        $OtherIncome = $OtherIncome->where('other_incomes.ledger_cr', $ledger);
    }
    $OtherIncome = $OtherIncome->sum('other_incomes.amount');
    return $OtherIncome;
}

function AllCustomerTransactionBalance($from = null, $to = null, $branchID = null)
{
    $auth      = auth()->user();
    $searchstr = '';
    $exclude   = " AND (transactions.transaction_type_id != 35 AND transactions.transaction_type_id != 39 AND transactions.transaction_type_id != 73 AND transactions.transaction_type_id != 98 AND transactions.transaction_type_id != 68) ";
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    $query = "
        SELECT (
            (SELECT IFNULL(SUM(amount), 0)
            FROM transactions
            JOIN wb_account_details ON wb_account_details.id = transactions.account_id
            JOIN users ON users.id = wb_account_details.user_id
            WHERE credit_debit_type_id = '1' AND transaction_status_id = 1 AND users.company_id = $auth->company_id AND parent_transaction_id IS NULL $searchstr $exclude)
            - (SELECT IFNULL(SUM(amount), 0)
            FROM transactions
            JOIN wb_account_details ON wb_account_details.id = transactions.account_id
            JOIN users ON users.id = wb_account_details.user_id
            WHERE credit_debit_type_id = '2' AND transaction_status_id = 1 AND users.company_id = $auth->company_id AND parent_transaction_id IS NULL $searchstr $exclude)
        ) AS balance
    ";
    $results = DB::select($query);
    return $results[0]->balance;
}

function transactionReportTypes($cr_dr = null, $from = null, $to = null, $branchID = null, $transactionType = null)
{
    $auth      = auth()->user();
    $searchstr = " AND (transactions.transaction_type_id != 35 AND transactions.transaction_type_id != 39 AND transactions.transaction_type_id != 73 AND transactions.transaction_type_id != 98 AND transactions.transaction_type_id != 68) ";
    if ($cr_dr != null) {
        $searchstr .= " AND transactions.credit_debit_type_id = $cr_dr";
    }
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    if ($transactionType) {
        $searchstr .= " AND transactions.transaction_type_id = '$transactionType'";
    }
    $query = "SELECT
        transactions.transaction_type_id,
        transactions.credit_debit_type_id,
        credit_debit_types.type,
        transaction_types.transaction_type,
        SUM(transactions.amount) as total
    FROM transactions
    JOIN wb_account_details ON wb_account_details.id = transactions.account_id
    JOIN users ON users.id = wb_account_details.user_id
    JOIN transaction_types ON transactions.transaction_type_id = transaction_types.id
    JOIN credit_debit_types ON transactions.credit_debit_type_id = credit_debit_types.id
    WHERE users.company_id = $auth->company_id
        AND transactions.transaction_status_id = 1
        AND users.status_id = 1
        $searchstr
    GROUP BY transactions.transaction_type_id";
    $results = DB::select($query);
    return $results;
}
function Allprofit($from = null, $to = null, $branchID = null, $officer = null)
{
    //7 withdrawal charge profit-
    //8 transfer charge profit-
    //27 bank transfer CHARGES,(company/wba) -profit -
    // 30 customer registration, -profit -
    // 33 sms charge - profit -
    // 38 overdraft charge - profit -
    // 39 esusu acumulated charge - profit -
    // 54 loan fee charge-profile -
    // 55 loan management fee -profile -
    // 56 loan repayment -profile -
    // 57 setup fee -profile -
    // 58 Savings Maintenance Charge -profit -
    // 62 loan penalty -profit -
    // 76 POS Credit Charge - profit(company/wba)
    // 91 Company Whatsapp Charge - profit(company/wba)
    // 92 Customer Whatsapp Charge - profit(company/wba)
    // 95 Driver License Verification fee - profit(company/wba)
    // 100 virtual card charge - profit(company/wba)
    // 41(whatsapp charges) - profit(company/wba)
    // 42(USSD CHARGE) - profit(company/wba)
    // 51 bill payment charge- profit(company/wba)
    // 93 local bank transfer
    $auth               = auth()->user();
    $transactionTypeIDs = [7, 8, 27, 30, 33, 38, 39, 54, 55, 57, 58, 62, 76, 91, 95, 100, 41, 42, 51, 93];

    $query = transactions::select([
        'transactions.transaction_type_id',
        'transactions.credit_debit_type_id',
        'credit_debit_types.type',
        'transaction_types.transaction_type',
        DB::raw('CASE WHEN transactions.transaction_type_id IN (7,8,93) THEN SUM(transactions.withdrawal_charge) ELSE SUM(transactions.amount) END AS total'),
    ])
        ->join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
        ->join('users', 'users.id', '=', 'wb_account_details.user_id')
        ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
        ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
        ->where(function ($query) use ($auth) {
            $query->where('users.company_id', $auth->company_id)
                ->orWhere('transactions.company_id', $auth->company_id);
        })
        ->where('transactions.transaction_status_id', 1)
        ->where('users.status_id', 1)
        ->whereIn('transactions.transaction_type_id', $transactionTypeIDs);

    if ($from) {
        $query->where('transactions.created_at', '>=', $from);
    }

    if ($to) {
        $query->where('transactions.created_at', '<=', $to);
    }

    if ($branchID) {
        $query->where('transactions.branch_id', $branchID);
    }
    if ($officer) {
        $query->where('users.account_officer_id', $officer);
    }

    $query->groupBy('transactions.transaction_type_id');

    $results = $query->get();

    // dd($results);

    return $results;
}

function AllLoss($from = null, $to = null, $branchID = null, $officer = null)
{
    //18 subscription
    // 76 POS Credit Charge - profit(company/wba)
    // 91 Company Whatsapp Charge - profit(company/wba)
    // 92 Customer Whatsapp Charge - profit(company/wba)
    // 95 Driver License Verification fee - profit(company/wba)
    // 100 virtual card charge - profit(company/wba)
    // 41(whatsapp charges) - profit(company/wba)
    // 42(USSD CHARGE) - profit(company/wba)
    // 51 bill payment charge- profit(company/wba)
    // 59 saving interest -loss

    $auth      = auth()->user();
    $searchstr = " AND
        (transactions.transaction_type_id = 18 OR
        transactions.transaction_type_id = 76 OR
        transactions.transaction_type_id = 91 OR
        transactions.transaction_type_id = 92 OR
        transactions.transaction_type_id = 95 OR
        transactions.transaction_type_id = 100 OR
        transactions.transaction_type_id = 41 OR
        transactions.transaction_type_id = 42 OR
        transactions.transaction_type_id = 51 OR
        transactions.transaction_type_id = 59
        ) ";

    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    if ($officer) {
        $searchstr .= " AND users.account_officer_id = '$officer'";
    }
    $query = "SELECT
        transactions.transaction_type_id,
        transactions.credit_debit_type_id,
        credit_debit_types.type,
        transaction_types.transaction_type,
        SUM(CASE WHEN transactions.transaction_type_id = 18 THEN transactions.amount ELSE transactions.company_amount END) as total
    FROM transactions
    JOIN wb_account_details ON wb_account_details.id = transactions.account_id
    JOIN users ON users.id = wb_account_details.user_id
    JOIN transaction_types ON transactions.transaction_type_id = transaction_types.id
    JOIN credit_debit_types ON transactions.credit_debit_type_id = credit_debit_types.id
    WHERE ((users.company_id = $auth->company_id) OR (transactions.company_id = $auth->company_id))
        AND transactions.transaction_status_id = 1
        AND users.status_id = 1
        $searchstr
    GROUP BY transactions.transaction_type_id";
    $results = DB::select($query);
    return $results;
}
function getTransactionTypeData($transactionTypeID, $from = null, $to = null, $branchID = null, $officer = null)
{
    // 56 loan repayment
    $auth      = auth()->user();
    $searchstr = " AND
        (transactions.transaction_type_id = $transactionTypeID) ";
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    if ($officer) {
        $searchstr .= " AND users.account_officer = '$officer'";
    }
    $query = "SELECT
    transactions.*,
    users.first_name,
    users.last_name,
    o.first_name as officer_first_name,
    o.last_name as officer_last_name,
    u.first_name as session_first_name,
    u.last_name as session_last_name,
    users.email,
    credit_debit_types.type AS creditDebitType,
    transaction_types.transaction_type

    FROM transactions
    JOIN wb_account_details ON wb_account_details.id = transactions.account_id
    JOIN users ON users.id = wb_account_details.user_id
    JOIN transaction_types ON transactions.transaction_type_id = transaction_types.id
    JOIN credit_debit_types ON transactions.credit_debit_type_id = credit_debit_types.id
    LEFT JOIN users u on u.id=transactions.created_by
    LEFT JOIN users o on o.id=users.account_officer_id
WHERE users.company_id = $auth->company_id
    AND transactions.transaction_status_id = 1
    AND (users.status_id = 1 OR users.status_id = 8)
    $searchstr";
    $results = DB::select($query);
    return $results;
}
function allCashLoanRepayment($from = null, $to = null, $branchID = null, $returnType = 1, $officer = null)
{
    $users          = User::where('company_id', auth()->user()->company_id)->get();
    $loanRepayments = LoanRepayment::with(['loan.user', 'loan.user.myOfficer' => function ($query) {
        // $query;
    }])
        ->whereIn('loan_id', function ($query) use ($users) {
            $query->select('id')
                ->from('loans')
                ->whereIn('user_id', $users->pluck('id'));
        }); // Assuming 'I' represents interest\
    if ($officer !== null) {
        // $loanRepayments = $loanRepayments->filter(function ($repayment) use ($officer) {
        //     return $repayment->loan->user->myOfficer->account_officer === $officer;
        // });
        $loanRepayments = $loanRepayments->whereHas('loan.user.myOfficer', function ($query) use ($officer) {
            $query->where('account_officer_id', $officer);
        });
    }
    if ($from && ! $to) {
        $loanRepayments = $loanRepayments->whereDate('paymentDate', '>=', $from);
    }
    if (! $from && $to) {
        $loanRepayments = $loanRepayments->whereDate('paymentDate', '<=', $to);
    }
    if ($from && $to) {
        $loanRepayments = $loanRepayments->whereBetween('paymentDate', [$from, $to]);
    }
    if ($branchID) {
        $loanRepayments = $loanRepayments->where('branch_id', $branchID);
    }
    if ($returnType == 1) {
        return $loanRepayments->sum('I');
    } else {
        return $loanRepayments->get();
    }
}

function buildQuerys($ledgerid, $from = null, $to = null, $branchID = null)
{
    $auth      = auth()->user();
    $searchstr = "";
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    $query = "SELECT transactions.amount, ledger_accounts.name,branch.branch_name,transactions.credit_debit_type_id,transactions.created_date,transactions.description
    FROM transactions
    JOIN users ON users.id = transactions.created_by
    JOIN branch ON branch.id = transactions.branch_id
    JOIN ledger_accounts ON (ledger_accounts.id = transactions.ledger_cr OR ledger_accounts.id = transactions.ledger_dr)
    WHERE users.company_id = $auth->company_id
        AND (transactions.ledger_cr = $ledgerid OR transactions.ledger_dr = $ledgerid)
        AND transactions.transaction_status_id = 1
        $searchstr";
    return $query;
}
function getLedgers($ledgerid, $from = null, $to = null, $branchID = null)
{
    $auth      = auth()->user();
    $searchstr = "";
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = '$branchID'";
    }
    $query = "SELECT
    COALESCE(SUM(transactions.amount), 0) AS total
    FROM transactions
    JOIN users ON users.id = transactions.created_by
    WHERE users.company_id = $auth->company_id
    AND (transactions.ledger_cr = $ledgerid)
    AND transactions.transaction_status_id = 1
    $searchstr";
    $credit = DB::select($query);
    $query  = "SELECT
    COALESCE(SUM(transactions.amount), 0) AS total
    FROM transactions
    JOIN users ON users.id = transactions.created_by
    WHERE users.company_id = $auth->company_id
    AND (transactions.ledger_dr = $ledgerid)
    AND transactions.transaction_status_id = 1
    $searchstr";
    $debit = DB::select($query);

    return ['debit' => $debit, 'credit' => $credit];
}
function getLedgersData($ledgerid, $from = null, $to = null, $branchID = null)
{
    // $query = buildQuerys($ledgerid, $from, $to, $branchID);

    $auth      = auth()->user();
    $searchstr = "";
    if ($from) {
        $searchstr .= " AND transactions.created_at >= '$from'";
    }
    if ($to) {
        $searchstr .= " AND transactions.created_at <= '$to'";
    }
    if ($branchID) {
        $searchstr .= " AND transactions.branch_id = $branchID";
    }
    if ($ledgerid) {
        $searchstr .= " AND (transactions.ledger_cr = $ledgerid OR transactions.ledger_dr = $ledgerid)";
    }
    $searchstr = " order by transactions.id desc";

    $query = " SELECT
    transactions.id,
    transactions.transaction_type_id,
    transaction_types.transaction_type,
    transactions.amount,
    branch.branch_name,
    transactions.credit_debit_type_id,
    transactions.created_date,
    transactions.description,
FROM
    transactions
JOIN
    users ON users.id = transactions.created_by
JOIN
    transaction_types ON transaction_types.id = transactions.transaction_type_id
JOIN
    branch ON branch.id = transactions.branch_id
WHERE
    users.company_id = $auth->company_id
    AND transactions.transaction_status_id = 1
    $searchstr";

    $response = DB::select($query);
    return $response;
}
function systemLedgerAccount($ledgerName, $company_id)
{
    $auth    = User::where('company_id', $company_id)->first(); // auth()->user();
    $ledgers = LedgerAccountModel::where(['name' => $ledgerName, 'deleteable' => 0, 'company_id' => $auth->company_id])->first();
    if ($ledgers) {
        return $ledgers;
    } else {
        $code = $randomNumber = mt_rand(1000, 9999);
        while (LedgerAccountModel::where(['code' => $code, 'company_id' => $auth->company_id])->count()) {
            $code = $randomNumber = mt_rand(1000, 9999);
        }
        $addAccount             = new LedgerAccountModel;
        $addAccount->access     = 1;
        $addAccount->code       = $code;
        $addAccount->name       = $ledgerName;
        $addAccount->deleteable = 0;
        $addAccount->created_by = $auth->id;
        $addAccount->company_id = $auth->company_id;
        $addAccount->save();
        return $addAccount;
    }
}
function ledgers()
{
    return LedgerAccountModel::where('company_id', auth()->user()->company_id)->get();
}
function get_CR_DR_TXN_Type_Ledger($id, $company_id)
{
    return LedgerSettings::where(['transaction_type_id' => $id, 'company_id' => $company_id])->first();
}

function forwardRequestToWirelesspay($endpoint, $request)
{
    $header = [
        "ApiExchangeKey" => "ZZGGAgh3&=+NPgw}ME}C05Yk.:u[6ie",
    ];

    $terminalResponse = postRequest($header, $request, "https://wba.ng/v1/$endpoint");

    logInfo($terminalResponse, "Another Third party response from wireless verify");

    return $terminalResponse;
}

function forwardRequestToAM($endpoint, $request)
{
    $header = [
        "ApiExchangeKey" => ".:u[ZZGGg9bb)2Q5f=9&1fm_TFBxW7W5Unkm",
    ];

    $terminalResponse = postRequest($header, $request, "https:/$endpoint");

    logInfo($terminalResponse, "Another Third party response from wireless verify");

    return $terminalResponse;
}

function ExpensesReportLedger($from = null, $to = null, $branchID = null, $ledger = null)
{
    $Expenses = Expenses::with('expenseType')
        ->join('users', 'users.id', '=', 'expenses.created_by')
        ->join('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')
        ->where('users.company_id', auth()->user()->company_id)
        ->where('expenses.ledger_dr', '!=', null);
    if ($branchID != null) {
        $Expenses->where('users.branch_id', $branchID);
    }

    if ($from != null) {
        $Expenses->where('expenses.created_at', '>=', $from);
    }

    if ($to != null) {
        $Expenses->where('expenses.created_at', '<=', $to);
    }

    if ($branchID != null) {
        $Expenses->where('users.branch_id', $branchID);
    }
    if ($ledger) {
        $Expenses = $Expenses->where('expenses.ledger_dr', $ledger);
    }
    $Expenses->groupBy('expenses.expense_type_id');
    $Expenses->selectRaw('SUM(expenses.amount) as total, expense_types.name');

    return $Expenses->get();
}

function OtherIncomeLedger($from = null, $to = null, $branchID = null, $ledger = null)
{
    $OtherIncome = OtherIncome::with('otherIncomeType')
        ->join('users', 'users.id', '=', 'other_incomes.created_by')
        ->join('other_income_types', 'other_income_types.id', '=', 'other_incomes.other_income_type_id')
        ->where('users.company_id', auth()->user()->company_id)
        ->where('other_incomes.ledger_cr', '!=', null);

    if ($branchID != null) {
        $OtherIncome->where('users.branch_id', $branchID);
    }

    if ($from != null) {
        $OtherIncome->where('other_incomes.created_at', '>=', $from);
    }
    if ($ledger) {
        $OtherIncome = $OtherIncome->where('other_incomes.ledger_cr', $ledger);
    }
    if ($to != null) {
        $OtherIncome->where('other_incomes.created_at', '<=', $to);
    }

    $OtherIncome->groupBy('other_incomes.other_income_type_id');
    $OtherIncome->selectRaw('SUM(other_incomes.amount) as total, other_income_types.name');

    return $OtherIncome->get();
}

function checkFrequencyInPeriod($selectedFrequencyOption, $selectPaymentOption)
{
    $NoInPeriod = 0;

    if ($selectPaymentOption == 'months') {
        if ($selectedFrequencyOption == 'Monthly') {
            return 1;
        }
        if ($selectedFrequencyOption == 'Weekly') {
            return 4;
        }
        if ($selectedFrequencyOption == 'Daily') {
            return 30;
        }
    }
    if ($selectPaymentOption == 'weeks') {
        if ($selectedFrequencyOption == 'Weekly') {
            return 1;
        }
        if ($selectedFrequencyOption == 'Daily') {
            return 7;
        }
    }
    if ($selectPaymentOption == 'years') {
        if ($selectedFrequencyOption == 'Monthly') {
            return 12;
        }
        if ($selectedFrequencyOption == 'Weekly') {
            return 4 * 12;
        }
        if ($selectedFrequencyOption == 'Daily') {
            return 30 * 12;
        }
    }
}
function convertToDate($period)
{
    $return = 1;
    switch ($period) {
        case 'months':
            $return = 30;
            break;
        case 'weeks':
            $return = 7;
            break;
        case 'years':
            $return = 30 * 12;
            break;
        default:
    }
    return $return;
}
function getTransactionIdByName($name)
{
    if (is_array($name)) {
        return transaction_types::whereIn('transaction_type', $name)->pluck('id')->toArray();
    } else {
        return transaction_types::where('transaction_type', $name)->first();
    }
}
function getallBranchAndStaff()
{
    return ['staff' => auth()->user()->company->allStaff, 'branch' => auth()->user()->company->branch];
}

function debitCreditCompany($data)
{

    $ledger_cr            = null;
    $ledger_dr            = null;
    $auth                 = $data['auth'];
    $transationid         = $data['transaction_id'];
    $desc                 = $data['desc'];
    $currency             = $data['currency'] ?? 'NGN';
    $amount               = $data['amount'];
    $credit_debit_type_id = $data['credit_debit_type_id'];
    $transactionref       = generate_txnID();
    $currencies           = currencies::where('code', $currency)->first();

    $company_wallet = company_wallet::where(['company_id' => $auth->company_id, 'currency_code' => $currency])->first();
    if (! $company_wallet) {
        $company_wallet_init = company_wallet::where(['id' => $auth->company_id])->first();

        $company_wallet = company_wallet::create([
            'currency_code' => $currency,
            'balance'       => 0,
            'sms'           => 0,
            'ussd'          => 0,
            'bank_id'       => $company_wallet_init->bank_id,
            'whatsapp'      => 0,
        ]);
    }
    $company_balance_before = $company_wallet->balance;
    $company_balance_after  = ($credit_debit_type_id == 1) ? $company_wallet->balance + $amount : $company_wallet->balance - $amount;
    $ledger_Settings        = get_CR_DR_TXN_Type_Ledger($transationid, $auth->company_id);
    if ($ledger_Settings) {
        $ledger_cr = $ledger_Settings->ledger_cr;
        $ledger_dr = $ledger_Settings->ledger_dr;
    }
    $data_company = [
        "company_balance_before" => $company_balance_before,
        "company_amount"         => $amount,
        "company_balance_after"  => $company_balance_after,
        "balance_before"         => 0,
        "balance_after"          => 0,
        "amount"                 => 0,
        "withdrawal_charge"      => 0,
        "description"            => $desc,
        "company_id"             => $auth->company_id,
        "branch_id"              => $auth->branch_id,
        "transaction_reference"  => $transactionref,
        "transaction_type_id"    => $transationid,         //debit 7
        "credit_debit_type_id"   => $credit_debit_type_id, //debit 2
        "transaction_remarks_id" => 2,                     //success 2
        "created_date"           => date('Y-m-d H:i:s'),
        "created_by"             => $auth->id,
        "ledger_dr"              => $ledger_dr,
        "ledger_cr"              => $ledger_cr,
        "currency_id"            => $currencies->id,
    ];
    $txn                     = transactions::create($data_company);
    $company_wallet->balance = $company_balance_after;
    $company_wallet->save();
    return $txn;
}

function smsCustomer($phone, $msg, $company_short, $title)
{
    $apikey = env('SMS_API_KEY');

    logInfo("{$apikey}", "SMS_API_KEY SMS Customer");

    $type = match ($title) {
        'Password reset'        => 'dnd',
        'Account reset for'     => 'dnd',
        'PIn reset'             => 'dnd',
        'Finova Login Security' => 'dnd',
        'OTP Confirmation'      => 'dnd',
        'Verify OTP'            => 'dnd',
        'Reset Password OTP'    => 'dnd',
        default                 => 'generic', // fallback
    };

    if ($type != "generic") {
        $company_short = "N-Alert";
    } else {
        $type = match ($company_short) {
            'Gosentra'    => 'dnd',
            'kipkash'     => 'dnd',
            'Finovaedge'  => 'dnd',
            'STRINGS PAY' => 'dnd',
            'SDDTIF'      => 'dnd',
            'COCTAL'      => 'dnd',
            'GoldRidg'    => 'dnd',

            default       => 'generic',
        };
    }

/*
    $validCompanies = [
        'Gosentra',
        'kipkash',
        'Finovaedge',
        'STRINGS PAY',
        'SDDTIF',
        'COCTAL',
        'GoldRidg',
    ];

    if (! in_array($company_short, $validCompanies, true)) {
        if ($type == "dnd") {
            $company_short = "N-Alert";
        }
    }
*/
    logInfo($company_short, "Company Short Name: ");
    logInfo($type, "Channel: ");

    $data = [
        'api_key' => $apikey,
        'to'      => $phone,
        'from'    => $company_short,
        'sms'     => $msg,
        'type'    => 'plain',
        'channel' => $type,

    ];

    $post_data = json_encode($data);
// terma
    $url      = "https://api.ng.termii.com/api/sms/send";
    $response = Http::post($url, $data);
    $result   = json_decode($response->getBody()->getContents());
    return $result;

}

function goldencreditSMSPost($phone, $transaction_type, $amount = null, $balance = null, $account_id = null, $code = null)
{

    if ($transaction_type == "otp") {
        $endpoint = "https://appservice.karadyn.com/app/sms/otp";
        $data     = [
            "Phone" => $phone,
            "Code"  => $code,

        ];
    }

    if ($transaction_type == "transaction") {
        $endpoint = "https://appservice.karadyn.com/app/sms/sendtransaction";
        $data     = [
            "Phone"     => $phone,
            "Amount"    => $amount,
            "Type"      => "Deposit",
            "Balance"   => $balance,
            "AccountID" => $account_id,
            "Date"      => Carbon::now(),

        ];
    }

    if ($transaction_type == "passwordreset") {
        $endpoint = "https://appservice.karadyn.com/app/sms/passwordreset";
        $data     = [
            "Phone" => $phone,
            "Code"  => $code,

        ];
    }

    $response = Http::withHeaders(goldencreditHeader())->post("$endpoint", $data);
    logInfo($response, "Golden Credit Sms : ");
    return $response;
}

function goldencreditHeader()
{
    $header = [
        "Content-Type"  => "application/json",
        "Authorization" => "cyuwnGScgR.YpNGHxHQorOFtN5ApLil4Ohn1ZPDqE68SFn4Jog2",
        "Clientid"      => "XCPfFbVaPLPfVcBC",
    ];

    return $header;
}

function reverseTransactionsMessages($ref)
{
    $transactions = ['BM2025122821591234654808724CITYAFRICA', 'BM2025123012292382492184995', 'BM2025123012375424851763987', 'BM2025123012394923398215462'];
    if ($ref == "BM2025123012292382492184995") {
        return response()->json(['status' => 0, "Transaction Verified Successfully (Session ID: 090267251229130015117248061405)"]);
    }
    if ($ref == "BM2025123012375424851763987") {
        return response()->json(['status' => 0, "Transaction Verified Successfully (Session ID: 090267251228202452778927148405)"]);
    }
    if ($ref == "BM2025123012394923398215462") {
        return response()->json(['status' => 0, "Transaction Verified Successfully (Session ID: 090267251228202224822735315405)"]);
    }

}

function loadProfit($from = null, $to = null, $branchID = null, $officer = null)
{
    $transactionTypeIDs = [
        7, 8, 27, 30, 33, 38, 39, 54, 55, 57, 58, 62,
        76, 91, 95, 100, 41, 42, 51, 93,
    ];

    /*  $this->revenue =  DailySummary::where('company_id', auth()->user()->company_id)
        ->whereBetween('summary_date', [$this->from, $this->to])
        ->whereIn('transaction_type_id', $transactionTypeIDs)
        ->groupBy('transaction_type_id')
        ->selectRaw('transaction_type_id, SUM(amount) as total')
        ->get();*/

    /*  $revenue = DailySummary::where('company_id', auth()->user()->company_id)
        ->whereBetween('summary_date', [$from, $to])
        ->whereIn('transaction_type_id', $transactionTypeIDs)
        ->join('transaction_types as tt', 'tt.id', '=', 'daily_summaries.transaction_type_id')
        ->groupBy('transaction_type_id')
        ->selectRaw('
            daily_summaries.transaction_type_id,
            tt.transaction_type as transaction_type,
            SUM(daily_summaries.amount) as total
        ')
        ->get();*/

    \Log::info('Start batch checking reports ----> ');

    \Log::info('company_id', [auth()->user()->company_id]);

    \Log::info('from', [$from]);

    \Log::info('to', [$to]);

    $revenue = DailySummary::where('company_id', auth()->user()->company_id)
        ->whereBetween(DB::raw('DATE(summary_date)'), [$from, $to])
        ->whereIn('transaction_type_id', $transactionTypeIDs)
        ->groupBy('transaction_type_id')
        ->selectRaw('
            daily_summaries.transaction_type_id,
            tt.transaction_type as transaction_type,
            SUM(daily_summaries.amount) as total
        ')
        ->get();

    dd($revenue);

    return $revenue;
}
