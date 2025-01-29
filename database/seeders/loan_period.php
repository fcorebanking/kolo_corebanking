<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
class loan_period extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['name' => 'Daily','days'=>1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Weekly','days'=>7, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bi-Weekly','days'=>14, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monthly','days'=>30, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bi-Monthly','days'=>60, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Quarterly','days'=>90, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Every 4 Months','days'=>120, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Semi-Annual','days'=>180, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Every 9 Months','days'=>270, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Yearly','days'=>360, 'created_at' => now(), 'updated_at' => now()],
            // ['name' => 'Lump-Sum','days'=>720, 'created_at' => now(), 'updated_at' => now()],
            ];
            DB::table('loan_periods')->insert($default);
    }
}
