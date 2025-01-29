<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class currencies extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $default = [

            ['name' => 'Naira', 'code' => "NGN", 'country_id' => 1, 'symbol' => '₦', "charge_by" => '1', 'charge_percentage' => NULL, 'capped_at' => NULL, 'charge_amount' => '100', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kwacha', 'code' => "ZMW", 'country_id' => 2, 'symbol' => 'K', "charge_by" => '0', 'charge_percentage' => "0.03", 'capped_at' => NULL, 'charge_amount' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'US Dollars', 'code' => "USD", 'country_id' => 3, 'symbol' => '$', "charge_by" => '0', 'charge_percentage' => "0.01", 'capped_at' => NULL, 'charge_amount' => '100', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Congolese Franc', 'code' => "CDF", 'country_id' => 11, 'symbol' => 'FC', "charge_by" => '0', 'charge_percentage' => "10", 'capped_at' => NULL, 'charge_amount' => NULL, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ghanian Cedis', 'code' => "GHS", 'country_id' => 4, 'symbol' => '₵', "charge_by" => '1', 'charge_percentage' => NULL, 'capped_at' => NULL, 'charge_amount' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ugandan Shilling', 'code' => "UGX", 'country_id' => 5, 'symbol' => 'USh', "charge_by" => '1', 'charge_percentage' => NULL, 'capped_at' => NULL, 'charge_amount' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tanzanian Shilling', 'code' => "TZS", 'country_id' => 6, 'symbol' => 'TSh', "charge_by" => '1', 'charge_percentage' => NULL, 'capped_at' => NULL, 'charge_amount' => 5, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('currencies')->insert($default);
    }
}
