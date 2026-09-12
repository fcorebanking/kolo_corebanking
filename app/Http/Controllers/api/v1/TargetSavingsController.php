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
                required: ["target_name", "target_amount", "withdrawal_date"],
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
                $request->withdrawal_date
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
}