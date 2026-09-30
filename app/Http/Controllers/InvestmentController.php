<?php
namespace App\Http\Controllers;

use App\Models\BranchWallet;
use App\Models\company_status;
use App\Models\frequency;
use App\Models\Investmentbooking;
use App\Models\InvestmentProduct;
use App\Models\status;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InvestmentController extends Controller
{

    public function __construct()
    {
        $this->middleware("custom_permission:book-investment|manage-investment|root");

    }

    public function index($company_name)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'html5',
            'page_menu'        => 'investment_product',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'product'          => InvestmentProduct::where('company_id', auth()->user()->company_id)->get(),
            'freq'             => frequency::where('id', '>', 1)->get(),
            'status'           => company_status::all(),
        ];
        // $pageName = 'html5';

        return view('company.investment.main')->with($data);
    }
    public function booking($company_name)
    {

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'investment_booking',
            'page_menu'        => 'investment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => status::where('id', '<', 3)->get(),
        ];
        // $pageName = 'html5';

        return view('company.investment.booking')->with($data);
    }

    public function viewinvestment($company_name)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'viewinvestment_booking',
            'page_menu'        => 'viewinvestment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'status'           => status::where('id', '<', 1)->get(),
        ];
        // $pageName = 'html5';

        return view('company.investment.viewinvestment')->with($data);
    }

    public function ajax_terminate_investment($id)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $runninginvestment = Investmentbooking::where('id', $id)->first();

        if ($runninginvestment->status == 1) {
            $runninginvestment->update(['status' => 0]);
            return response(json_encode(['status' => 1, 'message' => 'Investment Disabled']));
        } else {
            $runninginvestment->update(['status' => 1]);
            return response(json_encode(['status' => 1, 'message' => 'Investment Activated']));
        }
        return response(json_encode(['status' => 0, 'message' => 'An Error occured']));
    }
    //come back
    public function ajax_investmentstatus($id)
    {
        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(21, auth()->user()->company_id);
        if ($Investment_Plan) {
            $ledger_cr = $Investment_Plan->ledger_cr;
            $ledger_dr = $Investment_Plan->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Credit transaction type in ledger settings !']);
        }
        $terminationoption = auth()->user()->company->setting->investment_termination_control;
        $runninginvestment = Investmentbooking::where('id', $id)->first();
        if (runninginvestment->status != 1) {
            $termination_charge = $runninginvestment->plan->termination_charge;
            $amount             = $runninginvestment->amount;
            $PI                 = $amount + $runninginvestment->wallet;
            $percent_charge     = 0;
            $cashleft           = 0;
            if ($terminationoption == 1) {
                if ($runninginvestment->wallet > 1) {
                    $percent_charge = ($termination_charge / 100) * $runninginvestment->wallet;
                    if ($runninginvestment->wallet > $percent_charge) {
                        $cashleft = ($runninginvestment->wallet - $percent_charge) + $amount;
                    } else {
                        return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
                    }
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later (Low interest)']));
                }
            } elseif ($terminationoption == 2) {
                $percent_charge = ($termination_charge / 100) * $amount;
                $cashleft       = ($amount - $percent_charge) + $runninginvestment->wallet;
            } elseif ($terminationoption == 3) {
                $percent_charge = ($termination_charge / 100) * $PI;
                if ($PI > $percent_charge) {
                    $cashleft = $PI - $percent_charge;
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
                }
            } elseif ($terminationoption == 4) {
                $cashleft = $amount;
            } else {
                return response(json_encode(['status' => 0, 'message' => 'Go to company settings and set Investment termination Control']));
            }
            $wb      = wb_account_details::where('id', $runninginvestment->wb_accountdetails_id)->first();
            $wbcheck = ($wb->user->company_id == auth()->user()->company_id);
            if ($wbcheck) {
                if ($runninginvestment->status == 1) {

                    $userwallet          = user_wallet::where('wb_account_detail_id', $runninginvestment->wb_accountdetails_id)->first();
                    $balanceBefore       = $userwallet->balance;
                    $balanceAfter        = $balanceBefore + $cashleft;
                    $userwallet->balance = $balanceAfter; // update(['balance' => $]);

                    $userwallet->save();

                    $BranchWallet = BranchWallet::where('branch_id', $wb->user->branch_id)->first();
                    $BranchWallet->balance += $cashleft;
                    $BranchWallet->save();
                    $desc = "Investment Termination";
                    $date = date('Y-m-d H:i:s');
                    $runninginvestment->update(['status' => 2]); //Terminated
                    $data = [
                        "balance_before"         => $balanceBefore,
                        "amount"                 => $amount,
                        "description"            => $desc,
                        "withdrawal_charge"      => $percent_charge,
                        "account_id"             => $runninginvestment->wb_accountdetails_id,
                        "branch_id"              => $wb->user->branch_id,
                        "transaction_reference"  => generate_txnID(),
                        "transaction_type_id"    => 21, //investment plan
                        "credit_debit_type_id"   => 1,  //debit 2
                        "transaction_remarks_id" => 2,  //success 2
                        "balance_after"          => $balanceAfter,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ];
                    transactions::create($data);
                    sendSMS($runninginvestment->wb_accountdetails_id, "Credit Amt:$cashleft Charges:$percent_charge Desc:$desc Avail Bal:$userwallet->balance Date:$date", false, true, 'Investment Termination');
                    return response(json_encode(['status' => 1, 'message' => "Investment Terminated!"]));
                } else {
                    return response(json_encode(['status' => 0, 'message' => 'Investment no longer Active']));
                }
            } else {
                return response(json_encode(['status' => 0, 'message' => 'User restricted from other company customers']));
            }
        }
        return response(json_encode(['status' => 0, 'message' => 'Investment Terminated!']));
    }
    public function fetchInvestmentProduct()
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $product = InvestmentProduct::where('company_id', auth()->user()->company_id)->get();
        return response(json_encode($product));
    }

    public function calculateinvestment(Request $request)
    {

        if ($request->plan == null || $request->amount == null) {
            return response(json_encode(['status' => 0, 'message' => "Please fill all field!"]));
        }
        $InvestmentProduct = InvestmentProduct::findorfail($request->plan);
        $amount            = $request->amount;
        $plan_interest     = $InvestmentProduct->interest_rate;
        $interest          = $InvestmentProduct->interest_rate / 100 * $amount;
        $amt_interest      = $amount + $interest;
        $product_duration  = $InvestmentProduct->duration;
        $product_frequency = $InvestmentProduct->frequency_id;
        if ($product_frequency == 1) {
            $daysToAdd = $product_duration;
        } else if ($product_frequency == 2) {
            $daysToAdd = $product_duration * 7;
        } else if ($product_frequency == 3) {
            $daysToAdd = $product_duration * 30;
        } else if ($product_frequency == 4) {
            $daysToAdd = $product_duration * 365;
        }
        $percentinvalue = ($amount / $InvestmentProduct->maximum_investment) * 100;
        $startdate      = ($request->created_at == null) ? date("Y-m-d") : $request->created_at;
        $end_date       = add_day_to_date($daysToAdd, $startdate);
        return response(
            json_encode([
                'test'                 => $product_frequency,
                "product"              => $InvestmentProduct->product,
                "investmentproduct_id" => $InvestmentProduct->id,
                "amount"               => $amount,
                'percentvalue'         => round($percentinvalue),
                'termination'          => $InvestmentProduct->termination_charge,
                'max'                  => $InvestmentProduct->maximum_investment,
                'min'                  => $InvestmentProduct->minimum_investment,
                'start'                => $startdate,
                'end'                  => $end_date,
                'totalcash'            => $amt_interest,
                'interest'             => $interest,
                'plan_interest'        => $plan_interest,
                'plantype'             => $InvestmentProduct->investmentfrequency->frequency,
                'duration'             => $InvestmentProduct->duration,
                'account_type'         => auth()->user()->wb_account_details->first()->virtual_accnum,
            ])
        );
    }
    public function makeinvestment(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'amount'            => 'required',
            'account_type'      => 'required',
            'investmentproduct' => 'required',
        ]);
        $wb_account_details_check = wb_account_details::where(['virtual_accnum' => $request->account_type])->first();
        if (! $wb_account_details_check) {
            return response()->json(['status' => 0, 'message' => 'Access denied!']);
        }
        if ($wb_account_details_check->user_id != auth()->user()->id) {
            if (! cando('manage-investment|root')) {
                return abort(403);
            }

        }

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
        $errors = '';
        if ($request->status == null) {
            $errors .= 'Status field required ';
        }
        if ($request->uuid == null) {
            $errors .= 'UUID field required ';
        }
        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, auth()->user()->company_id);
        if ($Investment_Plan) {
            $ledger_cr = $Investment_Plan->ledger_cr;
            $ledger_dr = $Investment_Plan->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !']);
        }
        $created_at   = ($request->created_at == null) ? date("Y-m-d") : $request->created_at;
        $uuid         = $request->uuid;
        $amount       = $request->amount;
        $platform     = $request->platform;
        $userdata     = User::where(['uuid' => $uuid, 'company_id' => auth()->user()->company_id])->first();
        $BranchWallet = BranchWallet::where('branch_id', $userdata->branch_id)->first();
        if ($BranchWallet->balance < $amount) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => "Insufficient balance in Branch Wallet"], 400);
            }
            return response(json_encode(["status" => 0, "message" => "Insufficient balance in Branch Wallet"]));
        }
        if ($userdata->status_id != 1) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => "User is Either Disabled or on Pending Entry!"], 400);
            }
            return response(json_encode(["status" => 0, "message" => "User is Either Disabled or on Pending Entry!"]));
        }
        if (checkcustomerrestriction($userdata->id, 5)) {
            if ($platform == "mobile") {
                return response()->json(["status" => false, 'message' => "User is Restricted from performing this transaction!"], 400);
            }
            return response(json_encode(["status" => 0, "message" => "User is Restricted from performing this transaction!"]));
        }
        $account_type       = $request->account_type;
        $investmentproduct  = $request->investmentproduct;
        $status             = $request->status;
        $wb_account_details = wb_account_details::where(['virtual_accnum' => $account_type, 'user_id' => $userdata->id])->first();
        if ($wb_account_details) {
            $account_typeid  = $wb_account_details->id;
            $customerBalance = $wb_account_details->user_wallet->balance;
            $walletID        = $wb_account_details->user_wallet->id;

            if ($customerBalance >= $amount) {
                $product = InvestmentProduct::where(['id' => $investmentproduct, 'company_id' => auth()->user()->company_id])->first();
                if ($product->minimum_investment > $amount) {
                    if ($platform == "mobile") {
                        return response()->json(["status" => false, 'message' => 'Minimum amount for this investment is ' . $product->minimum_investment], 400);
                    }
                    return json_encode(['status' => 0, 'message' => 'Minimum amount for this investment is ' . $product->minimum_investment]);
                }
                if ($amount > $product->maximum_investment) {
                    if ($platform == "mobile") {
                        return response()->json(["status" => false, 'message' => 'Maximun amount for this investment is ' . $product->maximum_investment], 400);
                    }
                    return json_encode(['status' => 0, 'message' => 'Maximun amount for this investment is ' . $product->maximum_investment]);
                }
                if ($product->interest_rate >= 0 && $product->interest_rate <= 100) {
                    $expectedcash      = (($product->interest_rate / 100) * $amount) + $amount;
                    $product_duration  = $product->duration;
                    $product_frequency = $product->frequency_id;
                    if ($product_frequency == 2) {
                        $daysToAdd = $product_duration * 7;
                    } else if ($product_frequency == 3) {
                        $daysToAdd = $product_duration * 30;
                    } else if ($product_frequency == 4) {
                        $daysToAdd = $product_duration * 365;
                    } else {
                        if ($platform == "mobile") {
                            return response()->json(["status" => false, 'message' => 'Investment frequency error'], 400);
                        }
                        return json_encode(['status' => 0, 'message' => 'Investment frequency error ']);
                    }
                    $transactionId = generate_txnID();
                    $balanceAfter  = $customerBalance - $amount;
                    transactions::create(
                        [
                            "balance_before"         => $customerBalance,
                            "amount"                 => $amount,
                            "description"            => "$product->product Investment Plan ",
                            "account_id"             => $account_typeid,
                            "branch_id"              => $userdata->branch_id,
                            "transaction_reference"  => $transactionId,
                            "transaction_type_id"    => 34, //debit 7
                            "credit_debit_type_id"   => 2,  //debit 2
                            "transaction_remarks_id" => 2,  //success 2
                            "balance_after"          => $balanceAfter,
                            "created_date"           => $created_at,
                            "created_at"             => "$created_at 00:00:00",
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_cr"              => $ledger_dr,
                        ]
                    );
                    user_wallet::findorfail($walletID)->update(['balance' => $balanceAfter]);
                    $startdate = $created_at;
                    $end_date  = add_day_to_date($daysToAdd, $startdate);
                    $BranchWallet->balance -= $amount;
                    $BranchWallet->save();
                    $insertInv = [
                        'wb_accountdetails_id'  => $account_typeid,
                        'investment_product_id' => $investmentproduct,
                        'amount'                => $amount,
                        'wallet'                => 0,
                        'balance'               => 0,
                        'expected_balance'      => $expectedcash,
                        'duration'              => $daysToAdd,
                        'start_date'            => $startdate,
                        'end_date'              => $end_date,
                        'status'                => $status,
                        "created_at"            => $created_at,
                    ];
                    if (isset($request->paymentOption)) {
                        $insertInv["paymentOption"] = $request->paymentOption;
                    }

                    Investmentbooking::create($insertInv);
                    $desc = "Activated $product->product Investment Plan";
                    // sendSMS($account_typeid, "Debit Amt:$amount Desc: $desc Start:$startdate End:$end_date Avail Bal:$balanceAfter  Date:$startdate", false, true, 'Investment Transaction');
                    $redirectlink = "transaction-receipt/$transactionId";
                    return response(json_encode(["status" => 1, "message" => "Transaction Successful!", "redirectlink" => $redirectlink]));
                } else {
                    if ($platform == "mobile") {
                        return response()->json(["status" => false, 'message' => 'Investment frequency error'], 400);
                    }
                    return json_encode(['status' => 0, 'message' => 'Error occured ']);
                }
            } else {
                return response(json_encode(['status' => 0, 'message' => nomoney()]));
            }
        }
        if ($platform == "mobile") {
            return response()->json(["status" => false, 'message' => 'User not found!'], 400);
        }
        return response(json_encode(['status' => 0, 'message' => "User not found!"]));
    }
    public function ajax_viewInvestment($page, Request $request)
    {
        $search = $request->query('search', ['value' => '', 'regex' => false]);
        $draw   = $request->query('draw', 0);
        $start  = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order  = $request->query('order', [1, 'asc']);

        $filter = $search['value'];

        $sortColumns = [
            0 => 'investmentbooking.id',
            1 => 'users.first_name',
        ];
        $query = Investmentbooking::join('investment_products', 'investment_products.id', '=', 'investmentbooking.investment_product_id')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->join('frequencies', 'frequencies.id', '=', 'investment_products.frequency_id')
            ->where('users.company_id', auth()->user()->company_id)
            ->select('investmentbooking.*', 'wb_account_details.virtual_accnum', 'users.last_name', 'users.first_name', 'frequencies.frequency', 'investment_products.termination_charge', 'investment_products.duration', 'investment_products.product', 'investment_products.interest_rate', DB::raw('savings_products.savings_product as accountsavingsp'));
        $is_root = cando('root');
        if (! $is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        $state = 1;
        //
        if ($page == 'active') {
            $state = 1;
        } else if ($page == 'terminated') {
            $state = 2;
        } else {
            $state = 3;
        }
        $query = $query->where(['investmentbooking.status' => $state, 'users.user_type_id' => '1']);
        //from account statement
        $query = $query->where('users.status_id', '=', 1);
        if (! empty($filter)) {
            $query = $query->where(function ($query) use ($filter) {
                $query->where('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
                $query->orWhere('investment_products.product', 'like', '%' . $filter . '%');
                $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
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
        $info = $query->get();
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $pen     = $infoData->termination_charge;
            if ($state == 1) {
                $btn = "<button class='btn btn-outline-warning btn-sm' onclick=";
                $btn .= "invest('$btnlink','$pen') >Terminate</button>";
                $btn .= "<button class='btn btn-outline-secondary btn-sm' onclick=";
                $btn .= "edit('$btnlink') >Edit</button>";
            } else if ($state == 2) {
                $btn = "<button class='btn btn-danger btn-sm' disabled>Terminated</button>";
            } else {
                $btn = "<button class='btn btn-success btn-sm'  disabled>Completed</button>";
            }
            $btn .= "<button class='btn btn-danger btn-sm'  title='This will delete this invest,ment record' onclick=";
            $encryptedID = Crypt::encryptString($btnlink);
            $btn .= "deleteInvestment('$encryptedID') >Delete</button>";

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->accountsavingsp,
                $infoData->product,
                $infoData->amount,
                $infoData->interest_rate . '%',
                ($infoData->duration > 1) ? $infoData->duration . ' ' . $infoData->frequency . 's' : $infoData->duration . ' ' . $infoData->frequency,
                $infoData->wallet,
                $infoData->expected_balance,
                dateDiffInDays($infoData->start_date, date('Y-m-d')),
                $infoData->start_date,
                $infoData->end_date,
                $btn,
            ];
        }
        return $json;
    }
    public function ajax_userviewInvestment($page, $id, Request $request)
    {
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'investmentbooking.id',
            1 => 'users.first_name',
        ];
        $query = Investmentbooking::join('investment_products', 'investment_products.id', '=', 'investmentbooking.investment_product_id')
            ->join('wb_account_details', 'wb_account_details.id', '=', 'investmentbooking.wb_accountdetails_id')
            ->join('savings_products', 'savings_products.id', '=', 'wb_account_details.account_product_id')
            ->join('users', 'users.id', '=', 'wb_account_details.user_id')
            ->select('investmentbooking.*', 'wb_account_details.virtual_accnum', 'users.last_name', 'users.first_name', 'investment_products.product', 'investment_products.interest_rate', DB::raw('savings_products.savings_product as accountsavingsp'));
        $is_root = cando('root');
        if (! $is_root) {
            $query = $query->where('users.account_officer_id', auth()->user()->id);
        }
        $state = 1;
        if ($page == 'active') {
            $state = 1;
        } else if ($page == 'terminated') {
            $state = 2;
        } else {
            $state = 3;
        }
        $query = $query->where(['investmentbooking.status' => $state, 'users.user_type_id' => '1', 'users.company_id' => auth()->user()->company_id, 'investmentbooking.wb_accountdetails_id' => $id]);
        //from account statement
        $query = $query->where('users.status_id', '=', 1);
        if (! empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->orWhere('users.phone', 'like', '%' . $filter . '%');
            $query->orWhere('savings_products.savings_product', 'like', '%' . $filter . '%');
            $query->orWhere('investment_products.product', 'like', '%' . $filter . '%');
            $query->orWhere('wb_account_details.virtual_accnum', 'like', '%' . $filter . '%');
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
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $pen     = $infoData->interest_rate;

            if ($state == 1) {
                $btn = "<button class='btn btn-outline-warning btn-sm' onclick=";
                $btn .= "invest('$btnlink','$pen') >Terminate</button>";
            } else if ($state == 2) {
                $btn = "<button class='btn btn-danger btn-sm' disabled>Terminated</button>";
            } else {
                $btn = "<button class='btn btn-success btn-sm'  disabled>Completed</button>";
            }

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_accnum,
                $infoData->accountsavingsp,
                $infoData->product,
                $infoData->amount,
                $infoData->wallet,
                $infoData->expected_balance,
                $infoData->duration,
                $infoData->start_date,
                $infoData->end_date,
                $btn,
            ];
        }
        return $json;
    }
    public function store(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'name'               => 'required',
            'cover_pic'          => 'required',
            'freq'               => 'required',
            'interest'           => 'required',
            'minimum_investment' => 'required',
            'maximum_investment' => 'required',
            'termination_charge' => 'required',
            'duration'           => 'required',
            'status'             => 'required',
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            $ra = ["status" => 0, "message" => "$errors"];
            return redirect()->back()->with('fail', $errors);
        }
        $savings_product = new InvestmentProduct;
        if (InvestmentProduct::where(['company_id' => auth()->user()->company_id, 'product' => $request->name])->count() == 0) {
            $savings_product->cover_pic = '';
            $company_product            = $request->file('cover_pic');
            if ($company_product) {
                $productName                = $company_product->getClientOriginalName();
                $savings_product->cover_pic = $company_product->storeAs("public/company/product/" . auth()->user()->company_id, $productName);
            }
            $savings_product->product            = $request->name;
            $savings_product->company_id         = auth()->user()->company_id;
            $savings_product->frequency_id       = $request->freq ? $request->freq : 0;
            $savings_product->interest_rate      = $request->interest;
            $savings_product->minimum_investment = $request->minimum_investment;
            $savings_product->termination_charge = $request->termination_charge;
            $savings_product->status_id          = $request->status;
            $savings_product->maximum_investment = $request->maximum_investment;
            $savings_product->duration           = $request->duration;
            $savings_product->created_by         = auth()->user()->id;
            if ($savings_product->save()) {
                return redirect()->back()->with('success', 'Product Added!');
            } else {
                return redirect()->back()->with('fail', 'Operation Failed!');
            }
        } else {
            return redirect()->back()->with('fail', 'Duplicate entry!');
        }
    }
    public function update(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'name'         => 'required',
            'freq'         => 'required',
            'interest'     => 'required',
            'min_withdraw' => 'required|numeric|gt:0',
            'max_deposit'  => 'required|numeric|gt:0',
            'duration'     => 'required',
            'status'       => 'required',
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            $ra = ["status" => 0, "message" => "$errors"];
            return redirect()->back()->with('fail', $errors);
        }
        $savings_product = InvestmentProduct::findorfail($request->id);
        if (InvestmentProduct::where(['company_id' => auth()->user()->company_id, 'product' => $request->name])->count() < 2) {
            $company_product = $request->file('cover_pic');
            if ($company_product) {
                Storage::delete($savings_product->cover_pic);
                $productName                = $company_product->getClientOriginalName();
                $savings_product->cover_pic = $company_product->storeAs("public/company/product/" . auth()->user()->company_id, $productName);
            }
            $savings_product->product            = $request->name;
            $savings_product->company_id         = auth()->user()->company_id;
            $savings_product->frequency_id       = $request->freq ? $request->freq : 0;
            $savings_product->interest_rate      = $request->interest;
            $savings_product->minimum_investment = $request->min_withdraw;
            $savings_product->status_id          = $request->status;
            $savings_product->maximum_investment = $request->max_deposit;
            $savings_product->duration           = $request->duration;
            $savings_product->modified_by        = auth()->user()->id;
            $savings_product->termination_charge = $request->termination_charge;
            if ($savings_product->save()) {
                return redirect()->back()->with('success', 'Product Updated!');
            } else {
                return redirect()->back()->with('fail', 'Operation Failed!');
            }
        } else {
            return redirect()->back()->with('fail', 'Duplicate entry!');
        }
    }
    public function destroy($company_name, $id)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $investment_product = InvestmentProduct::where(['id' => $id, 'company_id' => auth()->user()->company_id])->first();
        Storage::delete($investment_product->cover_pic);
        $investment_product->delete();
        return redirect()->back()->with('success', 'Product Deleted!');
    }
    public function removeInvestment($id)
    {
        try {
            DB::beginTransaction();
            if (! cando('manage-investment|root')) {
                return abort(403);
            }

            $auth       = auth()->user();
            $id         = Crypt::decryptString($id);
            $investment = Investmentbooking::where(['id' => $id])->first();
            if (! $investment) {
                return json_encode(['status' => 0, 'message' => 'INVALID ACCOUNT ']);

            }
            if ($investment->wb_account_details->user->company_id == auth()->user()->company_id) {
                $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, auth()->user()->company_id);
                if ($Investment_Plan) {
                    $ledger_cr = $Investment_Plan->ledger_cr;
                    $ledger_dr = $Investment_Plan->ledger_dr;
                } else {
                    return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !']);
                }
                $transactionId = generate_txnID();
                $user_wallet   = user_wallet::where('wb_account_detail_id', $investment->wb_accountdetails_id)->first();
                if (! $user_wallet) {
                    return response()->json(['status' => 0, 'message' => 'Unknow error!' . $investment->wb_accountdetails_id]);
                }
                $balanceBefore = $user_wallet->balance;
                $extracash     = $investment->amount;
                $txnType       = getTransactionIdByName('Delete-Investment');
                if (! $txnType) {
                    return response()->json(['status' => 0, 'message' => 'Invalid Transaction Type']);
                }

                $data = [
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $extracash,
                    "description"            => $investment->product . " Delete Investment",
                    "account_id"             => $investment->wb_accountdetails_id,
                    "branch_id"              => $auth->branch_id,
                    "transaction_status_id"  => 1,
                    "transaction_reference"  => $transactionId,
                    "transaction_type_id"    => $txnType->id,
                    "credit_debit_type_id"   => 1,
                    "transaction_remarks_id" => 2, //success 2
                    "balance_after"          => ($balanceBefore + $extracash),
                    "created_date"           => date("Y-m-d h:i:s"),
                    "created_by"             => $auth->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ];
                $data['userParamType'] = 2;
                $data['userId']        = $data['account_id'];
                $data['remark']        = $data['transaction_remarks_id'];
                $data['chargeamt']     = 0;
                $CreditCustomer        = DebitCreditcustomer($data);
                if (! $CreditCustomer['status']) {
                    DB::rollBack();
                    return json_encode(['status' => 0, 'message' => $CreditCustomer['message']]);
                }
                $investment->delete();
                DB::commit();

                return json_encode(['status' => 1, 'message' => 'Investment deleted!']);

            }
            DB::rollBack();
            return json_encode(['status' => 0, 'message' => 'Access denied!']);

        } catch (Exception $e) {
            DB::rollBack();
            return json_encode(['status' => 0, 'message' => 'Error processing request!']);
        }

    }
    public function viewUserInvestment($company_name, $id)
    {
        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'viewinvestment_booking',
            'page_menu'        => 'user_viewinvestment_booking',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'accountid'        => $id,
            'status'           => status::where('id', '<', 1)->get(),
        ];
        // $pageName = 'html5';

        return view('company.investment.userinvestment')->with($data);
    }
    public function updateInvestment(Request $request)
    {
        if (! cando('manage-investment|root')) {
            return abort(403);
        }

        $validation = Validator::make($request->all(), [
            'amount'            => 'required',
            'investment_id'     => 'required',
            'investmentproduct' => 'required',
            'investment_date'   => 'required',
        ]);
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            $ra = ["status" => 0, "message" => "$errors"];
            return redirect()->back()->with('fail', $errors);
        }
        $Investment_Plan = get_CR_DR_TXN_Type_Ledger(34, auth()->user()->company_id);
        if ($Investment_Plan) {
            $ledger_cr = $Investment_Plan->ledger_cr;
            $ledger_dr = $Investment_Plan->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Debit transaction type in ledger settings !']);
        }
        $Investment_Plan_Credit = get_CR_DR_TXN_Type_Ledger(21, auth()->user()->company_id);
        if ($Investment_Plan_Credit) {
            $credit_ledger_cr = $Investment_Plan_Credit->ledger_cr;
            $credit_ledger_dr = $Investment_Plan_Credit->ledger_dr;
        } else {
            return response()->json(['status' => 0, 'message' => 'Transaction failed, set Investment Plan Credit transaction type in ledger settings !']);
        }
        $Investmentbooking = Investmentbooking::where('id', $request->investment_id)->first();
        if (! $Investmentbooking) {
            return redirect()->back()->with('fail', 'Fail to process transaction');
        }

        $investment_product = InvestmentProduct::where(['id' => $request->investmentproduct, 'company_id' => auth()->user()->company_id])->first();
        if (! $investment_product) {
            return redirect()->back()->with('fail', 'Fail to process transaction');
        }

        $auth               = auth()->user();
        $minimum_investment = $investment_product->minimum_investment;
        $maximum_investment = $investment_product->maximum_investment;
        if ($minimum_investment > $request->amount) {
            return redirect()->back()->with('fail', "Minimum Investment is $minimum_investment");
        }

        if ($maximum_investment < $request->amount) {
            return redirect()->back()->with('fail', "Maximum Investment is $maximum_investment");
        }

        $prev_cash     = $Investmentbooking->amount;
        $user_wallet   = user_wallet::where('id', $Investmentbooking->wb_accountdetails_id)->first();
        $balanceBefore = $user_wallet->balance;
        $transactionId = generate_txnID();
        if ($prev_cash < $request->amount) {
            $extracash = $request->amount - $prev_cash;
            if ($user_wallet->balance < $extracash) {
                return ["status" => 0, 'message' => "Insufficient Balance!"];
            }
            $data = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $extracash,
                "description"            => $investment_product->product . " Investment Plan ",
                "account_id"             => $Investmentbooking->wb_accountdetails_id,
                "branch_id"              => $auth->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 34,
                "credit_debit_type_id"   => 2,
                "transaction_remarks_id" => 2, //success 2
                "balance_after"          => ($balanceBefore - $extracash),
                "created_date"           => date("Y-m-d h:i:s"),
                "created_by"             => $auth->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ];
            $data['userParamType'] = 2;
            $data['userId']        = $data['account_id'];
            $data['remark']        = $data['transaction_remarks_id'];
            $data['chargeamt']     = 0;
            $CreditCustomer        = DebitCreditcustomer($data);
            if (! $CreditCustomer['status']) {
                return redirect()->back()->with('error', $CreditCustomer['message']);
            }
        }
        if ($prev_cash > $request->amount) {
            $overflow = $prev_cash - $request->amount;
            $data     = [
                "balance_before"         => $balanceBefore,
                "amount"                 => $overflow,
                "description"            => "$investment_product->product Investment Plan ",
                "account_id"             => $Investmentbooking->wb_accountdetails_id,
                "branch_id"              => $auth->branch_id,
                "transaction_status_id"  => 1,
                "transaction_reference"  => $transactionId,
                "transaction_type_id"    => 21,
                "credit_debit_type_id"   => 1,
                "transaction_remarks_id" => 2, //success 2
                "balance_after"          => ($balanceBefore + $overflow),
                "created_date"           => date("Y-m-d h:i:s"),
                "created_by"             => $auth->id,
                "ledger_cr"              => $credit_ledger_cr,
                "ledger_dr"              => $credit_ledger_dr,
            ];
            $data['userParamType'] = 2;
            $data['userId']        = $data['account_id'];
            $data['remark']        = $data['transaction_remarks_id'];
            $data['chargeamt']     = 0;
            $CreditCustomer        = DebitCreditcustomer($data);
            if (! $CreditCustomer['status']) {
                return redirect()->back()->with('error', $CreditCustomer['message']);
            }
        }
        $enddays                                  = date("Y-m-d", strtotime($request->investment_date . "+ $investment_product->duration " . str_replace('ly', 's', $investment_product->frequency->charge_customer_status)));
        $totalDuration                            = dateDiffInDays($request->investment_date, $enddays);
        $interest                                 = $investment_product->interest_rate;
        $totalInterest                            = ($interest / 100) * $request->amount;
        $expectedBalance                          = $totalInterest + $request->amount;
        $currentDuration                          = dateDiffInDays($request->investment_date, date('Y-m-d'));
        $dailycash                                = $expectedBalance / $totalDuration;
        $balance                                  = $currentDuration * $dailycash;
        $Investmentbooking->amount                = $request->amount;
        $Investmentbooking->wallet                = $balance;
        $Investmentbooking->balance               = $balance;
        $Investmentbooking->expected_balance      = $expectedBalance;
        $Investmentbooking->duration              = $totalDuration;
        $Investmentbooking->start_date            = $request->investment_date;
        $Investmentbooking->end_date              = $enddays;
        $Investmentbooking->status                = 1;
        $Investmentbooking->investment_product_id = $investment_product->id;
        if ($Investmentbooking->save()) {
            return redirect()->back()->with('success', 'Investment updated!');
        } else {
            return redirect()->back()->with('error', 'Failed to Update Investment!');

        }
    }
}
