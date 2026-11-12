<?php

namespace App\Http\Controllers;

use App\Models\CustomerOverdraft;
use App\Models\MainCustomerRestriction;
use App\Models\transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class UserController extends Controller
{
    public function registration(Request $request)
{
    $validation = Validator::make($request->all(), [
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'phone'      => 'required|string|max:20',
        'email'      => 'required|email|max:255',
        'dob'        => 'required|date',
        'username'   => 'required|string|max:255',
        'pin'        => 'required|string',
        'password'   => 'required|string|min:6',
        'companyid'  => 'required|integer',
    ]);

    if ($validation->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Please provide all required registration details.',
            'errors' => $validation->errors(),
            'data' => null,
        ], 422);
    }

    $email = strtolower(trim($request->email));
    $username = trim($request->username);
    $phone = trim($request->phone);

    // This is the KoloPay company ID, not Finova's company ID.
    $localCompanyId = (int) $request->companyid;

    // Prevent duplicate local registrations before calling Finova.
    $existingUser = User::where('email', $email)
        ->orWhere('username', $username)
        ->orWhere('phone', $phone)
        ->first();

    if ($existingUser) {
        return response()->json([
            'status' => false,
            'message' => 'User already exists.',
            'data' => null,
        ], 409);
    }

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
        'account_type',
        'selected_product',
        'referral',
        'account_bank',
        'identityNumber',
    ]);

    // Always register KoloPay customers under Finova company 111.
    $payload['company'] = '111';

    try {
        $baseUrl = rtrim(config('services.finova.base_url'), '/');

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(90)
            ->post($baseUrl . '/users/register', $payload);

        $responseData = $response->json();

        if (
            !$response->successful() ||
            !is_array($responseData) ||
            ($responseData['status'] ?? false) !== true
        ) {
            return response()->json(
                is_array($responseData) ? $responseData : [
                    'status' => false,
                    'message' => 'Finova registration failed.',
                    'data' => null,
                ],
                $response->status() >= 400 ? $response->status() : 422
            );
        }

        $finovaData = $responseData['data'] ?? [];

        $accountNumber = $finovaData['account_number'] ?? null;
        $userReference = $finovaData['user_reference'] ?? null;

        if (!$accountNumber || !$userReference) {
            return response()->json([
                'status' => false,
                'message' => 'Finova accepted the registration, but the account details required for local synchronization were not returned. Please contact support before retrying.',
                'data' => [
                    'user_reference' => $userReference,
                    'account_number' => $accountNumber,
                ],
            ], 502);
        }

        try {
            $user = DB::transaction(function () use (
                $request,
                $email,
                $username,
                $phone,
                $localCompanyId,
                $accountNumber,
                $userReference
            ) {
                return User::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => trim($request->first_name . ' ' . $request->last_name),
                    'company_id' => $localCompanyId,

                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'username' => $username,
                    'dob' => $request->dob,
                    'gender' => $request->gender,
                    'address' => $request->address,

                    'bvn' => $request->bvn,
                    'nin' => $request->nin,

                    // Store a local password hash for local account compatibility.
                    'password' => Hash::make($request->password),

                    // Finova remains authoritative for the transaction PIN.
                    'pin' => null,

                    // These values come from Finova's successful response.
                    'virtual_account' => $accountNumber,
                    'account_reference' => $userReference,

                    // Remote Finova IDs are not local KoloPay IDs.
                    'bank_id' => 126,
                    'branch_id' => 182,
                    'account_officer_id' => 1199,

                    'profile_pic' => $request->profile_pic,
                    'signature' => $request->signature,
                    'status_id' => $request->status_id,
                    'bvn_status' => $request->bvn_status,
                    'kyc_level_id' => $request->kyc_level_id,

                    'user_type_id' => 1,
                    'phone_verification_status_id' => null,
                    'otp' => null,
                    'access_token' => null,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            // Finova has already created the customer. Do not automatically
            // retry registration because that could create a duplicate account.
            return response()->json([
                'status' => false,
                'message' => 'Your Finova account was created, but KoloPay could not synchronize your local profile. Please contact support. Do not register again.',
                'data' => [
                    'user_reference' => $userReference,
                    'account_number' => $accountNumber,
                ],
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => $responseData['message'] ?? 'Registration Successful',
            'data' => [
                'name' => $user->name,
                'user_reference' => $userReference,
                'account_number' => $accountNumber,
                'bank' => $finovaData['bank'] ?? null,
                'phone_number' => $finovaData['phone_number'] ?? $phone,
                'otp_code' => $finovaData['otp_code'] ?? null,
            ],
        ], 200);

    } catch (ConnectionException $e) {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to connect to Finova. Please try again later.',
            'data' => null,
        ], 503);
    }
}

public function login(Request $request)
{
    $validation = Validator::make($request->all(), [
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    if ($validation->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Email/username and password are required.',
            'errors' => $validation->errors(),
            'data' => null,
        ], 422);
    }

    $loginIdentifier = trim($request->email);

    try {
        $baseUrl = rtrim(config('services.finova.base_url'), '/');

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(90)
            ->post($baseUrl . '/users/login', [
                'username' => $loginIdentifier,
                'password' => $request->password,
                'company_id' => 111,
            ]);

        $responseData = $response->json();

        if (
            !$response->successful() ||
            !is_array($responseData) ||
            ($responseData['status'] ?? false) !== true
        ) {
            return response()->json(
                is_array($responseData) ? $responseData : [
                    'status' => false,
                    'message' => 'Invalid Login Credentials.',
                    'data' => null,
                ],
                $response->status() >= 400 ? $response->status() : 401
            );
        }

        $finovaData = $responseData['data'] ?? [];
        $finovaUser = $finovaData['user'] ?? [];
        $finovaToken = $finovaData['access_token'] ?? null;

        if (!$finovaToken || !is_array($finovaUser)) {
            return response()->json([
                'status' => false,
                'message' => 'Finova login succeeded, but the authentication details were incomplete.',
                'data' => null,
            ], 502);
        }

        // Ensure the authenticated Finova account belongs to KoloPay.
        if ((int) ($finovaUser['company_id'] ?? 0) !== 111) {
            return response()->json([
                'status' => false,
                'message' => 'This account is not registered under KoloPay.',
                'data' => null,
            ], 403);
        }

        $email = $finovaUser['email'] ?? null;
        $username = $finovaUser['username'] ?? $loginIdentifier;
        $phone = $finovaUser['phone'] ?? null;

        $user = User::where('email', $email)
            ->when($email === null, function ($query) use ($username) {
                $query->where('username', $username);
            })
            ->first();

        // A local company ID must never be replaced with Finova's company_id 111.
        $localCompanyId = $user?->company_id ?? $request->input('companyid');

        $localData = [
            'name' => trim(
                ($finovaUser['first_name'] ?? '') . ' ' .
                ($finovaUser['last_name'] ?? '')
            ),
            'first_name' => $finovaUser['first_name'] ?? $user?->first_name,
            'last_name' => $finovaUser['last_name'] ?? $user?->last_name,
            'email' => $email ?? $user?->email,
            'username' => $username,
            'phone' => $phone ?? $user?->phone,
            'dob' => $finovaUser['dob'] ?? $user?->dob,
            'gender' => $finovaUser['gender'] ?? $user?->gender,
            'address' => $finovaUser['address'] ?? $user?->address,
            'bvn' => $finovaUser['bvn'] ?? $user?->bvn,
            'nin' => $finovaUser['nin'] ?? $user?->nin,

            // Keep the local company mapping.
            'company_id' => $localCompanyId,

            // Save the actual Finova token for subsequent proxy requests.
            'access_token' => $finovaToken,
        ];

        if (!$user) {
            $localData['uuid'] = (string) Str::uuid();
            $localData['password'] = Hash::make(Str::random(40));
            $localData['user_type_id'] = 1;
            $localData['pin'] = null;
            $localData['virtual_account'] = $finovaUser['virtual_account'] ?? null;
            $localData['account_reference'] = $finovaUser['account_reference'] ?? null;
            $localData['bank_id'] = null;
            $localData['branch_id'] = null;
            $localData['account_officer_id'] = null;

            $user = User::create($localData);
        } else {
            $user->update($localData);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'data' => [
                'name' => $user->name,
                'token' => $finovaToken,
            ],
        ], 200);

    } catch (ConnectionException $e) {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to connect to Finova. Please try again later.',
            'data' => null,
        ], 503);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while synchronizing your account.',
            'data' => null,
        ], 500);
    }
}

public function getCustomerBalance($company, $customer_id)
{
    try {
        $customer = User::where('uuid', $customer_id)->first();

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => "Invalid User Id {$customer_id}",
                'data' => [],
            ], 404);
        }

        if (empty($customer->access_token)) {
            return response()->json([
                'status' => false,
                'message' => 'Customer has no Finova authentication token. The customer may need to log in again.',
                'data' => [],
            ], 401);
        }

        $baseUrl = rtrim(config('services.finova.base_url'), '/');

        $coreResponse = Http::acceptJson()
            ->withToken($customer->access_token)
            ->connectTimeout(15)
            ->timeout(60)
            ->get($baseUrl . '/users/get-details');

        $responseData = $coreResponse->json();

        if (
            !$coreResponse->successful() ||
            !is_array($responseData) ||
            ($responseData['status'] ?? false) !== true
        ) {
            return response()->json([
                'status' => false,
                'message' => $responseData['message'] ?? 'Unable to fetch customer balance from Finova.',
                'data' => [],
            ], $coreResponse->status() ?: 502);
        }

        $totalWalletBalance = $responseData['data']['total_wallet_balance'] ?? null;

        if ($totalWalletBalance === null) {
            return response()->json([
                'status' => false,
                'message' => 'Finova did not return the customer total wallet balance.',
                'data' => [],
            ], 502);
        }

        return response()->json([
            'status' => true,
            'message' => 'Balance fetched successfully.',
            'data' => [
                'balance' => $totalWalletBalance,
                'total_wallet_balance' => $totalWalletBalance,
            ],
        ]);

    } catch (ConnectionException $e) {

        \logInfo([
            'error' => $e->getMessage(),
        ], 'KOLO CUSTOMER BALANCE - FINOVA CONNECTION ERROR');

        return response()->json([
            'status' => false,
            'message' => 'Unable to connect to Finova Core Banking.',
            'data' => [],
        ], 503);

    } catch (Throwable $e) {

        \logInfo([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ], 'KOLO CUSTOMER BALANCE - ERROR');

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while fetching the customer balance.',
            'data' => [],
        ], 500);
    }
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
