<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Mail\TransactionReport;
use App\Models\CarouselImage;
use App\Models\Company;
use App\Models\savings_product;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

#[OA\Post(
    path: "/api/v1/users/login",
    summary: "User Login",
    description: "Authenticates a customer using their username and password. If the credentials are valid and the account is active and verified, an access token and customer account information are returned. If the customer's phone has not been verified, an OTP is sent and the account is placed in pending verification status.",
    tags: ["User - Version 1"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: [ 
                "username",
                "password"
            ],
            properties: [
                new OA\Property(
                    property: "username",
                    type: "string",
                    example: "johndoe",
                    description: "Customer's username."
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "Password@123",
                    description: "Customer's login password."
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Login successful.",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Login Successful"
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "user",
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "first_name",
                                        type: "string",
                                        example: "John"
                                    ),
                                    new OA\Property(
                                        property: "last_name",
                                        type: "string",
                                        example: "Doe"
                                    ),
                                    new OA\Property(
                                        property: "uuid",
                                        type: "string",
                                        example: "CUS123456789"
                                    ),
                                    new OA\Property(
                                        property: "phone",
                                        type: "string",
                                        example: "08012345678"
                                    ),
                                    new OA\Property(
                                        property: "profile_pic",
                                        type: "string",
                                        example: "https://safe.wbalite.com/passports/default_image.jpg"
                                    ),
                                    new OA\Property(
                                        property: "username",
                                        type: "string",
                                        example: "johndoe"
                                    ),
                                    new OA\Property(
                                        property: "bvn",
                                        type: "string",
                                        example: "22123456789"
                                    ),
                                    new OA\Property(
                                        property: "dob",
                                        type: "string",
                                        format: "date",
                                    ),
                                    new OA\Property(
                                        property: "referral_id",
                                        type: "string",
                                        example: "REF123456789"
                                    ),
                                    new OA\Property(
                                        property: "bvn_status_id",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "nin",
                                        type: "string",
                                        example: "12345678901"
                                    ),
                                    new OA\Property(
                                        property: "email",
                                        type: "string",
                                        format: "email",
                                        example: "john.doe@example.com"
                                    ),
                                    new OA\Property(
                                        property: "address",
                                        type: "string",
                                        example: "12 Allen Avenue, Ikeja, Lagos"
                                    ),
                                    new OA\Property(
                                        property: "sex",
                                        type: "string",
                                        example: "Male"
                                    ),
                                    new OA\Property(
                                        property: "is_agent",
                                        type: "boolean",
                                        example: false
                                    ),
                                    new OA\Property(
                                        property: "virtual_account",
                                        type: "string",
                                        example: "5080135881"
                                    ),
                                    new OA\Property(
                                        property: "account_reference",
                                        type: "string",
                                        example: "CUS123456789"
                                    ),
                                    new OA\Property(
                                        property: "bank_id",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "validID_file",
                                        type: "string",
                                        nullable: true,
                                        example: "https://example.com/documents/valid-id.jpg"
                                    ),
                                    new OA\Property(
                                        property: "otherID_file",
                                        type: "string",
                                        nullable: true,
                                        example: ""
                                    ),
                                    new OA\Property(
                                        property: "company_id",
                                        type: "integer",
                                        example: 46
                                    ),
                                    new OA\Property(
                                        property: "status_id",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "phone_verification_status_id",
                                        type: "integer",
                                        nullable: true,
                                        example: 2
                                    ),
                                    new OA\Property(
                                        property: "account_officer_id",
                                        type: "integer",
                                        example: 15
                                    ),
                                    new OA\Property(
                                        property: "branch_id",
                                        type: "integer",
                                        example: 2
                                    ),
                                    new OA\Property(
                                        property: "tier",
                                        type: "integer",
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: "balance",
                                        type: "number",
                                        format: "float",
                                        example: 25000.50
                                    ),
                                    new OA\Property(
                                        property: "wallets",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(
                                                    property: "account_id",
                                                    type: "integer",
                                                    example: 101
                                                ),
                                                new OA\Property(
                                                    property: "account_number",
                                                    type: "string",
                                                    example: "5080135881"
                                                ),
                                                new OA\Property(
                                                    property: "bank_id",
                                                    type: "integer",
                                                    example: 1
                                                ),
                                                new OA\Property(
                                                    property: "bank",
                                                    type: "string",
                                                    example: "Example Bank"
                                                ),
                                                new OA\Property(
                                                    property: "product_name",
                                                    type: "string",
                                                    example: "Savings Account"
                                                ),
                                                new OA\Property(
                                                    property: "wallet_balance",
                                                    type: "number",
                                                    format: "float",
                                                    example: 25000.50
                                                ),
                                                new OA\Property(
                                                    property: "currency",
                                                    type: "string",
                                                    example: "NGN"
                                                ),
                                                new OA\Property(
                                                    property: "currency_id",
                                                    type: "integer",
                                                    example: 1
                                                )
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: "cards",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object"
                                        )
                                    ),
                                    new OA\Property(
                                        property: "sliders",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object"
                                        )
                                    ),
                                    new OA\Property(
                                        property: "loanCount",
                                        type: "integer",
                                        example: 2
                                    ),
                                    new OA\Property(
                                        property: "company",
                                        type: "object",
                                        properties: [
                                            new OA\Property(
                                                property: "phone",
                                                type: "string",
                                                example: "08098765432"
                                            ),
                                            new OA\Property(
                                                property: "email",
                                                type: "string",
                                                format: "email",
                                                example: "support@example.com"
                                            )
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "is_card_available",
                                        type: "boolean",
                                        example: true
                                    ),
                                    new OA\Property(
                                        property: "currency",
                                        type: "string",
                                        example: "NGN"
                                    ),
                                    new OA\Property(
                                        property: "total_wallet_balance_currency",
                                        type: "number",
                                        format: "float",
                                        example: 25000.50
                                    ),
                                    new OA\Property(
                                        property: "international_account",
                                        type: "string",
                                        nullable: true,
                                        example: "REM123456789"
                                    )
                                ]
                            ),
                            new OA\Property(
                                property: "access_token",
                                type: "string",
                                example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 203,
            description: "Account pending phone verification.",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Account Pending Verification"
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "user_reference",
                                type: "string",
                                example: "CUS123456789"
                            ),
                            new OA\Property(
                                property: "phone_number",
                                type: "string",
                                example: "08012345678"
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Login unavailable because the company's subscription has expired or the system is undergoing maintenance."
        ),
        new OA\Response(
            response: 401,
            description: "Authentication failed, account is blocked, company is inactive, or the account is not permitted to log in."
        ),
        new OA\Response(
            response: 404,
            description: "Invalid login credentials because the user's company has been deleted."
        ),
        new OA\Response(
            response: 406,
            description: "Validation failed. Username and password are required."
        )
    ]
)]

public function login(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Username and password fields are required',
            ], 406);
        }

        // Company ID is controlled by Kolo, never by the API caller.
        $companyId = 111;

        $payload = [
            'username' => $request->username,
            'password' => $request->password,
            'company_id' => $companyId,
        ];

        logInfo([
            'endpoint' => '/api/v1/users/login',
            'company_id' => $companyId,
            'username' => $request->username,
        ], 'KOLO LOGIN - FORWARDING TO FINOVA CORE');

        $coreResponse = Http::acceptJson()
            ->asJson()
            ->timeout(60)
            ->post(
                rtrim(config('services.finova.base_url'), '/') .
                '/users/login',
                $payload
            );

        $responseData = $coreResponse->json();

        logInfo([
            'company_id' => $companyId,
            'http_status' => $coreResponse->status(),
            'response_status' => data_get($responseData, 'status'),
            'response_message' => data_get($responseData, 'message'),
        ], 'KOLO LOGIN - FINOVA CORE RESPONSE');

        // Only process successful Finova authentication responses.
        if (
            !$coreResponse->successful() ||
            !data_get($responseData, 'status')
        ) {
            return response()->json(
                $responseData ?? [
                    'status' => false,
                    'message' => 'Invalid response received from Finova Core Banking',
                ],
                $coreResponse->status()
            );
        }

        $finovaUser = data_get($responseData, 'data.user');

        $finovaUsername = data_get($finovaUser, 'username');

        $finovaAccessToken = data_get(
            $responseData,
            'data.access_token'
        );

        // Validate the identity and company returned by Finova.
        if (
            empty($finovaUsername) ||
            (int) data_get($finovaUser, 'company_id') !== $companyId
        ) {
            logInfo([
                'company_id' => $companyId,
                'returned_username' => $finovaUsername,
                'returned_company_id' => data_get($finovaUser, 'company_id'),
            ], 'KOLO LOGIN - COMPANY OR USER VALIDATION FAILED');

            return response()->json([
                'status' => false,
                'message' => 'Invalid login credentials',
            ], 401);
        }

        if (empty($finovaAccessToken)) {
            return response()->json([
                'status' => false,
                'message' => 'Finova did not return an access token.',
            ], 500);
        }

        
/*
|--------------------------------------------------------------------------
| Synchronize Finova user with local Kolo user
|--------------------------------------------------------------------------
*/

// Get the user details returned by Finova.
$finovaUser = data_get($responseData, 'data.user');

$finovaUsername = data_get($finovaUser, 'username');

$finovaAccessToken = data_get(
    $responseData,
    'data.access_token'
);

// Validate the returned user details.
if (
    empty($finovaUsername) ||
    (int) data_get($finovaUser, 'company_id') !== $companyId
) {
    logInfo([
        'company_id' => $companyId,
        'returned_username' => $finovaUsername,
        'returned_company_id' => data_get($finovaUser, 'company_id'),
    ], 'KOLO LOGIN - COMPANY OR USER VALIDATION FAILED');

    return response()->json([
        'status' => false,
        'message' => 'Invalid login credentials',
    ], 401);
}

if (empty($finovaAccessToken)) {
    return response()->json([
        'status' => false,
        'message' => 'Finova did not return an access token.',
    ], 500);
}

$localUser = User::firstOrNew([
    'username' => $finovaUsername,
    'company_id' => $companyId,
]);

$isNewUser = !$localUser->exists;

if ($isNewUser) {
    $localUser->forceFill([
        'first_name' => data_get($finovaUser, 'first_name'),
        'last_name' => data_get($finovaUser, 'last_name'),
        'username' => $finovaUsername,
        'uuid' => data_get($finovaUser, 'uuid'),
        'phone' => data_get($finovaUser, 'phone'),
        'email' => data_get($finovaUser, 'email'),
        'profile_pic' => data_get($finovaUser, 'profile_pic'),
        'dob' => data_get($finovaUser, 'dob'),
        'address' => data_get($finovaUser, 'address'),
        'sex' => data_get($finovaUser, 'sex'),

        'bvn_status_id' => data_get($finovaUser, 'bvn_status_id'),
        'referral_id' => data_get($finovaUser, 'referral_id'),
        'is_agent' => data_get($finovaUser, 'is_agent', 0),

        'virtual_account' => data_get($finovaUser, 'virtual_account'),
        'account_reference' => data_get($finovaUser, 'account_reference'),
        'bank_id' => data_get($finovaUser, 'bank_id'),

        'company_id' => $companyId,
        'status_id' => data_get($finovaUser, 'status_id'),
        'phone_verification_status_id' => data_get(
            $finovaUser,
            'phone_verification_status_id'
        ),

        'account_officer_id' => data_get($finovaUser, 'account_officer_id'),
        'branch_id' => data_get($finovaUser, 'branch_id'),

        // Finova's tier value maps to Kolo's kyc_level_id.
        'kyc_level_id' => data_get($finovaUser, 'tier'),

        'password' => Hash::make(bin2hex(random_bytes(32))),
    ]);
}

$localUser->forceFill([
    'access_token' => $finovaAccessToken,
])->save();

logInfo([
    'company_id' => $companyId,
    'user_id' => $localUser->id,
    'username' => $localUser->username,
    'user_created' => $isNewUser,
    'token_saved' => true,
], 'KOLO LOGIN - LOCAL USER SYNCHRONIZED');

        // Return Finova's original response and HTTP status.
        return response()->json(
            $responseData,
            $coreResponse->status()
        );

    } catch (ConnectionException $e) {

        logInfo([
            'company_id' => 111,
            'error' => $e->getMessage(),
        ], 'KOLO LOGIN - FINOVA CONNECTION ERROR');

        return response()->json([
            'status' => false,
            'message' => 'Unable to connect to Finova Core Banking. Please try again later.',
        ], 503);

} catch (Throwable $e) {

    logInfo([
        'company_id' => 111,
        'exception' => get_class($e),
        'error' => 'An internal error occurred during KoloPay login synchronization.',
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
    ], 'KOLO LOGIN - ERROR');

    report($e);

    return response()->json([
        'status' => false,
        'message' => 'An error occurred while processing login.',
    ], 500);
}
}



// #[OA\Post(
//     path: "/api/v1/users/login-v2",
//     summary: "User Login V2",
//     description: "Authenticates a customer using their email and password and returns a fresh access token. This endpoint does not perform phone verification, OTP verification, or any other verification step. A new access token is generated on every successful login.",
//     tags: ["User - Version 2"],
//     requestBody: new OA\RequestBody(
//         required: true,
//         content: new OA\JsonContent(
//             type: "object",
//             required: [
//                 "email",
//                 "password"
//             ],
//             properties: [
//                 new OA\Property(
//                     property: "email",
//                     type: "string",
//                     format: "email",
//                     example: "john.doe@example.com",
//                     description: "Customer's email address."
//                 ),
//                 new OA\Property(
//                     property: "password",
//                     type: "string",
//                     format: "password",
//                     example: "Password\@123",
//                     description: "Customer's login password."
//                 )
//             ]
//         )
//     ),
//     responses: [
//         new OA\Response(
//             response: 200,
//             description: "Login successful and a new access token has been generated.",
//             content: new OA\JsonContent(
//                 type: "object",
//                 properties: [
//                     new OA\Property(
//                         property: "status",
//                         type: "boolean",
//                         example: true
//                     ),
//                     new OA\Property(
//                         property: "message",
//                         type: "string",
//                         example: "Login Successful"
//                     ),
//                     new OA\Property(
//                         property: "data",
//                         type: "object",
//                         properties: [
//                             new OA\Property(
//                                 property: "user",
//                                 type: "object",
//                                 properties: [
//                                     new OA\Property(
//                                         property: "first_name",
//                                         type: "string",
//                                         example: "John"
//                                     ),
//                                     new OA\Property(
//                                         property: "last_name",
//                                         type: "string",
//                                         example: "Doe"
//                                     ),
//                                     new OA\Property(
//                                         property: "uuid",
//                                         type: "string",
//                                         example: "CUS123456789"
//                                     ),
//                                     new OA\Property(
//                                         property: "phone",
//                                         type: "string",
//                                         example: "08012345678"
//                                     ),
//                                     new OA\Property(
//                                         property: "email",
//                                         type: "string",
//                                         format: "email",
//                                         example: "john.doe@example.com"
//                                     ),
//                                     new OA\Property(
//                                         property: "company_id",
//                                         type: "integer",
//                                         example: 46
//                                     ),
//                                     new OA\Property(
//                                         property: "status_id",
//                                         type: "integer",
//                                         example: 1
//                                     )
//                                 ]
//                             ),
//                             new OA\Property(
//                                 property: "access_token",
//                                 type: "string",
//                                 example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
//                             )
//                         ]
//                     )
//                 ]
//             )
//         ),
//         new OA\Response(
//             response: 400,
//             description: "Login unavailable because the company's subscription has expired."
//         ),
//         new OA\Response(
//             response: 401,
//             description: "Authentication failed, account is blocked, company is inactive, or the account is not permitted to log in."
//         ),
//         new OA\Response(
//             response: 404,
//             description: "Invalid login credentials because the user's company has been deleted."
//         ),
//         new OA\Response(
//             response: 406,
//             description: "Validation failed. Email and password are required."
//         )
//     ]
// )]
public function loginV2(Request $request)
{
    logInfo($request->all(), "LOGIN V2 REQUEST");

    /*
    |--------------------------------------------------------------------------
    | Validate request
    |--------------------------------------------------------------------------
    */

    $validate = Validator::make($request->all(), [
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Email and password fields are required'
        ], 406);
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticate using EMAIL + PASSWORD
    |--------------------------------------------------------------------------
    */

    $login = [
        'email' => $request->email,
        'password' => $request->password
    ];

    if (!Auth::attempt($login)) {

        $user = User::where('email', $request->email)
            ->where('status_id', '!=', 13)
            ->first();

        if ($user) {

            if ($user->login_fails == 4) {
                $user->status_id = 6;
            } else {
                $user->login_fails += 1;
            }

            $user->save();

            if ($user->status_id == 6) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Account has been blocked. Please contact admin to unlock Account'
                ], 401);
            }

            $attemptTxt = $user->login_fails == 3
                ? 'attempt'
                : 'attempts';

            return response()->json([
                'status' => false,
                'message' => 'Invalid Login credentials. Your account will be deactivated after ' .
                    (5 - $user->login_fails) . " wrong $attemptTxt"
            ], 401);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid Login Credentials'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Get authenticated user
    |--------------------------------------------------------------------------
    */

    $user = Auth::user();

    /*
    |--------------------------------------------------------------------------
    | Company checks
    |--------------------------------------------------------------------------
    */

    if ($user->company->status_id == 13) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid Login Credentials'
        ], 404);
    }

    if ($user->company->status_id != 1) {
        return response()->json([
            'status' => false,
            'message' => 'Encounter error while trying to connect to institution.. please try again later'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Restricted companies
    |--------------------------------------------------------------------------
    */

    if (in_array($user->company->id, [26, 32, 37, 51, 52, 54, 57, 83])) {
        return response()->json([
            'status' => false,
            'message' => 'Encounter error while trying to connect to institution.. please try again later'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer account check
    |--------------------------------------------------------------------------
    */

    if ($user->user_type_id == 2) {
        return response()->json([
            'status' => false,
            'message' => 'This Account is not a Customer Account'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Account status checks
    |--------------------------------------------------------------------------
    */

    if ($user->status_id == 7) {
        return response()->json([
            'status' => false,
            'message' => 'Your Account is currently being reviewed and will be approved soon'
        ], 401);
    }

    if ($user->status_id == 5) {
        return response()->json([
            'status' => false,
            'message' => 'Your account has been blocked. Kindly contact support to unblock.'
        ], 401);
    }

    if ($user->status_id == 6) {
        return response()->json([
            'status' => false,
            'message' => 'Your account has been blocked. Kindly Reset Password'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription check
    |--------------------------------------------------------------------------
    */

    if ($user->company->sub->end_date < Date('Y-m-d')) {
        return response()->json([
            'status' => false,
            'message' => 'Please hold on we are carrying out a quick maintenance on our system'
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Reset failed login counter
    |--------------------------------------------------------------------------
    */

    $user->login_fails = 0;
    $user->save();

    /*
    |--------------------------------------------------------------------------
    | Generate fresh access token
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | No phone verification
    | No OTP
    | No email verification
    | No verification status check
    |
    */

    $accessToken = $user
        ->createToken('authToken')
        ->accessToken;

    $virtualCard = UserCard::where(
            'user_id',
            auth()->user()->id
        )->get();

        if (!$virtualCard) {
            $virtualCard = [];
        }

        // Total wallet balance
        $total_wallet_balance = 0;

       $wallet = Auth::user()->accounts()->first();

      $userWallet = user_wallet::where(
        'wb_account_detail_id',
         $wallet?->id
          )->first();

        // Prevent null balance issues
        $walletBalance = $userWallet->balance ?? 0;

            // Add this wallet's balance to the overall wallet total
        $total_wallet_balance += $walletBalance;
    /*
    |--------------------------------------------------------------------------
    | Return user information
    |--------------------------------------------------------------------------
    */

    $userDetails = [
        "first_name" => $user->first_name,
        "last_name" => $user->last_name,
        "uuid" => $user->uuid,
        "phone" => $user->phone,
        "profile_pic" => $user->profile_pic ?? "",
        "username" => $user->username,
        "bvn" => $user->bvn,
        "dob" => $user->dob,
        "bvn_status_id" => $user->bvn_status_id,
        "referral_id" => $user->id,
        "nin" => $user->nin,
        "email" => $user->email,
        "address" => $user->address,
        "sex" => $user->sex,
        "is_agent" => $user->is_agent,
        "virtual_account" => $user->virtual_account,
        "account_reference" => $user->account_reference,
        "bank_id" => $user->bank_id,
        "validID_file" => $user->validID_file,
        "otherID_file" => $user->otherID_file,
        "company_id" => $user->company_id,
        "status_id" => $user->status_id,
        "phone_verification_status_id" => $user->phone_verification_status_id,
        "account_officer_id" => $user->account_officer_id,
        "branch_id" => $user->branch_id,
        "tier" => $user->kyc_level_id,
        "total_wallet_balance" => $total_wallet_balance,
        "cards" => $virtualCard,
    ];

    return response()->json([
        'status' => true,
        'message' => 'Login Successful',
        'data' => [
            'user' => $userDetails,
            'access_token' => $accessToken
        ]
    ], 200);
}


    public function getTokenAndRefreshToken($email, $password)
    {

        $http     = new Client;
        $response = $http->request('POST', 'http://safe.wbalite.com/oauth/token', [
            'form_params' => [
                'grant_type'    => 'password',
                'client_id'     => 1,
                'client_secret' => "yGTxmSZ0rwMzaP0xt5EE2GPmy5PSj4TVQS6zQkge",
                'username'      => $email,
                'password'      => $password,
                'scope'         => '*',
            ],
        ]);
        $result = json_decode((string) $response->getBody(), true);
        return $result;
    }

    private function sendEmail($subtext, $msg, $reason, $user)
    {

        $data = [
            'subtext'      => $subtext,
            'reason'       => $reason,
            'desc'         => $msg,
            'company_name' => $user->company->company_name,
            'user'         => $user->company->company_name,
        ];

        try {
            Mail::to($user->email)->send(new TransactionReport($data));
        } catch (\Throwable $e) {
            \Log::error("Failed to send TransactionReport email: " . $e->getMessage());
            // continue silently
        }
    }

    public function getExchangeRates($final_amount, $destinationCurrencyID, $sourceCurrencyID)
    {

        try {
            $destinationCurrency = $this->matchCurrency($destinationCurrencyID);
            $sourceCurrency      = $this->matchCurrency($sourceCurrencyID);
            $amount              = $final_amount;
            \logInfo("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount", "Get Exhange URL");
            $response = Http::get("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount");
            \logInfo(json_encode($response->json()), "Get Exhange Response");

            return $response->json();

        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Get Exhange Error");
            return response()->json(["status" => 0, 'message' => "Failed to Verify Account"], 400);
        }

    }


public function loginPin(Request $request)
{
    try {

        // 1. Validate request
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Username and password fields are required'
            ], 406);
        }

        // 2. Retrieve user and company relationships
        $user = User::with(['company.sub'])
            ->where('username', $request->username)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Login Credentials'
            ], 401);
        }

        // 3. Check blocked accounts
        if (in_array((int) $user->status_id, [5, 6], true)) {
            return response()->json([
                'status' => false,
                'message' => $user->status_id == 6
                    ? 'Your account has been blocked. Kindly Reset Password'
                    : 'Your account has been blocked. Kindly contact support to unblock.'
            ], 401);
        }

        // 4. Verify PIN
        if (!hash_equals((string) $user->pin, (string) $request->password)) {

            $user->login_fails = ((int) $user->login_fails) + 1;

            // Block after 5 incorrect PIN attempts
            if ($user->login_fails >= 5) {
                $user->status_id = 6;
            }

            $user->save();

            if ($user->status_id == 6) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Account has been blocked. Please contact admin to unlock Account'
                ], 401);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid Login Credentials'
            ], 401);
        }

        // 5. Reset failed login attempts after successful PIN verification
        if ((int) $user->login_fails > 0) {
            $user->login_fails = 0;
            $user->save();
        }

        // 6. Validate company
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to identify your institution.'
            ], 401);
        }

        // 7. Check company status
        if ($company->status_id == 13) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Login Credentials'
            ], 404);
        }

        if ($company->status_id != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Encounter error while trying to connect to institution. Please try again later'
            ], 401);
        }

        // 8. Restricted companies
        $restrictedCompanies = [26, 32, 33, 37, 51, 52, 54, 57, 83];

        if (in_array((int) $company->id, $restrictedCompanies, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Encounter error while trying to connect to institution. Please try again later'
            ], 401);
        }

        // 9. Customer account validation
        if ($user->user_type_id == 2) {
            return response()->json([
                'status' => false,
                'message' => 'This Account is not a Customer Account'
            ], 401);
        }

        // 10. Account approval status
        if ($user->status_id == 7) {
            return response()->json([
                'status' => false,
                'message' => 'Your Account is currently being reviewed and will be approved soon'
            ], 401);
        }

        // 11. Subscription validation
        $subscription = $company->sub;

        if (!$subscription || $subscription->end_date < now()->toDateString()) {
            return response()->json([
                'status' => false,
                'message' => 'Please hold on we are carrying out a quick maintenance on our system'
            ], 400);
        }

        // 12. Phone verification is NOT required for login.
        // Users can log in whether phone_verification_status_id is 1, 2, or another value.

        // 13. Prepare user details
        $userDetails = [
            'first_name'                   => $user->first_name,
            'last_name'                    => $user->last_name,
            'uuid'                         => $user->uuid,
            'phone'                        => $user->phone,
            'profile_pic'                  => $user->profile_pic ?? '',
            'username'                     => $user->username,
            'bvn'                          => $user->bvn,
            'bvn_status_id'                => $user->bvn_status_id,
            'nin'                          => $user->nin,
            'email'                        => $user->email,
            'address'                      => $user->address,
            'sex'                          => $user->sex,
            'is_agent'                     => $user->is_agent,
            'virtual_account'              => $user->virtual_account,
            'account_reference'            => $user->account_reference,
            'bank_id'                      => $user->bank_id,
            'validID_file'                 => $user->validID_file,
            'otherID_file'                 => $user->otherID_file,
            'company_id'                   => $user->company_id,
            'status_id'                    => $user->status_id,
            'phone_verification_status_id' => $user->phone_verification_status_id,
            'account_officer_id'           => $user->account_officer_id,
            'branch_id'                    => $user->branch_id,
            'tier'                         => $user->kyc_level_id,

            'company' => [
                'phone' => $company->company_phone,
                'email' => $company->company_email,
            ],
        ];

        // 14. Generate access token
        $accessToken = $user->createToken('authToken')->accessToken;

        // 15. Successful login response
        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'data' => [
                'user' => $userDetails,
                'access_token' => $accessToken
            ]
        ], 200);

    } catch (\Throwable $e) {

        logInfo([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 'Login PIN Error');

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while trying to log in. Please try again later.'
        ], 500);
    }
}


    private function matchCurrency($currency_id)
    {
        return match ($currency_id) {
            2       => "USD",
            3       => "CAD",
            4       => "GBP",
            5       => "ZAR",
            6       => "CNY",
            7       => "EUR",
            8       => "GHS",

            default => "NGN",
        };
    }
}
