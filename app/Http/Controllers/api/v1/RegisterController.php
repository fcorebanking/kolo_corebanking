<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Mail\TransactionReport;
use App\Models\banks;
use App\Models\branch;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use App\Services\FinovaApiService;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function branch(Request $request)
    {
        try {
            $companyRecord = Company::where(['company_reference' => $request->company])->first();
            return response()->json(['data' => branch::with('currency')->where('company_id', $companyRecord->id)->get(), 'status' => true]);
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }
    public function product(Request $request)
    {
        try {
            $companyRecord = Company::where(['company_reference' => $request->company])->first();
            return response()->json(['data' => $companyRecord->savingsProducts, 'status' => true]);
        } catch (\Exception $e) {
            return \response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
        // return User::all();
    }

 #[OA\Post(
    path: "/api/v1/users/register",
    summary: "Register a new user",
    description: "Creates a new customer account under a specified company. The registration process validates the user's information, optionally verifies BVN/NIN, creates a virtual account when applicable, creates the customer's wallet, assigns the default role, and returns the newly created account details.",
    tags: ["User - Version 1"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: [
                "first_name",
                "last_name",
                "phone",
                "email",
                "dob",
                "username",
                // "company",
                "pin",
                "password"
            ],
            properties: [
                new OA\Property(
                    property: "first_name",
                    type: "string",
                    minLength: 2,
                    maxLength: 50,
                    example: "John",
                    description: "Customer's first name."
                ),
                new OA\Property(
                    property: "last_name",
                    type: "string",
                    minLength: 2,
                    maxLength: 50,
                    example: "Doe",
                    description: "Customer's last name."
                ),
                new OA\Property(
                    property: "phone",
                    type: "string",
                    example: "08012345678",
                    description: "Customer's phone number."
                ),
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "john.doe@example.com",
                    description: "Customer's email address."
                ),
                new OA\Property(
                    property: "dob",
                    type: "string",
                    format: "date",
                    example: "1995-08-15",
                    description: "Customer's date of birth."
                ),
                new OA\Property(
                    property: "username",
                    type: "string",
                    example: "johndoe",
                    description: "Unique username for the customer."
                ),
                // new OA\Property(
                //     property: "company",
                //     type: "string",
                //     example: "1",
                //     description: "Company ID or company reference under which the customer will be registered."
                // ),
                new OA\Property(
                    property: "pin",
                    type: "string",
                    example: "1234",
                    description: "Customer's transaction PIN."
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "Password@123",
                    description: "Customer's login password."
                ),
                new OA\Property(
                    property: "nin",
                    type: "string",
                    nullable: true,
                    example: "12345678901",
                    description: "Customer's National Identification Number (NIN), when required/provided."
                ),
                new OA\Property(
                    property: "bvn",
                    type: "string",
                    nullable: true,
                    example: "22123456789",
                    description: "Customer's Bank Verification Number (BVN), when required/provided."
                ),
                new OA\Property(
                    property: "sex",
                    type: "string",
                    nullable: true,
                    example: "Male",
                    description: "Customer's gender."
                ),
                new OA\Property(
                    property: "address",
                    type: "string",
                    nullable: true,
                    example: "12 Allen Avenue, Ikeja, Lagos",
                    description: "Customer's residential address."
                ),
                new OA\Property(
                    property: "account_type",
                    type: "string",
                    nullable: true,
                    enum: ["virtual"],
                    example: "virtual",
                    description: "Account type. If set to regular, a virtual account is not created through the virtual account service."
                ),
                new OA\Property(
                    property: "selected_product",
                    type: "integer",
                    nullable: true,
                    example: 1,
                    description: "ID of the savings product to associate with the new account."
                ),
                new OA\Property(
                    property: "branch",
                    type: "integer",
                    nullable: true,
                    example: 182,
                    description: "Branch ID. If omitted, the company's first branch is used."
                ),
                // new OA\Property(
                //     property: "referral",
                //     type: "string",
                //     nullable: true,
                //     example: "johndoe123",
                //     description: "Referral username or user ID."
                // ),
                new OA\Property(
                    property: "account_bank",
                    type: "string",
                    nullable: true,
                    example: "090286",
                    description: "Bank code used for specific virtual account creation flows."
                ),
                new OA\Property(
                    property: "id",
                    type: "string",
                    nullable: true,
                    example: "12345678901",
                    description: "Identity verification reference used by supported virtual account providers."
                ),
                new OA\Property(
                    property: "identityNumber",
                    type: "string",
                    nullable: true,
                    example: "22123456789",
                    description: "Alternative identity number used when NIN is not supplied in supported provider flows."
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Registration successful.",
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
                        example: "Registration Successful"
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "user_reference",
                                type: "string",
                                example: "CUS1234567890"
                            ),
                            new OA\Property(
                                property: "account_number",
                                type: "string",
                                example: "5080135881"
                            ),
                            new OA\Property(
                                property: "bank",
                                type: "string",
                                example: "Example Bank"
                            ),
                            new OA\Property(
                                property: "phone_number",
                                type: "string",
                                example: "08012345678"
                            ),
                            new OA\Property(
                                property: "otp_code",
                                type: "string",
                                example: ""
                            ),
                            new OA\Property(
                                property: "pin",
                                type: "string",
                                example: ""
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Registration failed, invalid company/product/bank, identity verification failure, or account creation failure."
        ),
        new OA\Response(
            response: 406,
            description: "Validation failed."
        ),
        new OA\Response(
            response: 409,
            description: "Conflict. NIN, BVN, email, phone number, or username already exists."
        ),
        new OA\Response(
            response: 500,
            description: "Internal server error."
        )
    ]
)]
public function register(Request $request)
{
    try {
        /*
        |--------------------------------------------------------------------------
        | VALIDATE REGISTRATION REQUEST
        |--------------------------------------------------------------------------
        */

        $rules = [
            'company_id' => 'required|integer',
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'required|string',
            'email'      => 'required|email',
            'dob'        => 'required|date',
            'username'   => 'required|string',
            'pin'        => 'required|numeric|gt:0',
            'password'   => 'required|string',
        ];

        if ($request->has('nin')) {
            $rules['nin'] = 'required|string';
        }

        if ($request->has('bvn')) {
            $rules['bvn'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 406);
        }

        /*
        |--------------------------------------------------------------------------
        | CAPTURE KOLOPAY COMPANY ID
        |--------------------------------------------------------------------------
        |
        | This is the local company the new customer belongs to.
        | It must not be replaced with Finova's company ID.
        |
        */

        $localCompanyId = (int) $request->company_id;

        /*
        |--------------------------------------------------------------------------
        | CHECK LOCAL DUPLICATES
        |--------------------------------------------------------------------------
        */

        $existingEmail = User::where('company_id', $localCompanyId)
            ->where('email', $request->email)
            ->exists();

        if ($existingEmail) {
            return response()->json([
                'status'  => false,
                'message' => 'Email already exists.',
                'data'    => [],
            ], 409);
        }

        $existingPhone = User::where('company_id', $localCompanyId)
            ->where('phone', $request->phone)
            ->exists();

        if ($existingPhone) {
            return response()->json([
                'status'  => false,
                'message' => 'Phone number already exists.',
                'data'    => [],
            ], 409);
        }

        $existingUsername = User::where('username', $request->username)
            ->exists();

        if ($existingUsername) {
            return response()->json([
                'status'  => false,
                'message' => 'Username already exists.',
                'data'    => [],
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | PREPARE FINOVA REGISTRATION PAYLOAD
        |--------------------------------------------------------------------------
        */

        $payload = $request->only([
            'first_name',
            'last_name',
            'phone',
            'email',
            'dob',
            'username',
            'pin',
            'password',
            'nin',
            'bvn',
            'gender',
            'address',
            'account_type',
            'selected_product',
            'branch',
            'referral',
            'account_bank',
            'identityId',
            '_id',
            'id',
            'identityNumber',
            'otp',
        ]);

        // Finova registration is always under company 111.
        $payload['company'] = '111';

        // Deliberately do not send local company_id to Finova.

        \logInfo([
            'endpoint' => '/api/v1/users/register',
            'finova_company_id' => 111,
            'local_company_id' => $localCompanyId,
            'email' => $request->email,
            'phone' => $request->phone,
            'request_fields' => array_keys($payload),
        ], 'KOLO REGISTRATION - FINOVA REQUEST');

        /*
        |--------------------------------------------------------------------------
        | REGISTER CUSTOMER WITH FINOVA
        |--------------------------------------------------------------------------
        */

        $baseUrl = rtrim(config('services.finova.base_url'), '/');

        $coreResponse = Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(90)
            ->post(
                $baseUrl . '/users/register',
                $payload
            );

        $responseData = $coreResponse->json();

        \logInfo([
            'finova_company_id' => 111,
            'local_company_id' => $localCompanyId,
            'http_status' => $coreResponse->status(),
            'registration_status' => $responseData['status'] ?? false,
            'registration_message' => $responseData['message'] ?? null,
        ], 'KOLO REGISTRATION - FINOVA RESPONSE');

        /*
        |--------------------------------------------------------------------------
        | CHECK FINOVA REGISTRATION RESULT
        |--------------------------------------------------------------------------
        */

        if (
            !$coreResponse->successful() ||
            !is_array($responseData) ||
            ($responseData['status'] ?? false) !== true
        ) {
            return response()->json(
                $responseData ?? [
                    'status' => false,
                    'message' => 'Invalid response received from Finova Core Banking.',
                    'data' => [],
                ],
                $coreResponse->status() ?: 502
            );
        }

        /*
        |--------------------------------------------------------------------------
        | EXTRACT FINOVA ACCOUNT DETAILS
        |--------------------------------------------------------------------------
        */

        $finovaData = $responseData['data'] ?? [];

        $accountNumber = $finovaData['account_number'] ?? null;
        $accountReference = $finovaData['user_reference'] ?? null;

        if (empty($accountNumber) || empty($accountReference)) {
            \logInfo([
                'local_company_id' => $localCompanyId,
                'response_fields' => array_keys($finovaData),
                'reason' => 'Finova registration succeeded but account details were incomplete.',
            ], 'KOLO REGISTRATION - INCOMPLETE FINOVA RESPONSE');

            return response()->json([
                'status' => false,
                'message' => 'Finova registration succeeded, but the returned account details were incomplete. Please contact support before retrying.',
                'data' => [],
            ], 502);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE LOCAL KOLOPAY USER
        |--------------------------------------------------------------------------
        |
        | Finova does not return the complete user record.
        | Profile details are taken from the original request.
        | Account details are taken from Finova's response.
        |
        */

        DB::beginTransaction();

        try {
            $user = User::create([
                'uuid' => (string) Str::uuid(),

                // Local KoloPay company ID, NOT Finova's 111.
                'company_id' => $localCompanyId,

                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'username' => $request->username,

                'password' => Hash::make($request->password),

                // Do not store transaction PINs in plaintext locally.
                // Finova remains authoritative for the transaction PIN.
                'pin' => null,

                'dob' => $request->dob,
                'gender' => $request->gender ?? null,
                'address' => $request->address ?? null,

                'bvn' => $request->bvn ?? null,
                'nin' => $request->nin ?? null,

                // Finova's returned account details.
                'virtual_account' => $accountNumber,
                'account_reference' => $accountReference,

                // No local mapping has been established for these.
                'bank_id' => 126,
                'branch_id' => 182,
                'account_officer_id' => 1199,

                // Other optional fields.
                'profile_pic' => $request->profile_pic ?? null,
                'signature' => $request->signature ?? null,
                'status_id' => $request->status_id ?? null,
                'bvn_status' => $request->bvn_status ?? null,
                'kyc_level_id' => $request->kyc_level_id ?? null,
                'phone_verification_status_id' => null,
                'user_type_id' => 1,

                // Finova does not return these during registration.
                'otp' => null,
                'access_token' => null,
            ]);

            if (!$user) {
                throw new \RuntimeException(
                    'Unable to create the local KoloPay user.'
                );
            }

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            \logInfo([
                'local_company_id' => $localCompanyId,
                'finova_account_reference' => $accountReference,
                'error' => $e->getMessage(),
            ], 'KOLO REGISTRATION - LOCAL USER SYNC FAILED');

            return response()->json([
                'status' => false,
                'message' => 'Your Finova account was created successfully, but we could not synchronize your KoloPay profile. Please contact support. Do not retry registration.',
                'data' => [
                    'user_reference' => $accountReference,
                    'account_number' => $accountNumber,
                ],
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN FINOVA REGISTRATION RESPONSE
        |--------------------------------------------------------------------------
        */

        \logInfo([
            'local_user_id' => $user->id,
            'local_company_id' => $user->company_id,
            'finova_account_reference' => $accountReference,
            'status' => 'successful',
        ], 'KOLO REGISTRATION - LOCAL USER CREATED');

        return response()->json($responseData, $coreResponse->status());

    } catch (ConnectionException $e) {

        \logInfo([
            'error' => $e->getMessage(),
        ], 'KOLO REGISTRATION - FINOVA CONNECTION ERROR');

        return response()->json([
            'status' => false,
            'message' => 'Unable to connect to Finova Core Banking. Please try again later.',
            'data' => [],
        ], 503);

    } catch (Throwable $e) {

        \logInfo([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ], 'KOLO REGISTRATION - ERROR');

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while processing registration.',
            'data' => [],
        ], 500);
    }
}


// #[OA\Post(
//     path: "/api/v1/users/create-account",
//     summary: "Create a user account",
//     description: "Creates a new customer user record under a specified company. BVN/NIN verification and virtual account creation are not performed during registration. The customer can complete identity verification and account creation later.",
//     tags: ["User - Version 2"],
//     requestBody: new OA\RequestBody(
//         required: true,
//         content: new OA\JsonContent(
//             type: "object",
//             required: [
//                 "first_name",
//                 "last_name",
//                 "phone",
//                 "email",
//                 "dob",
//                 "username",
//                 "company",
//                 "pin",
//                 "password"
//             ],
//             properties: [
//                 new OA\Property(
//                     property: "first_name",
//                     type: "string",
//                     minLength: 2,
//                     maxLength: 50,
//                     example: "John",
//                     description: "Customer's first name."
//                 ),
//                 new OA\Property(
//                     property: "last_name",
//                     type: "string",
//                     minLength: 2,
//                     maxLength: 50,
//                     example: "Doe",
//                     description: "Customer's last name."
//                 ),
//                 new OA\Property(
//                     property: "phone",
//                     type: "string",
//                     example: "08012345678",
//                     description: "Customer's phone number."
//                 ),
//                 new OA\Property(
//                     property: "email",
//                     type: "string",
//                     format: "email",
//                     example: "john.doe@example.com",
//                     description: "Customer's email address."
//                 ),
//                 new OA\Property(
//                     property: "dob",
//                     type: "string",
//                     format: "date",
//                     example: "1995-08-15",
//                     description: "Customer's date of birth."
//                 ),
//                 new OA\Property(
//                     property: "username",
//                     type: "string",
//                     example: "johndoe",
//                     description: "Unique username for the customer."
//                 ),
//                 new OA\Property(
//                     property: "company",
//                     type: "string",
//                     example: "14",
//                     description: "Company ID or company reference under which the user will be registered."
//                 ),
//                 new OA\Property(
//                     property: "pin",
//                     type: "string",
//                     example: "1234",
//                     description: "Customer's transaction PIN."
//                 ),
//                 new OA\Property(
//                     property: "password",
//                     type: "string",
//                     format: "password",
//                     example: "Password@123",
//                     description: "Customer's login password."
//                 ),
//                 new OA\Property(
//                     property: "gender",
//                     type: "string",
//                     nullable: true,
//                     example: "Male",
//                     description: "Customer's gender."
//                 ),
//                 new OA\Property(
//                     property: "address",
//                     type: "string",
//                     nullable: true,
//                     example: "12 Allen Avenue, Ikeja, Lagos",
//                     description: "Customer's residential address."
//                 ),
//                 new OA\Property(
//                     property: "branch",
//                     type: "integer",
//                     nullable: true,
//                     example: 2,
//                     description: "Branch ID. If omitted, the company's first branch is used."
//                 ),
//                 new OA\Property(
//                     property: "referral",
//                     type: "string",
//                     nullable: true,
//                     example: "johndoe123",
//                     description: "Referral username or user ID."
//                 )
//             ]
//         )
//     ),
//     responses: [
//         new OA\Response(
//             response: 200,
//             description: "Registration successful.",
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
//                         example: "Registration Successful"
//                     ),
//                     new OA\Property(
//                         property: "data",
//                         type: "object",
//                         properties: [
//                             new OA\Property(
//                                 property: "user_reference",
//                                 type: "string",
//                                 example: "1234567890"
//                             ),
//                             new OA\Property(
//                                 property: "user_id",
//                                 type: "integer",
//                                 example: 12345
//                             ),
//                             new OA\Property(
//                                 property: "phone_number",
//                                 type: "string",
//                                 example: "08012345678"
//                             ),
//                             new OA\Property(
//                                 property: "company",
//                                 type: "string",
//                                 example: "14"
//                             ),
//                         ]
//                     )
//                 ]
//             )
//         ),
//         new OA\Response(
//             response: 400,
//             description: "Registration failed or invalid company."
//         ),
//         new OA\Response(
//             response: 406,
//             description: "Validation failed."
//         ),
//         new OA\Response(
//             response: 409,
//             description: "Conflict. Email, phone number, or username already exists."
//         ),
//         new OA\Response(
//             response: 500,
//             description: "Internal server error."
//         )
//     ]
//  )]
 public function noBvnRegister(Request $request)
 {
    $rules = [
        'first_name' => 'required|string|max:50|min:2',
        'last_name'  => 'required|string|max:50|min:2',
        'phone'      => 'required|string',
        'email'      => 'required|email|string',
        'dob'        => 'required|date',
        'username'   => 'required|string',
        'company'    => 'required|string',
        'pin'        => 'required|numeric|gt:0',
        'password'   => 'required|string',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->all()[0],
        ], 406);
    }

    \logInfo($request->except(['password', 'pin']), "No BVN Registration request");

    /*
    |--------------------------------------------------------------------------
    | Find Company
    |--------------------------------------------------------------------------
    */

    $companyRecord = Company::where('company_reference', $request->company)
        ->orWhere('id', $request->company)
        ->first();

    if (! $companyRecord || $companyRecord->id == 27) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid company provided',
        ], 400);
    }

    \logInfo($companyRecord->id, "Current company ID");

    /*
    |--------------------------------------------------------------------------
    | Validate Company Status
    |--------------------------------------------------------------------------
    */

    if ($companyRecord->status_id != 1) {
        return response()->json([
            'status'  => false,
            'message' => 'Encounter error while trying to connect to institution.. please try again later',
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Branch
    |--------------------------------------------------------------------------
    */

    $branch = $request->branch;

    if (! $branch) {
        $branch = $companyRecord->branch[0]->id ?? null;
    }

    if (! $branch) {
        return response()->json([
            'status'  => false,
            'message' => 'No branch found for this company',
        ], 400);
    }

    $branchData = branch::where('id', $branch)->first();

    if (! $branchData) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid branch selected',
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Email
    |--------------------------------------------------------------------------
    */

    $existingUser = User::where([
        ['email', $request->email],
        ['company_id', $companyRecord->id],
    ])->first();

    if ($existingUser) {
        return response()->json([
            'status'  => false,
            'message' => 'Email already exists',
        ], 409);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Phone
    |--------------------------------------------------------------------------
    */

    $existingUser = User::where([
        ['phone', $request->phone],
        ['company_id', $companyRecord->id],
    ])->first();

    if ($existingUser) {
        return response()->json([
            'status'  => false,
            'message' => 'Phone number already exists',
        ], 409);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Username
    |--------------------------------------------------------------------------
    */

    $existingUser = User::where(
        'username',
        $request->username
    )->first();

    if ($existingUser) {
        return response()->json([
            'status'  => false,
            'message' => 'Username already exists',
        ], 409);
    }

    /*
    |--------------------------------------------------------------------------
    | Account Officer
    |--------------------------------------------------------------------------
    */

    $accountOfficer = User::where(
        'company_id',
        $companyRecord->id
    )->first();

    /*
    |--------------------------------------------------------------------------
    | Generate Customer Reference + OTP
    |--------------------------------------------------------------------------
    */

    $customerReference = generateAccountReference();

    // $otp = generateOtp();

    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | No BVN verification.
    | No NIN verification.
    | No AccountCreationService.
    | No virtual account creation.
    | No wb_account_details.
    | No user_wallet.
    | No Otp
    |
    | BVN and NIN remain NULL until the customer completes verification.
    |
    */

    DB::beginTransaction();

    try {

        $userData = [
            'first_name'                   => $request->first_name,
            'last_name'                    => $request->last_name,
            'company_id'                   => $companyRecord->id,
            'email'                        => $request->email,

            // KYC is performed later.
            'bvn'                          => null,
            'nin'                          => null,

            'phone'                        => $request->phone,
            'otp'                          => null,
            // 'otp'                          => $otp,
            'bank_id'                      => null,
            'pin'                          => $request->pin,
            'address'                      => $request->address ?? null,
            'dob'                          => $request->dob,
            'gender'                       => $request->gender ?? null,

            'sms'                          => 1,
            'kyc_level_id'                 => 1,

            'status_id' => $companyRecord->setting->account_approval == 0
                ? 7
                : 1,

            'branch_id'                    => $branch,
            'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',

            'user_type_id'                 => 1,

            /*
             * No virtual account exists yet.
             */
            'virtual_account'              => null,
            'account_reference'             => null,

            'otp_expiration_date'           => null,

            // 'otp_expiration_date'           => Carbon::now()->addMinutes(10),

            'username'                      => $request->username,

            'account_officer_id'            => $accountOfficer->id ?? null,

            /*
             * uuid can still identify the user before
             * the financial account is created.
             */
            'uuid'                          => $customerReference,

            'password'                      => Hash::make($request->password),

            'phone_verification_status_id'  => (
                isset($branchData->currency_id)
                && $branchData->currency_id == 1
            )
                ? null
                : 1,
        ];

        /*
        |--------------------------------------------------------------------------
        | Referral
        |--------------------------------------------------------------------------
        */

        if ($request->filled('referral')) {

            $refeUser = User::where(
                'company_id',
                $companyRecord->id
            )
                ->where(function ($query) use ($request) {
                    $query->where(
                        'username',
                        $request->referral
                    )->orWhere(
                        'id',
                        $request->referral
                    );
                })
                ->first();

            if (! $refeUser) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid referral username',
                ], 400);
            }

            $userData['referral_id'] = $refeUser->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        $user = User::create($userData);

        if (! $user) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Failed to complete Registration Err::09',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Assign Default Customer Role
        |--------------------------------------------------------------------------
        */

        $user->assignRole([4]);

        
        /*
        |--------------------------------------------------------------------------
        | Generate Access Token
        |--------------------------------------------------------------------------
        */

        $accessToken = $user->createToken('authToken')->accessToken;

        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Referral Processing
        |--------------------------------------------------------------------------
        */

        if (isset($user->referral_id)) {
            $this->referralProcessJob($user);
        }

        /*
        |--------------------------------------------------------------------------
        | Registration Response
        |--------------------------------------------------------------------------
        */

        logInfo([
            'user_id'        => $user->id,
            'user_reference' => $customerReference,
            'phone_number'   => $user->phone,
            'company_id'     => $companyRecord->id,
        ], "No BVN Registration Successful");

        return response()->json([
            'status'  => true,
            'message' => 'Registration Successful',
            'data'    => [
                'user_reference' => $customerReference,
                'user_id'        => $user->id,
                'phone_number'   => $user->phone,
                'access_token'  => $accessToken,
                'company_id'     => $companyRecord->id,
                /*
                 * No account exists yet.
                 */
                'account_number' => null,
                'bank'           => null,

                /*
                 * Keep the existing OTP behavior.
                 */
                // 'otp_code'       => $user->otp ?? null,
                'pin'            => $user->pin ?? null,
                'bvn_verified' => false,
                'kyc_required' => true,
            ],
        ], 200);
        

    } catch (\Throwable $e) {

        DB::rollBack();

        \logInfo([
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ], "Registration failure");

        return response()->json([
            'status'  => false,
            'message' => 'Failed to complete Registration',
        ], 400);
    }
}

    public function ussdRegister(Request $request)
    {

        logInfo($request->all(), "Register Request log");

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'string',
            'email'      => 'email|string',
            'dob'        => 'date',
            'company'    => 'required|string',
        ]);

        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (! $companyRecord) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }
        $selectedProduct = $companyRecord->savingsProducts[0];

        if (! ($selectedProduct)) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }

        // $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // if ($companyRecord->country->phone_code == "234") {
        //     $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);
        // } else {
        //     $userPhone = $request->phone;
        // }
        $userPhone = $request->phone;

        // $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->orwhere([['phone', $userPhone], ['company_id', $companyRecord->id]])->orwhere('username', $request->username)->get();

        // \logInfo($user, "Current user");

        // if (count($user) > 0) {
        //     $ra = array("status" => false, "message" => "User is already existing");
        //     return response()->json($ra, 409);
        // }

        if ($validator->passes()) {

            $bank_id = $companyRecord->setting->preferred_bank_id ?? 126;

            logInfo($bank_id, "Preferred bank");

            $selectedBank = banks::find($bank_id);

            $virtualAccount = new VirtualAccountType();
            logInfo($selectedBank->bank_code, "Preferred bank code");

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            logInfo($response, "Create Account service response");
            $customerReference       = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];

            if ($response["status"]) {
                // if (true) {

                $accountOfficer = User::where('company_id', $companyRecord->id)->first();
                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name'                   => $request->first_name,
                        'last_name'                    => $request->last_name,
                        'company_id'                   => $companyRecord->id,
                        'email'                        => $request->email,
                        'bvn'                          => $request->bvn,
                        'phone'                        => $userPhone,
                        'otp'                          => $otp,
                        'bank_id'                      => $bank_id ?? 126,
                        'pin'                          => $request->pin,
                        'dob'                          => $request->dob,
                        'sms'                          => 1,
                        'kyc_level_id'                 => 1,
                        'status_id'                    => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                        'branch_id'                    => $companyRecord->branch[0]->id,
                        'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id'                 => 1,
                        'phone_verification_status_id' => 1,
                        'virtual_account'              => $virtual_acccount_number,
                        'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                        'account_reference'            => $customerReference,
                        'username'                     => $request->username,
                        'account_officer_id'           => $accountOfficer->id,
                        'uuid'                         => $customerReference,
                        'password'                     => Hash::make($request->password),
                    ]);

                    $regfee = $companyRecord->setting->registration_fee;

                    logInfo($regfee, "registration_fee for {$companyRecord->id}");

                    $wb_account_details = wb_account_details::create([

                        'user_id'            => $user->id,
                        'account_product_id' => $selectedProduct->id,
                        'bankid'             => $bank_id ?? 126,
                        'virtual_accnum'     => $virtual_acccount_number,
                        'acc_reference'      => $customerReference,
                        'has_atm_card'       => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance'              => (0 - $regfee),
                    ]);

                    logInfo($user_wallet->balance, "user_wallet balance");

                    \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

                    $bank = banks::where('id', $bank_id)->first();

                    if (! $bank) {
                        response()->json(['status' => false, 'message' => 'Invalid bank selected']);
                    }

                    if ($user && $user_wallet) {
                        $user->assignRole([4]);

                        $accountNumber = $virtual_acccount_number;

                        $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";

                        // $welcomeMessage = "Welcome FirstName! Your account number is 5080135881. Bank Name: Asset Matrix MFB.  upload your Valid ID and link BVN. Have Access via USSD. Simply dial *7006*10#. T&Cs applyEnjoy 24/7.";

                        $smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00%23 To verify your phone from the registered phone number", true, false, 'Verify OTP', '', 0, false);
                        $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);

                        // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);

                        \logInfo($smsstate, "SMS response");

                        DB::commit();

                        return response(['status' => true, 'message' => 'Registration Successful', 'data' => ['account_number' => $virtual_acccount_number, 'user_reference' => $customerReference]]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                    }
                } catch (\Exception $e) {

                    \logInfo($e->getTraceAsString(), "Registration failure");
                    DB::rollback();
                    // something went wrong
                    return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration', 'data' => $response], 400);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            // return back()->with('fail', $errors);
        }
    }

    public function apiRegister(Request $request)
    {

        logInfo($request->all(), "Register Request log");

        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:50|min:2',
            'last_name'      => 'required|string|max:50|min:2',
            'phone'          => 'required|string',
            'preferred_bank' => 'required',
            'email'          => 'required|email|string',
            'dob'            => 'required|date',
            'company'        => 'required|string',
        ]);

        if ($validator->fails()) {
            $ra = ["status" => false, "message" => $validator->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $companyRecord = Company::where(['company_reference' => $request->company])->first();

        if (! $companyRecord) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return response()->json($ra, 400);
        }

        if (count($companyRecord->branch) < 1) {
            $ra = ["status" => false, "message" => "Kindly setup your company branch to be able to acesss API. or contact Wired banking support for assistance tech@wbalite.com"];
            return response()->json($ra, 400);
        }

        \logInfo($companyRecord, "Branch first");

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return response()->json($ra, 400);
        }

        $userPhone = formatPhoneNumber($request->phone, $companyRecord->country->phone_code);

        $user = User::where([['email', $request->email], ['company_id', $companyRecord->id]])->get();

        \logInfo($user, "Current user");

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "User is already existing"];
            return response()->json($ra, 409);
        }

        if ($validator->passes()) {

            $bank_id = $companyRecord->setting->preferred_bank_id ?? 126;

            logInfo($bank_id, "Preferred bank");

            $selectedBank = banks::find($request->preferred_bank);

            if (! $selectedBank) {
                $ra = ["status" => false, "message" => "Invalid bank code. Kindly provide a valid bank code to proceed"];
                return response()->json($ra, 409);
            }

            $virtualAccount = new VirtualAccountType();

            $virtualAccount->bankCode                     = $selectedBank->bank_code;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            if ($selectedBank->bank_code == '090286') {
                $virtualAccount->bvnId = $request->identity_id;
                $virtualAccount->otp   = $request->otp;
            }

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return response()->json($response);
            }
            logInfo($response, "Create Account service response");
            $customerReference       = $response['data']['account_reference'];
            $virtual_acccount_number = $response['data']['virtual_account'];

            // echo \json_encode($createAdminAccount);

            if ($response["status"]) {

                //
                //  GENERATE AND SEND OTP TO USER HERE
                //
                $otp = generateOtp();
                // $otp = '7777';
                DB::beginTransaction();

                try {

                    $user = User::create([
                        'first_name'                   => $request->first_name,
                        'last_name'                    => $request->last_name,
                        'company_id'                   => $companyRecord->id,
                        'email'                        => $request->email,
                        'bvn'                          => $request->bvn,
                        'phone'                        => $userPhone,
                        'otp'                          => $otp,
                        'bank_id'                      => $request->preferred_bank,
                        'pin'                          => "0000",
                        'dob'                          => $request->dob,
                        'sms'                          => 1,
                        'kyc_level_id'                 => 1,
                        'status_id'                    => 7,
                        'branch_id'                    => $companyRecord->branch[0]->id,
                        'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                        'user_type_id'                 => 4,
                        'phone_verification_status_id' => 1,
                        'virtual_account'              => $virtual_acccount_number,
                        'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                        'account_reference'            => $customerReference,
                        'username'                     => strtolower($request->first_name) . "" . $otp,
                        'uuid'                         => $customerReference,
                        'password'                     => "00000000",
                    ]);

                    $wb_account_details = wb_account_details::create([

                        'user_id'            => $user->id,
                        'account_product_id' => 1,
                        'bankid'             => $request->preferred_bank,
                        'virtual_accnum'     => $virtual_acccount_number,
                        'acc_reference'      => $customerReference,
                        'has_atm_card'       => 0,

                    ]);

                    $user_wallet = user_wallet::create([
                        'wb_account_detail_id' => $wb_account_details->id,
                        'balance'              => 0,
                    ]);

                    if ($user && $user_wallet) {
                        $user->assignRole([4]);

                        $accountNumber = $virtual_acccount_number;

                        $welcomeMessage = "Welcome $request->username Account No: $accountNumber. Kindly upload your Valid ID & Recent Utility bill. USSD Access: *7006*10#. T&Cs apply";

                        // $sendSMS = \sendVansoSMS($userPhone, "Welcome to Asset Matrix. Your OTP is $otp");
                        // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);
                        $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);

                        \logInfo($smsstate, "SMS response");

                        DB::commit();

                        return response([
                            'status'  => true,
                            'message' => 'Registration Successful',
                            'data'    => [
                                'account_number' => $accountNumber,
                                'account_name'   => "$request->first_name $request->last_name",
                                'bank_name'      => $selectedBank->bank_name,
                                'bank_code'      => $selectedBank->bank_code,
                            ],
                        ]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                    }
                } catch (\Exception $e) {

                    \logInfo($e->getTraceAsString(), "Registration failure");
                    DB::rollback();
                    // something went wrong
                    return response()->json(['status' => false, 'message' => 'Failed to complete Registration'], 400);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to complete Registration', 'data' => $createAdminAccount], 400);
            }
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            // return back()->with('fail', $errors);
        }

        // return response(['status' => false, 'message' => 'Failed to register']);
    }

    public function verifyBVN(Request $request)
    {
        $identityPassController = new IdentityPassController();

        $bvnResponse = $identityPassController->verifyBVNMobile($request->bvn);

        return $bvnResponse;

    }


// #[OA\Post(
//     path: "/api/v1/users/verify-bvn-v2",
//     summary: "Verify BVN and create a virtual account",
//     description: "Requires the user's BVN and date of birth.",
//     tags: ["BVN"],
//     security: [["bearerAuth" => []]],
//     requestBody: new OA\RequestBody(
//         required: true,
//         content: new OA\JsonContent(
//             type: "object",
//             required: ["bvn", "dob"],
//             properties: [
//                 new OA\Property(
//                     property: "bvn",
//                     type: "string",
//                     example: "12345678901",
//                     description: "11-digit Bank Verification Number"
//                 ),
//                 new OA\Property(
//                     property: "dob",
//                     type: "string",
//                     format: "date",
//                     example: "1998-04-23",
//                     description: "Date of birth in YYYY-MM-DD format"
//                 )
//             ]
//         )
//     ),
//     responses: [
//         new OA\Response(
//             response: 200,
//             description: "BVN verified and virtual account created successfully"
//         ),
//         new OA\Response(
//             response: 401,
//             description: "Unauthenticated"
//         ),
//         new OA\Response(
//             response: 422,
//             description: "Validation failed"
//         ),
//         new OA\Response(
//             response: 400,
//             description: "BVN verification or account creation failed"
//         )
//     ]
// )]
public function verifyBVN2(Request $request)
{
    $token = $request->bearerToken();

    return app(FinovaApiService::class)->post(
        '/users/verify-bvn-v2',
        $token,
        $request->only([
            'bvn',
            'dob',
        ])
    );
}


    private function sendTransactionReportEmail($subtext, $msg, $reason, $user)
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

    private function referralProcessJob($userData)
    {
        try {
            \logInfo('Referral  job', 'Referral');
            if (isset($userData->company->CustomerReferalSetting->direct_comission)) {

                $referalsetting                  = $userData->company->CustomerReferalSetting;
                $referalsetting_direct_comission = $referalsetting->direct_comission;
                if ($referalsetting_direct_comission > 0) {
                    \logInfo("Message: Referral set for $userData->company_id ", 'Referral Direct commission for ' . $userData->ReferralUpline->company_id);

                    if (isset($userData->ReferralUpline)) {
                        if ($userData->ReferralUpline->company_id != $userData->company_id) {
                            $authuser            = $userData; // Fetch the user model
                            $authuser->status_id = 5;         // Update the status
                            $authuser->save();                // Save the changes

                            \logInfo('Message: possible fraud transaction ', 'possible fraud transaction for ' . $userData->id);
                        }
                        $wb_account_details            = wb_account_details::where('user_id', $userData->ReferralUpline->id)->first();
                        $referalPaymentTransactionType = 111; //refPayment
                        $transactions                  = transactions::where(['account_id' => $wb_account_details->id, 'transaction_type_id' => $referalPaymentTransactionType, "created_by" => $userData->id, "initiator" => $userData->id])->count();
                        \logInfo($transactions, "Referall paid transaction count");
                        if ($transactions == 0) {

                            $ledgerCr                      = null;
                            $ledgerDr                      = null;
                            $get_ledger_setting_commission = get_CR_DR_TXN_Type_Ledger($referalPaymentTransactionType, $userData->company_id);
                            if ($get_ledger_setting_commission) {
                                $ledgerCr = $get_ledger_setting->ledger_cr;
                                $ledgerDr = $get_ledger_setting->ledger_dr;
                            }

                            $userWallet    = $wb_account_details->user_wallet;
                            $balanceBefore = $userWallet->balance;
                            $balanceAfter  = $balanceBefore + $referalsetting_direct_comission;

                            $data = [
                                "balance_before"         => $balanceBefore,
                                "amount"                 => $referalsetting_direct_comission,
                                "description"            => "Referal Comission",
                                "account_id"             => $wb_account_details->id,
                                "branch_id"              => $userData->branch_id,
                                "transaction_status_id"  => 1,
                                "transaction_reference"  => generate_txnID(),
                                "transaction_type_id"    => $referalPaymentTransactionType, //debit 7
                                "credit_debit_type_id"   => 1,
                                "transaction_remarks_id" => 2,
                                "company_id"             => $userData->company_id,
                                "balance_after"          => $balanceAfter,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => $userData->id,
                                "ledger_cr"              => $ledgerCr,
                                "ledger_dr"              => $ledgerDr,
                                "initiator"              => $userData->id,
                            ];
                            $data['userParamType'] = 2;
                            $data['userId']        = $wb_account_details->id;
                            $data['remark']        = 2;
                            $data['chargeamt']     = 0;
                            $CreditCustomer        = DebitCreditcustomer($data);
                            \logInfo('Message: Success comission paid', 'Commisiion: paid for ' . $userData->id);
                        } else {
                            \logInfo('Message: Failed comission paid', 'Commisiion: already paid for ' . $userData->id);
                        }

                    }
                }
            }

        } catch (\Throwable $e) {

            \Log::error('Referral Commission failed for user ' . $userData->id, [
                'error' => $e->getMessage(),
            ]);

        }
    }
}
