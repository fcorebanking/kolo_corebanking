<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyChargesSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'bank_transfer_charge' => 20,
            'cable_tv_charge' => 10,
            'electricity_charge' => 30,
            'ussd_charge' => 5,
            'whatsapp_charge' => 20,
            'sms_charge' => 2,
            'new_physical_card_charge' => 1000,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('wba_company_charges')->insert($data);
    }
}
