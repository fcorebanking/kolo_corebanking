<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SavingsProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $products = [
            [
                'savings_product' => 'Savings Account',
                'company_id' => '1',
                'frequency_id' => '1',
                'interest_rate' => '5.5',
                'minimum_withdraw' => '100',
                'maximum_deposit' => '100000',
                'maintenance_charges' => '200',
                'created_by' => 4,
                'modified_by' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('savings_products')->insert($products);
    }
}
