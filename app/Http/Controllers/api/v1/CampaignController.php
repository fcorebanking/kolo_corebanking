<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use App\Models\CampaignTransaction;
use App\Models\CompanyFinancialEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Throwable;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Create a new Campaign.
     */
    #[OA\Post(
        path: "/api/v1/campaigns/create",
        summary: "Create a Campaign",
        description: "Creates a new Campaign for the authenticated user. The supplied creator account number must belong to the authenticated user. The Campaign target is only a benchmark and does not limit total contributions.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "campaign_name",
                    "target_amount",
                    "creator_account_number"
                ],
                properties: [
                    new OA\Property(
                        property: "campaign_name",
                        type: "string",
                        example: "Help Me Build My Dream Home"
                    ),
                    new OA\Property(
                        property: "description",
                        type: "string",
                        nullable: true,
                        example: "Raising funds towards the construction of my family home."
                    ),
                    new OA\Property(
                        property: "target_amount",
                        type: "number",
                        format: "float",
                        example: 1000000
                    ),
                    new OA\Property(
                        property: "creator_account_number",
                        type: "string",
                        example: "0123456789"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Campaign created successfully"
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
                description: "Unable to create Campaign"
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'campaign_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],

                'target_amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'creator_account_number' => [
                    'required',
                    'string',
                    'max:30',
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

            $campaign = $this->campaignService->createCampaign(
                $user,
                $request->campaign_name,
                (float) $request->target_amount,
                $request->creator_account_number,
                $request->description
            );

            return response()->json([
                'status' => true,
                'message' => 'Campaign created successfully.',
                'data' => $campaign,
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
     * Get Campaigns.
     *
     * By default, only active Campaigns are returned because
     * active Campaigns are the ones accepting new contributions.
     *
     * Optional:
     * status = active|closed
     * mine = 1
     */
    #[OA\Get(
        path: "/api/v1/campaigns/list",
        summary: "Get Campaign list",
        description: "Returns Campaigns available to the authenticated user. By default only active Campaigns are returned. Set mine=1 to return only Campaigns created by the authenticated user, or supply a status filter.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Campaign status filter.",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["active", "closed"],
                    example: "active"
                )
            ),
            new OA\Parameter(
                name: "mine",
                in: "query",
                required: false,
                description: "Set to 1 to return only Campaigns created by the authenticated user.",
                schema: new OA\Schema(
                    type: "integer",
                    enum: [0, 1],
                    example: 0
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
                description: "Campaigns retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 422,
                description: "Invalid query parameters"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Campaigns"
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

            $validator = Validator::make($request->all(), [
                'status' => [
                    'nullable',
                    'string',
                    'in:active,closed',
                ],

                'mine' => [
                    'nullable',
                    'integer',
                    'in:0,1',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
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
             * Eager-load creator information.
             */
            $query = Campaign::query()
                ->with([
                    'user:id,uuid,first_name,last_name,username,email,phone',
                ])
                ->orderByDesc('id');

            /*
             * By default, only active Campaigns are shown.
             */
            if ($request->filled('status')) {
                $query->where(
                    'status',
                    $request->status
                );
            } else {
                $query->where(
                    'status',
                    'active'
                );
            }

            /*
             * Optional "my Campaigns" filter.
             */
            if ((int) $request->get('mine', 0) === 1) {
                $query->where(
                    'user_id',
                    $user->id
                );
            }

            $perPage = min(
                max(
                    (int) $request->get('per_page', 20),
                    1
                ),
                100
            );

            $campaigns = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Campaigns retrieved successfully.',
                'data' => $campaigns,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Campaigns.',
            ], 500);
        }
    }

    /**
     * Get one Campaign.
     */
    #[OA\Get(
        path: "/api/v1/campaigns/details/{id}",
        summary: "Get Campaign details",
        description: "Fetches a specific Campaign and its transaction history. Both active and closed Campaigns can be viewed.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign retrieved successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Campaign not found"
            ),
            new OA\Response(
                response: 500,
                description: "Unable to retrieve Campaign"
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

            $campaign = Campaign::query()
                ->with([
                    'user:id,uuid,first_name,last_name,username,email,phone',
                    'transactions' => function ($query) {
                        $query->latest();
                    },
                ])
                ->where('id', $id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Campaign retrieved successfully.',
                'data' => $campaign,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve Campaign.',
            ], 500);
        }
    }

    /**
     * Deposit money into a Campaign.
     *
     * Any authenticated user can contribute to an active Campaign.
     */
    #[OA\Post(
        path: "/api/v1/campaigns/deposit/{id}",
        summary: "Contribute to a Campaign",
        description: "Debits the authenticated contributor's wallet and adds the amount to the Campaign. Contributions are allowed to exceed the Campaign target benchmark.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
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
                description: "Campaign contribution successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Campaign not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 400,
                description: "Campaign contribution failed"
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

            $campaign = Campaign::query()
                ->where('id', $id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            if ($campaign->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'This Campaign is closed and no longer accepts contributions.',
                ], 400);
            }

            $result = $this->campaignService->deposit(
                $campaign->id,
                $user,
                (float) $request->amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Campaign contribution successful.',
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
     * Withdraw Campaign funds.
     *
     * Only the Campaign creator can perform this operation.
     */
    #[OA\Post(
        path: "/api/v1/campaigns/withdraw/{id}",
        summary: "Withdraw Campaign funds",
        description: "Withdraws the full currently withdrawable Campaign balance to the Campaign creator's stored account. The configured Campaign fee is deducted from the creator's proceeds and recognized as company revenue. This is allowed for both active and closed Campaigns.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign withdrawal successful"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Campaign not found or user is not the creator"
            ),
            new OA\Response(
                response: 400,
                description: "Campaign withdrawal failed"
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

            $campaign = Campaign::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Campaign not found or you are not the Campaign creator.',
                ], 404);
            }

            $result = $this->campaignService->withdraw(
                $campaign->id,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Campaign withdrawal successful.',
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
     * Update Campaign target benchmark.
     *
     * Only the creator can edit it.
     */
    #[OA\Put(
        path: "/api/v1/campaigns/{id}/target",
        summary: "Update Campaign target",
        description: "Updates the Campaign target benchmark. Changing the target does not alter total contributions or the amount currently withdrawable.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["target_amount"],
                properties: [
                    new OA\Property(
                        property: "target_amount",
                        type: "number",
                        format: "float",
                        minimum: 0.01,
                        example: 1500000
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign target updated successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Campaign not found or user is not the creator"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            ),
            new OA\Response(
                response: 400,
                description: "Unable to update Campaign target"
            )
        ]
    )]
    public function updateTarget(
        Request $request,
        $id
    ): JsonResponse {
        try {

            $validator = Validator::make($request->all(), [
                'target_amount' => [
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

            $campaign = Campaign::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Campaign not found or you are not the Campaign creator.',
                ], 404);
            }

            $campaign = $this->campaignService->updateTarget(
                $campaign->id,
                $user,
                (float) $request->target_amount
            );

            return response()->json([
                'status' => true,
                'message' => 'Campaign target updated successfully.',
                'data' => $campaign,
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
     * Close a Campaign by its creator.
     *
     * The Campaign is not deleted.
     *
     * Existing funds remain withdrawable.
     */
    #[OA\Post(
        path: "/api/v1/campaigns/{id}/close",
        summary: "Close Campaign",
        description: "Closes a Campaign without deleting it. New contributions are rejected after closure, but any remaining withdrawable funds can still be withdrawn by the Campaign creator.",
        tags: ["Campaigns"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign closed successfully"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 404,
                description: "Campaign not found or user is not the creator"
            ),
            new OA\Response(
                response: 400,
                description: "Unable to close Campaign"
            )
        ]
    )]
    public function close(Request $request, $id): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $campaign = Campaign::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Campaign not found or you are not the Campaign creator.',
                ], 404);
            }

            $campaign = $this->campaignService->closeCampaign(
                $campaign->id,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Campaign closed successfully.',
                'data' => $campaign,
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
 * Get Campaign transaction history.
 */
#[OA\Get(
    path: "/api/v1/campaigns/transactions/{id}",
    summary: "Get Campaign transactions",
    description: "Fetches the authenticated user's transaction history for a Campaign, including their contributions and withdrawals.",
    tags: ["Campaigns"],
    security: [["bearerAuth" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "Campaign ID.",
            schema: new OA\Schema(
                type: "integer",
                example: 1
            )
        ),
        new OA\Parameter(
            name: "per_page",
            in: "query",
            required: false,
            description: "Number of transaction records per page.",
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
            description: "Campaign transactions retrieved successfully"
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),
        new OA\Response(
            response: 404,
            description: "Campaign not found"
        ),
        new OA\Response(
            response: 422,
            description: "Validation failed"
        ),
        new OA\Response(
            response: 500,
            description: "Unable to retrieve Campaign transactions"
        )
    ]
)]
public function transactions(
    Request $request,
    $id
): JsonResponse {
    try {

        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
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

        $campaign = Campaign::query()
            ->where('id', $id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign not found.',
            ], 404);
        }

        $perPage = min(
            max(
                (int) $request->get('per_page', 20),
                1
            ),
            100
        );

        /*
         * IMPORTANT:
         *
         * Only return transactions belonging to the
         * authenticated Core user.
         *
         * This prevents another authenticated user from
         * seeing everybody else's contributions/withdrawals
         * simply by supplying the Campaign ID.
         */
        $transactions = $campaign
            ->transactions()
            ->where(
                'user_id',
                $user->id
            )
            ->with([
                'user:id,uuid,first_name,last_name,username,email,phone',
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' =>
                'Campaign transactions retrieved successfully.',
            'data' => $transactions,
        ], 200);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'status' => false,
            'message' =>
                'Unable to retrieve Campaign transactions.',
        ], 500);
    }
}

    /**
     * Get all Campaigns belonging to users in the
     * authenticated administrator's company.
     */
    #[OA\Get(
        path: "/api/v1/campaigns/company/list",
        summary: "Get company Campaigns",
        description: "Returns Campaigns belonging to users under the authenticated administrator's company. The company is determined automatically from the authenticated user.",
        tags: ["Campaigns - Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Optional Campaign status filter.",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["active", "closed"],
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
                description: "Company Campaigns retrieved successfully"
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
                description: "Unable to retrieve company Campaigns"
            )
        ]
    )]
    public function companyIndex(
        Request $request
    ): JsonResponse {
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
                    'message' =>
                        'Your account is not linked to a company.',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'status' => [
                    'nullable',
                    'string',
                    'in:active,closed',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
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

            $query = Campaign::query()
                ->with([
                    'user:id,uuid,first_name,last_name,username,email,phone',
                ])
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->orderByDesc('id');

            if ($request->filled('status')) {
                $query->where(
                    'status',
                    $request->status
                );
            }

            $perPage = min(
                max(
                    (int) $request->get('per_page', 20),
                    1
                ),
                100
            );

            $campaigns = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' =>
                    'Company Campaigns retrieved successfully.',
                'company_id' =>
                    $user->company_id,
                'data' =>
                    $campaigns,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to retrieve company Campaigns.',
            ], 500);
        }
    }

    /**
     * Close a Campaign by administrator.
     *
     * The Campaign is not deleted.
     * Existing funds remain withdrawable by the creator.
     */
    #[OA\Post(
        path: "/api/v1/campaigns/company/{id}/close",
        summary: "Admin close Campaign",
        description: "Allows an authorized administrator/root user to close a Campaign belonging to their company. The Campaign remains in the database and any existing withdrawable funds remain available to its creator.",
        tags: ["Campaigns - Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Campaign ID.",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign closed successfully"
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
                description: "Campaign not found in administrator's company"
            ),
            new OA\Response(
                response: 400,
                description: "Unable to close Campaign"
            )
        ]
    )]
    public function adminClose(
        Request $request,
        $id
    ): JsonResponse {
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
                    'message' =>
                        'Your account is not linked to a company.',
                ], 400);
            }

            $campaign = Campaign::query()
                ->where('id', $id)
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Campaign not found in your company.',
                ], 404);
            }

            $campaign =
                $this->campaignService->adminCloseCampaign(
                    $campaign->id,
                    $user
                );

            return response()->json([
                'status' => true,
                'message' =>
                    'Campaign closed successfully by administrator.',
                'data' => $campaign,
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
     * Get the authenticated company's Campaign settings.
     */
    #[OA\Get(
        path: "/api/v1/campaigns/company/settings",
        summary: "Get company Campaign settings",
        description: "Returns the Campaign withdrawal fee configured for the authenticated administrator's company.",
        tags: ["Campaigns - Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign settings retrieved successfully"
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
                description: "Unable to retrieve Campaign settings"
            )
        ]
    )]
    public function companySettings(
        Request $request
    ): JsonResponse {
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
                    'message' =>
                        'Your account is not linked to a company.',
                ], 400);
            }

            $settings = DB::table('company_settings')
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->first([
                    'company_id',
                    'campaign_fee_percentage',
                ]);

            if (!$settings) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Company settings not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' =>
                    'Campaign settings retrieved successfully.',
                'data' => $settings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to retrieve Campaign settings.',
            ], 500);
        }
    }

    /**
     * Update the authenticated company's Campaign settings.
     */
    #[OA\Put(
        path: "/api/v1/campaigns/company/settings",
        summary: "Update company Campaign settings",
        description: "Updates the Campaign withdrawal fee percentage for the authenticated administrator's company.",
        tags: ["Campaigns - Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "campaign_fee_percentage"
                ],
                properties: [
                    new OA\Property(
                        property: "campaign_fee_percentage",
                        type: "number",
                        format: "float",
                        minimum: 0,
                        maximum: 100,
                        example: 3
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Campaign settings updated successfully"
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
                description: "Unable to update Campaign settings"
            )
        ]
    )]
    public function updateCompanySettings(
        Request $request
    ): JsonResponse {
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
                    'message' =>
                        'Your account is not linked to a company.',
                ], 400);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'campaign_fee_percentage' => [
                        'required',
                        'numeric',
                        'min:0',
                        'max:100',
                    ],
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            /*
             * Find settings belonging to the authenticated
             * administrator's company.
             */
            $companySettings = DB::table('company_settings')
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->first();

            if (!$companySettings) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Company settings not found.',
                ], 404);
            }

            /*
             * Update Campaign fee.
             *
             * Existing Campaigns keep their snapshotted fee.
             * This affects newly-created Campaigns.
             */
            DB::table('company_settings')
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->update([
                    'campaign_fee_percentage' =>
                        $request->campaign_fee_percentage,

                    'updated_at' =>
                        now(),
                ]);

            /*
             * Read back the actual stored values.
             */
            $updatedSettings = DB::table('company_settings')
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->first([
                    'company_id',
                    'campaign_fee_percentage',
                ]);

            return response()->json([
                'status' => true,
                'message' =>
                    'Campaign settings updated successfully.',
                'data' => $updatedSettings,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to update Campaign settings.',
            ], 500);
        }
    }

/**
 * Get company-wide Campaign summary for regular authenticated users.
 *
 * Returns the total number of Campaigns and the total amount
 * contributed across all Campaigns belonging to the user's company.
 *
 * Includes both active and closed Campaigns.
 * Total contributions are lifetime totals and are not reduced
 * when Campaign creators withdraw funds.
 */
#[OA\Get(
    path: "/api/v1/campaigns/company/summary",
    summary: "Get company Campaign summary",
    description: "Returns the total number of Campaigns and the total lifetime contributions across all Campaigns belonging to the authenticated user's company. Both active and closed Campaigns are included. This endpoint is available to regular authenticated users and does not require administrator privileges.",
    tags: ["Campaigns"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Company Campaign summary retrieved successfully",
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
                        example: "Company Campaign summary retrieved successfully."
                    ),
                    new OA\Property(
                        property: "data",
                        properties: [
                            new OA\Property(
                                property: "company_id",
                                type: "integer",
                                example: 46
                            ),
                            new OA\Property(
                                property: "total_campaigns",
                                type: "integer",
                                example: 12
                            ),
                            new OA\Property(
                                property: "total_contributed",
                                type: "number",
                                format: "float",
                                example: 2750000.00
                            ),
                        ],
                        type: "object"
                    ),
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),
        new OA\Response(
            response: 400,
            description: "Authenticated user is not linked to a company"
        ),
        new OA\Response(
            response: 500,
            description: "Unable to retrieve company Campaign summary"
        )
    ]
)]
public function companySummary(Request $request): JsonResponse
{
    try {

        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (empty($user->company_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not linked to a company.',
            ], 400);
        }

        /*
         * Scope all calculations to the authenticated user's company.
         *
         * Do not filter by status: both active and closed Campaigns
         * count toward the company's totals.
         */
        $summary = Campaign::query()
            ->where('company_id', $user->company_id)
            ->selectRaw('COUNT(*) AS total_campaigns')
            ->selectRaw(
                'COALESCE(SUM(total_contributed), 0) AS total_contributed'
            )
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Company Campaign summary retrieved successfully.',
            'data' => [
                'company_id' => (int) $user->company_id,
                'total_campaigns' => (int) $summary->total_campaigns,
                'total_contributed' => (float) $summary->total_contributed,
            ],
        ], 200);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Unable to retrieve company Campaign summary.',
        ], 500);
    }
}


/**
 * Admin campaign analytics dashboard.
 *
 * Accessible only to users with root or admin permissions.
 * All records are scoped to the authenticated user's company.
 */

public function adminDashboard(Request $request): JsonResponse
{
    try {
        /*
         * Only authorized root/admin users may access analytics.
         */

        $hasDashboardAccess = apiCando('root') || apiCando('admin');

        if (!$hasDashboardAccess) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to view campaign analytics.',
                'data' => [],
            ], 403);
        }

        $admin = auth('api')->user();

        if (!$admin || empty($admin->company_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not linked to a company.',
                'data' => [],
            ], 403);
        }

        $companyId = (int) $admin->company_id;

        /*
         * Company-wide campaign statistics.
         */
        $campaignStats = Campaign::query()
            ->where('company_id', $companyId)
            ->selectRaw('COUNT(*) as total_campaigns')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_campaigns"
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) as closed_campaigns"
            )
            ->selectRaw(
                'COALESCE(SUM(target_amount), 0) as total_targets'
            )
            ->selectRaw(
                'COALESCE(SUM(total_contributed), 0) as total_contributed'
            )
            ->selectRaw(
                'COALESCE(SUM(withdrawable_amount), 0) as total_withdrawable'
            )
            ->first();

        /*
         * Successful campaign withdrawal fee revenue.
         */
        $feeRevenue = CompanyFinancialEntry::query()
            ->where('company_id', $companyId)
            ->where('type', 'revenue')
            ->where('category', 'campaign_withdrawal_fee')
            ->where('status', 'successful')
            ->sum('amount');

        /*
         * Find unique contributors to this company's campaigns.
         */
        $contributorIds = CampaignTransaction::query()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->where('status', 'successful')
            ->whereHas('campaign', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $contributors = \App\Models\User::query()
            ->whereIn('id', $contributorIds)
            ->get();

        /*
         * Determine account status without assuming a particular
         * status column exists on every User model.
         */
        $getAccountStatus = function ($user): string {
            $attributes = $user->getAttributes();

            if (array_key_exists('is_active', $attributes)) {
                return in_array(
                    strtolower((string) $attributes['is_active']),
                    ['1', 'true', 'active', 'enabled'],
                    true
                ) ? 'active' : 'inactive';
            }

            if (array_key_exists('active', $attributes)) {
                return in_array(
                    strtolower((string) $attributes['active']),
                    ['1', 'true', 'active', 'enabled'],
                    true
                ) ? 'active' : 'inactive';
            }

            if (array_key_exists('status', $attributes)) {
                $status = strtolower(
                    trim((string) $attributes['status'])
                );

                if (in_array($status, ['active', 'enabled', '1'], true)) {
                    return 'active';
                }

                if (in_array($status, ['inactive', 'disabled', '0'], true)) {
                    return 'inactive';
                }
            }

            return 'unknown';
        };

        /*
         * Return only safe, relevant contributor information.
         */
        $formatUser = function ($user) use ($getAccountStatus): array {
            $attributes = $user->getAttributes();

            $fullName = trim(implode(' ', array_filter([
                $attributes['first_name'] ?? null,
                $attributes['last_name'] ?? null,
            ])));

            if ($fullName === '') {
                $fullName = $attributes['name'] ?? null;
            }

            return [
                'id' => $user->id,
                'name' => $fullName,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'username' => $attributes['username'] ?? null,
                'account_status' => $getAccountStatus($user),
            ];
        };

        $contributorDetails = $contributors
            ->map($formatUser)
            ->values();

        /*
         * Paginate company campaigns.
         */
        $perPage = min(
            max((int) $request->input('per_page', 25), 1),
            100
        );

        $campaignPaginator = Campaign::query()
            ->where('company_id', $companyId)
            ->with('user')
            ->withCount([
                'transactions as successful_contribution_count' => function ($query) {
                    $query->where('type', 'deposit')
                        ->where('status', 'successful');
                },
                'transactions as successful_withdrawal_count' => function ($query) {
                    $query->where('type', 'withdrawal')
                        ->where('status', 'successful');
                },
            ])
            ->withSum([
                'transactions as successful_contribution_amount' => function ($query) {
                    $query->where('type', 'deposit')
                        ->where('status', 'successful');
                },
            ], 'amount')
            ->withSum([
                'transactions as successful_withdrawal_fees' => function ($query) {
                    $query->where('type', 'withdrawal')
                        ->where('status', 'successful');
                },
            ], 'fee')
            ->latest('id')
            ->paginate($perPage);

        /*
         * Map only the campaigns on the current page.
         *
         * The paginator's collection() method is not required:
         * use the paginator's items() method to obtain the page
         * records, then transform those records separately.
         */
        $campaignItems = collect($campaignPaginator->items());

        $campaigns = $campaignItems
            ->map(function (Campaign $campaign) use (
                $companyId,
                $getAccountStatus,
                $formatUser
            ) {
                $creator = $campaign->user;

                /*
                 * Unique successful contributors for this campaign.
                 */
                $campaignContributorIds = CampaignTransaction::query()
                    ->where('campaign_id', $campaign->id)
                    ->where('company_id', $companyId)
                    ->where('type', 'deposit')
                    ->where('status', 'successful')
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id');

                $campaignContributors = \App\Models\User::query()
                    ->whereIn('id', $campaignContributorIds)
                    ->get()
                    ->map($formatUser)
                    ->values();

                /*
                 * Latest 20 campaign transactions.
                 */
                $recentTransactions = $campaign->transactions()
                    ->with('user')
                    ->latest('id')
                    ->limit(20)
                    ->get()
                    ->map(function ($transaction) {
                        $user = $transaction->user;
                        $attributes = $user?->getAttributes() ?? [];

                        $userName = null;

                        if ($user) {
                            $userName = trim(implode(' ', array_filter([
                                $attributes['first_name'] ?? null,
                                $attributes['last_name'] ?? null,
                            ])));

                            if ($userName === '') {
                                $userName = $attributes['name'] ?? null;
                            }
                        }

                        return [
                            'id' => $transaction->id,
                            'user_id' => $transaction->user_id,
                            'user_name' => $userName,
                            'type' => $transaction->type,
                            'amount' => (float) $transaction->amount,
                            'fee' => (float) $transaction->fee,
                            'balance_before' => (float) $transaction->balance_before,
                            'balance_after' => (float) $transaction->balance_after,
                            'reference' => $transaction->transaction_reference,
                            'status' => $transaction->status,
                            'description' => $transaction->description,
                            'metadata' => $transaction->metadata,
                            'created_at' => $transaction->created_at,
                        ];
                    })
                    ->values();

                $creatorDetails = $creator
                    ? $formatUser($creator)
                    : [
                        'id' => null,
                        'name' => null,
                        'email' => null,
                        'phone' => null,
                        'username' => null,
                        'account_status' => 'unknown',
                    ];

                return [
                    'id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name,
                    'description' => $campaign->description,
                    'status' => $campaign->status,
                    'target_amount' => (float) $campaign->target_amount,
                    'total_contributed' => (float) $campaign->total_contributed,
                    'withdrawable_amount' => (float) $campaign->withdrawable_amount,
                    'campaign_fee_percentage' => (float) $campaign->campaign_fee_percentage,
                    'creator_account_number' => $campaign->creator_account_number,

                    'creator' => [
                        'id' => $creatorDetails['id'],
                        'name' => $creatorDetails['name'],
                        'email' => $creatorDetails['email'],
                        'phone' => $creatorDetails['phone'],
                    ],

                    'successful_contribution_count' =>
                        (int) $campaign->successful_contribution_count,

                    'successful_withdrawal_count' =>
                        (int) $campaign->successful_withdrawal_count,

                    'successful_contribution_amount' =>
                        (float) ($campaign->successful_contribution_amount ?? 0),

                    'successful_withdrawal_fees' =>
                        (float) ($campaign->successful_withdrawal_fees ?? 0),

                    'contributor_count' => $campaignContributors->count(),
                    'contributors' => $campaignContributors,
                    'recent_transactions' => $recentTransactions,

                    'closed_at' => $campaign->closed_at,
                    'last_withdrawn_at' => $campaign->last_withdrawn_at,
                    'created_at' => $campaign->created_at,
                    'updated_at' => $campaign->updated_at,
                ];
            })
            ->values();

        /*
         * Construct the pagination response explicitly.
         * No paginator mutation methods are required.
         */
        $campaignPagination = [
            'current_page' => $campaignPaginator->currentPage(),
            'data' => $campaigns,
            'first_page_url' => $campaignPaginator->url(1),
            'from' => $campaignPaginator->firstItem(),
            'last_page' => $campaignPaginator->lastPage(),
            'last_page_url' => $campaignPaginator->url(
                $campaignPaginator->lastPage()
            ),
            'next_page_url' => $campaignPaginator->nextPageUrl(),
            'path' => $campaignPaginator->path(),
            'per_page' => $campaignPaginator->perPage(),
            'prev_page_url' => $campaignPaginator->previousPageUrl(),
            'to' => $campaignPaginator->lastItem(),
            'total' => $campaignPaginator->total(),
        ];

        return response()->json([
            'status' => true,
            'message' => 'Campaign analytics retrieved successfully.',
            'data' => [
                'overview' => [
                    'total_campaigns' => (int) $campaignStats->total_campaigns,
                    'active_campaigns' => (int) $campaignStats->active_campaigns,
                    'closed_campaigns' => (int) $campaignStats->closed_campaigns,
                    'total_targets' => (float) $campaignStats->total_targets,
                    'total_contributed' => (float) $campaignStats->total_contributed,
                    'total_withdrawable' => (float) $campaignStats->total_withdrawable,
                    'withdrawal_fee_revenue' => (float) $feeRevenue,
                    'unique_contributors' => $contributors->count(),
                    'active_contributors' => $contributorDetails
                        ->where('account_status', 'active')
                        ->count(),
                    'inactive_contributors' => $contributorDetails
                        ->where('account_status', 'inactive')
                        ->count(),
                    'contributors_with_unknown_status' => $contributorDetails
                        ->where('account_status', 'unknown')
                        ->count(),
                ],
                'contributors' => $contributorDetails,
                'campaigns' => $campaignPagination,
            ],
        ], 200);

    } catch (Throwable $e) {
        Log::error('Admin campaign dashboard failed', [
            'user_id' => auth('api')->id(),
            'company_id' => auth('api')->user()?->company_id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unable to retrieve campaign analytics.',
            'data' => [],
        ], 500);
    }
}
}