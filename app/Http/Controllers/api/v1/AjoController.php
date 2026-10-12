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
use App\Models\AjoInvitation;
use App\Models\User;

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
        description: "Creates an Ajo. The creator is the overseer and may subsequently join the Ajo as a normal member. Creating the Ajo does not automatically create a member position for the creator.",
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
                    new OA\Property(property: "description", type: "string", nullable: true),
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
                    new OA\Property(property: "terms", type: "string", nullable: true),
                    new OA\Property(property: "is_member", type: "boolean", nullable: true, example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ajo created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 400, description: "Unable to create Ajo"),
            new OA\Response(response: 500, description: "Unexpected server error"),
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

        if ($validator->fails()) return $this->validationResponse($validator);

        try {
            $ajo = $this->ajoService->createAjo(
                $this->authenticatedUser(),
                $request->only([
                    'ajo_name', 'description', 'collection_amount', 'collection_frequency',
                    'collection_interval', 'collection_day', 'collection_time', 'start_date',
                    'maturity_date', 'early_leave_penalty_type', 'early_leave_penalty_value',
                    'insufficient_balance_fee_type', 'insufficient_balance_fee_value',
                    'max_members', 'visibility', 'terms',
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
            return response()->json(['status' => false, 'message' => 'Unable to create Ajo.'], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/ajo/mine",
        summary: "Get the authenticated user's Ajo overview",
        description: "Returns Ajos where the user is a member, Ajos created by the user, and the user's Ajo transactions. There is no personal Ajo wallet in this response. A creator appears in member_ajos only after joining their own Ajo.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Ajo overview retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function mine(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();

            $members = AjoMember::query()
     ->where('user_id', $user->id)
     ->whereIn('status', [
        AjoMember::STATUS_ACTIVE,
        AjoMember::STATUS_LEAVE_REQUESTED,
        AjoMember::STATUS_MATURED,
     ])
     ->get();

            $memberAjoIds = $members->pluck('ajo_id')->filter()->unique()->values();
            $memberAjoMap = Ajo::query()->whereIn('id', $memberAjoIds)->get()->keyBy('id');

            $memberAjos = $members->map(function (AjoMember $member) use ($memberAjoMap, $user) {
                $ajo = $memberAjoMap->get($member->ajo_id);
                if (!$ajo) return null;

                return [
                    'id' => $ajo->id,
                    'role' => (int) $ajo->creator_user_id === (int) $user->id ? 'creator_member' : 'member',
                    'membership_id' => $member->id,
                    'is_member' => true,
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
                    'current_contribution_balance' => (float) $member->balance,
                    'total_contributed' => (float) $member->total_contributed,
                    'pending_collection_amount' => (float) ($member->pending_collection_amount ?? 0),
                    'insufficient_balance_count' => (int) $member->insufficient_balance_count,
                    'pending_insufficient_balance_fees' => (float) ($member->pending_insufficient_balance_fees ?? 0),
                    'total_insufficient_balance_fees' => (float) $member->total_insufficient_balance_fees,
                    'total_maturity_fees' => (float) $member->total_maturity_fees,
                    'total_early_leave_penalties' => (float) $member->total_early_leave_penalties,
                    'joined_at' => $member->joined_at,
                    'left_at' => $member->left_at,
                ];
            })->filter()->values();

            $createdAjos = Ajo::query()
        ->where('creator_user_id', $user->id)
        ->latest('id')
        ->get()
        ->map(function (Ajo $ajo) use ($memberAjoIds) {
            // Being the creator does not automatically mean the user joined.
            // is_member becomes true only if a current membership exists.
            $ajo->setAttribute(
                'is_member',
                $memberAjoIds->contains((int) $ajo->id)
            );

            return $ajo;
        })
        ->values();

            $transactions = AjoTransaction::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Ajo overview retrieved successfully.',
                'data' => [
                    'member_ajos' => $memberAjos,
                    'created_ajos' => $createdAjos,
                    'transactions' => $transactions,
                    'member_ajo_count' => $memberAjos->count(),
                    'created_ajo_count' => $createdAjos->count(),
                    'transaction_count' => $transactions->count(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to retrieve Ajo overview.'], 500);
        }
    }


    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/invite",
        summary: "Invite a member to an Ajo",
        description: "Creates an invitation for a user to join the specified Ajo.",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoId",
                in: "path",
                required: true,
                description: "Ajo ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["username"],
                properties: [
                    new OA\Property(
                        property: "username",
                        type: "string",
                        example: "john.doe"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ajo invitation created successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Ajo not found"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function invite(Request $request, int $ajoId): JsonResponse
{
    $validator = Validator::make(
        $request->all(),
        [
            'username' => [
                'required',
                'string',
                'max:255',
            ],
        ]
    );

    if ($validator->fails()) {
        return $this->validationResponse($validator);
    }

    try {
        $invitation = $this->ajoService->inviteMember(
            $ajoId,
            $this->authenticatedUser(),
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
        summary: "Join an Ajo",
        description: "Joins a public Ajo after accepting its terms. The creator may also use this endpoint to join their own public Ajo. Joining does not move money.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["accept_terms"],
                properties: [
                    new OA\Property(
                        property: "accept_terms",
                        type: "boolean",
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Joined Ajo successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 400, description: "Unable to join Ajo"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function join(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), ['accept_terms' => ['required', 'accepted']]);
        if ($validator->fails()) return $this->validationResponse($validator);

        try {
            $member = $this->ajoService->joinAjo($ajoId, $this->authenticatedUser(), true);
            return response()->json(['status' => true, 'message' => 'Joined Ajo successfully.', 'data' => $member], 201);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to join Ajo.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/invitations/{invitationId}/accept",
        summary: "Accept an Ajo invitation",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "invitationId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["accept_terms"],
                properties: [
                    new OA\Property(
                        property: "accept_terms",
                        type: "boolean",
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo invitation accepted successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function acceptInvitation(Request $request, int $invitationId): JsonResponse
    {
        $validator = Validator::make($request->all(), ['accept_terms' => ['required', 'accepted']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $member = $this->ajoService->acceptInvitation($invitationId, $this->authenticatedUser(), true);
            return response()->json(['status' => true, 'message' => 'Ajo invitation accepted successfully.', 'data' => $member]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to accept invitation.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/invitations/{invitationId}/decline",
        summary: "Decline an Ajo invitation",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "invitationId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Ajo invitation declined successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function declineInvitation(Request $request, int $invitationId): JsonResponse
    {
        try {
            $invitation = $this->ajoService->declineInvitation($invitationId, $this->authenticatedUser());
            return response()->json(['status' => true, 'message' => 'Ajo invitation declined successfully.', 'data' => $invitation]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to decline invitation.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/leave/request",
        summary: "Request early leave from an Ajo",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "reason",
                        type: "string",
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Early-leave request submitted successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function requestEarlyLeave(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), ['reason' => ['nullable', 'string', 'max:1000']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $member = $this->ajoService->requestEarlyLeave($ajoId, $this->authenticatedUser(), $request->filled('reason') ? $request->reason : null);
            return response()->json(['status' => true, 'message' => 'Early-leave request submitted successfully.', 'data' => $member]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to submit early-leave request.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/members/{ajoMemberId}/leave/approve",
        summary: "Approve Ajo member early leave",
        description: "Approves early leave. The historical total_contributed is preserved; the penalty is recorded separately and the net return is credited to the member's main wallet.",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoMemberId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Ajo member early leave approved successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function approveEarlyLeave(Request $request, int $ajoMemberId): JsonResponse
    {
        try {
            $result = $this->ajoService->approveEarlyLeave($ajoMemberId, $this->authenticatedUser());
            return response()->json(['status' => true, 'message' => 'Ajo member early leave approved successfully.', 'data' => $result]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to approve early leave.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/members/{ajoMemberId}/leave/reject",
        summary: "Reject Ajo member early leave",
        tags: ["Ajo Early Leave"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoMemberId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "reason",
                        type: "string",
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo member early leave rejected successfully"),
            new OA\Response(response: 400, description: "Validation or business rule error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function rejectEarlyLeave(Request $request, int $ajoMemberId): JsonResponse
    {
        $validator = Validator::make($request->all(), ['reason' => ['nullable', 'string', 'max:1000']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $member = $this->ajoService->rejectEarlyLeave($ajoMemberId, $this->authenticatedUser(), $request->filled('reason') ? $request->reason : null);
            return response()->json(['status' => true, 'message' => 'Ajo member early leave rejected successfully.', 'data' => $member]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to reject early leave.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/system/process-collections",
        summary: "Process due Ajo collections",
        tags: ["Ajo System"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "as_of",
                        type: "string",
                        format: "date-time",
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo scheduled collections processed successfully"),
            new OA\Response(response: 403, description: "Administrator access required"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function processCollections(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) return $this->forbiddenResponse();
        $validator = Validator::make($request->all(), ['as_of' => ['nullable', 'date']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $asOf = $request->filled('as_of') ? Carbon::parse($request->as_of) : null;
            return response()->json(['status' => true, 'message' => 'Ajo scheduled collections processed successfully.', 'data' => $this->ajoService->processScheduledCollections($asOf)]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to process Ajo scheduled collections.'], 500);
        }
    }

        #[OA\Get(
        path: "/api/v1/ajo/pending-actions",
        summary: "Get the authenticated user's pending Ajo actions",
        description: "Returns pending Ajo invitations for the authenticated user and pending early-leave requests in Ajos created by the authenticated user.",
        tags: ["Ajo Membership"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Pending Ajo actions retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
    public function pendingActions(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();

            $pendingInvitations = AjoInvitation::query()
                ->where('invited_user_id', $user->id)
                ->where('status', AjoInvitation::STATUS_PENDING)
                ->latest('id')
                ->get();

            $invitationAjoIds = $pendingInvitations
                ->pluck('ajo_id')
                ->filter()
                ->unique()
                ->values();

            $invitationAjos = Ajo::query()
                ->whereIn('id', $invitationAjoIds)
                ->get()
                ->keyBy('id');

            $inviterIds = $pendingInvitations
                ->pluck('invited_by')
                ->filter()
                ->unique()
                ->values();

            $inviters = User::query()
                ->whereIn('id', $inviterIds)
                ->get()
                ->keyBy('id');

            $invitations = $pendingInvitations->map(function (AjoInvitation $invitation) use ($invitationAjos, $inviters) {
                $ajo = $invitationAjos->get($invitation->ajo_id);
                $inviter = $inviters->get($invitation->invited_by);

                return [
                    'invitation_id' => $invitation->id,
                    'status' => $invitation->status,
                    'ajo_id' => $ajo?->id,
                    'ajo_name' => $ajo?->ajo_name,
                    'ajo_description' => $ajo?->description,
                    'creator_user_id' => $ajo?->creator_user_id,
                    'creator_name' => $inviter?->name ?? $inviter?->username ?? 'Ajo creator',
                    'creator_username' => $inviter?->username,
                    'collection_amount' => $ajo ? (float) $ajo->collection_amount : 0,
                    'collection_frequency' => $ajo?->collection_frequency,
                    'collection_interval' => $ajo?->collection_interval,
                    'collection_day' => $ajo?->collection_day,
                    'collection_time' => $ajo?->collection_time,
                    'start_date' => $ajo?->start_date,
                    'maturity_date' => $ajo?->maturity_date,
                    'visibility' => $ajo?->visibility,
                    'terms_snapshot' => $invitation->terms_snapshot,
                    'terms_version' => $invitation->terms_version,
                    'sent_at' => $invitation->sent_at,
                ];
            })->values();

            $leaveRequests = AjoMember::query()
                ->where('status', AjoMember::STATUS_LEAVE_REQUESTED)
                ->whereHas('ajo', function ($query) use ($user) {
                    $query->where('creator_user_id', $user->id)
                        ->where('status', Ajo::STATUS_ACTIVE);
                })
                ->with(['ajo', 'user'])
                ->latest('id')
                ->get()
                ->map(function (AjoMember $member) {
                    return [
                        'ajo_member_id' => $member->id,
                        'status' => $member->status,
                        'ajo_id' => $member->ajo?->id,
                        'ajo_name' => $member->ajo?->ajo_name,
                        'creator_user_id' => $member->ajo?->creator_user_id,
                        'member_user_id' => $member->user_id,
                        'member_name' => $member->user?->name ?? $member->user?->username ?? 'Ajo member',
                        'member_username' => $member->user?->username,
                        'current_balance' => (float) $member->balance,
                        'total_contributed' => (float) $member->total_contributed,
                        'early_leave_penalty_type' => $member->ajo?->early_leave_penalty_type,
                        'early_leave_penalty_value' => $member->ajo ? (float) $member->ajo->early_leave_penalty_value : 0,
                        'early_leave_reason' => $member->early_leave_reason,
                        'requested_at' => $member->early_leave_requested_at,
                        'maturity_date' => $member->ajo?->maturity_date,
                    ];
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Pending Ajo actions retrieved successfully.',
                'data' => [
                    'pending_invitations' => $invitations,
                    'early_leave_requests' => $leaveRequests,
                    'pending_invitation_count' => $invitations->count(),
                    'early_leave_request_count' => $leaveRequests->count(),
                    'total_pending_actions' => $invitations->count() + $leaveRequests->count(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve pending Ajo actions.',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/system/process-maturity",
        summary: "Process matured Ajos",
        description: "Processes matured Ajos. Maturity is calculated from each member's actual total contribution and the net return is credited to the member's main wallet.",
        tags: ["Ajo System"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "as_of",
                        type: "string",
                        format: "date-time",
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo maturity processing completed successfully"),
            new OA\Response(response: 403, description: "Administrator access required"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function processMaturity(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) return $this->forbiddenResponse();
        $validator = Validator::make($request->all(), ['as_of' => ['nullable', 'date']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $asOf = $request->filled('as_of') ? Carbon::parse($request->as_of) : null;
            return response()->json(['status' => true, 'message' => 'Ajo maturity processing completed successfully.', 'data' => $this->ajoService->processMaturity($asOf)]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to process Ajo maturity.'], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/ajo/{ajoId}/conditions",
        summary: "Get Ajo conditions",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Ajo conditions retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Ajo not found"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function conditions(Request $request, int $ajoId): JsonResponse
    {
        try {
            $user = $this->authenticatedUser();
            $ajo = Ajo::query()->find($ajoId);
            if (!$ajo) return response()->json(['status' => false, 'message' => 'Ajo not found.'], 404);
            if (!$this->canViewAjo($ajo, $user)) return response()->json(['status' => false, 'message' => 'You do not have access to this Ajo.'], 403);

            return response()->json([
                'status' => true,
                'message' => 'Ajo conditions retrieved successfully.',
                'data' => [
                    'ajo_id' => $ajo->id,
                    'terms_version' => $ajo->terms_version,
                    'conditions' => $this->ajoService->getConditions($ajo),
                    'company_fee_share_percentage' => (float) $ajo->company_fee_share_percentage,
                    'creator_fee_share_percentage' => (float) $ajo->creator_fee_share_percentage,
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to retrieve Ajo conditions.'], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/ajo/{ajoId}/fee-split",
        summary: "Calculate Ajo fee split",
        tags: ["Ajo"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "ajoId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
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
                        minimum: 0
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ajo fee split calculated successfully"),
            new OA\Response(response: 400, description: "Unable to calculate Ajo fee split"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Ajo not found"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Unexpected server error"),
        ]
    )]
public function feeSplit(Request $request, int $ajoId): JsonResponse
    {
        $validator = Validator::make($request->all(), ['amount' => ['required', 'numeric', 'min:0']]);
        if ($validator->fails()) return $this->validationResponse($validator);
        try {
            $user = $this->authenticatedUser();
            $ajo = Ajo::query()->find($ajoId);
            if (!$ajo) return response()->json(['status' => false, 'message' => 'Ajo not found.'], 404);
            if (!$this->canViewAjo($ajo, $user)) return response()->json(['status' => false, 'message' => 'You do not have access to this Ajo.'], 403);
            return response()->json(['status' => true, 'message' => 'Ajo fee split calculated successfully.', 'data' => $this->ajoService->calculateFeeSplit($ajo, (float)$request->amount)]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Unable to calculate Ajo fee split.'], 500);
        }
    }

    protected function authenticatedUser()
    {
        $user = auth('api')->user();
        if (!$user) throw new RuntimeException('Unauthenticated.');
        return $user;
    }

    protected function isAdmin(): bool
    {
        return apiCando('root|admin');
    }

    protected function canViewAjo(Ajo $ajo, $user): bool
    {
        if ($ajo->visibility === 'public' && $ajo->status === Ajo::STATUS_ACTIVE) return true;
        if ((int)$ajo->creator_user_id === (int)$user->id) return true;

        return $ajo->members()
            ->where('user_id', $user->id)
            // A member whose early leave has been approved is no longer
            // considered part of the Ajo for membership access.
            ->whereIn('status', [
                AjoMember::STATUS_ACTIVE,
                AjoMember::STATUS_LEAVE_REQUESTED,
                AjoMember::STATUS_MATURED,
            ])
            ->exists()
            || $ajo->invitations()->where('invited_user_id', $user->id)->where('status', 'pending')->exists();
    }

    protected function validationResponse($validator): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }

    protected function businessError(RuntimeException $e): JsonResponse
    {
        $message = $e->getMessage();
        if ($message === 'Unauthenticated.') return response()->json(['status' => false, 'message' => $message], 401);
        if (str_contains(strtolower($message), 'only the ajo creator')) return response()->json(['status' => false, 'message' => $message], 403);
        return response()->json(['status' => false, 'message' => $message], 400);
    }

    protected function forbiddenResponse(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Administrator access required.'], 403);
    }

    #[OA\Get(
    path: "/api/v1/ajo/invitations/pending",
    summary: "Get the authenticated user's pending Ajo invitations",
    description: "Returns all pending Ajo invitations that have been sent to the authenticated user.",
    tags: ["Ajo Membership"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Pending Ajo invitations retrieved successfully"
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),
        new OA\Response(
            response: 500,
            description: "Unexpected server error"
        ),
    ]
)]
public function pendingInvitations(Request $request): JsonResponse
{
    try {
        $user = $this->authenticatedUser();

        $invitations = $this->ajoService->getPendingInvitations($user);

        return response()->json([
            'status' => true,
            'message' => 'Pending Ajo invitations retrieved successfully.',
            'data' => [
                'invitations' => $invitations,
                'invitation_count' => $invitations->count(),
            ],
        ]);

    } catch (RuntimeException $e) {

        return $this->businessError($e);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to retrieve pending Ajo invitations.',
        ], 500);
    }
}

#[OA\Get(
    path: "/api/v1/ajo/settings/creation-fee",
    summary: "Get the authenticated user's Ajo creation fee",
    description: "Returns the current Ajo creation fee for the authenticated user's company.",
    tags: ["Ajo System"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Ajo Creation Fee retrieved successfully"
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),
        new OA\Response(
            response: 500,
            description: "Unexpected server error"
        ),
    ]
)]
    /**
     * Read the authenticated user's company's current Ajo creation fee.
     */
    public function creationFee(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $settings = $user->company?->setting;

        if (!$settings) {
            return response()->json([
                'status' => false,
                'message' => 'Company Ajo settings could not be found.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ajo creation fee retrieved successfully.',
            'data' => [
                'creation_fee' => round((float) ($settings->ajo_creation_fee ?? 0), 2),
            ],
        ]);
    }


/**
 * Update the authenticated user's company's Ajo creation fee.
 *
 * Access is controlled by Core's apiCando() staff-permission helper.
 */
public function updateCreationFee(Request $request)
{
    $user = auth('api')->user();

    if (!$user) {
        // Logging authentication failure reason
        logInfo('Ajo creation fee update failed: Unauthenticated user attempt.');
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    // Use Core's staff-permission system.
    if (!apiCando('admin|root')) {
        // Logging permission failure reason
        logInfo('Ajo creation fee update failed: Unauthorized permission attempt.', [
            'user_id' => $user->id,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Administrator access required.',
        ], 403);
    }

    $validated = $request->validate([
        'creation_fee' => [
            'required',
            'numeric',
            'min:0',
            'max:1000000000',
        ],
    ]);

    try {
        $settings = $user->company?->setting;

        if (!$settings) {
            // Logging application logic failure reason
            logInfo('Ajo creation fee update failed: Company settings missing.', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Company Ajo settings could not be found.',
            ], 422);
        }

        $settings->ajo_creation_fee = round(
            (float) $validated['creation_fee'],
            2
        );

        $settings->save();

        logInfo('Ajo creation fee updated successfully', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'new_creation_fee' => $settings->ajo_creation_fee,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ajo creation fee updated successfully.',
            'data' => [
                'creation_fee' => (float) $settings->ajo_creation_fee,
            ],
        ]);

    } catch (\Exception $e) {
        // Logging unexpected database/system failures with exception details
        logInfo('Ajo creation fee update failed: System Exception thrown.', [
            'user_id' => $user->id,
            'company_id' => $user->company_id ?? null,
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while updating the creation fee.',
            'error' => config('app.debug') ? $e->getMessage() : 'Server Error',
        ], 500);
    }
}


#[OA\Get(
    path: "/api/v1/ajo/{ajoId}/members",
    summary: "Get all members of an Ajo",
    description: "Returns the members of a specific Ajo. Only the creator of that Ajo can access the full member list.",
    tags: ["Ajo Membership"],
    security: [["bearerAuth" => []]],
    parameters: [
        new OA\Parameter(
            name: "ajoId",
            in: "path",
            required: true,
            description: "ID of the Ajo group",
            schema: new OA\Schema(type: "integer", minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Ajo members retrieved successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Ajo members retrieved successfully."
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "ajo_id",
                                type: "integer",
                                example: 12
                            ),
                            new OA\Property(
                                property: "ajo_name",
                                type: "string",
                                example: "Friends Monthly Ajo"
                            ),
                            new OA\Property(
                                property: "member_count",
                                type: "integer",
                                example: 3
                            ),
                            new OA\Property(
                                property: "members",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "membership_id", type: "integer", example: 5),
                                        new OA\Property(property: "user_id", type: "integer", example: 24),
                                        new OA\Property(property: "name", type: "string", nullable: true, example: "John Doe"),
                                        new OA\Property(property: "username", type: "string", nullable: true, example: "john.doe"),
                                        new OA\Property(property: "status", type: "string", example: "active"),
                                        new OA\Property(property: "balance", type: "number", format: "float", example: 100000),
                                        new OA\Property(property: "total_contributed", type: "number", format: "float", example: 150000),
                                        new OA\Property(property: "joined_at", type: "string", nullable: true, example: "2026-10-01T08:00:00.000000Z"),
                                    ]
                                )
                            ),
                        ]
                    ),
                ]
            )
        ),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Only the Ajo creator can view all members"),
        new OA\Response(response: 404, description: "Ajo not found"),
        new OA\Response(response: 500, description: "Unexpected server error"),
    ]
)]
public function members(Request $request, int $ajoId): JsonResponse
{
    try {
        $user = $this->authenticatedUser();

        // Find the requested Ajo group.
        $ajo = Ajo::query()->find($ajoId);

        if (!$ajo) {
            return response()->json([
                'status' => false,
                'message' => 'Ajo not found.',
            ], 404);
        }

        // Only the creator can see the complete member list.
        if ((int) $ajo->creator_user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Only the Ajo creator can view all members.',
            ], 403);
        }

        // Return all membership records that still exist for this Ajo.
        // This includes members with different membership statuses.
        $members = AjoMember::query()
            ->where('ajo_id', $ajo->id)
            ->with('user:id,name,username')
            ->orderBy('id')
            ->get()
            ->map(function (AjoMember $member) {
                return [
                    'membership_id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name,
                    'username' => $member->user?->username,
                    'status' => $member->status,
                    'balance' => (float) $member->balance,
                    'total_contributed' => (float) $member->total_contributed,
                    'pending_collection_amount' => (float) ($member->pending_collection_amount ?? 0),
                    'insufficient_balance_count' => (int) $member->insufficient_balance_count,
                    'pending_insufficient_balance_fees' => (float) ($member->pending_insufficient_balance_fees ?? 0),
                    'total_insufficient_balance_fees' => (float) $member->total_insufficient_balance_fees,
                    'total_maturity_fees' => (float) $member->total_maturity_fees,
                    'total_early_leave_penalties' => (float) $member->total_early_leave_penalties,
                    'joined_at' => $member->joined_at,
                    'left_at' => $member->left_at,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Ajo members retrieved successfully.',
            'data' => [
                'ajo_id' => $ajo->id,
                'ajo_name' => $ajo->ajo_name,
                'member_count' => $members->count(),
                'members' => $members,
            ],
        ]);
    } catch (RuntimeException $e) {
        return $this->businessError($e);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to retrieve Ajo members.',
        ], 500);
    }
}


#[OA\Get(
    path: "/api/v1/ajo/my-memberships",
    summary: "Get Ajos the authenticated user currently belongs to",
    description: "Returns Ajos where the authenticated user has an active, leave-requested, or matured membership. Ajos created by the user are included only if the user has actually joined them.",
    tags: ["Ajo Membership"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Ajo memberships retrieved successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Your Ajo memberships were retrieved successfully."
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "membership_count",
                                type: "integer",
                                example: 2
                            ),
                            new OA\Property(
                                property: "memberships",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "ajo_id", type: "integer", example: 12),
                                        new OA\Property(property: "ajo_name", type: "string", example: "Friends Monthly Ajo"),
                                        new OA\Property(property: "description", type: "string", nullable: true),
                                        new OA\Property(property: "creator_user_id", type: "integer", example: 4),
                                        new OA\Property(property: "role", type: "string", enum: ["member", "creator_member"]),
                                        new OA\Property(property: "membership_id", type: "integer", example: 25),
                                        new OA\Property(property: "membership_status", type: "string", example: "active"),
                                        new OA\Property(property: "collection_amount", type: "number", format: "float", example: 50000),
                                        new OA\Property(property: "collection_frequency", type: "string", example: "monthly"),
                                        new OA\Property(property: "collection_interval", type: "integer", nullable: true, example: 1),
                                        new OA\Property(property: "collection_day", type: "integer", nullable: true, example: 15),
                                        new OA\Property(property: "collection_time", type: "string", example: "08:00"),
                                        new OA\Property(property: "start_date", type: "string", format: "date", nullable: true),
                                        new OA\Property(property: "maturity_date", type: "string", format: "date", nullable: true),
                                        new OA\Property(property: "next_collection_at", type: "string", format: "date-time", nullable: true),
                                        new OA\Property(property: "ajo_status", type: "string", example: "active"),
                                        new OA\Property(property: "current_contribution_balance", type: "number", format: "float", example: 100000),
                                        new OA\Property(property: "total_contributed", type: "number", format: "float", example: 150000),
                                        new OA\Property(property: "joined_at", type: "string", nullable: true),
                                        new OA\Property(property: "left_at", type: "string", nullable: true)
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 500, description: "Unexpected server error")
    ]
)]
public function myMemberships(Request $request): JsonResponse
{
    try {
        $user = $this->authenticatedUser();

        $memberships = AjoMember::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                AjoMember::STATUS_ACTIVE,
                AjoMember::STATUS_LEAVE_REQUESTED,
                AjoMember::STATUS_MATURED,
            ])
            ->with('ajo')
            ->latest('id')
            ->get()
            ->filter(fn (AjoMember $member) => $member->ajo !== null)
            ->map(function (AjoMember $member) use ($user) {
                $ajo = $member->ajo;

                return [
                    'ajo_id' => $ajo->id,
                    'ajo_name' => $ajo->ajo_name,
                    'description' => $ajo->description,
                    'creator_user_id' => $ajo->creator_user_id,

                    'role' => (int) $ajo->creator_user_id === (int) $user->id
                        ? 'creator_member'
                        : 'member',

                    'membership_id' => $member->id,
                    'membership_status' => $member->status,

                    'collection_amount' => (float) $ajo->collection_amount,
                    'collection_frequency' => $ajo->collection_frequency,
                    'collection_interval' => $ajo->collection_interval,
                    'collection_day' => $ajo->collection_day,
                    'collection_time' => $ajo->collection_time,
                    'start_date' => $ajo->start_date,
                    'maturity_date' => $ajo->maturity_date,
                    'next_collection_at' => $ajo->next_collection_at,
                    'ajo_status' => $ajo->status,

                    'current_contribution_balance' => (float) $member->balance,
                    'total_contributed' => (float) $member->total_contributed,
                    'pending_collection_amount' => (float) ($member->pending_collection_amount ?? 0),

                    'insufficient_balance_count' => (int) $member->insufficient_balance_count,
                    'pending_insufficient_balance_fees' => (float) ($member->pending_insufficient_balance_fees ?? 0),
                    'total_insufficient_balance_fees' => (float) $member->total_insufficient_balance_fees,
                    'total_maturity_fees' => (float) $member->total_maturity_fees,
                    'total_early_leave_penalties' => (float) $member->total_early_leave_penalties,

                    'joined_at' => $member->joined_at,
                    'left_at' => $member->left_at,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Your Ajo memberships were retrieved successfully.',
            'data' => [
                'membership_count' => $memberships->count(),
                'memberships' => $memberships,
            ],
        ]);
    } catch (RuntimeException $e) {
        return $this->businessError($e);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to retrieve your Ajo memberships.',
        ], 500);
    }
}

}
