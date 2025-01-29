<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class back_date_statuses extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $default = [

            ['back_date_status' => 'Off', 'created_at' => now(), 'updated_at' => now()],
            ['back_date_status' => 'On','created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('back_date_statuses')->insert($default);
    }
}
