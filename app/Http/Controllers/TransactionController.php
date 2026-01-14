<?php
namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\CyberpayControllelr;
use App\Http\Controllers\ThirdParty\DojahController;
use App\Http\Controllers\ThirdParty\FlutterwaveController;
use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\KlashaController;
use App\Http\Controllers\ThirdParty\KudaBusinessController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\MonnifyController;
use App\Http\Controllers\ThirdParty\ReloadlyController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\StripeController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\CustomerRestrictionLog;
use App\Models\frequency;
use App\Models\MainCustomerRestriction;
use App\Models\Mandate;
use App\Models\PreferredEbankingPartner;
use App\Models\savings_product;
use App\Models\TempWebhook;
use App\Models\transactions;
use App\Models\transaction_types;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\wb_account_details;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{

    private $flutterwaveSecretKey;
    private $baseUrl;

    private $vtpassController;
    private $reloadlyController;

    public function __construct()
    {
        $this->middleware("custom_permission:buy-airtime-data|manage-saving|fund-wallet|bvn-verification|root|funds-transfer|mobile-money-transfer");
        if (env('APP_MODE') == 'test') {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_TEST');
        } else {
            $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY_LIVE');
        }

        $this->vtpassController   = new VTPassController();
        $this->reloadlyController = new ReloadlyController();

        $this->baseUrl = url('/');
    }

    public function fundWallet()
    {
        if (! cando('fund-wallet|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'fund_wallet',
            'page_name'        => 'html5',
            'page_menu'        => 'fund_wallet_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),

        ];

        return view('company.ebanking.fund_wallet.fund_wallet')->with($data);
    }

    public function fundWalletPage(Request $request)
    {
        $data = [
            'category_name'    => 'fund_wallet_page',
            'page_name'        => 'html5',
            'page_menu'        => 'fund_wallet_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.ebanking.fund_wallet.fund_wallet_page')->with($data);
    }
    public function transactionListType(Request $request)
    {
        $search          = $request->searchTerm;
        $transactionType = transaction_types::query();

        if ($search != null) {
            $transactionType->where(function ($transactionType) use ($search) {
                $transactionType->orWhere('transaction_type', 'like', '%' . $search . '%');
            });
        }

        $transactionType = $transactionType->paginate(10);

        $transactioninfo = [];
        if ($transactionType) {
            foreach ($transactionType as $transactionTypeData) {
                $userinfo[] = ['id' => $transactionTypeData->id, 'text' => $transactionTypeData->transaction_type];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function customerOverdraft()
    {
        if (! cando('fund-wallet|root')) {
            return abort(403);
        }

        $frequency = frequency::all();

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'customer_overdraft',
            'page_menu'        => 'customer_overdraft',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'frequencies'      => $frequency,

        ];

        return view('company.customer.overdraft.customer_overdraft')->with($data);
    }

    public function customerOverdraftPage(Request $request)
    {
        $data = [
            'category_name'    => 'customer_overdraft_page',
            'page_name'        => 'html5',
            'page_menu'        => 'customer_overdraft_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.customer.overdraft.customer_overdraft_page')->with($data);
    }

    public function mandates()
    {
        if (! cando('direct-mandate|root')) {
            return abort(403);
        }

        $banks = banks::all();

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'create_direct_debit_mandate',
            'page_menu'        => 'create_direct_debit_mandate',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'banks'            => $banks,

        ];

        return view('company.customer.mandates.mandates')->with($data);
    }

    public function mandatesPage(Request $request)
    {
        if (! cando('direct-mandate|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'create_direct_debit_mandate_page',
            'page_name'        => 'html5',
            'page_menu'        => 'create_direct_debit_mandate_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.customer.mandates.mandates_page')->with($data);
    }

    public function allMandates()
    {
        if (! cando('approve-customer|view-all-customer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html',
            'page_menu'        => 'all_mandates',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => auth()->user()->company->branch,
            'product'          => savings_product::where('company_id', auth()->user()->company_id)->get(),
        ];
        return view('company.customer.mandates.all_mandates')->with($data);
    }

    public function verifyAndReverseTransaction()
    {
        if (! cando('reverse-transactions|root')) {
            return abort(403);
        }

        $banks = banks::all();

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'verify_and_reverse_transactions',
            'page_menu'        => 'verify_and_reverse_transactions',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'banks'            => $banks,

        ];

        return view('company.transaction.verify_and_reverse_transaction.verify_and_reverse_transaction')->with($data);
    }

    public function verifyAndReverseTransactionPage(Request $request)
    {
        $data = [
            'category_name'    => 'verify_and_reverse_transactions_page',
            'page_name'        => 'html5',
            'page_menu'        => 'verify_and_reverse_transactions_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.transaction.verify_and_reverse_transaction.verify_and_reverse_transaction_page')->with($data);
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

        if (! $transaction) {
            return response()->json(['status' => 0, 'message' => "Invalid Transaction reference"]);
        }

        if ($transaction->transaction_type_id != 8) {
            return response()->json(['status' => 0, 'message' => "This transaction is not a bank transfer"]);
        }

        $startingDate    = Carbon::parse('2022-12-15 00:00:00');
        $transactionDate = Carbon::parse($transaction->created_at);

        if ($startingDate->gt($transactionDate)) {
            return response()->json(['status' => 0, 'message' => "This transaction cannot be reversed"]);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => 0, 'message' => "Transaction has already been reversed"]);
        }

        if ($transaction->transaction_remarks_id == 1 || $transaction->transaction_remarks_id == 6) {
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

    public function createMandate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "customer"         => "required|string",
            "payerName"        => "required|string",
            "payerAddress"     => "required|string",
            "emailAddress"     => "required|string|email",
            "phoneNumber"      => "required|numeric",
            "bank"             => "required",
            "accountNumber"    => "required|numeric",
            "accountName"      => "required|string",
            "narration"        => "required|string",
            "amount"           => "required|numeric",
            "variableAmounts"  => "required|string",
            "startDate"        => "required|date",
            "endDate"          => "required|date",
            "mandate_form"     => "required|mimes:jpeg,png,pdf",
            "firstPaymentDate" => "required|date",
            "subscriberCode"   => "required|string",
            "repeat"           => "required|string",
            "frequency"        => "required|numeric",
        ]);
        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }
        $variableAmounts = explode(",", $request->variableAmounts);
        $customer        = User::where(['uuid' => $request->customer, 'users.status_id' => 1])->first();
        if (! $customer) {
            return \response()->json(['status' => 0, 'message' => 'Invalid customer Id']);
        }
        try {
            $extension          = $request->file('mandate_form')->extension();
            $mandateImageBase64 = base64_encode(file_get_contents($request->file('mandate_form')));
            $image              = str_replace('data:image/png;base64,', '', $mandateImageBase64);
            $image              = str_replace(' ', '+', $image);
            $imageName          = 'mandate_form' . rand(100000, 999999) . '.png';
            $mandateImage       = storage_path() . '/public/company/customer/mandate_form/' . $imageName;
            File::put($mandateImage, base64_decode($image));
        } catch (\Throwable $th) {
            //throw $th;
        }
        $selectedBank       = banks::where("bank_code", $request->bank)->first();
        $cyberpayController = new CyberpayControllelr();
        $createMandate      = $cyberpayController->createAccount(
            auth()->user()->company->company_name . ": Loan Mandate",
            $request->payerName,
            $request->payerAddress,
            $request->emailAddress,
            $request->phoneNumber,
            $selectedBank->short_code,
            $request->accountNumber,
            $request->accountName,
            $request->narration,
            $request->amount,
            $variableAmounts,
            $request->startDate,
            $request->endDate,
            $request->firstPaymentDate,
            $request->subscriberCode,
            $request->repeat,
            $request->frequency,
            $extension,
            $mandateImageBase64
        );

        if (! $createMandate['status']) {
            $createMandate['status'] = 0;
            return response()->json($createMandate);
        }
        $createMandate['status'] = 1;

        Mandate::create([
            'user_id'            => $customer->id,
            'payer_name'         => $request->payerName,
            'pay_address'        => $request->payerAddress,
            'email_address'      => $request->emailAddress,
            'phone_number'       => $request->phoneNumber,
            'bank_id'            => $selectedBank->id,
            'account_number'     => $request->accountNumber,
            'account_name'       => $request->accountName,
            'narration'          => $request->narration,
            'variable_amounts'   => $request->variableAmounts,
            'start_date'         => $request->startDate,
            'end_date'           => $request->endDate,
            'first_payment_date' => $request->firstPaymentDate,
            'subscriber_code'    => $request->subscriberCode,
            'repeat'             => $request->repeat,
            'frequency'          => $request->frequency,
            'amount'             => $request->amount,
            'mandate_code'       => $createMandate['data']["mandateCode"],
            'status_id'          => 1,
        ]);

        return \response()->json($createMandate);
    }

    public function getDownload()
    {
        //PDF file is stored under project/public/download/info.pdf
        $file = public_path() . "/download/info.pdf";

        $headers = [
            'Content-Type: application/pdf',
        ];

        return Response::download($file, 'filename.pdf', $headers);
    }

    public function bvnVerification($company_name)
    {
        //
        if (! cando('bvn-verification|root')) {
            abort(403);
        }

        $data = [
            'category_name'    => 'forms',
            'page_name'        => 'wizards',
            'page_menu'        => 'bvn_verification_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),
        ];
        return view('company.ebanking.bvn_verification.bvn_verification')->with($data);
    }

    public function transactionList()
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'transaction_list_menu',
            'page_menu'        => 'transaction_list_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'transaction_type' => transaction_types::all(),
            'alt_menu'         => 0,
        ];
        return view('company.transaction.transaction-list')->with($data);
    }

    public function otherTransactionList()
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'other_transaction_list_menu',
            'page_menu'        => 'other_transaction_list_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.transaction.other-transaction-list')->with($data);
    }

    public function tierSettlement()
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'tier_settlements',
            'page_menu'        => 'tier_settlements',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.transaction.tier_settlement')->with($data);
    }

    public function companyTransactionList(Request $request)
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = ($request->paginate == 'all') ? false : $request->query('length', 25);
        $order       = $request->query('order', [1, 'desc']);
        $filter      = $search['value'];
        $sortColumns = [
            0  => 'id',
            1  => 'transaction_reference',
            2  => 'balance_before',
            3  => 'amount',
            4  => 'balance_after',
            5  => 'description',
            6  => 'transaction_type_id',
            6  => 'credit_debit_type_id',
            7  => 'transaction_remarks_id',
            8  => 'created_at',
            9  => 'created_at',
            10 => 'created_at',
        ];
        $mycompany = auth()->user()->company_id;
        $query     = transactions::with(['type', 'remark', 'author', 'creditDebitType'])->where('company_id', $mycompany)->where('transaction_type_id', "<>", "16")->where("transaction_type_id", "<>", "33")->where('transaction_type_id', "<>", "15")->where("transaction_type_id", "<>", "42");
        if ($request->from != null && $request->to != null) {
            $query = $query->where('created_at', '>=', $request->from)->where('created_at', '<=', $request->to);
        }
        if ($request->transaction_type != null) {
            $query = $query->where('transaction_type_id', $request->transaction_type);
        }
        if ($request->status == 'customer') {
            $query = $query->where('account_id', '!=', null);
        } else if ($request->status == 'company') {
            $query = $query->where('account_id', null);
        }
        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orWhere('transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('description', 'like', '%' . $filter . '%');
                $query->orWhere('created_at', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        $query->orderBy('id', 'desc');
        if ($length) {
            $query->take($length)
                ->skip($start);
        }
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
            $loop++;
            $newDate          = date("d-m-Y H:i:s", strtotime($infoData->created_at));
            $remarkClass      = $infoData->remark->id == 2 ? "success" : (($infoData->remark->id == 1 || $infoData->remark->id == 4) ? 'warning' : 'danger');
            $amountColor      = $infoData->credit_debit_type_id == 1 ? "green" : 'red';
            $debitCreditColor = $infoData->credit_debit_type_id == 1 ? "info" : 'warning';
            $json['data'][]   = [
                $loop,
                @$infoData->beneficiary->virtual_accnum,
                $infoData->transaction_reference,
                $infoData->balance_before,
                "<span style='color: $amountColor'><b>$infoData->amount</b></span>",
                $infoData->balance_after,
                $infoData->company_balance_before,
                "<span style='color: $amountColor'><b>$infoData->company_amount</b></span>",
                $infoData->company_balance_after,
                $infoData->description,
                $infoData->type->transaction_type,
                "<span class='badge badge-" . $debitCreditColor . "' >" . $infoData->creditDebitType->type . "</span>",
                "<span class='badge badge-" . $remarkClass . "' >" . $infoData->remark->transaction_remark . "</span>",
                $infoData->author->username,
                $newDate,
                "<a class='btn btn-primary' href='/company/transaction/receipt/$infoData->transaction_reference'>View Receipt</a>",
            ];
        }
        return $json;
    }

    public function companyOtherTransactionList(Request $request)
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'desc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'id',
            1 => 'transaction_reference',
            2 => 'balance_before',
            3 => 'amount',
            4 => 'balance_after',
            5 => 'description',
            6 => 'transaction_type_id',
            7 => 'transaction_remarks_id',
            8 => 'created_at',
            9 => 'created_at',
        ];

        $mycompany = auth()->user()->company_id;
        $query     = transactions::with(['type', 'remark', 'author'])
            ->where('company_id', $mycompany);
        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->where('transaction_type_id', "=", "16");
                $query->orWhere("transaction_type_id", "=", "33");
                $query->orWhere('transaction_type_id', "=", "15");
                $query->orWhere("transaction_type_id", "=", "42");
                $query->orWhere('transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('description', 'like', '%' . $filter . '%');
                $query->orWhere('created_at', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        $query->orderBy('id', 'desc')
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
            $loop++;
            $newDate        = date("d-m-Y H:i:s", strtotime($infoData->created_at));
            $remarkClass    = $infoData->remark->id == 2 ? "success" : (($infoData->remark->id == 1 || $infoData->remark->id == 4) ? 'warning' : 'danger');
            $json['data'][] = [
                $loop,
                $infoData->transaction_reference,
                $infoData->balance_before,
                $infoData->amount,
                $infoData->balance_after,
                $infoData->description,
                $infoData->type->transaction_type,
                "<button class='btn btn-xs btn-" . $remarkClass . "' >" . $infoData->remark->transaction_remark . "</button>",
                $infoData->author->username,
                $newDate,
            ];
        }
        return $json;
    }

    public function tierSettlementsList(Request $request)
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'desc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'id',
            1 => 'unique_reference',
            3 => 'amount',
            7 => 'name',
            8 => 'company_name',
            9 => 'created_at',
        ];

        $mycompany = auth()->user()->company_id;
        $query     = TempWebhook::with(['status', 'company'])
            ->where('company_id', $mycompany);

        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        $query->orderBy('id', 'desc')
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();

        logInfo($info, "+++++++++++++++++++++++++++++++++++++++++");
        $loop = 0;
        foreach ($info as $infoData) {
            $loop++;
            $newDate        = date("d-m-Y H:i:s", strtotime($infoData->created_at));
            $json['data'][] = [
                $loop,
                $infoData->unique_reference,
                $infoData->amount,
                $infoData->account_number,
                $infoData->company->company_name,
                $infoData->status->name,
                $newDate,
            ];
        }
        return $json;
    }

    public function otherCompanyTransactionList(Request $request)
    {
        if (! cando('view-company-transaction-history|root')) {
            return abort(403);
        }

        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'desc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'id',
            1 => 'transaction_reference',
            2 => 'balance_before',
            3 => 'amount',
            4 => 'balance_after',
            5 => 'description',
            6 => 'transaction_type_id',
            7 => 'transaction_remarks_id',
            8 => 'created_at',
            9 => 'created_at',
        ];
        $mycompany = auth()->user()->company_id;
        $query     = transactions::with(['type', 'remark', 'author'])->where('company_id', $mycompany)->where('transaction_type_id', "<>", "16")->where("transaction_type_id", "<>", "33")->where('transaction_type_id', "<>", "15")->where("transaction_type_id", "<>", "42");
        if (! empty($filter)) {

            $query->where(function ($query) use ($filter) {
                $query->orWhere('transaction_reference', 'like', '%' . $filter . '%');
                $query->orWhere('description', 'like', '%' . $filter . '%');
                $query->orWhere('created_at', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        // $query->orderBy($sortColumnName, $order[0]['dir'])
        $query->orderBy('id', 'desc')
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
            $loop++;
            $newDate        = date("d-m-Y H:i:s", strtotime($infoData->created_at));
            $remarkClass    = $infoData->remark->id == 2 ? "success" : (($infoData->remark->id == 1 || $infoData->remark->id == 4) ? 'warning' : 'danger');
            $json['data'][] = [
                $loop,
                $infoData->transaction_reference,
                $infoData->balance_before,
                $infoData->amount,
                $infoData->balance_after,
                $infoData->description,
                $infoData->type->transaction_type,
                "<button class='btn btn-xs btn-" . $remarkClass . "' >" . $infoData->remark->transaction_remark . "</button>",
                $infoData->author->username,
                $newDate,
            ];
        }
        return $json;
    }

    public function bvnVerificationPage(Request $request)
    {
        if (! cando('bvn-verification|root')) {
            abort(403);
        }

        $data = [
            'category_name'             => 'bvn_verification_page',
            'page_name'                 => 'html5',
            'page_menu'                 => 'bvn_verification_page',
            'has_scrollspy'             => 0,
            'scrollspy_offset'          => '',
            'alt_menu'                  => 0,
            'bvn_fee'                   => WbaCharge::find(25)->charge,
            'nin_fee'                   => WbaCharge::find(26)->charge,
            'driver_licence_fee'        => WbaCharge::find(27)->charge,
            'company_verification_type' => \auth()->user()->company->verification_status_id,

        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.ebanking.bvn_verification.bvn_verification_page')->with($data);
    }

    public function getNetworkOperators()
    {

        if (\auth()->user()->company->country->code == "NG") {

            $networkProviders = [
                ["id" => "mtn", "name" => "MTN Nigeria"],
                ["id" => "airtel", "name" => "Airtel Nigeria"],
                ["id" => "glo", "name" => "Glo Nigeria"],
                ["id" => "etisalat", "name" => "9Mobile (Etisalat) Nigeri"],
            ];

            return \response()->json(['status' => 1, 'message' => 'Operators fetched successfully', 'data' => $networkProviders]);
        } else {

            $reloadlyLogin = $this->reloadlyController->reloadlyLogin();

            if (isset($reloadlyLogin['access_token'])) {

                $token = $reloadlyLogin['access_token'];

                $header = ["Authorization" => "Bearer $token"];

                $countryCode = \auth()->user()->company->country->code;

                $newCode = \strtolower($countryCode);

                $url = "https://topups.reloadly.com/operators/countries/$newCode?includeBundles=false&includeData=false&includePin=false&suggestedAmounts=false&suggestedAmountsMap=false";

                $newtorkOperators = \getRequest($header, [], $url);

                $newOperators = [];

                foreach ($newtorkOperators as $operator) {
                    if ($operator['data'] == false & $operator['bundle'] == false) {
                        $newOperators[] = $operator;
                    }
                }

                return \response()->json(['status' => 1, 'message' => 'Operators fetched successfully' . $newCode, 'data' => $newOperators]);
            } else {
                return \response()->json(['status' => 0, 'message' => 'Failed to get operators', 'data' => $reloadlyLogin]);
            }
        }
    }

    public function getDataNetworkOperators()
    {

        if (\auth()->user()->company->country->code == "NG") {

            $networkProviders = [
                ["id" => "mtn-data", "name" => "MTN Nigeria"],
                ["id" => "airtel-data", "name" => "Airtel Nigeria"],
                ["id" => "glo-data", "name" => "Glo Nigeria"],
                ["id" => "etisalat-data", "name" => "9Mobile (Etisalat) Nigeri"],
            ];

            return \response()->json(['status' => 1, 'message' => 'Operators fetched successfully', 'data' => $networkProviders]);
        } else {

            $reloadlyLogin = $this->reloadlyController->reloadlyLogin();

            if (isset($reloadlyLogin['access_token'])) {

                $token = $reloadlyLogin['access_token'];

                $header = ["Authorization" => "Bearer $token"];

                $countryCode = \auth()->user()->company->country->code;

                $newCode = \strtolower($countryCode);

                $url = "https://topups.reloadly.com/operators/countries/$newCode?includeBundles=false&includeData=false&includePin=false&suggestedAmounts=false&suggestedAmountsMap=false";

                $newtorkOperators = \getRequest($header, [], $url);

                $newOperators = [];

                foreach ($newtorkOperators as $operator) {
                    if ($operator['data'] == false & $operator['bundle'] == false) {
                        $newOperators[] = $operator;
                    }
                }

                return \response()->json(['status' => 1, 'message' => 'Operators fetched successfully' . $newCode, 'data' => $newOperators]);
            } else {
                return \response()->json(['status' => 0, 'message' => 'Failed to get operators', 'data' => $reloadlyLogin]);
            }
        }
    }

    public function verifyBVN(Request $request)
    {
        if (! cando('bvn-verification|root')) {
            abort(403);
            return 0;
        }

        $validation = Validator::make($request->all(), [
            "verification_type"   => 'required',
            "verification_number" => 'required',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => 0, "message" => $validation->errors()->all()[0]];
            return response()->json($ra);
        }

        $transactionType = 0;

        if ($request->verification_type == "bvn") {
            $transactionType = 43;
            $companyCharge   = WbaCharge::find(25);
        } else if ($request->verification_type == "nin") {
            $transactionType = 46;
            $companyCharge   = WbaCharge::find(26);
        } else {
            $transactionType = 95;
            $companyCharge   = WbaCharge::find(27);
        }

        $debitAmount = $companyCharge->charge;

        $transactionType = $request->verification_type == "bvn" ? 43 : 46;

        $debitCompany = \debitCompanyWithCharge(\auth()->user()->company_id, $debitAmount, 0);

        if (! $debitCompany['status']) {
            $debitCompany['status'] = 0;
            return \response()->json($debitCompany);
        }

        $transactionId   = \generateTransactionReference();
        $CreditBVNLedger = get_CR_DR_TXN_Type_Ledger($transactionType, auth()->user()->company_id);
        if ($CreditBVNLedger) {
            $ledger_cr = $CreditBVNLedger->ledger_cr;
            $ledger_dr = $CreditBVNLedger->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set POS Agent Company Debit transaction type in ledger settings !']);
        }
        transactions::create([
            "balance_before"         => $debitCompany['balance_before'],
            "balance_after"          => $debitCompany['balance_after'],
            "amount"                 => $debitAmount,
            "withdrawal_charge"      => 0,
            "description"            => "BVN Verification Charge",
            "transaction_status_id"  => 1,
            "company_id"             => \auth()->user()->company_id,
            "transaction_reference"  => $transactionId,
            "transaction_type_id"    => $transactionType, //debit 7
            "credit_debit_type_id"   => 2,                //debit 2
            "transaction_remarks_id" => 2,                //success 2
            "balance_after"          => $debitCompany['balance_after'],
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $dojahController        = new DojahController();
        $identityPassController = new IdentityPassController();

        if ($request->verification_type == "bvn") {
            //   $verifyBVN = $dojahController->verifyBVN($request->verification_number);
            $verifyBVN = $identityPassController->verifyBVN($request->verification_number);
        } else if ($request->verification_type == "nin") {
            //$verifyBVN = $dojahController->verifyNIN($request->verification_number);
            $verifyBVN = $identityPassController->verifyNIN($request->verification_number);
        } else {
            // $verifyBVN = $dojahController->verifyDriverLicense($request->verification_number);
            $verifyBVN = $identityPassController->verifyDriverLicense($request->verification_number);
        }
        /*   if ($verifyBVN['status']) {
            $verifyBVN['status'] = 1;
            return \response()->json($verifyBVN);
        } else {
            $verifyBVN['status'] = 0;
            return \response()->json($verifyBVN);
        }
    }*/
        if (isset($verifyBVN['data']['verification']['status']) && $verifyBVN['data']['verification']['status'] == "VERIFIED") {
            $verifyBVN['status'] = 1;
            return \response()->json($verifyBVN);
        } else {
            $verifyBVN['status'] = 0;
            return \response()->json($verifyBVN);
        }
    }
    public function buyAirtime()
    {
        if (! cando('buy-airtime-data|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'airtime_n_data',
            'page_name'        => 'html5',
            'page_menu'        => 'airtime_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),
        ];

        return view('company.ebanking.airtime.buy_airtime')->with($data);
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

        // try {
        //     $destinationCurrency = $request->countryCurrency;
        //     // $sourceCurrency = $request->sourceCurrency;
        //     $sourceCurrency = $request->user()->company->currency->code;
        //     $exchangeAmount = $request->exchangeAmount;

        //     $flutterwaveController = new FlutterwaveController();

        //     $charges = 0;

        //     if (isset($request->isInverted)) {
        //         $exhangeRate = $flutterwaveController->getExchangeRater($exchangeAmount, $sourceCurrency, $destinationCurrency);

        //         if ($exhangeRate['data']['charge_by'] == "percentage") {
        //             $charges = $exhangeRate['data']['fee'] + 0.01;
        //             $charges *= $exchangeAmount;
        //         } else {
        //             $charges = $exhangeRate['data']['fee']  + ($exhangeRate['data']['fee'] / 2);
        //         }
        //     } else {
        //         $exhangeRate = $flutterwaveController->getExchangeRater($exchangeAmount, $destinationCurrency, $sourceCurrency);

        //         if ($exhangeRate['data']['charge_by'] == "percentage") {
        //             $charge = $exhangeRate['data']['fee'] + 0.01;

        //             $charges = $charge * $exchangeAmount;

        //             if ($request->user()->company->currency->code == "NGN") {
        //                 $charges = $exhangeRate["data"]["final_amount"] * $charge;

        //                 $charges /= $exhangeRate["data"]["rate"];
        //             }
        //         } else {
        //             $charges = $exhangeRate['data']['fee']  + ($exhangeRate['data']['fee'] / 2);
        //         }
        //     }
        //     $rate = $exhangeRate["data"]["rate"];
        //     $amount = $exhangeRate["data"]["final_amount"];

        //     $finalAmount = $exchangeAmount + $charges;

        //     if (!$exhangeRate['status']) {
        //         return response()->json(["status" => 0, 'message' => "Failed to Exchange rate"], 400);
        //     }
        return response()->json(
            [
                'status'  => 1,
                'message' => "Exchange rate received",
                'data'    => [
                    'rate'              => 1600,
                    'amount'            => 1600,
                    'charge'            => 0,
                    'final_amount'      => 1600,
                    'available_balance' => auth()->user()->company->wallet->balance,
                ],
            ]
        );
        // } catch (Exception $e) {

        //     return response()->json(["status" => 0, 'message' => "Failed to Verify Account"], 400);
        // }
    }

    public function buyAirtimePage(Request $request)
    {
        if (! cando('buy-airtime-data|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'airtime_n_data',
            'page_name'        => 'html5',
            'page_menu'        => 'airtime_n_data',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.ebanking.airtime.buy_airtime_page')->with($data);
    }

    public function buyData()
    {
        if (! cando('buy-airtime-data|root')) {
            return abort(403);
        }
        $data = [
            'category_name'    => 'airtime_n_data',
            'page_name'        => 'html5',
            'page_menu'        => 'data_menu',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),
        ];

        return view('company.ebanking.data.buy_data')->with($data);
    }

    public function buyDataPage(Request $request)
    {
        if (! cando('buy-airtime-data|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'airtime_n_data',
            'page_name'        => 'html5',
            'page_menu'        => 'airtime_n_data',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.ebanking.data.buy_data_page')->with($data);
    }

    public function fundsTransfer($company_name)
    {
        if (! cando('funds-transfer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'funds_transfer',
            'page_menu'        => 'funds_transfer',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),

        ];

        return view('company.funds_transfer.funds_transfer')->with($data);
    }

    public function fundsTransferPage(Request $request)
    {
        if (! cando('funds-transfer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer_page',
            'page_name'        => 'html5',
            'page_menu'        => 'funds_transfer_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.funds_transfer.funds_transfer_page')->with($data);
    }

    public function companyWalletTransferView($company_name)
    {
        if (! cando('business-transfer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'company_wallet_transfer',
            'page_menu'        => 'company_wallet_transfer',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),

        ];

        return view('company.funds_transfer.company_wallet_transfer.company_wallet_transfer')->with($data);
    }

    public function nigerianBankTransferPage(Request $request)
    {
        // if (!cando('fund-wallet|root'))  return abort(403);

        // $banks = \getBanksFromDB('long');

        // $data = [
        //     'category_name' => 'funds_transfer',
        //     'page_name' => 'html5',
        //     'page_menu' => 'nigerian_bank_transfer_page',
        //     'has_scrollspy' => 0,
        //     'scrollspy_offset' => '',
        //     'banks' => $banks,
        //     'alt_menu' => 0,
        // ];

        // if ($request->hasMessage == "true") {
        //     Session::flash($request->messageType, $request->message);
        // }

        // return view('company.funds_transfer.bank_transfer.nigerian_bank_transfer_page')->with($data);
    }

    public function nigerianBankTransferView($company_name)
    {
        if (! cando('mobile-money-transfer|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'nigerian_bank_transfer',
            'page_menu'        => 'nigerian_bank_transfer',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'banks'            => \getBanksFromDB('long'),
            'alt_menu'         => 0,
            'status'           => frequency::all(),

        ];

        return view('company.funds_transfer.bank_transfer.nigerian_bank_transfer')->with($data);
    }

    public function mobileMoneyTransferPage(Request $request)
    {
        if (! cando('fund-wallet|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'html5',
            'page_menu'        => 'mobile_money_transfer_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.funds_transfer.mobile_money_transfer.mobile_money_transfer_page')->with($data);
    }

    public function mobileMoneyTransferView($company_name)
    {
        if (! cando('fund-wallet|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'mobile_money_transfer',
            'page_menu'        => 'mobile_money_transfer',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => frequency::all(),

        ];

        return view('company.funds_transfer.mobile_money_transfer.mobile_money_transfer')->with($data);
    }

    public function companyWalletTransferPage(Request $request)
    {
        if (! cando('fund-wallet|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'html5',
            'page_menu'        => 'company_wallet_transfer_page',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];

        if ($request->hasMessage == "true") {
            Session::flash($request->messageType, $request->message);
        }

        return view('company.funds_transfer.company_wallet_transfer.company_wallet_transfer_page')->with($data);
    }
    public function wallet_to_wallet_transfer()
    {
        if (! cando('wallet-to-wallet|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'wallet_to_wallet',
            'page_menu'        => 'wallet_to_wallet',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,

        ];
        // $pageName = 'html5';
        return view('company.funds_transfer.wallet_to_wallet')->with($data);
    }

    public function make_wallet_to_wallet_transfer(Request $request)
    {
        if (! cando('wallet-to-wallet|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            "customer"            => 'required',
            "beneficiary"         => 'required',
            "sender_account"      => 'required',
            "beneficiary_account" => 'required',
            "amount"              => 'required|gt:0',
            "remark"              => 'required',
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $ra = ["status" => false, "message" => "$errors"];
            return response()->json($ra);
        }
        if ($request->sender_account == $request->beneficiary_account) {
            $ra = ["status" => false, "message" => "Transfer to same accounts is not accepted!"];
            return response()->json($ra);
        }
        $Wallet_to_Wallet_Debit  = get_CR_DR_TXN_Type_Ledger(4, auth()->user()->company_id);
        if ($Wallet_to_Wallet_Debit) {
            $Wallet_Debit_ledger_cr = $Wallet_to_Wallet_Debit->ledger_cr;
            $Wallet_Debit_ledger_dr = $Wallet_to_Wallet_Debit->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Wallet to Wallet Debit transaction type in ledger settings !']);
        }
        $Wallet_to_Wallet_Credit = get_CR_DR_TXN_Type_Ledger(32, auth()->user()->company_id);
        if ($Wallet_to_Wallet_Credit) {
            $Wallet_Credit_ledger_cr = $Wallet_to_Wallet_Credit->ledger_cr;
            $Wallet_Credit_ledger_dr = $Wallet_to_Wallet_Credit->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Wallet to Wallet Credit transaction type in ledger settings !']);
        }
        $amount          = $request->amount;
        $desc            = $request->remark;
        $backdate        = date("Y-m-d H:i:s");
        $companydata     = auth()->user()->company;
        $company_setting = $companydata->setting;
        $sender_account  = wb_account_details::where('id', $request->sender_account)->first();
        $ben_account     = wb_account_details::where('id', $request->beneficiary_account)->first();
        if ($sender_account->user->company_id == auth()->user()->company_id) {
            $account_settings = $sender_account->accountType;
            $wcharge          = $min_withdraw_charge          = $request->w_charge;
            $charge           = 0;
            if ($wcharge > 0) {
                if ($company_setting->min_withdraw_charge < $company_setting->max_withdraw_charge && $company_setting->max_withdraw_charge > 0) {
                    $minwc = $companydata->setting->min_withdraw_charge;
                    $maxwc = $companydata->setting->max_withdraw_charge;
                    $wcper = $companydata->setting->withdraw_charge_state ? '%' : $companydata->currency->symbol;
                    if ($wcharge >= $companydata->setting->min_withdraw_charge && $wcharge <= $companydata->setting->max_withdraw_charge) {
                        $chargeamt  = $companydata->setting->withdraw_charge_state ? (($wcharge / 100) * $amount) : $wcharge;
                        $charge    += $chargeamt;
                    } else {
                        return ["status" => 0, "message" => "Withdrawal charge is between " . $wcper . $minwc . " and " . $wcper . $maxwc];
                    }
                }
            }

            $min_transfer_amount      = $account_settings->minimum_withdraw;
            $max_localtransfer_amount = $account_settings->max_withdraw;

            $daily_transfer_limit_count = $account_settings->daily_withdraw_limit_count;

            //             Method # 1
            //  Mood::where('created_at', '>=', Carbon::now()->subDay())->get();

            // Method # 2
            //  Mood::whereBetween('created_at', [now()->subMinutes(1440), now()])->get();
            $transactions_timing = transactions::where('account_id', $request->sender_account)->orderBy('id', 'desc')->first();
            $time_is_ok          = true;
            if ($transactions_timing) {
                $dbtimestamp = strtotime($transactions_timing->created_at);
                if (time() - $dbtimestamp < 1 * 60) {
                    $ra = ["status" => false, "message" => "Time restriction! Transaction is allowed a munit after previouse transaction on a account!"];
                    return response()->json($ra);
                }
            }
            $alldaily_trnsfer = transactions::where('created_at', '>=', Carbon::now()->subDay())->where(['credit_debit_type_id' => 2, 'account_id' => $request->sender_account])->sum('amount');
            if ($daily_transfer_limit_count == null) {
                $ra = ["status" => false, "message" => "Set daily transfer limit in product settings!"];
                return response()->json($ra);
            }
            if (($alldaily_trnsfer + $amount) > $daily_transfer_limit_count) {
                $ra = ["status" => false, "message" => "Minimum daily transfer limit is $daily_transfer_limit_count!"];
                return response()->json($ra);
            }
            if ($amount < $min_transfer_amount) {
                $ra = ["status" => false, "message" => "Minimum transfer limit is $min_transfer_amount!"];
                return response()->json($ra);
            }
            if ($amount > $max_localtransfer_amount) {
                $ra = ["status" => false, "message" => "Maximum transfer limit is $max_localtransfer_amount!"];
                return response()->json($ra);
            }

            $sender_wallet = $sender_account->user_wallet;
            $balanceBefore = $sender_wallet->balance;
            if ($sender_wallet->balance >= ($amount + $charge)) {
                $ben_wallet_id          = $ben_account->user_wallet->id;
                $ben_balanceBefore      = $ben_account->user_wallet->balance;
                $credit_ben_wallet      = $ben_account->user_wallet->balance + $amount;
                $debit_sender_balance   = $sender_wallet->balance - ($amount + $charge);
                $debitsend              = user_wallet::find($sender_wallet->id)->update(['balance' => $debit_sender_balance]);
                $balanceAfter           = $debit_sender_balance;
                $transactionId          = generate_txnID();
                $BranchWallet           = BranchWallet::where('branch_id', $sender_account->user->branch_id)->first();
                $BranchWallet->balance -= ($amount + $charge);
                $BranchWallet->save();
                // $desc
                if ($debitsend) {
                    $transaction = transactions::create([
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $amount,
                        "withdrawal_charge"      => $charge,
                        "description"            => $desc,
                        "account_id"             => $sender_account->id,
                        "branch_id"              => $sender_account->user->branch_id,
                        "company_id"             => $sender_account->user->company_id,
                        "transaction_status_id"  => 1,
                        "transaction_reference"  => $transactionId,
                        "transaction_type_id"    => 4, //debit 7
                        "credit_debit_type_id"   => 2, //debit 2
                        "transaction_remarks_id" => 2, //success 2
                        "balance_after"          => $balanceAfter,
                        "created_date"           => $backdate,
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $Wallet_Debit_ledger_cr,
                        "ledger_dr"              => $Wallet_Debit_ledger_dr,
                    ]);
                    sendSMS($sender_account->id, "Debit Amt:$amount TXNcharge:$charge Desc: $desc Avail Bal:$balanceAfter Date:$backdate Ref:$transactionId", false, true, 'Debit Transaction');
                    //log txn for sender
                    $credit_user = user_wallet::find($ben_wallet_id)->update(['balance' => $credit_ben_wallet]);
                    if ($credit_user) {
                        $ben_balanceAfter   = $credit_ben_wallet;
                        $ben_transactionId  = generate_txnID();
                        $transaction        = transactions::create([
                            "balance_before"         => $ben_balanceBefore,
                            "amount"                 => $amount,
                            "description"            => $desc,
                            "account_id"             => $ben_account->id,
                            "branch_id"              => $ben_account->user->branch_id,
                            "company_id"             => $ben_account->user->company_id,
                            "transaction_status_id"  => 1,
                            "transaction_reference"  => $ben_transactionId,
                            "transaction_type_id"    => 32, //debit 7
                            "credit_debit_type_id"   => 1,  //debit 2
                            "transaction_remarks_id" => 2,  //success 2
                            "balance_after"          => $ben_balanceAfter,
                            "created_date"           => $backdate,
                            "parent_transaction_id"  => $transaction->id,
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $Wallet_Credit_ledger_cr,
                            "ledger_dr"              => $Wallet_Credit_ledger_dr,
                        ]);
                        $BranchWallet           = BranchWallet::where('branch_id', $ben_account->user->branch_id)->first();
                        $BranchWallet->balance += ($amount);
                        $BranchWallet->save();
                        sendSMS($ben_account->id, "Credit Amt:$amount TXNcharge:$charge Desc: $desc Avail Bal:$ben_balanceAfter Date:$backdate Ref:$ben_transactionId", false, true, 'Credit Transaction');
                        $redirectlink  = "transaction-receipt/$transactionId";
                        $ra            = ["status" => true, "message" => "Transfer  was Successful!", "redirectlink" => $redirectlink];
                        return response()->json($ra);
                    } else {
                        $ra = ["status" => false, "message" => "A Fatal Error occured!"];
                        return response()->json($ra);
                    }
                } else {
                    $ra = ["status" => false, "message" => "An Error occured!"];
                    return response()->json($ra);
                }
            } else {
                $ra = ["status" => false, "message" => "Insufficient balance"];
                return response()->json($ra);
            }
        } else {
            $ra = ["status" => false, "message" => "You dont have any access to this user account"];
            return response()->json($ra);
        }
    }

    public function initiateTransaction(Request $request)
    {

        $transactionId   = generateTransactionReference();
        $description     = $request->description;
        $transactionType = $request->transaction_type;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set ledger for this transaction type in ledger settings !']);
        }
        $checkTransaction = $this->checkLastTransaction();

        if (! $checkTransaction['status']) {
            $checkTransaction["status"] = $checkTransaction["status"] ? 1 : 0;
            return \response()->json($checkTransaction);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] != 1) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1 ? 1 : 0;
            return $checkAllowedTransaction;
        }

        if ($request->finalAmount > 0) {
            if ($transactionType != 3 || $transactionType != 1 || $transactionType != 2 || $transactionType != 6 || $transactionType != 9) {
                if ($request->amount > \auth()->user()->company->wallet->balance) {
                    return response()->json(["status" => 2, 'message' => "Insufficient Funds"]);
                }
            }
            if ($request->transaction_type == 3) {
                if (! cando('fund-wallet|root')) {
                    return response()->json(['status' => 0, 'Restricted from performing (Fund-Wallet) operation']);
                }

                $companyName = auth()->user()->company->company_name;

                $transaction = transactions::create([
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_balance_before" => auth()->user()->company->wallet->balance,
                    "company_amount"         => $request->finalAmount,
                    "description"            => "Funding Admin Wallet",
                    "transaction_reference"  => $transactionId,
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
                    "amount"          => $request->finalAmount,
                    "currency"        => auth()->user()->company->currency->code,
                    "redirect_url"    => url("company/" . auth()->user()->company->short_name . "/transaction/verify-fund-wallet"),
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
                return response()->json(["status" => 1, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => " " . generateTransactionReference()]);
            } else if ($request->transaction_type == 5) {
                if (! cando('business-transfer|root')) {
                    return response()->json(['status' => 0, 'Restricted from performing (Business-Transfer) operation']);
                }

                $description = $description ?? "Company Wallet Transfer"; //

                $checkRestriction = \checkForDebitRestriction();

                if (! $checkRestriction['status']) {
                    $checkRestriction['status'] = 0;
                    return response()->json($checkRestriction);
                }

                if (auth()->user()->company->wallet->balance >= $request->finalAmount) {

                    $charge = $request->exchangeCharge;

                    $transaction = transactions::create([
                        "balance_before"         => auth()->user()->company->wallet->balance,
                        "amount"                 => $request->amount,
                        "description"            => "Company Wallet Transfer - " . $description,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 6,
                        "company_id"             => auth()->user()->company_id,
                        "transaction_type_id"    => $request->transaction_type,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    return response()->json(
                        [
                            "status"  => 1,
                            'message' => "Transaction initialized",
                            'data'    => [
                                'charge'            => $charge,
                                'transactionId'     => $transaction->id,
                                'transactionTypeId' => $request->transaction_type,
                                'exchangeAmount'    => $request->amount,
                                'total'             => $request->finalAmount,
                            ],
                        ]
                    );
                } else {
                    return response()->json(["status" => 0, 'message' => "Insufficient Balance"]);
                }
            } else if ($request->transaction_type == 8 || $request->transaction_type == 19) {
                if (! cando('mobile-money-transfer|root')) {
                    return response()->json(['status' => 0, 'Restricted from performing (Mobile-Money-Transfer) operation']);
                }

                $amount           = 0;
                $totalAmount      = 0;
                $checkRestriction = \checkForDebitRestriction();
                if (! $checkRestriction['status']) {
                    $checkRestriction['status'] = 0;
                    return response()->json($checkRestriction);
                }
                if ($request->transaction_type == 19) {
                    $description = $description ?? "Mobile Money Transfer"; //
                    $totalAmount = $request->finalAmount;
                    $amount      = $request->finalAmount;
                } else if ($request->transaction_type == 8) {
                    $amount      = $request->finalAmount;
                    $totalAmount = $request->finalAmount;
                    $description = $description ?? "Wallet to Bank Transfer"; //
                }
                // die();
                if (auth()->user()->company->wallet->balance >= $request->finalAmount) {
                    $charge           = 0;
                    $transactionsTime = transactions::where(['company_id' => auth()->user()->company_id, "created_by" => auth()->user()->id, "amount" => $totalAmount, "transaction_type_id" => $request->transaction_type])->orderBy('id', 'desc')->first();
                    $time_is_ok       = true;
                    if ($transactionsTime) {
                        $dbtimestamp = strtotime($transactionsTime->created_at);
                        if (time() - $dbtimestamp < 0.5 * 60) {
                            return response()->json(["status" => 0, "message" => env('txn_restrict_msg')]);
                        }
                    }

                    $validateTransferAmount = $this->validateTransfer($request->finalAmount);

                    if ($validateTransferAmount['status'] == 0) {
                        return \response()->json($validateTransferAmount);
                    }

                    $customerBalanceBefore = 0;
                    $customerAmount        = 0;
                    $customerBalanceAfter  = 0;
                    $customerAccountId     = 0;

                    if ($request->transaction_type == 8) {

                        $bank = banks::where('bank_code', $request->transferBankCode)->first();

                        if (! $bank) {
                            return response()->json(['status' => 0, 'message' => 'Invalid bank code provided'], 400);
                        }

                        $transfer_log            = new Beneficiary;
                        $transfer_log->name      = $request->transferAccountName;
                        $transfer_log->bank_id   = $bank->id;
                        $transfer_log->accountNo = $request->transferAccountNumber;
                        $transfer_log->user_id   = auth()->user()->id;

                        $isUserAttached = false;
                        if ($request->customer != null) {
                            $user = User::where('uuid', $request->customer)->first();
                            if ($user) {
                                $transfer_log->user_id    = $user->id;
                                $customerBalanceBefore    = $user->accounts[0]->user_wallet->balance;
                                $customerAccountId        = $user->accounts[0]->id;
                                $transfer_log->account_id = $customerAccountId;
                                $customerAmount           = $totalAmount;
                                $isUserAttached           = true;
                            }
                        }
                    }

                    $transaction = transactions::create([
                        "balance_before"         => $customerBalanceBefore,
                        "amount"                 => $customerAmount,
                        "company_balance_before" => auth()->user()->company->wallet->balance,
                        "company_amount"         => $totalAmount,
                        "account_id"             => $customerAccountId > 0 ? $customerAccountId : null,
                        "description"            => "Mobile Money transfer - " . $description, //
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => (cando('root|direct-bank-transfer')) ? 6 : 4,
                        "company_id"             => auth()->user()->company_id,
                        "transaction_type_id"    => $request->transaction_type,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "withdrawal_charge"      => $request->withdrawal_charge,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);
                    if ($request->transaction_type == 8) {

                        $transfer_log->transaction_id = $transaction->id;

                        $transfer_log->save();
                    }
                    return response()->json(
                        [
                            "status"  => 1,
                            'message' => "Transaction initialized",
                            'data'    => [
                                'charge'            => $charge,
                                'transactionId'     => $transactionId,
                                'transactionTypeId' => $request->transaction_type,
                                'transferAmount'    => $amount,
                                'total'             => $totalAmount,
                                'userAccess'        => (cando('root|direct-bank-transfer')) ? 1 : 0,
                            ],
                        ]
                    );
                } else {
                    return response()->json(["status" => 0, 'message' => "Insufficient Balance"]);
                }
            } else if ($request->transaction_type == 11 || $request->transaction_type == 12) {
                $charge = 0;
                if (! cando('buy-airtime-data|root')) {
                    return response()->json(['status' => 0, 'Restricted from performing (Buy-Airtime-Data) operation']);
                }

                $transactionId = $this->vtpassController->vtPassRequestId();
                $transaction   = transactions::create([
                    "balance_before"         => auth()->user()->company->wallet->balance,
                    "amount"                 => $request->amount,
                    "description"            => $description, //airtime and data
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 6,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
                return response()->json(
                    [
                        "status"  => 1,
                        'message' => "Transaction initialized",
                        'data'    => [
                            'charge'            => $charge,
                            'transactionId'     => $transactionId,
                            'transactionTypeId' => $request->transaction_type,
                            'transferAmount'    => $request->amount,
                            'total'             => $request->amount,
                        ],
                    ]
                );
            }
        } else {
            return response()->json(["status" => 2, 'message' => "Invalid Transaction Amount"]);
        }
    }

    public function verifySetupFeePayment(Request $request)
    {

        $transactionId = $request->transaction_ref;

        $checkTransaction = $this->checkLastTransaction();
        // app('App\Http\Controllers\TestController')->resetImport();
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(57, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Setup Fee Payment ledger for this transaction type in ledger settings !']);
        }
        if (! $checkTransaction['status']) {
            $checkTransaction["status"] = $checkTransaction["status"] ? 1 : 0;
            return \response()->json($checkTransaction);
        }

        $checkTransaction = transactions::where('transaction_reference', $transactionId)->first();

        if (! $checkTransaction) {

            $klashaController = new KlashaController();

            $verifyTransaction = $klashaController->verifyTransaction($transactionId);

            if ($verifyTransaction['status']) {

                if ($verifyTransaction['data']['amount'] != 15 || $verifyTransaction['data']['source_currency'] != "USD") {
                    return response()->json(["status" => 0, 'message' => "Amount paid is invalid"]);
                }

                $transaction = transactions::create([
                    "balance_before"         => auth()->user()->company->wallet->balance,
                    "amount"                 => $verifyTransaction['data']['amount'],
                    "balance_after"          => auth()->user()->company->wallet->balance,
                    "description"            => "Setup fee payment", //airtime and data
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_type_id"    => 57,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                \auth()->user()->company->status_id = 7;
                auth()->user()->company->save();

                return response()->json(
                    [
                        "status"  => 1,
                        'message' => "Setup Fee Payment Processed",
                        'data'    => [
                            'transactionId'     => $transactionId,
                            'transactionTypeId' => $request->transaction_type,
                            'transferAmount'    => $request->amount,
                            'total'             => $request->amount,
                        ],
                    ]
                );
            } else {
                return response()->json(["status" => 0, 'message' => "Invalid Transaction Reference"]);
            }
        } else {
            return response()->json(["status" => 0, 'message' => "Duplicate Transaction"]);
        }
    }

    public function verifyFlutterwaveSetupFee(Request $request)
    {

        $transactionId = $request->session_id;

        $checkTransaction = transactions::where(['transaction_reference' => $transactionId])->first();

        if ($checkTransaction) {

            $flutterwaveController = new StripeController();

            $verifyTransaction = $flutterwaveController->verifyTransaction($transactionId);

            if ($verifyTransaction['status']) {

                $checkTransaction->transaction_remarks_id = 2;
                $checkTransaction->save();

                \auth()->user()->company->status_id = 7;
                auth()->user()->company->save();

                $request->session()->flash('success', "Setup fee paid Successfully");

                return redirect('/phone-verification');
            } else {
                $request->session()->flash('fail', "Invalid Transaction Reference");

                return redirect('/phone-verification');
                // return response()->json(["status" => 0, 'message' => "Invalid Transaction Reference"]);
            }
        } else {
            $request->session()->flash('fail', "Invalid Transaction Reference");

            return redirect('/phone-verification');
            // return response()->json(["status" => 0, 'message' => "Invalid Transaction Reference"]);
        }
    }

    public function initiateSetupFeeTransaction(Request $request)
    {
        // app('App\Http\Controllers\TestController')->resetImport();

        $transactionId = generateTransactionReference();
        // $ledger_Settings = get_CR_DR_TXN_Type_Ledger(57, auth()->user()->company_id);
        // logInfo($ledger_Settings);

        // if ($ledger_Settings) {
        //     $ledger_cr = $ledger_Settings->ledger_cr;
        //     $ledger_dr = $ledger_Settings->ledger_dr;
        // } else {
        //     return response()->json(['status' => 0, 'message' => 'Transaction failed, set Setup Fee Payment ledger for this transaction type in ledger settings !']);
        // }
        $flutterwaveController = new FlutterwaveController();

        $getExchangeRate = $flutterwaveController->getExchangeRater(15, \auth()->user()->company->currency->code, "USD");

        if (! $getExchangeRate['status']) {
            return \response()->json($getExchangeRate);
        }

        $finalAmount = $getExchangeRate['data']['amount'];

        $transaction = transactions::create([

            "balance_before"         => auth()->user()->company->wallet->balance,
            "amount"                 => $finalAmount,
            "description"            => "Flutterwave Setup fee payment",
            "transaction_reference"  => $transactionId,
            "company_id"             => auth()->user()->company->id,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 57,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => \auth()->user()->company_id,
        ]);

        $stripeController = new StripeController();

        $jsonData = $stripeController->initiateSetupFeeTransaction();

        $transaction->transaction_reference = $jsonData['data']['session_id'];
        $transaction->save();

        if ($jsonData['status']) {
            return response()->json(["status" => 1, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => "Transaction Initiated Successfully"]);
        } else {
            return response()->json(["status" => 0, "transactionId" => $transactionId, 'message' => $jsonData['message']]);
        }
    }

    public function buyAirtimeTransaction(Request $request)
    {

        $description = $request->description;

        $validation = Validator::make($request->all(), [
            "amount"            => 'required|numeric|gt:0',
            "transactionTypeId" => 'required',
            "transactionId"     => 'required',
            "transactionPin"    => 'required',
            "networkProviderId" => 'required',
            "mobileNumber"      => 'required',
        ], [
            'amount.required'            => 'Your amount is Required',       // custom message
            'transactionTypeId.required' => 'transactionTypeId is required', // custom message
            'transactionId.required'     => 'transactionId is Required',     // custom message
            "transactionPin"             => 'pin is required',
            "networkProviderId"          => 'providder isrequired',
            "mobileNumber"               => 'phone number is required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "All fields are required"];
            return response()->json($ra, 406);
        }

        if ($request->amount > 50000) {
            return response()->json(['status' => false, 'message' => 'Maximum Airtime amount is 50,000']);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        // $checkTransaction = $this->checkLastTransaction();

        // if (!$checkTransaction['status']) {
        //     $checkTransaction["status"] = $checkTransaction["status"] ? 1 : 0;
        //     return \response()->json($checkTransaction);
        // }

        $validateTransferAmount = $this->validateTransfer($request->amount);

        if ($validateTransferAmount['status'] == 0) {
            return $validateTransferAmount;
        }

        $validatePinCheckBalance = validatePinDebitCompanyBalance($request->transactionPin, $request->amount);

        if (! $validatePinCheckBalance["status"]) {
            $validatePinCheckBalance['status'] = $validatePinCheckBalance['responseCode'];
            return response()->json($validatePinCheckBalance, 200);
        }

        $checkVTPASSBalance = $this->checkVTPASSBalance($request->amount);
        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        if (auth()->user()->company->currency->code == "NGN") {

            $networkProvider = $request->networkProviderId == '9mobile' ? 'etisalat' : $request->networkProviderId;

            $buyAirtime = $this->vtpassController->buyAirtime($request->transactionId, $networkProvider, $request->amount, $request->mobileNumber);
        } else {
            $buyAirtime = $this->reloadlyController->buyAirtime($request->transactionId, $request->networkProviderId, $request->amount, $request->mobileNumber);
        }

        if (! $buyAirtime['status']) {
            $buyAirtime['status'] = 0;
            return response()->json($buyAirtime, 200);
        }

        $buyAirtime['status'] = 1;

        $this->updateTransactionAsSuccessful($request->transactionId, $validatePinCheckBalance['balance_after']);

        return response()->json(["status" => 1, 'message' => "Airtime purchase Successful", 'data' => ['balance' => $validatePinCheckBalance['balance_after']]]);

        // } catch (\Exception $e) {
        //         return response()->json(["status" => 0, "message" => "Internal Server error", "data" => $e->message]);
        //     }

    }

    public function buyDataTransaction(Request $request)
    {

        $description = $request->description;

        $validation = Validator::make($request->all(), [
            "amount"            => 'required|numeric|gt:0',
            "transactionTypeId" => 'required',
            "transactionId"     => 'required',
            "transactionPin"    => 'required',
            "dataBundle"        => 'required',
            "networkProviderId" => 'required',
            "mobileNumber"      => 'required',
        ], [
            'amount.required'            => 'Your amount is Required',       // custom message
            'transactionTypeId.required' => 'transactionTypeId is required', // custom message
            'transactionId.required'     => 'transactionId is Required',     // custom message
            'dataBundle.required'        => 'Data Bundle is Required',       // custom message
            "transactionPin"             => 'pin is required',
            "networkProviderId"          => 'providder isrequired',
            "mobileNumber"               => 'phone number is required',
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

        $validateTransferAmount = $this->validateTransfer($request->amount);

        if ($validateTransferAmount['status'] == 0) {
            return $validateTransferAmount;
        }

        // $checkTransaction = $this->checkLastTransaction();

        // if (!$checkTransaction['status']) {
        //     $checkTransaction["status"] = $checkTransaction["status"] ? 1 : 0;
        //     return \response()->json($checkTransaction);
        // }

        $validatePinCheckBalance = validatePinDebitCompanyBalance($request->transactionPin, $request->amount);

        if ($validatePinCheckBalance["status"] != 1) {
            return response()->json($validatePinCheckBalance, 200);
        }

        $checkVTPASSBalance = $this->checkVTPASSBalance($request->amount);
        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
        }

        if (auth()->user()->company->currency->code == "NGN") {
            $buyAirtime = $this->vtpassController->buyData($request->transactionId, $request->networkProviderId, $request->dataBundle, $request->amount, $request->mobileNumber);
        } else {
            $buyAirtime = $this->reloadlyController->buyData($request->transactionId, $request->networkProviderId, $request->amount, $request->mobileNumber);
        }

        if (! $buyAirtime['status']) {
            $buyAirtime['status'] = 0;
            return response()->json($buyAirtime, 200);
        }

        $buyAirtime['status'] = 1;

        $this->updateTransactionAsSuccessful($request->transactionId, $validatePinCheckBalance['balance_after']);

        return response()->json(["status" => true, 'message' => "Data purchase Successful", 'data' => ['balance' => $validatePinCheckBalance['balance_after']]]);
    }

    public function updateTransactionAsSuccessful($transactionId, $balance)
    {
        $transaction                         = transactions::where('transaction_reference', $transactionId)->first();
        $transaction->balance_after          = $balance;
        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function checkCompanyBalance($transactionId, $amount)
    {
        $user    = auth()->user();
        $company = $user->company;
        $wallet  = $company->wallet;

        // 2. Validate Transaction
        $transaction = transactions::where([
            'transaction_reference' => $transactionId,
            'amount'                => $amount,
        ])->orWhere([
            'id'     => $transactionId,
            'amount' => $amount,
        ])->first();

        if (! $transaction) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid Transaction ID",
            ];
        }

        // 3. Validate Amount
        if ($amount <= 0) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid Transaction Amount",
            ];
        }

        // 4. Validate Company Wallet Balance
        if ($wallet->balance < $amount) {
            return $this->handleBalanceFailure($user);
        }

        // SUCCESS → Reset failure counters
        $user->balance_fails         = 0;
        $user->transaction_pin_fails = 0;
        $user->save();

        return [
            "status"       => true,
            "responseCode" => 1,
            "message"      => "Amount and pin are valid",
            "balance"      => $wallet->balance,
        ];
    }

    private function handlePinFailure($user)
    {
        if ($user->transaction_pin_fails < 2) {
            $user->transaction_pin_fails++;
            $user->save();

            return [
                "status"       => false,
                "responseCode" => 2,
                "message"      => "Invalid Transaction PIN. Your account will be restricted after "
                . (3 - $user->transaction_pin_fails) . " more attempt(s)",
            ];
        }

        // Restrict User
        $this->restrictUserAccount(2, "Multiple Invalid transaction pin");

        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Your account has been restricted. Please contact support",
        ];
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

    public function debitCompany($request)
    {

        $amount = $request->amount;

        $response = [];

        if ($amount > 0) {

            if ($request->user()->company->wallet->balance >= $amount) {

                $request->user()->company->wallet->balance    -= $amount;
                $request->user()->company->wallet->updated_at  = Carbon::now();
                $request->user()->company->wallet->save();

                $response = ["status" => 1, 'message' => "Company Debited", 'balance' => $request->user()->company->wallet->balance];
            } else {
                $response = ["status" => 0, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => 0, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function getDataBundles(Request $request)
    {

        $dataBundles = $this->vtpassController->getDataBundles($request->network);
        return response()->json($dataBundles);
    }

    public function checkLastTransaction()
    {
        // if($request->user()->updated_at > Carbon::now()->subSeconds(5))

        $transaction = transactions::where('company_id', \auth()->user()->company_id)->orderBy('created_at', 'desc')->first();

        if ($transaction) {
            if ($transaction->created_at > Carbon::now()->subSeconds(30)) {
                return ['status' => false, 'message' => 'Duplicate Transaction. Kindly wait for at least 30 Seconds to perform another transaction'];
            } else {
                return ['status' => true, 'message' => 'Transaction Time passed'];
            }
        } else {
            return ['status' => true, 'message' => 'Transaction Time passed'];
        }
    }
    public function verifyWalletAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "accountNumber" => 'required',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => 0, "message" => "Account Number is required"];
            return response()->json($ra, 406);
        }

        $userAccount = company_wallet::where('vaccount', $request->accountNumber)->first();

        if (! $userAccount) {

            logInfo("Forwading things");
            $body = [
                "account_number" => $request->accountNumber,
            ];

            $response = $this->forwardPostRequestToAM("api/v1/transactions/verify-wireless-verify-account", $body);

            if ($response['status'] == "00") {
                return response()->json(
                    [
                        "status"  => 1,
                        'message' => "Account Verified Successfully",
                        'data'    => [
                            'accountName'     => $response['data']['account_name'],
                            'countryCurrency' => auth()->user()->company->currency->code,
                        ],
                    ]
                );
            } else {
                return response()->json(["status" => 0, 'message' => "Invalid company account number"]);
            }
        }

        $company = Company::where('id', $userAccount->company_id)->first();

        if ($company->id == \auth()->user()->company_id) {
            return response()->json(["status" => 2, 'message' => "Cannot Transfer to Self"]);
        } else {

            return response()->json(
                [
                    "status"  => 1,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'accountName'     => $company->company_name,
                        'countryCurrency' => $company->currency->code,
                    ],
                ]
            );
        }
    }

    public function forwardGetRequestToAM($endpoint)
    {
        $header = [
            "ApiExchangeKey" => "bb0F3hw}ME}C05Yk.:u[ZZGGAgh3&eNe6ieRRNTAdP!xA:}JLZh0+NPg9bb)2Q5f=9&1fm_TFBxW7W5Unkm",
        ];

        $terminalResponse = getRequest($header, [], "https://backend.wirelessbeta.com/api/v1/transactions/verify-wireless-verify-account/$endpoint");

        return $terminalResponse;
    }

    public function forwardPostRequestToAM($endpoint, $request)
    {
        $header = [
            "ApiExchangeKey" => "bb0F3hw}ME}C05Yk.:u[ZZGGAgh3&eNe6ieRRNTAdP!xA:}JLZh0+NPg9bb)2Q5f=9&1fm_TFBxW7W5Unkm",
        ];

        logInfo("https://backend.wirelessbeta.com/$endpoint", "Our end url");
        logInfo($request, "Our end body");

        $terminalResponse = postRequest($header, $request, "https://backend.wirelessbeta.com/$endpoint");

        return $terminalResponse;
    }

    public function companyWalletTransfer(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "finalAmount"        => 'required|gt:0',
            "amount"             => 'required|gt:0',
            "destinationAccount" => 'required',
            "transactionId"      => 'required',
            "transactionPin"     => 'required',
        ]);

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = 0;
            return response()->json($checkAllowedTransaction);
        }

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !']);
        }
        $Company_Wallet_Transfer = get_CR_DR_TXN_Type_Ledger(5, auth()->user()->company_id);
        if ($Company_Wallet_Transfer) {
            $Company_Wallet_Transfer_ledger_cr = $Company_Wallet_Transfer->ledger_cr;
            $Company_Wallet_Transfer_ledger_dr = $Company_Wallet_Transfer->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Company Wallet Transfer ledger for this transaction type in ledger settings !']);
        }
        $sourceCompanytransaction = transactions::where(['id' => $request->transactionId, 'amount' => $request->amount])->first();

        if ($sourceCompanytransaction) {

            if ($sourceCompanytransaction->transaction_remarks_id == 2) {
                return response()->json(["status" => 0, 'message' => "Duplicate Transaction Id"]);
            } else {

                $charge = $request->finalAmount - $request->amount;

                $validatePinDebit = \checkPinDebitCompanyWithCharge($request->transactionPin, \auth()->user()->company_id, $request->amount, $charge);

                if (! $validatePinDebit['status']) {
                    $validatePinDebit['status'] = $validatePinDebit['responseCode'];
                    return \response()->json($validatePinDebit);
                }

                $destinationCompanyWallet = company_wallet::where('vaccount', $request->destinationAccount)->first();

                if (! $destinationCompanyWallet) {

                    $body = [
                        'account_number'        => $request->destinationAccount,
                        'amount'                => $request->finalAmount,
                        'transaction_reference' => $request->transactionId,
                        'narration'             => auth()->user()->company->company_name . " WBL/" . $request->description,
                    ];

                    $respnose = $this->forwardPostRequestToAM("api/v1/transactions/credit-wireless-verify-account", $body);

                    $destinationCompanyWallet = company_wallet::where('company_id', '261')->first();

                    // return response()->json($respnose);
                }

                $transactionRef = generateTransactionReference();

                $newAmount = $destinationCompanyWallet->balance + ($request->amount * 1);

                if ($charge > 0) {
                    //Create a successful transaction for the Recipient
                    $transaction = transactions::create([
                        "balance_before"         => $validatePinDebit['charge_balance_before'],
                        "amount"                 => $charge,
                        "balance_after"          => $validatePinDebit['charge_balance_after'],
                        "description"            => "Transfer charges",
                        "transaction_reference"  => $transactionRef,
                        "transaction_remarks_id" => 2,
                        "company_id"             => auth()->user()->company_id,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);
                }

                $transactionRef2 = generateTransactionReference();

                //Create a successful transaction for the Recipient
                $transaction = transactions::create([
                    "balance_before"         => $destinationCompanyWallet->balance,
                    "amount"                 => $request->amount,
                    "balance_after"          => $newAmount,
                    "description"            => auth()->user()->company->company_name . " WBL/" . $request->description,
                    "transaction_reference"  => $transactionRef2,
                    "company_id"             => $destinationCompanyWallet->company_id,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 5,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $Company_Wallet_Transfer_ledger_cr,
                    "ledger_dr"              => $Company_Wallet_Transfer_ledger_dr,
                ]);

                //Credit the Recipient/Destination Company wallet
                $destinationCompanyWallet->balance += $request->amount;
                $destinationCompanyWallet->save();

                //update transaction to Successful
                $sourceCompanytransaction->transaction_remarks_id = 2;
                $sourceCompanytransaction->balance_after          = $validatePinDebit['balance_after'];
                $sourceCompanytransaction->save();

                $newCompanyDetails = company_wallet::where('company_id', \auth()->user()->company_id)->first();
                // return response()->json($userAccount->company_id);
                return response()->json(
                    [
                        "status"  => 1,
                        'message' => "Company Wallet Transfer Successful",
                        'data'    => [
                            'balance' => $newCompanyDetails->balance,
                        ],
                    ]
                );
            }
        } else {
            return response()->json(["status" => 0, 'message' => "Invalid Transaction Reference $request->transactionId"]);
        }

    }

    // public function getExchangeRates(Request $request)
    // {

    //

    //     $validation = Validator::make($request->all(), [
    //         "countryCurrency" => 'required',
    //         "exchangeAmount" => 'required',
    //     ]);
    //     if ($validation->fails()) {
    //         $ra = array("status" => false, "message" => "Currency Code and Exchange amount is required");
    //         return response()->json($ra, 406);
    //     }

    //     try {
    //         $destinationCurrency = $request->countryCurrency;
    //         $sourceCurrency = isset($request->sourceCurrency) ? $request->s÷ourceCurrency : $request->user()->company->currency->code;
    //         $sourceCurrency = $request->user()->company->currency->code;
    //         $exchangeAmount = $request->exchangeAmount;

    //         $exhangeRate = $this->getExchangeRate($sourceCurrency, $destinationCurrency, $exchangeAmount);

    //         if ($exhangeRate['status'] == 'success') {
    //             $rate = $exhangeRate["data"]["rate"];
    //             $amount = $exhangeRate["data"]["source"]["amount"];

    //             $charge = 0;

    //             $finalAmount = $amount + $charge;

    //             return response()->json(
    //                 [
    //                     'status' => 1,
    //                     'message' => "Exchange rate received",
    //                     'data' => [
    //                         'rate' => $rate,
    //                         'availableBalance' => auth()->user()->company->wallet->balance,
    //                         'amount' => $amount,
    //                         'charge' => $charge,
    //                         'finalAmount' => $finalAmount
    //                     ]
    //                 ]
    //             );
    //         }

    //         return response()->json(["status" => 0, 'message' => "Failed to Verify Account"]);
    //     } catch (Exception $e) {
    //         return response()->json(["status" => 0, 'message' => "Failed to Verify Account"]);
    //     }
    // }

    public function verifyBankAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "accountNumber" => 'required|numeric|gt:0',
            "bankCode"      => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Account number and Bank code are required"];
            return response()->json($ra, 406);
        }
        //9 safe heaven
        $preferredEbankingPartner = PreferredEbankingPartner::all();
        $ebankingService          = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bankCode, $request->accountNumber);

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

    public function verifyWallet(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "account_number" => 'required|numeric|gt:0',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $userAccount = wb_account_details::where('virtual_accnum', $request->account_number)->first();

        if (! $userAccount) {
            return response()->json(['status' => 0, 'message' => 'Invalid wallet account number']);
        }

        if ($userAccount->user->company_id != \auth()->user()->company_id) {
            return response()->json(['status' => 0, 'message' => 'User does not belong to this company']);
        }

        return response()->json(['status' => 1, 'message' => 'Account verified successfully', 'data' => ['account_name' => $userAccount->user->first_name . ' ' . $userAccount->user->last_name]]);
    }

    public function verifyFundWallet(Request $request)
    {
        $transactionRef = $request->query('tx_ref');
        $transactionId  = $request->query('transaction_id');
        $status         = $request->query('status');

        //Checks the status of the transaction from payment-gateway (Flutterwave/Paystack)
        if ($status == 'successful') {

            //Check if the transaction reference exists on our database
            $transaction = transactions::where(['transaction_reference' => $transactionRef])->first();

            //This runs when the transaction reference exists on our database
            if ($transaction) {

                //This runs when the transaction has already been handled... or amount has already been credited to the user
                if ($transaction->transaction_remarks_id == 2) {
                    $request->session()->flash('error', "Duplicate Transactions");

                    return redirect('company/' . auth()->user()->company->short_name . '/transaction/fund-wallet');
                } else { //This runs when the transaction is not handled yet

                    $flutterwaveController = new FlutterwaveController();
                    //Verifying the transaction from payment-gateway(Flutterwave/Paystack)
                    $verifyTransaction = $flutterwaveController->verifyTransaction($transactionId);

                    //Runs when the verification was successfull
                    if ($verifyTransaction["status"]) {
                        //*****THIS WAS WERE THE GUY TOOK ADVANTAGE OF BY SENDING HIS OWN VALUES */
                        //Credits the company wallet account with amount gotten from payment-gateway verification
                        $creditCompany = \creditCompanyWithCharge(\auth()->user()->company_id, $verifyTransaction['data']['amount'], 0);

                        if (! $creditCompany['status']) {
                            $creditCompany['status'] = 0;
                            return response()->json($creditCompany);
                        }

                        $transaction                         = transactions::where('transaction_reference', $transactionRef)->first();
                        $transaction->company_balance_after  = $creditCompany['balance_after'];
                        $transaction->transaction_remarks_id = 2;
                        $transaction->save();

                        $request->session()->flash('success', "Wallet Funded Successfully");

                        return redirect('company/' . auth()->user()->company->short_name . '/transaction/fund-wallet');
                    }
                    return $verifyTransaction;
                }
            } else {
                $request->session()->flash('error', "Invalid Transaction Reference");

                return redirect('company/' . auth()->user()->company->short_name . '/transaction/fund-wallet');
            }
        } else {
            $request->session()->flash('error', "Transaction Failed");

            return redirect('company/' . auth()->user()->company->short_name . '/transaction/fund-wallet');
        }
    }
    public function bankTrS($request)
    {
        if (! cando('approve-bank-transfer|root|direct-bank-transfer')) {
            return ['status' => 0, 'message' => 'Only privilage user can access this function'];
        }

        $description = $request->description;
        // if (\auth()->user()->company->currency->id == 1 ) {
        $companyCharge = WbaCharge::find(1);
        $chargeAmount  = $companyCharge->charge;
        // } else {
        if ($request->has('chargeAmount')) {
            $chargeAmount = $request->chargeAmount != null ? $request->chargeAmount : $chargeAmount;
        }
        // }

        if ($request->amount > 2000000) {
            return ['status' => 0, 'message' => 'Maximum transfer amount is 2,000,000'];
        }
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        } else {
            return ['status' => 0, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
        }

        try {
            DB::beginTransaction();
            $transaction = transactions::where(['transaction_reference' => $request->transactionId])->lockForUpdate()->first();

            logInfo(json_encode($transaction), "transaction reference");

            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return ["status" => 0, 'message' => "Transaction has already been completed"];
                } else if ($transaction->transaction_remarks_id == 3) {
                    return ["status" => 0, 'message' => "Transaction has been processed and failed. Kindly try another transaction"];
                } else if ($transaction->transaction_remarks_id == 5) {
                    return ["status" => 0, 'message' => "Transaction has been Reversed. Kindly try another transaction in 1 minute"];
                } else if ($transaction->transaction_remarks_id == 1) {
                    return ["status" => 0, 'message' => "Duplicate Transaction Reference. Please try again later"];
                } else {

                    if ($request->transactionPin != auth()->user()->pin) {

                        if (auth()->user()->transaction_pin_fails < 4) {
                            auth()->user()->transaction_pin_fails += 1;
                            auth()->user()->save();
                            return ["status" => 0, "responseCode" => 2, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - auth()->user()->transaction_pin_fails) . " wrong attempts"];
                        } else {
                            // auth()->user()->status_id = 5;
                            restrictUserAccount(2, "Multiple Transaction pin trials");

                            return ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
                        }
                    }

                    $checkCompanyBalance = $this->checkCompanyBalance($request->transactionId, $request->amount);
                    if (! $checkCompanyBalance['status']) {
                        return \response()->json($checkCompanyBalance, 400);
                    }

                    $customerCharge = 0;

                    $transaction->transaction_remarks_id = 1;
                    $transaction->save();

                    if (\auth()->user()->company->setting->bank_transfer_charge_state == 1) {
                        $customerCharge = $request->amount * (\auth()->user()->company->setting->min_bank_transfer_charge_or_percentage / 100);

                        $customerCharge = $customerCharge > \auth()->user()->company->setting->max_bank_transfer_charge ? \auth()->user()->company->setting->max_bank_transfer_charge : $customerCharge;
                    } else {
                        $customerCharge = \auth()->user()->company->setting->max_bank_transfer_charge;
                    }

                    $transactionRef2 = generateTransactionReference();

                    logInfo($transactionRef2, "transref 2");

                    $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

                    if ($checkAllowedTransaction['status'] != 1) {
                        $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1 ? 1 : 0;
                        return $checkAllowedTransaction;
                    }

                    logInfo(json_encode($checkAllowedTransaction), "checkAllowedTransaction");

                    $companyCharge = getWbaTransferCharges(1, $request->amount);

                    logInfo(json_encode($companyCharge), "companyCharge");

                    $validateTransferAmount = $this->validateTransfer($request->amount);

                    logInfo(json_encode($validateTransferAmount), "validateTransferAmount");

                    if ($validateTransferAmount['status'] == 0) {
                        return $validateTransferAmount;
                    }

                    $customerCharge = $customerCharge < $companyCharge ? ($companyCharge + ($companyCharge * 0.01)) : $customerCharge;

                    if ($request->chargeAmount != null) {
                        $customerCharge = $request->chargeAmount;
                    }

                    $customerBalanceBefore       = 0;
                    $customerBalanceAfter        = 0;
                    $customerChargeBalanceBefore = 0;
                    $customerChargeBalanceAfter  = 0;
                    $accountId                   = 0;

                    $companyBalanceBefore       = 0;
                    $companyBalanceAfter        = 0;
                    $companyChargeBalanceBefore = 0;
                    $companyChargeBalanceAfter  = 0;

                    $entityType = "both";

                    logInfo(json_encode($entityType), "entityType");

                    logInfo(json_encode($transaction), "TXNBankDetails");

                    $bank = banks::where('bank_code', $request->bankCode)->orwhere('short_code', $request->bankCode)->first();

                    //9 safe heaven
                    $preferredEbankingPartner = PreferredEbankingPartner::all();
                    $ebankingService          = new EbankingService($preferredEbankingPartner);

                    $bankAccount = $ebankingService->verifyAccountNumberFull($request->bankCode, $request->destinationAccount);

                    if (! $bankAccount['status']) {
                        $bankAccount['status'] = 0;
                        return response()->json($bankAccount);
                    }

                    $gatewayDescription = auth()->user()->first_name . " " . auth()->user()->last_name . " / " . auth()->user()->company->company_name;
                    $myDescription      = "You transferred funds to " . $bankAccount['data']['account_name'] . "/" . $request->destinationAccount . " / " . $bank->bank_name . " / " . auth()->user()->company->company_name;

                    $bankTransferType                       = new BankTransferType();
                    $bankTransferType->bankCode             = $bank->bank_code;
                    $bankTransferType->shortBankCode        = $bank->short_code;
                    $bankTransferType->accountNumber        = $request->destinationAccount;
                    $bankTransferType->description          = $gatewayDescription;
                    $bankTransferType->amount               = $request->amount;
                    $bankTransferType->transactionReference = $transaction->transaction_reference;

                    $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

                    logInfo($bankTransfer, "Transfer Bank Log");

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
                        if (isset($transaction->TXNBankDetails->user_id) && $transaction->TXNBankDetails->account_id != null) {
                            if ($transaction->TXNBankDetails->user_id > 0) {

                                $entityType = "both";

                                if ($request->chargeAmount != null) {
                                    $customerCharge = $request->chargeAmount;
                                }

                                $debitCustomerAndCompany = debitCustomerAndCompanyWithCharge($transaction->TXNBankDetails->account_id, auth()->user()->company_id, $request->amount, $customerCharge, $companyCharge);
                                logInfo(json_encode($debitCustomerAndCompany), "debitCustomerAndCompany Entity");
                                if (! $debitCustomerAndCompany['status']) {
                                    $debitCustomerAndCompany['status'] = 0;
                                    return $debitCustomerAndCompany;
                                }

                                logInfo($debitCustomerAndCompany, "Debit Tagged customer Log");

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
                                $transaction->amount                 = $request->amount + $customerCharge;
                                $transaction->balance_after          = $customerChargeBalanceAfter;
                                $transaction->company_balance_after  = $companyBalanceBefore;
                                $transaction->company_amount         = $request->amount + $companyCharge;
                                $transaction->company_balance_before = $companyChargeBalanceAfter;
                            }
                        } else {

                            $entityType = "company";

                            $chargeAmount = $request->amount > 1000000 ? 100 : $chargeAmount;

                            $debitCompanyWithCharge = \debitCompanyWithCharge(\auth()->user()->company_id, $request->amount, $chargeAmount);

                            logInfo(json_encode($debitCompanyWithCharge), "debitCompanyWithCharg Entity");
                            if (! $debitCompanyWithCharge['status']) {
                                $debitCompanyWithCharge['status'] = $debitCompanyWithCharge['responseCode'];
                                return $debitCompanyWithCharge;
                            }

                            logInfo($debitCompanyWithCharge, "Debit Tagged company Log");

                            $companyBalanceBefore       = $debitCompanyWithCharge['balance_before'];
                            $companyBalanceAfter        = $debitCompanyWithCharge['balance_after'];
                            $companyChargeBalanceBefore = $debitCompanyWithCharge['charge_balance_before'];
                            $companyChargeBalanceAfter  = $debitCompanyWithCharge['charge_balance_after'];

                            $transaction->balance_before         = 0;
                            $transaction->balance_after          = 0;
                            $transaction->company_balance_before = $companyBalanceBefore;
                            $transaction->company_amount         = $request->amount + $chargeAmount;
                            $transaction->company_balance_after  = $companyChargeBalanceAfter;

                            $transaction->description            = $myDescription;
                            $transaction->transaction_remarks_id = 2;
                        }

                        $transaction->save();

                        DB::commit();
                    }

                    //    $transaction->description            = $myDescription;
                    //      $transaction->transaction_remarks_id = 2;
                    //     $transaction->save();

                    return [
                        "status"  => 1,
                        'message' => "Wallet to Bank Transfer Successful",
                        'data'    => [
                            'balance' => $companyBalanceBefore,
                        ],
                    ];
                }
            } else {
                return ['status' => 0, 'message' => "Invalid Transaction Reference"];
            }
        } catch (Exception $e) {
            DB::rollBack();
            logInfo($e->getMessage(), "Tranfer Error");
            return ['status' => 0, 'message' => "Server Error... Please contact support"];
        }
    }

    public function validateTransfer($amount)
    {
        $accountTier = auth()->user()->tier;

        $companyWallet = company_wallet::where('company_id', auth()->user()->company_id)->first();

        if (! $companyWallet) {
            return ["status" => 0, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => 0, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'company_id' => auth()->user()->company_id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
        $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'company_id' => auth()->user()->company_id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->sum('company_amount');

        if (($dailySum + $amount) > $accountTier->daily_transaction_limit) {
            return ["status" => 0, 'message' => "Daily Transaction Limit Exceeded"];
        }
        if ($sum >= $accountTier->daily_transaction_count) {
            return ["status" => 0, 'message' => "Daily Transaction Count Exceeded"];
        }

        return ["status" => 1, 'message' => "Transaction Validated"];
    }

    public function transferToBankAccount(Request $request)
    {
        if (! cando('approve-bank-transfer|root|direct-bank-transfer')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            "amount"             => "required|gt:0",
            "transactionId"      => "required",
            "destinationAccount" => "required",
            "bankCode"           => "required",
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->bankCode == "090267") {
            $ra = ["status" => false, "message" => "Service is not available right now"];
            return response()->json($ra, 406);
        }

        return response()->json($this->bankTrS($request));
    }
    public function mobileMoneyTransfer(Request $request)
    {
        $description = $request->description;

        $validation = Validator::make($request->all(), [
            "amount"             => "required|gt:0",
            "exchangeAmount"     => "required",
            "transactionId"      => "required",
            "destinationAccount" => "required",
            "exchangeCurrency"   => "required",
            "bankCode"           => "required",
            "transactionPin"     => "required",
            "receipientName"     => "required",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => 0, "message" => "All fields are required"];
            return response()->json($ra, 406);
        }

        if (\auth()->user()->pin == $request->transactionPin) {

            $transaction = transactions::where(['id' => $request->transactionId])->first();

            if ($transaction) {

                if ($request->amount > 0) {
                    if ($transaction->transaction_remarks_id == 2) {
                        return response()->json(["status" => 0, 'message' => "Duplicate Transaction ID"]);
                    } else {

                        if (\auth()->user()->company->wallet->balance > $request->amount) {

                            $newdescription = "mydescription";

                            $myAccountName = [];

                            $myAccountName = explode(" ", $request->receipientName);

                            $firstName = $myAccountName[0];
                            $lastName  = count($myAccountName) > 1 ? $myAccountName[1] : $myAccountName[0];

                            $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];

                            $body = [
                                "account_bank"     => $request->bankCode,
                                "account_number"   => $request->destinationAccount,
                                "amount"           => $request->exchangeAmount,
                                "narration"        => $request->description,
                                "currency"         => $request->exchangeCurrency,
                                "beneficiary_name" => $request->receipientName,
                                "reference"        => $transaction->transaction_reference,
                                "debit_currency"   => "NGN",
                            ];

                            $url = "https://api.flutterwave.com/v3/transfers";

                            $bankTransfer = \postRequest($header, $body, $url);

                            if ($bankTransfer["status"] == 'success') {

                                $wallet = company_wallet::where('company_id', auth()->user()->company_id)->first();

                                $wallet->balance -= $request->amount;
                                $wallet->save();

                                $transaction                         = transactions::where('id', $request->transactionId)->first();
                                $transaction->balance_after          = $wallet->balance;
                                $transaction->transaction_remarks_id = 2;
                                $transaction->save();

                                return response()->json(
                                    [
                                        "status"  => 1,
                                        'message' => "Mobile Money Transfer Successful",
                                        'data'    => [
                                            'balance' => $wallet->balance,
                                        ],
                                    ]
                                );
                            } else {
                                return response()->json(["status" => 3, 'message' => "Failed transaction", "data" => $bankTransfer]);
                            }

                        } else {

                            return response()->json(["status" => 0, 'message' => "Insufficient Balance"]);
                        }
                    }
                } else {
                    return response()->json(["status" => 0, 'message' => "Invalid Transaction Amount"]);
                }
            } else {
                return response()->json(["status" => 0, 'message' => "Invalid Transaction Reference"]);
            }
        } else {
            return response()->json(["status" => 2, 'message' => "Invalid Transaction Pin"]);
        }
    }

    public function verifyTransaction($transactionId)
    {

        $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
        $url    = "https://api.flutterwave.com/v3/transactions/$transactionId/verify";
        return \getRequest($header, [], $url);
    }

    public function getExchangeRate($sourceCurrency, $destinationCurrency, $exchangeAmount)
    {

        $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
        $body   = ["amount" => $exchangeAmount, "destination_currency" => $sourceCurrency, "source_currency" => $destinationCurrency];
        $url    = "https://api.flutterwave.com/v3/transfers/rates?amount=$exchangeAmount&destination_currency=$destinationCurrency&source_currency=$sourceCurrency";

        return \getRequest($header, $body, $url);
    }

    public function randomNumber($length)
    {
        $result = 'WBA';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }
    public function pendingTransaction($companname)
    {
        if (! cando('approve-bank-transfer|root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name'    => 'funds_transfer',
            'page_name'        => 'pending_transactions',
            'page_menu'        => 'pending_transactions',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'branch'           => branch::has('author')->where('company_id', auth()->user()->company_id)->get(),
            'alt_menu'         => 0,
        ];
        return view('company.savings.pending_transactions')->with($data);
    }
    public function display_pending(Request $request)
    {
        if (! cando('approve-bank-transfer|root')) {
            abort(403);
            return 0;
        }

        $search = $request->query('search', ['value' => '', 'regex' => false]);
        $draw   = $request->query('draw', 0);
        $start  = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order  = $request->query('order', [1, 'asc']);

        $filter = $search['value'];

        // response()->json($request->all());

        $sortColumns = [
            0 => 'id',
            1 => 'branch_id',
            2 => 'branch_id',
            3 => 'amount',
            4 => 'transaction_reference',
            6 => 'description',
            6 => 'created_by',
            7 => 'created_at',
            8 => 'created_at',
        ];
        $query   = transactions::with('TXNBankDetails')->where(['company_id' => auth()->user()->company_id, 'transaction_remarks_id' => 4, 'transaction_type_id' => 8]);
        $is_root = cando('approve-bank-transfer|root');
        if (! empty($filter)) {
            $query->where('transaction_reference', 'like', '%' . $filter . '%');
        }
        if ($request->staff != null) {
            $staffid = User::where(['uuid' => $request->staff, 'company_id' => auth()->user()->company_id])->first();
            if ($staffid) {
                $query->where('created_by', $staffid->id);
            } else {
                $query->where('created_by', 0);
            }
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
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
            $btnlink    = $infoData->transaction_reference;
            $created_by = User::find($infoData->created_by);
            $cryptedID  = "'" . ($infoData->transaction_reference) . "'";
            //comeback
            $btn            = '<input type="checkbox" name="pendingid[]" value="' . $infoData->transaction_reference . '">';
            $btn2           = '<button type="button" class="btn btn-info" onclick="viewPendingDetails(' . $cryptedID . ')">View</button>';
            $json['data'][] = [
                @$infoData->TXNBankDetails->name,
                @$infoData->TXNBankDetails->accountNo,
                @$infoData->TXNBankDetails->bank->bank_name,
                number_format($infoData->amount, 2),
                // $infoData->w_charge,
                $infoData->type->transaction_type,
                $infoData->description,
                $created_by->first_name . ' ' . $created_by->last_name,
                $btn2,
                $btn,
            ];
        }

        return $json;
    }
    public function viewTXNDetails($pendingID)
    { //use for viewing pending users before approving
        $ids = ($pendingID);
        if (! cando('approve-bank-transfer|root')) {
            return abort(403);
        }

        $pendingdata = transactions::with('type')->where(['transaction_reference' => $ids, 'transaction_type_id' => 8])->first();
        $wbdetails   = 0;
        if ($pendingdata) {
            if (isset($pendingdata->TXNBankDetails->user_id)) {
                $wbdetails = wb_account_details::with('user_wallet')->with('accountType')->with('user')->where('user_id', $pendingdata->TXNBankDetails->user_id)->get();
            }
            $teller = User::with('staff_wallet')->where(['id' => $pendingdata->created_by, 'company_id' => auth()->user()->company_id])->first();
            if ($teller) {
                $Beneficiary = Beneficiary::with('bank')->where('transaction_id', $pendingdata->id)->first();
                if (! $Beneficiary) {
                    $Beneficiary = false;
                }
                $logo       = '';
                $tellerlogo = '';
                if (isset($pendingdata->TXNBankDetails->user->profile_pic)) {
                    $logo = ($pendingdata->TXNBankDetails->user->profile_pic != null) ? url(str_replace('public', 'storage', $pendingdata->TXNBankDetails->user->profile_pic)) : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                }
                $tellerlogo = ($teller->profile_pic != null) ? url(str_replace('public', 'storage', $teller->profile_pic)) : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';

                return json_encode(['data' => $pendingdata, 'teller' => $teller, 'customerlogo' => $logo, 'tellerLogo' => $tellerlogo, 'beneficiary' => $Beneficiary, 'userData' => $wbdetails]);
            }
        }
        return [];
    }
    public function approve_pending_transaction(Request $request)
    {
        if (! cando('approve-bank-transfer|root')) {
            return abort(403);
        }

        $validator = Validator::make($request->all(), [
            'pendingid' => 'required',
            'action'    => 'required',
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
        $message    = '';
        $status     = 0;
        $penidarray = $request->pendingid;
        for ($i = 0; $i < count($penidarray); $i++) {
            $transactionid = $penidarray[$i];
            $transaction   = transactions::where(['transaction_reference' => $transactionid, 'transaction_type_id' => 8, 'transaction_remarks_id' => 4])->first();
            if (! $transaction) {
                return response()->json(['status' => 0, 'message' => 'Transaction Already Processed!']);
            }

            $teller = User::with('staff_wallet')->where(['id' => $transaction->created_by, 'company_id' => auth()->user()->company_id])->first();
            if (! $teller) {
                return response()->json(['status' => 0, 'message' => 'Access Denied!']);
            }

            if ($request->action == 1) {
                if ($transaction) {
                    $staff = User::where(['id' => $transaction->created_by, 'company_id' => auth()->user()->id])->first();
                    if ($staff) {
                        $amount                       = $transaction->amount;
                        $transactionId                = $transaction->transaction_reference;
                        $destinationAccount           = $transaction->TXNBankDetails->accountNo;
                        $bankCode                     = $transaction->TXNBankDetails->bank->bank_code;
                        $request->amount              = $amount;
                        $request->transactionId       = $transactionId;
                        $request->destinationAccount  = $destinationAccount;
                        $request->bankCode            = $bankCode;
                        $request->chargeAmount        = $transaction->withdrawal_charge ?? 0;
                        $messageData                  = $this->bankTrS($request);
                        $message                     .= $messageData['message'] . '<br>';
                        $status                      += $messageData['status'];
                    }
                }
            } else {
                Beneficiary::where('transaction_id', $transaction->id)->delete();
                $transaction->delete();
                $status  = 1;
                $message = 'Transaction Deleted!';
            }
        }
        return response()->json(['status' => ($status) ? 1 : 0, 'message' => $message]);
    }
    public function txnReceipt($id)
    {
        if (! cando('root|view-company-transaction-history')) {
            return abort(403);
        }

        $is_root = cando('root|view-company-transaction-history');
        $auth    = auth()->user();
        if (! $is_root) {
            $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
                ->where(['users.company_id' => $auth->company_id, 'transaction_reference' => $id, 'transactions.created_by' => $auth->id]);
        } else {
            $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
                ->join('users', 'users.id', '=', 'wb_account_details.user_id')
                ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
                ->where(['users.company_id' => $auth->company_id, 'transaction_reference' => $id]);
        }
        $query = $query->first();
        $data  = [
            'category_name'    => 'apps',
            'page_name'        => 'form',
            'page_menu'        => 'sub_invoice',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 1,
            'txnid'            => $id,
            'txn'              => $query,
            'success'          => "Transaction Successful!",
        ];
        return view('company.invoice.company_transaction_invoice')->with($data);
    }

    public function reverseTransactionsMessages($ref)
    {
        logInfo($ref, "Transaction Ref");
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
}
