<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\FixedSaving;
use App\Services\FixedSavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Throwable;
use Illuminate\Support\Facades\DB;

class FixedSavingsController extends Controller
{
    protected FixedSavingsService $fixedSavingsService;

    public function __construct(FixedSavingsService $fixedSavingsService)
    {
        $this->fixedSavingsService = $fixedSavingsService;
    }

    /**
     * Create a new Fixed Savings.
     */
    #[OA\Post(
        path: "/api/v1/fixed-savings/create",
        summary: "Create a Fixed Savings",
        description: "Creates a new Fixed Savings plan for the authenticated user using the company's configured interest rate and early withdrawal fee.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["fixed_name", "fixed_amount", "withdrawal_date"],
                properties: [
                    new OA\Property(
                        property: "fixed_name",
                        type: "string",
                        example: "My New Car"
                    ),
                    new OA\Property(
                        property: "fixed_amount",
                        type: "number",
                        format: "float",
                        example: 1000000
                    ),
                    new OA\Property(
                        property: "withdrawal_date",
                        type: "string",
                        format: "date",
                        example: "2026-12-31"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Fixed Savings created successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 400,
                description: "Unable to create Fixed Savings"
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'fixed_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'fixed_amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'withdrawal_date' => [
                    'required',
                    'date',
                    'after:today',
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $fixedSavings = $this->fixedSavingsService->createFixed(
                $user,
                $request->fixed_name,
                (float) $request->fixed_amount,
                $request->withdrawal_date
            );

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings created successfully.',
                'data' => $fixedSavings,
            ], 201);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get the authenticated user's Fixed Savings.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/list",
        summary: "Get Fixed Savings list",
        description: "Fetches all Fixed Savings plans belonging to the authenticated user. An optional status filter may be supplied.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Fixed Savings status filter.",
                schema: new OA\Schema(
                    type: "string",
                    example: "active"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Fixed Savings"
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $query = FixedSaving::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id');

            /*
             * Optional status filter.
             */
            if ($request->filled('status')) {
                $query->where(
                    'status',
                    $request->status
                );
            }

            $fixeds = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings retrieved successfully.',
                'data' => $fixeds,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Fixed Savings.',
            ], 500);
        }
    }

        /**
     * Get authenticated user's Fixed Savings summary.
     *
     * Returns:
     * - Total fixed amount across all Fixed Savings.
     * - Number of Fixed Savings created.
     * - Total amount deposited across all Fixed Savings.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/summary",
        summary: "Get Fixed Savings summary",
        description: "Returns the authenticated user's Fixed Savings summary, including the combined fixed amount, total number of Fixed Savings created, and cumulative amount deposited across all Fixed Savings.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings summary retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Fixed Savings summary"
            )
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            /*
             * Get all Fixed Savings belonging to
             * the authenticated user.
             */
            $query = FixedSaving::query()
                ->where('user_id', $user->id);

            /*
             * Total number of Fixed Savings created.
             */
            $fixedSavingsCount = (clone $query)->count();

            /*
             * Combined fixed amount across
             * all Fixed Savings.
             */
            $totalFixedAmount = (float) (
                (clone $query)->sum('fixed_amount')
            );

            /*
             * Cumulative amount actually deposited
             * across all Fixed Savings.
             *
             * We use total_deposited rather than current_balance
             * because current_balance can become zero after withdrawal,
             * while total_deposited represents the cumulative
             * amount deposited into each fixed.
             */
            $totalAmountDeposited = (float) (
                (clone $query)->sum('total_deposited')
            );

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings summary retrieved successfully.',
                'data' => [
                    'fixed_savings_count' => $fixedSavingsCount,
                    'total_fixed_amount' => round($totalFixedAmount, 2),
                    'total_amount_deposited' => round($totalAmountDeposited, 2),
                ],
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Fixed Savings summary.',
            ], 500);
        }
    }

    /**
     * Get one Fixed Savings.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/details/{id}",
        summary: "Get Fixed Savings details",
        description: "Fetches a specific Fixed Savings belonging to the authenticated user, including its transaction history.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Fixed Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Fixed Savings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Fixed Savings"
            )
        ]
    )]
    public function show(Request $request, $id): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $fixedSavings = FixedSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$fixedSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fixed Savings not found.',
                ], 404);
            }

            $fixedSavings->load([
                'transactions' => function ($query) {
                    $query->latest();
                },
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings retrieved successfully.',
                'data' => $fixedSavings,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Fixed Savings.',
            ], 500);
        }
    }

    /**
     * Deposit money into Fixed Savings.
     */
    #[OA\Post(
        path: "/api/v1/fixed-savings/deposit/{id}",
        summary: "Deposit into Fixed Savings",
        description: "Deposits money from the authenticated user's source savings account into the specified Fixed Savings plan.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Fixed Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(
                        property: "amount",
                        type: "number",
                        format: "float",
                        example: 10000
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings deposit successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Fixed Savings not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 400,
                description: "Fixed Savings deposit failed"
            )
        ]
    )]
    public function deposit(Request $request, $id): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            /*
             * Make sure this fixed belongs to the authenticated user.
             */
            $fixedSavings = FixedSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$fixedSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fixed Savings not found.',
                ], 404);
            }

            $result = $this->fixedSavingsService->deposit(
                $fixedSavings->id,
                $user,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings deposit successful.',
                'data' => $result,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Withdraw from Fixed Savings.
     *
     * This may be either a maturity withdrawal or
     * an early withdrawal depending on the date/status.
     */
    #[OA\Post(
        path: "/api/v1/fixed-savings/withdraw/{id}",
        summary: "Withdraw from Fixed Savings",
        description: "Withdraws the full available Fixed Savings balance. If the withdrawal is before the maturity date, the configured early withdrawal fee is deducted. On maturity, the full matured balance including accrued interest is released.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Fixed Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings withdrawal successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Fixed Savings not found"
            ),
            new OA\Response(
                response: 400,
                description: "Fixed Savings withdrawal failed"
            )
        ]
    )]
    public function withdraw(Request $request, $id): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $fixedSavings = FixedSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$fixedSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fixed Savings not found.',
                ], 404);
            }

            $result = $this->fixedSavingsService->withdraw(
                $fixedSavings->id,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings withdrawal successful.',
                'data' => $result,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Fixed Savings transaction history.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/transactions/{id}",
        summary: "Get Fixed Savings transactions",
        description: "Fetches the transaction history for a specific Fixed Savings plan belonging to the authenticated user.",
        tags: ["Fixed Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Fixed Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings transactions retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Fixed Savings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Fixed Savings transactions"
            )
        ]
    )]
    public function transactions(Request $request, $id): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $fixedSavings = FixedSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$fixedSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fixed Savings not found.',
                ], 404);
            }

            $query = $fixedSavings->transactions()
                ->latest();

            $transactions = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings transactions retrieved successfully.',
                'data' => $transactions,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Fixed Savings transactions.',
            ], 500);
        }
    }

        /**
     * Get all Fixed Savings belonging to users in the
     * authenticated administrator's company.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/company/list",
        summary: "Get company Fixed Savings",
        description: "Returns all Fixed Savings belonging to users under the authenticated administrator's company. The company is determined automatically from the authenticated user.",
        tags: ["Fixed Savings - Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Fixed Savings status filter.",
                schema: new OA\Schema(
                    type: "string",
                    example: "active"
                )
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Number of records per page.",
                schema: new OA\Schema(
                    type: "integer",
                    minimum: 1,
                    maximum: 100,
                    example: 20
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Company Fixed Savings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 403,
                description: "Administrator access required"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve company Fixed Savings"
            )
        ]
    )]
    public function companyIndex(Request $request): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            /*
             * Only administrators/root users should be able
             * to view Fixed Savings belonging to the entire company.
             *
             * Adjust these permission names if your application's
             * actual admin permissions use different names.
             */
            if (!apiCando('root|admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Administrator access required.',
                ], 403);
            }

            if (empty($user->company_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is not linked to a company.',
                ], 400);
            }

            $query = FixedSaving::with([
      'user:id,uuid,first_name,last_name,username,email,phone'
             ])
       ->where('company_id', $user->company_id)
       ->orderByDesc('id');

            /*
             * Optional status filter.
             */
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            /*
             * Prevent excessively large API responses.
             */
            $perPage = min(
                max((int) $request->get('per_page', 20), 1),
                100
            );

            $fixeds = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Company Fixed Savings retrieved successfully.',
                'company_id' => $user->company_id,
                'data' => $fixeds,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve company Fixed Savings.',
            ], 500);
        }
    }

        /**
     * Get the authenticated company's Fixed Savings settings.
     */
    #[OA\Get(
        path: "/api/v1/fixed-savings/company/settings",
        summary: "Get company Fixed Savings settings",
        description: "Returns the Fixed Savings interest rate and early withdrawal fee configured for the authenticated administrator's company.",
        tags: ["Fixed Savings - Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings settings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 403,
                description: "Administrator access required"
            ),
            new OA\Response(
                response: 404,
                description: "Company settings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Fixed Savings settings"
            )
        ]
    )]
    public function companySettings(Request $request): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            if (!apiCando('root|admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Administrator access required.',
                ], 403);
            }

            if (empty($user->company_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is not linked to a company.',
                ], 400);
            }

            $settings = DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->first([
                    'company_id',
                    'fixed_savings_interest_rate',
                    'fixed_savings_early_withdrawal_fee_percentage',
                ]);

            if (!$settings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Company settings not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings settings retrieved successfully.',
                'data' => $settings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Fixed Savings settings.',
            ], 500);
        }
    }

        /**
     * Get or update the authenticated company's Fixed Savings settings.
     */
    #[OA\Put(
        path: "/api/v1/fixed-savings/company/settings",
        summary: "Update company Fixed Savings settings",
        description: "Updates the Fixed Savings annual interest rate and early withdrawal fee percentage for the authenticated administrator's company.",
        tags: ["Fixed Savings - Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "fixed_savings_interest_rate",
                    "fixed_savings_early_withdrawal_fee_percentage"
                ],
                properties: [
                    new OA\Property(
                        property: "fixed_savings_interest_rate",
                        type: "number",
                        format: "float",
                        minimum: 0,
                        maximum: 100,
                        example: 12
                    ),
                    new OA\Property(
                        property: "fixed_savings_early_withdrawal_fee_percentage",
                        type: "number",
                        format: "float",
                        minimum: 0,
                        maximum: 100,
                        example: 5
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Fixed Savings settings updated successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 403,
                description: "Administrator access required"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 404,
                description: "Company settings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to update Fixed Savings settings"
            )
        ]
    )]
    public function updateCompanySettings(Request $request): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            /*
             * Only administrators/root users can change
             * company Fixed Savings settings.
             */
            if (!apiCando('root|admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Administrator access required.',
                ], 403);
            }

            if (empty($user->company_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is not linked to a company.',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'fixed_savings_interest_rate' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'fixed_savings_early_withdrawal_fee_percentage' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            /*
             * Find the existing settings row belonging to
             * the authenticated administrator's company.
             *
             * We deliberately DO NOT accept company_id from
             * the request.
             */
            $companySettings = DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->first();

            if (!$companySettings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Company settings not found.',
                ], 404);
            }

            DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->update([
                    'fixed_savings_interest_rate' => $request->fixed_savings_interest_rate,
                    'fixed_savings_early_withdrawal_fee_percentage' => $request->fixed_savings_early_withdrawal_fee_percentage,
                    'updated_at' => now(),
                ]);

            /*
             * Read the values back from the database so the API
             * returns exactly what was actually stored.
             */
            $updatedSettings = DB::table('company_settings')
                ->where('company_id', $user->company_id)
                ->first([
                    'company_id',
                    'fixed_savings_interest_rate',
                    'fixed_savings_early_withdrawal_fee_percentage',
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Fixed Savings settings updated successfully.',
                'data' => $updatedSettings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to update Fixed Savings settings.',
            ], 500);
        }
    }
}