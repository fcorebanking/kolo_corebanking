<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\GTBController;
use App\Models\banks;
use App\Models\CustomerReferal;
use App\Models\CustomerReferalSetting;

use App\Models\branch;
use App\Models\Company;
use App\Models\company_wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\frequency;
use App\Models\savings_product;
use App\Models\transactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Models\company_settings;
use App\Models\Contries;
use App\Models\wb_account_details;
use App\Models\user_wallet;
use App\Models\CooperateCustomer;
use Illuminate\Support\Facades\Hash;
use Image;
use App\Models\NextOfKinDetail;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Exception;
use Illuminate\Support\Facades\Storage;

class PublicTransactionController extends Controller
{
    private $flutterwaveSecretKey;
    private $baseUrl;
    private $vtpassController;
    private $reloadlyController;

    function __construct()
    {
        if (env('APP_MODE') == 'test') {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_TEST');
        } else {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_LIVE');
        }
        $this->baseUrl = url('/');
    }

    public function paymentLink($company_reference)
    {
        $company = company_wallet::where('refaccount', $company_reference)->first();

        if ($company) {

            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => '',
                'alt_menu' => '',
                'status' => frequency::all(),
                'isReferenceValid' => true,
                'company_reference' => $company_reference,
                'bank_name' => $company->bank->bank_name,
                'currency' => $company->company->currency,
                'virtual_account' => $company->vaccount,
                'company_reference' => $company_reference,
                'company_name' => $company->company->company_name

            ];
            return view('company.ebanking.paymentlinks.payment_links_page')->with($data);
        } else {

            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => '',
                'alt_menu' => '',
                'status' => frequency::all(),
                'isReferenceValid' => false
            ];
            return view('company.ebanking.paymentlinks.payment_links_page')->with($data);
        }
    }

    public function customerRegistration($company_reference, Request $request)
    {
        $countries = Contries::all();

        if ($request->referral) {
            $checkRef = User::where('virtual_account', $request->referral)->first();
            $countries = Contries::all();
            if (!$checkRef) {
                $data = [
                    'category_name' => 'auth',
                    'page_name' => 'auth_default',
                    'has_scrollspy' => 0,
                    'scrollspy_offset' => '',
                    'page_menu' => '',
                    'alt_menu' => '',
                    'isReferenceValid' => false,
                    'request' => $request,
                    'errortype' => 1,
                    'countries' => $countries
                ];
                Session::flash('error', 'Invalid Referrer');
                return view('company.customer.custom-link.create_customer_account')->with($data);
            }
        }
        $company = company_wallet::where('refaccount', $company_reference)->first();
        if ($company) {

            $companySetting  = company_settings::where('company_id', $company->company_id)->first();

            if (!$companySetting) {
                $data = [
                    'category_name' => 'auth',
                    'page_name' => 'auth_default',
                    'has_scrollspy' => 0,
                    'scrollspy_offset' => '',
                    'page_menu' => '',
                    'alt_menu' => '',
                    'status' => frequency::all(),
                    'isReferenceValid' => false,
                    'request' => $request,
                    'countries' => $countries
                ];

                Session::flash('error', 'Bank not dictated please contact support to resolve the concern');

                return view('company.customer.custom-link.create_customer_account')->with($data);
            }

            $preferredBankId = $company->company->setting->preferred_bank_id;
            $selectedBank = banks::find($preferredBankId);

            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => 'custom_register_link_menu',
                'alt_menu' => '',
                'isReferenceValid' => true,
                'company_reference' => $company_reference,
                'company_name' => $company->company->company_name,
                'company' => $company->company,
                'supportEmail' => $company->company->company_email,
                'supportPhone' => $company->company->company_phone,
                'company_logo' => $company->company->logo,
                'request' => $request,
                'countries' => $countries,
                'branch' => branch::has('author')->where('company_id', $company->company_id)->get()
            ];
            $account_types = savings_product::where('company_id', $company->company_id)->get();
            $banks = banks::where('in_use', 1)->get();

            return view('company.customer.custom-link.create_customer_account', [
                'account_types' => $account_types,
                'banks' => $banks,
                'selectedbank' => $selectedBank,

            ])->with($data);
        } else {

            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => '',
                'alt_menu' => '',
                'status' => frequency::all(),
                'isReferenceValid' => false,
                'request' => $request,
                'countries' => $countries
            ];
            return view('company.customer.custom-link.create_customer_account')->with($data);
        }
    }


    public function create_customer_account(Request $request)
    {

        $request->branch = Crypt::decryptString($request->branch);
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name' => 'required|string|max:50|min:2',
            'phone' => 'required|string',
            'email' => 'required|email|string',
            'dob' => 'required',
            'branch' => 'required',
            'account_id' => 'required',
            'username' => 'required|string|unique:users',
            'bvn' => 'required|string',
            'password' => 'required|string',
            'transaction_pin' => 'required|string',

        ]);

        $account_type_msg = 'Wallet';
        if ($validator->fails()) {

            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $messages,
                'subtext' => $messages,

                'status' => 0,

            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }
        $salaryAccount = '';
        $salaryBank = '';
        $referral = null;

        if ($request->fullresponse != null) {
            if ($request->bank_Account_number != null && $request->bank != null) {
                $salaryAccount = $request->bank_Account_number;
                $salaryBank = $request->bank;
            } else {
                return response()->json(["status" => 0, 'message' => "Input salary account information"]);
            }
        }
        $companyWallet = company_wallet::where('refaccount', $request->company_reference)->first();
        $auth = User::where('company_id', $companyWallet->company_id)->first();
        if (!$companyWallet) {
            return response()->json(["status" => 0, 'message' => "Invalid Company Reference"]);
        }
        if ($request->referral) {
            $checkRef = User::where(['virtual_account' => $request->referral, 'company_id' => $companyWallet->company_id])->first();
            if (!$checkRef) {
                return response()->json(["status" => 0, 'message' => "Invalid Referrer!"]);
            }
            $referral = $checkRef->id;
        }
        $message =  "";
        if ($request->email) {
            $emailCount = User::where(['email' => $request->email, 'company_id' => $auth->company_id])->count();
            if ($emailCount > 0) {
                $message =  "Email already exists";
            }
        }
        $phoneCount = User::where(['phone' => $request->phone, 'company_id' => $auth->company_id])->count();
        if ($phoneCount > 0) {
            $message =  "Phone already exists";
        }
        if ($request->username) {
            $usernameCount = User::where(['username' => $request->username, 'company_id' => $auth->company_id])->count();
            if ($usernameCount > 0) {
                $message =  "Username already exists";
            }
        }
        if ($message != "") {
            $ra = array("status" => 0, "message" => $message);
            return response()->json($ra);
        }

        $allcount = User::where(['company_id' => $companyWallet->company_id, 'user_type_id' => 1])->count();
        if ($companyWallet->company->sub->plan->max_customers < $allcount) {
            $datax = [
                'message' => "Maximum Customer Reached, Upgrade Plan!",
                'subtext' => "Maximum Customer Reached, Upgrade Plan!",
                'status' => 0,
            ];
            return response()->json($datax);
        }
        $company_setting = $companyWallet->company->setting;
        $regfee = $company_setting->registration_fee;
        $ussd_control = $company_setting->ussd_control;
        $account_status = $company_setting->account_approval;
        $whatsapp_control = $company_setting->whatsapp_control;
        $virtual_acccount_number = rand(1000000000, 9999999999);
        $officerid = User::where('uuid', $request->officer)->first();
        $txnpin = $request->transaction_pin;
        $newpsw = $request->password;
        $sms = $request->sms;
        $customer_reference = "Kae33344c2l99" . rand(120030, 999999);
        $bankId = 231;

        if ($request->account_type == 'virtual') {

            $bank = banks::where('bank_code', $request->account_bank)->first();
            $bankId = $bank->id;
            $account_type_msg = 'Virtual';
            // if ($request->account_category == 'personal') {

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode = $request->account_bank;
            $virtualAccount->currencyCode = $companyWallet->company->currency->code;
            $virtualAccount->firstName = $request->first_name;
            $virtualAccount->companyShortName = $companyWallet->company->short_name;
            $virtualAccount->companyName = $request->company_name;
            $virtualAccount->lastName = $request->last_name;
            $virtualAccount->accountType = $request->account_category;
            $virtualAccount->email = $request->email;
            $virtualAccount->phone = $request->phone;
            $virtualAccount->dob = $request->dob;
            $virtualAccount->gender = $request->gender;
            $virtualAccount->bvn = $request->bvn;
            $virtualAccount->address = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyWallet->company->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response =  $createAccountService->createVirtualAccount($virtualAccount);

            if (!$response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }

            logInfo($response, "Create Account service response");
            $customer_reference = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];
        }

        $accountOfficer = User::where(['company_id' => $companyWallet->company_id, 'user_type_id' => 2])->first();

        $user = User::create([

            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'uuid' => uniqid(),
            'phone' => $request->phone,
            'username' => $request->username,
            'pin' => $txnpin,
            'status_id' => $account_status == 0 ? 7 : 1,
            'bvn' => $request->bvn,
            'email' => $request->email,
            'account_officer_id' => $accountOfficer->id,
            'branch_id' => $request->branch,
            'user_type_id' => 1,
            'reg_date' => isset($request->registration_date) ? $request->registration_date : Carbon::now(),
            'company_id' => $companyWallet->company_id,
            'address' => $request->address,
            'dob' => $request->dob,
            'sms' => 1,
            'bank_id' => $bankId,
            'virtual_account' => $virtual_acccount_number,
            'password' => Hash::make($newpsw),
            'sex' => $request->gender,
            'country_id' => $request->country,
            'nin' => $request->national_id,
            'ussd_status_id' => $ussd_control,
            'whatsapp_status_id' => $whatsapp_control,
            'account_reference' => $customer_reference,
            'remittar_accountNo' => $salaryAccount,
            'remittar_bankCode' => $salaryBank,
            'referral_id' => $referral,
        ]);
        // referral
        // CustomerReferal
        // CustomerReferalSetting
        if ($request->referral) {
        }

        NextOfKinDetail::create([
            'user_id' => $user->id,
            'nok_name' => $request->nok_name,
            'nok_phone' => $request->nok_phone,
            'nok_address' => $request->nok_address,
        ]);

        if ($request->account_category == 'coperate') {
            CooperateCustomer::create([
                'user_id' => $user->id,
                'account_name' => $request->company_name,
                'tin' => $request->tin_Number,
                'cac_reg_no' => $request->reg_number,
                'created_by' => $user->id
            ]);
        }
        $wb_account_details = wb_account_details::create([
            'user_id' => $user->id,
            'account_product_id' => $request->account_id,
            'bankid' => $bankId ?? 25,
            'virtual_accnum' => $virtual_acccount_number,
            'acc_reference' => $virtual_acccount_number,
            'has_atm_card' => 0,
        ]);
        user_wallet::create([
            'wb_account_detail_id' => $wb_account_details->id,
            'balance' => (0 - $regfee)
        ]);
        $ledger_cr = null;
        $registerLedger = get_CR_DR_TXN_Type_Ledger(30, $companyWallet->company_id);
        if ($registerLedger) {
            $ledger_cr = $registerLedger->ledger_cr;
            $ledger_dr = $registerLedger->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set customer registration transaction type in ledger settings !']);
        }
        if ($ledger_cr) {
            $transaction = transactions::create([
                "balance_before" => 0,
                "amount" => $regfee,
                "withdrawal_charge" => 0,
                "description" => 'Account registration',
                "account_id" => $wb_account_details->id,
                "branch_id" => $user->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" => 30, //debit 7
                "credit_debit_type_id" => 2, //debit 2
                "transaction_remarks_id" => 2, //success 2
                "balance_after" => (0 - $regfee),
                "created_date"  => date('Y-m-d H:i:s'),
                "created_by" => $user->id,
                "ledger_cr" => $ledger_cr,
                "ledger_dr" => $ledger_dr
            ]);
        }


        if ($company_setting->restrict_customers == 1) {
            \restrictUserAccountAPI($user->id, 2, "Registration User Block");
        }


        if ($request->account_bank == "000013") {

            $debitCompany = debitCompanyWithCharge($companyWallet->company_id, 10, 0);
            if ($ledger_cr) {
                $transaction = transactions::create([
                    "balance_before" => 0,
                    "amount" => 0,
                    "withdrawal_charge" => 0,
                    "description" => 'GTB virtual account opening fee',
                    "branch_id" => $user->branch_id,
                    "transaction_status_id" => 1,
                    "company_id" => $companyWallet->company_id,
                    "company_balance_before" => $debitCompany["balance_before"],
                    "company_amount" => 10,
                    "company_balance_after" => $debitCompany["balance_after"],
                    "transaction_reference" => generate_txnID(),
                    "transaction_type_id" => 30, //debit 7
                    "credit_debit_type_id" => 2, //debit 2
                    "transaction_remarks_id" => 2, //success 2
                    "created_date"  => date('Y-m-d H:i:s'),
                    "created_by" => $user->id,
                    "ledger_cr" => $ledger_cr,
                    "ledger_dr" => $ledger_dr
                ]);
            }
        }

        \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

        if ($request->hasFile('passport')) {
            try {

                $uploadPath = "public/company/customer/profile_pic/$user->id/";

                $image = $request->file('passport');

                // create a new filename for the resized image
                $filename = time() . '.' . $image->getClientOriginalExtension();

                // resize the image
                $resizedImage = Image::make($image)
                    ->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode();

                // upload the resized image to storage
                $upload = Storage::put($uploadPath . $filename, $resizedImage);
                // $file1 = $doc->storeAs("$uploadPath/$user->id/$filename", $resizedImage);
                $user->update([
                    'profile_pic' => $uploadPath . $filename
                ]);
            } catch (Exception $e) {
                //die($e);
            }
        }

        if ($request->hasFile('valid_id')) {

            try {
                $uploadPath = "public/company/customer/validID_file/$user->id/";

                $image = $request->file('valid_id');

                // create a new filename for the resized image
                $filename = time() . '.' . $image->getClientOriginalExtension();

                // resize the image
                $resizedImage = Image::make($image)
                    ->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode();

                // upload the resized image to storage
                $upload = Storage::put($uploadPath . $filename, $resizedImage);
                // $file1 = $doc->storeAs("$uploadPath/$user->id/$filename", $resizedImage);
                $user->update([
                    'validID_file' => $uploadPath . $filename
                ]);
            } catch (Exception $e) {
                //die($e);
            }
        }
        $branchName = isset($user->bank->bank_name) ? $user->bank->bank_name : '';

        $companyname = $companyWallet->company->company_name;
        $datax = [
            'message' => "Welcome to $companyname, your TXN PIN: $txnpin, Password: $newpsw and your $account_type_msg account is $virtual_acccount_number",
            'subtext' => "Welcome to $companyname, your TXN PIN: $txnpin, Password: $newpsw and your $account_type_msg account is $virtual_acccount_number",
            'desc' => "Welcome to $companyname",
            'status' => 1,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'account_number' => $virtual_acccount_number,
            'test' => $request->push_wmn,
            'bank' => $branchName,
            'password' => $newpsw
        ];
        $branch = ($user->branch) ? $user->branch->branch_name : ' default ';
        if (strlen($companyname) > 20) $companyname = substr($companyname, 0, 20) . '...';

        $smsresponse = sendSMS($user->id, "Welcome to $companyname, we are excited to have you, your Pin: $txnpin, Password: $newpsw, $account_type_msg No: $virtual_acccount_number, Bank: $branchName", true, true, 'Account', $virtual_acccount_number);
        if ($request->fullresponse) {
            if ($request->fullresponse == 'rrxgfuja') {
                $datax['userid'] = $user->id;
                return response()->json($datax);
            }
        }
        return response()->json($datax);
        //  return redirect('/account_creation_success')->with('account_creation_msg','Account Created Successfully. Your account number is ');
    }

    public function paymentLinkPage(Request $request)
    {

        $company = company_wallet::where('refaccount', $request->company_reference)->first();

        $data = [
            'category_name' => 'payment_link_page',
            'page_name' => 'html5',
            'page_menu' => 'payment_link_page',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'bank_name' => $company->bank->bank_name,
            'virtual_account' => $company->vaccount,
            'company_reference' => $request->company_reference,
            'company_name' => $company->company->company_name,
            'currency' => $company->company->currency
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }


        return view('company.ebanking.paymentlinks.payment_links_page')->with($data);
    }


    public function initiatePaymentLink(Request $request)
    {
        \logInfo($request->all(), "Initiate Payment link request");
        $transactionId = generateTransactionReference();

        $company_wallet = company_wallet::where('refaccount', $request->company_reference)->first();

        if ($company_wallet) {

            $adminUser =  $company_wallet->company->user[0];

            $transaction = transactions::create([

                "balance_before" => $company_wallet->balance,
                "amount" => $request->finalAmount,
                "description" => "Payment via Payment link, Sender Phone: $request->phone",
                "transaction_reference" => $transactionId,
                "company_id" =>  $company_wallet->company->id,
                "transaction_remarks_id" => 1,
                "transaction_type_id" => $request->transaction_type,
                "credit_debit_type_id" => 1,
                "created_date"  => date('Y-m-d'),
                "created_by" => $adminUser->id
            ]);

            $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
            $body = [
                "tx_ref" => $transactionId,
                "amount" => $request->finalAmount,
                "currency" => $company_wallet->company->currency->code,
                "redirect_url" => url("/transaction/verify-fund-wallet?company_reference=$request->company_reference"),
                "payment_options" => "card",
                "customer" => [
                    "email" =>  $adminUser->email,
                    "phonenumber" =>  $adminUser->phone,
                    "name" =>  $adminUser->first_name . ' ' .  $adminUser->last_name
                ],
                "customizations" => [
                    "title" => "Funding WBA Wallet",
                    "description" => "Fund Wired Banking Wallet",
                    "logo" => "https://wiredbanking.ng/img/logo/logo-dark.png"
                ]
            ];
            $url = 'https://api.flutterwave.com/v3/payments';

            $jsonData = \postRequest($header, $body, $url);

            // {"status":"success","message":"Hosted Link","data":{"link":"https:\/\/ravemodal-dev.herokuapp.com\/v3\/hosted\/pay\/79d6b15bf0bcb701e77d"}}
            return response()->json(["status" => 1, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => " " . generateTransactionReference()]);
        } else {
            return response()->json(["status" => 0, "transactionId" => $transactionId, 'message' => "Invalid company Reference"]);
        }
    }

    public function get_branch_staff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_reference' => 'required|string',
            'branch' => 'required|string'
        ]);

        if ($validator->fails()) {
            return \response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]]);
        }

        $company = company_wallet::where('refaccount', $request->company_reference)->first();

        if (!$company) {
            return response()->json(["status" => 0, 'message' => "Invalid Company Reference"]);
        }

        $id = Crypt::decryptString($request->branch);
        $user = User::where(['company_id' => $company->company_id, 'branch_id' => $id, 'user_type_id' => 2])->select('id', 'uuid', 'first_name', 'last_name')->get();
        return json_encode($user);
    }


    public function validate_details(Request $request)
    {

        // $username = User::where('username', $request->username)->count();

        $company = company_wallet::where('refaccount', $request->company_reference)->first();

        if ($company) {

            $email = User::where(['email' => $request->email, 'company_id' => $company->company_id])->count();
            $phone = User::where(['phone' => $request->phone, 'company_id' => $company->company_id])->count();
            $username = User::where(['username' => $request->username, 'company_id' => $company->company_id])->count();
            return response()->json([
                'user' => $username ? 1 : 0,
                'username' => $username ? 1 : 0,
                'email' => $email ? 1 : 0,
                'phone' => $phone ? 1 : 0,
            ]);
        } else {
            return response()->json([]);
        }
    }


    public function verifyFundWallet(Request $request)
    {

        \logInfo($request->all(), "Verify Fund Wallet request");
        $transactionRef = $request->query('tx_ref');
        $transactionId = $request->query('transaction_id');
        $status = $request->query('status');
        $companyReference = $request->query('company_reference');

        //Checks company wallet reference code
        $company_wallet = company_wallet::where('refaccount', $request->company_reference)->first();

        $company =  $company_wallet->company;

        //Checks the status of the transaction from payment-gateway (Flutterwave/Paystack)
        if ($status == 'successful') {

            //Check if the transaction reference exists on our database
            $transaction = transactions::where(['transaction_reference' => $transactionRef])->first();

            //This runs when the transaction reference exists on our database
            if ($transaction) {

                //This runs when the transaction has already been handled... or amount has already been credited to the user
                if ($transaction->transaction_remarks_id == 2) {
                    $request->session()->flash('error', "Duplicate Transaction");

                    return redirect('/transaction/payment-link/' . $companyReference);

                    //This runs when the transaction is not handled yet
                } else {

                    $flutterwaveController = new FlutterwaveController();
                    //Verifying the transaction from payment-gateway(Flutterwave/Paystack)
                    $verifyTransaction = $flutterwaveController->verifyTransaction($transactionId);

                    \logInfo($verifyTransaction, "Flutterwave Verification Log");

                    //Runs when the verification was successfull
                    if ($verifyTransaction["status"]) {

                        $wallet = company_wallet::where('company_id', $company->id)->first();

                        //*****THIS WAS WERE THE GUY TOOK ADVANTAGE OF BY SENDING HIS OWN VALUES */
                        //Credits the company wallet account with amount gotten from payment-gateway verification
                        $creditCompany = \creditCompanyWithCharge($company->id, $verifyTransaction['data']['amount'], 0);

                        if (!$creditCompany['status']) {
                            $creditCompany['status'] = 0;
                            return response()->json($creditCompany);
                        }

                        //Update transaction as successful and update balance after
                        $transaction = transactions::where('transaction_reference', $transactionRef)->first();
                        $transaction->balance_after =  $creditCompany['balance_after'];
                        $transaction->transaction_remarks_id = 2;
                        $transaction->save();

                        $request->session()->flash('success', "Payment Sent Successfully");

                        //SMS is supposed to be sent here to the company admin

                        return redirect('/transaction/payment-link/' . $companyReference);
                    }

                    return $verifyTransaction;
                }
            } else {
                $request->session()->flash('error', "Invalid Transaction Reference");

                return redirect('/transaction/payment-link/' . $companyReference);
            }
        } else {
            $request->session()->flash('error', "Transaction Failed");

            return redirect('/transaction/payment-link/' . $companyReference);
        }
    }
}
