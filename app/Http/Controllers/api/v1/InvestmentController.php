<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\company_status;
use App\Models\frequency;
use App\Models\Investmentbooking;
use App\Models\InvestmentProduct;
use App\Models\status;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class InvestmentController extends Controller
{
    #[OA\Get(
        path: "/api/v1/investment/investment-variable",
        summary: "Get investment variables",
        description: "Returns investment products, available frequencies, company statuses, and the authenticated user's accounts.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Investment variables retrieved successfully.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "product",
                            type: "array",
                            items: new OA\Items(type: "object"),
                            description: "Investment products belonging to the authenticated user's company."
                        ),
                        new OA\Property(
                            property: "freq",
                            type: "array",
                            items: new OA\Items(type: "object"),
                            description: "Available investment frequencies with IDs greater than 1."
                        ),
                        new OA\Property(
                            property: "status",
                            type: "array",
                            items: new OA\Items(type: "object"),
                            description: "Company status records."
                        ),
                        new OA\Property(
                            property: "userAccounts",
                            type: "array",
                            items: new OA\Items(type: "object"),
                            description: "Accounts associated with the authenticated user."
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function investmentVariable()
    {
        $data = [
            'product'      => InvestmentProduct::with('investmentfrequency')
                ->where('company_id', auth()->user()->company_id)
                ->get(),
            'freq'         => frequency::where('id', '>', 1)->get(),
            'status'       => company_status::all(),
            'userAccounts' => auth()->user()->wb_account_details,
        ];

        return json_encode($data);
    }

    #[OA\Get(
        path: "/api/v1/investment/all-investment-variable",
        summary: "List available investment plans",
        description: "Returns investment products belonging to the authenticated user's company, available frequencies, company statuses, and the user's accounts. The app reads investment plans from data.product.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Investment variables retrieved successfully.",
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
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "product",
                                    type: "array",
                                    description: "Available investment plans.",
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            new OA\Property(
                                                property: "id",
                                                type: "integer",
                                                example: 1
                                            ),
                                            new OA\Property(
                                                property: "product",
                                                type: "string",
                                                example: "Fixed Income Plan"
                                            ),
                                            new OA\Property(
                                                property: "cover_pic",
                                                type: "string",
                                                example: "public/images/investment.png"
                                            ),
                                            new OA\Property(
                                                property: "company_id",
                                                type: "integer",
                                                example: 14
                                            ),
                                            new OA\Property(
                                                property: "duration",
                                                type: "integer",
                                                example: 12
                                            ),
                                            new OA\Property(
                                                property: "interest_rate",
                                                type: "number",
                                                format: "float",
                                                example: 15
                                            ),
                                            new OA\Property(
                                                property: "minimum_investment",
                                                type: "number",
                                                example: 10000
                                            ),
                                            new OA\Property(
                                                property: "maximum_investment",
                                                type: "number",
                                                example: 1000000
                                            ),
                                            new OA\Property(
                                                property: "termination_charge",
                                                type: "number",
                                                example: 2
                                            ),
                                            new OA\Property(
                                                property: "investmentfrequency",
                                                type: "object",
                                                properties: [
                                                    new OA\Property(
                                                        property: "id",
                                                        type: "integer",
                                                        example: 1
                                                    ),
                                                    new OA\Property(
                                                        property: "frequency",
                                                        type: "string",
                                                        example: "Monthly"
                                                    )
                                                ]
                                            )
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: "freq",
                                    type: "array",
                                    items: new OA\Items(type: "object"),
                                    description: "Available investment frequencies."
                                ),
                                new OA\Property(
                                    property: "status",
                                    type: "array",
                                    items: new OA\Items(type: "object"),
                                    description: "Company status records."
                                ),
                                new OA\Property(
                                    property: "userAccounts",
                                    type: "array",
                                    items: new OA\Items(type: "object"),
                                    description: "Accounts associated with the authenticated user."
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function allInvestmentVariable()
    {
        $investmentProduct = InvestmentProduct::with('investmentfrequency')
            ->where('company_id', auth()->user()->company_id)
            ->get();

        $frequency = frequency::where('id', '>', 1)->get();
        $company = company_status::all();

        $data = [
            'product'      => $investmentProduct,
            'freq'         => $frequency,
            'status'       => $company,
            'userAccounts' => auth()->user()->wb_account_details,
        ];

        return response()->json([
            'message' => 'Data Found!',
            'data'    => $data,
            'status'  => 1,
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/investment/process-investment",
        summary: "Process an investment",
        description: "Calculates an investment for confirmation. This endpoint prepares the investment details but does not book the investment. The calculation response is returned at the response root.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["plan", "amount"],
                properties: [
                    new OA\Property(
                        property: "plan",
                        type: "integer",
                        example: 1,
                        description: "ID of the selected investment product."
                    ),
                    new OA\Property(
                        property: "amount",
                        type: "string",
                        example: "10000",
                        description: "Amount the customer intends to invest."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Investment calculation returned successfully.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "product",
                            type: "string",
                            example: "Fixed Income Plan"
                        ),
                        new OA\Property(
                            property: "amount",
                            type: "string",
                            example: "10000"
                        ),
                        new OA\Property(
                            property: "start",
                            type: "string",
                            format: "date",
                            example: "2026-09-21"
                        ),
                        new OA\Property(
                            property: "end",
                            type: "string",
                            format: "date",
                            example: "2027-09-21"
                        ),
                        new OA\Property(
                            property: "totalcash",
                            type: "string",
                            example: "11500"
                        ),
                        new OA\Property(
                            property: "interest",
                            type: "string",
                            example: "1500"
                        ),
                        new OA\Property(
                            property: "plan_interest",
                            type: "number",
                            example: 15
                        ),
                        new OA\Property(
                            property: "plantype",
                            type: "string",
                            example: "Fixed"
                        ),
                        new OA\Property(
                            property: "duration",
                            type: "integer",
                            example: 12
                        ),
                        new OA\Property(
                            property: "account_type",
                            type: "string",
                            example: "wallet",
                            description: "Account type to pass to the booking endpoint."
                        ),
                        new OA\Property(
                            property: "investmentproduct_id",
                            type: "integer",
                            example: 1,
                            description: "Investment product ID to pass to the booking endpoint."
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid investment details."),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 422, description: "Validation failed."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function processInvestment(Request $request)
    {
        $calculate = app('App\Http\Controllers\InvestmentController')
            ->calculateinvestment($request);

        return $calculate;
    }

    #[OA\Post(
        path: "/api/v1/investment/send-process-investment",
        summary: "Book an investment",
        description: "Books the investment after the customer confirms the processed investment. The authenticated user's UUID is supplied by the server. The status and platform are also set by the server and do not need to be sent by the client.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["amount", "account_type", "investmentproduct"],
                properties: [
                    new OA\Property(
                        property: "amount",
                        type: "string",
                        example: "10000",
                        description: "Investment amount returned by the processing endpoint."
                    ),
                    new OA\Property(
                        property: "account_type",
                        type: "string",
                        example: "wallet",
                        description: "Account type returned by the processing endpoint."
                    ),
                    new OA\Property(
                        property: "investmentproduct",
                        type: "integer",
                        example: 1,
                        description: "Investment product ID returned by the processing endpoint."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Investment booking request processed successfully. The response body depends on the delegated makeinvestment method."
            ),
            new OA\Response(
                response: 400,
                description: "Investment booking failed or the request was invalid."
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: false
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Unauthenticated."
                        ),
                        new OA\Property(
                            property: "trace_id",
                            type: "string",
                            format: "uuid",
                            example: "123e4567-e89b-12d3-a456-426614174000"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed."
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: false
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Error processing investment request."
                        ),
                        new OA\Property(
                            property: "trace_id",
                            type: "string",
                            format: "uuid",
                            example: "123e4567-e89b-12d3-a456-426614174000"
                        )
                    ]
                )
            )
        ]
    )]
    public function bookinvestment(Request $request)
    {
        $traceId = (string) Str::uuid();
        $authUser = auth()->user();

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'bookinvestment_wrapper_start',
            'authenticated' => (bool) $authUser,
            'auth_user_id' => $authUser?->id,
            'auth_company_id' => $authUser?->company_id,
            'auth_uuid' => $authUser?->uuid,
            'incoming_amount' => $request->amount,
            'incoming_account_type' => $request->account_type,
            'incoming_investmentproduct' => $request->investmentproduct,
            'incoming_status' => $request->status,
            'incoming_platform' => $request->platform,
        ], 'INVESTMENT DEBUG WRAPPER');

        if (! $authUser) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'wrapper_auth_failed',
            ], 'INVESTMENT DEBUG WRAPPER ERROR');

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
                'trace_id' => $traceId,
            ], 401);
        }

        $request->merge([
            'status' => 1,
            'uuid' => $authUser->uuid,
            'platform' => 'mobile',
            'trace_id' => $traceId,
        ]);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'bookinvestment_wrapper_after_merge',
            'uuid' => $request->uuid,
            'status' => $request->status,
            'platform' => $request->platform,
            'account_type' => $request->account_type,
            'amount' => $request->amount,
            'investmentproduct' => $request->investmentproduct,
        ], 'INVESTMENT DEBUG WRAPPER MERGED REQUEST');

        try {
            $send = app(\App\Http\Controllers\InvestmentController::class)
                ->makeinvestment($request);

            logInfo([
                'trace_id' => $traceId,
                'stage' => 'bookinvestment_wrapper_response',
                'response' => $send instanceof \Illuminate\Http\JsonResponse
                    ? $send->getData(true)
                    : $send,
            ], 'INVESTMENT DEBUG WRAPPER RESPONSE');

            return $send;

        } catch (\Throwable $e) {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'bookinvestment_wrapper_exception',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 'INVESTMENT DEBUG WRAPPER EXCEPTION');

            return response()->json([
                'status' => false,
                'message' => 'Error processing investment request.',
                'trace_id' => $traceId,
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/investment/view-investment",
        summary: "List customer investments (legacy response)",
        description: "Returns investment bookings associated with the authenticated user's accounts, including each booking's plan. The response is a JSON-encoded array.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Customer investments retrieved successfully.",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "wb_accountdetails_id",
                                type: "integer",
                                example: 10
                            ),
                            new OA\Property(
                                property: "investment_product_id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "amount",
                                type: "number",
                                example: 10000
                            ),
                            new OA\Property(
                                property: "wallet",
                                type: "number",
                                example: 10000
                            ),
                            new OA\Property(
                                property: "status",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "plan",
                                type: "object",
                                description: "Related investment plan."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function viewInvestment()
    {
        $Investmentbooking = Investmentbooking::with('plan')
            ->whereHas('wb_account_details', function ($q) {
                $q->where(
                    'wb_account_details.user_id',
                    auth()->user()->id
                );
            })
            ->get();

        return json_encode($Investmentbooking);
    }

    #[OA\Get(
        path: "/api/v1/investment/list-all-investment",
        summary: "List customer investments",
        description: "Returns investment bookings associated with the authenticated user's accounts, including each booking's investment plan. The results are wrapped in a data array.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Customer investments retrieved successfully.",
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
                                        property: "id",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "wb_accountdetails_id",
                                        type: "integer",
                                        example: 10
                                    ),
                                    new OA\Property(
                                        property: "investment_product_id",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "amount",
                                        type: "number",
                                        example: 10000
                                    ),
                                    new OA\Property(
                                        property: "wallet",
                                        type: "number",
                                        example: 10000
                                    ),
                                    new OA\Property(
                                        property: "balance",
                                        type: "number",
                                        example: 11500
                                    ),
                                    new OA\Property(
                                        property: "expected_balance",
                                        type: "number",
                                        example: 11500
                                    ),
                                    new OA\Property(
                                        property: "duration",
                                        type: "integer",
                                        example: 12
                                    ),
                                    new OA\Property(
                                        property: "start_date",
                                        type: "string",
                                        format: "date-time",
                                        example: "2026-09-21T00:00:00.000Z"
                                    ),
                                    new OA\Property(
                                        property: "end_date",
                                        type: "string",
                                        format: "date-time",
                                        example: "2027-09-21T00:00:00.000Z"
                                    ),
                                    new OA\Property(
                                        property: "status",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "paymentOption",
                                        type: "string",
                                        example: "wallet"
                                    ),
                                    new OA\Property(
                                        property: "plan",
                                        type: "object",
                                        description: "Related investment product."
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function viewInvestmentUpdate()
    {
        $Investmentbooking = Investmentbooking::with('plan')
            ->whereHas('wb_account_details', function ($q) {
                $q->where(
                    'wb_account_details.user_id',
                    auth()->user()->id
                );
            })
            ->get();

        return response()->json([
            'message' => 'Data Found!',
            'data'    => $Investmentbooking,
            'status'  => 1,
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/investment/terminate-investment",
        summary: "Terminate an investment",
        description: "Requests termination of an investment. The investment must be active, and termination proceeds and charges depend on the company's investment termination settings.",
        tags: ["Investment"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["id"],
                properties: [
                    new OA\Property(
                        property: "id",
                        type: "integer",
                        example: 1,
                        description: "ID of the investment booking to terminate."
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Termination request processed.",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "integer",
                            example: 1,
                            description: "1 indicates success; 0 indicates failure."
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Investment Terminated!"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated."),
            new OA\Response(response: 403, description: "Access denied."),
            new OA\Response(response: 500, description: "Internal server error.")
        ]
    )]
    public function TerminateInvestment(Request $request)
    {
        // ajax_investmentstatus
        $send = app('App\Http\Controllers\InvestmentController')
            ->ajax_investmentstatus($request);

        return $send;
    }

    public function ajax_investmentstatus($id)
    {
        $terminationoption = auth()->user()->company->setting
            ->investment_termination_control;

        $runninginvestment = Investmentbooking::where('id', $id)->first();
        $cashleft = 0;

        // wb_account_details
        // return $runninginvestment->wb_account_details->id;
        if (
            $runninginvestment->wb_account_details->id
            != $runninginvestment->wb_accountdetails_id
        ) {
            return response(json_encode([
                'status' => 0,
                'message' => 'Access Denied!',
            ]));
        }

        $termination_charge = $runninginvestment->plan->termination_charge;
        $amount = $runninginvestment->amount;
        $PI = $amount + $runninginvestment->wallet;
        $percent_charge = 0;

        if ($terminationoption == 1) {
            if ($runninginvestment->wallet > 1) {
                $percent_charge = ($termination_charge / 100)
                    * $runninginvestment->wallet;

                if ($runninginvestment->wallet > $percent_charge) {
                    $cashleft = (
                        $runninginvestment->wallet - $percent_charge
                    ) + $amount;
                } else {
                    // return response(json_encode([
                    //     'status' => 0,
                    //     'message' => 'You are not eligeble to withdraw at this time try again later'
                    // ]));
                }
            } else {
                // return response(json_encode([
                //     'status' => 0,
                //     'message' => 'You are not eligeble to withdraw at this time try again later (Low interest)'
                // ]));
            }
        } elseif ($terminationoption == 2) {
            $percent_charge = ($termination_charge / 100) * $amount;
            $cashleft = ($amount - $percent_charge)
                + $runninginvestment->wallet;

        } elseif ($terminationoption == 3) {
            $percent_charge = ($termination_charge / 100) * $PI;

            if ($PI > $percent_charge) {
                $cashleft = $PI - $percent_charge;
            } else {
                return response(json_encode([
                    'status' => 0,
                    'message' => 'You are not eligeble to withdraw at this time try again later',
                ]));
            }

        } elseif ($terminationoption == 4) {
            $cashleft = $amount;

        } else {
            return response(json_encode([
                'status' => 0,
                'message' => 'Error occured',
            ]));
        }

        $wb = wb_account_details::where(
            'id',
            $runninginvestment->wb_accountdetails_id
        )->first();

        if ($wb->user->company_id == auth()->user()->company_id) {
            if ($runninginvestment->status == 1) {
                $userwallet = user_wallet::where(
                    'wb_account_detail_id',
                    $runninginvestment->wb_accountdetails_id
                )->first();

                $balanceBefore = $userwallet->balance;
                $balanceAfter = $balanceBefore + $cashleft;

                $userwallet->update([
                    'balance' => $balanceAfter,
                ]);

                $desc = "Investment Termination";
                $date = date('Y-m-d H:i:s');

                $runninginvestment->update([
                    'status' => 2,
                ]); // Terminated

                $ledger_cr = null;
                $ledger_dr = null;

                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(
                    21,
                    auth()->user()->company_id
                );

                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }

                $data = [
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $amount,
                    "description"            => $desc,
                    "withdrawal_charge"      => $percent_charge,
                    "account_id"             => $runninginvestment->wb_accountdetails_id,
                    "branch_id"              => $wb->user->branch_id,
                    "transaction_reference"  => generate_txnID(),
                    "transaction_type_id"    => 21, // investment plan
                    "credit_debit_type_id"   => 1,  // debit 2
                    "transaction_remarks_id" => 2,  // success 2
                    "balance_after"          => $balanceAfter,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ];

                transactions::create($data);

                sendSMS(
                    $runninginvestment->wb_accountdetails_id,
                    "Credit Amt:$cashleft Charges:$percent_charge Desc:$desc Avail Bal:$userwallet->balance Date:$date",
                    false,
                    true,
                    'Investment Termination'
                );

                return response(json_encode([
                    'status' => 1,
                    'message' => "Investment Terminated!",
                ]));

            } else {
                return response(json_encode([
                    'status' => 0,
                    'message' => 'Investment no longer Active',
                ]));
            }

        } else {
            return response(json_encode([
                'status' => 0,
                'message' => 'User restricted '
                    . $wb->user->id . '=='
                    . auth()->user()->company_id . '=='
                    . $id,
            ]));
        }

        return response(json_encode([
            'status' => 0,
            'message' => 'An Error occured',
        ]));
    }
}