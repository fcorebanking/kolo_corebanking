<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RequestMethodSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

 
            ['request_method' => 'POST', 'created_at' => now(), 'updated_at' => now()],
            ['request_method' => 'GET', 'created_at' => now(), 'updated_at' => now()],
          
          ];
      
          DB::table('request_methods')->insert($default);
    }
}
