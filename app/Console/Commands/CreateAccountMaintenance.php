<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Models\Company;
use Illuminate\Console\Command;

class CreateAccountMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accountmaintenance:create';

    /**
     * The console command description.
     */
    protected $description = 'Create missing company subscription maintenance accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription maintenance account backfill...');

        $this->getCompanies()->each(function ($company) {
            try {
                $this->createMaintenanceAccount($company);
            } catch (\Throwable $e) {
                $this->error("Company {$company->id}: {$e->getMessage()}");

                \logInfo([
                    'company_id' => $company->id,
                    'error'      => $e->getMessage(),
                    'line'       => $e->getLine(),
                ], 'maintenance account error');
            }
        });

        $this->info('Subscription maintenance account backfill completed.');

        return self::SUCCESS;
    }

    public function getCompanies()
    {
        return Company::with('wallet', 'allStaff')
            ->whereHas('wallet', function ($query) {
                $query->whereNull('subscription_vaccount')
                    ->orWhere('subscription_vaccount', '');
            })
            ->select('id')
            ->cursor();
    }

public function createMaintenanceAccount($company)
{
    $company = Company::with('wallet')->find($company->id);

    if (! $company) {
        $this->warn("Company not found.");
        return false;
    }

    $wallet = $company->wallet;

    if (! $wallet) {
        $this->warn("Company {$company->id}: wallet not found");

        \logInfo([
            'company_id' => $company->id,
            'error'      => 'Company wallet not found',
        ], 'maintenance account');

        return false;
    }

    if (! empty($wallet->subscription_vaccount)) {
        $this->line(
            "Company {$company->id}: subscription account already exists - {$wallet->subscription_vaccount}"
        );

        return true;
    }

    $this->line(
        "Company {$company->id}: creating subscription account..."
    );

    $requestReference  = generateAccountReference();
    $trackingReference = generateAccountReference();

    $kudaService = app(KudaController::class);

    $response = $kudaService->createMaintenanceAccount(
        $requestReference,
        null,
        null,
        $company->short_name,
        '',
        $trackingReference
    );

    if (! ($response['status'] ?? false)) {
        $message = $response['message'] ?? 'Unknown Kuda error';

        $this->error(
            "Company {$company->id}: Kuda failed - {$message}"
        );

        \logInfo([
            'company_id' => $company->id,
            'response'   => $response,
        ], 'maintenance account failed');

        return false;
    }

    $accountNumber = $response['data']['account_number'] ?? null;

    if (! $accountNumber) {
        $this->error(
            "Company {$company->id}: Kuda returned no account number"
        );

        return false;
    }

    $wallet->subscription_vaccount = $accountNumber;
    $wallet->save();

    $this->info(
        "Company {$company->id}: subscription account created - {$accountNumber}"
    );

    \logInfo([
        'company_id'            => $company->id,
        'subscription_vaccount' => $accountNumber,
    ], 'maintenance account created successfully');

    return true;
}

    public function createSudoMaintenanceAccountSafeHaven($company)
    {
        $company = Company::with('allStaff')->find($company->id);

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'No admin found',
            ], 'maintenance account');

            return;
        }

        if ($admin->bvn == null || $admin->bvn == " ") {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'No BVN found',
            ], 'maintenance account');

            return;
        }

        if ($company->id == 14) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'cant do this',
            ], 'maintenance account');

            return;
        }

        if ($company->wallet->card_vaccount != null) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'Account already created',
            ], 'maintenance account');

            return;
        }

        $len = strlen($admin->bvn);

        if ($len < 11) {
            \logInfo([
                'company_id' => $company->id,
                'error'      => 'BVN Wrong format',
            ], 'maintenance account');

            return;
        }

        $userInfo = $this->verifyWithIdentityPass($admin->bvn);
        $dob      = $userInfo['data']['dob'];
        $phone    = $userInfo['data']['phone'];

        $transaction = $this->safeHavenIdentityCheckResponse($admin->bvn, 14);
        $bvnId       = $transaction['data']['id'];

        $requestReference = generateAccountReference();

        $safeHavenService = app(SafehavenController::class);

        $response = $safeHavenService->createVirtualAccountMaintenance(
            $company->short_name,
            "",
            $admin->email,
            $phone,
            $requestReference,
            $admin->bvn,
            $dob,
            $bvnId,
            14
        );

        if (! ($response['status'] ?? false)) {
            \logInfo($response, "Maintenance account failed");
            return;
        }

        $wallet = $company->wallet;

        $wallet->card_vaccount = $response["data"]["accountNumber"];
        $wallet->save();

        \logInfo(
            $response,
            "Maintenance Sudo account created successfully"
        );
    }

    public function safeHavenIdentityCheckResponse($bvn, $company_id)
    {
        $safehavenController = app(SafehavenController::class);

        $bvnResponse = $safehavenController->verifyBVN($bvn, $company_id);

        if ($bvnResponse['status']) {
            return [
                'status'  => true,
                'message' => 'Account Verified Successfully',
                'data'    => [
                    'id'             => $bvnResponse['data']['_id'],
                    'clientId'       => $bvnResponse['data']['clientId'],
                    'identityNumber' => $bvnResponse['data']['identityNumber'],
                    'type'           => $bvnResponse['data']['type'],
                    'otpId'          => trim($bvnResponse['data']['otpId']),
                    'message'        => trim($bvnResponse['message']),
                ],
            ];
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed to verify BVN',
        ], 400);
    }

    public function verifyWithIdentityPass($bvn)
    {
        $identityPassController = app(IdentityPassController::class);

        $bvnResponse = $identityPassController->verifyBVNMobile($bvn);

        if (
            isset($bvnResponse['data']['verification']['status']) &&
            $bvnResponse['data']['verification']['status'] == 'VERIFIED'
        ) {
            $firstName       = $bvnResponse['data']['data']['firstName'];
            $lastName        = $bvnResponse['data']['data']['lastName'];
            $dob             = $bvnResponse['data']['data']['dateOfBirth']
                ?? $bvnResponse['data']['data']['dob'];
            $phone           = $bvnResponse['data']['data']['phoneNumber']
                ?? null;
            $address         = $bvnResponse['data']['data']['residentialAddress']
                ?? null;

            return [
                'status'  => true,
                'message' => 'Account Verified Successfully',
                'data'    => [
                    'first_name' => trim($firstName),
                    'last_name'  => trim($lastName),
                    'dob'        => $dob,
                    'bvn'        => trim($bvn),
                    'phone'      => trim($phone),
                    'address'    => trim($address),
                ],
            ];
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed to verify BVN',
        ], 400);
    }
}
