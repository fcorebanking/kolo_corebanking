<?php

namespace Database\Seeders;

use App\Models\company_wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $companyWallets = [
            [
                "company_id" => 1,
                "balance" => 0.0,
                "sms" => 0,
                "ussd" => 0,
                "bank_id" => "1",
                "vaccount" => "7199871772",
                "refaccount" => "FHZR1JDNrvIsfAoxWgnO",
                "sms_type_id" => 1,
                "withdraw_charge" => 0.0,
                "sms_charge" => 0.0,
                "ussd_charge" => 0.0,
                "modified_by" => "1",
            ],
            [
                "company_id" => 2,
                "balance" => 0.0,
                "sms" => 0,
                "ussd" => 0,
                "bank_id" => "1",
                "vaccount" => "7199986801",
                "refaccount" => "isRJmvfO502GjGCDqZKz",
                "sms_type_id" => 1,
                "withdraw_charge" => 0.0,
                "sms_charge" => 0.0,
                "ussd_charge" => 0.0,
                "modified_by" => "2",
            ],
            [
                "company_id" => 3,
                "balance" => 0.0,
                "sms" => 0,
                "ussd" => 0,
                "bank_id" => "1",
                "vaccount" => "7199990246",
                "refaccount" => "rgucF29cMaJzNCZRGpjX",
                "sms_type_id" => 1,
                "withdraw_charge" => 0.0,
                "sms_charge" => 0.0,
                "ussd_charge" => 0.0,
                "modified_by" => "3",
               
            ]

        ];

        $user = DB::table('company_wallets')->insert($companyWallets);
    }
}
