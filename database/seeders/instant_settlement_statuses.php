<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class instant_settlement_statuses extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['instant_settlement_status' => 'On', 'created_at' => now(), 'updated_at' => now()],
            ['instant_settlement_status' => 'Off','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('instant_settlement_statuses')->insert($default);
    }
}
