<?php
namespace App\Console\Commands;

use App\Http\Controllers\ThirdParty\SafehavenController;
use Illuminate\Console\Command;

class UpdateAccountSafeHaven extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accountcreation:update';

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
        $safeHavenController = new SafehavenController();
        $id                  = "69f8a83a8aa9320024d3d435";
        $email               = "digitalfinancialservices1@gmail.com";
        $phone               = "07062777542";
        $customerReference   = generateAccountReference();
        $bvn                 = "22290745937";
        $companyID           = 97;
        return $safeHavenController->updateVirtualAccount($id, $email, $phone, $customerReference, $bvn, $companyID);

    }

}
