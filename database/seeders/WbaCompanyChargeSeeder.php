<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WbaCompanyChargeSeeder extends Seeder
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
            'incoming_transfer_charge_percentage' => 1,
            'incoming_transfer_charge_capped_at' => 1000,
            'cable_tv_charge' => 10,
            'electricity_charge' => 30,
            'ussd_charge' => 5,
            'whatsapp_charge' => 20,
            'sms_charge' => 2,
            'pos_charge_percent' => 0.7,
            'pos_charge_cap' => 85,
            'new_physical_card_charge' => 1000,
            'physical_card_maintenance_charge' => 50,
            'crc_credit_bureau_charge' => 100.0,
            'first_central_search_charge' => 150.0,
            'eco_system_search_charge' => 50.0,
            'karma_search_charge' => 50.0,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('wba_company_charges')->insert($data);
    }
}
