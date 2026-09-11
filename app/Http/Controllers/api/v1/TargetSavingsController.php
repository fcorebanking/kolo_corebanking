<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\TargetSaving;
use App\Services\TargetSavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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