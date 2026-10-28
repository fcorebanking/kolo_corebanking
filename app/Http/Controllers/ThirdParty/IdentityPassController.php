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

public function verifyBVNMobile2($bvn, $dob)
{
    logInfo([
     'bvn'      => $bvn,
     'bvn_type' => gettype($bvn),
     'dob'      => $dob,
     ], "BVN Number - Account Creation Flow");

    /*
    |--------------------------------------------------------------------------
    | 1. Get authenticated user
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
    | 2. Prevent duplicate BVN/account verification
    |--------------------------------------------------------------------------
    */

    if (!empty($user->bvn_verified_at)) {
        return [
            'status'  => false,
            'message' => 'Your BVN has already been verified.',
            'data'    => [
                'bvn_verified_at' => $user->bvn_verified_at,
                'virtual_account' => $user->virtual_account,
                'account_reference' => $user->account_reference,
            ],
        ];
    }

    if (!empty($user->virtual_account)) {
        return [
            'status'  => false,
            'message' => 'Your virtual account already exists.',
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

        $bvnResponse = $this->identitypassPostRequest(
            $url,
            $body
        );

        logInfo(
            $bvnResponse,
            "Verify BVN - Account Creation Flow"
        );


        /*
        |--------------------------------------------------------------------------
        | 4. Stop if BVN verification failed
        |--------------------------------------------------------------------------
        */

        if (
            !isset($bvnResponse['response_code']) ||
            $bvnResponse['response_code'] !== "00"
        ) {

            return [
                'status'        => false,
                'response_code' => $bvnResponse['response_code'] ?? null,
                'message'       => $bvnResponse['message']
                    ?? $bvnResponse['detail']
                    ?? 'Failed to verify BVN',
                'data'          => $bvnResponse,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Get company
        |--------------------------------------------------------------------------
        */

        $company = \App\Models\Company::find(
            $user->company_id
        );

        if (!$company) {
            return [
                'status'  => false,
                'message' => 'Company associated with this user was not found',
            ];
        }

        logInfo(
            $company->id,
            "BVN Account Creation Company ID"
        );


        /*
        |--------------------------------------------------------------------------
        | 6. Determine company's bank
        |--------------------------------------------------------------------------
        */

        $companyWallet = \App\Models\company_wallet::where(
            'company_id',
            $company->id
        )->first();

        $walletBankId = $companyWallet->bank_id ?? null;

        $bankId = $company->setting->preferred_bank_id
            ?? $walletBankId;

        if (!$bankId) {
            return [
                'status'  => false,
                'message' => 'No bank has been configured for this company',
            ];
        }

        logInfo(
            $bankId,
            "BVN Account Creation Bank ID"
        );


        /*
        |--------------------------------------------------------------------------
        | 7. Get bank
        |--------------------------------------------------------------------------
        */

        $selectedBank = \App\Models\banks::find($bankId);

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
        | 8. Check virtual account creation permission
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
        |--------------------------------------------------------------------------
        | Account name
        |--------------------------------------------------------------------------
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

        $virtualAccount->lastName = "";

        $virtualAccount->email =
            $user->email;

        $virtualAccount->accountType =
            'personal';

        $virtualAccount->phone =
            $user->phone;

        $virtualAccount->dob =
            $dob;

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
        | 10. Create virtual account
        |--------------------------------------------------------------------------
        */

        $accountCreationService =
            new \App\Services\AccountCreationService();

        $accountResponse =
            $accountCreationService->createVirtualAccount(
                $virtualAccount
            );

        logInfo(
            $accountResponse,
            "Account Creation Service Response"
        );


        /*
        |--------------------------------------------------------------------------
        | 11. Stop if account creation failed
        |--------------------------------------------------------------------------
        */

        if (
            !isset($accountResponse['status']) ||
            $accountResponse['status'] !== true
        ) {

            return [
                'status'  => false,
                'message' => $accountResponse['message']
                    ?? 'Failed to provision virtual account',
                'data'    => $accountResponse,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Extract account information
        |--------------------------------------------------------------------------
        */

        $virtualAccountNumber =
            $accountResponse['data']['virtual_account']
            ?? null;

        $accountReference =
            $accountResponse['data']['account_reference']
            ?? null;

        if (!$virtualAccountNumber) {

            logInfo(
                $accountResponse,
                "Account Creation Missing Virtual Account Number"
            );

            return [
                'status'  => false,
                'message' => 'Virtual account was created but no account number was returned.',
                'data'    => $accountResponse,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Save user verification/account details
        |--------------------------------------------------------------------------
        */

        $user->bvn = (string) $bvn;

        $user->bvn_verified_at = now();

        $user->virtual_account =
            $virtualAccountNumber;

        $user->account_reference =
            $accountReference;

        $user->save();


        /*
        |--------------------------------------------------------------------------
        | 14. Return success
        |--------------------------------------------------------------------------
        */

        return [
            'status'  => true,
            'message' => 'BVN verified and virtual account created successfully.',
            'data'    => [
                'virtual_account' =>
                    $user->virtual_account,

                'account_reference' =>
                    $user->account_reference,

                'bvn_verified_at' =>
                    $user->bvn_verified_at,
            ],
        ];

    } catch (\Exception $e) {

        logInfo([
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ], "BVN Account Creation Exception Error");

        return [
            'status'  => false,
            'message' => 'An error occurred during account creation.',
            'data'    => [
                'error' => $e->getMessage(),
            ],
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
