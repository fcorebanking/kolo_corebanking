<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestrictionLogTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [
            ['restriction_log_type' => 'Added Restriction', 'created_at' => now(), 'updated_at' => now()],
            ['restriction_log_type' => 'Removed Restriction', 'created_at' => now(), 'updated_at' => now()]
        ];

        DB::table('restriction_log_types')->insert($default);
    }
}
