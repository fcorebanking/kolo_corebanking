<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EbankingPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transactionTypes = [
            ['ebanking_service' => 'Kuda', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Woven', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'VTPass', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Asset Matrix', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'VFD', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'GTBank', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Cgate', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Dojah', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Errandpay', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Providus', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Monnify', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Reloadly', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Safe Haven', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
            ['ebanking_service' => 'Fairmoney', 'account_verification_enabled' => 0, 'bank_transfer_enabled' => 0, 'airtime_data_enabled' => 0, 'cable_tv_enabled' => 0, 'electricity_enabled' => 0],
        ];

        DB::table('ebanking_partners')->insert($transactionTypes);
    }
}
