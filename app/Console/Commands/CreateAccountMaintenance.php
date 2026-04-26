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
        try {

            $getCompanies = $this->getCompanies();
            foreach ($getCompanies as $company) {
                $this->createMaintenanceAccount($company);

            }

        } catch (\Exception $e) {

            \logInfo(["error" => 'Message: ' . $e->getMessage() . 'Line: ' . $e->getLine()], 'error logg');
        }

    }
    public function getCompanies()
    {

        $companies = Company::all();
        return $companies;
    }

    public function createMaintenanceAccount($company)
    {

        $admin             = $company->allStaff()->first();
        $requestReference  = generateAccountReference();
        $trackingReference = generateAccountReference();
        $email             = $admin->email;
        $phoneNumber       = $admin->email;
        $firstName         = $admin->first_name;
        $lastName          = $admin->last_name;

        $kudaController = new KudaController();

        $createAccountService = $kudaController->createMaintenanceAccount($requestReference, $email, $phoneNumber, $firstName, $lastName, $trackingReference);

        if ($createAccountService["status"]) {

            $company->wallet->subscription_vaccount = $createAccountService["data"]["account_number"];
            $company->wallet->save();

        }

        \logInfo(json_encode($createAccountService), "create Account Maintenance was successful");

    }

}
