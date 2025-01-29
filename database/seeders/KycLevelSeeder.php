<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KycLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['daily_transaction_limit' => "50000", 'maximum_balance' => "300000", 'requirements' => "BVN Validation", "created_at" => Carbon::now()],
            ['daily_transaction_limit' => "200000", 'maximum_balance' => "500000", 'requirements' => "BVN Validation, Photograph and National ID card", "created_at" => Carbon::now()],
            ['daily_transaction_limit' => "50000000", 'maximum_balance' => "0", 'requirements' => "BVN Validation, National ID card and Proof of Address (utility bill)", "created_at" => Carbon::now()],
        ];

        DB::table('kyc_levels')->insert($default);
    }
}
