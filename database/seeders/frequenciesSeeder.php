<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class frequenciesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $default = [

 
      ['frequency' => 'Day', 'created_at' => now(), 'updated_at' => now()],
      ['frequency' => 'Week', 'created_at' => now(), 'updated_at' => now()],
      ['frequency' => 'Month', 'created_at' => now(), 'updated_at' => now()],
      ['frequency' => 'Year', 'created_at' => now(), 'updated_at' => now()],
    ];

    DB::table('frequencies')->insert($default);
  }
}
