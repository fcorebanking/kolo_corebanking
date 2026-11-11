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
use App\Services\FinovaApiService;
use Illuminate\Http\JsonResponse;

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
public function eligibility(): JsonResponse
{
    return app(FinovaApiService::class)->get(
        '/loan/eligibility',
        request()->bearerToken()
    );
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
public function processLoan(Request $request): JsonResponse
{
    return app(FinovaApiService::class)->post(
        '/loan/process-loan',
        $request->bearerToken(),
        $request->all()
    );
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
public function PostBooklLoan(Request $request): JsonResponse
{
    return app(FinovaApiService::class)->post(
        '/loan/send-process-loan',
        $request->bearerToken(),
        $request->all()
    );
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
public function listUserLoan(): JsonResponse
{
    return app(FinovaApiService::class)->get(
        '/loan/list-loan',
        request()->bearerToken()
    );
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
public function PaylLoan(Request $request): JsonResponse
{
    return app(FinovaApiService::class)->post(
        '/loan/pay',
        $request->bearerToken(),
        $request->all()
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
public function viewRepayment(string $id): JsonResponse
{
    return app(FinovaApiService::class)->get(
        "/loan/repayment/{id}",
        request()->bearerToken()
    );
}
}