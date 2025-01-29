<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['user_type' => 'Customer', 'created_at' => now(), 'updated_at' => now()],
            ['user_type' => 'Company', 'created_at' => now(), 'updated_at' => now()],
            ['user_type' => 'Cooperate', 'created_at' => now(), 'updated_at' => now()],
            ['user_type' => 'Virtual Account', 'created_at' => now(), 'updated_at' => now()],
          ];
      
          DB::table('user_types')->insert($default);
    }
}
