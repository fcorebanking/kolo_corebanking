<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class Company_status extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            ['name' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Disable','created_at' => now(), 'updated_at' => now()],
          
        ];

        DB::table('company_statuses')->insert($default);
    }
}
