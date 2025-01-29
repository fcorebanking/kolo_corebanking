<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class charge_customer_statuses extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['charge_customer_status' => 'Instant', 'created_at' => now(), 'updated_at' => now()],
            ['charge_customer_status' => 'weekly','created_at' => now(), 'updated_at' => now()],
            ['charge_customer_status' => 'monthly','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('charge_customer_statuses')->insert($default);
    }
}
