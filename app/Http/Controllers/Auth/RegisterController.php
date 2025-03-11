<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(Request $request)
    { //regex:/^\S*$/u
                                                      //  $this->validator($request->all())->validate();

        \logInfo($request->all(), "Company Registration Request");

        Validator::extend('without_spaces', function ($attr, $value) {
            return preg_match('/^\S*$/u', $value);
        });
        $validator = Validator::make($request->all(), [
            'firstname'              => 'required|max:100',
            'lastname'               => 'required|max:100',
            'username'               => 'required|unique:users|without_spaces|max:30',
            'email'                  => 'required|email|unique:users|max:191',
            'password'               => 'required',
            'companyname'            => 'required|max:191',
            'companyshort'           => 'required|max:12',
            'countryphone'           => 'required|max:15',
            'formatted_phone'        => 'required|max:15',
            'companycooperativetype' => 'required',
            'companycurrency'        => 'required',
            'companycountry'         => 'required',
            'companyaddress'         => 'required',
        ], [
            'username.without_spaces' => 'Whitespace not allowed.',
            'email.without_spaces'    => 'Whitespace not allowed.',
        ]);
        if ($validator->passes()) {

            $splitCompanyName = \explode(" ", $request->companyname);

            if (count($splitCompanyName) > 1) {
                $checkCompanyName = Company::where('company_name', 'LIKE', $splitCompanyName[0] . " " . $splitCompanyName[1] . '%')->first();
            } else {
                $checkCompanyName = Company::where('company_name', 'LIKE', $request->companyname . '%')->first();
            }

            $validationErrors = [];

            if ($checkCompanyName) {
                array_push($validationErrors, "Company already exists");
            }

            if ($request->companycountry == 1) {
                if (! $request->has('bvn')) {
                    array_push($validationErrors, "BVN must be filled out");
                } else {
                    $bvnCheck = User::where('bvn', $request->bvn)->first();

                    if ($bvnCheck) {
                        array_push($validationErrors, "BVN already exists");
                    }
                }
            }

            if (\count($validationErrors) > 0) {
                return \response()->json(['status' => 2, 'message' => "Validation Errors", 'data' => $validationErrors]);
            }

            $currency = currencies::find($request->companycurrency);

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode         = "090267";
            $virtualAccount->currencyCode     = $currency->code;
            $virtualAccount->firstName        = $request->companyname;
            $virtualAccount->companyShortName = $request->companyshort;
            $virtualAccount->companyName      = $request->companyname;
            $virtualAccount->lastName         = "";
            $virtualAccount->accountType      = $request->account_category;
            $virtualAccount->email            = $request->email;
            $virtualAccount->phone            = $request->formatted_phone;
            $virtualAccount->accountReference = $companyReference = generateAccountReference();
            // $virtualAccount->dob = $request->dob;
            // $virtualAccount->gender = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->companyaddress;
            $virtualAccount->companyAccountCreationStatus = 1;

            $createAccountService = new AccountCreationService();

            $createCompanyAccount = $createAccountService->createVirtualAccount($virtualAccount);

            // $wovenController = new WovenController();

            // $customerReference = generateAccountReference();
            // $createCompanyAccount = $wovenController->createVirtualAccount($request->companyname, $request->email, $request->formatted_phone, $customerReference);

            $virtualAccount->firstName        = $request->firstname;
            $virtualAccount->lastName         = $request->lastname;
            $virtualAccount->accountReference = $customerReference = generateAccountReference();
            $createAdminAccount               = $createAccountService->createVirtualAccount($virtualAccount);

            // $companyReference = generateAccountReference();
            // $createAdminAccount = $wovenController->createVirtualAccount("$request->firstname $request->lastname", $request->email, $request->formatted_phone, $companyReference);

            \logInfo($createAdminAccount, "Create Account Log");

            if ($createCompanyAccount["status"] && $createAdminAccount["status"]) {

                $company               = new Company;
                $company->company_name = $request->companyname;
                $company->currency_id  = $request->companycurrency;
                $company->address      = $request->companyaddress;
                $company->cac_no       = $request->companycac_no;
                $company->license_no   = $request->companylicense_no;
                $company->countries_id = $request->companycountry;
                $company->status_id    = 10;
                $loginPin              = rand(1231, 3879);
                while (Company::where('loginCode', $loginPin)->count() > 0) {
                    $loginPin = rand(1241, 6879);
                }
                $company->loginCode         = $loginPin;
                $company->company_reference = $companyReference;
                $company->short_name        = $request->companyshort;
                $company->referer_phone     = $request->referer_phone;

                $company->transaction_pin = rand(1231, 7879);

                $company_logo  = $request->file('logo');
                $company->logo = '';
                $company->save();
                $logo_Path = '';
                if ($company_logo) {
                    $logoName = $company_logo->getClientOriginalName();

                    $logo_Path     = $company_logo->storeAs("storage/company/profile/$company->id", $logoName);
                    $company->logo = $logo_Path;
                    $company->save();
                }

                $companyWallet = new company_wallet();

                $companyWallet->company_id      = $company->id;
                $companyWallet->balance         = 0.0;
                $companyWallet->sms             = 0;
                $companyWallet->ussd            = 0;
                $companyWallet->vaccount        = $request->companycountry != 1 ? $createCompanyAccount["data"]["virtual_account"] . "_" : $createCompanyAccount["data"]["virtual_account"];
                $companyWallet->refaccount      = $createCompanyAccount["data"]["account_reference"];
                $companyWallet->bank_id         = 231;
                $companyWallet->sms_type_id     = 1;
                $companyWallet->withdraw_charge = 0.0;
                $companyWallet->sms_charge      = 0.0;
                $companyWallet->ussd_charge     = 0.0;

                $companyWallet->save();

                $verificationHash = \generateAPIKey(12);

                $user = User::create([
                    'first_name'             => $request->firstname,
                    'last_name'              => $request->lastname,
                    'company_id'             => $company->id,
                    'email'                  => $request->email,
                    'bvn'                    => $request->bvn,
                    'phone'                  => $request->formatted_phone,
                    'virtual_account'        => $request->companycountry != 1 ? $createAdminAccount["data"]["virtual_account"] . "_" : $createAdminAccount["data"]["virtual_account"],
                    'account_reference'      => $createAdminAccount["data"]["account_reference"],
                    'profile_pic'            => $logo_Path,
                    'verification_status_id' => 3,
                    'username'               => $request->username,
                    'uuid'                   => $customerReference,
                    'user_type_id'           => 2,
                    'sms'                    => 1,
                    'whatsapp'               => 1,
                    'verification_hash'      => $verificationHash,
                    'status_id'              => 1,
                    'password'               => Hash::make($request->password),
                ]);

                $company->created_by = $user->id;
                $company->save();

                $userWallet = StaffWallet::create([
                    "balance"    => 0.0,
                    "user_id"    => $user->id,
                    "created_by" => $user->id,
                ]);
                $initroleid = Staffrole::create([
                    'name'       => 'Company Owner',
                    'company_id' => $company->id,
                    'created_by' => $user->id,
                ]);
                Staffrole::create([
                    'name'       => 'Staff',
                    'company_id' => $company->id,
                    'created_by' => $user->id,
                ], [
                    'name'       => 'Teller',
                    'company_id' => $company->id,
                    'created_by' => $user->id,
                ]);
                if ($user && $userWallet) {
                    $user->assignRole([4]);
                    Custom_role_as_permission::create([
                        'staffrole_id'  => $initroleid->id,
                        'permission_id' => 1,
                    ]);
                    StaffpermissionTable::create([
                        'staffrole_id' => $initroleid->id,
                        'user_id'      => $user->id,
                        'created_by'   => $user->id,
                    ]);

                    $plan            = plans::find(1);
                    $endpayment      = add_day_to_date($plan->duration, date('Y-m-d'));
                    $transactionId   = generate_txnID();
                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(18, $user->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }

                    $transaction = transactions::create([
                        "balance_before"         => $company->wallet->balance,
                        "amount"                 => 0,
                        "description"            => "Wiredbanking Subsciption Trial",
                        "transaction_reference"  => $transactionId,
                        "transaction_type_id"    => 18,
                        "credit_debit_type_id"   => 2,
                        "transaction_remarks_id" => 2,
                        "balance_after"          => $company->wallet->balance,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $user->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);
                    sublog::create([
                        'company_id'     => $user->company_id,
                        'transaction_id' => $transaction->id,
                        'plan_id'        => $plan->id,
                        'quantity'       => $plan->duration,
                        'start_date'     => date('Y-m-d'),
                        'end_date'       => $endpayment,

                    ]);
                    sub::create([
                        'company_id'     => $user->company_id,
                        'transaction_id' => $transaction->id,
                        'plan_id'        => 1,
                        'quantity'       => 1,
                        'start_date'     => date('Y-m-d'),
                        'end_date'       => $endpayment,
                        'status'         => 1,
                    ]);

                    $url = url("verify-account/" . $verificationHash);

                    \logInfo($url, "url log");

                    $myMessage = "Welcome to Wired Banking Lite, We are excited to have you around. Your Details are as follows <br>
                    Account Name: " . $user->company->company_name . "
                    Account Number: " . $user->company->wallet->vaccount . "<br>Bank: " . $user->company->wallet->bank->bank_name . " <br> Company Setup pin: " . $user->company->transaction_pin . " Login Pin: $loginPin<br>";

                    $msg = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
                    $msg .= '<a href="' . $url . '">Verify Account</a>';

                    // $sendSms = \sendSMS($user->id, $msg, true, false, "Account Creation Success", '', 0, false, 3, []);

                    $sendSms = sendEmail("Account Creation Success", $msg, $user);
                    $sendSms = sendEmail("Welcome Message", $myMessage, $user);

                    \logInfo($sendSms, "Send SMS log");

                    // event(new Registered($user));

                    // $plotsms =  sendSMS($user->id, "$msg", true, true, 'Company Registration', '', 3);

                    return response(json_encode(["status" => 1, "message" => "A Verification email has been sent to your email account. Click on the link in the email to verify your account"])); //redirect("/login")->with('success', );
                } else {
                    // return back()->with('fail', "An error occured!");
                    return response(json_encode(["status" => 0, "message" => "An error occured!"]));
                }
            } else {
                // return back()->with('fail', "Failed to Create Account.. Please try again");
                return response(json_encode(["status" => 0, "message" => "Failed to Create Account.. Please try again"]));
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }

            return response(json_encode(["status" => 0, "message" => $errors]));
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

            $msg = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
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

        $msg = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
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
}
