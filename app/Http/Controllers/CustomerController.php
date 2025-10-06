<?php
namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Http\Controllers\TransactionController;
use App\Imports\UsersImport;
use App\Mail\TransactionReport;
use App\Models\AddedLoanLog;
use App\Models\banks;
use App\Models\branch;
use App\Models\CardRequestLog;
use App\Models\CardType;
use App\Models\company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\Contries;
use App\Models\CooperateCustomer;
use App\Models\CryptoTransferTransaction;
use App\Models\CustomerOverdraft;
use App\Models\CustomerPermission;
use App\Models\CustomerRestriction;
use App\Models\CustomerRestrictionLog;
use App\Models\Customersavings;
use App\Models\DomAccount;
use App\Models\Esusu;
use App\Models\Esusulog;
use App\Models\EsusuTXN;
use App\Models\esusu_types;
use App\Models\guarantor;
use App\Models\Investmentbooking;
use App\Models\InvestmentProduct;
use App\Models\KycLevel;
use App\Models\loan;
use App\Models\LoanComment;
use App\Models\LoanCommentAttachment;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\loan_product;
use App\Models\logCustomerTransfer;
use App\Models\MainCustomerRestriction;
use App\Models\Mandate;
use App\Models\NextOfKinDetail;
use App\Models\Notification;
use App\Models\payLoanPenalty;
use App\Models\PendingEsusu;
use App\Models\PendingRepayment;
use App\Models\PendingSaving;
use App\Models\plans;
use App\Models\savings_product;
use App\Models\StaffWallet;
use App\Models\status;
use App\Models\sub;
use App\Models\tempuser;
use App\Models\Temp_Charges;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Image;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware("custom_permission:delete-customer|manage-customer|root|manage-saving|credit-customer|debit-customer|import-customer|customer-restriction|physical-card-management");
    }
    public function create_account_page()
    {
        if (! cando('create-customer|manage-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'forms',
            'page_name'        => 'wizards',
            'page_menu'        => 'create_customer',
            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => auth()->user()->company->branch,
        ];
        $account_types   = savings_product::where('company_id', auth()->user()->company_id)->get();
        $preferredBankId = auth()->user()->company->setting->preferred_bank_id;
        $selectedBank    = banks::find($preferredBankId);
        $banks           = banks::where('in_use', 1)->get();
        $countries       = Contries::all();

        return view('company.customer.create_account', [
            'account_types' => $account_types,
            'selectedbank'  => $selectedBank,
            'banks'         => $banks,
            'countries'     => $countries,
        ])->with($data);
    }
    public function pending_entry()
    {
        if (! cando('approve-customer|view-pending-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'pending_entry_statement',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.customer.pending_account')->with($data);
    }
    public function view_account_page()
    {
        if (! cando('manage-customer|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html5',
            'page_menu'        => 'company_branch_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.customer.account_details')->with($data);
    }

    public function CustomerTransfer()
    {
        if (! cando('approve-customer|view-pending-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'transfer_customer',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.customer.transfer.transfer')->with($data);
    }
    public function validate_details(Request $request)
    {

        $auth     = auth()->user();
        $email    = User::where(['email' => $request->email, 'company_id' => $auth->company_id])->count();
        $phone    = User::where(['phone' => $request->phone, 'company_id' => $auth->company_id])->count();
        $username = $request->username ? User::where(['username' => $request->username, 'company_id' => $auth->company_id])->count() : 0;
        return response()->json([
            'user'     => $username,
            'username' => $username,
            'email'    => $email,
            'phone'    => $phone,
            'auth'     => $auth->company_id,
        ]);
    }
    public function compressImage($source, $destination, $quality)
    {
        // Get image info
        $imgInfo = getimagesize($source);
        $mime    = $imgInfo['mime'];

        // Create a new image from file
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            default:
                $image = imagecreatefromjpeg($source);
        }

        // Save image
        imagejpeg($image, $destination, $quality);

        // Return compressed image
        return $destination;
    }

    public function convert_filesize($bytes, $decimals = 2)
    {
        $size   = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
    public function create_customer_account(Request $request)
    {
        if (! cando('create-customer|manage-customer|root')) {
            abort(403);
            return 0;
        }

        if (isset($request->cac_status_response) && ($request->has('cac_status_response') && $request->cac_status_response == "error")) {
            $messages = "There was error with CAC Registration Number";
            $datax    = [
                'message' => $messages,
                'subtext' => $messages,
                'status'  => 0,
            ];
            return response()->json($datax);
        }

        $request->branch = Crypt::decryptString($request->branch);
        $validator       = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:50|min:2',
            'last_name'    => 'required|string|max:50|min:2',
            'phone'        => 'required|string',
            'email'        => 'nullable|email|string',
            'dob'          => 'required',
            'branch'       => 'required',
            'officer'      => 'required',
            'account_id'   => 'required',
            'username'     => 'nullable|string',
            'bvn'          => 'required|numeric',
            'account_type' => 'required',
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
                'status'  => 0,
            ];
            return response()->json($datax);
        }
        if ($request->ref_link) {
            $company = Company::where('external_link_code', $request->ref_link)->first();
            if (! $company) {
                return response()->json(['status' => false, 'message' => 'Link Expired']);
            }
            $auth = User::where('company_id', $company->id)->first();
        } else {
            $auth = auth()->user();
        }
        $message = "";
        if ($request->email) {

            $emailCount = User::where(['email' => $request->email, 'company_id' => $auth->company_id])->count();
            if ($emailCount > 0) {
                $message = "Email already exists";
            }
        }
        $phoneCount = User::where(['phone' => $request->phone, 'company_id' => $auth->company_id])->count();
        if ($phoneCount > 0) {
            $message = "Phone already exists";
        }
        if ($request->username) {
            $usernameCount = User::where(['username' => $request->username, 'company_id' => $auth->company_id])->count();
            if ($usernameCount > 0) {
                $message = "Username already exists";
            }
        }
        $err = '';
        if ($message != "") {
            $ra = ["status" => 0, "message" => $message];
            return response()->json($ra);
        }
        $allcount            = User::where(['company_id' => $auth->company_id, 'user_type_id' => 1])->where('status_id', '!=', 12)->where('status_id', '!=', 14)->count();
        $max_count_customers = $auth->company->sub->plan->max_customers ?? $this->getMaxCuststomers($auth);
        if ($auth->company->sub->plan->max_customers < $allcount) {
            $datax = [
                'message' => "Maximum Customer Reached, Upgrade Plan!" . $max_count_customers,
                'subtext' => "Maximum Customer Reached, Upgrade Plan!",
                'status'  => 0,
            ];
            return response()->json($datax);
        }

        $company_setting  = $auth->company->setting;
        $regfee           = $company_setting->registration_fee;
        $ussd_control     = $company_setting->ussd_control;
        $account_status   = $company_setting->account_approval;
        $whatsapp_control = $company_setting->whatsapp_control;

        $saving                  = savings_product::where(['company_id' => $auth->company_id, 'id' => $request->account_id])->first();
        $virtual_acccount_number = rand(1000000000, 9999999999);
        $customer_reference      = rand(1000000000, 9999999999);
        if (isset($saving->code)) {
            if ($saving->code != null) {
                $length                  = strlen($saving->code);
                $virtual_acccount_number = substr_replace($virtual_acccount_number, $saving->code, 0, $length);
            }
        }
        $branchdata = branch::where('id', $request->branch)->first();

        if (isset($branchdata->code)) {
            if ($branchdata->code != null) {
                $branchCodeLength = strlen($branchdata->code);
                $branchCodeLength -= 1;
                $virtual_acccount_number = substr_replace($virtual_acccount_number, $branchdata->code, -$branchCodeLength);
            }
        }

        $officerid          = User::where('uuid', $request->officer)->first();
        $txnpin             = rand(1231, 7879);
        $newpsw             = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 8))), 1, 8);
        $sms                = $request->sms;
        $customer_reference = "Kae33344c2l99" . rand(120030, 999999);
        $bankId             = 231;
        if ($request->account_bank == "000013" && $request->account_type != "internal") {

            $debitCompany = debitCompanyWithCharge($auth->company_id, 10, 0);
            if ($debitCompany['status'] == false) {
                $ra = ["status" => false, "message" => $debitCompany['message']];
                return response()->json($ra, 409);
            }
        }
        if ($request->account_type == 'virtual') {

            $bank             = banks::where('bank_code', $request->account_bank)->first();
            $bankId           = $bank->id;
            $account_type_msg = 'Virtual';

            $companyIDs = [33];

            $companyIDsStatus = false;

            if (in_array($auth->company_id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            if ($companyIDsStatus) {
                $customSelectedBank = "090645";
            } else {
                $customSelectedBank = $request->account_bank;
            }

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode                     = $customSelectedBank;
            $virtualAccount->currencyCode                 = $auth->company->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $auth->company->short_name;
            $virtualAccount->companyName                  = $request->company_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->accountType                  = $request->account_category;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $auth->company->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            $customer_reference      = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];
        }

        $user = User::create([
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'uuid'               => uniqid(),
            'phone'              => $request->phone,
            'username'           => $request->username,
            'pin'                => $txnpin,
            'status_id'          => $account_status == 0 ? 7 : 1,
            'bvn'                => $request->bvn,
            'email'              => $request->email,
            'account_officer_id' => $officerid->id,
            'branch_id'          => $request->branch,
            'user_type_id'       => 1,
            'reg_date'           => isset($request->registration_date) ? $request->registration_date : Carbon::now(),
            'company_id'         => $auth->company_id,
            'created_by'         => $auth->id,
            'address'            => $request->address,
            'dob'                => $request->dob,
            'sms'                => $sms,
            'bank_id'            => $bankId,
            'country_id'         => $request->country,
            'nin'                => $request->national_id,
            'virtual_account'    => $virtual_acccount_number,
            'password'           => Hash::make($newpsw),
            'sex'                => $request->gender,
            'ussd_status_id'     => $ussd_control,
            'whatsapp_status_id' => $whatsapp_control,
            'account_reference'  => $customer_reference,
        ]);

        NextOfKinDetail::create([
            'user_id'     => $user->id,
            'nok_name'    => $request->nok_name,
            'nok_phone'   => $request->nok_phone,
            'nok_address' => $request->nok_address,
        ]);

        if ($request->account_category == 'coperate') {
            CooperateCustomer::create([
                'user_id'      => $user->id,
                'account_name' => $request->company_name,
                'tin'          => $request->tin_Number,
                'cac_reg_no'   => $request->reg_number,
                'created_by'   => $auth->id,
            ]);
        }
        $wb_account_details = wb_account_details::create([
            'user_id'            => $user->id,
            'account_product_id' => $request->account_id,
            'bankid'             => $bankId ?? 84,
            'virtual_accnum'     => $virtual_acccount_number,
            'acc_reference'      => $request->account_bank == "090267" || $request->account_bank == "000013" ? $customer_reference : $virtual_acccount_number,
            'has_atm_card'       => 0,
            'currency_id'        => $branchdata->currency_id ?? 1,
        ]);
        $savings_product = savings_product::where('id', $request->account_id)->first();
        if ($savings_product->registration_fee != null) {
            $regfee = $savings_product->registration_fee;
        }
        user_wallet::create([
            'wb_account_detail_id' => $wb_account_details->id,
            'balance'              => (0 - $regfee),
        ]);

        if ($regfee > 0) {
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(30, $auth->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([
                "balance_before"         => 0,
                "amount"                 => $regfee,
                "withdrawal_charge"      => 0,
                "description"            => 'Account registration',
                "account_id"             => $wb_account_details->id,
                "branch_id"              => $user->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => generate_txnID(),
                "transaction_type_id"    => 30, //debit 7
                "credit_debit_type_id"   => 2,  //debit 2
                "transaction_remarks_id" => 2,  //success 2
                "balance_after"          => (0 - $regfee),
                "created_date"           => date('Y-m-d H:i:s'),
                "created_by"             => $auth->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
        }

        if ($auth->company->setting->restrict_customers == 1) {
            $this->restrictUserAccount($user, 2, "Registration User Block");
        }

        if ($request->account_bank == "000013" && $request->account_type != "internal") {

            $debitCompany    = debitCompanyWithCharge($auth->company_id, 10, 0);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(30, $auth->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([
                "balance_before"         => 0,
                "amount"                 => 0,
                "withdrawal_charge"      => 0,
                "description"            => 'Account registration',
                "branch_id"              => $user->branch_id,
                "transaction_status_id"  => 1,
                "company_id"             => $auth->company_id,
                "company_balance_before" => $debitCompany["balance_before"],
                "company_amount"         => 10,
                "company_balance_after"  => $debitCompany["balance_after"],
                "transaction_reference"  => generate_txnID(),
                "transaction_type_id"    => 30, //debit 7
                "credit_debit_type_id"   => 2,  //debit 2
                "transaction_remarks_id" => 2,  //success 2
                "created_date"           => date('Y-m-d H:i:s'),
                "created_by"             => $auth->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
        }
        $err = '';
        try {

            $image = $request->file('passport');
            if ($image) {
                // create a new filename for the resized image
                $filename   = time() . str_replace(' ', '_', $image->getClientOriginalExtension());
                $uploadPath = "public/company/customer/profile_pic/$user->uuid";

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
                    'profile_pic' => $uploadPath . $filename,
                ]);
            }
        } catch (Exception $e) {
            //die($e);
        }
        try {

            $image = $request->file('valid_id');
            if ($image) {

                // create a new filename for the resized image
                $filename   = time() . str_replace(' ', '_', $image->getClientOriginalExtension());
                $uploadPath = "public/company/customer/validID_file/$user->uuid";

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
                    'validID_file' => $uploadPath . $filename,
                ]);
            }
        } catch (Exception $e) {

            //die($e);
        }
        // try {
        //     //code...

        //     $doc2 = $request->file('valid_id');

        //     $new_name2 = rand() . "." . $doc2->getClientOriginalExtension();

        //     $file2 = $doc2->storeAs("public/company/customer/validID_file/$user->id", $new_name2);
        //     $user->update([
        //         'validID_file' => $file2
        //     ]);
        // } catch (\Throwable $th) {
        //     //throw $th;
        // }
        $branchName = isset($user->bank->bank_name) ? $user->bank->bank_name : '';

        $companyname = $auth->company->company_name;
        $datax       = [
            'message'        => "Welcome to $companyname, your TXN PIN: $txnpin, Password: $newpsw and your $account_type_msg account is $virtual_acccount_number",
            'subtext'        => "Welcome to $companyname, your TXN PIN: $txnpin, Password: $newpsw and your $account_type_msg account is $virtual_acccount_number",
            'desc'           => "Welcome to $companyname",
            'status'         => 1,
            'name'           => $request->first_name . ' ' . $request->last_name,
            'email'          => $request->email,
            'account_number' => "$virtual_acccount_number",
            'test'           => $request->push_wmn,
            'error'          => $err,
            'bank'           => $branchName,
        ];
        $branch = ($user->branch) ? $user->branch->branch_name : ' default ';
        if ($request->push_wmn) {
            if (strlen($companyname) > 20) {
                $companyname = substr($companyname, 0, 20) . '...';
            }

            $excludedCompanies = [29];

            $message = "Welcome to $companyname, we are excited to have you, your Pin: $txnpin, Password: $newpsw, $account_type_msg No: $virtual_acccount_number, Bank: $branchName";

            if (in_array($auth->company->id, $excludedCompanies)) {
                $message = "Welcome to $companyname, we are excited to have you, your Pin: $txnpin, Password: $newpsw, $account_type_msg No: $virtual_acccount_number";
            }

            $smsresponse = sendSMS($user->id, $message, true, true, 'Account', $virtual_acccount_number);
        }
        return response()->json($datax);
        //  return redirect('/account_creation_success')->with('account_creation_msg','Account Created Successfully. Your account number is ');
    }

    public function createPhysicalCard(Request $request)
    {

        //  return response()->json(['status' => 0, 'message' => 'This service has been disabled. Kindly contact support for updates']);

        $transactionId = generateTransactionReference();
        $auth          = auth()->user();
        $validator     = Validator::make($request->all(), [
            'first_name'         => 'required|string|max:50|min:2',
            'last_name'          => 'required|string|max:50|min:2',
            'phone'              => 'required|string',
            'address'            => 'required|string',
            'email'              => 'required|email|string',
            'bvn'                => 'required|string',
            'customer_reference' => 'required|string',
            'card_number'        => 'required|string',
        ]);

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

                'status'  => 0,

            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }

        if (auth()->user()->company_id != 14) {
            return response()->json(['status' => 0, 'message' => 'This service has been disabled. Kindly contact support for updates']);
        }

        $customer  = User::where('uuid', $request->customer_reference)->first();
        $checkCard = UserCard::where('user_id', $customer->id)->where('card_category_id', 2)->first();
        if ($checkCard != null) {
            $customer_sudo_account_id = $checkCard->card_reference;
        } else {
            $customer_sudo_account_id = null;
        }

        if ($customer) {

            /*   $userCard = CardRequestLog::where(['user_id' => $customer->id]);

            if (! $userCard) {
                return response()->json(['status' => 0, 'message' => 'User has already requested for Cards']);
            }*/

            DB::beginTransaction();
            try {

                $wbaSettings     = WbaCharge::find(17);
                $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

                $companyDebit    = debitCompanyWithCharge(auth()->user()->company_id, $wbaSettings->charge, 0);
                $customerBalance = \debitCustomerOnly($customer->accounts->first()->id, $companySettings->new_physical_card_charge, $customer);

                if (! $customerBalance['status']) {
                    $customerBalance['status'] = 0;
                    DB::rollback();
                    return response()->json($customerBalance);
                }

                if (! $companyDebit['status']) {
                    $companyDebit['status'] = 0;
                    DB::rollback();

                    return response()->json($companyDebit);
                }

                logInfo($customerBalance, "Customer Balance");
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $auth->company_id);
                $ledger_cr       = null;
                $ledger_dr       = null;
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                //Create a successful transaction for the Recipient
                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance_before'],
                    "company_balance_before" => $companyDebit['balance_before'],
                    "account_id"             => $customerBalance['account_id'],
                    "branch_id"              => auth()->user()->branch->id,
                    "amount"                 => $companySettings->new_physical_card_charge,
                    "company_amount"         => $wbaSettings->charge,
                    "balance_after"          => $customerBalance['balance_after'],
                    "company_balance_after"  => $companyDebit['balance_after'],
                    "company_id"             => auth()->user()->company_id,
                    "description"            => "Purchase of Physical Card",
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 1,
                    "transaction_type_id"    => 23,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $auth->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                /*   CardRequestLog::create([
                    'user_id'    => $customer->id,
                    'company_id' => auth()->user()->company->id,
                ]);*/

                $customer->user_card_status_id = 2;
                $customer->bvn                 = $request->bvn;
                $customer->address             = $request->address;
                $customer->save();

                $safehavenController = new SafehavenController();

                $createCardResult = $safehavenController->createPhysicalCard($request, $transactionId, $customer, $customer_sudo_account_id);

                logInfo($createCardResult, "Card Request Result");

                if (! $createCardResult['status']) {
                    $createCardResult['status'] = 0;
                    // $customerBalance = \creditCustomerAndCompanyWithCharge($customer->accounts->first()->id, auth()->user()->company_id, $companySettings->new_physical_card_charge, 0, 0);

                    DB::rollback();

                    return response()->json($createCardResult['message']);
                }

                //Debit source Company Wallet
                $transaction->transaction_remarks_id = 2;
                $transaction->save();

                DB::commit();

                $createCardResult['status'] = 1;
                $userCard                   = UserCard::create([
                    "user_id"   => $user->id,
                    "card_name" => "{$user->first_name} {$user->last_name}",
                    "card_type_id"     => 2,
                    "card_category_id" => $request->card_category,
                    "card_number"      => $createCardResult['result']['data']["card_pan"] ?? $createVirtualCard['result']['data']["maskedPan"],
                    "masked_pan"       => $createCardResult['result']['data']["maskedPan"],
                    "card_reference"   => $createCardResult['result']['data']["_id"],
                    "currency_id"      => $companySettings->preferred_virtual_card_currency,
                    "exp_date"         => $createCardResult['result']['data']["expiration"],
                    "cvv"              => $createCardResult['result']['data']["cvv"],
                    "created_by"       => $createCardResult['result']['data']["customer"],
                ]);

                return response()->json($createCardResult);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json(['status' => 0, 'message' => $e->getMessage()]);
            }
        } else {
            return response()->json(['status' => 0, 'message' => 'Invalid Customer Reference']);
        }
    }

    public function addPhysicalCard(Request $request)
    {

        $transactionId = generateTransactionReference();

        return response()->json(['status' => 0, 'message' => 'This service has been disabled. Kindly contact support for updates']);

        logInfo($request->all(), "Add Physical Card Request");

        $validator = Validator::make($request->all(), [
            'card_name'          => 'required|string|max:100|min:2',
            'card_type'          => 'required|numeric|gt:0',
            'card_number'        => 'required|numeric|gt:0',
            'exp_date'           => 'required',
            'cvv'                => 'required|numeric|gt:0',
            'customer_reference' => 'required|string',
        ]);

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
                'status'  => 0,

            ];

            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }

        $providusController = new ProvidusController();

        $checkBalance = $providusController->getPhysicalCardBalance($request->card_number);

        if (! $checkBalance["status"]) {
            return \response()->json(["status" => 0, "message" => "Failed to add Card"]);
        }

        $checkCard = UserCard::where('card_number', $request->card_number)->first();

        if ($checkCard) {
            \response()->json(["status" => 0, "message" => "User card number already exists"]);
        }

        $customer = User::where('uuid', $request->customer_reference)->first();

        if ($customer) {

            //Add Card to Database
            $userCard = UserCard::create([
                'user_id'          => $customer->id,
                'card_name'        => $request->card_name,
                'card_category_id' => 2,
                'card_type_id'     => $request->card_type,
                'card_number'      => $request->card_number,
                'masked_pan'       => $request->card_number,
                'currency_id'      => auth()->user()->company->currency->id,
                'exp_date'         => $request->exp_date,
                'cvv'              => $request->cvv,
            ]);

            //Update Customer has card
            $customer->user_card_status_id = 3;
            $customer->updated_at          = Carbon::now();
            $customer->save();

            //Update Card request log to Completed
            CardRequestLog::where('user_id', $customer->id)->update(['status_id' => 3]);

            $msg = "Dear customer your ATM card is ready and had been linked to your account kindly visit our office for pickup";

            sendSMS($customer->id, $msg, false, true, 'Your Card is Ready');

            return response()->json(['status' => 1, 'message' => 'Card Added Successfully']);
        } else {
            return response()->json(['status' => 0, 'message' => 'Invalid Customer Reference']);
        }
    }

    public function getCustomerAccounts(Request $request, $companyShort, $uuid)
    {
        \logInfo($request->all(), "Get customer list log");

        $user    = User::with(['accounts', 'accounts.user_wallet'])->where(['company_id' => auth()->user()->company_id, 'uuid' => $uuid, 'user_type_id' => 1]);
        $is_root = cando('root|allow-staff-view-all-customers');

        $user = $user->first();
        if ($user) {

            return response()->json([
                'status'  => 1,
                'message' => 'User details fetched successfully',
                'data'    => $user,
            ]);
        } else {
            return response(json_encode(['status' => 0, 'message' => 'Invalid User information']));
        }
    }

    public function fundPhysicalCard(Request $request)
    {

        $transactionId = generateTransactionReference();

        return response()->json(['status' => 0, 'message' => 'This service has been disabled. Kindly contact support for updates']);

        $validator = Validator::make($request->all(), [
            'customer_reference' => 'required|string',
            'fund_amount'        => 'required|numeric|min:50',
        ]);

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
                'status'  => 0,
            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        $customer = User::where('uuid', $request->customer_reference)->first();

        if ($customer) {

            $accountId = $customer->accounts->first()->id;

            $charge = 0;

            if (\auth()->user()->company->setting->bank_transfer_charge_state == 1) {
                $charge = $request->final_amount * (\auth()->user()->company->setting->min_bank_transfer_charge_or_percentage / 100);

                $charge = $charge > \auth()->user()->company->setting->max_bank_transfer_charge ? \auth()->user()->company->setting->max_bank_transfer_charge : $charge;
            } else {
                $charge = \auth()->user()->company->setting->max_bank_transfer_charge;
            }

            $wbaCharge = 10;

            $charge = $charge < $wbaCharge && $charge > 0 ? ($wbaCharge + ($wbaCharge * 0.01)) : $charge;

            // $customerBalance = checkCustomerBalanceCompanyBalanceAndDebit($customer->accounts->first()->id, $request->fund_amount);
            $customerBalance = debitCustomerAndCompanyWithCharge($customer->accounts->first()->id, $customer->company_id, $request->fund_amount, $charge, $wbaCharge);

            if (! $customerBalance['status']) {
                $customerBalance['status'] = 0;
                return response()->json($customerBalance);
            }

            $transactionId   = rand(11111111111111, 99999999999999);
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(24, $customer->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            //Create a successful transaction for the Recipient
            $transaction = transactions::create([
                "balance_before"         => $customerBalance['balance_before'],
                "amount"                 => $request->fund_amount,
                "account_id"             => $accountId,
                "branch_id"              => auth()->user()->branch->id,
                "balance_after"          => $customerBalance['balance_after'],
                "company_id"             => auth()->user()->company_id,
                "description"            => "Physical card funded Linked to $customer->virtual_account",
                "company_balance_after"  => $customerBalance["company_balance_after"],
                "company_balance_before" => $customerBalance["company_balance_before"],
                "company_amount"         => $customerBalance["company_amount"],
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 24,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => auth()->user()->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $transactionChargeId = generate_txnID();
            //Create a successful transaction for the Recipient
            $chargeTtransaction = transactions::create([
                "balance_before"         => $customerBalance['charge_balance_before'],
                "amount"                 => $charge,
                "account_id"             => $accountId,
                "branch_id"              => auth()->user()->branch->id,
                "balance_after"          => $customerBalance['charge_balance_after'],
                "company_id"             => auth()->user()->company_id,
                "description"            => "Physical card funding Charge",
                "company_balance_after"  => $customerBalance["company_charge_balance_after"],
                "company_balance_before" => $customerBalance["company_charge_balance_before"],
                "company_amount"         => $wbaCharge,
                "transaction_reference"  => $transactionChargeId,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 107,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => auth()->user()->id,
            ]);

            $providusController = new ProvidusController();

            $createCardResult = $providusController->fundPhysicalCard($customer->email, $customer->phone, $request->fund_amount, $customer->card->card_number, $customer->first_name, $customer->last_name, $transactionId);

            logInfo($createCardResult, "Fund Card Result");

            if ($createCardResult['status'] != "00") {
                $createCardResult['status'] = 0;
                return response()->json($createCardResult);
            }

            //Debit source Company Wallet
            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            $createCardResult['status'] = 1;

            $msg = "Your card has  successfully  been Prefunded with NGN" . moneyFormat($request->fund_amount) . ". Ready to use, you can use on ATM and POS for withdrawal";

            logInfo($customer, "Prefunded Customer Log");
            logInfo($createCardResult, "Prefunded Customer Log");

            // sendSMS($customer->id, $msg, false, true, 'Card Credit Alert');

            return response()->json($createCardResult);
        } else {
            return response()->json(['status' => 0, 'message' => 'Invalid Customer Reference']);
        }
    }

    public function getPhysicalCardBalance(Request $request)
    {

        return response()->json(['status' => 0, 'message' => 'This service has been disabled. Kindly contact support for updates']);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {

            $datax = [
                'user_id' => $validator->errors()->all()[0],
            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }

        $providusController = new ProvidusController();

        $userCard = UserCard::where('user_id', $request->user_id)->first();

        $createCardResult = $providusController->getPhysicalCardBalance($userCard->card_number);

        logInfo($createCardResult, "Card Balance Result");

        if (! $createCardResult['status']) {
            $createCardResult['status'] = 0;
            return response()->json($createCardResult);
        }

        $createCardResult['status']       = 1;
        $createCardResult['data']['card'] = $userCard;
        return response()->json($createCardResult);
    }

    public function account_creation_success()
    {

        $data = [
            'category_name'    => 'forms',
            'page_name'        => 'wizards',
            'page_menu'        => 'company_branch_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            // 'status' => status::all(),

        ];

        return view('company.customer.account_creation_success', [])->with($data);
    }

    public function customer_profiles()
    {
        if (! cando('edit-customer|manage-customer|view-customer-profile|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'customers_profile',
            'page_menu'        => 'customers_profile',
            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        return view('company.customer.profiles')->with($data);
    }

    public function customer_delete()
    {
        if (! cando('delete-customer|manage-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'customers_delete',
            'page_menu'        => 'customers_delete',
            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        return view('company.customer.recycle_customer')->with($data);
    }
    public function physicalCardManagement()
    {
        if (! cando('physical-card-management|root')) {
            return abort(403);
        }

        $cardType = CardType::all();

        $cardCharge = company_settings::where('company_id', auth()->user()->company->id)->get('new_physical_card_charge')->first();

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'customers_profile',
            'page_menu'        => 'physical_card_management',

            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'card_types'       => $cardType,
            'card_charge'      => $cardCharge,
            // 'status' => status::all(),
        ];

        $users = User::latest()->paginate(50);

        return view('company.customer.physical_card_management', [

            'users' => $users,

        ])->with($data);
    }

    public function physicalCardRequests()
    {
        if (! cando('physical-card-request|root')) {
            return abort(403);
        }

        $cardRequests = CardRequestLog::with('user')->where('company_id', auth()->user()->company->id)->get();

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html5x',
            'page_menu'        => 'card_requests_menu',
            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            // 'status' => status::all(),
        ];

        return view('company.customer.card_requests', [
            'cardRequests' => $cardRequests,
        ])->with($data);
    }

    public function getwbaccounttype($id)
    {
        $wb_account_details = wb_account_details::where('virtual_accnum', $id)->first();
        $user               = User::where(['id' => $wb_account_details->user_id, 'company_id' => auth()->user()->company_id])->first();
        if ($user) {
            return response(
                json_encode(
                    [
                        'balance' => $wb_account_details->user_wallet->balance,
                        'account' => $id,
                        'name'    => $user->first_name . ' ' . $user->last_name,
                        'product' => $wb_account_details->accountType->savings_product,

                    ]
                )
            );
        } else {
            return response(
                json_encode(
                    [
                        'status'  => 0,

                        'message' => "Error ",

                    ]
                )
            );
        }
        return null;
    }

    public function customer_profile($company_name, $id)
    {
        if (! cando('edit-customer|manage-customer|root')) {
            return abort(403);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return abort(403);
        }
        //return json_encode(['status'=>0,'message'=>'staff restriction from manage-user']);
        //branch
        $subscription_restriction_status_id = 14;
        $data                               = [
            'category_name'    => 'forms',
            'page_name'        => 'wizards',
            'page_menu'        => 'customer_profile',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'tiers'            => KycLevel::where('Status_id', 1)->get(),
            'account_types'    => savings_product::where('company_id', auth()->user()->company_id)->get(),
            'branch'           => auth()->user()->company->branch,
            'status'           => status::where('id', '!=', 8)->where('id', '!=', $subscription_restriction_status_id)->get(),
            'countries'        => Contries::all(),
        ];

        $user = User::with('nok_details')->where(['uuid' => $id, 'company_id' => auth()->user()->company_id])->first();

        return view('company.customer.profile', ['user' => $user])->with($data);
    }

    public function update_profilex(Request $request)
    {
        try {
            // if (!cando('edit-customer|manage-customer|root'))     return json_encode(array('status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"));
            if (! cando('edit-customer|root')) {
                return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
            }

            if (! restrict_to_just_my_company(['user_uuid' => $request->user_id])) {
                return json_encode(['status' => 0, 'message' => "Access Denied!"]);
            }

            if ($request->username != null) {
                $username_checker = User::where(['username' => $request->username])->where('uuid', '!=', $request->user_id)->count();
                if ($username_checker > 0) {
                    return back()->with('fail', 'Username Already Exist!!!');
                }
                // return json_encode(array('status' => 0, 'message' => "Username Already Exist!"));
            }
            if ($request->email != null) {
                $email_checker = User::where(['email' => $request->email, 'company_id' => auth()->user()->company_id])->where('uuid', '!=', $request->user_id)->count();
                if ($email_checker > 0) {
                    return back()->with('fail', 'Email Already Exist!!!');
                }
                // return json_encode(array('status' => 0, 'message' => "Username Already Exist!"));
            }
            $user          = User::where(['uuid' => $request->user_id, 'company_id' => auth()->user()->company_id])->where('status_id', '!=', 12)->first();
            $update_record = [
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'phone'              => $request->phone,
                'username'           => $request->username,
                'email'              => $request->email,
                'dob'                => $request->dob,
                'nin'                => $request->nin,
                'kyc_level_id'       => ($request->account_tier != null) ? $request->account_tier : $user->kyc_level_id,
                'ussd_status_id'     => $request->ussd_status_id,
                'whatsapp_status_id' => $request->whatsapp_status_id,
                'bvn'                => $request->bvn,
                'status_id'          => $request->user_status,
                'address'            => $request->address,
                'sex'                => $request->sex,
                'nike_name'          => $request->nike_name,
                'occupation'         => $request->occupation,
                'marital_status'     => $request->marital_status,
                'alternative_phone'  => $request->alternative_phone,
                'business_name'      => $request->business_name,
                'home_address'       => $request->home_address,
                'state_of_origin'    => $request->state_of_origin,
                'country_id'         => $request->country_id,
            ];
            if (cando('set-customer-branch-officer|root')) {
                if ($request->branch != null) {
                    $request->branch            = Crypt::decryptString($request->branch);
                    $update_record['branch_id'] = $request->branch;
                }
                if ($request->officer != null) {
                    $accountofficer                      = User::where(['uuid' => $request->officer, 'company_id' => auth()->user()->company_id])->first();
                    $update_record['account_officer_id'] = $accountofficer->id;
                }
            }

            $user->update($update_record);
            // $user = User::where('email', $request->email)->first();
            $nok_details = NextOfKinDetail::where('user_id', $user->id)->first();

            if ($nok_details) {
                $nok_details->update([
                    'nok_name'    => $request->nok_name,
                    'nok_phone'   => $request->nok_phone,
                    'nok_address' => $request->nok_address,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                NextOfKinDetail::create([
                    'user_id'     => $user->id,
                    'nok_name'    => $request->nok_name,
                    'nok_phone'   => $request->nok_phone,
                    'nok_address' => $request->nok_address,
                ]);
            }
            return back()->with('message', 'Update Successful!!');
        } catch (Exception $e) {
            return back()->with('fail', $e->getMessage());
        }
    }
    public function verify_customer_profile_bvn(Request $request)
    {
        try {
            $request->merge([
                "verification_type"   => 'bvn',
                "verification_number" => $request->bvn,
            ]);
            $GetUserByUUID = User::where('uuid', $request->uuid)->first();
            if (! $GetUserByUUID) {
                return ['status' => false, 'message' => 'Fail to fetch customer information'];
            }
            $verifyBVNInfo = ((new TransactionController)->verifyBVN($request));
            $jsonString    = $verifyBVNInfo->getContent();
            $verifyBVN     = json_decode($jsonString);
            if ($verifyBVN->status == 1) {
                // return $verifyBVN;
                $GetUserByUUID->bvn_status_id     = 1;
                $GetUserByUUID->BVN               = $request->bvn;
                $GetUserByUUID->first_name        = $verifyBVN->data->data->firstName;
                $GetUserByUUID->last_name         = $verifyBVN->data->data->lastName;
                $GetUserByUUID->alternative_phone = $verifyBVN->data->data->phoneNumber1 ?? $GetUserByUUID->alternative_phone;
                $GetUserByUUID->dob               = $verifyBVN->data->data->dateOfBirth ? date("Y-m-d", strtotime($verifyBVN->data->data->dateOfBirth)) : $GetUserByUUID->dob;
                $GetUserByUUID->address .= ' BVN Address: ' . $verifyBVN->data->data->residentialAddress . ' Resident State: ' . $verifyBVN->data->data->stateOfResidence;
                $GetUserByUUID->sex       = $verifyBVN->data->data->gender;
                $GetUserByUUID->bvn_image = $verifyBVN->data->data->base64Image;

                $GetUserByUUID->state_of_origin = $verifyBVN->data->data->stateOfOrigin;
                $GetUserByUUID->nike_name       = $GetUserByUUID->nike_name ?? $verifyBVN->data->data->middleName;

                $GetUserByUUID->save();
                return response()->json(['status' => true, 'message' => 'BVN record Updated', 'bvn' => $verifyBVN]);
            } else {
                return response()->json(['status' => false, 'message' => $verifyBVN->message]);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
        // return  $verifyBVN;
    }
    public function updateprofile(Request $request)
    {
        try {
            if (! cando('edit-customer|root')) {
                return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
            }

            // if (!cando('edit-customer|manage-customer|root'))     return json_encode(array('status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"));
            if (! restrict_to_just_my_company(['user_uuid' => $request->user_id])) {
                return json_encode(['status' => 0, 'message' => 'staff restricted!']);
            }

            $userid    = $request->user_id;
            $imagename = $request->imgname;
            $user      = User::where(['uuid' => $userid, 'company_id' => auth()->user()->company_id])->first();
            $file      = $request->file('file');
            $fileName  = time() . str_replace(' ', '_', $file->getClientOriginalName());

            if ($file) {
                Storage::delete($user->signature);

                $image_info = getimagesize($file->getPathname());
                if (is_array($image_info)) {
                    // resize the image
                    $image        = Image::make($file);
                    $resizedImage = $image->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                        ->encode();
                    // $Path = $file->storeAs("public/company/customer/$imagename/$userid", $resizedImage);

                    // if (!file_exists("public/company/customer/$imagename/$userid")) {
                    //     mkdir("public/company/customer/$imagename/$userid", 0755);
                    // }
                    $Path = "public/company/customer/$imagename/$userid" . $fileName;

                    $upload = Storage::put($Path, $resizedImage);
                    // $file->storeAs($Path, $resizedImage);
                } else {
                    $Path = $file->storeAs("public/company/customer/$imagename/$userid", $fileName);
                }
                if ($imagename == 'utilityBillID_file') {
                    $user->utilityBillID_file = $Path;
                }
                if ($imagename == 'profile_pic') {
                    $user->profile_pic = $Path;
                }
                if ($imagename == 'validID_file') {
                    $user->validID_file = $Path;
                }
                if ($imagename == 'signature') {
                    $user->signature = $Path;
                }
                if ($imagename == 'otherID_file') {
                    $user->otherID_file = $Path;
                }

                if ($user->save()) {
                    return json_encode(['status' => 1, 'message' => "User $imagename Updated!", "path" => $Path]);
                } else {
                    return json_encode(['status' => 0, 'message' => "Update Failed"]);
                }
            }

            return json_encode(['status' => 0, 'message' => "An Error Occured!"]);
        } catch (Exception $e) {
            return json_encode(['status' => 0, 'message' => $e->getMessage()]);
        }
    }
    public function staffDatalist(Request $request)
    {
        $search = $request->searchTerm;

        if ($search != null) {
            $user = User::where(['users.user_type_id' => '2', 'users.company_id' => auth()->user()->company_id])
                ->where('users.first_name', 'like', '%' . $search . '%')
                ->orwhere('users.last_name', 'like', '%' . $search . '%')
                ->orwhere('users.phone', 'like', '%' . $search . '%')
                ->orwhere('users.email', 'like', '%' . $search . '%')
                ->paginate(10);
        } else {
            $user = User::where(['users.user_type_id' => '2', 'users.company_id' => auth()->user()->company_id])->paginate(10);
        }

        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->id, 'text' => $userdata->first_name . ' ' . $userdata->last_name . ' - ' . $userdata->username];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function userDatalist(Request $request)
    {
        $type       = ($request->type) ? 2 : 1;
        $search     = $request->searchTerm;
        $company_id = auth()->user()->company_id;
        $userID     = auth()->user()->id;
        $is_root    = cando('root|allow-staff-view-all-customers');
        $user       = User::where(['status_id' => 1, 'user_type_id' => $type, 'company_id' => $company_id]);
        if (! $is_root) {
            $user = $user->where('account_officer_id', $userID);
        }
        if ($search != null) {
            $user->where(function ($user) use ($search) {
                $user->orWhere('virtual_account', 'like', '%' . $search . '%');
                $user->orWhere('email', 'like', '%' . $search . '%');
                $user->orwhere('first_name', 'like', '%' . $search . '%');
                $user->orWhere('last_name', 'like', '%' . $search . '%');
                $user->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $user = $user->paginate(10);

        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->uuid, 'text' => $userdata->first_name . ' ' . $userdata->last_name . ' - ' . $userdata->virtual_account];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function selectuserDatalist($id)
    {
        // $search = $request->searchTerm;
        $user    = User::where(['user_type_id' => '1', 'company_id' => auth()->user()->company_id, 'uuid' => $id]);
        $is_root = cando('root|allow-staff-view-all-customers');
        if (! $is_root) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $user = $user->first();
        // if ($search != null) {

        $useraccount = wb_account_details::where(['user_id' => $user->id])
            ->paginate(10);

        // } else {
        //        }
        $userinfo = [];
        if ($useraccount) {
            foreach ($useraccount as $userdata) {
                $userinfo[] = ['id' => $userdata->id, 'text' => $userdata->accountType->savings_product . ' - ' . $userdata->virtual_accnum];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }

    public function userDataSource($page, Request $request)
    {

        $search = $request->query('search', ['value' => '', 'regex' => false]);
        $draw   = $request->query('draw', 0);
        $start  = $request->query('start', 0);
        $length = $request->query('length', 100);
        $order  = $request->query('order', [1, 'asc']);
        $filter = $search['value'];

        if ($page == 'export-all-customer') {
            $sortColumns = [
                0  => 'users.id',
                1  => 'wb_account_details.virtual_accnum',
                2  => 'users.username',
                3  => 'users.phone',
                4  => 'users.sex',
                5  => 'users.address',
                6  => 'users.address',
                7  => 'users.branch_id',
                8  => 'users.bvn',
                9  => 'users.nin',
                10 => 'users.updated_at',
                11 => 'users.account_officer_id',
                12 => 'users.account_officer_id',
                13 => 'users.created_at',
                14 => 'user_wallets.balance',
                15 => 'users.email',
                16 => 'users.dob',
                17 => 'users.id',
                18 => 'users.id',
                19 => 'users.id',
                20 => 'users.bankcode',
                21 => 'users.bankcode',
                22 => 'users.bankcode',
            ];
        } else if ($page == 'esusu-mobile') {
            $sortColumns = [
                0 => 'users.id',
                1 => 'users.first_name',
                2 => 'users.last_name',
                3 => 'users.email',
                4 => 'users.username',
                5 => 'wb_account_details.virtual_accnum',
                6 => 'user_wallets.balance',
                7 => 'users.id',
            ];
        } else if ($page == 'all-customer') {
            $sortColumns = [
                0 => 'users.id',
                1 => 'users.first_name',
                2 => 'users.last_name',
                3 => 'users.email',
                4 => 'users.username',
                5 => 'wb_account_details.virtual_accnum',
                6 => 'wb_account_details.virtual_accnum',
                7 => 'user_wallets.balance',
                8 => 'users.id',
            ];
        } else {
            $sortColumns = [
                0 => 'users.id',
                1 => 'users.first_name',
                2 => 'users.last_name',
                3 => 'users.email',
                4 => 'users.username',
                5 => 'wb_account_details.virtual_accnum',
                6 => 'user_wallets.balance',
                7 => 'users.id',
            ];
        }
        if ($page == "statement") {
            $query = wb_account_details::join('users', 'wb_account_details.user_id', '=', 'users.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
                ->select('wb_account_details.*', 'users.uuid', 'users.phone', 'users.last_name', 'users.first_name', DB::raw('wb_account_details.id as accountID'), DB::raw('savings_products.savings_product as accountsavingsp'), DB::raw('round(SUM(user_wallets.balance),1) as balance'));
        } else if ($page == "card_request") {
            $query = CardRequestLog::join('users', 'card_request_logs.user_id', '=', 'users.id')
                ->join('user_card_statuses', 'user_card_statuses.id', '=', 'card_request_logs.status_id')
                ->select('users.*', DB::raw('card_request_logs.created_at as ReqeustDate'), DB::raw('user_card_statuses.user_card_status as cardStatus'))
                ->where('card_request_logs.status_id', 2);
        } else if ($page == "all-mandates") {
            $query = Mandate::join('users', 'mandates.user_id', '=', 'users.id')
                ->join('statuses', 'statuses.id', '=', 'mandates.status_id')
                ->select('mandates.*', DB::raw('statuses.name as status'));
        } else if ($page == 'esusu-mobile') {
            $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
                ->where(['users.is_agent' => $request->is_agent, 'users.status_id' => 1])
                ->select('users.*', 'wb_account_details.virtual_accnum', DB::raw('wb_account_details.id as accountID'), DB::raw('savings_products.savings_product as accountsavingsp'), DB::raw('round(SUM(user_wallets.balance),1) as balance'));
        } else {
            $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
                ->join('user_wallets', 'user_wallets.wb_account_detail_id', '=', 'wb_account_details.id')
                ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
                ->select('users.*', 'wb_account_details.virtual_accnum', DB::raw('wb_account_details.id as accountID'), DB::raw('savings_products.savings_product as accountsavingsp'), DB::raw('round(SUM(user_wallets.balance),1) as balance'));
        }

        if ($page == "pending-entry") {
            $query = $query->where('users.status_id', 7);
        } else if ($page == "recycle-account") {
            $query = $query->where('users.status_id', 9);
        } else if ($page == 'all-customer') {
            $sortColumns[7] = 'users.created_at';
            $query          = $query->where(function ($query) {
                $query->orwhere('users.status_id', 1);
                $query->orwhere('users.status_id', 7);
                $query->orwhere('users.status_id', 6);
            });
        } else {
            $query = $query->where('users.status_id', 1);
        }
        $is_root = cando('root|allow-staff-view-all-customers');
        if (! $is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        $query = $query->where(['users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id])->groupBy('users.id');
        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orwhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orwhere(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('users.username', 'like', '%' . $filter . '%');
                $query->orWhere('users.email', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('user_wallets.balance', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
            });
        }

        if ($page == 'all-customer') {
            if ($request->from != null && $request->to != null) {
                $from  = $request->from;
                $to    = $request->to;
                $query = $query->where('users.created_at', '>=', $from)->where('users.created_at', '<=', $to);
            }
            if ($request->product != '') {
                $productID = Crypt::decryptString($request->product);
                $query     = $query->where('savings_products.id', $productID);
            }
            if ($request->option == 'all') {
                $length = 200000;
            }
            if ($request->branch != '') {
                $request->branch = Crypt::decryptString($request->branch);
                $query           = $query->where('users.branch_id', $request->branch);
            }
            if (cando('root')) {
                if ($request->officer != 'null' && $request->officer != null && $request->officer != '') {
                    $officerID = User::where('uuid', $request->officer)->first();
                    $query     = $query->where('users.account_officer_id', $officerID->id);
                }
            }
        } else {
            if (isset($order[0]['column'])) {
                if (isset($sortColumns[$order[0]['column']])) {
                    $sortColumnName = $sortColumns[$order[0]['column']];
                    $query->orderBy($sortColumnName, $order[0]['dir']);
                }
            }
            // ->take( $length)
            $query->take($page != "card_request" && $page != "physical_cards" ? $length : 20)
                ->skip($start);
        } //here

        $recordsTotal = $query->count();
        $json         = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();

        $loop = 0;
        foreach ($info as $infoData) {
            // $loop++;
            $btnlink   = $infoData->uuid;
            $shortname = auth()->user()->company->short_name;
            $btndom    = "<a href='/company/$shortname/customer/activate/$btnlink' target='_blank' class='btn btn-outline-success btn-sm' onclick=";
            $btndom .= " >Activate</a>";
            if ($page == "debitcredit") {
                $cooperateStatus   = "";
                $CooperateCustomer = CooperateCustomer::where('user_id', $infoData->id)->first();
                if ($CooperateCustomer) {
                    $cooperateStatus = "<span class='badge badge-success'>$CooperateCustomer->account_name</span>";
                }
                $btncredit = "<button class='btn btn-outline-primary btn-sm' onclick=";
                $btncredit .= "credit('$btnlink') >Credit</button>";
                $btndebit = "<button class='btn btn-outline-primary btn-sm' onclick=";
                $btndebit .= "debit('$btnlink') >Debit</button>";
                $savingdata = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->virtual_accnum . "<br>" . $cooperateStatus,
                    $infoData->phone,
                    $infoData->accountsavingsp,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                ];
                if (cando('credit-customer|manage-saving|root')) {
                    $savingdata[] = $btncredit;
                }
                if (cando('debit-customer|manage-saving|root')) {
                    $savingdata[] = $btndebit;
                }
                $json['data'][] = $savingdata;
            } else if ($page == "investment") {
                $btn = "<button class='btn btn-outline-primary btn-sm' onclick=";
                $btn .= "invest('$btnlink') >Invest</button>";
                $json['data'][] = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->virtual_accnum,
                    $infoData->phone,
                    $infoData->accountsavingsp,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            } else if ($page == "statement") {

                $btn = "<a href='/company/$shortname/user-statement/$btnlink' target='_blank' class='btn btn-outline-primary btn-sm' onclick=";
                $btn .= " >Statement</a>";

                $json['data'][] = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->virtual_accnum,
                    $infoData->phone,
                    $infoData->accountsavingsp,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            } else if ($page == "profile") {
                $shortname = Auth()->user()->company->short_name;
                $btn       = "<a href='/company/$shortname/customer/view-profile/$btnlink'  class='btn btn-outline-primary btn-sm' onclick=";
                $btn .= " >View</a>";
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->email,
                    $infoData->virtual_accnum,

                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            } else if ($page == "physical_cards") {
                $shortname = Auth()->user()->company->short_name;
                if ($infoData->user_card_status_id == 1) {

                    $btn = '<button class="btn btn-outline-primary btn-sm" onclick="show_customer_details(' . $infoData->id . ')"';
                    $btn .= " >Request Card</button>";
                } else if ($infoData->user_card_status_id == 2) {

                    $btn = '<button class="btn btn-outline-info btn-sm" id="addMyCard" data-id="' . $infoData->uuid . '" onclick="show_card_customer_name(' . $infoData->id . ')"';
                    $btn .= " >Add Card</button>";
                } else if ($infoData->user_card_status_id == 3) {
                    $btn = '<button class="btn btn-outline-success btn-sm" id="fundMyCard" onclick="showFundCardModal(\'' . $infoData->uuid . '\')"';
                    $btn .= ">Fund Card</button>";
                    $btn .= '<button class="btn btn-outline-warning btn-sm" id="getMyCardBalance-' . $infoData->id . '" onclick="getPhysicalCardBalance(' . $infoData->id . ')" data-id="' . $infoData->uuid . '"';
                    $btn .= " >Get Balance</button>";
                }
                $loop++;
                $json['data'][] = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->email,
                    $infoData->virtual_account,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            } else if ($page == "card_request") {
                $shortname = Auth()->user()->company->short_name;
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->first_name . " " . $infoData->last_name,
                    $infoData->virtual_account,
                    $infoData->bvn,
                    $infoData->phone,
                    $infoData->ReqeustDate,
                    $infoData->cardStatus,

                ];
            } else if ($page == "pending-entry") {
                $is_root = cando('root|approve-customer');
                if (! $is_root) {
                    $btndom = "<button class='btn btn-outline-warning btn-sm' disabled>Waiting...</button>";
                } else { //activate_account
                    $btndom = "<button class='btn btn-outline-success btn-sm' onclick=";
                    $btndom .= "get_user_profile_details('$btnlink') >Activate</button>";
                }
                $json['data'][] = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->virtual_accnum,
                    $infoData->phone,
                    $infoData->accountsavingsp,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $infoData->branch->branch_name,
                    ($infoData->author) ? ($infoData->author->first_name . ' ' . $infoData->author->last_name) : '---',
                    $btndom,
                ];
            } else if ($page == "active-account" || $page == "recycle-account" || $page == "transfer_customer") {
                $btn = '';
                if ($page == "active-account") {
                    $btn = "<button type='button' class='btn btn-outline-danger btn-sm' onclick=delete_customer('$btnlink')>Delete Account</button>";
                } else if ($page == "recycle-account") {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"  href="#" onclick=permanent_delete("' . $btnlink . '")>Delete Permanently</a>
                            <a class="dropdown-item" href="#" onclick=restore("' . $btnlink . '")>Restore</a>
                        </div>
                    </div>';
                } else if ($page == "transfer_customer") {
                    $btn = '<input type="checkbox" name="Userid[]" class="checkSingle" value="' . $infoData->uuid . '"> ';
                }
                $loop++;
                $appendCheckbox = ($page != "transfer_customer") ? '<input type="checkbox" name="Userid[]" class="checkSingle" value="' . $infoData->uuid . '"> ' : '';
                $json['data'][] = [
                    $appendCheckbox . $loop,
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->phone,
                    $infoData->virtual_accnum,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            } else if ($page == 'all-customer') {
                $is_root = cando('root|approve-customer');
                if (! $is_root) {
                    $btndom = "<button class='btn btn-outline-warning btn-sm' disabled>Waiting...</button>";
                } else { //activate_account
                    if ($infoData->status_id == 7) {
                        $btndom = "<button class='btn btn-outline-success btn-sm' onclick=";
                        $btndom .= "get_user_profile_details('$btnlink') >Activate</button>";
                    } else {
                        $btndelete   = url('company/' . Auth()->user()->company->short_name . '/customer/customer-delete');
                        $restriction = url('company/' . Auth()->user()->company->short_name . '/restriction');
                        $profile     = url('company/' . Auth()->user()->company->short_name . '/customer/view-profile/' . $infoData->uuid);
                        // $btndom ="<a href=''><span class='alert alert-info'>Account</span></a>";
                        $btndom = '<div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"  href="' . $btndelete . '" >Delete</a>
                            <a class="dropdown-item" href="' . $restriction . '" >Restriction</a>
                            <a class="dropdown-item" href="' . $profile . '" >Profile</a>
                        </div>
                    </div>';
                    }
                }
                $json['data'][] = [
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->email,
                    $infoData->username,
                    $infoData->virtual_accnum,
                    $infoData->phone,
                    $infoData->accountsavingsp,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $infoData->branch->branch_name,
                    @$infoData->myOfficer->first_name . ' ' . @$infoData->myOfficer->last_name,
                    ($infoData->author) ? ($infoData->author->first_name . ' ' . $infoData->author->last_name) : '---',
                    $infoData->created_at,
                    $btndom,

                ];
            } else if ($page == 'all-mandates') {
                $is_root = cando('root|approve-customer');
                if (! $is_root) {
                    $btndom = "<button class='btn btn-outline-warning btn-sm' disabled>Waiting...</button>";
                } else { //activate_account
                    if ($infoData->status_id == 7) {
                        $btndom = "<button class='btn btn-outline-success btn-sm' onclick=";
                        $btndom .= "get_user_profile_details('$btnlink') >Activate</button>";
                    } else {
                        $btndelete   = url('company/' . Auth()->user()->company->short_name . '/customer/customer-delete');
                        $restriction = url('company/' . Auth()->user()->company->short_name . '/restriction');
                        $profile     = url('company/' . Auth()->user()->company->short_name . '/customer/view-profile/' . $infoData->uuid);
                        // $btndom ="<a href=''><span class='alert alert-info'>Account</span></a>";
                        $btndom = '<div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"  href="' . $btndelete . '" >Delete</a>
                            <a class="dropdown-item" href="' . $restriction . '" >Restriction</a>
                            <a class="dropdown-item" href="' . $profile . '" >Profile</a>
                        </div>
                    </div>';
                    }
                }

                $repeatTxt = "";

                if ($infoData->repeat == 'D') {
                    $repeatTxt = "Daily";
                }

                if ($infoData->repeat == 'W') {
                    $repeatTxt = "Weekly";
                }

                if ($infoData->repeat == 'M') {
                    $repeatTxt = "Monthly";
                }

                if ($infoData->repeat == 'Y') {
                    $repeatTxt = "Yearly";
                }

                $statusBadge = $infoData->status_id == 1 ? "success" : ($infoData->status_id == 7 ? 'warning' : 'danger');

                $statusBtn = "<span class='badge mb-2 me-4 badge-$statusBadge' >$infoData->status</span>";

                $freqText = $infoData->frequency == 1 ? $infoData->frequency . " Once " : $infoData->frequency . ' times ';

                $freqText .= $repeatTxt;

                $json['data'][] = [
                    $infoData->mandate_code,
                    $infoData->payer_name,
                    $infoData->amount,
                    $infoData->start_date,
                    $infoData->end_date,
                    $infoData->first_payment_date,
                    $freqText,
                    $statusBtn,
                    date_format($infoData->created_at, 'd-m-Y'),
                    $btndom,

                ];
            } else if ($page == 'export-all-customer') {

                $is_root = cando('root|approve-customer');
                if (! $is_root) {
                    $btndom = "<button class='btn btn-outline-warning btn-sm' disabled>Waiting...</button>";
                } else { //activate_account
                    if ($infoData->status_id == 7) {
                        $btndom = "<button class='btn btn-outline-success btn-sm' onclick=";
                        $btndom .= "get_user_profile_details('$btnlink') >Activate</button>";
                    } else {
                        $btndelete   = url('company/' . Auth()->user()->company->short_name . '/customer/customer-delete');
                        $restriction = url('company/' . Auth()->user()->company->short_name . '/restriction');
                        $profile     = url('company/' . Auth()->user()->company->short_name . '/customer/view-profile/' . $infoData->uuid);
                        // $btndom ="<a href=''><span class='alert alert-info'>Account</span></a>";
                        $btndom = '<div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"  href="' . $btndelete . '" >Delete</a>
                            <a class="dropdown-item" href="' . $restriction . '" >Restriction</a>
                            <a class="dropdown-item" href="' . $profile . '" >Profile</a>
                        </div>
                    </div>';
                    }
                }
                $loanbalance = loan::where(['user_id' => $infoData->id, 'loan_status_id' => 1])->sum('loanBalance');

                $json['data'][] = [
                    $loop,
                    $infoData->first_name . ' ' . $infoData->last_name,
                    $infoData->virtual_accnum,
                    $infoData->username,
                    $infoData->phone,
                    $infoData->sex,
                    $infoData->address,
                    $infoData->accountsavingsp,
                    $infoData->branch->branch_name,
                    $infoData->bvn,
                    $infoData->nin,
                    $infoData->updated_at,
                    ($infoData->author) ? ($infoData->author->first_name . ' ' . $infoData->author->last_name) : '---',
                    ($infoData->author) ? ($infoData->author->first_name . ' ' . $infoData->author->last_name) : '---',
                    $infoData->created_at,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $infoData->email,
                    $infoData->dob,
                    $infoData->bankcode,
                    "11111" . $infoData->id,
                    $infoData->account_reference,
                    $loanbalance,
                    '-',
                ];
            } else if ($page == 'esusu-mobile') {
                $shortname = auth()->user()->company->short_name;
                $btn       = $infoData->is_agent;
                if ($infoData->is_agent == 1) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     Action
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item" href="/company/' . $shortname . '/customer/view-profile/' . $infoData->uuid . '">View Profile</a>
                      <a class="dropdown-item" href="#" onclick=disable(' . $infoData->id . ')>Disable</a>
                      <a class="dropdown-item" href="#">View Esusu</a>
                    </div>
                  </div>';
                } else if ($infoData->is_agent == 2) {
                    $btn = "<a   class='btn btn-outline-success btn-sm' onclick=";
                    $btn .= " approved(" . $infoData->id . ") >Approve</a>";
                } else if ($infoData->is_agent == 3) {
                    $btn = "<a   class='btn btn-outline-success btn-sm' onclick=";
                    $btn .= " approved(" . $infoData->id . ")>Enable</a>";
                }
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->first_name,
                    $infoData->last_name,
                    $infoData->email,
                    $infoData->virtual_accnum,
                    number_format($infoData->balance, 2) . auth()->user()->company->currency->symbol,
                    $btn,
                ];
            }
        }

        return $json;
    }

    public function fetchUserProduct($id)
    {
        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('root|allow-staff-view-all-customers');
        if (! $is_root) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $user = $user->first();
        if ($user) {
            $product = wb_account_details::with('user_wallet')->with('accountType')->where('user_id', $user->id)->get();
            return response(json_encode($product));
        } else {
            return response(json_encode(["message" => "data no found "]));
        }
    }
    public function updateSMS($id)
    {
        if (! cando('control-customer-notification|manage-customer|root')) {
            return response(json_encode(["message" => "Staff Notification Control Restricted", "status" => 0]));
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return json_encode(['status' => 0, 'message' => 'staff restricted!']);
        }

        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('root|control-customer-notification');
        if (! $is_root) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $user = $user->first();
        if ($user) {
            if ($user->sms) {
                $user->sms = 0;
            } else {
                $user->sms = 1;
            }
            $user->save();
            return response(json_encode(["message" => "true", "status" => 1, "sms" => $user->sms]));
        } else {
            return response(json_encode(["message" => "data no found ", "status" => 0]));
        }
    }
    public function updateWhatsApp($id)
    {
        if (! cando('control-customer-notification|root')) {
            return response(json_encode(["message" => "Staff Notification Control Restricted", "status" => 0]));
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return json_encode(['status' => 0, 'message' => 'staff restricted']);
        }

        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('root|control-customer-notification');
        if (! $is_root) {

            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $user = $user->first();

        if ($user) {
            if ($user->whatsapp) {
                $user->whatsapp = 0;
            } else {
                $user->whatsapp = 1;
            }
            $user->save();
            return response(json_encode(["message" => "true", "status" => 1, "whatsapp" => $user->whatsapp]));
        } else {
            return response(json_encode(["message" => "data no found ", "status" => 0]));
        }
    }
    public function getSMSstatus($id)
    {
        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('root|control-customer-notification');
        if (! $is_root) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $user = $user->first();
        if ($user) {

            return response(json_encode(["message" => "true", "status" => 1, "whatsapp" => $user->whatsapp, "sms" => $user->sms]));
        } else {
            return response(json_encode(["message" => "data no found ", "status" => 0]));
        }
    }
    public function RestrictCustomer(Request $request)
    {

        if (! cando('customer-restriction|root')) {
            return response(json_encode(['status' => 0, 'message' => 'Access Denied']));
        }

        \logInfo($request->all(), "Restrict Customer request");

        $validation = Validator::make($request->all(), [
            'permission' => 'required',
            'customer'   => 'required',
            'remark'     => 'required',
        ]);

        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }

            return response(json_encode(['status' => 0, 'message' => $errors]));
        }

        $user = User::where('uuid', $request->customer)->first();
        \logInfo($user, "User restriction log things");

        return $this->restrictUserAccount($user, $request->permission, $request->remark);
    }

    public function restrictUserAccount($user, $restrictionId, $remark)
    {

        \logInfo($user, "User Record Restrict user acocunt method");

        $custrest           = MainCustomerRestriction::where(['user_id' => $user->id, 'customer_permission_id' => $restrictionId])->first();
        $customerPermission = CustomerPermission::where('id', $restrictionId)->first();
        if ($custrest) {
            return response(json_encode(['status' => 0, 'message' => $user->first_name . 'Customer already restricted on ' . $customerPermission->title]));
        }

        //  $user->customerPermissions()->attach($restrictionId);
        MainCustomerRestriction::create([
            'user_id'                => $user->id,
            'company_id'             => $user->company_id,
            'customer_permission_id' => $restrictionId,
            "remark"                 => $remark,
            "created_by"             => auth()->user()->id,
        ]);

        CustomerRestrictionLog::create([
            "user_id"                 => $user->id,
            "customer_permission_id"  => $restrictionId,
            "remark"                  => $remark,
            "created_by"              => auth()->user()->id,
            "restricted_by_system"    => 1,
            "restriction_log_type_id" => 1,
        ]);

        return response(json_encode(['status' => 1, 'message' => $user->first_name . ' has been restricted on ' . $customerPermission->title]));
    }

    public function RemoveRestrictCustomer(Request $request)
    {
        if (! cando('customer-restriction|root')) {
            return response(json_encode(['status' => 0, 'message' => 'Access Denied']));
        }

        $validation = Validator::make($request->all(), [
            'user_reference' => 'required|string',
            'restriction_id' => 'required',
        ]);

        if ($validation->fails()) {
            \response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $user = User::where('uuid', $request->user_reference)->first();

        if ($user) {

            $restriction = MainCustomerRestriction::find($request->restriction_id);

            if ($restriction) {
                $restrictionTypeId = $restriction->customer_permission_id;

                if ($restriction->delete()) {

                    CustomerRestrictionLog::create([
                        "user_id"                 => $user->id,
                        "customer_permission_id"  => $restrictionTypeId,
                        "remark"                  => "Restriction removed",
                        "restricted_by_system"    => 1,
                        "restriction_log_type_id" => 2,
                        "created_by"              => auth()->user()->id,
                    ]);
                    return response(json_encode(['status' => 1, 'message' => 'User Restricted Removed']));
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'Operation Failed']));
                }
            } else {
                return response(json_encode(['status' => 0, 'message' => 'Invalid User Reference passed']));
            }
        } else {
            return response(json_encode(['status' => 0, 'message' => 'Invalid User Reference passed']));
        }
    }

    public function resetUserPSW($id)
    {
        if (! cando('edit-customer|manage-customer|root')) {
            return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
        }

        try {
            $userdata = User::where('uuid', $id);
            $user     = $userdata;
            $is_root  = cando('root');
            if (! $is_root) {
                $user = $user->where('account_officer_id', auth()->user()->id);
            }
            $user->first();
            $newpsw   = generateTransactionReference();
            $password = Hash::make($newpsw);
            $userdata->update(['password' => $password]);

            $datax = [
                'subtext'        => 'Account reset for ',
                'desc'           => "Your Account Password has been Updated to <b> $newpsw </b> Login and reset",
                'name'           => $user->first_name . ' ' . $user->last_name,
                'email'          => $user->email,
                'account_number' => $user->email,
            ];
            // Mail::to($user->email)
            // ->send(new NewAccountCreated($datax));
            $sms_sent = sendSMS($userdata->id, "Your Account Password has been Updated to <b> $newpsw </b> Login and reset", true, true, 'Account reset for', $user->email);

            return response(json_encode(['status' => 1, 'message' => 'Account Password reset and sent to customer!']));
        } catch (\Throwable $th) {
            //throw $th;
            return response(json_encode(['status' => 0, 'message' => $th]));
        }
    }

    public function get_customer_list(Request $request, $companyName, $id)
    {

        \logInfo($request->all(), "Get customer list log");
        \logInfo($id, "Get customer id log");

        $user    = User::where(['company_id' => auth()->user()->company_id, 'id' => $id, 'user_type_id' => 1]);
        $is_root = cando('root|allow-staff-view-all-customers');

        $user = $user->first();
        if ($user) {

            if (isset($request->add_card)) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'User details fetched successfully',
                    'data'    => [
                        'first_name' => $user['first_name'],
                        'last_name'  => $user['last_name'],
                        'uuid'       => $user['uuid'],
                        'username'   => $user['username'],
                        'email'      => $user['email'],
                        'phone'      => $user['phone'],
                        'address'    => $user['address'],
                        'bvn'        => $user['bvn'],
                    ],
                ]);
            }

            $companySettings = company_settings::where('company_id', auth()->user()->company_id)->first();

            if ($user->wb_account_details[0]->user_wallet->balance < $companySettings->new_physical_card_charge) {
                return response(json_encode(['status' => 0, 'message' => "Insufficient Customer wallet balance"]));
            } else {

                $wbaCharges = WbaCharge::find(17);

                if (auth()->user()->company->wallet->balance < $wbaCharges->charge) {
                    return response()->json(['status' => 0, 'message' => "Switcher Error... Please contact support for assistance"]);
                } else {
                    return response()->json(
                        [
                            'status'  => 1,
                            'message' => 'User details fetched successfully',
                            'data'    => [
                                'first_name' => $user['first_name'],
                                'last_name'  => $user['last_name'],
                                'uuid'       => $user['uuid'],
                                'username'   => $user['username'],
                                'email'      => $user['email'],
                                'phone'      => $user['phone'],
                                'address'    => $user['address'],
                                'bvn'        => $user['bvn'],
                            ],
                        ]
                    );
                }
            }
        } else {
            return response(json_encode(['status' => 0, 'message' => 'Invalid User information']));
        }
    }

    public function add_account(Request $request)
    {
        if (! cando('edit-customer|manage-customer|root')) {
            return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $request->uuid])) {
            return json_encode(['status' => 0, 'message' => 'staff restriction from manage-user']);
        }

        try {
            $user = User::where(['user_type_id' => '1', 'uuid' => $request->uuid, 'company_id' => auth()->user()->company_id]);

            $is_root = cando('root|manage-customer');
            if (! $is_root) {
                $user = $user->where('account_officer_id', auth()->user()->id);
            }
            $user   = $user->first();
            $bankId = 231;
            $saving = savings_product::where(['company_id' => auth()->user()->company_id, 'id' => $request->savings_product_type])->first();

            if ($request->saving_type != 'virtual') {
                $virtual_acccount_number = rand(1000000000, 9999999999);
                $customer_reference      = rand(1000000000, 9999999999);
                if (isset($saving->code)) {
                    if ($saving->code != null) {
                        $length                  = strlen($saving->code);
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $saving->code, 0, $length);
                    }
                }
                $branchdata = branch::where(['company_id' => auth()->user()->company_id, 'currency_id' => $request->currency_id])->first();
                if (isset($branchdata->code)) {
                    if ($branchdata->code != null) {
                        $branchCodeLength = strlen($branchdata->code);
                        $branchCodeLength -= 1;
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $branchdata->code, -$branchCodeLength);
                    }
                }
            } else {
                $bank = banks::where('bank_code', $request->bankcode)->first();
                if (! $bank) {
                    $response['status'] = 0;
                    return ['status' => false, 'message' => "Account Bank Code does not exist"];
                }

                $bankId = $bank->id;

                $virtualAccount = new VirtualAccountType();

                $virtualAccount->bankCode                     = $request->bankcode;
                $virtualAccount->currencyCode                 = \auth()->user()->company->currency->code;
                $virtualAccount->firstName                    = $user->first_name;
                $virtualAccount->companyShortName             = auth()->user()->company->short_name;
                $virtualAccount->lastName                     = $user->last_name;
                $virtualAccount->email                        = $user->email;
                $virtualAccount->accountType                  = 'personal';
                $virtualAccount->phone                        = $user->phone;
                $virtualAccount->dob                          = $user->dob;
                $virtualAccount->gender                       = $request->gender;
                $virtualAccount->bvn                          = $user->bvn;
                $virtualAccount->address                      = $user->address;
                $virtualAccount->companyAccountCreationStatus = auth()->user()->company->setting->can_create_virtual_account;

                $createAccountService = new AccountCreationService();

                $response = $createAccountService->createVirtualAccount($virtualAccount);

                if (! $response['status']) {
                    $response['status'] = 0;
                    return response()->json($response);
                }
                logInfo($response, "Create Account service response");
                $customer_reference      = $response['data']['account_reference'] ?? $customer_reference;
                $virtual_acccount_number = $response['data']['virtual_account'] ?? $response['data']['account_number'];

                $user->virtual_account   = $virtual_acccount_number;
                $user->account_reference = $customer_reference;
                $user->save();

            }

            if ($saving) {
                if ($user) {
                    $wb = wb_account_details::where(['account_product_id' => $saving->id, 'user_id' => $user->id])->first();
                    if ($wb && ($request->replace_account != "yes")) {
                        return response()->json(['status' => 0, 'message' => 'Error! Account Type Already Exist']);
                    }

                    if ($request->replace_account == "yes") {
                        $wb_account_details = wb_account_details::where('user_id', $user->id)->first();
                        $wb_account_details->update([
                            'virtual_accnum' => $virtual_acccount_number,
                            'acc_reference'  => $customer_reference,
                            'bankid'         => $bankId,
                            "currency_id"    => $request->currency_id,
                        ]);
                    } else {

                        $wb_account_details = wb_account_details::create([
                            'user_id'            => $user->id,
                            'account_product_id' => $saving->id,
                            'bankid'             => $bankId,
                            'virtual_accnum'     => $virtual_acccount_number,
                            'acc_reference'      => $customer_reference,
                            'has_atm_card'       => 0,
                            "currency_id"        => $request->currency_id,
                        ]);
                        user_wallet::create([
                            'wb_account_detail_id' => $wb_account_details->id,
                            'balance'              => 0,
                        ]);
                    }

                    return response()->json(['status' => 1, 'message' => 'Account Added!']);
                } else {
                    return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
                }
            } else {
                return response()->json(['status' => 0, 'message' => 'Account Registration Failed!']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        }
    }

    public function replace_account(Request $request, $accountId)
    {
        if (! cando('edit-customer|manage-customer|root')) {
            return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
        }

        // if (!restrict_to_just_my_company(['user_uuid' => $request->uuid])) return json_encode(['status' => 0, 'message' => 'staff restriction from manage-user']);
        try {
            $user = User::where(['user_type_id' => '1', 'uuid' => $request->uuid, 'company_id' => auth()->user()->company_id]);

            $is_root = cando('root|manage-customer');
            if (! $is_root) {
                $user = $user->where('account_officer_id', auth()->user()->id);
            }

            $wb = wb_account_details::where(['id' => $accountId])->first();

            if (! $wb) {
                return response()->json(['status' => 0, 'message' => 'Error! Invalid Account provided']);
            }

            $wb->bankid = 231;
            $wb->save();

            return response()->json(['status' => 1, 'message' => 'Bank Updated Successfully']);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        }
    }

    public function view_viewprofile($company_name, $id)
    {
        $auth = auth()->user();
        if (! cando('edit-customer|view-customer-profile|manage-customer|root')) {
            return abort(403);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return abort(403);
        }
        //return json_encode(['status'=>0,'message'=>'staff restriction from manage-user']);
        $txn = $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->select('transactions.amount', 'transactions.description', 'transactions.created_date', 'transaction_types.transaction_type');
        $product = wb_account_details::whereHas('user', function ($q) use (&$id, &$auth) {
            $q->where(['uuid' => $id, 'company_id' => $auth->company_id]);
        })->get();
        $companyRef  = $auth->company->wallet->refaccount;
        $customerUrl = url('/company/customer-registration/' . $companyRef);
        $userData    = User::where(['uuid' => $id, 'company_id' => $auth->company_id])->first();

        $data = [
            'category_name'    => 'users',
            'page_name'        => 'profile',
            'page_menu'        => 'viewprofile',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'savingsproduct'   => savings_product::where('company_id', $auth->company_id)->get(),
            'user'             => $userData,
            'txn'              => $txn->where(['users.uuid' => $id, 'users.company_id' => $auth->company_id])->orderBy('transactions.id', 'desc')->paginate()->take(7),
            'product'          => $product,
            'hasRef'           => false,
        ];
        if (isset($auth->company->CustomerReferalSetting)) {
            $data['hasRef']           = true;
            $data['registrationLink'] = $customerUrl . '?referral=' . $userData->virtual_account;
        } else {
            $data['registrationLink'] = $customerUrl;
        }
        $data['totalAccountData'] =
        Customersavings::with('user')
            ->whereHas('user', function ($query) use (&$userData) {
                $query->where('user_id', $userData->id);
            })
            ->get();
        return view('company.customer.profile_view')->with($data);
    }
    public function changeproduct($wbid, Request $request)
    {
        $auth = auth()->user();
        // $wbid = $request->id;
        $productID = $request->productID;
        $account   = wb_account_details::where('id', $wbid)->whereHas('user', function ($q) use (&$auth) {
            $q->where(['company_id' => $auth->company_id]);
        })->first();
        if (! restrict_to_just_my_company(['user_id' => $account->user_id])) {
            return abort(403);
        }
        //return json_encode(['status'=>0,'message'=>'staff restriction from manage-user']);
        $checkproduct = savings_product::where(['company_id' => $auth->company_id, 'id' => $productID])->count();
        if ($productID == $account->account_product_id) {
            return response()->json(['status' => 0, 'message' => 'You cannot change to same account!']);
        }
        if (wb_account_details::where(['user_id' => $account->User->id, 'account_product_id' => $productID])->count() > 0) {
            return response()->json(['status' => 0, 'message' => 'Prodcut Already Exist!']);
        }

        if ($checkproduct > 0 && ($account)) {
            wb_account_details::where('id', $wbid)->update(['account_product_id' => $productID]);
            return response()->json(['status' => 1, 'message' => 'Account type changed!']);
        }
        return response()->json(['status' => 0, 'message' => 'Failed to change account!']);
    }
    public function sendresetPSW($id)
    {
        $user_checkself = User::where(['uuid' => auth()->user()->uuid, 'uuid' => $id, 'company_id' => auth()->user()->company_id])->count();
        if ($user_checkself == 0) {
            if (! cando('manage-staff|edit-customer|manage-customer|root')) {
                return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (manage-staff|edit-customer|manage-customer)"]);
            }

        }
        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('manage-staff|edit-customer|manage-customer|root');
        if (! $is_root && $user_checkself == 0) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }
        $getuser        = $user->first();
        $company        = company::where('id', auth()->user()->company_id)->first();
        $getcompanyName = $company->short_name;
        \logInfo(json_encode($getcompanyName), "Get Company name from Customer Controller");

        if ($getuser) {
            $newpsw = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 8))), 1, 8);
            $newPSW = bcrypt($newpsw);
            $user->update(['password' => $newPSW]);
            if ($company->id == 33) {
                $phone            = $getuser->phone;
                $transaction_type = "passwordreset";
                $code             = $newpsw;
                \logInfo(json_encode($transaction_type), "Get Transacion type from Customer Controller");
                goldencreditSMSPost($phone, $transaction_type, $code);
            } else {
                $smssent = sendSMS($getuser->id, "Your Account Password Has just Been reset! <br> Your new Password is: $newpsw <br> ($getcompanyName) You can login and reset your password!", true, true, 'Password reset');
            }

            $subtext = "Password reset";
            $reason  = "";
            $msg     = "Your Account Password Has just Been reset! <br> Your new Password is: $newpsw <br> ($getcompanyName) You can login and reset your password!";
            $this->sendTransactionReportEmail($subtext, $msg, $reason, $getuser);

            return response()->json(['status' => 1, 'message' => 'Password reset Sent!']);
        }
        return response()->json(['status' => 0, 'message' => 'Error sending reset code!']);
    }
    public function sendresetPIN($id)
    {
        $user_checkself = User::where(['uuid' => auth()->user()->uuid, 'uuid' => $id, 'company_id' => auth()->user()->company_id])->count();
        if ($user_checkself == 0) {
            if (! cando('manage-staff|edit-customer|manage-customer|root')) {
                return json_encode(['status' => 0, 'message' => "Staff is restricted from performing this operation (edit-customer|manage-customer)"]);
            }

        }

        $user    = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id]);
        $is_root = cando('manage-staff|edit-customer|manage-customer|root');
        if (! $is_root && $user_checkself == 0) {
            $user = $user->where('account_officer_id', auth()->user()->id);
        }

        $getuser = $user->first();

        if ($getuser) {
            $txnpin  = rand(1231, 7879);
            $company = company::where('id', $user->company_id)->first();
            $user->update(['pin' => $txnpin]);
            if ($company->company_id == 33) {
                $phone            = $getuser->phone;
                $transaction_type = "otp";
                $code             = $txnpin;
                \logInfo(json_encode($transaction_type), "Get Transacion type from Customer Controller");
                goldencreditSMSPost($phone, $transaction_type, $code);
            } else {
                $smssent = sendSMS($getuser->id, "Transaction Pin Has just Been reset! <br> Your new Pin is: $txnpin", true, true, 'PIn reset');
            }
            $subtext = "PIn reset";
            $reason  = "";
            $msg     = "Transaction Pin Has just Been reset! <br> Your new Pin is $txnpin";
            $this->sendTransactionReportEmail($subtext, $msg, $reason, $getuser);

            return response()->json(['status' => 1, 'message' => 'PIn reset code Sent!', 'sms' => $smssent]);
        }

        return response()->json(['status' => 0, 'message' => 'Error sending reset code!']);
    }
    public function activate_entry($id)
    {
        DB::beginTransaction();

        if (! cando('approve-customer|root')) {
            return json_encode(['status' => 0, 'message' => 'staff restriction from view-pending-customer']);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return json_encode(['status' => 0, 'message' => 'Access Denied']);
        }

        try {
            $user        = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id])->update(['status_id' => 1]);
            $companyname = auth()->user()->company->company_name;
            if ($user) {
                $data_User               = User::where(['uuid' => $id, 'company_id' => auth()->user()->company_id])->first();
                $txnpin                  = $data_User->pin;
                $bankname                = isset($data_User->bank->bank_name) ? "Bank: " . $data_User->bank->bank_name : '';
                $virtual_acccount_number = $data_User->virtual_account;
                $account_type_msg        = $data_User->wb_account_details[0]->accountType->savings_product;
                if (strlen($companyname) > 20) {
                    $companyname = substr($companyname, 0, 20) . '...';
                }

                //backhere
                if (isset(auth()->user()->company->CustomerReferalSetting->direct_comission)) {

                    $referalsetting                  = auth()->user()->company->CustomerReferalSetting;
                    $referalsetting_direct_comission = $referalsetting->direct_comission;
                    if ($referalsetting_direct_comission > 0) {
                        if ($data_User->referral_id) {
                            if (isset($data_User->ReferralUpline)) {
                                if ($data_User->ReferralUpline->company_id != auth()->user()->company_id) {
                                    $authuser            = auth()->user(); // Fetch the user model
                                    $authuser->status_id = 5;              // Update the status
                                    $authuser->save();                     // Save the changes
                                    return json_encode(['status' => 0, 'message' => 'possible fraud transaction']);
                                }
                                $wb_account_details            = wb_account_details::where('user_id', $data_User->ReferralUpline->id)->first();
                                $referalPaymentTransactionType = 111; //refPayment
                                $transactions                  = transactions::where(['account_id' => $wb_account_details->id, 'transaction_type_id' => $referalPaymentTransactionType])->count();
                                if ($transactions == 0) {

                                    $ledgerCr                      = null;
                                    $ledgerDr                      = null;
                                    $get_ledger_setting_commission = get_CR_DR_TXN_Type_Ledger($referalPaymentTransactionType, $data_User->company_id);
                                    if ($get_ledger_setting_commission) {
                                        $ledgerCr = $get_ledger_setting->ledger_cr;
                                        $ledgerDr = $get_ledger_setting->ledger_dr;
                                    }

                                    $userWallet    = $wb_account_details->user_wallet;
                                    $balanceBefore = $userWallet->balance;
                                    $balanceAfter  = $balanceBefore + $referalsetting_direct_comission;

                                    $data = [
                                        "balance_before"         => $balanceBefore,
                                        "amount"                 => $referalsetting_direct_comission,
                                        "description"            => "Referal Comission",
                                        "account_id"             => $wb_account_details->id,
                                        "branch_id"              => $data_User->branch_id,
                                        "transaction_status_id"  => 1,
                                        "transaction_reference"  => generate_txnID(),
                                        "transaction_type_id"    => $referalPaymentTransactionType, //debit 7
                                        "credit_debit_type_id"   => 1,
                                        "transaction_remarks_id" => 2,
                                        "balance_after"          => $balanceAfter,
                                        "created_date"           => date('Y-m-d'),
                                        "created_by"             => $data_User->id,
                                        "ledger_cr"              => $ledgerCr,
                                        "ledger_dr"              => $ledgerDr,
                                        "initiator"              => $data_User->id,
                                    ];
                                    $data['userParamType'] = 2;
                                    $data['userId']        = $wb_account_details->id;
                                    $data['remark']        = 2;
                                    $data['chargeamt']     = 0;
                                    $CreditCustomer        = DebitCreditcustomer($data);
                                }
                            }
                        }
                    }
                }
                DB::commit();
                $smsresponse = sendSMS($data_User->id, "Welcome to $companyname, we are excited to have you, your Pin: $txnpin, $account_type_msg No: $virtual_acccount_number, $bankname", true, true, 'Account', $virtual_acccount_number);
                return response()->json(['status' => 1, 'message' => 'Account Activated!', 'sms' => $smsresponse]);
                // return response()->json(['status' => 1, 'message' => 'Account Activated!']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }
    public function reject_entry($id)
    {
        if (! cando('view-pending-customer|root')) {
            return abort(403);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return json_encode(['status' => 0, 'message' => 'staff restriction from manage-user']);
        }

        $user = User::where('uuid', $id)->update(['status_id' => 2]);
        return $user ? json_encode(['status' => 1, 'message' => 'User account moved to Recycle section']) : json_encode(['status' => 0, 'message' => 'Operation failed, try again!']);
    }
    public function import_account_page()
    {
        if (! cando('import-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'forms',
            'page_name'        => 'wizards',
            'page_menu'        => 'import_customer',
            'menu_category'    => 'customer_management',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => auth()->user()->company->branch,
        ];
        $account_types = savings_product::where('company_id', auth()->user()->company_id)->get();
        $banks         = banks::latest()->get();
        return view('company.customer.import', [
            'account_types' => $account_types,
            'banks'         => $banks,
        ])->with($data);
    }

    public function import_OLD_wba_users(Request $request)
    {
        try {
            if (! cando('import-customer|root')) {
                return abort(403);
            }

            Excel::import(new UsersImport, $request->file('cover_pic')->store('temp'));
            $auth      = auth()->user();
            $tempuser  = tempuser::where(['company_id' => $auth->company_id])->get();
            $message   = '';
            $nosuccess = 0;
            if ($tempuser) {
                foreach ($tempuser as $tempuserdata) {
                    $tempuserdata->sms              = 0;
                    $tempuserdata->branch           = Crypt::decryptString($request->branch);
                    $tempuserdata->officer          = $request->officer;
                    $tempuserdata->account_id       = $request->account_id;
                    $tempuserdata->account_type     = $request->account_type;
                    $tempuserdata->account_bank     = $request->account_bank;
                    $tempuserdata->account_category = $request->account_category;
                    $tempuserdata->company_name     = $auth->company->company_name;
                    if (User::where('virtual_account', $tempuserdata->ac_no)->count() == 0) {
                        if ((User::where(['first_name' => $tempuserdata->first_name, 'last_name' => $tempuserdata->last_name, 'company_id' => $auth])->count() == 0)) {
                            $response = $this->import_customer_account($tempuserdata);
                            $message .= $response['message'] . "<br>";
                            if ($response['status']) {
                                $nosuccess += 1;
                            }
                        } else {
                            $message .= "Old user record id $tempuserdata->old_wba_user already exist <br>";
                        }
                    } else {
                        $message .= "Account $tempuserdata->ac_no already exist <br>";
                    }

                    tempuser::where(['id' => $tempuserdata->id])->delete();
                }
                return back()->with(['message' => "$message", 'title' => "Total of $nosuccess records uploaded", 'status' => $nosuccess]);
            }
            return back()->with(['message' => "Upload Failed!", 'title' => "Upload Error!", 'status' => 0]);
        }
        //catch exception
         catch (Exception $e) {
            $error = 'Message: ' . $e->getMessage() . ' ' . $e->getLine();
            $error = $error . ' | Invalid or Corrupt Document';
            return back()->with(['message' => "$error", 'title' => "Upload Error !", 'status' => 0]);
        }
    }
    public function import_customer_account($request)
    {
        if (! cando('import-customer|root')) {
            return abort(403);
        }

        $account_type_msg = 'Wallet';
        $auth             = auth()->user();
        $allcount         = User::where(['company_id' => $auth->company_id, 'user_type_id' => 1])->count();

        $is_root = cando('root|import-customer');
        // if (!$is_root) {
        //     $allcount = $allcount->where('account_officer_id', auth()->user()->id);
        // }
        // $allcount = $allcount->count();
        $maxcust = $auth->company->sub->plan->max_customers;
        //$maxcustID = $auth->company->sub->plan->id;
        if ($maxcust < $allcount) {
            $datax = [
                'message' => "<span style='color:red'>Total $allcount Max $maxcust Maximum Customer Reached, Upgrade Plan!</span> <br> ",
                'subtext' => "Maximum Customer Reached, Upgrade Plan!",
                'status'  => 0,
            ];
            return $datax;
        }
        $virtual_acccount_number = $request->ac_no;

        if ($request->old_wba_user != null) {
            if ($request->old_wba_user > 0) {
                if (User::where(['old_wba_user' => $request->old_wba_user])->orWhere(['virtual_account' => $virtual_acccount_number])->count() > 0) {
                    $datax = [
                        'message' => "<span style='color:red'>User already register ($request->first_name $request->ac_no)!</span>",
                        'subtext' => "User already register ($request->first_name $request->ac_no)!",
                        'status'  => 0,
                    ];
                    return $datax;
                }
            }
        }

        $company_setting  = $auth->company->setting;
        $regfee           = $company_setting->registration_fee;
        $ussd_control     = $company_setting->ussd_control;
        $whatsapp_control = $company_setting->whatsapp_control;

        $acno = time();
        if ($virtual_acccount_number == null) {
            while (User::where(['virtual_account' => $acno])->count() > 0) {
                $acno = $acno++;
            }
            $virtual_acccount_number = $acno;
        }
        $officerid          = User::where('uuid', $request->officer)->first();
        $txnpin             = rand(1231, 7879);
        $newpsw             = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 8))), 1, 8);
        $sms                = $request->sms;
        $customer_reference = $request->acc_ref; //"Kae33344c2l99" . rand(120030, 999999);
        $bankId             = $request->bank_code;

        if ($request->account_type == 'virtual' && $request->account_bank) {
            $customer_reference = "Kae33344c2l99" . rand(120030, 999999);
            if ($request->account_bank == null) {
                $datax = [
                    'message' => "<span style='color:red'>Invalid Bank, Select bank!</span>",
                    'status'  => 0,
                ];
                return $datax;
            }
            $bank   = banks::where('bank_code', $request->account_bank)->first();
            $bankId = $bank->id;
            if ($bank) {
                $datax = [
                    'message' => "<span style='color:red'>Invalid Bank ID($request->account_bank), Select Valide bank!</span>",
                    'status'  => 0,
                ];
                return $datax;
            }
            $account_type_msg = 'Virtual';
            if ($request->account_bank != '000023') {
                $wovenController = new WovenController();

                $accountName = $request->account_category == 'coperate' ? $request->company_name : $request->first_name . ' ' . $request->last_name;

                $response = $wovenController->createCustomerAccount($accountName, $request->email, $request->phone, $request->account_bank, $customer_reference);
                if (! $response['status']) {
                    $response['status']  = 0;
                    $response['message'] = '<br><span style="color:red">Error creating Virtual Account for ' . $request->ac_no . '</span>';
                    return $response;
                }
                $virtual_acccount_number = $response['data']['vnuban'];
            } else {
                $providusController = new ProvidusController();

                $accountName = $request->account_category == 'coperate' ? $request->company_name : $request->first_name . ' ' . $request->last_name;
                $response    = $providusController->createAccount($accountName);

                logInfo($response, "Providus Account creation");

                if (! $response['status']) {
                    $response['status']  = 0;
                    $response['message'] = 'Error creating Virtual Account for ' . $request->ac_no;
                    return $response;
                }
                $virtual_acccount_number = $response['data']['account_number'];
                //Providus Account creation
            }
        }
        if ($request->old_wba_user != null) {
            $request->old_wba_user = null;
        }
        $user = User::create([
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'uuid'               => uniqid(),
            'phone'              => $request->phone,
            'username'           => $request->username,
            'pin'                => $txnpin,
            'status_id'          => 1,
            'bvn'                => $request->bvn,
            'email'              => $request->email,
            'account_officer_id' => $officerid->id,
            'branch_id'          => $request->branch,
            'user_type_id'       => 1,
            'company_id'         => $auth->company_id,
            'created_by'         => $auth->id,
            'address'            => $request->address,
            'dob'                => $request->dob,
            'sms'                => $sms,
            'bank_id'            => $bankId,
            'virtual_account'    => $virtual_acccount_number,
            'password'           => Hash::make($newpsw),
            'sex'                => $request->gender,
            'ussd_status_id'     => $ussd_control == 1 ? 1 : 2,
            'whatsapp_status_id' => $whatsapp_control,
            'account_reference'  => $customer_reference,
            'old_wba_user'       => $request->old_wba_user,
        ]);
        $wb_account_details = wb_account_details::create([
            'user_id'            => $user->id,
            'account_product_id' => $request->account_id,
            'bankid'             => $bankId ?? 25,
            'virtual_accnum'     => $virtual_acccount_number,
            'acc_reference'      => $request->acc_reference == null ? $virtual_acccount_number : $customer_reference,
            'has_atm_card'       => 0,
        ]);
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(24, $auth->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => 0,
            "amount"                 => $request->balance ?? 0,
            "withdrawal_charge"      => 0,
            "description"            => 'Account Import Registration',
            "account_id"             => $wb_account_details->id,
            "branch_id"              => $user->branch_id,
            "transaction_status_id"  => 1,
            "company_id"             => $auth->company_id,
            "transaction_reference"  => generate_txnID(),
            "transaction_type_id"    => 1, //debit 7
            "credit_debit_type_id"   => 1, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "balance_after"          => $request->balance ?? 0,
            "created_date"           => date('Y-m-d H:i:s'),
            "created_by"             => $auth->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);
        if ($request->loanBalance > 0) {
            $request->balance -= $request->loanBalance;
        }
        user_wallet::create([
            'wb_account_detail_id' => $wb_account_details->id,
            'balance'              => ($request->balance != null) ? $request->balance : 0,
        ]);
        $companyname = $auth->company->company_name;
        $datax       = [
            'message' => "Registered $virtual_acccount_number Successful ",
            'status'  => 1,
        ];
        return $datax;
    }
    public function get_branch_staff($company_name, $id)
    {
        $user = User::where(['company_id' => auth()->user()->company_id, 'branch_id' => $id, 'user_type_id' => 2])->get();

        return json_encode($user);
    }
    public function get_specific_user_by_uuid($id)
    {
        $auth = auth()->user();
        if (! cando('edit-customer|manage-customer|view-pending-customer|view-customer-profile|root')) {
            return abort(403);
        }

        if (! restrict_to_just_my_company(['user_uuid' => $id])) {
            return json_encode(['status' => 0, 'message' => 'staff restriction from manage-user']);
        }

        $user = User::where(['company_id' => $auth->company_id, 'user_type_id' => 1, 'uuid' => $id])->first();
        // href="'.url("company/".$auth->company->short_name."/customer/customer_profile/".$user->uuid.')"
        $editbtn = (cando('edit-customer|root')) ? '<a href="' . url('/company/' . $auth->company->short_name . '/customer/customer_profile/' . $user->uuid) . '"  class="mt-2 edit-profile"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-3"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></a></div><div class="text-center user-info">' : '';

        echo '<div class="d-flex justify-content-between">
        <h3>Profile</h3>' . $editbtn;

        $logo = url(str_replace('public', 'storage', $user->profile_pic));
        $logo = ($user->profile_pic == null) ? "http://safe.wbalite.com/wba/public/passports/default_image.jpg" : $logo;
        echo "<img src='$logo'    alt='avatar' width='100'>
        <p >$user->first_name $user->last_name</p>
        </div>
        <div> ";
        echo '<div >
            <ul class="contacts-block list-unstyled">
                <li class="contacts-block__item">
                     ' . $user->username . '
                </li>
                <li class="contacts-block__item">
                    ' . $user->dob . '|' . $user->sex . '
                </li>
                <li class="contacts-block__item">
                   ' . $user->address . '
                </li>
                <li class="contacts-block__item">
                    <a href="mailto:' . $user->email . '">' . $user->email . '</a>
                </li>
                <li class="contacts-block__item">
                     ' . $user->phone . '
                </li>

            </ul>';
        $userbtn       = "activate_account('$user->uuid')";
        $userrejectbtn = "reject_account('$user->uuid')";
        echo '<center><button type="button" data-dismiss="modal" class="btn btn-success" onclick="' . $userbtn . '">Approve</button>
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="' . $userrejectbtn . '">Reject</button></center>
        </div>
      </div>';
    }

    public function send_batch_delete_restore_account(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required',
            'Userid' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status'  => 0,
            ];
            // return redirect()->back()->with('fail',$validator->messages());
            return response()->json($datax);
        }
        $success = 0;
        $action  = $request->action;
        $what    = 'affected';
        foreach ($request->input('Userid') as $userID) {
            $oop = 0;
            if ($action == 'delete') {
                $what = 'Permanently Deleted';
                // $oopdata = $this->delete_user($userID);
                $oop = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->first();
                if ($oop) {
                    $oop->status_id = 12;
                    $oop->phone     = "_" . $oop->phone;
                    $oop->email     = "_" . $oop->email;
                    $oop->username  = "_" . $oop->username;
                    // $oop->company_id = "_".$oop->company_id;

                    $oop->save();
                    // if ($oopdata['status'] == false) {
                    //     return response()->json(['status' => 0, 'message' => $oopdata['message']]);
                    // }
                    $oop = 1;
                }
            } else if ($action == 'restore') {
                $what = 'Restored';
                $oop  = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->update(['status_id' => 1]);
            } else if ($action == 'trash') {
                $what = 'moved to Trash';
                $oop  = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->update(['status_id' => 9]);
            }
            if ($oop) {
                $success++;
            }
        }
        $tag = 'user';
        if ($success > 1) {
            $tag = 'users';
        }

        return response()->json(['status' => 1, 'message' => "$success $tag where $what!"]);
    }
    public function delete_user($userID)
    {
        try {
            $alldelete = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->first();
            $userID    = $alldelete->id;
            NextOfKinDetail::where(['user_id' => $userID])->delete();
            CustomerOverdraft::where(['user_id' => $userID])->delete();
            Notification::where(['user_id' => $userID])->delete();
            if ($alldelete) {
                $wallets = wb_account_details::where(['user_id' => $userID])->get();
                foreach ($wallets as $wbiddata) {
                    $wbid  = $wbiddata->id;
                    $esusu = Esusu::where('wb_account_detail_id', $wbid)->first();
                    if ($esusu) {
                        Esusulog::where('esusu_id', $esusu->id)->delete();
                        EsusuTXN::where('esusu_id', $esusu->id)->delete();
                        PendingEsusu::where('esusuid', $esusu->id)->delete();
                        Esusu::where('wb_account_detail_id', $wbid)->delete();
                    }

                    $alltxn = transactions::where(['account_id' => $wbid])->get();
                    if ($alltxn) {
                        foreach ($alltxn as $dataTXN) {
                            savings_product::where('transaction_id', $dataTXN->id)->delete();
                            $alldelete = transactions::where(['id' => $dataTXN->id])->delete();
                        }
                    }

                    Investmentbooking::where('wb_accountdetails_id', $wbid)->delete();
                    DomAccount::where('wb_account_detail_id', $wbid)->delete();
                    user_wallet::where('wb_account_detail_id', $wbid)->delete();
                    Temp_Charges::where('wb_account_detail_id', $wbid)->delete();
                    echo "deleting account $wbid";
                }
                CustomerRestrictionLog::where(['user_id' => $userID])->delete();
                MainCustomerRestriction::where(['user_id' => $userID])->delete();
                CustomerRestriction::where(['user_id' => $userID])->delete();
                CardRequestLog::where(['user_id' => $userID])->delete();
                loan::where(['user_id' => $userID])->delete();
                guarantor::where(['user_id' => $userID])->delete();
                wb_account_details::where(['user_id' => $userID])->delete();
                $alldelete = User::where(['id' => $userID])->delete();
                return ['status' => true, 'message' => 'Done user: '+$userID];
            }
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage() . $e->getLine()];
        }
    }
    public function trash($userID)
    {
        $oop = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->update(['status_id' => 9]);
        if ($oop) {
            return response()->json(['status' => 1, 'message' => "User moved to trash!"]);
        } else {
            return response()->json(['status' => 0, 'message' => "Fail to perform operation!"]);
        }
    }
    public function restore($userID)
    {
        $oop = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->update(['status_id' => 1]);
        if ($oop) {
            return response()->json(['status' => 1, 'message' => "User account restored!"]);
        } else {
            return response()->json(['status' => 0, 'message' => "Fail to perform operation!"]);
        }
    }
    public function permanent_delete($userID)
    {
        // $oop = $this->delete_user($userID);
        $oop = User::where(['uuid' => $userID, 'company_id' => auth()->user()->company_id])->first();
        if ($oop) {
            $oop->status_id = 12;
            $oop->phone     = "_" . $oop->phone;
            $oop->email     = "_" . $oop->email;
            $oop->username  = "_" . $oop->username;
            // $oop->company_id = ".".$oop->company_id;
            $oop->save();
            return response()->json(['status' => 1, 'message' => "User account Permanently deleted!"]);
        } else {
            return response()->json(['status' => 0, 'message' => "Fail to perform operation!"]);
        }
    }
    public function allCustomer()
    {
        if (! cando('approve-customer|view-all-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'all_account',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => auth()->user()->company->branch,
            'product'          => savings_product::where('company_id', auth()->user()->company_id)->get(),
        ];
        return view('company.customer.all-account')->with($data);
    }
    public function exportallCustomer()
    {
        if (! cando('approve-customer|view-all-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'export_all_account',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => auth()->user()->company->branch,
            'product'          => savings_product::where('company_id', auth()->user()->company_id)->get(),
        ];
        return view('company.customer.export-all-account')->with($data);
    }
    public function HardResetresetSystem(Request $request)
    {
        try {
            $company = auth()->user()->company;
            $users   = User::where(['company_id' => $company->id, 'user_type_id' => 1])->get();
            foreach ($users as $user) {
                $alldelete = $user;
                $userID    = $user->id;
                echo "User ID :$userID<br>";
                NextOfKinDetail::where(['user_id' => $userID])->delete();
                CustomerOverdraft::where(['user_id' => $userID])->delete();
                CryptoTransferTransaction::where(['user_id' => $userID])->delete();
                echo "Overdraf TXN  Deleted<br>";

                foreach ($alldelete->wb_account_details as $wbiddata) {
                    $wbid  = $wbiddata->id;
                    $esusu = Esusu::where('wb_account_detail_id', $wbid)->first();
                    if ($esusu) {
                        Esusulog::where('esusu_id', $esusu->id)->delete();
                        EsusuTXN::where('esusu_id', $esusu->id)->delete();
                        PendingEsusu::where('esusuid', $esusu->id)->delete();
                        Esusu::where('wb_account_detail_id', $wbid)->delete();
                        echo "Esusu TXN Deleted <br>";
                    }
                    PendingSaving::where(['account_type' => $wbiddata->virtual_accnum])->delete();
                    $alldelete = transactions::where(['account_id' => $wbid])->delete();
                    Investmentbooking::where('wb_accountdetails_id', $wbid)->delete();
                    DomAccount::where('wb_account_detail_id', $wbid)->delete();
                    user_wallet::where('wb_account_detail_id', $wbid)->update(['balance' => 0]);
                    Temp_Charges::where('wb_account_detail_id', $wbid)->delete();
                    echo "Investment,Dom Account and other  TXN Deleted <br>";
                }

                $createdby = transactions::where(['created_by' => $userID])->get();

                foreach ($createdby as $datacreated) {
                    echo "$datacreated->id on $company->id";
                    CustomerOverdraft::where(['transaction_id' => $datacreated->id])->delete();
                    EsusuTXN::where('transaction_id', $datacreated->id)->delete();
                    transactions::where(['id' => $datacreated->id])->delete();
                    echo "Unlinked staff created txn<br>";
                }
                //die();

                StaffWallet::where('user_id', $userID)->update(['balance' => 0]);
                CustomerRestrictionLog::where(['user_id' => $userID])->delete();
                MainCustomerRestriction::where(['user_id' => $userID])->delete();
                CustomerRestriction::where(['user_id' => $userID])->delete();
                CardRequestLog::where(['user_id' => $userID])->delete();
                $loan = loan::where(['user_id' => $userID])->get();
                if ($loan) {
                    foreach ($loan as $loanData) {
                        $comment = LoanComment::where(['loan_id' => $loanData->id])->get();
                        if ($comment) {
                            foreach ($comment as $commentData) {
                                LoanCommentAttachment::where(['loan_comment_id' => $commentData->id])->delete();
                                LoanComment::where(['id' => $commentData])->delete();
                            }
                        }
                        AddedLoanLog::where(['loan_id' => $loanData->id])->delete();
                        payLoanPenalty::where(['loan_id' => $loanData->id, 'status' => 'paid'])->delete();
                        LoanRepayment::where(['loan_id' => $loanData->id])->delete();
                        LoanGuarantor::where(['loan_id' => $loanData->id])->delete();
                        PendingRepayment::where(['loan_id' => $loanData->id])->delete();
                        echo "Loan Data Deleted<br>";
                    }
                    loan::where(['user_id' => $userID])->delete();
                    echo "Loan txn Deleted<br>";
                }
                guarantor::where(['user_id' => $userID])->delete();
                //Next_of_kin
                if ($request->customer != null) {
                    var_dump($this->delete_user($user->uuid));
                    // echo "User Deleted";

                }
                if ($request->allproduct != null) {
                    savings_product::where('company_id', $company->id)->delete();
                    esusu_types::where('company_id', $company->id)->delete();
                    InvestmentProduct::where('company_id', $company->id)->delete();
                    loan_product::where('company_id', $company->id)->delete();
                }
                echo "Done hello";
            }
            echo "Hard Reset Completed on ! " . count($users) . " Users";
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage() . $e->getLine()];
        }
    }
    public function transferCustomer(Request $request)
    {
        if (! cando('root')) {
            return abort(403);
        }

        $validator = Validator::make($request->all(), [
            'Userid'    => 'required',
            'pin'       => 'required',
            'accountNo' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->all()]);
        }
        $array  = [];
        $auth   = auth()->user();
        $authID = $auth->company_id;

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        try {
            //generateOtp
            $company_wallet = company_wallet::with('company')->where('vaccount', $request->accountNo)->first();

            if ($company_wallet && count($request->Userid) > 0) {
                if ($company_wallet->company->transaction_pin != $request->pin) {
                    return response()->json(['status' => false, 'message' => "Invalid Transaction Pin!"]);
                }

                $query = User::where('company_id', $authID);
                $query->where(function ($query) use ($request) {
                    for ($i = 0; $i < count($request->Userid); $i++) {
                        $uuid = $request->Userid[$i];
                        $query->orwhere(['uuid' => $uuid]);
                    }
                });
                $query                     = $query->get();
                $user                      = User::where(['company_id' => $authID])->first();
                $user->otp                 = generateOtp();
                $user->otp_expiration_date = date("Y-m-d h:i:s", strtotime(date("Y-m-d h:i:s") . "+ 1 minute"));
                $user->save(); //otp_expiration_date
                $smsstate = sendSMS($user->id, "To complete customer transfer Enter OTP: $user->otp", true, false, 'Customer Transfer', '', 0, false, 2);

                return response()->json(['status' => true, 'company' => $company_wallet->company->company_name, 'company_ref' => $company_wallet->company->company_reference, 'customers' => $query, 'comp' => $smsstate]);
            } else {
                return response()->json(['status' => false, 'message' => 'Company not found']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'fail to load data' . $e->getMessage()]);
        }
    }
    public function doTransfer(Request $request)
    {
        if (! cando('root')) {
            return abort(403);
        }

        $validator = Validator::make($request->all(), [
            'customers' => 'required',
            'otp'       => 'required',
            'accountNo' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->all()]);
        }
        try {
            $auth   = auth()->user();
            $authID = $auth->company_id;
            $user   = User::where(['company_id' => $authID, 'id' => $auth->id])->first();
            if ($user->otp_expiration_date < date('Y-m-d h:i:s')) {
                return response()->json(['status' => false, 'message' => "OTP Expired!"]);
            }

            if ($user->otp != $request->otp) {
                return response()->json(['status' => false, 'message' => "OTP Verification Failed!"]);
            }

            // company_ref
            $company_wallet = company_wallet::with('company')->where('vaccount', $request->accountNo)->first();
            if (! $company_wallet) {
                return response()->json(['status' => false, 'message' => "Cannot verify company!"]);
            }

            $company = $company_wallet->company;

            if ($company->id == $authID) {
                return response()->json(['status' => false, 'message' => "Cannot transfer to same company!"]);
            }

            $query = User::where('company_id', $authID);
            $query->where(function ($query) use ($request) {
                for ($i = 0; $i < count($request->customers); $i++) {
                    $uuid = $request->customers[$i];
                    $query->orwhere(['uuid' => $uuid]);
                }
            });
            $query         = $query->get();
            $countTransfer = 0;
            if ($query) {
                foreach ($query as $queryData) {
                    if ($this->logTransfer($auth, $queryData, $company->id)) {
                        $query = User::where('id', $queryData->id)->update(['company_id' => $company->id]);
                        $countTransfer++;
                    }
                }
            }
            $receiver      = User::where(['company_id' => $company->id])->first();
            $sender        = $user->first_name . ' ' . $user->last_name;
            $senderCompany = $user->company->company_name;
            if (strlen($senderCompany) > 20) {
                $senderCompany = substr($senderCompany, 0, 15);
            }
            if (strlen($sender) > 20) {
                $sender = substr($sender, 0, 15);
            }

            $smsstate  = sendSMS($user->id, "$countTransfer Customer Transfered out of your company Successfully", true, false, 'Customer Transfer', '', 0, false, 1);
            $smsstateR = sendSMS($receiver->id, "$countTransfer customer was migrated to your organization from " . $senderCompany . " initiated by " . $sender, true, false, 'Customer Transfer', '', 0, false, 1);

            return response()->json(['status' => true, 'message' => "$countTransfer Customer Transfered Successfully!"]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => "Transfer Failed!"]);
        }
    }
    public function logTransfer($auth, $user, $company)
    {
        $logCustomerTransfer                         = new logCustomerTransfer;
        $logCustomerTransfer->user_id                = $user->id;
        $logCustomerTransfer->created_by             = $auth->id;
        $logCustomerTransfer->sender_company_id      = $user->company_id;
        $logCustomerTransfer->destination_company_id = $company;
        if ($logCustomerTransfer->save()) {
            return true;
        } else {
            return false;
        }
    }
    public function view_accounts()
    {
        if (! cando('root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'view_account_by_branch',
            'page_menu'        => 'view_account_by_branch',
            'menu_category'    => 'view_account_by_branch',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => branch::where('company_id', auth()->user()->company_id)->get(),
        ];
        // $account_types = savings_product::where('company_id', auth()->user()->company_id)->get();
        // $preferredBankId = auth()->user()->company->setting->preferred_bank_id;
        // $selectedBank = banks::find($preferredBankId);
        // $banks = banks::where('in_use', 1)->get();
        //currency

        return view('company.statement.view_user_by_currency', [
            // 'account_types' => $account_types,
            // 'selectedbank' => $selectedBank,
            // 'banks' => $banks
        ])->with($data);
    }
    public function view_accounts_data(Request $request)
    {
        $search = $request->query('search', ['value' => '', 'regex' => false]);
        $draw   = $request->query('draw', 0);
        $start  = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order  = $request->query('order', [1, 'desc']);

        $filter = $search['value'];

        $sortColumns = [
            0 => 'wb_account_details.id',
            1 => 'users.first_name',
            2 => 'users.last_name',
            3 => 'wb_account_details.virtual_accnum',
            4 => 'currencies.currency_name',
            5 => 'wb_account_details.balance',
            6 => 'wb_account_details.id',
            // 7 => 'wb_account_details.id',
        ];
        $is_root = cando('root');

        $query = wb_account_details::select('currencies.name as currency_name', 'currencies.symbol', 'users.created_by', 'wb_account_details.id', 'wb_account_details.virtual_accnum', 'users.first_name', 'users.last_name')
            ->join('currencies', 'currencies.id', '=', 'wb_account_details.currency_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->where('users.company_id', '=', auth()->user()->company_id);

        if (! $is_root) {
            $branchData = branch::where('id', auth()->user()->branch_id)->first();
            $query      = $query->where('wb_account_details.currency_id', '=', $branchData->branch_id);
        } else if ($request->currency_id) {
            $query = $query->where('wb_account_details.currency_id', '=', $request->currency_id);
        }
        if (! empty($filter)) {
            $query = $query->where(function ($query) use ($filter) {
                $query->where('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('transactions.transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
                $query->orWhere('currencies.name', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];

        $info  = $query->get();
        $sn    = 0;
        $total = 0;
        foreach ($info as $infoData) {

            $sn++;
            $btnlink = $infoData->id;
            $total += $infoData->balance;
            $created        = User::where('id', $infoData->created_by)->first();
            $json['data'][] = [
                $sn,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->currency_name,
                // number_format($infoData->balance, 2) . $infoData->symbol,
                $created->first_name . ' ' . $created->last_name,
                $infoData->created_at,
            ];
        }
        $json['data'][] = [
            '#',
            "Total",
            '-',
            '-',
            $total,
            '-',
            '-',
        ];

        return $json;
    }
    public function resetWBCurrency()
    {
        $query = User::where('company_id', auth()->user()->company_id)->get();
        if ($query) {
            foreach ($query as $queryData) {
                if (isset($queryData->wb_account_details[0])) {
                    $wb_account_details              = $queryData->wb_account_details[0];
                    $wb_account_details->currency_id = auth()->user()->company->currency->id;
                    $wb_account_details->save();
                    echo "Swaping $wb_account_details->virtual_accnum currency -from" . $wb_account_details->currency_id . "to" . auth()->user()->company->currency->id . " Done<br>";
                }
            }
        }
    }
    public function delete_account_request(Request $request)
    {
        try {
            if (isset($request->account_number) && isset($request->pin)) {
                if (User::where(['virtual_account' => $request->account_number, 'pin' => $request->pin])->update(['status_id' => 13])) {
                    return redirect()->route('delete-account-request')->with('success', 'Account deleted successfully');
                } else {
                    return redirect()->route('delete-account-request')->with('fail', 'Fail to delete account');
                }
            }
            $data = [
                'category_name'    => 'auth',
                'page_name'        => 'auth_default',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'alt_menu'         => '',
            ];
            return view('auth.delete_account')->with($data);
        } catch (Exception $e) {
            return response()->json(["status" => false, "message" => "Operation Failed" . $e->getMessage()], 400);
        }
    }

    public function check_cac(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'reg_number' => 'required',

        ]);

        if ($validator->fails()) {

            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                $loop = 0;
                foreach ($messages as $message) {
                    if ($loop > 0) {
                        $errors .= ',';
                    }
                    $errors .= "$message ";
                }
            }

            return response()->json(['status' => 0, 'message' => $errors]);
        }

        $company_id = $request->company_id;

        \logInfo($company_id, "CAC Company id");

        $company_type = substr($request->reg_number, 0, 2);

// Get the rest of the string
        $company_no = substr($request->reg_number, 2);

        $identityPassController = new IdentityPassController();

        $result = $identityPassController->verifyCAC($company_type, $company_no);

        $transactionId = generateTransactionReference();

        $chargeAmount = 3000;

        $company = Company::where('id', $company_id)->first();

        $adminUser = User::where('company_id', $company->id)->first();

        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(18, $adminUser->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        $myDescription = $adminUser->first_name . " " . $adminUser->last_name . " / " . $adminUser->company->company_name . '/' . "CAC Search Charge";

        $customerBalance = debitCompanyWithCharge($adminUser->company_id, $chargeAmount, $chargeAmount, $adminUser);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

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

        return response()->json($result, $result['status'] ? 200 : 400);

        // Separate first two characters

    }

    public function getMaxCuststomers($auth)
    {
        $sub = sub::where('company_id', $auth->company_id)->first();
        logInfo(json_encode($sub), "max customers subs");
        $maxcust = plans::where('id', $sub->plan_id)->first()->max_customers;
        logInfo(json_encode($maxcust), "max customers plans");
        return $maxcust;
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

}
