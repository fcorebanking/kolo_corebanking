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
     *
     * @var string
     */
    protected $signature = 'accountmaintenance:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create Account Maintenance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->getCompanies()->each(function ($company) {
            try {
                //  $this->createMaintenanceAccount($company);
                $this->createSudoMaintenanceAccountSafeHaven($company);
            } catch (\Throwable $e) {
                \logInfo([
                    "company_id" => $company->id,
                    "error"      => $e->getMessage(),
                    "line"       => $e->getLine(),
                ], 'maintenance account error');
            }
        });

    }
    public function getCompanies()
    {

        return Company::select('id') // ✅ optimized
            ->cursor();
    }

    public function createMaintenanceAccount($company)
    {
        $company = Company::with('allStaff')->find($company->id);

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo(["company_id" => $company->id, "error" => "No admin found"], 'maintenance account');
            return;
        }

        $requestReference  = generateAccountReference();
        $trackingReference = generateAccountReference();

        $kudaService = app(KudaController::class); // better than new

        $response = $kudaService->createMaintenanceAccount(
            $requestReference,
            $admin->email,
            $admin->phone ?? $admin->email,
            $company->short_name,
            "",
            $trackingReference
        );

        if (! ($response['status'] ?? false)) {
            \logInfo($response, "Maintenance account failed");
            return;
        }

        $wallet = $company->wallet;

        $wallet->subscription_vaccount = $response["data"]["account_number"];
        $wallet->save();

        \logInfo($response, "Maintenance account created successfully");

    }

    public function createSudoMaintenanceAccountSafeHaven($company)
    {
        $company = Company::with('allStaff')->find($company->id);

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo(["company_id" => $company->id, "error" => "No admin found"], 'maintenance account');
            return;
        }

        if ($admin->bvn == null || $admin->bvn == " ") {
            \logInfo(["company_id" => $company->id, "error" => "No BVN found"], 'maintenance account');
            return;
        }

        if ($company->id == 14) {
            \logInfo(["company_id" => $company->id, "error" => "cant do this "], 'maintenance account');
            return;
        }

        if ($company->wallet->card_vaccount != null) {
            \logInfo(["company_id" => $company->id, "error" => "Account already created "], 'maintenance account');
            return;
        }

        $len = strlen($admin->bvn);
        if ($len < 11) {
            \logInfo(["company_id" => $company->id, "error" => "BVN Wrong format "], 'maintenance account');
            return;
        }

        $userInfo = $this->verifyWithIdentityPass($admin->bvn);
        $dob      = $userInfo['data']['dob'];
        $phone    = $userInfo['data']['phone'];

        $transaction = $this->safeHavenIdentityCheckResponse($admin->bvn, 14);
        $bvnId       = $transaction['data']['id'];

        $requestReference = generateAccountReference();

        $safeHavenService = app(SafehavenController::class); // better than new

        $response = $safeHavenService->createVirtualAccountMaintenance(
            $company->short_name, //firstName
            "",                   //lastName
            $admin->email,
            $phone,
            $requestReference,
            $admin->bvn,
            $dob,
            $bvnId,
            14);

        if (! ($response['status'] ?? false)) {
            \logInfo($response, "Maintenance account failed");
            return;
        }

        $wallet = $company->wallet;

        $wallet->card_vaccount = $response["data"]["accountNumber"];
        $wallet->save();

        \logInfo($response, "Maintenance Sudo account created successfully");

    }

    public function safeHavenIdentityCheckResponse($bvn, $company_id)
    {

        $safehavenController = app(SafehavenController::class);
        $bvnResponse         = $safehavenController->verifyBVN($bvn, $company_id);

        if ($bvnResponse['status']) {
            return
                [
                'status'  => true,
                'message' => "Account Verified Successfully",
                'data'    => [
                    'id'             => $bvnResponse['data']['_id'],
                    'clientId'       => $bvnResponse['data']['clientId'],
                    "identityNumber" => $bvnResponse['data']['identityNumber'],
                    "type"           => $bvnResponse['data']['type'],
                    'otpId'          => trim($bvnResponse['data']['otpId']),
                    "message"        => trim($bvnResponse['message']),

                ],

            ];
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify BVN",
                ],
                400
            );
        }
    }

    public function verifyWithIdentityPass($bvn)
    {
        $identityPassController = app(IdentityPassController::class);

        $bvnResponse = $identityPassController->verifyBVNMobile($bvn);

        if (isset($bvnResponse['data']['verification']['status']) && $bvnResponse['data']['verification']['status'] == "VERIFIED") {
            $firstName       = $bvnResponse['data']['data']["firstName"];
            $lastName        = $bvnResponse['data']['data']["lastName"];
            $dob             = $bvnResponse['data']['data']["dateOfBirth"] ?? $bvnResponse['data']['data']["dob"];
            $bvnFromResponse = $bvn;
            $phone           = $bvnResponse['data']['data']["phoneNumber"] ?? null;
            $address         = $bvnResponse['data']['data']["residentialAddress"] ?? null;

            return [
                'status'  => true,
                'message' => "Account Verified Successfully",
                'data'    => [
                    'first_name' => trim($firstName),
                    "last_name"  => trim($lastName),
                    "dob"        => $dob,
                    "bvn"        => trim($bvnFromResponse),
                    "phone"      => trim($phone),
                    "address"    => trim($address),
                ],
            ];

        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify BVN",
                ],
                400
            );
        }
    }

}
