<?php
namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\BridgeController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Models\Company;
use Illuminate\Console\Command;

class CreateBridgeCompanyAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'companyaccountbridge:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create Bridge Account Company';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id      = 69;
        $company = Company::where('id', $id)->first();

        //"image_front":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mP8z8BQz0AEYBxVSF+FABJADveax8o1AAAAAElFTkSuQmCC",
        // "image_back":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mP8z8BQz0AEYBxVSF+FABJADveax8o1AAAAAElFTkSuQmCC",
        $customer = $company->allStaff->first();
        $data     = [
            "number"          => "22460591185",
            "issuing_country" => "NGA",
            "type"            => "national_id",
            "description"     => "",
            "postal_code"     => "100212",
            "expiration"      => "2027-01-31",
            "image_front"     => "",
            "image_back"      => "",
        ];
        try {
            $bridgeController      = new BridgeController();
            $bridgeCustomerAccount = $bridgeController->createCompanyAccount($customer, $data);
        } catch (\Throwable $e) {
            \logInfo([
                "company_id" => $company->id,
                "error"      => $e->getMessage(),
                "line"       => $e->getLine(),
            ], 'createCompanyAccount account error');
        }

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
