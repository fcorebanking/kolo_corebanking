<?php

namespace App\Http\Controllers;

use App\Models\CustomerOverdraft;
use App\Models\MainCustomerRestriction;
use App\Models\transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function  registration(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "name" => 'required',
            "email" => 'required',
            "password" => 'required'

        ]);
        if ($validation->fails()) {
            $ra = array("status" => false, "message" => "All fields are required");
            return response()->json($ra, 406);
        }
        $email = htmlentities($request->get('email'));
        $userstate = User::whereEmail($email)->first();
        if ($userstate) {
            $resArr['status'] = false;
            $resArr['message'] = "User already exists";
            $resArr['data'] = null;

            return response()->json($resArr, 409);
        } else {

            $allData = $request->all();
            $password = bcrypt($allData['password']);

            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'companyid' => $request->get('companyid'),
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'phone' => $request->get('phone'),
                'profile_pic' => $request->get('profile_pic'),
                'username' => $request->get('username'),
                'pin' => $request->get('pin'),
                'status_id' => $request->get('status_id'),
                'bvn' => $request->get('bvn'),
                'bvn_status' => $request->get('bvn_status'),
                'nin' => $request->get('nin'),
                'signature' => $request->get('signature'),
                'session_user' => $request->get('session_user'),
                'address' => $request->get('address'),
                'gender' => $request->get('gender'),
                'branch' => $request->get('branch'),
                'role_id' => $request->get('role_id'),
                'email_verified_at' => $request->get('email_verified_at'),
                'password' => $password
            ]);

            // $user = User::create([
            //     'name' => $request->get('name'),
            //     'email' => htmlentities($request->get('email')),
            //     'password' =>  $password
            // ]);

            $resArr = [];
            $data = [];
            //$resArr['token'] = $request->create('/oauth/token', 'POST', $request->all());


            $resArr['status'] = true;
            $resArr['message'] = "Registration Successful";
            $resArr['data'] = array('name' => $user->name, 'token' => $user->createToken('api-application')->accessToken);
            return response()->json($resArr, 200);
        }
    }
    public function  login(Request $request)
    {

        // die(print_r($request));
        $auth = Auth::attempt([
            'email' => htmlentities($request['email']),
            'password' => $request['password']
        ]);
        if ($auth) {
            $user = Auth::user();

            $resArr = [];
            // $resArr['token'] = $user->createToken('api-application')->accessToken;
            // $resArr['name'] = $user->name;

            $resArr['status'] = true;
            $resArr['message'] = "Login Successful";
            $resArr['data'] = array('name' => $user->name, 'token' => $user->createToken('api-application')->accessToken);


            return response()->json($resArr, 200);
        } else {
            $resArr['status'] = false;
            $resArr['message'] = "Invalid Login Credentials";
            $resArr['data'] = null;

            return response()->json($resArr, 401);
        }
    }

    function getCustomerBalance($company, $customer_id)
    {

        $customer = User::where('uuid', $customer_id)->first();

        if (!$customer) {
            return response()->json(["status" => 0, 'message' => "Invalid User Id $customer_id"]);
        }
        return response()->json(["status" => 1, 'message' => "Balance fetched successfully", 'data' => ['balance' => $customer->wb_account_details[0]->user_wallet->balance]]);
    }

    function getCustomerProfile($company, $customer_id)
    {

        $customer = User::where('uuid', $customer_id)->first();

        if (!$customer) {
            return response()->json(["status" => 0, 'message' => "Invalid User Id $customer_id"]);
        }
        return response()->json(
            [
                "status" => 1,
                'message' => "Balance fetched successfully",
                'data' => [
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'name' => "$customer->first_name $customer->last_name",
                    'balance' => $customer->wb_account_details[0]->user_wallet->balance
                ]
            ]
        );
    }

    function createOverdraft(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "customer" => 'required',
            "amount" => 'required|gt:0',
            "interest" => 'required',
            "frequency" => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $customer = User::where('uuid', $request->customer)->first();

        if (!$customer) {
            return \response()->json(['status' => 0, 'message' => 'Invalid customer Id']);
        }

        $transactionId = \generate_txnID();

        $initialBalance = $customer->wb_account_details[0]->user_wallet->balance;

        if ($initialBalance > $request->amount) {
            return \response()->json(['status' => 0, 'message' => "Cannot request overdraft less than available balance"]);
        }

        $overDraftAmount = $initialBalance > 0 ? $initialBalance - $request->amount : $request->amount;

        $interestAmount = ($request->interest / 100) * abs($overDraftAmount);

        $totalRequestedAmount = $request->amount + $interestAmount;

        $overDraftAmount = abs($overDraftAmount) + (abs($overDraftAmount));

        $transaction = transactions::create([
            "balance_before" => $initialBalance,
            "balance_after" => $initialBalance - $totalRequestedAmount,
            "amount" => $totalRequestedAmount,
            "account_id" => $customer->wb_account_details[0]->id,
            "withdrawal_charge" => 0,
            "description" => "Customer Overdraft",
            "transaction_status_id" => 1,
            "company_id" => \auth()->user()->company_id,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => 60, //debit 7
            "credit_debit_type_id" => 2, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "created_date"  => date('Y-m-d'),
            "created_by" => auth()->user()->id
        ]);

        $createOverdraft = CustomerOverdraft::create([
            "user_id" => $customer->id,
            "frequency_id" => $request->frequency,
            "interest" => $request->interest,
            "transaction_id" => $transaction->id,
            "amount" => $overDraftAmount
        ]);

        $customer->wb_account_details[0]->user_wallet->balance;
        $customer->wb_account_details[0]->user_wallet->balance -= $totalRequestedAmount;
        $customer->wb_account_details[0]->user_wallet->save();

        return \response()->json(['status' => 1, 'message' => 'Customer Overdraft Processed successfully', 'data' => ['balance' => $customer->wb_account_details[0]->user_wallet->balance]]);
    }

    public function customerOverdraftList()
    {
        if (!cando('view-customer-overdraft|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'customer_overdraft_list',
            'page_menu' => 'customer_overdraft_list',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0
        ];
        // $pageName = 'html5';

        return view('company.report.customer_overdrafts')->with($data);
    }

    public function ajax_view(Request $request)
    {
        if (!cando('view-customer-overdraft|root')) return abort(403);

        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 25);
        $order = $request->query('order', array(1, 'asc'));

        $filter = $search['value'];

        $sortColumns = array(
            0 => 'users.first_name',
            1 => 'users.last_name',
            2 => 'users.phone',
            3 => 'users_wallets.balance',
        );
        $query = User::join('wb_account_details', 'wb_account_details.user_id', '=', 'users.id')
            ->join('user_wallets', 'wb_account_details.id', '=', 'user_wallets.wb_account_detail_id')
            ->join('savings_products', 'wb_account_details.account_product_id', '=', 'savings_products.id')
            ->select('users.*', 'user_wallets.balance as balance', 'savings_products.savings_product as savings_product')
            ->where('user_wallets.balance', '<', '0')->where('users.company_id', \auth()->user()->company_id);
        // $query =  $query->where(['users.type'=>1,'users.company_id'=>auth()->user()->company_id]);
        $is_root = cando('root');

        // if (!$is_root) {
        //     if (!cando('view-customer-overdraft'))
        //         $query = $query->where('users.account_officer_id', auth()->user()->id);
        // }

        if (!empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
            $query->where('users.last_name', 'like', '%' . $filter . '%');

            $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
            $query->orWhere('users_wallets.balance', 'like', '%' . $filter . '%');
        }

        $recordsTotal = $query->count();

        $sortColumnName = $sortColumns[$order[0]['column']];

        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);

        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );

        $info = $query->get();
        foreach ($info as $infoData) {
            $btnlink = $infoData->id;
            $bydata = User::find($infoData->created_by);
            $byname = '';
            if ($bydata) {
                $byname = $bydata->first_name . ' ' . $bydata->last_name;
            }

            $userReference = $infoData->uuid;

            if ($is_root || cando('view-customer-overdraft')) {
                $btn = "<button class='btn btn-outline-danger btn-sm' onclick=";
                $btn .= "deletefunc('$btnlink','$userReference') >Remove</button>";
            } else {
                $btn = "";
            }

            $json['data'][] = [
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->phone,
                $infoData->virtual_account,
                $infoData->balance,
                $infoData->savings_product,
                $infoData->remark,
                $byname,
                $btn
            ];
        }

        return $json;
    }
}
