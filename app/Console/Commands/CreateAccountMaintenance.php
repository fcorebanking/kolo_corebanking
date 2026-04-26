<?php
namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\KudaController;
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
                $this->createMaintenanceAccount($company);
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

}
