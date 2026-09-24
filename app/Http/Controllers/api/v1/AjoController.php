<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Ajo;
use App\Models\AjoMember;
use App\Models\AjoTransaction;
use App\Services\AjoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

class AjoController extends Controller
{
    protected AjoService $ajoService;

    public function __construct(AjoService $ajoService)
    {
        $this->ajoService = $ajoService;
    }

    #[OA\Post(
        path: "/api/v1/ajo/create",
        summary: "Create an Ajo",
        description: "Creates an Ajo group. The authenticated creator is the overseer only and is not added as a member. The company creation fee and maturity fee percentage are snapshotted into the Ajo. The returned terms contain the collection rules and the 40% company / 60% creator fee split.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "ajo_name",
                    "collection_amount",
                    "collection_frequency",
                    "collection_time",
                    "start_date",
                    "maturity_date"
                ],
                properties: [
                    new OA\Property(property: "ajo_name", type: "string", example: "Friends Monthly Ajo"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Monthly savings group for six friends."),
                    new OA\Property(property: "collection_amount", type: "number", format: "float", example: 50000),
                    new OA\Property(property: "collection_frequency", type: "string", enum: ["daily", "weekly", "monthly"], example: "monthly"),
                    new OA\Property(property: "collection_interval", type: "integer", minimum: 1, example: 1),
                    new OA\Property(property: "collection_day", type: "integer", nullable: true, example: 15),
                    new OA\Property(property: "collection_time", type: "string", example: "08:00"),
                    new OA\Property(property: "start_date", type: "string", format: "date", example: "2026-10-01"),
                    new OA\Property(property: "maturity_date", type: "string", format: "date", example: "2027-04-01"),
                    new OA\Property(property: "early_leave_penalty_type", type: "string", enum: ["fixed", "percentage"], example: "percentage"),
                    new OA\Property(property: "early_leave_penalty_value", type: "number", format: "float", example: 5),
                    new OA\Property(property: "insufficient_balance_fee_type", type: "string", enum: ["fixed", "percentage"], example: "fixed"),
                    new OA\Property(property: "insufficient_balance_fee_value", type: "number", format: "float", example: 1000),
                    new OA\Property(property: "max_members", type: "integer", nullable: true, example: 10),
                    new OA\Property(property: "visibility", type: "string", enum: ["public", "private"], example: "public"),
                    new OA\Property(property: "terms", type: "string", nullable: true, example: "Members must maintain the agreed savings discipline.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ajo created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to create Ajo"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ajo_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'collection_amount' => ['required', 'numeric', 'gt:0'],
            'collection_frequency' => ['required', 'string', 'in:daily,weekly,monthly'],
            'collection_interval' => ['nullable', 'integer', 'min:1'],
            'collection_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'collection_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'maturity_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'early_leave_penalty_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'early_leave_penalty_value' => ['nullable', 'numeric', 'min:0'],
            'insufficient_balance_fee_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'insufficient_balance_fee_value' => ['nullable', 'numeric', 'min:0'],
            'max_members' => ['nullable', 'integer', 'min:1'],
            'visibility' => ['nullable', 'string', 'in:public,private'],
            'terms' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();

            $ajo = $this->ajoService->createAjo(
                $user,
                $request->only([
                    'ajo_name',
                    'description',
                    'collection_amount',
                    'collection_frequency',
                    'collection_interval',
                    'collection_day',
                    'collection_time',
                    'start_date',
                    'maturity_date',
                    'early_leave_penalty_type',
                    'early_leave_penalty_value',
                    'insufficient_balance_fee_type',
                    'insufficient_balance_fee_value',
                    'max_members',
                    'visibility',
                    'terms',
                ])
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo created successfully.',
                'data' => $ajo,
            ], 201);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to create Ajo.',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/ajo/account",
        summary: "Get personal Ajo Account",
        description: "Returns the authenticated user's separate personal Ajo Account. Older users can have the account created on demand until registration is updated to create it automatically.",
        tags: ["Ajo Account"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Personal Ajo Account retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 400, description: "Unable to retrieve personal Ajo Account"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function account(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();
            $account = $this->ajoService->getOrCreateAjoAccount($user);

            return response()->json([
                'status' => true,
                'message' => 'Personal Ajo Account retrieved successfully.',
                'data' => $account,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve personal Ajo Account.',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/ajo/mine",
        summary: "Get the authenticated user's Ajo groups and transactions",
        description: "Returns the authenticated user's current Ajo memberships, Ajos they created as overseer, their separate personal Ajo Account, and all Ajo transactions recorded against their user account. Creators are not treated as members; creator-owned Ajos are returned separately from current member Ajos.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Authenticated user's Ajo overview retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Ajo overview retrieved successfully."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "ajo_account",
                                    type: "object",
                                    nullable: true,
                                    additionalProperties: true
                                ),
                                new OA\Property(
                                    property: "member_ajos",
                                    type: "array",
                                    items: new OA\Items(type: "object", additionalProperties: true)
                                ),
                                new OA\Property(
                                    property: "created_ajos",
                                    type: "array",
                                    items: new OA\Items(type: "object", additionalProperties: true)
                                ),
                                new OA\Property(
                                    property: "transactions",
                                    type: "array",
                                    items: new OA\Items(type: "object", additionalProperties: true)
                                ),
                                new OA\Property(property: "member_ajo_count", type: "integer", example: 2),
                                new OA\Property(property: "created_ajo_count", type: "integer", example: 1),
                                new OA\Property(property: "transaction_count", type: "integer", example: 12)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function mine(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();

            $ajoAccount = $this->ajoService->getOrCreateAjoAccount($user);

            // The creator is NOT a member. Current membership therefore comes
            // only from active or pending-early-leave member records.
            $members = AjoMember::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    'active',
                    'leave_requested',
                ])
                ->latest('id')
                ->get();

            $memberAjoIds = $members
                ->pluck('ajo_id')
                ->filter()
                ->unique()
                ->values();

            $memberAjoMap = Ajo::query()
                ->whereIn('id', $memberAjoIds)
                ->get()
                ->keyBy('id');

            $memberAjos = $members
                ->map(function (AjoMember $member) use ($memberAjoMap) {
                    $ajo = $memberAjoMap->get($member->ajo_id);

                    if (!$ajo) {
                        return null;
                    }

                    return [
                        'id' => $ajo->id,
                        'role' => 'member',
                        'membership_id' => $member->id,
                        'membership_status' => $member->status,
                        'ajo_name' => $ajo->ajo_name,
                        'description' => $ajo->description,
                        'creator_user_id' => $ajo->creator_user_id,
                        'collection_amount' => (float) $ajo->collection_amount,
                        'collection_frequency' => $ajo->collection_frequency,
                        'collection_interval' => $ajo->collection_interval,
                        'collection_day' => $ajo->collection_day,
                        'collection_time' => $ajo->collection_time,
                        'start_date' => $ajo->start_date,
                        'maturity_date' => $ajo->maturity_date,
                        'next_collection_at' => $ajo->next_collection_at,
                        'creation_fee' => (float) $ajo->creation_fee,
                        'maturity_fee_percentage' => (float) $ajo->maturity_fee_percentage,
                        'early_leave_penalty_type' => $ajo->early_leave_penalty_type,
                        'early_leave_penalty_value' => (float) $ajo->early_leave_penalty_value,
                        'insufficient_balance_fee_type' => $ajo->insufficient_balance_fee_type,
                        'insufficient_balance_fee_value' => (float) $ajo->insufficient_balance_fee_value,
                        'max_members' => $ajo->max_members,
                        'visibility' => $ajo->visibility,
                        'terms' => $ajo->terms,
                        'terms_version' => $ajo->terms_version,
                        'company_fee_share_percentage' => (float) $ajo->company_fee_share_percentage,
                        'creator_fee_share_percentage' => (float) $ajo->creator_fee_share_percentage,
                        'status' => $ajo->status,
                        'member_balance' => (float) $member->balance,
                        'total_contributed' => (float) $member->total_contributed,
                        'insufficient_balance_count' => (int) $member->insufficient_balance_count,
                        'total_insufficient_balance_fees' => (float) $member->total_insufficient_balance_fees,
                        'total_maturity_fees' => (float) $member->total_maturity_fees,
                        'total_early_leave_penalties' => (float) $member->total_early_leave_penalties,
                        'joined_at' => $member->joined_at,
                        'left_at' => $member->left_at,
                    ];
                })
                ->filter()
                ->values();

            // Creators are overseers only, so these are deliberately returned
            // separately and are never represented as member positions.
            $createdAjos = Ajo::query()
                ->where('creator_user_id', $user->id)
                ->latest('id')
                ->get();

            $transactions = AjoTransaction::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Ajo overview retrieved successfully.',
                'data' => [
                    'ajo_account' => $ajoAccount,
                    'member_ajos' => $memberAjos,
                    'created_ajos' => $createdAjos,
                    'transactions' => $transactions,
                    'member_ajo_count' => $memberAjos->count(),
                    'created_ajo_count' => $createdAjos->count(),
                    'transaction_count' => $transactions->count(),
                ],
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Ajo overview.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/account/deposit",
        summary: "Deposit into personal Ajo Account",
        description: "Voluntarily transfers money from the authenticated user's main wallet to their personal Ajo Account. This is not a group collection.",
        tags: ["Ajo Account"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", minimum: 0.01, example: 100000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo Account funded successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to fund Ajo Account"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function depositToAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();
            $result = $this->ajoService->depositToAjoAccount(
                $user,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo Account funded successfully.',
                'data' => $result,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to fund Ajo Account.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/account/withdraw",
        summary: "Withdraw from personal Ajo Account",
        description: "Voluntarily transfers available money from the authenticated user's personal Ajo Account back to the main wallet. Money already committed to an active Ajo member position is not available through this endpoint.",
        tags: ["Ajo Account"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", minimum: 0.01, example: 25000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo Account withdrawal successful"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Withdrawal exceeds available Ajo Account balance"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function withdrawFromAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();
            $result = $this->ajoService->withdrawFromAjoAccount(
                $user,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo Account withdrawal successful.',
                'data' => $result,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to withdraw from Ajo Account.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/invite",
        summary: "Invite a member to an Ajo",
        description: "Allows only the Ajo creator to invite another user by username. The current Ajo terms are snapshotted into the invitation.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 15)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["username"],
                properties: [
                    new OA\Property(property: "username", type: "string", example: "john_doe")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Invitation created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Only the Ajo creator can invite members"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to create invitation"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function invite(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $creator = $this->authenticatedUser();
            $invitation = $this->ajoService->inviteMember(
                $ajoId,
                $creator,
                $request->username
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo invitation created successfully.',
                'data' => $invitation,
            ], 201);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to create Ajo invitation.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/join",
        summary: "Join a public Ajo",
        description: "Joins a public active Ajo after the authenticated user explicitly accepts the displayed terms. Joining does not move or debit money.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 15)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["accept_terms"],
                properties: [
                    new OA\Property(property: "accept_terms", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Joined Ajo successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to join Ajo"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function join(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accept_terms' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();
            $member = $this->ajoService->joinAjo(
                $ajoId,
                $user,
                true
            );

            return response()->json([
                'status' => true,
                'message' => 'Joined Ajo successfully.',
                'data' => $member,
            ], 201);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to join Ajo.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/invitations/{invitationId}/accept",
        summary: "Accept an Ajo invitation",
        description: "Accepts a pending Ajo invitation after the authenticated user explicitly accepts the invitation terms. No money is moved when accepting.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "invitationId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 25)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["accept_terms"],
                properties: [
                    new OA\Property(property: "accept_terms", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Invitation accepted successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to accept invitation"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function acceptInvitation(Request $request, int $invitationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accept_terms' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();
            $member = $this->ajoService->acceptInvitation(
                $invitationId,
                $user,
                true
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo invitation accepted successfully.',
                'data' => $member,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to accept Ajo invitation.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/invitations/{invitationId}/decline",
        summary: "Decline an Ajo invitation",
        description: "Declines a pending invitation belonging to the authenticated user.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "invitationId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 25)
        ],
        responses: [
            new OA\Response(response: 200, description: "Invitation declined successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 400, description: "Unable to decline invitation"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function declineInvitation(Request $request, int $invitationId): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();
            $invitation = $this->ajoService->declineInvitation(
                $invitationId,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo invitation declined successfully.',
                'data' => $invitation,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to decline Ajo invitation.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/leave/request",
        summary: "Request early leave from an Ajo",
        description: "Allows an active Ajo member to request early leave. No funds are moved until the Ajo creator approves the request.",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 15)
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "reason", type: "string", nullable: true, example: "I need the funds for an unexpected expense.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Early-leave request submitted successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to submit early-leave request"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function requestEarlyLeave(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();
            $member = $this->ajoService->requestEarlyLeave(
                $ajoId,
                $user,
                $request->filled('reason') ? $request->reason : null
            );

            return response()->json([
                'status' => true,
                'message' => 'Early-leave request submitted successfully.',
                'data' => $member,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to submit early-leave request.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/members/{ajoMemberId}/leave/approve",
        summary: "Approve Ajo member early leave",
        description: "Allows only the Ajo creator to approve a pending early-leave request. The configured penalty is deducted from the member's Ajo position, split 40% to the company and 60% to the creator, and the remaining member balance is returned to the member's personal Ajo Account.",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoMemberId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 42)
        ],
        responses: [
            new OA\Response(response: 200, description: "Early leave approved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 400, description: "Unable to approve early leave"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function approveEarlyLeave(Request $request, int $ajoMemberId): JsonResponse
    {
        try {
            $creator = $this->authenticatedUser();
            $result = $this->ajoService->approveEarlyLeave(
                $ajoMemberId,
                $creator
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo member early leave approved successfully.',
                'data' => $result,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to approve Ajo member early leave.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/members/{ajoMemberId}/leave/reject",
        summary: "Reject Ajo member early leave",
        description: "Allows only the Ajo creator to reject a pending early-leave request without moving member funds.",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoMemberId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 42)
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "reason", type: "string", nullable: true, example: "Please continue until the next collection cycle.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Early leave rejected successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to reject early leave"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function rejectEarlyLeave(Request $request, int $ajoMemberId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $creator = $this->authenticatedUser();
            $member = $this->ajoService->rejectEarlyLeave(
                $ajoMemberId,
                $creator,
                $request->filled('reason') ? $request->reason : null
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo member early leave rejected successfully.',
                'data' => $member,
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to reject Ajo member early leave.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/system/process-collections",
        summary: "Process due Ajo collections",
        description: "Administrator/internal operational endpoint that processes all Ajo collection cycles due at or before the supplied timestamp. For normal production automation, the Laravel scheduler should invoke AjoService directly.",
        tags: ["Ajo System"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "as_of", type: "string", format: "date-time", nullable: true, example: "2026-10-15T08:00:00+01:00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo collections processing completed"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Administrator access required"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unable to process Ajo collections")
        ]
    )]
    public function processCollections(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $validator = Validator::make($request->all(), [
            'as_of' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $asOf = $request->filled('as_of')
                ? Carbon::parse($request->as_of)
                : null;

            $result = $this->ajoService->processScheduledCollections($asOf);

            return response()->json([
                'status' => true,
                'message' => 'Ajo scheduled collections processed successfully.',
                'data' => $result,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to process Ajo scheduled collections.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/system/process-maturity",
        summary: "Process matured Ajos",
        description: "Administrator/internal operational endpoint that processes Ajos whose maturity date has arrived. Maturity fees are split 40% company / 60% creator and remaining member funds are returned to personal Ajo Accounts.",
        tags: ["Ajo System"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "as_of", type: "string", format: "date-time", nullable: true, example: "2027-04-01T00:05:00+01:00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo maturity processing completed"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Administrator access required"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unable to process Ajo maturity")
        ]
    )]
    public function processMaturity(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $validator = Validator::make($request->all(), [
            'as_of' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $asOf = $request->filled('as_of')
                ? Carbon::parse($request->as_of)
                : null;

            $result = $this->ajoService->processMaturity($asOf);

            return response()->json([
                'status' => true,
                'message' => 'Ajo maturity processing completed successfully.',
                'data' => $result,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to process Ajo maturity.',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/ajo/{ajoId}/conditions",
        summary: "Get Ajo conditions",
        description: "Returns the exact terms stored on the Ajo, including the collection schedule, personal Ajo Account rules, insufficient-balance rule, maturity fee, early-leave penalty and 40% company / 60% creator fee split.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 15)
        ],
        responses: [
            new OA\Response(response: 200, description: "Ajo conditions retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "You do not have access to this Ajo"),
            new OA\Response(response: 404, description: "Ajo not found"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function conditions(Request $request, int $ajoId): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();

            $ajo = Ajo::query()
                ->where('id', $ajoId)
                ->first();

            if (!$ajo) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ajo not found.',
                ], 404);
            }

            if (!$this->canViewAjo($ajo, $user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this Ajo.',
                ], 403);
            }

            $conditions = $this->ajoService->getConditions($ajo);

            return response()->json([
                'status' => true,
                'message' => 'Ajo conditions retrieved successfully.',
                'data' => [
                    'ajo_id' => $ajo->id,
                    'terms_version' => $ajo->terms_version,
                    'conditions' => $conditions,
                    'company_fee_share_percentage' => (float) $ajo->company_fee_share_percentage,
                    'creator_fee_share_percentage' => (float) $ajo->creator_fee_share_percentage,
                ],
            ], 200);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Ajo conditions.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/fee-split",
        summary: "Calculate Ajo fee split",
        description: "Calculates the snapshotted company/creator fee split for a supplied fee amount. Shared Ajo fees use 40% company and 60% creator.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "ajoId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 15)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", minimum: 0, example: 10000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Fee split calculated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "You do not have access to this Ajo"),
            new OA\Response(response: 404, description: "Ajo not found"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unexpected server error")
        ]
    )]
    public function feeSplit(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        try {
            $user = $this->authenticatedUser();

            $ajo = Ajo::query()
                ->where('id', $ajoId)
                ->first();

            if (!$ajo) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ajo not found.',
                ], 404);
            }

            if (!$this->canViewAjo($ajo, $user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this Ajo.',
                ], 403);
            }

            $split = $this->ajoService->calculateFeeSplit(
                $ajo,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Ajo fee split calculated successfully.',
                'data' => $split,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to calculate Ajo fee split.',
            ], 500);
        }
    }

    protected function authenticatedUser()
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new RuntimeException('Unauthenticated.');
        }

        return $user;
    }

    protected function isAdmin(): bool
    {
        return apiCando('root|admin');
    }

    protected function canViewAjo(Ajo $ajo, $user): bool
    {
        if (
            $ajo->visibility === 'public' &&
            $ajo->status === Ajo::STATUS_ACTIVE
        ) {
            return true;
        }

        if ((int) $ajo->creator_user_id === (int) $user->id) {
            return true;
        }

        if (
            $ajo->members()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    'active',
                    'leave_requested',
                ])
                ->exists()
        ) {
            return true;
        }

        return $ajo->invitations()
            ->where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    protected function validationResponse($validator): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422);
    }

    protected function businessError(RuntimeException $e): JsonResponse
    {
        $message = $e->getMessage();

        if ($message === 'Unauthenticated.') {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], 401);
        }

        if (str_contains(strtolower($message), 'only the ajo creator')) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], 403);
        }

        return response()->json([
            'status' => false,
            'message' => $message,
        ], 400);
    }

    protected function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Administrator access required.',
        ], 403);
    }
}
