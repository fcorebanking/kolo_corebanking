<?php
namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\BridgeController;
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
            "number"              => "22460591185",
            "issuing_country"     => "NGA",
            "type"                => "national_id",
            "description"         => "",
            "signed_agreement_id" => "b1c2fd11-006f-4406-8039-aa5debda6d61",
            "postal_code"         => "100212",
            "expiration"          => "2027-01-31",
            "image_front"         => "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAoHBwgHBgoICAkLCgkLDhgQDg0NDh0VFhEYIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDg0OGhAQGi0lHyYtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAWgB4AMBIgACEQEDEQH/xAAbAAACAgMBAAAAAAAAAAAAAAAFBgMEAAIHB//EAD8QAAEDAgMFBQYEBQUBAAAAAAECAwQFEQASIQYxQVEHEyJhcYGRFDKhsdHwBxRCUmJy8RUjYnLxFf/EABkBAAMBAQEAAAAAAAAAAAAAAAABAgMEBf/EACURAQEAAgICAgMBAAAAAAAAAAABAhEDIRIxBBMiQVEUcf/aAAwDAQACEQMRAD8A9WiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//Z",
            "image_back"          => "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAoHBwgHBgoICAkLCgkLDhgQDg0NDh0VFhEYIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDg0OGhAQGi0lHyYtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAWgB4AMBIgACEQEDEQH/xAAbAAACAgMBAAAAAAAAAAAAAAAFBgMEAAIHB//EAD8QAAEDAgMFBQYEBQUBAAAAAAECAwQFEQASIQYxQVEHEyJhcYGRFDKhsdHwBxRCUmJy8RUjYnLxFf/EABkBAAMBAQEAAAAAAAAAAAAAAAABAgMEBf/EACURAQEAAgICAgMBAAAAAAAAAAABAhEDIRIxBBMiQVEUcf/aAAwDAQACEQMRAD8A9WiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//Z",
        ];
        try {
            $bridgeController      = new BridgeController();
            $bridgeCustomerAccount = $bridgeController->createCompanyAccount($customer, $data);
            logInfo(json_encode($bridgeCustomerAccount), "bridgeCompanyAccount Result");
        } catch (\Throwable $e) {
            \logInfo([
                "company_id" => $company->id,
                "error"      => $e->getMessage(),
                "line"       => $e->getLine(),
            ], 'createCompanyAccount account error');
        }

    }

}
