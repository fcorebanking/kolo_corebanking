<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreferredEbankingPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transactionTypes = [
            ['ebanking_service' => 'Bank Account Verification', 'ebanking_partners_id' => 1, 'created_at' => now()],
            ['ebanking_service' => 'Bank Transfer', 'ebanking_partners_id' => 1, 'created_at' => now()],
            ['ebanking_service' => 'Airtime and Data', 'ebanking_partners_id' => 1, 'created_at' => now()],
            ['ebanking_service' => 'International Airtime and Data', 'ebanking_partners_id' => 1, 'created_at' => now()]
        ];

        DB::table('preferred_ebanking_partners')->insert($transactionTypes);
    }
}
