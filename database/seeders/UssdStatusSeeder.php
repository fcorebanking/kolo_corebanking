<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UssdStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['ussd_status' => 'Allow All', 'created_at' => now(), 'updated_at' => now()],
            ['ussd_status' => 'Disabled', 'created_at' => now(), 'updated_at' => now()],
            ['ussd_status' => 'Balance Check', 'created_at' => now(), 'updated_at' => now()],
          ];
      
          DB::table('ussd_status')->insert($default);
    }
}
