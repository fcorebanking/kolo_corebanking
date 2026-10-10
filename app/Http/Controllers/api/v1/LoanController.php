<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListLoanResource;
use App\Models\ApploanSettings;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\loan_period;
use App\Models\transactions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class LoanController extends Controller
{
    #[OA\Get(
        path: "/api/v1/loan/eligibility",
        summary: "Get loan eligibility",
        description: "Checks the authenticated customer's loan eligibility using transaction activity, wallet balance, and the company's loan settings. Returns the eligible loan product and application limits, or a message explaining that the customer is not eligible.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Eligibility information retrieved. The response may contain eligible loan details or an ineligible status and message.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "id",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "productName",
                            type: "string",
                            example: "Salary Advance"
                        ),
                        new OA\Property(
                            property: "productRate",
                            type: "number",
                            format: "float",
                            example: 12.5
                        ),
                        new OA\Property(
                            property: "repaymenttext",
                            type: "string",
                            example: "Monthly"
                        ),
                        new OA\Property(
                            property: "loantypeapp",
                            type: "string",
                            example: "Flat"
                        ),
                        new OA\Property(
                            property: "remark",
                            type: "string",
                            nullable: true,
                            example: "Available for verified salary earners"
                        ),
                        new OA\Property(
                            property: "duration",
                            type: "string",
                            example: "6"
                        ),
                        new OA\Property(
                            property: "durationPeriod",
                            type: "string",
                            example: "Months"
                        ),
                        new OA\Property(
                            property: "NumOngoinAllowed",
                            type: "integer",
                            example: 2
                        ),
                        new OA\Property(
                            property: "minamt",
                            type: "number",
                            example: 5000
                        ),
                        new OA\Property(
                            property: "maxamt",
                            type: "number",
                            example: 500000
                        ),
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Success"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated."
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error."
            )
        ]
    )]
    public function eligibility()
    {
        $user = auth()->user();

        logInfo("{$user->username}", "Eligibility on mobile");

        $wb_account_details = $user->wb_account_details;
        $transactions_sum = 0;
        $balance = 0;

        foreach ($wb_account_details as $walletCheck) {
            $balance = $walletCheck->user_wallet->balance;

            $loanCount = loan::where([
                'user_id' => $user->id,
                'loan_status_id' => 1,
            ])->count();

            $transactions_sum = transactions::where(
                'account_id',
                $walletCheck->id
            )->sum('amount');

            logInfo(
                "{$user->username}: $transactions_sum",
                "transactions_sum"
            );

            logInfo(
                "{$user->username}: $balance",
                " balance"
            );

            $appdata = ApploanSettings::where([
                    'company_id' => $user->company_id,
                ])
                ->where('transactionMin', '<=', $transactions_sum)
                ->where('transactionMax', '>=', $transactions_sum)
                ->where('minWalletBalance', '<=', $balance)
                ->where('maxWalletBalance', '>=', $balance)
                ->where('NumOngoinAllowed', '>', $loanCount)
                ->first();

            $result = json_encode($appdata);

            logInfo("$result", " App Data");

            if ($appdata) {
                $appdata->message = 'Success';
                $appdata->status = 1;
                $appdata->productName = $appdata->LoanProduct->product_name;
                $appdata->productRate = $appdata->LoanProduct->interest_rate;
                $appdata->repaymenttext = $appdata->PeriodID->name;
                $appdata->loantypeapp = ($appdata->loan_type == 1)
                    ? 'Flat'
                    : 'Reducing';

                return $appdata;
            }
        }

        return [
            'status' => 0,
            'message' => 'Unfortunately, you are not eligible for a loan at the moment.',
        ];
    }

    #[OA\Post(
        path: "/api/v1/loan/process-loan",
        summary: "Process a loan application",
        description: "Processes the requested loan amount using the authenticated customer's eligibility settings and prepares the loan repayment calculation. This endpoint does not book the loan.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["amount"],
                properties: [
                    new OA\Property(
                        property: "amount",
                        type: "string",
                        example: "250000",
                        description: "Amount the customer wants to borrow. Must be within the eligible minimum and maximum amounts."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Loan calculation returned successfully.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "EachRepayment",
                            type: "number",
                            example: 43750
                        ),
                        new OA\Property(
                            property: "principal",
                            type: "number",
                            example: 250000
                        ),
                        new OA\Property(
                            property: "interestRate",
                            type: "number",
                            format: "float",
                            example: 12.5
                        ),
                        new OA\Property(
                            property: "Principel_Interest",
                            type: "number",
                            example: 12500,
                            description: "Field name as returned by the loan processing implementation."
                        ),
                        new OA\Property(
                            property: "TotalLoanInterest",
                            type: "number",
                            example: 25000
                        ),
                        new OA\Property(
                            property: "duration",
                            type: "integer",
                            example: 6
                        ),
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            nullable: true,
                            example: 1
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            nullable: true,
                            example: "Success"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requested amount is outside the eligible loan limits."
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated."
            ),
            new OA\Response(
                response: 500,
                description: "Error processing loan request.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 0
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Error processing loan request."
                        )
                    ]
                )
            )
        ]
    )]
    public function processLoan(Request $request)
    {
        $traceId = (string) \Illuminate\Support\Str::uuid();

        if ($request->has('amount') && $request->amount !== null) {
            $request->merge([
                'amount' => (string) $request->amount,
            ]);
        }

        $request->merge([
            'trace_id' => $traceId,
        ]);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'API PROCESS LOAN START',
            'authenticated' => auth()->check(),
            'auth_user_id' => auth()->id(),
            'auth_company_id' => auth()->user()->company_id ?? null,
            'request_summary' => [
                'customer_type' => gettype($request->customer),
                'customer_count' => is_array($request->customer)
                    ? count($request->customer)
                    : ($request->customer ? 1 : 0),
                'amount' => $request->amount,
                'amount_type' => gettype($request->amount),
                'loan_products_present' => !empty($request->loan_products),
                'loan_type' => $request->loan_type,
                'branch_present' => !empty($request->branch),
                'officer' => $request->officer,
                'period_present' => !empty($request->period),
                'duration' => $request->Duration,
                'duration_period' => $request->DurationPeriod,
                'principal' => $request->principal,
                'principal_type' => gettype($request->principal),
            ],
        ], 'API LOAN DEBUG START');

        try {
            $newrequest = $this->postDataProcessing($request);

            if (
                is_object($newrequest) &&
                isset($newrequest->amount) &&
                $newrequest->amount !== null
            ) {
                $newrequest->merge([
                    'amount' => (string) $newrequest->amount,
                ]);
            }

            $newRequestStatus = is_object($newrequest)
                ? ($newrequest->status ?? null)
                : (is_array($newrequest)
                    ? ($newrequest['status'] ?? null)
                    : null);

            $newRequestMessage = is_object($newrequest)
                ? ($newrequest->message ?? null)
                : (is_array($newrequest)
                    ? ($newrequest['message'] ?? null)
                    : null);

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'POST DATA PROCESSING RESULT',
                'result_class' => is_object($newrequest)
                    ? get_class($newrequest)
                    : gettype($newrequest),
                'status' => $newRequestStatus,
                'message' => $newRequestMessage,
                'amount' => is_object($newrequest)
                    ? ($newrequest->amount ?? null)
                    : null,
                'amount_type' => is_object($newrequest)
                    && isset($newrequest->amount)
                    ? gettype($newrequest->amount)
                    : null,
            ], 'API LOAN DEBUG POST PROCESSING');

            if ((string) $newRequestStatus !== '1') {
                logInfo([
                    'trace_id' => $traceId,
                    'stage' => 'STOPPED BEFORE LOAN CONTROLLER',
                    'status' => $newRequestStatus,
                    'message' => $newRequestMessage,
                ], 'API LOAN DEBUG FAILURE');

                return response()->json($newrequest);
            }

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'CALLING LOAN CONTROLLER DO PROCESSING',
                'loan_controller' => 'App\\Http\\Controllers\\LoanController',
                'amount' => $newrequest->amount ?? null,
                'amount_type' => isset($newrequest->amount)
                    ? gettype($newrequest->amount)
                    : null,
            ], 'API LOAN DEBUG ROUTING');

            $loanController = app('App\Http\Controllers\LoanController');

            $rawResult = $loanController->doProcessing($newrequest);

            $decodedResult = is_string($rawResult)
                ? json_decode($rawResult, true)
                : (is_array($rawResult) ? $rawResult : null);

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'LOAN CONTROLLER RESPONSE',
                'response' => $decodedResult ?? $rawResult,
            ], 'API LOAN DEBUG RESPONSE');

            if (is_string($rawResult)) {
                $decoded = json_decode($rawResult);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            return $rawResult;

        } catch (\Throwable $e) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'API LOAN EXCEPTION',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 'API LOAN DEBUG EXCEPTION');

            return response()->json([
                'status' => 0,
                'message' => 'Error processing loan request.',
            ], 500);
        }
    }

    public function postDataProcessing(Request $request)
    {
        $user = auth()->check()
            ? auth()->user()
            : User::where('id', $request->userid)->first();

        logInfo(json_encode($user), "loan user");
        logInfo(json_encode($request), "User's Loan Request Data");

        $officer = User::where('id', $user->account_officer_id)->first();
        $eligibility = $this->eligibility();

        if ($eligibility->status == 0) {
            $request->status = 0;
            $request->message = $eligibility->message;

            return $eligibility;
        }

        $amount = $request->amount;
        $minamt = $eligibility->minamt;
        $maxamt = $eligibility->maxamt;

        if ($amount < $minamt || $amount > $maxamt) {
            $request->status = 0;
            $request->message = "Amount should be greater than equal to $minamt and less than equal to $maxamt";

            return $request;
        }

        $request->customer = [$user->uuid];
        $request->loan_products = Crypt::encryptString($eligibility->loan_product_id);
        $request->loan_type = $eligibility->loan_type;
        $request->branch = Crypt::encryptString($user->branch_id);
        $request->officer = $officer->uuid;
        $request->loanFeeMethod = 1;
        $request->loanFee = 0;
        $request->managementFee = 0;
        $request->managementFeeMethod = 1;
        $request->period = Crypt::encryptString($eligibility->repayment);
        $request->Duration = $eligibility->duration;
        $request->principal = $amount;
        $request->keep_as_past_onMissed_onExtend = 1;
        $request->guarantorOption = 2;
        $request->auto_pay = 1;
        $request->ExtendDuration = 0;
        $request->missedFee = 0;
        $request->DurationPeriod = $eligibility->durationPeriod;
        $request->status = 1;

        return $request;
    }

    public function loanData()
    {
        $data = [
            'period' => loan_period::all(),
            'loanType' => [
                ['id' => 1, 'name' => 'Flat'],
                ['id' => 2, 'name' => 'Reducing Balance'],
            ],
            'durationPeriod' => [
                ['id' => 'weeks', 'name' => 'Weeks'],
                ['id' => 'months', 'name' => 'Months'],
                ['id' => 'years', 'name' => 'Years'],
            ],
        ];
    }

    #[OA\Post(
        path: "/api/v1/loan/send-process-loan",
        summary: "Book a processed loan",
        description: "Books a loan application for the authenticated customer. The amount is supplied by the client; the controller derives the customer, product, branch, repayment period, officer, and other processing fields. The booked loan requires approval and disbursement.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["amount"],
                properties: [
                    new OA\Property(
                        property: "amount",
                        type: "string",
                        example: "250000",
                        description: "Amount the customer wants to borrow."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Loan booking request processed.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "newref",
                            type: "string",
                            example: "123456789"
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Loan Successfully Booked! LoanID: LN-2026-0001; Loan now requires approval and disbursment"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "A pending loan must be approved before another loan can be booked."
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated or customer account could not be verified."
            ),
            new OA\Response(
                response: 500,
                description: "Error processing loan request."
            )
        ]
    )]
    public function PostBooklLoan(Request $request)
    {
        $user = null;

        if (!auth()->check()) {
            if (!$request->userid) {
                return response()->json([
                    "status" => 0,
                    "message" => 'Error verifying account!',
                ]);
            }

            $user = User::where('id', $request->userid)->first();

            auth()->login($user);
        } else {
            $user = auth()->user();
        }

        if ($user == null) {
            return response()->json([
                "status" => 0,
                "message" => 'Error!',
            ]);
        }

        $loanCount = loan::where([
                'user_id' => $user->id,
                'loan_status_id' => 1,
            ])
            ->orderBy('id', 'desc')
            ->count();

        if (!$request->userid) {
            $eligi = json_decode($this->eligibility());

            if ($eligi->status == 0) {
                return response()->json([
                    "status" => 0,
                    "message" => $eligi->message,
                ]);
            }

            if ($loanCount > $eligi->NumOngoinAllowed) {
                return response()->json([
                    "status" => 0,
                    "message" => 'You have exceed maximun number of time to book for loan!',
                ]);
            }
        }

        $loanCountPending = loan::where([
                'user_id' => $user->id,
                'loan_status_id' => 3,
            ])
            ->orderBy('id', 'desc')
            ->count();

        if ($loanCountPending) {
            return response()->json([
                "status" => 0,
                "message" => 'Your pending loan need to be approved before booking for another!',
            ], 400);
        }

        $ValidateRequest = [];

        try {
            $request = $this->postDataProcessing($request);

            if (!$request->status) {
                return response()->json([
                    'status' => 0,
                    'message' => $request->message,
                ]);
            }

            return DB::transaction(function () use ($request) {
                $ValidateRequest = json_decode(
                    app('App\Http\Controllers\LoanController')
                        ->doProcessing($request)
                );

                if ($ValidateRequest->status) {
                    $interestRate = $ValidateRequest->interestRate;
                    $duration = $ValidateRequest->duration;
                    $EachRepayment = $ValidateRequest->EachRepayment;
                    $loanFee = $ValidateRequest->loanFee;
                    $managementFee = $ValidateRequest->managementFee;
                    $loanFeeMethod = $request->loanFeeMethod;
                    $managementFeeMethod = $request->managementFeeMethod;
                    $auto_pay = $request->auto_pay;
                    $missedFee = $request->missedFee;
                    $ExtendDuration = $request->ExtendDuration;
                    $keep_as_past_onMissed_onExtend = $request->keep_as_past_onMissed_onExtend;
                    $principal = $ValidateRequest->principal;
                    $LoanType = $ValidateRequest->LoanType;
                    $borrower = $ValidateRequest->borrower;
                    $productid = Crypt::decryptString($request->loan_products);
                    $branchid = Crypt::decryptString($request->branch);
                    $period = Crypt::decryptString($request->period);
                    $officer = $request->officer;
                    $loanOfficer = User::where('uuid', $officer)->first();
                    $guarantor = $request->guarantor;
                    $noBorrower = 0;
                    $loanrefID = [];

                    foreach ($borrower as $borrowerData) {
                        $loan = new loan;

                        $noBorrower++;
                        $request->loanref = str_shuffle(time());

                        if ($noBorrower > 1) {
                            if (is_numeric($noBorrower)) {
                                $request->loanref += $noBorrower;
                            } else {
                                $request->loanref .= $noBorrower;
                            }
                        }

                        if ($request->loanref) {
                            $loanRefExist = loan::where(
                                'ref',
                                $request->loanref
                            )->count();

                            if ($loanRefExist > 0) {
                                return response()->json([
                                    "status" => 0,
                                    "message" => 'Loan Reference ID  Exist!' . $request->loanref,
                                ]);
                            }
                        }

                        $loanrefID[] = $request->loanref;
                        $loan->ref = $request->loanref;
                        $loan->created_by = auth()->user()->id;
                        $loan->loan_status_id = 3;

                        $user = User::where(
                            'uuid',
                            $borrowerData->uuid
                        )->first();

                        $loan->user_id = $user->id;
                        $loan->loan_product_id = $productid;
                        $loan->remark = $request->remark;
                        $loan->LoanBranch_id = $branchid;
                        $loan->LoanOfficer_id = $loanOfficer->id;
                        $loan->DurationPeriod = $request->DurationPeriod;
                        $loan->modified_by = auth()->user()->id;
                        $loan->duration = $ValidateRequest->duration;
                        $loan->period_id = $period;
                        $loan->rate = $ValidateRequest->interestRate;
                        $loan->principal = $ValidateRequest->principal;
                        $loan->loanBalance = $ValidateRequest->Principel_Interest;
                        $loan->LoanType = $request->loan_type;
                        $loan->EachRepayment = $EachRepayment;
                        $loan->InterestLeft = $ValidateRequest->TotalLoanInterest;
                        $loan->principalLeft = $ValidateRequest->principal;
                        $loan->LoanFee = $loanFee;
                        $loan->mrgtFee = $managementFee;
                        $loan->LoanFeeMethod = $loanFeeMethod;
                        $loan->mrgtFeeMethod = $managementFeeMethod;
                        $loan->auto_pay = $auto_pay;
                        $loan->missedFee = $missedFee;
                        $loan->on_guarantor = $request->guarantorOption;
                        $loan->ExtendDuration = $ExtendDuration;
                        $loan->keep_as_past_onMissed_onExtend = $keep_as_past_onMissed_onExtend;
                        $loan->save();

                        if ($loan) {
                            return response()->json([
                                'status' => 1,
                                'newref' => str_shuffle(time()),
                                'message' => "Loan Successfully Booked! \n LoanID: $loan->ref;  \n  Loan now requires approval and disbursment",
                            ]);
                        } else {
                            return response()->json([
                                'status' => 0,
                                'newref' => str_shuffle(time()),
                                'message' => "An error occured!",
                            ]);
                        }
                    }

                    return response()->json([
                        'status' => 1,
                        'newref' => str_shuffle(time()),
                        'message' => "Loan Successfully Booked! <br> LoanID: $loan->ref;  <br>  Loan now requires approval and disbursment",
                    ]);
                } else {
                    return $ValidateRequest->message;
                }
            });

            return response()->json([
                'status' => 0,
                'message' => "An error occured!",
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() . $e->getLine(),
                'status' => 0,
            ]);
        }
    }

    #[OA\Get(
        path: "/api/v1/loan/list-loan",
        summary: "List customer loans",
        description: "Returns loans belonging to the authenticated customer. Each loan is serialized through ListLoanResource.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Loan list retrieved. The data array may be empty if the customer has no loans.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Data Found!"
                        ),
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "loanstatusName",
                                        type: "string",
                                        example: "Active"
                                    ),
                                    new OA\Property(
                                        property: "loanstatustag",
                                        type: "string",
                                        example: "green"
                                    ),
                                    new OA\Property(
                                        property: "remark",
                                        type: "string",
                                        example: "Salary advance"
                                    ),
                                    new OA\Property(
                                        property: "principal",
                                        type: "number",
                                        example: 250000
                                    ),
                                    new OA\Property(
                                        property: "principalLeft",
                                        type: "number",
                                        example: 180000
                                    ),
                                    new OA\Property(
                                        property: "loanBalance",
                                        type: "number",
                                        example: 180000
                                    ),
                                    new OA\Property(
                                        property: "missedLoan",
                                        type: "number",
                                        example: 0
                                    ),
                                    new OA\Property(
                                        property: "missedLoanLeft",
                                        type: "number",
                                        example: 0
                                    ),
                                    new OA\Property(
                                        property: "account_id",
                                        type: "string",
                                        example: "wallet"
                                    ),
                                    new OA\Property(
                                        property: "loan_ref",
                                        type: "string",
                                        example: "LN-2026-0001"
                                    ),
                                    new OA\Property(
                                        property: "modified_by",
                                        type: "string",
                                        example: "System"
                                    ),
                                    new OA\Property(
                                        property: "InterestLeft",
                                        type: "number",
                                        example: 25000
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated."
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error."
            )
        ]
    )]
    public function listUserLoan()
    {
        logInfo(json_encode(auth()->user()->id), "User Loan");

        $loanResource = loan::where(
            'user_id',
            auth()->user()->id
        )->get();

        $loan = ListLoanResource::collection($loanResource);

        logInfo(json_encode($loan), "List User Loan");

        return count($loan)
            ? response()->json([
                'message' => 'Data Found!',
                'data' => $loan,
                'status' => 1,
            ])
            : response()->json([
                'message' => "no loan data",
                'data' => $loan,
                'status' => 0,
            ]);
    }

    #[OA\Post(
        path: "/api/v1/loan/pay",
        summary: "Repay an existing loan",
        description: "Validates the repayment request, finds the loan by its reference, encrypts the internal loan ID, and delegates payment processing to the generalpayloan method.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["loanRef", "amount", "accountType"],
                properties: [
                    new OA\Property(
                        property: "loanRef",
                        type: "string",
                        example: "LN-2026-0001",
                        description: "Reference of the loan being repaid."
                    ),
                    new OA\Property(
                        property: "amount",
                        type: "string",
                        example: "50000",
                        description: "Amount to repay."
                    ),
                    new OA\Property(
                        property: "accountType",
                        type: "string",
                        example: "wallet",
                        description: "Account type selected for repayment."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Payment request processed. The final response is returned by generalpayloan.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Loan payment successful"
                        ),
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 1
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Required fields are missing or the loan reference is invalid.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Invalid Loan!"
                        ),
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 0
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated."
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error."
            )
        ]
    )]
    public function PaylLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loanRef' => 'required',
            'amount' => 'required',
            'accountType' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = '';

            foreach (
                $validator->messages()->getMessages()
                as $field_name => $messages
            ) {
                foreach ($messages as $message) {
                    $errors .= "$message <br>";
                }
            }

            return response()->json([
                'message' => $errors,
                'status' => 0,
            ]);
        }

        $loan = loan::where('ref', $request->loanRef)->first();

        if (!$loan) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid Loan!',
            ]);
        }

        $request->merge([
            'method' => 'wallet',
            'payment' => date('Y-m-d'),
            'loanID' => Crypt::encryptString($loan->id),
        ]);

        return response()->json(
            app('App\Http\Controllers\LoanController')
                ->generalpayloan($request)
        );
    }

    #[OA\Get(
        path: "/api/v1/loan/repayment/{id}",
        summary: "View loan repayments",
        description: "Returns repayment records for the authenticated customer and the specified loan reference.",
        tags: ["Loan"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Loan reference, for example LN-2026-0001.",
                schema: new OA\Schema(type: "string"),
                example: "LN-2026-0001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Loan repayment records retrieved.",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated."
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error."
            )
        ]
    )]
    public function viewRepayment($id)
    {
        $userID = auth()->user()->id;

        $loan = LoanRepayment::join(
            'loans',
            'loans.id',
            '=',
            'loan_repayments.loan_id'
        )
            ->where([
                'loans.user_id' => $userID,
                'loans.ref' => $id,
            ])
            ->get();

        return response()->json($loan);
    }
}