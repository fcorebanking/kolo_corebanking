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
use App\Services\FinovaApiService;

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
    return app(FinovaApiService::class)->post(
        '/ajo/create',
        $request->bearerToken(),
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
    return app(FinovaApiService::class)->get(
        '/ajo/mine',
        $request->bearerToken()
    );
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
    return app(FinovaApiService::class)->post(
        "/ajo/{$ajoId}/invite",
        $request->bearerToken(),
        $request->only([
            'username',
        ])
    );
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
    return app(FinovaApiService::class)->post(
        "/ajo/{$ajoId}/join",
        $request->bearerToken(),
        $request->only([
            'accept_terms',
        ])
    );
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
public function acceptInvitation(
    Request $request,
    int $invitationId
): JsonResponse {
    return app(FinovaApiService::class)->post(
        "/ajo/invitations/{$invitationId}/accept",
        $request->bearerToken(),
        $request->only([
            'accept_terms',
        ])
    );
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
public function declineInvitation(
    Request $request,
    int $invitationId
): JsonResponse {
    return app(FinovaApiService::class)->post(
        "/ajo/invitations/{$invitationId}/decline",
        $request->bearerToken()
    );
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
    return app(FinovaApiService::class)->post(
        "/ajo/{$ajoId}/leave/request",
        $request->bearerToken(),
        $request->only([
            'reason',
        ])
    );
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
public function approveEarlyLeave(
    Request $request,
    int $ajoMemberId
): JsonResponse {
    return app(FinovaApiService::class)->post(
        "/ajo/members/{$ajoMemberId}/leave/approve",
        $request->bearerToken()
    );
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
public function rejectEarlyLeave(
    Request $request,
    int $ajoMemberId
): JsonResponse {
    return app(FinovaApiService::class)->post(
        "/ajo/members/{$ajoMemberId}/leave/reject",
        $request->bearerToken(),
        $request->only([
            'reason',
        ])
    );
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
    return app(FinovaApiService::class)->post(
        '/ajo/system/process-collections',
        $request->bearerToken(),
        $request->only([
            'as_of',
        ])
    );
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
    return app(FinovaApiService::class)->get(
        '/ajo/pending-actions',
        $request->bearerToken()
    );
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
    return app(FinovaApiService::class)->post(
        '/ajo/system/process-maturity',
        $request->bearerToken(),
        $request->only([
            'as_of',
        ])
    );
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
    return app(FinovaApiService::class)->get(
        "/ajo/{$ajoId}/conditions",
        $request->bearerToken()
    );
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
    return app(FinovaApiService::class)->post(
        "/ajo/{$ajoId}/fee-split",
        $request->bearerToken(),
        $request->only(['amount'])
    );
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
    return app(FinovaApiService::class)->get(
        '/ajo/invitations/pending',
        $request->bearerToken()
    );
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
public function creationFee(Request $request): JsonResponse
{
    return app(FinovaApiService::class)->get(
        '/ajo/settings/creation-fee',
        $request->bearerToken()
    );
}


// /**
//  * Update the authenticated user's company's Ajo creation fee.
//  *
//  * Access is controlled by Core's apiCando() staff-permission helper.
//  */
// public function updateCreationFee(Request $request)
// {
//     $user = auth('api')->user();

//     if (!$user) {
//         // Logging authentication failure reason
//         logInfo('Ajo creation fee update failed: Unauthenticated user attempt.');
//         return response()->json([
//             'status' => false,
//             'message' => 'Unauthenticated.',
//         ], 401);
//     }

//     // Use Core's staff-permission system.
//     if (!apiCando('admin|root')) {
//         // Logging permission failure reason
//         logInfo('Ajo creation fee update failed: Unauthorized permission attempt.', [
//             'user_id' => $user->id,
//         ]);
//         return response()->json([
//             'status' => false,
//             'message' => 'Administrator access required.',
//         ], 403);
//     }

//     $validated = $request->validate([
//         'creation_fee' => [
//             'required',
//             'numeric',
//             'min:0',
//             'max:1000000000',
//         ],
//     ]);

//     try {
//         $settings = $user->company?->setting;

//         if (!$settings) {
//             // Logging application logic failure reason
//             logInfo('Ajo creation fee update failed: Company settings missing.', [
//                 'user_id' => $user->id,
//                 'company_id' => $user->company_id,
//             ]);
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Company Ajo settings could not be found.',
//             ], 422);
//         }

//         $settings->ajo_creation_fee = round(
//             (float) $validated['creation_fee'],
//             2
//         );

//         $settings->save();

//         $settings->refresh();

//         logInfo('Ajo creation fee updated successfully', [
//           'user_id' => $user->id,
//           'company_id' => $user->company_id,
//           'settings_id' => $settings->id,
//           'saved_creation_fee' => $settings->ajo_creation_fee,
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Ajo creation fee updated successfully.',
//             'data' => [
//                 'creation_fee' => (float) $settings->ajo_creation_fee,
//             ],
//         ]);

//     } catch (\Exception $e) {
//         // Logging unexpected database/system failures with exception details
//         logInfo('Ajo creation fee update failed: System Exception thrown.', [
//             'user_id' => $user->id,
//             'company_id' => $user->company_id ?? null,
//             'error_message' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine(),
//         ]);

//         return response()->json([
//             'status' => false,
//             'message' => 'An error occurred while updating the creation fee.',
//             'error' => config('app.debug') ? $e->getMessage() : 'Server Error',
//         ], 500);
//     }
// }


#[OA\Get(
    path: "/api/v1/ajo/{ajoId}/members",
    summary: "Get all members of an Ajo",
    description: "Returns all membership records for a specific Ajo. Access is restricted to the Ajo creator and authorized administrators.",
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
                                        new OA\Property(property: "first_name", type: "string", nullable: true, example: "John"),
                                        new OA\Property(property: "last_name", type: "string", nullable: true, example: "Doe"),
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
        new OA\Response(response: 403, description: "Only the Ajo creator or an administrator can view all members"),
        new OA\Response(response: 404, description: "Ajo not found"),
        new OA\Response(response: 500, description: "Unexpected server error"),
    ]
)]
public function members(Request $request, int $ajoId): JsonResponse
{
    return app(FinovaApiService::class)->get(
        "/ajo/{$ajoId}/members",
        $request->bearerToken()
    );
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
    return app(FinovaApiService::class)->get(
        '/ajo/my-memberships',
        $request->bearerToken()
    );
}

}
