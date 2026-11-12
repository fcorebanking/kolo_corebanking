<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Models\Company;
use App\Models\company_types;
use App\Models\company_wallet;
use App\Models\Contries;
use App\Models\currencies;
use App\Models\Custom_role_as_permission;
use App\Models\plans;
use App\Models\StaffpermissionTable;
use App\Models\Staffrole;
use App\Models\StaffWallet;
use App\Models\sub;
use App\Models\sublog;
use App\Models\transactions;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\banks;
use App\Models\branch;
use App\Models\wb_account_details;
use App\Models\user_wallet;

class RegisterController extends Controller
{

    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(Request $request)
    {

        $referer = $request->has('referer') ? $request->referer : "";

        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'page_menu'        => 'wired_banking_onboarding_setup_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'referer'          => $referer,
            'currency'         => currencies::all(),
            'country'          => Contries::all(),
            'cooperative'      => company_types::all(),

        ];

        return view('pages.authentication.auth_register')->with($data);
    }
    protected function validator(Request $data)
    {
        // Validator::extend('without_spaces', function($attr, $value){
        //     return preg_match('/^\S*$/u', $value);});
        return Validator::make($data, [
            'firstname' => ['required', 'string', 'max:100'],
            'lastname'  => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:30', 'without_spaces'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    // /**
    //  * Create a new user instance after a valid registration.
    //  *
    //  * @param  array  $data
    //  * @return \App\User
    //  */
    // protected function create(Request $request)
    // { //regex:/^\S*$/u
    //                                                   //  $this->validator($request->all())->validate();

    //     \logInfo($request->all(), "Company Registration Request");

    //     Validator::extend('without_spaces', function ($attr, $value) {
    //         return preg_match('/^\S*$/u', $value);
    //     });
    //     $validator = Validator::make($request->all(), [
    //         'firstname'              => 'required|max:100',
    //         'lastname'               => 'required|max:100',
    //         'username'               => 'required|unique:users|without_spaces|max:30',
    //         'email'                  => 'required|email|unique:users|max:191',
    //         'password'               => 'required',
    //         'companyname'            => 'required|max:191',
    //         'companyshort'           => 'required|max:12',
    //         'countryphone'           => 'required|max:15',
    //         'formatted_phone'        => 'required|max:15',
    //         'companycooperativetype' => 'required',
    //         'companycurrency'        => 'required',
    //         'companycountry'         => 'required',
    //         'companyaddress'         => 'required',
    //     ], [
    //         'username.without_spaces' => 'Whitespace not allowed.',
    //         'email.without_spaces'    => 'Whitespace not allowed.',
    //     ]);
    //     if ($validator->passes()) {

    //         $splitCompanyName = \explode(" ", $request->companyname);

    //         if (count($splitCompanyName) > 1) {
    //             $checkCompanyName = Company::where('company_name', 'LIKE', $splitCompanyName[0] . " " . $splitCompanyName[1] . '%')->first();
    //         } else {
    //             $checkCompanyName = Company::where('company_name', 'LIKE', $request->companyname . '%')->first();
    //         }

    //         $validationErrors = [];

    //         if ($checkCompanyName) {
    //             array_push($validationErrors, "Company already exists");
    //         }

    //         if ($request->companycountry == 1) {
    //             if (! $request->has('bvn')) {
    //                 array_push($validationErrors, "BVN must be filled out");
    //             } else {
    //                 $bvnCheck = User::where('bvn', $request->bvn)->first();

    //                 if ($bvnCheck) {
    //                     array_push($validationErrors, "BVN already exists");
    //                 }
    //             }
    //         }

    //         if (\count($validationErrors) > 0) {
    //             return \response()->json(['status' => 2, 'message' => "Validation Errors", 'data' => $validationErrors]);
    //         }

    //         $currency = currencies::find($request->companycurrency);

    //         $virtualAccount = new VirtualAccountType();

    //         $virtualAccount->bankCode         = "090267";
    //         $virtualAccount->currencyCode     = $currency->code;
    //         $virtualAccount->firstName        = "";
    //         $virtualAccount->companyShortName = $request->companyshort;
    //         $virtualAccount->companyName      = $request->companyname;
    //         $virtualAccount->lastName         = $request->companyname;
    //         $virtualAccount->accountType      = $request->account_category;
    //         $virtualAccount->email            = $request->email;
    //         $virtualAccount->phone            = $request->formatted_phone;
    //         $virtualAccount->accountReference = $companyReference = generateAccountReference();
    //         // $virtualAccount->dob = $request->dob;
    //         // $virtualAccount->gender = $request->gender;
    //         $virtualAccount->bvn                          = $request->bvn;
    //         $virtualAccount->address                      = $request->companyaddress;
    //         $virtualAccount->companyAccountCreationStatus = 1;

    //         $createAccountService = new AccountCreationService();

    //         $createCompanyAccount = $createAccountService->createVirtualAccount($virtualAccount);

    //         // $wovenController = new WovenController();

    //         // $customerReference = generateAccountReference();
    //         // $createCompanyAccount = $wovenController->createVirtualAccount($request->companyname, $request->email, $request->formatted_phone, $customerReference);

    //         $virtualAccount->firstName        = $request->firstname;
    //         $virtualAccount->lastName         = $request->lastname;
    //         $virtualAccount->accountReference = $customerReference = generateAccountReference();
    //         $createAdminAccount               = $createAccountService->createVirtualAccount($virtualAccount);

    //         // $companyReference = generateAccountReference();
    //         // $createAdminAccount = $wovenController->createVirtualAccount("$request->firstname $request->lastname", $request->email, $request->formatted_phone, $companyReference);

    //         \logInfo($createAdminAccount, "Create Account Log");

    //         if ($createCompanyAccount["status"] && $createAdminAccount["status"]) {

    //             $company               = new Company;
    //             $company->company_name = $request->companyname;
    //             $company->currency_id  = $request->companycurrency;
    //             $company->address      = $request->companyaddress;
    //             $company->cac_no       = $request->companycac_no;
    //             $company->license_no   = $request->companylicense_no;
    //             $company->countries_id = $request->companycountry;
    //             $company->status_id    = 10;
    //             $loginPin              = rand(1231, 3879);
    //             while (Company::where('loginCode', $loginPin)->count() > 0) {
    //                 $loginPin = rand(1241, 6879);
    //             }
    //             $company->loginCode         = $loginPin;
    //             $company->company_reference = $companyReference;
    //             $company->short_name        = $request->companyshort;
    //             $company->referer_phone     = $request->referer_phone;

    //             $company->transaction_pin = rand(1231, 7879);

    //             $company_logo  = $request->file('logo');
    //             $company->logo = '';
    //             $company->save();
    //             $logo_Path = '';
    //             if ($company_logo) {
    //                 $logoName = $company_logo->getClientOriginalName();

    //                 $logo_Path     = $company_logo->storeAs("storage/company/profile/$company->id", $logoName);
    //                 $company->logo = $logo_Path;
    //                 $company->save();
    //             }

    //             $companyWallet = new company_wallet();

    //             $companyWallet->company_id      = $company->id;
    //             $companyWallet->balance         = 0.0;
    //             $companyWallet->sms             = 0;
    //             $companyWallet->ussd            = 0;
    //             $companyWallet->vaccount        = $request->companycountry != 1 ? $createCompanyAccount["data"]["virtual_account"] . "_" : $createCompanyAccount["data"]["virtual_account"];
    //             $companyWallet->refaccount      = $createCompanyAccount["data"]["account_reference"];
    //             $companyWallet->bank_id         = 126;
    //             $companyWallet->sms_type_id     = 1;
    //             $companyWallet->withdraw_charge = 0.0;
    //             $companyWallet->sms_charge      = 0.0;
    //             $companyWallet->ussd_charge     = 0.0;

    //             $companyWallet->save();

    //             $this->createMaintenanceAccount($company); 

    //             $verificationHash = \generateAPIKey(12);

    //             $user = User::create([
    //                 'first_name'             => $request->firstname,
    //                 'last_name'              => $request->lastname,
    //                 'company_id'             => $company->id,
    //                 'email'                  => $request->email,
    //                 'bvn'                    => $request->bvn,
    //                 'phone'                  => $request->formatted_phone,
    //                 'virtual_account'        => $request->companycountry != 1 ? $createAdminAccount["data"]["virtual_account"] . "_" : $createAdminAccount["data"]["virtual_account"],
    //                 'account_reference'      => $createAdminAccount["data"]["account_reference"],
    //                 'profile_pic'            => $logo_Path,
    //                 'verification_status_id' => 3,
    //                 'username'               => $request->username,
    //                 'uuid'                   => $customerReference,
    //                 'user_type_id'           => 2,
    //                 'sms'                    => 1,
    //                 'whatsapp'               => 1,
    //                 'verification_hash'      => $verificationHash,
    //                 'status_id'              => 1,
    //                 'password'               => Hash::make($request->password),
    //             ]);

    //             $company->created_by = $user->id;
    //             $company->save();

    //             $userWallet = StaffWallet::create([
    //                 "balance"    => 0.0,
    //                 "user_id"    => $user->id,
    //                 "created_by" => $user->id,
    //             ]);
    //             $initroleid = Staffrole::create([
    //                 'name'       => 'Company Owner',
    //                 'company_id' => $company->id,
    //                 'created_by' => $user->id,
    //             ]);
    //             Staffrole::create([
    //                 'name'       => 'Staff',
    //                 'company_id' => $company->id,
    //                 'created_by' => $user->id,
    //             ], [
    //                 'name'       => 'Teller',
    //                 'company_id' => $company->id,
    //                 'created_by' => $user->id,
    //             ]);
    //             if ($user && $userWallet) {
    //                 $user->assignRole([4]);
    //                 Custom_role_as_permission::create([
    //                     'staffrole_id'  => $initroleid->id,
    //                     'permission_id' => 1,
    //                 ]);
    //                 StaffpermissionTable::create([
    //                     'staffrole_id' => $initroleid->id,
    //                     'user_id'      => $user->id,
    //                     'created_by'   => $user->id,
    //                 ]);

    //                 $plan            = plans::find(1);
    //                 $endpayment      = add_day_to_date($plan->duration, date('Y-m-d'));
    //                 $transactionId   = generate_txnID();
    //                 $ledger_cr       = null;
    //                 $ledger_dr       = null;
    //                 $ledger_Settings = get_CR_DR_TXN_Type_Ledger(18, $user->company_id);
    //                 if ($ledger_Settings) {
    //                     $ledger_cr = $ledger_Settings->ledger_cr;
    //                     $ledger_dr = $ledger_Settings->ledger_dr;
    //                 }

    //                 $transaction = transactions::create([
    //                     "balance_before"         => $company->wallet->balance,
    //                     "amount"                 => 0,
    //                     "description"            => "Wiredbanking Subsciption Trial",
    //                     "transaction_reference"  => $transactionId,
    //                     "transaction_type_id"    => 18,
    //                     "credit_debit_type_id"   => 2,
    //                     "transaction_remarks_id" => 2,
    //                     "balance_after"          => $company->wallet->balance,
    //                     "created_date"           => date('Y-m-d'),
    //                     "created_by"             => $user->id,
    //                     "ledger_dr"              => $ledger_dr,
    //                     "ledger_cr"              => $ledger_cr,
    //                 ]);
    //                 sublog::create([
    //                     'company_id'     => $user->company_id,
    //                     'transaction_id' => $transaction->id,
    //                     'plan_id'        => $plan->id,
    //                     'quantity'       => $plan->duration,
    //                     'start_date'     => date('Y-m-d'),
    //                     'end_date'       => $endpayment,

    //                 ]);
    //                 sub::create([
    //                     'company_id'     => $user->company_id,
    //                     'transaction_id' => $transaction->id,
    //                     'plan_id'        => 1,
    //                     'quantity'       => 1,
    //                     'start_date'     => date('Y-m-d'),
    //                     'end_date'       => $endpayment,
    //                     'status'         => 1,
    //                 ]);

    //                 $url = url("verify-account/" . $verificationHash);

    //                 \logInfo($url, "url log");

    //                 $myMessage = "Account Name: " . $user->company->company_name . "
    //                 Account Number: " . $user->company->wallet->vaccount . "<br>Bank: " . $user->company->wallet->bank->bank_name . " <br> Company Setup pin: " . $user->company->transaction_pin . " Login Pin: $loginPin<br>";

    //                 $msg  = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
    //                 $msg .= '<a href="' . $url . '">Verify Account</a>';

    //                 // $sendSms = \sendSMS($user->id, $msg, true, false, "Account Creation Success", '', 0, false, 3, []);

    //                 $sendSms = sendEmail("Account Creation Success", $msg, $user);
    //                 $sendSms = sendEmail("Welcome Message", $myMessage, $user);

    //                 \logInfo($sendSms, "Send SMS log");

    //                 $this->createMaintenanceAccount($company, $user);

    //                 // event(new Registered($user));

    //                 // $plotsms =  sendSMS($user->id, "$msg", true, true, 'Company Registration', '', 3);

    //                 return response(json_encode(["status" => 1, "message" => "A Verification email has been sent to your email account. Click on the link in the email to verify your account"])); //redirect("/login")->with('success', );
    //             } else {
    //                 // return back()->with('fail', "An error occured!");
    //                 return response(json_encode(["status" => 0, "message" => "An error occured!"]));
    //             }
    //         } else {
    //             // return back()->with('fail', "Failed to Create Account.. Please try again");
    //             return response(json_encode(["status" => 0, "message" => "Failed to Create Account.. Please try again"]));
    //         }
    //     } else {
    //         $errors = '';
    //         foreach ($validator->messages()->getMessages() as $field_name => $messages) {
    //             // Go through each message for this field.
    //             foreach ($messages as $message) {
    //                 $errors .= $message;
    //             }
    //         }

    //         return response(json_encode(["status" => 0, "message" => $errors]));
    //     }
    // }


protected function create(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | 1. VALIDATE REGISTRATION REQUEST
    |--------------------------------------------------------------------------
    */

    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|min:2|max:50',
        'last_name'  => 'required|string|min:2|max:50',
        'phone'      => 'required|string|max:20',
        'email'      => 'required|email|max:255',
        'dob'        => 'required|date',
        'username'   => 'required|string|max:255',
        'companyid'  => 'required|integer',
        'pin'        => 'required|string|min:4|max:6',
        'password'   => 'required|string|min:6',

        'bvn'           => 'nullable|string',
        'nin'           => 'nullable|string',
        'gender'        => 'nullable|string',
        'address'       => 'nullable|string',
        'identityId'    => 'nullable|string',
        '_id'           => 'nullable|string',
        'id'            => 'nullable|string',
        'identityNumber'=> 'nullable|string',
        'otp'           => 'nullable|string',
        'selected_product' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
            'data'    => null,
        ], 422);
    }

    $email    = strtolower(trim($request->email));
    $username = trim($request->username);
    $phone    = trim($request->phone);

    /*
    |--------------------------------------------------------------------------
    | 2. LOCAL KOLPAY CONFIGURATION
    |--------------------------------------------------------------------------
    |
    | These are IDs from the local Corebanking database.
    | They must NOT be confused with Finova's database IDs.
    |
    */

    $localCompanyId = (int) $request->companyid;

    $localBankId          = 126;
    $localBranchId        = 182;
    $localAccountOfficerId = 1199;

    // Finova company under which every customer will be registered.
    $finovaCompanyId = '111';

    /*
    |--------------------------------------------------------------------------
    | 3. CHECK LOCAL COMPANY
    |--------------------------------------------------------------------------
    */

    $localCompany = Company::find($localCompanyId);

    if (!$localCompany) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid company provided.',
            'data'    => null,
        ], 400);
    }

    if ($localCompany->status_id != 1) {
        return response()->json([
            'status'  => false,
            'message' => 'Company is currently inactive.',
            'data'    => null,
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. CHECK LOCAL BANK, BRANCH AND ACCOUNT OFFICER
    |--------------------------------------------------------------------------
    */

    $localBank = banks::find($localBankId);
    $localBranch = branch::find($localBranchId);
    $accountOfficer = User::find($localAccountOfficerId);

    if (!$localBank) {
        return response()->json([
            'status'  => false,
            'message' => 'Configured local bank ID 126 was not found.',
            'data'    => null,
        ], 500);
    }

    if (!$localBranch) {
        return response()->json([
            'status'  => false,
            'message' => 'Configured local branch ID 182 was not found.',
            'data'    => null,
        ], 500);
    }

    if (!$accountOfficer) {
        return response()->json([
            'status'  => false,
            'message' => 'Configured account officer ID 1199 was not found.',
            'data'    => null,
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. RESOLVE LOCAL SAVINGS PRODUCT
    |--------------------------------------------------------------------------
    */

    $companySavingsProducts = $localCompany->savingsProducts;

    if ($request->filled('selected_product')) {

        $selectedProduct = $companySavingsProducts->firstWhere(
            'id',
            (int) $request->selected_product
        );

        if (!$selectedProduct) {
            return response()->json([
                'status'  => false,
                'message' => 'Selected savings product does not belong to this company.',
                'data'    => null,
            ], 400);
        }

    } else {

        $selectedProduct = $companySavingsProducts->first();

        if (!$selectedProduct) {
            return response()->json([
                'status'  => false,
                'message' => 'No savings product found for this company.',
                'data'    => null,
            ], 400);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. CHECK FOR EXISTING LOCAL CUSTOMER
    |--------------------------------------------------------------------------
    */

    $existingUser = User::where('email', $email)
        ->orWhere('username', $username)
        ->orWhere('phone', $phone)
        ->first();

    if ($existingUser) {

        $message = 'User already exists.';

        if ($existingUser->email === $email) {
            $message = 'Email already exists.';
        } elseif ($existingUser->username === $username) {
            $message = 'Username already exists.';
        } elseif ($existingUser->phone === $phone) {
            $message = 'Phone number already exists.';
        }

        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null,
        ], 409);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. PREPARE FINOVA REGISTRATION PAYLOAD
    |--------------------------------------------------------------------------
    |
    | Local-only fields such as companyid, selected_product and branch
    | are not forwarded as Finova database IDs.
    |
    | Finova company ID is always 111.
    | Account type is always virtual.
    |
    */

    $payload = $request->only([
        'first_name',
        'last_name',
        'phone',
        'email',
        'dob',
        'username',
        'pin',
        'password',
        'gender',
        'address',
        'bvn',
        'nin',
        'identityId',
        '_id',
        'id',
        'identityNumber',
        'otp',
    ]);

    $payload['company'] = $finovaCompanyId;
    $payload['account_type'] = 'virtual';

    /*
    |--------------------------------------------------------------------------
    | 8. CALL FINOVA REGISTRATION API
    |--------------------------------------------------------------------------
    |
    | services.finova.base_url should contain the Finova API base URL
    | INCLUDING /api/v1.
    |
    | Example:
    | FINOVA_BASE_URL=https://core.finova.ng/api/v1
    |
    */

    try {

        $baseUrl = rtrim(
            config('services.finova.base_url'),
            '/'
        );

        if (empty($baseUrl)) {
            return response()->json([
                'status'  => false,
                'message' => 'Finova API base URL is not configured.',
                'data'    => null,
            ], 500);
        }

        $finovaResponse = Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(90)
            ->post(
                $baseUrl . '/users/register',
                $payload
            );

        $finovaData = $finovaResponse->json();

        \logInfo([
            'http_status' => $finovaResponse->status(),
            'response_status' => is_array($finovaData)
                ? ($finovaData['status'] ?? null)
                : null,
            'response_message' => is_array($finovaData)
                ? ($finovaData['message'] ?? null)
                : null,
            'response_has_data' => is_array($finovaData)
                && isset($finovaData['data']),
            'local_company_id' => $localCompanyId,
            'finova_company_id' => $finovaCompanyId,
        ], 'FINOVA REGISTRATION RESPONSE');

        /*
        |--------------------------------------------------------------------------
        | 9. HANDLE FINOVA REGISTRATION FAILURE
        |--------------------------------------------------------------------------
        */

        if (
            !$finovaResponse->successful() ||
            !is_array($finovaData) ||
            ($finovaData['status'] ?? false) !== true
        ) {
            return response()->json(
                is_array($finovaData)
                    ? $finovaData
                    : [
                        'status'  => false,
                        'message' => 'Finova registration failed.',
                        'data'    => null,
                    ],
                $finovaResponse->status() >= 400
                    ? $finovaResponse->status()
                    : 422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. EXTRACT FINOVA ACCOUNT DETAILS
        |--------------------------------------------------------------------------
        */

        $finovaAccountData = $finovaData['data'] ?? [];

        $accountNumber = $finovaAccountData['account_number'] ?? null;
        $userReference = $finovaAccountData['user_reference'] ?? null;

        if (empty($accountNumber) || empty($userReference)) {

            \logInfo([
                'local_company_id' => $localCompanyId,
                'finova_company_id' => $finovaCompanyId,
                'account_number_received' => !empty($accountNumber),
                'user_reference_received' => !empty($userReference),
            ], 'FINOVA REGISTRATION - REQUIRED ACCOUNT DATA MISSING');

            return response()->json([
                'status'  => false,
                'message' => 'Finova registration succeeded, but the required account details were not returned. Please contact support before attempting registration again.',
                'data'    => [
                    'user_reference' => $userReference,
                    'account_number' => $accountNumber,
                ],
            ], 502);
        }

        /*
        |--------------------------------------------------------------------------
        | 11. CREATE LOCAL CUSTOMER RECORDS
        |--------------------------------------------------------------------------
        |
        | Finova has already created the customer's virtual account.
        |
        | We now create:
        |   1. User
        |   2. wb_account_details
        |   3. user_wallet
        |
        | All three local records are created inside one transaction.
        |
        */

        try {

            $user = DB::transaction(function () use (
                $request,
                $email,
                $username,
                $phone,
                $localCompany,
                $localCompanyId,
                $localBankId,
                $localBranchId,
                $localAccountOfficerId,
                $localBranch,
                $selectedProduct,
                $accountNumber,
                $userReference
            ) {

                /*
                |--------------------------------------------------------------------------
                | CREATE LOCAL USER
                |--------------------------------------------------------------------------
                */

                $registrationStatus = (
                    $localCompany->setting->account_approval ?? 1
                ) == 0 ? 7 : 1;

                $user = User::create([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),

                    'name' => trim(
                        $request->first_name . ' ' . $request->last_name
                    ),

                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,

                    'company_id' => $localCompanyId,

                    'email' => $email,
                    'phone' => $phone,
                    'username' => $username,

                    'dob' => $request->dob,
                    'gender' => $request->gender,
                    'address' => $request->address,

                    'bvn' => $request->bvn,
                    'nin' => $request->nin,

                    // Local login password.
                    'password' => Hash::make($request->password),

                    // Finova is authoritative for the virtual account.
                    'virtual_account' => $accountNumber,
                    'account_reference' => $userReference,

                    // Local KoloPay database IDs.
                    'bank_id' => $localBankId,
                    'branch_id' => $localBranchId,
                    'account_officer_id' => $localAccountOfficerId,

                    'profile_pic' => 'https://safe.wbalite.com/passports/default_image.jpg',

                    'status_id' => $registrationStatus,
                    'kyc_level_id' => $request->kyc_level_id ?? 1,
                    'user_type_id' => 1,

                    // Finova handles the external registration process.
                    'pin' => null,
                    'otp' => null,
                    'otp_expiration_date' => null,
                    'phone_verification_status_id' => null,
                    'access_token' => null,
                ]);

                if (!$user) {
                    throw new \RuntimeException(
                        'Failed to create local customer record.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE LOCAL BANK ACCOUNT DETAILS
                |--------------------------------------------------------------------------
                */

                $accountDetails = wb_account_details::create([
                    'user_id' => $user->id,
                    'account_product_id' => $selectedProduct->id,

                    // Local bank ID for Kuda.
                    'bankid' => $localBankId,

                    // Account number generated by Finova.
                    'virtual_accnum' => $accountNumber,

                    // Account reference generated by Finova.
                    'acc_reference' => $userReference,

                    'has_atm_card' => 0,

                    'currency_id' => $localBranch->currency_id ?? 1,
                ]);

                if (!$accountDetails) {
                    throw new \RuntimeException(
                        'Failed to create local account details.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE LOCAL USER WALLET
                |--------------------------------------------------------------------------
                */

                $registrationFee = $localCompany->setting->registration_fee ?? 0;

                $wallet = user_wallet::create([
                    'wb_account_detail_id' => $accountDetails->id,

                    // Preserve the original registration-fee logic.
                    'balance' => (0 - $registrationFee),
                ]);

                if (!$wallet) {
                    throw new \RuntimeException(
                        'Failed to create local customer wallet.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ASSIGN DEFAULT CUSTOMER ROLE
                |--------------------------------------------------------------------------
                */

                $user->assignRole([4]);

                return $user;
            });

        } catch (\Throwable $e) {

            report($e);

            \logInfo([
                'local_company_id' => $localCompanyId,
                'finova_company_id' => $finovaCompanyId,
                'finova_user_reference' => $userReference,
                'finova_account_number' => $accountNumber,
                'error' => $e->getMessage(),
            ], 'FINOVA REGISTRATION - LOCAL SYNCHRONIZATION FAILED');

            /*
             * IMPORTANT:
             * Finova has already created the account.
             * Do not automatically retry Finova registration here.
             */

            return response()->json([
                'status'  => false,
                'message' => 'Your Finova account was created successfully, but we could not synchronize your local customer profile. Please contact support. Do not attempt registration again.',
                'data'    => [
                    'user_reference' => $userReference,
                    'account_number' => $accountNumber,
                ],
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | 12. REGISTRATION SUCCESS RESPONSE
        |--------------------------------------------------------------------------
        */

        \logInfo([
            'local_user_id' => $user->id,
            'local_company_id' => $localCompanyId,
            'finova_company_id' => $finovaCompanyId,
            'user_reference' => $userReference,
            'account_number' => $accountNumber,
            'bank_id' => $localBankId,
            'branch_id' => $localBranchId,
            'account_officer_id' => $localAccountOfficerId,
            'status' => 'successful',
        ], 'FINOVA REGISTRATION - LOCAL SYNCHRONIZATION SUCCESSFUL');

        return response()->json([
            'status'  => true,
            'message' => 'Registration Successful',
            'data'    => [
                'name' => $user->name,
                'user_reference' => $userReference,
                'account_number' => $accountNumber,

                'bank' => $finovaAccountData['bank']
                    ?? $localBank->bank_name,

                'phone_number' => $finovaAccountData['phone_number']
                    ?? $phone,

                'otp_code' => $finovaAccountData['otp_code'] ?? '',
            ],
        ], 200);

    } catch (\Illuminate\Http\Client\ConnectionException $e) {

        report($e);

        \logInfo([
            'local_company_id' => $localCompanyId,
            'finova_company_id' => $finovaCompanyId,
            'error' => $e->getMessage(),
        ], 'FINOVA REGISTRATION - CONNECTION ERROR');

        return response()->json([
            'status'  => false,
            'message' => 'Unable to connect to Finova. Please try again later.',
            'data'    => null,
        ], 503);

    } catch (\Throwable $e) {

        report($e);

        \logInfo([
            'local_company_id' => $localCompanyId,
            'error' => $e->getMessage(),
        ], 'FINOVA REGISTRATION - UNEXPECTED ERROR');

        return response()->json([
            'status'  => false,
            'message' => 'An unexpected error occurred during registration.',
            'data'    => null,
        ], 500);
    }
}


    public function verifyAccount($verification_hash)
    {
        $verification = User::where('verification_hash', $verification_hash)->first();

        \logInfo($verification);
        if ($verification) {

            if ($verification->email_verified_at == null) {
                if (Carbon::now()->subMinute(5) > $verification->updated_at) {

                    $data = [
                        'category_name'    => 'auth',
                        'page_name'        => 'auth_default',
                        'has_scrollspy'    => 0,
                        'scrollspy_offset' => '',
                        'page_menu'        => '',
                        'alt_menu'         => '',
                        'usertype'         => $verification->user_type_id,

                        'isReferenceValid' => true,
                        'status'           => 'expired',
                        'resendUrl'        => url("resend-email/$verification_hash"),
                        'title'            => "Link Expired",
                        'description'      => "This link has expired.<br>Kindly Click the button below to resend activation email",
                    ];
                } else {

                    $verification->email_verified_at = Carbon::now();
                    $verification->save();

                    $data = [
                        'category_name'    => 'auth',
                        'page_name'        => 'auth_default',
                        'has_scrollspy'    => 0,
                        'scrollspy_offset' => '',
                        'page_menu'        => '',
                        'usertype'         => $verification->user_type_id,

                        'alt_menu'         => '',
                        'status'           => 'successful',
                        'isReferenceValid' => true,
                        'title'            => "Account Activation Successful",
                        'description'      => "Your account has been activated successfully.<br>Kindly Login to Complete your profile",
                    ];
                }
            } else {
                $data = [
                    'category_name'    => 'auth',
                    'page_name'        => 'auth_default',
                    'has_scrollspy'    => 0,
                    'scrollspy_offset' => '',
                    'page_menu'        => '',
                    'alt_menu'         => '',
                    'usertype'         => $verification->user_type_id,

                    'status'           => 'successful',
                    'isReferenceValid' => true,
                    'title'            => "Account Activation Successful",
                    'description'      => "Your account has been activated successfully.<br>Kindly Login to Complete your profile",
                ];
            }
        } else {
            $data = [
                'category_name'    => 'auth',
                'page_name'        => 'auth_default',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'page_menu'        => '',
                'alt_menu'         => '',
                'status'           => 'failed',
                'usertype'         => 3,

                'isReferenceValid' => true,
                'title'            => "Invalid Link",
                'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to validate your account",
            ];
        }

        return view('auth.show_verification_status')->with($data);
    }

    public function resendEmil($verification_hash)
    {
        $verification = User::where('verification_hash', $verification_hash)->first();

        if ($verification) {

            $verificationHash = \generateAPIKey(12);

            $verification->updated_at        = Carbon::now();
            $verification->verification_hash = $verificationHash;
            $verification->save();

            $url = url("verify-account/" . $verificationHash);

            $msg  = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
            $msg .= '<a href="' . $url . '">Verify Account</a>';

            $sendSms = sendEmail("Account Activate Email", $msg, $verification);

            Session::flash("success", "Account Activation email has been sent to your email successfully");

            $data = [
                'category_name'    => 'auth',
                'page_name'        => 'auth_default',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'page_menu'        => '',
                'alt_menu'         => '',
                'isReferenceValid' => true,
                'status'           => 'expired',
                'usertype'         => $verification->user_type_id,
                'resendUrl'        => url("resend-email/$verificationHash"),
                'title'            => "Link Expired",
                'description'      => "This link has expired.<br>Kindly Click the button below to resend activation email",
            ];
        } else {
            $data = [
                'category_name'    => 'auth',
                'page_name'        => 'auth_default',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'page_menu'        => '',
                'alt_menu'         => '',
                'usertype'         => $verification->user_type_id,
                'status'           => 'success',
                'isReferenceValid' => true,
                'title'            => "Invalid Link",
                'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to validate your account",
            ];
        }

        return view('auth.show_verification_status')->with($data);
    }

    public function mainResendEmil(Request $request)
    {

        $userAccount = \auth()->user();
        \logInfo("skjhgfghj", "Resend Email log");
        \logInfo($userAccount, "User accounts log");

        if (\auth()->user()->email_verified_at != null) {
            return redirect('/phone-verification');
        }

        $verificationHash = \generateAPIKey(12);

        $userAccount->updated_at        = Carbon::now();
        $userAccount->verification_hash = $verificationHash;
        $userAccount->save();

        $url = url("verify-account/" . $verificationHash);

        $msg  = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
        $msg .= '<a href="' . $url . '">Verify Account</a>';

        $sendSms = sendEmail("Account Activate Email", $msg, $userAccount);

        Session::flash("success", "Activation email has been sent to your email successfully");

        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'page_menu'        => '',
            'alt_menu'         => '',
            'status'           => 'failed',
            'usertype'         => $userAccount->user_type_id,

            'isReferenceValid' => true,
            'title'            => "Invalid Link",
            'description'      => "The link provided is not a valid link.<br>Kindly click on the link sent to your email to validate your account",
        ];
        return view('auth.verify')->with($data);
    }

    protected function validateEmailPhone(Request $request)
    { //regex:/^\S*$/u

        \logInfo($request->all(), "Validate Email phone");

        //  $this->validator($request->all())->validate();
        Validator::extend('without_spaces', function ($attr, $value) {
            return preg_match('/^\S*$/u', $value);
        });
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|max:100',
            'lastname'  => 'required|max:100',
            'username'  => 'required|unique:users|without_spaces|max:30',
            'email'     => 'required|email|unique:users|max:191',
            'password'  => 'required',
            'phone'     => 'required|unique:users',
        ], [
            'username.without_spaces' => 'Whitespace not allowed.',
            'email.without_spaces'    => 'Whitespace not allowed.',
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => 0, "message" => $validator->errors()->all()[0], "data" => $validator->errors()->all()]);
        } else {
            return response()->json(["status" => 1, "message" => "Verified Successfully"]);
        }
    }

    public function createMaintenanceAccount($company)
{
    try {
        $company = Company::with('allStaff', 'wallet')->find($company->id);

        if (! $company) {
            \logInfo([
                'error' => 'Company not found',
            ], 'maintenance account');

            return false;
        }

        $wallet = $company->wallet;

        if (! $wallet) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'Company wallet not found',
            ], 'maintenance account');

            return false;
        }

        // Don't create another subscription account if one already exists.
        if (! empty($wallet->subscription_vaccount)) {
            \logInfo([
                'company_id'           => $company->id,
                'subscription_vaccount' => $wallet->subscription_vaccount,
                'message'              => 'Subscription account already exists',
            ], 'maintenance account');

            return true;
        }

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'No admin found',
            ], 'maintenance account');

            return false;
        }

        $requestReference  = generateAccountReference();
        $trackingReference = generateAccountReference();

        $kudaService = app(KudaController::class);

        $response = $kudaService->createMaintenanceAccount(
            $requestReference,
            $admin->email,
            $admin->phone ?? $admin->email,
            $company->short_name,
            '',
            $trackingReference
        );

        if (! ($response['status'] ?? false)) {
            \logInfo([
                'company_id' => $company->id,
                'response'   => $response,
            ], 'maintenance account failed');

            return false;
        }

        $accountNumber = $response['data']['account_number'] ?? null;

        if (! $accountNumber) {
            \logInfo([
                'company_id' => $company->id,
                'response'   => $response,
                'error'      => 'Provider did not return account number',
            ], 'maintenance account failed');

            return false;
        }

        $wallet->subscription_vaccount = $accountNumber;
        $wallet->save();

        \logInfo([
            'company_id'           => $company->id,
            'subscription_vaccount' => $accountNumber,
        ], 'maintenance account created successfully');

        return true;

    } catch (\Throwable $e) {
        \logInfo([
            'company_id' => $company->id ?? null,
            'error'      => $e->getMessage(),
            'line'       => $e->getLine(),
        ], 'maintenance account error');

        return false;
    }
}
}
