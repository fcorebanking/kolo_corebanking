<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Http;

class IdentityPassController extends Controller
{
    private $authorization;
    private $appId;
    private $baseUrl;
    private $appKey;

    public function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->authorization = config('identitypass.authorization_test');
            $this->appId         = config('identitypass.app_id_test');
            $this->baseUrl       = config('identitypass.base_url_test');
            $this->appKey        = config('identitypass.app_key_test');

        } else {

            $this->authorization = config('identitypass.authorization_live');
            $this->appId         = config('identitypass.app_id_live');
            $this->baseUrl       = config('identitypass.base_url_live');
            $this->appKey        = config('identitypass.app_key_live');
        }

        \logInfo($this->baseUrl, "Dojah Base Url");
    }

    public function verifyBVN($bvn)
    {
        logInfo($bvn, "BVN Number");

        $url = "{$this->baseUrl}verification/bvn";

        $body = [
            'number' => $bvn,

        ];

        try {
            $bvn_response = $this->identitypassPostRequest($url, $body);

            logInfo($bvn_response, "Verify BVN");

            if (isset($bvn_response['response_code']) && $bvn_response['response_code'] == "00") {
                return [
                    'status'  => true,
                    'message' => 'BVN Verified Successfuly', 'data' => $bvn_response,
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify BVN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Identitypass Error");

            return ['status' => false, 'message' => 'Failed to Verify BVN'];
        }
    }

public function verifyBVNMobile($bvn)
{
    logInfo([
        'bvn' => $bvn,
        'bvn_type' => gettype($bvn),
    ], "BVN Number");

    $url = "{$this->baseUrl}verification/bvn_validation";

    $body = [
        'number' => (string) $bvn,
    ];

    logInfo([
        'url' => $url,
        'body' => $body,
    ], "Prembly BVN Request");

    try {

        $bvn_response = $this->identitypassPostRequest($url, $body);

        logInfo($bvn_response, "Verify BVN");

        if (
            isset($bvn_response['response_code'])
            && $bvn_response['response_code'] == "00"
        ) {
            return [
                'status'  => true,
                'message' => 'BVN Verified Successfully',
                'data'    => $bvn_response,
            ];
        }

        return [
            'status'        => false,
            'response_code' => $bvn_response['response_code'] ?? null,
            'message'       => $bvn_response['message']
                ?? $bvn_response['detail']
                ?? 'Failed to verify BVN',
            'data'          => $bvn_response,
        ];

    } catch (\Throwable $e) {

        logInfo([
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ], "Identitypass Error");

        return [
            'status'  => false,
            'message' => 'Failed to verify BVN',
        ];
    }
}

public function verifyBVNMobile2($bvn)
{
    logInfo([
        'bvn'      => $bvn,
        'bvn_type' => gettype($bvn),
    ], "BVN Number - Account Creation Flow");

    /*
    |--------------------------------------------------------------------------
    | 1. Get the authenticated user
    |--------------------------------------------------------------------------
    */

    $user = auth('api')->user();

    if (!$user) {
        return [
            'status'  => false,
            'message' => 'Unauthenticated',
        ];
    }

    logInfo([
        'user_id'    => $user->id,
        'company_id' => $user->company_id,
        'email'      => $user->email,
    ], "BVN Account Creation User");


    /*
    |--------------------------------------------------------------------------
    | 2. Prevent creating another account if the user already has one
    |--------------------------------------------------------------------------
    */

    if (!empty($user->virtual_account)) {
        return [
            'status'  => false,
            'message' => 'User already has a virtual account',
            'data'    => [
                'virtual_account'   => $user->virtual_account,
                'account_reference' => $user->account_reference,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Verify BVN with Prembly
    |--------------------------------------------------------------------------
    */

    $url = "{$this->baseUrl}verification/bvn_validation";

    $body = [
        'number' => (string) $bvn,
    ];

    logInfo([
        'url'  => $url,
        'body' => $body,
    ], "Prembly BVN Request - Account Creation Flow");

    try {

        $bvn_response = $this->identitypassPostRequest($url, $body);

        logInfo($bvn_response, "Verify BVN - Account Creation Flow");

        /*
        |--------------------------------------------------------------------------
        | 4. Stop immediately if BVN verification failed
        |--------------------------------------------------------------------------
        */

        if (
            !isset($bvn_response['response_code']) ||
            $bvn_response['response_code'] != "00"
        ) {

            return [
                'status'        => false,
                'response_code' => $bvn_response['response_code'] ?? null,
                'message'       => $bvn_response['message']
                    ?? $bvn_response['detail']
                    ?? 'Failed to verify BVN',
                'data'          => $bvn_response,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Get company
        |--------------------------------------------------------------------------
        */

        $company = \App\Models\Company::find($user->company_id);

        if (!$company) {
            return [
                'status'  => false,
                'message' => 'Company associated with this user was not found',
            ];
        }

        logInfo($company->id, "BVN Account Creation Company ID");


        /*
        |--------------------------------------------------------------------------
        | 6. Get the company's configured bank
        |
        | Same logic used in the old registration:
        |
        | preferred_bank_id
        |       OR
        | company_wallet.bank_id
        |--------------------------------------------------------------------------
        */

        $companyWallet = \App\Models\company_wallet::where(
            'company_id',
            $company->id
        )->first();

        $walletBankId = $companyWallet->bank_id ?? null;

        $bankId = $company->setting->preferred_bank_id ?? $walletBankId;

        if (!$bankId) {
            return [
                'status'  => false,
                'message' => 'No bank has been configured for this company',
            ];
        }

        logInfo($bankId, "BVN Account Creation Bank ID");


        /*
        |--------------------------------------------------------------------------
        | 7. Get bank record
        |--------------------------------------------------------------------------
        */

        $selectedBank = \App\Models\banks::where('id', $bankId)->first();

        if (!$selectedBank) {
            return [
                'status'  => false,
                'message' => 'Invalid bank configured for this company',
            ];
        }

        logInfo([
            'bank_id'   => $selectedBank->id,
            'bank_name' => $selectedBank->bank_name,
            'bank_code' => $selectedBank->bank_code,
        ], "BVN Account Creation Selected Bank");


        /*
        |--------------------------------------------------------------------------
        | 8. Check whether the company is allowed to create virtual accounts
        |--------------------------------------------------------------------------
        */

        $companyAccountCreationStatus =
            $company->setting->can_create_virtual_account ?? 0;

        if ((int) $companyAccountCreationStatus !== 1) {
            return [
                'status'  => false,
                'message' => 'This company is currently not allowed to create virtual accounts',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Build VirtualAccountType
        |--------------------------------------------------------------------------
        */

        $virtualAccount = new \App\Services\VirtualAccountType();

        $virtualAccount->bankCode =
            $selectedBank->bank_code;

        $virtualAccount->bankID =
            $bankId;

        $virtualAccount->currencyCode =
            $company->currency->code ?? 'NGN';

        /*
         * Keep the same account-name logic from the old registration.
         */
        if (in_array($company->id, [47])) {

            $virtualAccount->firstName =
                "{$user->first_name} {$user->last_name}/{$company->short_name}";

        } elseif (in_array($company->id, [59])) {

            $virtualAccount->firstName =
                "{$user->first_name} {$user->last_name}";

        } else {

            $virtualAccount->firstName =
                "{$company->short_name}/{$user->first_name} {$user->last_name}";
        }

        $virtualAccount->companyShortName =
            $company->short_name;

        $virtualAccount->lastName =
            "";

        $virtualAccount->email =
            $user->email;

        $virtualAccount->accountType =
            'personal';

        $virtualAccount->phone =
            $user->phone;

        $virtualAccount->dob =
            $user->dob;

        $virtualAccount->gender =
            $user->gender;

        $virtualAccount->bvn =
            $bvn;

        $virtualAccount->nin =
            $user->nin;

        $virtualAccount->address =
            $user->address;

        $virtualAccount->country =
            $company->currency_id ?? "";

        $virtualAccount->companyID =
            $company->id;

        $virtualAccount->companyAccountCreationStatus =
            $companyAccountCreationStatus;

        $virtualAccount->accountReference =
            null;

        $virtualAccount->trackingReference =
            null;

        $virtualAccount->type =
            "BVN";

        logInfo(
            json_encode($virtualAccount),
            "VirtualAccountType - BVN Account Creation"
        );


        /*
        |--------------------------------------------------------------------------
        | 10. Create virtual account through AccountCreationService
        |--------------------------------------------------------------------------
        */

        $accountCreationService = new \App\Services\AccountCreationService();
        $accountResponse = $accountCreationService->createVirtualAccount($virtualAccount);

        logInfo($accountResponse, "Account Creation Service Response");

        // Check if the service actually succeeded before saving anything
        if (!isset($accountResponse['status']) || $accountResponse['status'] !== true) {
            return [
                'status'  => false,
                'message' => $accountResponse['message'] ?? 'Failed to provision virtual account',
                'data'    => $accountResponse
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Update the User Model (The actual model instance has the save method!)
        |--------------------------------------------------------------------------
        */
        // Extract the generated account data from the response array
        $virtualAccountNumber = $accountResponse['data']['virtual_account'] ?? null;
        $accountReference = $accountResponse['data']['account_reference'] ?? null;

        // Assign values to the Eloquent model
        $user->virtual_account = $virtualAccountNumber;
        $user->account_reference = $accountReference;
        $user->bvn = $bvn; // Optional: Save the BVN to the user if needed

        $user->save(); 

        return [
            'status'  => true,
            'message' => 'Virtual account created successfully',
            'data'    => [
                'virtual_account'   => $user->virtual_account,
                'account_reference' => $user->account_reference,
            ]
        ];

    } catch (\Exception $e) {
        logInfo(['error' => $e->getMessage()], "BVN Account Creation Exception Error");
        return [
            'status'  => false,
            'message' => 'An error occurred during account creation: ' . $e->getMessage(),
        ];
    }
}

    public function verifyNIN($nin)
    {

        logInfo("getting nin details for $nin");

        $url = "{$this->baseUrl}/verification/vnin";

        $body = [
            'number_nin' => $nin,

        ];

        try {
            $verifyNIN = $this->identitypassPostRequest($url, $body);

            logInfo(json_encode($verifyNIN), "Verify NIN Rates");

            if (isset($verifyNIN['response_code']) && $verifyNIN['response_code'] == "00") {

                return [
                    'status'  => true,
                    'message' => 'NIN Verified Successfuly', 'data' => $verifyNIN,
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to verify NIN'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Identitypass Error");

            return ['status' => false, 'message' => 'Failed to Verify NIN'];
        }
    }

    public function verifyDriverLicense($licenseNo)
    {
        $url = "{$this->baseUrl}verification/bvn_validation";

        logInfo($company_no, "RC Number");
        logInfo($company_type, "Company Type");

        $body = [
            'rc_number'    => $company_no,
            'company_type' => $company_type,
        ];

        try {
            $bvn = $this->identitypassPostRequest($url, $body);

            logInfo($driverLicense, "Verify Driver License");

            if (isset($driverLicense['entity'])) {

                return [
                    'status'  => true,
                    'message' => 'Driver License Verified Successfuly', 'data' => $driverLicense['entity'],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to Drivers License'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Dojah Error");

            return ['status' => false, 'message' => 'Failed to Drivers License'];
        }
    }

    public function verifyCAC($company_type, $company_no)
    {
        $url = "{$this->baseUrl}verification/cac";

        logInfo($company_no, "RC Number");
        logInfo($company_type, "Company Type");

        $body = [
            'rc_number'    => $company_no,
            'company_type' => $company_type,
        ];

        try {
            $cac = $this->identitypassPostRequest($url, $body);

            logInfo($cac, "Verify CAC");

            if (isset($cac['response_code']) && $cac['response_code'] == "00") {

                return [
                    'status'  => true,
                    'message' => 'CAC Verified Successfuly', 'data' => $cac['data'],
                ];
            } else {
                return ['status' => false, 'message' => 'Failed to CAC'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "IdentityPass Error");

            return ['status' => false, 'message' => 'Failed to  CAC'];
        }
    }

public function identitypassPostRequest($endpoint, $body)
{
    logInfo([
        'endpoint' => $endpoint,
        'body' => $body,
    ], "IdentityPass HTTP Request");

    try {

        $response = Http::withHeaders(
            $this->identitypassHeader()
        )
        ->connectTimeout(10)
        ->timeout(30)
        ->post($endpoint, $body);


        logInfo([
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'failed' => $response->failed(),
            'body' => $response->json(),
        ], "IdentityPass HTTP Response");


        return $response->json();

    } catch (\Throwable $e) {

        logInfo([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'endpoint' => $endpoint,
        ], "IdentityPass HTTP Error");

        throw $e;
    }
}

    public function identitypassGetRequest($endpoint)
    {
        logInfo("$endpoint", "Convertion url");
        logInfo("$this->appKey", "Authorization key");
        $response = Http::withHeaders($this->identitypassHeader())->get("$this->baseUrl $endpoint");
        return $response->json();
    }

    public function identitypassHeader()
    {

        $header = [
            "Content-Type" => "application/json",
            "app_id"       => $this->appId,
            "x-api-key"    => $this->appKey,
        ];

        return $header;

    }
}
