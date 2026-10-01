<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\TargetSaving;
use App\Services\TargetSavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Throwable;
use Illuminate\Support\Facades\DB;

class TargetSavingsController extends Controller
{
    protected TargetSavingsService $targetSavingsService;

    public function __construct(TargetSavingsService $targetSavingsService)
    {
        $this->targetSavingsService = $targetSavingsService;
    }

    /**
     * Create a new Target Savings.
     */
    #[OA\Post(
        path: "/api/v1/target-savings/create",
        summary: "Create a Target Savings",
        description: "Creates a new Target Savings plan for the authenticated user using the company's configured interest rate and early withdrawal fee.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "target_name",
                    "target_amount",
                    "withdrawal_date",
                    "auto_deposit_amount",
                    "auto_deposit_frequency",
                    "first_deposit_at"
                ],
                properties: [
                    new OA\Property(
                        property: "target_name",
                        type: "string",
                        example: "My New Car"
                    ),
                    new OA\Property(
                        property: "target_amount",
                        type: "number",
                        format: "float",
                        example: 1000000
                    ),
                    new OA\Property(
                        property: "withdrawal_date",
                        type: "string",
                        format: "date",
                        example: "2026-12-31"
                    ),
                    new OA\Property(
                        property: "auto_deposit_amount",
                        type: "number",
                        format: "float",
                        minimum: 0.01,
                        example: 20000
                    ),
                    new OA\Property(
                        property: "auto_deposit_frequency",
                        type: "string",
                        enum: ["daily", "weekly", "monthly"],
                        example: "weekly"
                    ),
                    new OA\Property(
                        property: "first_deposit_at",
                        type: "string",
                        format: "date-time",
                        example: "2026-10-05 09:00:00"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Target Savings created successfully"
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
                description: "Unable to create Target Savings"
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'target_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'target_amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'withdrawal_date' => [
                    'required',
                    'date',
                    'after:today',
                ],

                'auto_deposit_amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                    'lte:target_amount',
                ],

                'auto_deposit_frequency' => [
                    'required',
                    'string',
                    'in:daily,weekly,monthly',
                ],

                'first_deposit_at' => [
                    'required',
                    'date',
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

            $targetSavings = $this->targetSavingsService->createTarget(
                $user,
                $request->target_name,
                (float) $request->target_amount,
                $request->withdrawal_date,
                (float) $request->auto_deposit_amount,
                $request->auto_deposit_frequency,
                $request->first_deposit_at
            );

            return response()->json([
                'status' => true,
                'message' => 'Target Savings created successfully.',
                'data' => $targetSavings,
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
     * Get the authenticated user's Target Savings.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/list",
        summary: "Get Target Savings list",
        description: "Fetches all Target Savings plans belonging to the authenticated user. An optional status filter may be supplied.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Target Savings status filter.",
                schema: new OA\Schema(
                    type: "string",
                    example: "active"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Target Savings"
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

            $query = TargetSaving::query()
                ->with('schedule')
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

            $targets = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Target Savings retrieved successfully.',
                'data' => $targets,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Target Savings.',
            ], 500);
        }
    }

        /**
     * Get authenticated user's Target Savings summary.
     *
     * Returns:
     * - Total target amount across all Target Savings.
     * - Number of Target Savings created.
     * - Total amount deposited across all Target Savings.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/summary",
        summary: "Get Target Savings summary",
        description: "Returns the authenticated user's Target Savings summary, including the combined target amount, total number of Target Savings created, and cumulative amount deposited across all Target Savings.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings summary retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Target Savings summary"
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
             * Get all Target Savings belonging to
             * the authenticated user.
             */
            $query = TargetSaving::query()
                ->where('user_id', $user->id);

            /*
             * Total number of Target Savings created.
             */
            $targetSavingsCount = (clone $query)->count();

            /*
             * Combined target amount across
             * all Target Savings.
             */
            $totalTargetAmount = (float) (
                (clone $query)->sum('target_amount')
            );

            /*
             * Cumulative amount actually deposited
             * across all Target Savings.
             *
             * We use total_deposited rather than current_balance
             * because current_balance can become zero after withdrawal,
             * while total_deposited represents the cumulative
             * amount deposited into each target.
             */
            $totalAmountDeposited = (float) (
                (clone $query)->sum('total_deposited')
            );

            return response()->json([
                'status' => true,
                'message' => 'Target Savings summary retrieved successfully.',
                'data' => [
                    'target_savings_count' => $targetSavingsCount,
                    'total_target_amount' => round($totalTargetAmount, 2),
                    'total_amount_deposited' => round($totalAmountDeposited, 2),
                ],
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Target Savings summary.',
            ], 500);
        }
    }

    /**
     * Get one Target Savings.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/details/{id}",
        summary: "Get Target Savings details",
        description: "Fetches a specific Target Savings belonging to the authenticated user, including its transaction history.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Target Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Target Savings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Target Savings"
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

            $targetSavings = TargetSaving::query()
                ->with(['schedule', 'transactions'])
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$targetSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Target Savings not found.',
                ], 404);
            }

            $targetSavings->load([
                'transactions' => function ($query) {
                    $query->latest();
                },
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Target Savings retrieved successfully.',
                'data' => $targetSavings,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Target Savings.',
            ], 500);
        }
    }

    /**
     * Deposit money into Target Savings.
     */
    #[OA\Post(
        path: "/api/v1/target-savings/deposit/{id}",
        summary: "Deposit into Target Savings",
        description: "Deposits money from the authenticated user's source savings account into the specified Target Savings plan.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Target Savings ID.",
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
                description: "Target Savings deposit successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Target Savings not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 400,
                description: "Target Savings deposit failed"
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
             * Make sure this target belongs to the authenticated user.
             */
            $targetSavings = TargetSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$targetSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Target Savings not found.',
                ], 404);
            }

            $result = $this->targetSavingsService->deposit(
                $targetSavings->id,
                $user,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Target Savings deposit successful.',
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
     * Withdraw from Target Savings.
     *
     * This may be either a maturity withdrawal or
     * an early withdrawal depending on the date/status.
     */
    #[OA\Post(
        path: "/api/v1/target-savings/withdraw/{id}",
        summary: "Withdraw from Target Savings",
        description: "Withdraws the full available Target Savings balance. If the withdrawal is before the maturity date, the configured early withdrawal fee is deducted. On maturity, the full matured balance including accrued interest is released.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Target Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings withdrawal successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Target Savings not found"
            ),
            new OA\Response(
                response: 400,
                description: "Target Savings withdrawal failed"
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

            $targetSavings = TargetSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$targetSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Target Savings not found.',
                ], 404);
            }

            $result = $this->targetSavingsService->withdraw(
                $targetSavings->id,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Target Savings withdrawal successful.',
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
     * Get Target Savings transaction history.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/transactions/{id}",
        summary: "Get Target Savings transactions",
        description: "Fetches the transaction history for a specific Target Savings plan belonging to the authenticated user.",
        tags: ["Target Savings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Target Savings ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings transactions retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Target Savings not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Target Savings transactions"
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

            $targetSavings = TargetSaving::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$targetSavings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Target Savings not found.',
                ], 404);
            }

            $query = $targetSavings->transactions()
                ->latest();

            $transactions = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Target Savings transactions retrieved successfully.',
                'data' => $transactions,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Target Savings transactions.',
            ], 500);
        }
    }

        /**
     * Get all Target Savings belonging to users in the
     * authenticated administrator's company.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/company/list",
        summary: "Get company Target Savings",
        description: "Returns all Target Savings belonging to users under the authenticated administrator's company. The company is determined automatically from the authenticated user.",
        tags: ["Target Savings - Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Target Savings status filter.",
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
                description: "Company Target Savings retrieved successfully"
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
                description: "Unable to retrieve company Target Savings"
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
             * to view Target Savings belonging to the entire company.
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

            $query = TargetSaving::with([
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

            $targets = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Company Target Savings retrieved successfully.',
                'company_id' => $user->company_id,
                'data' => $targets,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve company Target Savings.',
            ], 500);
        }
    }

        /**
     * Get the authenticated company's Target Savings settings.
     */
    #[OA\Get(
        path: "/api/v1/target-savings/company/settings",
        summary: "Get company Target Savings settings",
        description: "Returns the Target Savings interest rate and early withdrawal fee configured for the authenticated administrator's company.",
        tags: ["Target Savings - Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Target Savings settings retrieved successfully"
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
                description: "Unable to retrieve Target Savings settings"
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
                    'target_savings_interest_rate',
                    'target_savings_early_withdrawal_fee_percentage',
                ]);

            if (!$settings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Company settings not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Target Savings settings retrieved successfully.',
                'data' => $settings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Target Savings settings.',
            ], 500);
        }
    }

        /**
     * Get or update the authenticated company's Target Savings settings.
     */
    #[OA\Put(
        path: "/api/v1/target-savings/company/settings",
        summary: "Update company Target Savings settings",
        description: "Updates the Target Savings annual interest rate and early withdrawal fee percentage for the authenticated administrator's company.",
        tags: ["Target Savings - Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "target_savings_interest_rate",
                    "target_savings_early_withdrawal_fee_percentage"
                ],
                properties: [
                    new OA\Property(
                        property: "target_savings_interest_rate",
                        type: "number",
                        format: "float",
                        minimum: 0,
                        maximum: 100,
                        example: 12
                    ),
                    new OA\Property(
                        property: "target_savings_early_withdrawal_fee_percentage",
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
                description: "Target Savings settings updated successfully"
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
                description: "Unable to update Target Savings settings"
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
             * company Target Savings settings.
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
                'target_savings_interest_rate' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'target_savings_early_withdrawal_fee_percentage' => [
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
                    'target_savings_interest_rate' => $request->target_savings_interest_rate,
                    'target_savings_early_withdrawal_fee_percentage' => $request->target_savings_early_withdrawal_fee_percentage,
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
                    'target_savings_interest_rate',
                    'target_savings_early_withdrawal_fee_percentage',
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Target Savings settings updated successfully.',
                'data' => $updatedSettings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to update Target Savings settings.',
            ], 500);
        }
    }
}