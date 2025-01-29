<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $companies = [
            [
                'company_id' => '1',
                'teller_option_id' => '1',
                'company_type_id' => '1',
                'esusu_type_id' => '1',
                'plan_id' => '1',
                'auto_renewal_id' => '1',
                'date_control' => '1',
                'sms_charge' => '10',
                'maintenance_change_amount' => '100',
                'sms_charge_frequencie_id' => '1',
                'withdraw_charge_state' => '1',
                'incoming_transfer_charge_amount' => '50',
                'investment_termination_control' => 'investment_termination_control',
                'new_physical_card_charge' => '1000',
                'physical_card_maintenance_charge' => '50',
                'whatsapp_control' => '1',
                'whatsapp_charge' => '3',
                'preferred_bank_id' => '222',
                'additional_pos_charge_percent' => '1.5',
                'pos_charge_capped_at' => '50',
                'dom_account_duration' => '30',
                'min_withdraw_charge' => '100',
                'max_withdraw_charge' => '20000',
                'charge_customer_maintenance_id' => '1',
                'teller_commission' => '100',
                'ussd_charge' => '2',
                'minimum_balance' => '100',
                'ussd_control' => '1',
                'allsms_state' => '1',
                'min_transfer_amount' => '100',
                'registration_fee' => '100',
                'max_localtransfer_amount' => '50000',
                'max_onlinetransfer_amount' => '50000',
                'daily_transfer_limit_count' => '30000',             
                'back_date_status_id' => '1',
                'smsapi_id' => '1',
                "created_at" => Carbon::now()
            ]
        ];

        $user = DB::table('company_settings')->insert($companies);
    }
}
